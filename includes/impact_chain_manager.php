<?php
/**
 * ฟังก์ชันจัดการ Multiple Impact Chains
 * 
 * @author SROIV4 System
 * @created 2025-08-16
 */

require_once __DIR__ . '/impact_chain_status.php';

/**
 * สร้าง Impact Chain ใหม่
 * 
 * @param int $project_id รหัสโครงการ
 * @param int $activity_id รหัสกิจกรรม
 * @param string $created_by ผู้สร้าง
 * @return int|false ID ของ Impact Chain ที่สร้างใหม่ หรือ false หากเกิดข้อผิดพลาด
 */
function createImpactChain($project_id, $activity_id, $created_by) {
    global $conn;
    
    try {
        mysqli_autocommit($conn, false);
        
        // ดึงชื่อกิจกรรม
        $activity_query = "SELECT activity_name FROM activities WHERE activity_id = ?";
        $activity_stmt = mysqli_prepare($conn, $activity_query);
        mysqli_stmt_bind_param($activity_stmt, 'i', $activity_id);
        mysqli_stmt_execute($activity_stmt);
        $activity_result = mysqli_stmt_get_result($activity_stmt);
        $activity = mysqli_fetch_assoc($activity_result);
        mysqli_stmt_close($activity_stmt);
        
        if (!$activity) {
            throw new Exception("ไม่พบข้อมูลกิจกรรม");
        }
        
        // หาลำดับถัดไป
        $sequence_query = "SELECT COALESCE(MAX(sequence_order), 0) + 1 as next_sequence 
                          FROM impact_chains WHERE project_id = ?";
        $sequence_stmt = mysqli_prepare($conn, $sequence_query);
        mysqli_stmt_bind_param($sequence_stmt, 'i', $project_id);
        mysqli_stmt_execute($sequence_stmt);
        $sequence_result = mysqli_stmt_get_result($sequence_stmt);
        $sequence_row = mysqli_fetch_assoc($sequence_result);
        $next_sequence = $sequence_row['next_sequence'];
        mysqli_stmt_close($sequence_stmt);
        
        // สร้างชื่อ Impact Chain
        $chain_name = "Impact Chain {$next_sequence}: " . $activity['activity_name'];
        
        // สร้าง Impact Chain
        $insert_query = "INSERT INTO impact_chains (project_id, chain_name, activity_id, sequence_order, created_by) 
                        VALUES (?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 'issis', $project_id, $chain_name, $activity_id, $next_sequence, $created_by);
        
        if (!mysqli_stmt_execute($insert_stmt)) {
            throw new Exception("ไม่สามารถสร้าง Impact Chain ได้");
        }
        
        $chain_id = mysqli_insert_id($conn);
        mysqli_stmt_close($insert_stmt);
        
        // เพิ่มกิจกรรมลงใน impact_chain_activities
        $activity_insert_query = "INSERT INTO impact_chain_activities (impact_chain_id, activity_id, created_by) 
                                 VALUES (?, ?, ?)";
        $activity_insert_stmt = mysqli_prepare($conn, $activity_insert_query);
        mysqli_stmt_bind_param($activity_insert_stmt, 'iis', $chain_id, $activity_id, $created_by);
        
        if (!mysqli_stmt_execute($activity_insert_stmt)) {
            throw new Exception("ไม่สามารถเพิ่มกิจกรรมได้");
        }
        mysqli_stmt_close($activity_insert_stmt);
        
        // อัปเดตโปรเจค
        updateProjectChainInfo($project_id, $chain_id);
        
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        
        return $chain_id;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        error_log("Error creating impact chain: " . $e->getMessage());
        return false;
    }
}

/**
 * อัปเดตข้อมูล Impact Chain ในโปรเจค
 * 
 * @param int $project_id รหัสโครงการ
 * @param int $current_chain_id รหัส Impact Chain ปัจจุบัน
 */
