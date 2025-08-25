<?php
// SROI Ex-post Analysis Functions

/**
 * ดึงข้อมูลโครงการทั้งหมดของผู้ใช้
 */
function getUserProjects($conn, $user_id) {
    $projects_query = "SELECT id, project_code, name, status, budget, created_at, updated_at 
                      FROM projects 
                      WHERE created_by = ? 
                      ORDER BY updated_at DESC";
    $projects_stmt = mysqli_prepare($conn, $projects_query);
    mysqli_stmt_bind_param($projects_stmt, 's', $user_id);
    mysqli_stmt_execute($projects_stmt);
    $projects_result = mysqli_stmt_get_result($projects_stmt);
    $projects = mysqli_fetch_all($projects_result, MYSQLI_ASSOC);
    mysqli_stmt_close($projects_stmt);
    
    return $projects;
}

/**
 * ดึงข้อมูลโครงการเฉพาะ
 */
function getProjectById($conn, $project_id, $user_id) {
    $project_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
    $project_stmt = mysqli_prepare($conn, $project_query);
    mysqli_stmt_bind_param($project_stmt, 'is', $project_id, $user_id);
    mysqli_stmt_execute($project_stmt);
    $project_result = mysqli_stmt_get_result($project_stmt);
    $project = mysqli_fetch_assoc($project_result);
    mysqli_stmt_close($project_stmt);
    
    return $project;
}

/**
 * ดึงข้อมูลต้นทุนโครงการ
 */
function getProjectCosts($conn, $project_id) {
    $costs_query = "SELECT cost_name, yearly_amounts FROM project_costs WHERE project_id = ? ORDER BY id ASC";
    $costs_stmt = mysqli_prepare($conn, $costs_query);
    mysqli_stmt_bind_param($costs_stmt, 'i', $project_id);
    mysqli_stmt_execute($costs_stmt);
    $costs_result = mysqli_stmt_get_result($costs_stmt);
    
    $costs = [];
    while ($cost_row = mysqli_fetch_assoc($costs_result)) {
        $yearly_data = json_decode($cost_row['yearly_amounts'], true);
        $costs[] = [
            'name' => $cost_row['cost_name'],
            'amounts' => $yearly_data ? $yearly_data : []
        ];
    }
    mysqli_stmt_close($costs_stmt);
    
    return $costs;
}

/**
 * ดึงข้อมูลผลประโยชน์โครงการ
 */
function getProjectBenefits($conn, $project_id) {
    $benefits = [];
    $benefit_notes_by_year = [];
    $base_case_factors = []; // เก็บ attribution, deadweight, displacement
    $benefit_counter = 0;
    $processed_benefits = []; // ป้องกันข้อมูลซ้ำ
    
    // ดึงข้อมูลจาก project_impact_ratios (Legacy system)
    $legacy_benefits_query = "SELECT benefit_number, benefit_detail, beneficiary, benefit_note, year, attribution, deadweight, displacement, 'legacy' as source_type
                              FROM project_impact_ratios 
                              WHERE project_id = ? AND benefit_detail IS NOT NULL AND benefit_detail != '' 
                              ORDER BY benefit_number ASC, year ASC";
    $legacy_stmt = mysqli_prepare($conn, $legacy_benefits_query);
    mysqli_stmt_bind_param($legacy_stmt, 'i', $project_id);
    mysqli_stmt_execute($legacy_stmt);
    $legacy_result = mysqli_stmt_get_result($legacy_stmt);
    
    while ($benefit_row = mysqli_fetch_assoc($legacy_result)) {
        $benefit_key = $benefit_row['benefit_detail'] . '_' . $benefit_row['beneficiary'] . '_legacy';
        $year = $benefit_row['year'];
        
        // ถ้ายังไม่มีผลประโยชน์นี้ ให้เพิ่มใหม่
        if (!isset($processed_benefits[$benefit_key])) {
            $benefit_counter++;
            $benefits[$benefit_counter] = [
                'detail' => $benefit_row['benefit_detail'],
                'beneficiary' => $benefit_row['beneficiary'],
                'source_type' => $benefit_row['source_type']
            ];
            $processed_benefits[$benefit_key] = $benefit_counter;
        }
        
        $current_benefit_counter = $processed_benefits[$benefit_key];
        
        // เก็บ benefit_note ตามปีและ benefit_counter
        if (!isset($benefit_notes_by_year[$current_benefit_counter])) {
            $benefit_notes_by_year[$current_benefit_counter] = [];
        }
        $benefit_notes_by_year[$current_benefit_counter][$year] = $benefit_row['benefit_note'];
        
        // เก็บ base case factors ตามปีและ benefit_counter
        if (!isset($base_case_factors[$current_benefit_counter])) {
            $base_case_factors[$current_benefit_counter] = [];
        }
        $base_case_factors[$current_benefit_counter][$year] = [
            'attribution' => floatval($benefit_row['attribution']),
            'deadweight' => floatval($benefit_row['deadweight']),
            'displacement' => floatval($benefit_row['displacement'])
        ];
    }
    mysqli_stmt_close($legacy_stmt);
    
    // ดึงข้อมูลจาก impact_chain_ratios (New chain system)
    $new_benefits_query = "SELECT icr.benefit_number, icr.benefit_detail, icr.beneficiary, icr.benefit_note, icr.year, icr.attribution, icr.deadweight, icr.displacement, 'new_chain' as source_type
                          FROM impact_chain_ratios icr
                          INNER JOIN impact_chains ic ON icr.impact_chain_id = ic.id
                          WHERE ic.project_id = ? AND icr.benefit_detail IS NOT NULL AND icr.benefit_detail != ''
                          ORDER BY icr.benefit_number ASC, icr.year ASC";
    $new_stmt = mysqli_prepare($conn, $new_benefits_query);
    mysqli_stmt_bind_param($new_stmt, 'i', $project_id);
    mysqli_stmt_execute($new_stmt);
    $new_result = mysqli_stmt_get_result($new_stmt);
    
    while ($benefit_row = mysqli_fetch_assoc($new_result)) {
        $benefit_key = $benefit_row['benefit_detail'] . '_' . $benefit_row['beneficiary'] . '_new_chain';
        $year = $benefit_row['year'];
        
        // ถ้ายังไม่มีผลประโยชน์นี้ ให้เพิ่มใหม่
        if (!isset($processed_benefits[$benefit_key])) {
            $benefit_counter++;
            $benefits[$benefit_counter] = [
                'detail' => $benefit_row['benefit_detail'],
                'beneficiary' => $benefit_row['beneficiary'],
                'source_type' => $benefit_row['source_type']
            ];
            $processed_benefits[$benefit_key] = $benefit_counter;
        }
        
        $current_benefit_counter = $processed_benefits[$benefit_key];
        
        // เก็บ benefit_note ตามปีและ benefit_counter
        if (!isset($benefit_notes_by_year[$current_benefit_counter])) {
            $benefit_notes_by_year[$current_benefit_counter] = [];
        }
        $benefit_notes_by_year[$current_benefit_counter][$year] = $benefit_row['benefit_note'];
        
        // เก็บ base case factors ตามปีและ benefit_counter
        if (!isset($base_case_factors[$current_benefit_counter])) {
            $base_case_factors[$current_benefit_counter] = [];
        }
        $base_case_factors[$current_benefit_counter][$year] = [
            'attribution' => floatval($benefit_row['attribution']),
            'deadweight' => floatval($benefit_row['deadweight']),
            'displacement' => floatval($benefit_row['displacement'])
        ];
    }
    mysqli_stmt_close($new_stmt);
    
    return [
        'benefits' => $benefits,
        'benefit_notes_by_year' => $benefit_notes_by_year,
        'base_case_factors' => $base_case_factors
    ];
}

