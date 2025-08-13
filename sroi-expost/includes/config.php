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

// ดึงค่า discount_rate จากฐานข้อมูล
$saved_discount_rate = 0.03; // ค่าเริ่มต้น 3%
$discount_query = "SELECT discount_rate FROM present_value_factors WHERE pvf_name = 'current' AND is_active = 1 LIMIT 1";
$discount_result = mysqli_query($conn, $discount_query);
if ($discount_result && mysqli_num_rows($discount_result) > 0) {
    $row = mysqli_fetch_assoc($discount_result);
    $saved_discount_rate = floatval($row['discount_rate']) / 100; // แปลงเป็นทศนิยม
}

// ข้อมูลค่าเริ่มต้นสำหรับการคำนวณ
$default_settings = [
    'analysis_period' => 5, // ปี
    'discount_rate' => $saved_discount_rate, // ใช้ค่าจากฐานข้อมูล
    'inflation_rate' => 0.02, // 2%
    'sensitivity_range' => 0.2 // ±20%
];
?>