<?php
require_once 'config.php';

// Set content type to JSON
header('Content-Type: application/json');

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

try {
    // ดึงข้อมูลยุทธศาสตร์ทั้งหมด
    $query = "SELECT 
                strategy_id, 
                strategy_code, 
                strategy_name, 
                description,
                created_at 
              FROM strategies 
              ORDER BY strategy_id ASC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]);
        exit;
    }

    $strategies = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $strategies[] = [
            'strategy_id' => (int)$row['strategy_id'],
            'strategy_code' => $row['strategy_code'],
            'strategy_name' => $row['strategy_name'],
            'description' => $row['description'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'strategies' => $strategies,
        'count' => count($strategies)
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}

mysqli_close($conn);