/**
 * คำนวณ Net Present Value (NPV)
 */
function calculateNPV($cash_flows, $discount_rate) {
    $npv = 0;
    foreach ($cash_flows as $year => $amount) {
        $npv += $amount / pow(1 + $discount_rate, $year);
    }
    return $npv;
}

/**
 * คำนวณ SROI Ratio
 */
function calculateSROIRatio($total_benefits, $total_costs) {
    if ($total_costs == 0) return 0;
    return $total_benefits / $total_costs;
}

/**
 * คำนวณ Payback Period
 */
function calculatePaybackPeriod($costs, $benefits) {
    $cumulative_net_benefit = 0;
    
    foreach ($benefits as $year => $benefit) {
        $cost = isset($costs[$year]) ? $costs[$year] : 0;
        $net_benefit = $benefit - $cost;
        $cumulative_net_benefit += $net_benefit;
        
        if ($cumulative_net_benefit >= 0) {
            return $year;
        }
    }
    
    return null; // ไม่มี payback
}

/**
 * คำนวณ Sensitivity Analysis
 */
function calculateSensitivityAnalysis($base_sroi, $sensitivity_range) {
    return [
        'best_case' => $base_sroi * (1 + $sensitivity_range),
        'base_case' => $base_sroi,
        'worst_case' => $base_sroi * (1 - $sensitivity_range)
    ];
}

/**
 * จัดรูปแบบตัวเลข
 */
function formatNumber($number, $decimals = 2) {
    return number_format($number, $decimals, '.', ',');
}

/**
 * จัดรูปแบบเงิน
 */
function formatCurrency($amount, $decimals = 2) {
    return '฿' . number_format($amount, $decimals, '.', ',');
}

/**
 * แปลงวันที่เป็นรูปแบบไทย
 */
function formatThaiDate($date) {
    $thai_months = [
        '01' => 'ม.ค.', '02' => 'ก.พ.', '03' => 'มี.ค.', '04' => 'เม.ย.',
        '05' => 'พ.ค.', '06' => 'มิ.ย.', '07' => 'ก.ค.', '08' => 'ส.ค.',
        '09' => 'ก.ย.', '10' => 'ต.ค.', '11' => 'พ.ย.', '12' => 'ธ.ค.'
    ];
    
    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $thai_months[date('m', $timestamp)];
    $year = date('Y', $timestamp) + 543;
    
    return "$day $month $year";
}

/**
 * สร้างสีสำหรับกราฟ
 */
function generateChartColors($count) {
    $colors = [
        'rgba(102, 126, 234, 0.8)',
        'rgba(118, 75, 162, 0.8)',
        'rgba(86, 171, 47, 0.8)',
        'rgba(240, 147, 251, 0.8)',
        'rgba(245, 87, 108, 0.8)',
        'rgba(78, 205, 196, 0.8)'
    ];
    
    $result = [];
    for ($i = 0; $i < $count; $i++) {
        $result[] = $colors[$i % count($colors)];
    }
    
    return $result;
}
?>