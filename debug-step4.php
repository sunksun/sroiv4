<?php
session_start();
require_once 'config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    die("กรุณาเข้าสู่ระบบก่อน");
}

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 1; // ใช้ project_id = 1 เป็นค่าเริ่มต้น

echo "<h2>Debug Step 4 - Project ID: $project_id</h2>";

// 1. ตรวจสอบข้อมูลผลผลิตที่เลือก
echo "<h3>1. ข้อมูลผลผลิตที่เลือก (project_outputs):</h3>";
$outputs_query = "SELECT po.*, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
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

echo "<pre>";
var_dump($selected_outputs);
echo "</pre>";

if (empty($selected_outputs)) {
    echo "<p style='color: red;'>❌ ไม่พบผลผลิตที่เลือกในโครงการนี้</p>";

    // แสดงผลผลิตทั้งหมดที่มี
    echo "<h4>ผลผลิตทั้งหมดในระบบ:</h4>";
    $all_outputs_query = "SELECT o.*, a.activity_name, s.strategy_name
                          FROM outputs o 
                          JOIN activities a ON o.activity_id = a.activity_id 
                          JOIN strategies s ON a.strategy_id = s.strategy_id
                          ORDER BY s.strategy_id, a.activity_id, o.output_sequence";
    $all_outputs_result = mysqli_query($conn, $all_outputs_query);
    $all_outputs = mysqli_fetch_all($all_outputs_result, MYSQLI_ASSOC);

    echo "<pre>";
    var_dump($all_outputs);
    echo "</pre>";

    exit;
}

// 2. ตรวจสอบผลลัพธ์ที่เกี่ยวข้อง
echo "<h3>2. ผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก:</h3>";
$output_ids = array_column($selected_outputs, 'output_id');
$output_ids_str = implode(',', array_map('intval', $output_ids));

$outcomes_query = "SELECT oc.*, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
                   FROM outcomes oc 
                   JOIN outputs o ON oc.output_id = o.output_id 
                   JOIN activities a ON o.activity_id = a.activity_id 
                   JOIN strategies s ON a.strategy_id = s.strategy_id
                   WHERE oc.output_id IN ($output_ids_str) 
                   ORDER BY s.strategy_id ASC, a.activity_id ASC, o.output_id ASC, oc.outcome_sequence ASC";
$outcomes_result = mysqli_query($conn, $outcomes_query);
$outcomes = mysqli_fetch_all($outcomes_result, MYSQLI_ASSOC);

echo "<pre>";
var_dump($outcomes);
echo "</pre>";

if (empty($outcomes)) {
    echo "<p style='color: red;'>❌ ไม่พบผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก</p>";

    // แสดงผลลัพธ์ทั้งหมดที่มี
    echo "<h4>ผลลัพธ์ทั้งหมดในระบบ:</h4>";
    $all_outcomes_query = "SELECT oc.*, o.output_description, a.activity_name, s.strategy_name
                           FROM outcomes oc 
                           JOIN outputs o ON oc.output_id = o.output_id 
                           JOIN activities a ON o.activity_id = a.activity_id 
                           JOIN strategies s ON a.strategy_id = s.strategy_id
                           ORDER BY s.strategy_id, a.activity_id, o.output_id, oc.outcome_sequence";
    $all_outcomes_result = mysqli_query($conn, $all_outcomes_query);
    $all_outcomes = mysqli_fetch_all($all_outcomes_result, MYSQLI_ASSOC);

    echo "<pre>";
    var_dump($all_outcomes);
    echo "</pre>";
} else {
    echo "<p style='color: green;'>✅ พบผลลัพธ์ " . count($outcomes) . " รายการ</p>";
}

// 3. ตรวจสอบผลลัพธ์ที่เลือกไว้แล้ว
echo "<h3>3. ผลลัพธ์ที่เลือกไว้แล้ว (project_outcomes):</h3>";
$selected_outcomes_query = "SELECT po.*, oc.outcome_description FROM project_outcomes po 
                            JOIN outcomes oc ON po.outcome_id = oc.outcome_id 
                            WHERE po.project_id = ?";
$selected_stmt = mysqli_prepare($conn, $selected_outcomes_query);
mysqli_stmt_bind_param($selected_stmt, 'i', $project_id);
mysqli_stmt_execute($selected_stmt);
$selected_result = mysqli_stmt_get_result($selected_stmt);
$selected_outcomes = mysqli_fetch_all($selected_result, MYSQLI_ASSOC);
mysqli_stmt_close($selected_stmt);

echo "<pre>";
var_dump($selected_outcomes);
echo "</pre>";

echo "<hr>";
echo "<a href='impact-chain/step4-outcome.php?project_id=$project_id'>ไปยัง Step 4</a> | ";
echo "<a href='project-list.php'>กลับไปรายการโครงการ</a>";
