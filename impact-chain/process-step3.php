<?php
session_start();
require_once '../config.php';
require_once '../includes/impact_chain_status.php';
require_once '../includes/impact_chain_manager.php';

// Debug: Log form submission
error_log("process-step3.php called with POST data: " . print_r($_POST, true));
error_log("process-step3.php: Starting processing...");

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// รับ data จาก GET หรือ POST
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
$selected_output_id = isset($_GET['selected_output_id']) ? (int)$_GET['selected_output_id'] : (isset($_POST['selected_output_id']) ? (int)$_POST['selected_output_id'] : 0);
$output_details = isset($_GET['output_details']) ? trim($_GET['output_details']) : (isset($_POST['output_details']) ? trim($_POST['output_details']) : '');
$chain_id = isset($_POST['chain_id']) ? (int)$_POST['chain_id'] : (isset($_GET['chain_id']) ? (int)$_GET['chain_id'] : null);

// ตรวจสอบว่าเป็นระบบเดิม (legacy) หรือระบบใหม่ (new chain)
$is_legacy_system = true;
if ($chain_id && $chain_id > 0) {
    // ตรวจสอบว่า chain_id นี้มีอยู่ในระบบใหม่หรือไม่
    $check_new_chain = "SELECT id FROM impact_chains WHERE id = ?";
    $check_stmt = mysqli_prepare($conn, $check_new_chain);
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, 'i', $chain_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        $is_legacy_system = (mysqli_num_rows($check_result) == 0);
        mysqli_stmt_close($check_stmt);
    } else {
        error_log("process-step3.php: Failed to prepare chain check query: " . mysqli_error($conn));
    }
}

error_log("process-step3.php: project_id=$project_id, selected_output_id=$selected_output_id, output_details=$output_details");
error_log("process-step3.php: chain_id=" . ($chain_id ?: 'null'));
error_log("process-step3.php: is_legacy_system=" . ($is_legacy_system ? 'true' : 'false'));

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: ../project-list.php");
    exit;
}

// ตรวจสอบสิทธิ์เข้าถึงโครงการ
$user_id = $_SESSION['user_id'];
$check_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'is', $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$project_result = mysqli_stmt_get_result($check_stmt);
$project = mysqli_fetch_assoc($project_result);
mysqli_stmt_close($check_stmt);

if (!$project) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงโครงการนี้";
    header("location: ../project-list.php");
    exit;
}

error_log("Validation check: selected_output_id=$selected_output_id, output_details='$output_details'");

if (empty($selected_output_id)) {
    error_log("process-step3.php: Empty selected_output_id - redirecting back");
    $_SESSION['error_message'] = "กรุณาเลือกผลผลิต (ID: $selected_output_id)";
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}

if (empty($output_details)) {
    error_log("process-step3.php: Empty output_details - redirecting back");
    $_SESSION['error_message'] = "กรุณากรอกรายละเอียดเพิ่มเติม (Details: '$output_details')";
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}

