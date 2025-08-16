<?php
session_start();
require_once '../config.php';
require_once '../includes/impact_chain_status.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบ POST data
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: ../project-list.php");
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$selected_activity = isset($_POST['selected_activity']) ? trim($_POST['selected_activity']) : '';

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

if (empty($selected_activity)) {
    $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อนดำเนินการต่อ";
    header("location: step2-activity.php?project_id=" . $project_id);
    exit;
}

// บันทึกข้อมูลการเลือกกิจกรรมลงฐานข้อมูล
try {
    // ตรวจสอบว่า activity_id ที่เลือกมีจริงในฐานข้อมูล
    $verify_query = "SELECT a.activity_id, a.activity_name, a.activity_code, a.activity_description, s.strategy_name 
                     FROM activities a 
                     JOIN strategies s ON a.strategy_id = s.strategy_id 
                     WHERE a.activity_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, 's', $selected_activity);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if ($activity = mysqli_fetch_assoc($verify_result)) {
        // ตรวจสอบว่าโครงการนี้ได้เลือกยุทธศาสตร์แล้วหรือไม่
        $check_strategy_query = "SELECT strategy_id FROM project_strategies WHERE project_id = ?";
        $check_strategy_stmt = mysqli_prepare($conn, $check_strategy_query);
        mysqli_stmt_bind_param($check_strategy_stmt, 'i', $project_id);
        mysqli_stmt_execute($check_strategy_stmt);
        $strategy_result = mysqli_stmt_get_result($check_strategy_stmt);

        if (mysqli_num_rows($strategy_result) == 0) {
            $_SESSION['error_message'] = "กรุณาเลือกยุทธศาสตร์ก่อน";
            header("location: step1-strategy.php?project_id=" . $project_id);
            exit;
        }
        mysqli_stmt_close($check_strategy_stmt);

        // ลบข้อมูลการเลือกกิจกรรมเดิม (ถ้ามี)
        $delete_query = "DELETE FROM project_activities WHERE project_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        // ลบข้อมูลการเลือกผลผลิตเดิม (เนื่องจากเปลี่ยนกิจกรรม)
        $delete_outputs_query = "DELETE FROM project_outputs WHERE project_id = ?";
        $delete_outputs_stmt = mysqli_prepare($conn, $delete_outputs_query);
        mysqli_stmt_bind_param($delete_outputs_stmt, 'i', $project_id);
        mysqli_stmt_execute($delete_outputs_stmt);
        mysqli_stmt_close($delete_outputs_stmt);

        // บันทึกการเลือกกิจกรรมใหม่
        $insert_query = "INSERT INTO project_activities (project_id, activity_id, created_by) VALUES (?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);
        mysqli_stmt_bind_param($insert_stmt, 'iis', $project_id, $selected_activity, $user_id);

        if (mysqli_stmt_execute($insert_stmt)) {
            // เก็บข้อมูลใน session เพื่อใช้ในการแสดงผล
            $_SESSION['selected_activities'] = [$activity['activity_id']];
            $_SESSION['selected_activity_detail'] = $activity;
            $_SESSION['success_message'] = "บันทึกการเลือกกิจกรรมสำเร็จ: " . $activity['activity_name'];

            // อัปเดตสถานะ Impact Chain - Step 2 เสร็จสิ้น
            updateImpactChainStatus($project_id, 2, true);

            // ไปยัง Step 3
            header("location: step3-output.php?project_id=" . $project_id);
            exit;
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            header("location: step2-activity.php?project_id=" . $project_id);
            exit;
        }
        mysqli_stmt_close($insert_stmt);
    } else {
        $_SESSION['error_message'] = "ไม่พบข้อมูลกิจกรรมที่เลือก";
        header("location: step2-activity.php?project_id=" . $project_id);
        exit;
    }
    mysqli_stmt_close($verify_stmt);
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    header("location: step2-activity.php?project_id=" . $project_id);
    exit;
}
