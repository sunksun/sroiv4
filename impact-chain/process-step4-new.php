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

// บันทึกข้อมูลการเลือกผลลัพธ์ลงฐานข้อมูล
try {
    // ตรวจสอบว่า outcome_id ที่เลือกมีจริงในฐานข้อมูล
    $valid_outcomes = [];

    foreach ($selected_outcomes as $outcome_id) {
        $outcome_id = (int)$outcome_id;
        $verify_query = "SELECT oc.outcome_id, oc.outcome_sequence, oc.outcome_description, o.output_description, a.activity_name, s.strategy_name
                         FROM outcomes oc 
                         JOIN outputs o ON oc.output_id = o.output_id 
                         JOIN activities a ON o.activity_id = a.activity_id 
                         JOIN strategies s ON a.strategy_id = s.strategy_id
                         WHERE oc.outcome_id = ?";
        $verify_stmt = mysqli_prepare($conn, $verify_query);
        mysqli_stmt_bind_param($verify_stmt, 'i', $outcome_id);
        mysqli_stmt_execute($verify_stmt);
        $verify_result = mysqli_stmt_get_result($verify_stmt);

        if ($outcome = mysqli_fetch_assoc($verify_result)) {
            $valid_outcomes[] = $outcome;
        }
        mysqli_stmt_close($verify_stmt);
    }

    if (empty($valid_outcomes)) {
        $_SESSION['error_message'] = "ไม่พบข้อมูลผลลัพธ์ที่เลือก";
        header("location: step4-outcome.php?project_id=" . $project_id);
        exit;
    }

    // สร้างตาราง project_outcomes ถ้าไม่มี (สำหรับการบันทึกผลลัพธ์ที่เลือก)
    $create_table_query = "CREATE TABLE IF NOT EXISTS project_outcomes (
        id INT(11) NOT NULL AUTO_INCREMENT,
        project_id INT(11) NOT NULL COMMENT 'รหัสโครงการ',
        outcome_id INT(11) NOT NULL COMMENT 'รหัสผลลัพธ์',
        created_by VARCHAR(100) DEFAULT NULL COMMENT 'ผู้สร้าง',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP(),
        updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP() ON UPDATE CURRENT_TIMESTAMP(),
        PRIMARY KEY (id),
        KEY idx_project_id (project_id),
        KEY idx_outcome_id (outcome_id),
        FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
        FOREIGN KEY (outcome_id) REFERENCES outcomes(outcome_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บการเลือกผลลัพธ์ของโครงการ'";
    mysqli_query($conn, $create_table_query);

    // ลบข้อมูลการเลือกผลลัพธ์เดิม (ถ้ามี)
    $delete_query = "DELETE FROM project_outcomes WHERE project_id = ?";
    $delete_stmt = mysqli_prepare($conn, $delete_query);
    mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
    mysqli_stmt_execute($delete_stmt);
    mysqli_stmt_close($delete_stmt);

    // บันทึกการเลือกผลลัพธ์ใหม่
    $insert_query = "INSERT INTO project_outcomes (project_id, outcome_id, created_by) VALUES (?, ?, ?)";
    $insert_stmt = mysqli_prepare($conn, $insert_query);

    foreach ($valid_outcomes as $outcome) {
        mysqli_stmt_bind_param($insert_stmt, 'iis', $project_id, $outcome['outcome_id'], $user_id);
        mysqli_stmt_execute($insert_stmt);
    }
    mysqli_stmt_close($insert_stmt);

    $_SESSION['success_message'] = "บันทึกการเลือกผลลัพธ์สำเร็จ: " . count($valid_outcomes) . " รายการ";

    // ไปยังหน้าสรุปผล
    header("location: summary.php?project_id=" . $project_id);
    exit;
} catch (Exception $e) {
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    header("location: step4-outcome.php?project_id=" . $project_id);
    exit;
}
