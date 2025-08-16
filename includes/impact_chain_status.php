<?php
/**
 * ฟังก์ชันสำหรับจัดการสถานะ Impact Chain
 * 
 * @author SROIV4 System
 * @created 2025-08-16
 */

/**
 * ดึงข้อมูลสถานะ Impact Chain ของโครงการ
 * 
 * @param int $project_id รหัสโครงการ
 * @return array สถานะ Impact Chain
 */
function getImpactChainStatus($project_id) {
    global $conn;
    
    $query = "SELECT impact_chain_status FROM projects WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("MySQL prepare error: " . mysqli_error($conn));
        return getDefaultStatus();
    }
    
    mysqli_stmt_bind_param($stmt, 'i', $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $project = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($project && $project['impact_chain_status']) {
        $status = json_decode($project['impact_chain_status'], true);
        if (json_last_error() === JSON_ERROR_NONE) {
            return $status;
        }
    }
    
    return getDefaultStatus();
}

/**
 * สร้างสถานะเริ่มต้น
 * 
 * @return array สถานะเริ่มต้น
 */
function getDefaultStatus() {
    return [
        'step1_completed' => false,
        'step2_completed' => false,
        'step3_completed' => false,
        'step4_completed' => false,
        'current_step' => 1,
        'last_updated' => date('Y-m-d H:i:s')
    ];
}

/**
 * อัปเดตสถานะ Impact Chain
 * 
 * @param int $project_id รหัสโครงการ
 * @param int $step ขั้นตอนที่จะอัปเดต (1-4)
 * @param bool $completed สถานะการเสร็จสิ้น
 * @return bool ผลการอัปเดต
 */
function updateImpactChainStatus($project_id, $step, $completed = true) {
    global $conn;
    
    if ($step < 1 || $step > 4) {
        error_log("Invalid step number: $step");
        return false;
    }
    
    $status = getImpactChainStatus($project_id);
    $status["step{$step}_completed"] = $completed;
    
    // อัปเดต current_step
    if ($completed) {
        // หาก step ปัจจุบันเสร็จแล้ว ให้ไปขั้นตอนถัดไป
        if ($step < 4) {
            $status['current_step'] = $step + 1;
        } else {
            $status['current_step'] = 4; // step สุดท้าย
        }
    } else {
        // หาก step ปัจจุบันยังไม่เสร็จ ให้อยู่ที่ step นั้น
        $status['current_step'] = $step;
    }
    
    $status['last_updated'] = date('Y-m-d H:i:s');
    
    $query = "UPDATE projects SET impact_chain_status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("MySQL prepare error: " . mysqli_error($conn));
        return false;
    }
    
    $status_json = json_encode($status, JSON_UNESCAPED_UNICODE);
    mysqli_stmt_bind_param($stmt, 'si', $status_json, $project_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    if (!$result) {
        error_log("Failed to update impact chain status: " . mysqli_error($conn));
    }
    
    return $result;
}

/**
 * ตรวจสอบสถานะจากข้อมูลที่มีอยู่ในฐานข้อมูล (รองรับ Multiple Impact Chains)
 * 
 * @param int $project_id รหัสโครงการ
 * @return bool ผลการอัปเดต
 */
