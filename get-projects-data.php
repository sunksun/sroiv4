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
    // ดึงข้อมูลโครงการทั้งหมด
    $query = "SELECT 
                id, 
                project_code, 
                name, 
                description,
                objective,
                budget, 
                organization, 
                project_manager, 
                status,
                created_at 
              FROM projects 
              ORDER BY created_at DESC";

    $result = mysqli_query($conn, $query);

    if (!$result) {
        echo json_encode(['error' => 'Query failed: ' . mysqli_error($conn)]);
        exit;
    }

    $projects = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = [
            'id' => (int)$row['id'],
            'project_code' => $row['project_code'],
            'name' => $row['name'],
            'description' => $row['description'],
            'objective' => $row['objective'],
            'budget' => (float)$row['budget'],
            'organization' => $row['organization'],
            'project_manager' => $row['project_manager'],
            'status' => $row['status'],
            'created_at' => $row['created_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'projects' => $projects,
        'count' => count($projects)
    ]);
} catch (Exception $e) {
    echo json_encode(['error' => 'Exception: ' . $e->getMessage()]);
}

mysqli_close($conn);
