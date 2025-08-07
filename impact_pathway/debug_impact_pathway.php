<?php
// Debug script for impact_pathway.php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== DEBUG IMPACT PATHWAY ===\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

// Test basic requirements
echo "1. Testing config.php...\n";
if (file_exists('../config.php')) {
    echo "✅ config.php exists\n";
    try {
        require_once '../config.php';
        if (isset($conn) && $conn) {
            echo "✅ Database connection successful\n";
        } else {
            echo "❌ Database connection failed\n";
        }
    } catch (Exception $e) {
        echo "❌ Config error: " . $e->getMessage() . "\n";
    }
} else {
    echo "❌ config.php not found\n";
}

// Test session
echo "\n2. Testing session...\n";
session_start();
if (session_status() == PHP_SESSION_ACTIVE) {
    echo "✅ Session started successfully\n";
} else {
    echo "❌ Session failed to start\n";
}

// Test project_id parameter
echo "\n3. Testing project_id parameter...\n";
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;
echo "Project ID from URL: " . $project_id . "\n";

if ($project_id > 0 && isset($conn)) {
    echo "Testing project query...\n";
    try {
        $project_query = "SELECT id, project_code, name FROM projects WHERE id = ?";
        $project_stmt = mysqli_prepare($conn, $project_query);
        if ($project_stmt) {
            mysqli_stmt_bind_param($project_stmt, "i", $project_id);
            mysqli_stmt_execute($project_stmt);
            $project_result = mysqli_stmt_get_result($project_stmt);
            $selected_project = mysqli_fetch_assoc($project_result);
            mysqli_stmt_close($project_stmt);

            if ($selected_project) {
                echo "✅ Project found: " . $selected_project['project_code'] . "\n";
            } else {
                echo "⚠️  Project not found with ID: " . $project_id . "\n";
            }
        } else {
            echo "❌ Failed to prepare project query: " . mysqli_error($conn) . "\n";
        }
    } catch (Exception $e) {
        echo "❌ Project query error: " . $e->getMessage() . "\n";
    }
}

// Test related data queries
if ($project_id > 0 && isset($conn)) {
    echo "\n4. Testing related data queries...\n";

    // Test strategies
    try {
        $strategies_query = "
            SELECT COUNT(*) as count
            FROM strategies s
            INNER JOIN project_strategies ps ON s.strategy_id = ps.strategy_id
            WHERE ps.project_id = ?
        ";
        $strategies_stmt = mysqli_prepare($conn, $strategies_query);
        mysqli_stmt_bind_param($strategies_stmt, "i", $project_id);
        mysqli_stmt_execute($strategies_stmt);
        $strategies_result = mysqli_stmt_get_result($strategies_stmt);
        $strategies_count = mysqli_fetch_assoc($strategies_result);
        mysqli_stmt_close($strategies_stmt);
        echo "✅ Strategies count: " . $strategies_count['count'] . "\n";
    } catch (Exception $e) {
        echo "❌ Strategies query error: " . $e->getMessage() . "\n";
    }

    // Test activities
    try {
        $activities_query = "
            SELECT COUNT(*) as count
            FROM activities a
            INNER JOIN project_activities pa ON a.activity_id = pa.activity_id
            WHERE pa.project_id = ?
        ";
        $activities_stmt = mysqli_prepare($conn, $activities_query);
        mysqli_stmt_bind_param($activities_stmt, "i", $project_id);
        mysqli_stmt_execute($activities_stmt);
        $activities_result = mysqli_stmt_get_result($activities_stmt);
        $activities_count = mysqli_fetch_assoc($activities_result);
        mysqli_stmt_close($activities_stmt);
        echo "✅ Activities count: " . $activities_count['count'] . "\n";
    } catch (Exception $e) {
        echo "❌ Activities query error: " . $e->getMessage() . "\n";
    }

    // Test outputs
    try {
        $outputs_query = "
            SELECT COUNT(*) as count
            FROM outputs o
            INNER JOIN project_outputs po ON o.output_id = po.output_id
            WHERE po.project_id = ?
        ";
        $outputs_stmt = mysqli_prepare($conn, $outputs_query);
        mysqli_stmt_bind_param($outputs_stmt, "i", $project_id);
        mysqli_stmt_execute($outputs_stmt);
        $outputs_result = mysqli_stmt_get_result($outputs_stmt);
        $outputs_count = mysqli_fetch_assoc($outputs_result);
        mysqli_stmt_close($outputs_stmt);
        echo "✅ Outputs count: " . $outputs_count['count'] . "\n";
    } catch (Exception $e) {
        echo "❌ Outputs query error: " . $e->getMessage() . "\n";
    }

    // Test outcomes
    try {
        $outcomes_query = "
            SELECT COUNT(*) as count
            FROM outcomes oc
            INNER JOIN outputs o ON oc.output_id = o.output_id
            INNER JOIN project_outputs po ON o.output_id = po.output_id
            WHERE po.project_id = ?
        ";
        $outcomes_stmt = mysqli_prepare($conn, $outcomes_query);
        mysqli_stmt_bind_param($outcomes_stmt, "i", $project_id);
        mysqli_stmt_execute($outcomes_stmt);
        $outcomes_result = mysqli_stmt_get_result($outcomes_stmt);
        $outcomes_count = mysqli_fetch_assoc($outcomes_result);
        mysqli_stmt_close($outcomes_stmt);
        echo "✅ Outcomes count: " . $outcomes_count['count'] . "\n";
    } catch (Exception $e) {
        echo "❌ Outcomes query error: " . $e->getMessage() . "\n";
    }

    // Test impact ratios
    try {
        $ratios_query = "
            SELECT COUNT(*) as count
            FROM project_impact_ratios pir
            WHERE pir.project_id = ?
        ";
        $ratios_stmt = mysqli_prepare($conn, $ratios_query);
        mysqli_stmt_bind_param($ratios_stmt, "i", $project_id);
        mysqli_stmt_execute($ratios_stmt);
        $ratios_result = mysqli_stmt_get_result($ratios_stmt);
        $ratios_count = mysqli_fetch_assoc($ratios_result);
        mysqli_stmt_close($ratios_stmt);
        echo "✅ Impact ratios count: " . $ratios_count['count'] . "\n";
    } catch (Exception $e) {
        echo "❌ Impact ratios query error: " . $e->getMessage() . "\n";
    }
}

echo "\n=== END DEBUG ===\n";