function updateProjectChainInfo($project_id, $current_chain_id = null) {
    global $conn;
    
    // นับจำนวน Impact Chain
    $count_query = "SELECT COUNT(*) as total FROM impact_chains WHERE project_id = ? AND status = 'active'";
    $count_stmt = mysqli_prepare($conn, $count_query);
    mysqli_stmt_bind_param($count_stmt, 'i', $project_id);
    mysqli_stmt_execute($count_stmt);
    $count_result = mysqli_stmt_get_result($count_stmt);
    $count_row = mysqli_fetch_assoc($count_result);
    $total_chains = $count_row['total'];
    mysqli_stmt_close($count_stmt);
    
    // อัปเดตข้อมูลในตาราง projects
    $update_query = "UPDATE projects SET total_impact_chains = ?";
    $params = [$total_chains];
    $types = 'i';
    
    if ($current_chain_id !== null) {
        $update_query .= ", current_chain_id = ?";
        $params[] = $current_chain_id;
        $types .= 'i';
    }
    
    $update_query .= " WHERE id = ?";
    $params[] = $project_id;
    $types .= 'i';
    
    $update_stmt = mysqli_prepare($conn, $update_query);
    mysqli_stmt_bind_param($update_stmt, $types, ...$params);
    mysqli_stmt_execute($update_stmt);
    mysqli_stmt_close($update_stmt);
    
    // อัปเดต JSON status
    $status = getImpactChainStatus($project_id);
    $status['total_chains'] = $total_chains;
    $status['current_chain'] = $current_chain_id ? getChainSequence($current_chain_id) : $total_chains;
    
    updateImpactChainStatusJSON($project_id, $status);
}

/**
 * ดึงข้อมูล Impact Chain ทั้งหมดของโปรเจค
 * 
 * @param int $project_id รหัสโครงการ
 * @return array รายการ Impact Chain
 */
function getProjectImpactChains($project_id) {
    global $conn;
    
    $query = "SELECT ic.*, a.activity_name,
                     (SELECT COUNT(*) FROM impact_chain_outputs ico WHERE ico.impact_chain_id = ic.id) as has_outputs,
                     (SELECT COUNT(*) FROM impact_chain_outcomes icc WHERE icc.impact_chain_id = ic.id) as has_outcomes
              FROM impact_chains ic
              JOIN activities a ON ic.activity_id = a.activity_id
              WHERE ic.project_id = ? AND ic.status = 'active'
              ORDER BY ic.sequence_order ASC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $chains = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $row['is_complete'] = ($row['has_outputs'] > 0 && $row['has_outcomes'] > 0);
        $chains[] = $row;
    }
    
    mysqli_stmt_close($stmt);
    return $chains;
}

/**
 * ดึงข้อมูล Impact Chain เฉพาะ
 * 
 * @param int $chain_id รหัส Impact Chain
 * @return array|null ข้อมูล Impact Chain
 */
function getImpactChain($chain_id) {
    global $conn;
    
    $query = "SELECT ic.*, a.activity_name, a.activity_description
              FROM impact_chains ic
              JOIN activities a ON ic.activity_id = a.activity_id
              WHERE ic.id = ? AND ic.status = 'active'";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $chain_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $chain = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $chain;
}

/**
 * ดึงลำดับของ Impact Chain
 * 
 * @param int $chain_id รหัส Impact Chain
 * @return int ลำดับ
 */
function getChainSequence($chain_id) {
    global $conn;
    
    $query = "SELECT sequence_order FROM impact_chains WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $chain_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row ? $row['sequence_order'] : 1;
}

/**
 * เพิ่มผลผลิตให้ Impact Chain
 * 
 * @param int $chain_id รหัส Impact Chain
 * @param int $output_id รหัสผลผลิต
 * @param string $output_details รายละเอียดเพิ่มเติม
 * @param string $created_by ผู้สร้าง
 * @return bool ผลการดำเนินการ
 */
function addOutputToChain($chain_id, $output_id, $output_details, $created_by) {
    global $conn;
    
    // ลบผลผลิตเดิม (ถ้ามี)
    $delete_query = "DELETE FROM impact_chain_outputs WHERE impact_chain_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, 'i', $chain_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    // เพิ่มผลผลิตใหม่
    $insert_query = "INSERT INTO impact_chain_outputs (impact_chain_id, output_id, output_details, created_by) 
                    VALUES (?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, 'iiss', $chain_id, $output_id, $output_details, $created_by);
    $result = mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);
    
    return $result;
}

/**
 * เพิ่มผลลัพธ์ให้ Impact Chain
 * 
 * @param int $chain_id รหัส Impact Chain
 * @param int $outcome_id รหัสผลลัพธ์
 * @param string $outcome_details รายละเอียดเพิ่มเติม
 * @param string $evaluation_year ปีที่ประเมิน
 * @param string $benefit_data ข้อมูลผลประโยชน์
 * @param string $created_by ผู้สร้าง
 * @return bool ผลการดำเนินการ
 */
