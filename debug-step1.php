<?php
session_start();
require_once 'config.php';

echo "<h2>Debug Step 1 Strategy</h2>";

// ตรวจสอบ session
echo "<h3>Session Information:</h3>";
echo "Logged in: " . (isset($_SESSION["loggedin"]) ? "Yes" : "No") . "<br>";
echo "User ID: " . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : "Not set") . "<br>";

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
echo "<h3>Database Connection:</h3>";
if (!$conn) {
    echo "Connection failed: " . mysqli_connect_error() . "<br>";
} else {
    echo "Connection successful<br>";
}

// ตรวจสอบ project_id
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
echo "<h3>Project ID: {$project_id}</h3>";

if ($project_id > 0) {
    // ตรวจสอบโครงการ
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    echo "Checking project access for user {$user_id}...<br>";
    
    $check_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    
    if ($check_stmt) {
        mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $project_result = mysqli_stmt_get_result($check_stmt);
        $project = mysqli_fetch_assoc($project_result);
        mysqli_stmt_close($check_stmt);
        
        if ($project) {
            echo "Project found: " . htmlspecialchars($project['name']) . "<br>";
        } else {
            echo "Project not found or no access<br>";
        }
    } else {
        echo "Failed to prepare project query: " . mysqli_error($conn) . "<br>";
    }
    
    // ตรวจสอบยุทธศาสตร์
    echo "<h3>Strategies in database:</h3>";
    $strategies_query = "SELECT * FROM strategies ORDER BY strategy_id ASC";
    $strategies_result = mysqli_query($conn, $strategies_query);
    
    if ($strategies_result) {
        $strategies = mysqli_fetch_all($strategies_result, MYSQLI_ASSOC);
        echo "Found " . count($strategies) . " strategies:<br>";
        foreach ($strategies as $strategy) {
            echo "- ID: {$strategy['strategy_id']}, Name: {$strategy['strategy_name']}<br>";
        }
    } else {
        echo "Failed to get strategies: " . mysqli_error($conn) . "<br>";
    }
    
    // ตรวจสอบยุทธศาสตร์ที่เลือกแล้ว
    echo "<h3>Selected strategies for this project:</h3>";
    $selected_query = "SELECT ps.strategy_id, s.strategy_name 
                       FROM project_strategies ps 
                       JOIN strategies s ON ps.strategy_id = s.strategy_id 
                       WHERE ps.project_id = ?";
    $selected_stmt = mysqli_prepare($conn, $selected_query);
    
    if ($selected_stmt) {
        mysqli_stmt_bind_param($selected_stmt, 'i', $project_id);
        mysqli_stmt_execute($selected_stmt);
        $selected_result = mysqli_stmt_get_result($selected_stmt);
        
        $count = 0;
        while ($selected = mysqli_fetch_assoc($selected_result)) {
            echo "- Selected: {$selected['strategy_id']} - {$selected['strategy_name']}<br>";
            $count++;
        }
        
        if ($count == 0) {
            echo "No strategies selected for this project yet.<br>";
        }
        
        mysqli_stmt_close($selected_stmt);
    } else {
        echo "Failed to prepare selected strategies query: " . mysqli_error($conn) . "<br>";
    }
} else {
    echo "Invalid project ID<br>";
}

echo "<br><a href='impact-chain/step1-strategy.php?project_id={$project_id}'>← กลับไป Step 1</a>";
?>