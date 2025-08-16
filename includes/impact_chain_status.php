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
 * ตรวจสอบสถานะจากข้อมูลที่มีอยู่ในฐานข้อมูล
 * 
 * @param int $project_id รหัสโครงการ
 * @return bool ผลการอัปเดต
 */
function refreshImpactChainStatus($project_id) {
    global $conn;
    
    // ตรวจสอบสถานะแต่ละ step จากฐานข้อมูล
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
    
    // Step 2: ตรวจสอบว่ามีการเลือกกิจกรรมหรือไม่
    $query2 = "SELECT COUNT(*) as count FROM project_activities WHERE project_id = ?";
    $stmt2 = mysqli_prepare($conn, $query2);
    mysqli_stmt_bind_param($stmt2, 'i', $project_id);
    mysqli_stmt_execute($stmt2);
    $result2 = mysqli_stmt_get_result($stmt2);
    $row2 = mysqli_fetch_assoc($result2);
    $step2_completed = ($row2['count'] > 0);
    mysqli_stmt_close($stmt2);
    
    // Step 3: ตรวจสอบว่ามีการเลือกผลผลิตหรือไม่
    $query3 = "SELECT COUNT(*) as count FROM project_outputs WHERE project_id = ?";
    $stmt3 = mysqli_prepare($conn, $query3);
    mysqli_stmt_bind_param($stmt3, 'i', $project_id);
    mysqli_stmt_execute($stmt3);
    $result3 = mysqli_stmt_get_result($stmt3);
    $row3 = mysqli_fetch_assoc($result3);
    $step3_completed = ($row3['count'] > 0);
    mysqli_stmt_close($stmt3);
    
    // Step 4: ตรวจสอบว่ามีการกำหนดผลลัพธ์หรือไม่
    $query4 = "SELECT COUNT(*) as count FROM project_outcomes WHERE project_id = ?";
    $stmt4 = mysqli_prepare($conn, $query4);
    mysqli_stmt_bind_param($stmt4, 'i', $project_id);
    mysqli_stmt_execute($stmt4);
    $result4 = mysqli_stmt_get_result($stmt4);
    $row4 = mysqli_fetch_assoc($result4);
    $step4_completed = ($row4['count'] > 0);
    mysqli_stmt_close($stmt4);
    
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
?>