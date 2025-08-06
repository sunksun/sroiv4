<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    echo "<h3>ไม่ได้เข้าสู่ระบบ</h3>";
    echo "<a href='../login.php'>เข้าสู่ระบบ</a>";
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// รับ project_id จาก URL
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: ../project-list.php");
    exit;
}

echo "<h2>Debug Step 4 - Project ID: $project_id</h2>";
echo "<p>User ID: " . $_SESSION['user_id'] . "</p>";

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
    echo "<h3>ไม่มีสิทธิ์เข้าถึงโครงการ</h3>";
    echo "<p>Project ID: $project_id, User ID: $user_id</p>";

    // แสดงโครงการที่มีสิทธิ์เข้าถึง
    $user_projects_query = "SELECT * FROM projects WHERE created_by = ?";
    $user_projects_stmt = mysqli_prepare($conn, $user_projects_query);
    mysqli_stmt_bind_param($user_projects_stmt, 's', $user_id);
    mysqli_stmt_execute($user_projects_stmt);
    $user_projects_result = mysqli_stmt_get_result($user_projects_stmt);
    $user_projects = mysqli_fetch_all($user_projects_result, MYSQLI_ASSOC);
    mysqli_stmt_close($user_projects_stmt);

    echo "<h4>โครงการที่คุณมีสิทธิ์เข้าถึง:</h4>";
    echo "<pre>";
    var_dump($user_projects);
    echo "</pre>";

    exit;
}

echo "<h3>✅ โครงการ: " . htmlspecialchars($project['name']) . "</h3>";

// ดึงข้อมูลกิจกรรมที่เลือก
$activity_query = "SELECT pa.activity_id, a.activity_name, a.activity_code, a.activity_description, s.strategy_id, s.strategy_name 
                   FROM project_activities pa 
                   JOIN activities a ON pa.activity_id = a.activity_id 
                   JOIN strategies s ON a.strategy_id = s.strategy_id 
                   WHERE pa.project_id = ? 
                   ORDER BY pa.created_at DESC 
                   LIMIT 1";
$activity_stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($activity_stmt, 'i', $project_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);
$selected_activity = mysqli_fetch_assoc($activity_result);
mysqli_stmt_close($activity_stmt);

if ($selected_activity) {
    echo "<h4>✅ กิจกรรมที่เลือก:</h4>";
    echo "<pre>";
    var_dump($selected_activity);
    echo "</pre>";
} else {
    echo "<h4>❌ ไม่พบกิจกรรมที่เลือก</h4>";
}

// ดึงข้อมูลผลผลิตที่เลือก
$outputs_query = "SELECT po.output_id, o.output_description, o.output_sequence, po.output_details, a.activity_name, s.strategy_name
                  FROM project_outputs po 
                  JOIN outputs o ON po.output_id = o.output_id 
                  JOIN activities a ON o.activity_id = a.activity_id
                  JOIN strategies s ON a.strategy_id = s.strategy_id
                  WHERE po.project_id = ?";
$outputs_stmt = mysqli_prepare($conn, $outputs_query);
mysqli_stmt_bind_param($outputs_stmt, 'i', $project_id);
mysqli_stmt_execute($outputs_stmt);
$outputs_result = mysqli_stmt_get_result($outputs_stmt);
$selected_outputs = mysqli_fetch_all($outputs_result, MYSQLI_ASSOC);
mysqli_stmt_close($outputs_stmt);

if (!empty($selected_outputs)) {
    echo "<h4>✅ ผลผลิตที่เลือก:</h4>";
    echo "<pre>";
    var_dump($selected_outputs);
    echo "</pre>";
} else {
    echo "<h4>❌ ไม่พบผลผลิตที่เลือก</h4>";
}

// ดึงผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก
$output_ids = array_column($selected_outputs, 'output_id');
$outcomes = [];

if (!empty($output_ids)) {
    $output_ids_str = implode(',', array_map('intval', $output_ids));

    $outcomes_query = "SELECT oc.*, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
                       FROM outcomes oc 
                       JOIN outputs o ON oc.output_id = o.output_id 
                       JOIN activities a ON o.activity_id = a.activity_id 
                       JOIN strategies s ON a.strategy_id = s.strategy_id
                       WHERE oc.output_id IN ($output_ids_str) 
                       ORDER BY s.strategy_id ASC, a.activity_id ASC, o.output_id ASC, oc.outcome_sequence ASC";
    echo "<h4>SQL Query:</h4>";
    echo "<code>$outcomes_query</code>";

    $outcomes_result = mysqli_query($conn, $outcomes_query);
    if ($outcomes_result) {
        $outcomes = mysqli_fetch_all($outcomes_result, MYSQLI_ASSOC);
        echo "<h4>✅ ผลลัพธ์ที่พบ (" . count($outcomes) . " รายการ):</h4>";
        echo "<pre>";
        var_dump($outcomes);
        echo "</pre>";
    } else {
        echo "<h4>❌ เกิดข้อผิดพลาดในการดึงข้อมูลผลลัพธ์:</h4>";
        echo "<p>" . mysqli_error($conn) . "</p>";
    }
}

echo "<hr>";
echo "<a href='step4-outcome.php?project_id=$project_id'>ไปยัง Step 4 จริง</a> | ";
echo "<a href='../project-list.php'>กลับไปรายการโครงการ</a>";
