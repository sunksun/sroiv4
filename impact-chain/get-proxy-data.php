<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// รับ outcome_id จาก GET parameter
$outcome_id = isset($_GET['outcome_id']) ? (int)$_GET['outcome_id'] : 0;

if ($outcome_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid outcome_id']);
    exit;
}

try {
    // ดึงข้อมูล Proxy จากฐานข้อมูล
    $proxies_query = "SELECT proxy_id, proxy_sequence, proxy_name, calculation_formula, proxy_description 
                      FROM proxies 
                      WHERE outcome_id = ? 
                      ORDER BY CAST(proxy_sequence AS UNSIGNED) ASC";

    $proxies_stmt = mysqli_prepare($conn, $proxies_query);

    if (!$proxies_stmt) {
        throw new Exception('Prepare statement failed: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($proxies_stmt, 'i', $outcome_id);
    mysqli_stmt_execute($proxies_stmt);
    $proxies_result = mysqli_stmt_get_result($proxies_stmt);

    $proxies = [];
    while ($row = mysqli_fetch_assoc($proxies_result)) {
        // ทำความสะอาดข้อมูลเพื่อความปลอดภัย
        $proxies[] = [
            'proxy_id' => (int)$row['proxy_id'],
            'proxy_sequence' => htmlspecialchars($row['proxy_sequence'] ?? '', ENT_QUOTES, 'UTF-8'),
            'proxy_name' => htmlspecialchars($row['proxy_name'] ?? '', ENT_QUOTES, 'UTF-8'),
            'calculation_formula' => htmlspecialchars($row['calculation_formula'] ?? '', ENT_QUOTES, 'UTF-8'),
            'proxy_description' => htmlspecialchars($row['proxy_description'] ?? '', ENT_QUOTES, 'UTF-8')
        ];
    }

    mysqli_stmt_close($proxies_stmt);

    // ส่งผลลัพธ์กลับ
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => true,
        'proxies' => $proxies,
        'count' => count($proxies)
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    error_log("Error in get-proxy-data.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Internal server error'
    ]);
}

mysqli_close($conn);