function refreshImpactChainStatus($project_id) {
    global $conn;
    
    // ตรวจสอบสถานะแต่ละ step จากฐานข้อมูล (รองรับทั้งระบบเดิมและใหม่)
    $step1_completed = false;
    $step2_completed = false;
    $step3_completed = false;
    $step4_completed = false;
    
    // Step 1: ตรวจสอบว่ามีการเลือกยุทธศาสตร์หรือไม่
    $query1 = "SELECT COUNT(*) as count FROM project_strategies WHERE project_id = ?";
    $stmt1 = mysqli_prepare($conn, $query1);
    mysqli_stmt_bind_param($stmt1, 'i', $project_id);
    mysqli_stmt_execute($stmt1);
    $result1 = mysqli_stmt_get_result($stmt1);
    $row1 = mysqli_fetch_assoc($result1);
    $step1_completed = ($row1['count'] > 0);
    mysqli_stmt_close($stmt1);
    
    // Step 2: ตรวจสอบว่ามีการเลือกกิจกรรมหรือไม่ (ตารางเดิม + ตารางใหม่)
    $query2a = "SELECT COUNT(*) as count FROM project_activities WHERE project_id = ?";
    $stmt2a = mysqli_prepare($conn, $query2a);
    mysqli_stmt_bind_param($stmt2a, 'i', $project_id);
    mysqli_stmt_execute($stmt2a);
    $result2a = mysqli_stmt_get_result($stmt2a);
    $row2a = mysqli_fetch_assoc($result2a);
    mysqli_stmt_close($stmt2a);
    
    $query2b = "SELECT COUNT(*) as count FROM impact_chains WHERE project_id = ? AND status = 'active'";
    $stmt2b = mysqli_prepare($conn, $query2b);
    mysqli_stmt_bind_param($stmt2b, 'i', $project_id);
    mysqli_stmt_execute($stmt2b);
    $result2b = mysqli_stmt_get_result($stmt2b);
    $row2b = mysqli_fetch_assoc($result2b);
    mysqli_stmt_close($stmt2b);
    
    $step2_completed = ($row2a['count'] > 0 || $row2b['count'] > 0);
    
    // Step 3: ตรวจสอบว่ามีการเลือกผลผลิตหรือไม่ (ตารางเดิม + ตารางใหม่)
    $query3a = "SELECT COUNT(*) as count FROM project_outputs WHERE project_id = ?";
    $stmt3a = mysqli_prepare($conn, $query3a);
    mysqli_stmt_bind_param($stmt3a, 'i', $project_id);
    mysqli_stmt_execute($stmt3a);
    $result3a = mysqli_stmt_get_result($stmt3a);
    $row3a = mysqli_fetch_assoc($result3a);
    mysqli_stmt_close($stmt3a);
    
    $query3b = "SELECT COUNT(*) as count FROM impact_chain_outputs ico 
                JOIN impact_chains ic ON ico.impact_chain_id = ic.id 
                WHERE ic.project_id = ? AND ic.status = 'active'";
    $stmt3b = mysqli_prepare($conn, $query3b);
    mysqli_stmt_bind_param($stmt3b, 'i', $project_id);
    mysqli_stmt_execute($stmt3b);
    $result3b = mysqli_stmt_get_result($stmt3b);
    $row3b = mysqli_fetch_assoc($result3b);
    mysqli_stmt_close($stmt3b);
    
    $step3_completed = ($row3a['count'] > 0 || $row3b['count'] > 0);
    
    // Step 4: ตรวจสอบว่ามีการกำหนดผลลัพธ์หรือไม่ (ตารางเดิม + ตารางใหม่)
    $query4a = "SELECT COUNT(*) as count FROM project_outcomes WHERE project_id = ?";
    $stmt4a = mysqli_prepare($conn, $query4a);
    mysqli_stmt_bind_param($stmt4a, 'i', $project_id);
    mysqli_stmt_execute($stmt4a);
    $result4a = mysqli_stmt_get_result($stmt4a);
    $row4a = mysqli_fetch_assoc($result4a);
    mysqli_stmt_close($stmt4a);
    
    $query4b = "SELECT COUNT(*) as count FROM impact_chain_outcomes ico 
                JOIN impact_chains ic ON ico.impact_chain_id = ic.id 
                WHERE ic.project_id = ? AND ic.status = 'active'";
    $stmt4b = mysqli_prepare($conn, $query4b);
    mysqli_stmt_bind_param($stmt4b, 'i', $project_id);
    mysqli_stmt_execute($stmt4b);
    $result4b = mysqli_stmt_get_result($stmt4b);
    $row4b = mysqli_fetch_assoc($result4b);
    mysqli_stmt_close($stmt4b);
    
    $step4_completed = ($row4a['count'] > 0 || $row4b['count'] > 0);
    
    // กำหนด current_step
    $current_step = 1;
    if ($step4_completed) {
        $current_step = 4;
    } elseif ($step3_completed) {
        $current_step = 4;
    } elseif ($step2_completed) {
        $current_step = 3;
    } elseif ($step1_completed) {
        $current_step = 2;
    }
    
    // อัปเดตสถานะ
    $status = [
        'step1_completed' => $step1_completed,
        'step2_completed' => $step2_completed,
        'step3_completed' => $step3_completed,
        'step4_completed' => $step4_completed,
        'current_step' => $current_step,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    $query = "UPDATE projects SET impact_chain_status = ? WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    
    if (!$stmt) {
        error_log("MySQL prepare error: " . mysqli_error($conn));
        return false;
    }
    
    $status_json = json_encode($status, JSON_UNESCAPED_UNICODE);
    mysqli_stmt_bind_param($stmt, 'si', $status_json, $project_id);
    $result = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    
    return $result;
}

/**
 * คำนวณเปอร์เซ็นต์ความคืบหน้า
 * 
 * @param array $status สถานะ Impact Chain
 * @return float เปอร์เซ็นต์ความคืบหน้า (0-100)
 */
function calculateProgress($status) {
    $completed_steps = 0;
    
    for ($i = 1; $i <= 4; $i++) {
        if ($status["step{$i}_completed"]) {
            $completed_steps++;
        }
    }
    
    $progress = ($completed_steps / 4) * 100;
    
    // เพิ่ม 12.5% สำหรับ step ปัจจุบันที่กำลังดำเนินการ (หากยังไม่เสร็จ)
    $current_step = $status['current_step'];
    if ($current_step <= 4 && !$status["step{$current_step}_completed"]) {
        $progress += 12.5; // 25% / 2 = 12.5%
    }
    
    return min(100, $progress); // ไม่เกิน 100%
}

/**
 * ตรวจสอบว่าสามารถเข้าถึง step ได้หรือไม่
 * 
 * @param array $status สถานะ Impact Chain
 * @param int $requested_step step ที่ต้องการเข้าถึง
 * @return bool สามารถเข้าถึงได้หรือไม่
 */
function canAccessStep($status, $requested_step) {
    if ($requested_step == 1) {
        return true; // step 1 เข้าถึงได้เสมอ
    }
    
    // step อื่นๆ ต้องทำ step ก่อนหน้าให้เสร็จก่อน
    for ($i = 1; $i < $requested_step; $i++) {
        if (!$status["step{$i}_completed"]) {
            return false;
        }
    }
    
    return true;
}

/**
 * ดึงข้อมูลสถานะ Multiple Impact Chains ของโครงการ
 * 
 * @param int $project_id รหัสโครงการ
 * @return array ข้อมูลสถานะรวม
 */
function getMultipleImpactChainStatus($project_id) {
    global $conn;
    
    // ดึงสถานะพื้นฐาน
    $status = getImpactChainStatus($project_id);
    
    // นับ Impact Chains ทั้งหมด
    $query_chains = "SELECT COUNT(*) as total_chains FROM impact_chains WHERE project_id = ? AND status = 'active'";
    $stmt_chains = mysqli_prepare($conn, $query_chains);
    mysqli_stmt_bind_param($stmt_chains, 'i', $project_id);
    mysqli_stmt_execute($stmt_chains);
    $result_chains = mysqli_stmt_get_result($stmt_chains);
    $row_chains = mysqli_fetch_assoc($result_chains);
    $total_chains = $row_chains['total_chains'];
    mysqli_stmt_close($stmt_chains);
    
    // นับ Impact Chains ที่เสร็จสิ้น (มีทั้ง output และ outcome)
    $query_completed = "SELECT COUNT(DISTINCT ic.id) as completed_chains 
                        FROM impact_chains ic
                        WHERE ic.project_id = ? AND ic.status = 'active'
                        AND EXISTS (SELECT 1 FROM impact_chain_outputs ico WHERE ico.impact_chain_id = ic.id)
                        AND EXISTS (SELECT 1 FROM impact_chain_outcomes ico2 WHERE ico2.impact_chain_id = ic.id)";
    $stmt_completed = mysqli_prepare($conn, $query_completed);
    mysqli_stmt_bind_param($stmt_completed, 'i', $project_id);
    mysqli_stmt_execute($stmt_completed);
    $result_completed = mysqli_stmt_get_result($stmt_completed);
    $row_completed = mysqli_fetch_assoc($result_completed);
    $completed_chains = $row_completed['completed_chains'];
    mysqli_stmt_close($stmt_completed);
    
    // เพิ่มข้อมูล Multiple Impact Chains
    $status['multiple_chains'] = [
        'total_chains' => $total_chains,
        'completed_chains' => $completed_chains,
        'has_old_chain' => ($status['step2_completed'] && $total_chains == 0) // มี chain เดิมหรือไม่
    ];
    
    return $status;
}

/**
 * อัปเดตสถานะ Impact Chain สำหรับ Multiple Chains
 * 
 * @param int $project_id รหัสโครงการ
 * @param int $chain_id รหัส Impact Chain (สำหรับ chain ใหม่)
 * @param int $step ขั้นตอนที่จะอัปเดต (1-4)
 * @param bool $completed สถานะการเสร็จสิ้น
 * @return bool ผลการอัปเดต
 */
function updateMultipleImpactChainStatus($project_id, $chain_id = null, $step = null, $completed = true) {
    // รีเฟรชสถานะจากข้อมูลจริงในฐานข้อมูล
    $result = refreshImpactChainStatus($project_id);
    
    // ถ้ามี chain_id แสดงว่าเป็น Impact Chain ใหม่
    if ($chain_id && $step) {
        // อัปเดต timestamp ของ chain
        global $conn;
        $update_chain = "UPDATE impact_chains SET updated_at = NOW() WHERE id = ?";
        $stmt_update = mysqli_prepare($conn, $update_chain);
        mysqli_stmt_bind_param($stmt_update, 'i', $chain_id);
        mysqli_stmt_execute($stmt_update);
        mysqli_stmt_close($stmt_update);
    }
    
    return $result;
}
?>