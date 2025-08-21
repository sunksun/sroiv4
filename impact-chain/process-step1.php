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
$selected_strategy = isset($_POST['strategy']) ? (int)$_POST['strategy'] : 0;

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

if (empty($selected_strategy)) {
    $_SESSION['error_message'] = "กรุณาเลือกยุทธศาสตร์";
    header("location: step1-strategy.php?project_id=" . $project_id);
    exit;
}

// บันทึกข้อมูลการเลือกยุทธศาสตร์ลงฐานข้อมูล
try {
    // ตรวจสอบว่า strategy_id ที่เลือกมีจริงในฐานข้อมูล
    $verify_query = "SELECT strategy_id, strategy_name FROM strategies WHERE strategy_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, 'i', $selected_strategy);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if ($strategy = mysqli_fetch_assoc($verify_result)) {
        // ตรวจสอบยุทธศาสตร์เดิมก่อนแก้ไข
        $check_existing_query = "SELECT strategy_id FROM project_strategies WHERE project_id = ?";
        $check_existing_stmt = mysqli_prepare($conn, $check_existing_query);
        mysqli_stmt_bind_param($check_existing_stmt, 'i', $project_id);
        mysqli_stmt_execute($check_existing_stmt);
        $existing_result = mysqli_stmt_get_result($check_existing_stmt);
        $existing_strategy = mysqli_fetch_assoc($existing_result);
        mysqli_stmt_close($check_existing_stmt);

        $strategy_changed = false;
        
        if ($existing_strategy) {
            // มียุทธศาสตร์เดิมอยู่แล้ว - ตรวจสอบว่าเปลี่ยนหรือไม่
            if ($existing_strategy['strategy_id'] != $selected_strategy) {
                $strategy_changed = true;
            }
            
            // อัปเดตยุทธศาสตร์
            $update_query = "UPDATE project_strategies SET strategy_id = ? WHERE project_id = ?";
            $update_stmt = mysqli_prepare($conn, $update_query);
            mysqli_stmt_bind_param($update_stmt, 'ii', $selected_strategy, $project_id);
            $update_success = mysqli_stmt_execute($update_stmt);
            mysqli_stmt_close($update_stmt);
        } else {
            // ไม่มียุทธศาสตร์เดิม - เพิ่มใหม่
            $insert_query = "INSERT INTO project_strategies (project_id, strategy_id, created_by) VALUES (?, ?, ?)";
            $insert_stmt = mysqli_prepare($conn, $insert_query);
            mysqli_stmt_bind_param($insert_stmt, 'iis', $project_id, $selected_strategy, $user_id);
            $update_success = mysqli_stmt_execute($insert_stmt);
            mysqli_stmt_close($insert_stmt);
        }

        // ลบข้อมูล step2-4 เฉพาะเมื่อยุทธศาสตร์เปลี่ยน
        if ($strategy_changed) {
            // ลบข้อมูลการเลือกกิจกรรมและผลผลิตเดิม (เนื่องจากเปลี่ยนยุทธศาสตร์)
            $delete_activities_query = "DELETE FROM project_activities WHERE project_id = ?";
            $delete_activities_stmt = mysqli_prepare($conn, $delete_activities_query);
            mysqli_stmt_bind_param($delete_activities_stmt, 'i', $project_id);
            mysqli_stmt_execute($delete_activities_stmt);
            mysqli_stmt_close($delete_activities_stmt);

            $delete_outputs_query = "DELETE FROM project_outputs WHERE project_id = ?";
            $delete_outputs_stmt = mysqli_prepare($conn, $delete_outputs_query);
            mysqli_stmt_bind_param($delete_outputs_stmt, 'i', $project_id);
            mysqli_stmt_execute($delete_outputs_stmt);
            mysqli_stmt_close($delete_outputs_stmt);

            $_SESSION['success_message'] = "อัปเดตยุทธศาสตร์สำเร็จ: " . $strategy['strategy_name'] . " (ข้อมูลกิจกรรมและผลผลิตถูกรีเซ็ต)";
        } else {
            $_SESSION['success_message'] = "อัปเดตยุทธศาสตร์สำเร็จ: " . $strategy['strategy_name'];
        }

        if ($update_success) {
            // เก็บข้อมูลใน session เพื่อใช้ในการแสดงผล
            $_SESSION['selected_strategies'] = [$strategy];

            // อัปเดตสถานะ Impact Chain - Step 1 เสร็จสิ้น
            updateImpactChainStatus($project_id, 1, true);

            // ไปยัง Step 2
            header("location: step2-activity.php?project_id=" . $project_id);
            exit;
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            header("location: step1-strategy.php?project_id=" . $project_id);
            exit;
        }
    } else {
        $_SESSION['error_message'] = "ยุทธศาสตร์ที่เลือกไม่ถูกต้อง";
        header("location: step1-strategy.php?project_id=" . $project_id);
        exit;
    }
    mysqli_stmt_close($verify_stmt);
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    header("location: step1-strategy.php?project_id=" . $project_id);
    exit;
}
