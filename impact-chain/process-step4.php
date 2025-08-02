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
$selected_outcomes = isset($_POST['outcomes']) ? $_POST['outcomes'] : [];

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

if (empty($selected_outcomes)) {
    $_SESSION['error_message'] = "กรุณาเลือกผลลัพธ์อย่างน้อย 1 รายการ";
    header("location: step4-outcome.php?project_id=" . $project_id);
    exit;
}

// TEST MODE: ไม่บันทึกข้อมูลจริง เพียงแค่ทดสอบการทำงาน
try {
    // ตรวจสอบว่า outcome_id ที่เลือกมีจริงในฐานข้อมูล และดึง financial proxies
    $valid_outcomes = [];
    $financial_proxies_summary = [];

    foreach ($selected_outcomes as $outcome_id) {
        $verify_query = "SELECT moc.id, moc.name, moc.description, mo.name as output_name, ma.name as activity_name, ms.name as strategy_name
                         FROM master_outcomes moc 
                         JOIN master_outputs mo ON moc.output_id = mo.id 
                         JOIN master_activities ma ON mo.activity_id = ma.id 
                         JOIN master_strategies ms ON ma.strategy_id = ms.id
                         WHERE moc.id = ? AND moc.is_active = 1";
        $verify_stmt = mysqli_prepare($conn, $verify_query);
        mysqli_stmt_bind_param($verify_stmt, 'i', $outcome_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);

        if ($outcome = mysqli_fetch_assoc($verify_result)) {
            $valid_outcomes[] = $outcome;

            // ดึง financial proxies ที่เกี่ยวข้อง
            $fp_query = "SELECT id, name, unit, estimated_value FROM master_financial_proxies WHERE outcome_id = ? AND is_active = 1";
            $fp_stmt = mysqli_prepare($conn, $fp_query);
            mysqli_stmt_bind_param($fp_stmt, 'i', $outcome_id);
            mysqli_stmt_execute($fp_stmt);
            $fp_result = mysqli_stmt_get_result($fp_stmt);

            while ($fp = mysqli_fetch_assoc($fp_result)) {
                $financial_proxies_summary[] = array_merge($fp, [
                    'outcome_name' => $outcome['name'],
                    'output_name' => $outcome['output_name']
                ]);
            }
            mysqli_stmt_close($fp_stmt);
        }
        mysqli_stmt_close($verify_stmt);
    }

    // เก็บข้อมูลใน session สำหรับการทดสอบ
    $_SESSION['test_selected_outcomes'] = array_column($valid_outcomes, 'id');
    $_SESSION['test_selected_outcomes_detail'] = $valid_outcomes;
    $_SESSION['test_financial_proxies'] = $financial_proxies_summary;
    $_SESSION['success_message'] = "ทดสอบการสร้าง Impact Chain สำเร็จ (ยังไม่บันทึกข้อมูลจริง)";

    // ไปยังหน้าสรุปผล
    header("location: summary.php?project_id=" . $project_id);
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการทดสอบ: " . $e->getMessage();
    header("location: step4-outcome.php?project_id=" . $project_id);
    exit;
}
