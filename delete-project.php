<?php
session_start();
require_once 'config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ตรวจสอบว่าเป็น POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("location: project-list.php");
    exit;
}

// ดึงข้อมูล session ที่จำเป็น
$user_id = $_SESSION['user_id'];

// รับข้อมูลจากฟอร์ม
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

if ($project_id <= 0) {
    $_SESSION['error_message'] = "ไม่พบรหัสโครงการที่ต้องการลบ";
    header("location: project-list.php");
    exit;
}

try {
    // ตรวจสอบว่าโครงการเป็นของผู้ใช้ที่ login หรือไม่
    $check_query = "SELECT id, name FROM projects WHERE id = ? AND created_by = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, "is", $project_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $check_result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($check_result) === 0) {
        mysqli_stmt_close($check_stmt);
        $_SESSION['error_message'] = "ไม่พบโครงการที่ต้องการลบ หรือคุณไม่มีสิทธิ์ลบโครงการนี้";
        header("location: project-list.php");
        exit;
    }

    $project_data = mysqli_fetch_assoc($check_result);
    $project_name = $project_data['name'];
    mysqli_stmt_close($check_stmt);

    mysqli_begin_transaction($conn);

    // ลบโครงการ
    $delete_query = "DELETE FROM projects WHERE id = ? AND created_by = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, "is", $project_id, $user_id);

    if (!mysqli_stmt_execute($delete_stmt)) {
        throw new Exception("เกิดข้อผิดพลาดในการลบโครงการ: " . mysqli_error($conn));
    }

    $affected_rows = mysqli_stmt_affected_rows($delete_stmt);
    mysqli_stmt_close($delete_stmt);

    if ($affected_rows === 0) {
        throw new Exception("ไม่สามารถลบโครงการได้");
    }

    // บันทึก log
    $log_message = "ลบโครงการ: {$project_name} (ID: {$project_id})";
    $log_query = "
        INSERT INTO system_logs (log_level, module, message, user_id, timestamp)
        VALUES ('INFO', 'PROJECT', ?, ?, NOW())
    ";

    $log_stmt = mysqli_prepare($conn, $log_query);
    mysqli_stmt_bind_param($log_stmt, "ss", $log_message, $user_id);

    if (!mysqli_stmt_execute($log_stmt)) {
        // ถ้า log ไม่สำเร็จ ให้เตือนแต่ไม่ rollback
        error_log("Failed to log project deletion: " . mysqli_error($conn));
    }
    mysqli_stmt_close($log_stmt);

    mysqli_commit($conn);
    $_SESSION['success_message'] = "ลบโครงการ '{$project_name}' เรียบร้อยแล้ว";
} catch (Exception $e) {
    mysqli_rollback($conn);
    $_SESSION['error_message'] = $e->getMessage();
    error_log("Project deletion error: " . $e->getMessage());
}

// กลับไปหน้า project-list
header("location: project-list.php");
exit;
