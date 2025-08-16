<?php
session_start();
require_once '../config.php';
require_once '../includes/impact_chain_manager.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'ไม่ได้รับอนุญาต']);
    exit;
}

// ตรวจสอบ method
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method ไม่ถูกต้อง']);
    exit;
}

// รับข้อมูล JSON
$input = json_decode(file_get_contents('php://input'), true);
$chain_id = isset($input['chain_id']) ? (int)$input['chain_id'] : 0;

if ($chain_id == 0) {
    echo json_encode(['success' => false, 'message' => 'ไม่พบข้อมูล Impact Chain']);
    exit;
}

// ตรวจสอบสิทธิ์เข้าถึง Impact Chain
$user_id = $_SESSION['user_id'];
$check_query = "SELECT ic.id, ic.project_id, p.created_by 
                FROM impact_chains ic
                JOIN projects p ON ic.project_id = p.id
                WHERE ic.id = ? AND p.created_by = ? AND ic.status = 'active'";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'ii', $chain_id, $user_id);
mysqli_stmt_execute($check_stmt);
$check_result = mysqli_stmt_get_result($check_stmt);
$chain = mysqli_fetch_assoc($check_result);
mysqli_stmt_close($check_stmt);

if (!$chain) {
    echo json_encode(['success' => false, 'message' => 'คุณไม่มีสิทธิ์ลบ Impact Chain นี้']);
    exit;
}

// ลบ Impact Chain
$result = deleteImpactChain($chain_id);

if ($result) {
    echo json_encode(['success' => true, 'message' => 'ลบ Impact Chain สำเร็จ']);
} else {
    echo json_encode(['success' => false, 'message' => 'เกิดข้อผิดพลาดในการลบ Impact Chain']);
}
?>