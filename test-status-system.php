<?php
session_start();
require_once 'config.php';
require_once 'includes/impact_chain_status.php';

// Simple test to check the status system
if (!isset($_GET['project_id'])) {
    echo "กรุณาระบุ project_id ใน URL เช่น ?project_id=1";
    exit;
}

$project_id = (int)$_GET['project_id'];

echo "<h2>ทดสอบระบบสถานะ Impact Chain</h2>";
echo "<h3>Project ID: {$project_id}</h3>";

// ทดสอบการรีเฟรชสถานะ
echo "<h4>1. รีเฟรชสถานะ</h4>";
$refresh_result = refreshImpactChainStatus($project_id);
echo "ผลการรีเฟรช: " . ($refresh_result ? "สำเร็จ" : "ล้มเหลว") . "<br><br>";

// ดึงสถานะปัจจุบัน
echo "<h4>2. สถานะปัจจุบัน</h4>";
$status = getImpactChainStatus($project_id);
echo "<pre>" . print_r($status, true) . "</pre>";

// ดึงสถานะ Multiple Impact Chains
echo "<h4>3. สถานะ Multiple Impact Chains</h4>";
$multi_status = getMultipleImpactChainStatus($project_id);
echo "<pre>" . print_r($multi_status, true) . "</pre>";

// คำนวณความคืบหน้า
echo "<h4>4. ความคืบหน้า</h4>";
$progress = calculateProgress($status);
echo "ความคืบหน้า: {$progress}%<br>";

$completed_count = 0;
for ($i = 1; $i <= 4; $i++) {
    if ($status["step{$i}_completed"]) $completed_count++;
}
echo "เสร็จสิ้น {$completed_count} จาก 4 ขั้นตอน<br><br>";

// ตรวจสอบข้อมูลในฐานข้อมูล
echo "<h4>5. ข้อมูลในฐานข้อมูล</h4>";

echo "<strong>ตารางเดิม:</strong><br>";
$queries = [
    "project_strategies" => "SELECT COUNT(*) as count FROM project_strategies WHERE project_id = {$project_id}",
    "project_activities" => "SELECT COUNT(*) as count FROM project_activities WHERE project_id = {$project_id}",
    "project_outputs" => "SELECT COUNT(*) as count FROM project_outputs WHERE project_id = {$project_id}",
    "project_outcomes" => "SELECT COUNT(*) as count FROM project_outcomes WHERE project_id = {$project_id}"
];

foreach ($queries as $table => $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "{$table}: {$row['count']} รายการ<br>";
    }
}

echo "<br><strong>ตารางใหม่:</strong><br>";
$new_queries = [
    "impact_chains" => "SELECT COUNT(*) as count FROM impact_chains WHERE project_id = {$project_id} AND status = 'active'",
    "impact_chain_outputs" => "SELECT COUNT(*) as count FROM impact_chain_outputs ico JOIN impact_chains ic ON ico.chain_id = ic.id WHERE ic.project_id = {$project_id} AND ic.status = 'active'",
    "impact_chain_outcomes" => "SELECT COUNT(*) as count FROM impact_chain_outcomes ico JOIN impact_chains ic ON ico.chain_id = ic.id WHERE ic.project_id = {$project_id} AND ic.status = 'active'"
];

foreach ($new_queries as $table => $query) {
    $result = mysqli_query($conn, $query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        echo "{$table}: {$row['count']} รายการ<br>";
    }
}

echo "<br><a href='project-list.php'>← กลับไปรายการโครงการ</a>";
?>