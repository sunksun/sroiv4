<?php
session_start();
require_once '../config.php';

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
$selected_output_id = isset($_POST['selected_output_id']) ? (int)$_POST['selected_output_id'] : 0;
$output_details = isset($_POST['output_details']) ? trim($_POST['output_details']) : '';

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

if (empty($selected_output_id)) {
    $_SESSION['error_message'] = "กรุณาเลือกผลผลิต";
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}

if (empty($output_details)) {
    $_SESSION['error_message'] = "กรุณากรอกรายละเอียดเพิ่มเติม";
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}

// บันทึกข้อมูลการเลือกผลผลิตลงฐานข้อมูล
try {
    // ตรวจสอบว่าโครงการนี้ได้เลือกกิจกรรมแล้วหรือไม่
    $check_activity_query = "SELECT activity_id FROM project_activities WHERE project_id = ?";
    $check_activity_stmt = mysqli_prepare($conn, $check_activity_query);
    mysqli_stmt_bind_param($check_activity_stmt, 'i', $project_id);
    mysqli_stmt_execute($check_activity_stmt);
    $activity_result = mysqli_stmt_get_result($check_activity_stmt);

    if (mysqli_num_rows($activity_result) == 0) {
        $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อน";
        header("location: step2-activity.php?project_id=" . $project_id);
        exit;
    }
    mysqli_stmt_close($check_activity_stmt);

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

            // ไปยัง Summary เนื่องจากไม่มีตาราง outcomes ในโครงสร้างใหม่
            header("location: summary.php?project_id=" . $project_id);
            exit;
        } else {
            $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล";
            header("location: step3-output.php?project_id=" . $project_id);
            exit;
        }
        mysqli_stmt_close($insert_stmt);
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