function addOutcomeToChain($chain_id, $outcome_id, $outcome_details, $evaluation_year, $benefit_data, $created_by) {
    global $conn;
    
    // ลบผลลัพธ์เดิม (ถ้ามี)
    $delete_query = "DELETE FROM impact_chain_outcomes WHERE impact_chain_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, 'i', $chain_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);
    
    // เพิ่มผลลัพธ์ใหม่
    $insert_query = "INSERT INTO impact_chain_outcomes (impact_chain_id, outcome_id, outcome_details, evaluation_year, benefit_data, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);
    mysqli_stmt_bind_param($insert_stmt, 'iissss', $chain_id, $outcome_id, $outcome_details, $evaluation_year, $benefit_data, $created_by);
    $result = mysqli_stmt_execute($insert_stmt);
    mysqli_stmt_close($insert_stmt);
    
    return $result;
}

/**
 * ลบ Impact Chain
 * 
 * @param int $chain_id รหัส Impact Chain
 * @return bool ผลการดำเนินการ
 */
function deleteImpactChain($chain_id) {
    global $conn;
    
    try {
        mysqli_autocommit($conn, false);
        
        // ดึงข้อมูล project_id ก่อนลบ
        $project_query = "SELECT project_id FROM impact_chains WHERE id = ?";
        $project_stmt = mysqli_prepare($conn, $project_query);
        mysqli_stmt_bind_param($project_stmt, 'i', $chain_id);
        mysqli_stmt_execute($project_stmt);
        $project_result = mysqli_stmt_get_result($project_stmt);
        $project_row = mysqli_fetch_assoc($project_result);
        $project_id = $project_row['project_id'];
        mysqli_stmt_close($project_stmt);
        
        // เปลี่ยนสถานะเป็น inactive แทนการลบจริง
        $update_query = "UPDATE impact_chains SET status = 'inactive' WHERE id = ?";
        $update_stmt = mysqli_prepare($conn, $update_query);
        mysqli_stmt_bind_param($update_stmt, 'i', $chain_id);
        
        if (!mysqli_stmt_execute($update_stmt)) {
            throw new Exception("ไม่สามารถลบ Impact Chain ได้");
        }
        mysqli_stmt_close($update_stmt);
        
        // อัปเดตข้อมูลโปรเจค
        updateProjectChainInfo($project_id);
        
        mysqli_commit($conn);
        mysqli_autocommit($conn, true);
        
        return true;
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        mysqli_autocommit($conn, true);
        error_log("Error deleting impact chain: " . $e->getMessage());
        return false;
    }
}

/**
 * อัปเดต JSON status สำหรับ multiple chains
 * 
 * @param int $project_id รหัสโครงการ
 * @param array $status ข้อมูลสถานะ
 */
function updateImpactChainStatusJSON($project_id, $status) {
    global $conn;
    
    $query = "UPDATE projects SET impact_chain_status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    $status_json = json_encode($status, JSON_UNESCAPED_UNICODE);
    mysqli_stmt_bind_param($stmt, 'si', $status_json, $project_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * ตรวจสอบว่าโปรเจคมี Impact Chain หรือไม่
 * 
 * @param int $project_id รหัสโครงการ
 * @return bool
 */
function hasImpactChains($project_id) {
    global $conn;
    
    $query = "SELECT COUNT(*) as count FROM impact_chains WHERE project_id = ? AND status = 'active'";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, 'i', $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    return $row['count'] > 0;
}

/**
 * ดึงข้อมูลสำหรับการแสดงใน Progress Bar
 * 
 * @param int $project_id รหัสโครงการ
 * @return array ข้อมูลสำหรับ Progress Bar
 */
function getMultiChainProgress($project_id) {
    $chains = getProjectImpactChains($project_id);
    $total_chains = count($chains);
    $completed_chains = 0;
    
    foreach ($chains as $chain) {
        if ($chain['is_complete']) {
            $completed_chains++;
        }
    }
    
    return [
        'total_chains' => $total_chains,
        'completed_chains' => $completed_chains,
        'current_chain' => $total_chains > 0 ? $total_chains : 1,
        'progress_percentage' => $total_chains > 0 ? ($completed_chains / $total_chains * 100) : 0,
        'chains' => $chains
    ];
}
?>