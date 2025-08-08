<?php
// SROI Ex-post Analysis Configuration
session_start();
require_once '../config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตั้งค่าตัวแปรสำหรับข้อความแจ้งเตือน
$message = '';
$error = '';

// ดึงข้อมูล session ที่จำเป็น
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// ข้อมูลค่าเริ่มต้นสำหรับการคำนวณ
$default_settings = [
    'analysis_period' => 5, // ปี
    'discount_rate' => 0.03, // 3%
    'inflation_rate' => 0.02, // 2%
    'sensitivity_range' => 0.2 // ±20%
];
?>