// บันทึกข้อมูลการเลือกผลผลิตลงฐานข้อมูล
try {
    // ตรวจสอบว่าได้เลือกกิจกรรมแล้วหรือไม่ (รองรับทั้งระบบเดิมและ new chain)
    $activity_found = false;
    
    if (!$is_legacy_system && $chain_id > 0) {
        // New Chain - ตรวจสอบใน impact_chains
        $check_activity_query = "SELECT activity_id FROM impact_chains WHERE id = ?";
        $check_activity_stmt = mysqli_prepare($conn, $check_activity_query);
        mysqli_stmt_bind_param($check_activity_stmt, 'i', $chain_id);
        mysqli_stmt_execute($check_activity_stmt);
        $activity_result = mysqli_stmt_get_result($check_activity_stmt);
        $activity_found = (mysqli_num_rows($activity_result) > 0);
        error_log("process-step3.php: New Chain system check - chain_id=$chain_id, activity_found=" . ($activity_found ? 'true' : 'false'));
        mysqli_stmt_close($check_activity_stmt);
    } else {
        // ระบบเดิม - ตรวจสอบใน project_activities
        $check_activity_query = "SELECT activity_id FROM project_activities WHERE project_id = ?";
        $check_activity_stmt = mysqli_prepare($conn, $check_activity_query);
        mysqli_stmt_bind_param($check_activity_stmt, 'i', $project_id);
        mysqli_stmt_execute($check_activity_stmt);
        $activity_result = mysqli_stmt_get_result($check_activity_stmt);
        $activity_found = (mysqli_num_rows($activity_result) > 0);
        error_log("process-step3.php: Legacy system check - project_id=$project_id, activity_found=" . ($activity_found ? 'true' : 'false'));
        mysqli_stmt_close($check_activity_stmt);
    }
    
    if (!$activity_found) {
        $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อน";
        if (!$is_legacy_system && $chain_id > 0) {
            header("location: step2-activity.php?project_id=" . $project_id . "&new_chain=1");
        } else {
            header("location: step2-activity.php?project_id=" . $project_id);
        }
        exit;
    }

    // ตรวจสอบว่า output_id ที่เลือกมีจริงในฐานข้อมูล
    $verify_query = "SELECT o.output_id, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
                     FROM outputs o 
                     JOIN activities a ON o.activity_id = a.activity_id 
                     JOIN strategies s ON a.strategy_id = s.strategy_id
                     WHERE o.output_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, 'i', $selected_output_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if ($output = mysqli_fetch_assoc($verify_result)) {
        error_log("Found output: " . $output['output_description'] . ", chain_id: " . ($chain_id ? $chain_id : 'null'));
        
        if (!$is_legacy_system && $chain_id) {
            // Impact Chain ใหม่ - ใช้ตารางใหม่
            error_log("Using new Impact Chain system (impact_chain_outputs table)");
            $result = addOutputToChain($chain_id, $selected_output_id, $output_details, $user_id);
            
            if ($result) {
                // เก็บข้อมูลใน session เพื่อใช้ในการแสดงผล
                $_SESSION['selected_outputs'] = [$output];
                $_SESSION['selected_output_detail'] = $output;
                $_SESSION['success_message'] = "บันทึกการเลือกผลผลิตสำเร็จ: " . $output['output_description'];

                // อัปเดตสถานะ Impact Chain - Step 3 เสร็จสิ้น
                updateMultipleImpactChainStatus($project_id, $chain_id, 3, true);

                // ไปยัง Step 4 เพื่อเลือกผลลัพธ์
                $step4_url = "step4-outcome.php?project_id=" . $project_id;
                if ($chain_id) {
                    $step4_url .= "&chain_id=" . $chain_id;
                }
                header("location: " . $step4_url);
                exit;
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                header("location: step3-output.php?project_id=" . $project_id . "&chain_id=" . $chain_id);
                exit;
            }
        } else {
            // Impact Chain เดิม - ใช้ตารางเดิม
            error_log("Using legacy Impact Chain system (project_outputs table)");
            // ลบข้อมูลการเลือกผลผลิตเดิม (ถ้ามี)
            $delete_query = "DELETE FROM project_outputs WHERE project_id = ?";
            $delete_stmt = mysqli_prepare($conn, $delete_query);
            mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
            mysqli_stmt_execute($delete_stmt);
            mysqli_stmt_close($delete_stmt);

            // บันทึกการเลือกผลผลิตใหม่ พร้อมรายละเอียดเพิ่มเติม
            $insert_query = "INSERT INTO project_outputs (project_id, output_id, output_details, created_by) VALUES (?, ?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, 'iiss', $project_id, $selected_output_id, $output_details, $user_id);

            if (mysqli_stmt_execute($insert_stmt)) {
                // เก็บข้อมูลใน session เพื่อใช้ในการแสดงผล
                $_SESSION['selected_outputs'] = [$selected_output_id];
                $_SESSION['selected_outputs_detail'] = [$output];
                $_SESSION['selected_output_details'] = $output_details;
                $_SESSION['success_message'] = "บันทึกการเลือกผลผลิตสำเร็จ: " . $output['output_description'];

                // อัปเดตสถานะ Impact Chain - Step 3 เสร็จสิ้น
                updateMultipleImpactChainStatus($project_id, null, 3, true);

                // ไปยัง Step 4 เพื่อเลือกผลลัพธ์
                $step4_url = "step4-outcome.php?project_id=" . $project_id;
                if ($chain_id) {
                    $step4_url .= "&chain_id=" . $chain_id;
                }
                header("location: " . $step4_url);
                exit;
            } else {
                $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
                header("location: step3-output.php?project_id=" . $project_id);
                exit;
            }
            mysqli_stmt_close($insert_stmt);
        }
    } else {
        $_SESSION['error_message'] = "ไม่พบข้อมูลผลผลิตที่เลือก";
        header("location: step3-output.php?project_id=" . $project_id);
        exit;
    }
    mysqli_stmt_close($verify_stmt);
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}
