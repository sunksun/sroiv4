<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();

// Check if config file exists
if (!file_exists('../config.php')) {
    die("Error: config.php file not found");
}

try {
    require_once '../config.php';
} catch (Exception $e) {
    die("Config error: " . $e->getMessage());
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!isset($conn) || !$conn) {
    die("Connection failed: Database connection not established");
}

if (mysqli_connect_error()) {
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
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
$username = isset($_SESSION['username']) ? $_SESSION['username'] : '';

// รับ project_id จาก URL
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// Initialize variables
$selected_project = null;
$existing_pathways = [];
$project_strategies = [];
$project_activities = [];
$project_outputs = [];
$project_outcomes = [];
$project_impact_ratios = [];
$data_completion_status = [
    'strategies' => false,
    'activities' => false,
    'outputs' => false,
    'outcomes' => false,
    'impact_ratios' => false
];

// ดึงข้อมูลโครงการที่เลือก
if ($project_id > 0) {
    try {
        $project_query = "SELECT id, project_code, name FROM projects WHERE id = ?";
        $project_stmt = mysqli_prepare($conn, $project_query);
        if ($project_stmt) {
            mysqli_stmt_bind_param($project_stmt, "i", $project_id);
            mysqli_stmt_execute($project_stmt);
            $project_result = mysqli_stmt_get_result($project_stmt);
            $selected_project = mysqli_fetch_assoc($project_result);
            mysqli_stmt_close($project_stmt);
        } else {
            $error = "Failed to prepare project query: " . mysqli_error($conn);
        }
    } catch (Exception $e) {
        $error = "Error fetching project: " . $e->getMessage();
    }
}

// ดึงข้อมูล impact pathway ที่มีอยู่แล้วสำหรับโครงการนี้
if ($project_id > 0 && !$error) {
    try {
        $pathway_query = "SELECT * FROM social_impact_pathway WHERE project_id = ? ORDER BY pathway_sequence";
        $pathway_stmt = mysqli_prepare($conn, $pathway_query);
        if ($pathway_stmt) {
            mysqli_stmt_bind_param($pathway_stmt, "i", $project_id);
            mysqli_stmt_execute($pathway_stmt);
            $pathway_result = mysqli_stmt_get_result($pathway_stmt);
            while ($pathway = mysqli_fetch_assoc($pathway_result)) {
                $existing_pathways[] = $pathway;
            }
            mysqli_stmt_close($pathway_stmt);
        }
    } catch (Exception $e) {
        // Ignore pathway errors for now
    }
}

// ดึงข้อมูลที่สัมพันธ์กันทั้งหมดของโครงการ
if ($project_id > 0 && !$error) {
    // ดึงกลยุทธ์ของโครงการ
    try {
        $strategies_query = "
            SELECT DISTINCT s.strategy_id, s.strategy_code, s.strategy_name, s.strategy_description,
                   ps.strategy_details as project_strategy_details
            FROM strategies s
            INNER JOIN project_strategies ps ON s.strategy_id = ps.strategy_id
            WHERE ps.project_id = ?
            ORDER BY s.strategy_code
        ";
        $strategies_stmt = mysqli_prepare($conn, $strategies_query);
        if ($strategies_stmt) {
            mysqli_stmt_bind_param($strategies_stmt, "i", $project_id);
            mysqli_stmt_execute($strategies_stmt);
            $strategies_result = mysqli_stmt_get_result($strategies_stmt);
            while ($strategy = mysqli_fetch_assoc($strategies_result)) {
                $project_strategies[] = $strategy;
            }
            mysqli_stmt_close($strategies_stmt);
            $data_completion_status['strategies'] = count($project_strategies) > 0;
        }
    } catch (Exception $e) {
        // Continue with other queries
    }

    // ดึงกิจกรรมที่โครงการเลือกใช้
    try {
        $activities_query = "
            SELECT DISTINCT a.activity_id, a.activity_code, a.activity_name, a.activity_description,
                   pa.activity_details as project_activity_details
            FROM activities a
            INNER JOIN project_activities pa ON a.activity_id = pa.activity_id
            WHERE pa.project_id = ?
            ORDER BY a.activity_code
        ";
        $activities_stmt = mysqli_prepare($conn, $activities_query);
        if ($activities_stmt) {
            mysqli_stmt_bind_param($activities_stmt, "i", $project_id);
            mysqli_stmt_execute($activities_stmt);
            $activities_result = mysqli_stmt_get_result($activities_stmt);
            while ($activity = mysqli_fetch_assoc($activities_result)) {
                $project_activities[] = $activity;
            }
            mysqli_stmt_close($activities_stmt);
            $data_completion_status['activities'] = count($project_activities) > 0;
        }
    } catch (Exception $e) {
        // Continue with other queries
    }

    // ดึงผลผลิตที่โครงการเลือกใช้
    try {
        $outputs_query = "
            SELECT DISTINCT o.output_id, o.output_sequence, o.output_description, o.target_details,
                   po.output_details as project_output_details, po.target_amount, po.target_unit
            FROM outputs o
            INNER JOIN project_outputs po ON o.output_id = po.output_id
            WHERE po.project_id = ?
            ORDER BY o.output_sequence
        ";
        $outputs_stmt = mysqli_prepare($conn, $outputs_query);
        if ($outputs_stmt) {
            mysqli_stmt_bind_param($outputs_stmt, "i", $project_id);
            mysqli_stmt_execute($outputs_stmt);
            $outputs_result = mysqli_stmt_get_result($outputs_stmt);
            while ($output = mysqli_fetch_assoc($outputs_result)) {
                $project_outputs[] = $output;
            }
            mysqli_stmt_close($outputs_stmt);
            $data_completion_status['outputs'] = count($project_outputs) > 0;
        }
    } catch (Exception $e) {
        // Continue with other queries
    }

    // ดึงผลลัพธ์ที่เกี่ยวข้องกับโครงการ
    try {
        $outcomes_query = "
            SELECT DISTINCT oc.outcome_id, oc.outcome_sequence, oc.outcome_description
            FROM outcomes oc
            INNER JOIN outputs o ON oc.output_id = o.output_id
            INNER JOIN project_outputs po ON o.output_id = po.output_id
            WHERE po.project_id = ?
            ORDER BY oc.outcome_sequence
        ";
        $outcomes_stmt = mysqli_prepare($conn, $outcomes_query);
        if ($outcomes_stmt) {
            mysqli_stmt_bind_param($outcomes_stmt, "i", $project_id);
            mysqli_stmt_execute($outcomes_stmt);
            $outcomes_result = mysqli_stmt_get_result($outcomes_stmt);
            while ($outcome = mysqli_fetch_assoc($outcomes_result)) {
                $project_outcomes[] = $outcome;
            }
            mysqli_stmt_close($outcomes_stmt);
            $data_completion_status['outcomes'] = count($project_outcomes) > 0;
        }
    } catch (Exception $e) {
        // Continue with other queries
    }

    // ดึงข้อมูล Impact Ratios ของโครงการ
    try {
        $ratios_query = "
            SELECT DISTINCT pir.ratio_id, pir.outcome_id, pir.proxy_id, pir.benefit_note, 
                   pir.financial_proxy, pir.deadweight_percent, pir.displacement_percent, 
                   pir.drop_off_percent, pir.attribution_percent, pir.year_calculated
            FROM project_impact_ratios pir
            WHERE pir.project_id = ?
            ORDER BY pir.ratio_id
        ";
        $ratios_stmt = mysqli_prepare($conn, $ratios_query);
        if ($ratios_stmt) {
            mysqli_stmt_bind_param($ratios_stmt, "i", $project_id);
            mysqli_stmt_execute($ratios_stmt);
            $ratios_result = mysqli_stmt_get_result($ratios_stmt);
            while ($ratio = mysqli_fetch_assoc($ratios_result)) {
                $project_impact_ratios[] = $ratio;
            }
            mysqli_stmt_close($ratios_stmt);
            $data_completion_status['impact_ratios'] = count($project_impact_ratios) > 0;
        }
    } catch (Exception $e) {
        // Continue with other queries
    }
}

// ดึงรายการโครงการสำหรับ dropdown
$projects_result = false;
try {
    $projects_query = "SELECT id, project_code, name FROM projects WHERE status = 'incompleted' ORDER BY project_code";
    $projects_result = mysqli_query($conn, $projects_query);
    if (!$projects_result) {
        $error = "Failed to fetch projects: " . mysqli_error($conn);
    }
} catch (Exception $e) {
    $error = "Error fetching projects: " . $e->getMessage();
}

// ดึงรายการกิจกรรมทั้งหมดสำหรับ dropdown
$all_activities_result = false;
try {
    $all_activities_query = "SELECT activity_id, activity_code, activity_name FROM activities ORDER BY activity_code";
    $all_activities_result = mysqli_query($conn, $all_activities_query);
} catch (Exception $e) {
    // Continue without all activities
}

// ดึงรายการผลลัพธ์ทั้งหมดสำหรับ dropdown  
$all_outcomes_result = false;
try {
    $all_outcomes_query = "SELECT outcome_id, outcome_sequence, outcome_description FROM outcomes ORDER BY outcome_sequence";
    $all_outcomes_result = mysqli_query($conn, $all_outcomes_query);
} catch (Exception $e) {
    // Continue without all outcomes
}

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $project_id = intval($_POST['project_id']);
        $pathway_sequence = trim($_POST['pathway_sequence']);
        $from_modal = isset($_POST['from_modal']) ? true : false;

        // รับข้อมูลจากแต่ละขั้นตอน
        $input_description = trim($_POST['input_description'] ?? '');
        $activities_description = trim($_POST['activities_description'] ?? '');
        $activity_id = !empty($_POST['activity_id']) ? intval($_POST['activity_id']) : null;
        $output_description = trim($_POST['output_description'] ?? '');
        $output_id = !empty($_POST['output_id']) ? intval($_POST['output_id']) : null;
        $user_description = trim($_POST['user_description'] ?? '');
        $adoption_description = trim($_POST['adoption_description'] ?? '');
        $outcome_description = trim($_POST['outcome_description'] ?? '');
        $outcome_id = !empty($_POST['outcome_id']) ? intval($_POST['outcome_id']) : null;
        $impact_description = trim($_POST['impact_description'] ?? '');

        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($project_id)) {
            throw new Exception("กรุณาเลือกโครงการ");
        }
        if (empty($pathway_sequence)) {
            throw new Exception("กรุณากรอกลำดับของห่วงโซ่");
        }

        mysqli_begin_transaction($conn);

        // บันทึกข้อมูล Social Impact Pathway
        $query = "
            INSERT INTO social_impact_pathway (
                project_id, pathway_sequence, input_description, activities_description, 
                activity_id, output_description, output_id, user_description, adoption_description, 
                outcome_description, outcome_id, impact_description, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $query);
        if (!$stmt) {
            throw new Exception("Failed to prepare statement: " . mysqli_error($conn));
        }

        mysqli_stmt_bind_param(
            $stmt,
            "isssisssssiss",
            $project_id,
            $pathway_sequence,
            $input_description,
            $activities_description,
            $activity_id,
            $output_description,
            $output_id,
            $user_description,
            $adoption_description,
            $outcome_description,
            $outcome_id,
            $impact_description,
            $user_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn));
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conn);

        $_SESSION['success_message'] = "บันทึกข้อมูล Social Impact Pathway เรียบร้อยแล้ว";

        // ถ้ามาจาก modal ใน step4 ให้กลับไปหน้า impact chain
        if ($from_modal) {
            header("Location: ../impact-chain/step4-outcome.php?project_id=" . $project_id);
        } else {
            header("Location: impact_pathway_fixed.php?project_id=" . $project_id);
        }
        exit();
    } catch (Exception $e) {
        if (isset($conn)) {
            mysqli_rollback($conn);
        }
        $error = $e->getMessage();
    }
}

// Check for success message from session
if (isset($_SESSION['success_message'])) {
    $message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้าง Social Impact Pathway - SROI System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #56ab2f;
            --warning-color: #f093fb;
            --danger-color: #f5576c;
            --info-color: #4ecdc4;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --border-color: #e0e0e0;
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.15);
            --shadow-heavy: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--border-color);
        }

        .form-title {
            font-size: 2rem;
            color: var(--text-dark);
            margin-bottom: 2rem;
            text-align: center;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
        }

        /* Data Status Cards */
        .data-status-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .data-status-card {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-light);
            border: 2px solid var(--border-color);
        }

        .data-status-card.complete {
            border-color: var(--success-color);
            background: linear-gradient(45deg, rgba(86, 171, 47, 0.05), rgba(168, 230, 207, 0.05));
        }

        .data-status-card.incomplete {
            border-color: var(--warning-color);
            background: linear-gradient(45deg, rgba(240, 147, 251, 0.05), rgba(249, 168, 212, 0.05));
        }

        .data-status-header {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            margin-bottom: 1rem;
            font-weight: bold;
            font-size: 1.1rem;
        }

        .status-icon {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 0.8rem;
        }

        .status-icon.complete {
            background: var(--success-color);
        }

        .status-icon.incomplete {
            background: var(--warning-color);
        }

        .data-item {
            background: white;
            border-radius: 6px;
            padding: 0.75rem;
            margin-bottom: 0.5rem;
            border-left: 4px solid var(--primary-color);
            font-size: 0.9rem;
        }

        .data-item:last-child {
            margin-bottom: 0;
        }

        .data-code {
            font-weight: bold;
            color: var(--primary-color);
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(45deg, rgba(86, 171, 47, 0.1), rgba(168, 230, 207, 0.1));
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background: linear-gradient(45deg, rgba(245, 87, 108, 0.1), rgba(240, 147, 251, 0.1));
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        .alert-warning {
            background: linear-gradient(45deg, rgba(240, 147, 251, 0.1), rgba(249, 168, 212, 0.1));
            color: #b45309;
            border: 1px solid #f59e0b;
        }

        /* Form Elements */
        .form-group {
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            padding-top: 0.75rem;
        }

        .step-number {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        /* Basic Info Section */
        .basic-info {
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            background: var(--light-bg);
            border-radius: 12px;
        }

        /* Buttons */
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid var(--light-bg);
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-muted);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-group {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .data-status-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                🎯 SROI System
            </a>
            <ul class="nav-menu">
                <li><a href="../dashboard.php" class="nav-link">📊 Dashboard</a></li>
                <li><a href="../project-list.php" class="nav-link">📋 โครงการ</a></li>
                <li><a href="impact_pathway_fixed.php" class="nav-link active">📈 การวิเคราะห์</a></li>
                <li><a href="../reports.php" class="nav-link">📄 รายงาน</a></li>
                <li><a href="../settings.php" class="nav-link">⚙️ ตั้งค่า</a></li>
            </ul>
            <div style="color: white;">👤 <?php echo htmlspecialchars($username); ?></div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <div class="form-container">
            <h2 class="form-title">🔗 Social Impact Pathway</h2>

            <!-- Alert Messages -->
            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <?php if ($message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <!-- Project Selection -->
            <div class="basic-info">
                <div class="form-group">
                    <label class="form-label">
                        โครงการ <span style="color: var(--danger-color);">*</span>
                    </label>
                    <select class="form-select" id="project_id" name="project_id" required onchange="loadProjectData()">
                        <option value="">เลือกโครงการ</option>
                        <?php if ($projects_result): ?>
                            <?php mysqli_data_seek($projects_result, 0); ?>
                            <?php while ($project = mysqli_fetch_assoc($projects_result)): ?>
                                <option value="<?php echo $project['id']; ?>"
                                    <?php echo ($project_id == $project['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['name']); ?>
                                </option>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <!-- Project Info Display -->
            <?php if ($selected_project): ?>
                <div class="alert alert-success">
                    <strong>โครงการที่เลือก:</strong> <?php echo htmlspecialchars($selected_project['project_code'] . ' - ' . $selected_project['name']); ?>
                </div>

                <!-- Data Completion Status -->
                <div class="data-status-container">
                    <!-- Strategies -->
                    <div class="data-status-card <?php echo $data_completion_status['strategies'] ? 'complete' : 'incomplete'; ?>">
                        <div class="data-status-header">
                            <div class="status-icon <?php echo $data_completion_status['strategies'] ? 'complete' : 'incomplete'; ?>">
                                <?php echo $data_completion_status['strategies'] ? '✓' : '!'; ?>
                            </div>
                            กลยุทธ์ (<?php echo count($project_strategies); ?> รายการ)
                        </div>
                        <?php if (!empty($project_strategies)): ?>
                            <?php foreach (array_slice($project_strategies, 0, 3) as $strategy): ?>
                                <div class="data-item">
                                    <div class="data-code"><?php echo htmlspecialchars($strategy['strategy_code']); ?></div>
                                    <div><?php echo htmlspecialchars(substr($strategy['strategy_name'], 0, 50)); ?>
                                        <?php if (strlen($strategy['strategy_name']) > 50) echo '...'; ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($project_strategies) > 3): ?>
                                <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                                    และอีก <?php echo count($project_strategies) - 3; ?> รายการ
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--text-muted); font-style: italic;">ยังไม่มีการเลือกกลยุทธ์</div>
                        <?php endif; ?>
                    </div>

                    <!-- Activities -->
                    <div class="data-status-card <?php echo $data_completion_status['activities'] ? 'complete' : 'incomplete'; ?>">
                        <div class="data-status-header">
                            <div class="status-icon <?php echo $data_completion_status['activities'] ? 'complete' : 'incomplete'; ?>">
                                <?php echo $data_completion_status['activities'] ? '✓' : '!'; ?>
                            </div>
                            กิจกรรม (<?php echo count($project_activities); ?> รายการ)
                        </div>
                        <?php if (!empty($project_activities)): ?>
                            <?php foreach (array_slice($project_activities, 0, 3) as $activity): ?>
                                <div class="data-item">
                                    <div class="data-code"><?php echo htmlspecialchars($activity['activity_code']); ?></div>
                                    <div><?php echo htmlspecialchars(substr($activity['activity_name'], 0, 50)); ?>
                                        <?php if (strlen($activity['activity_name']) > 50) echo '...'; ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($project_activities) > 3): ?>
                                <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                                    และอีก <?php echo count($project_activities) - 3; ?> รายการ
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--text-muted); font-style: italic;">ยังไม่มีการเลือกกิจกรรม</div>
                        <?php endif; ?>
                    </div>

                    <!-- Outputs -->
                    <div class="data-status-card <?php echo $data_completion_status['outputs'] ? 'complete' : 'incomplete'; ?>">
                        <div class="data-status-header">
                            <div class="status-icon <?php echo $data_completion_status['outputs'] ? 'complete' : 'incomplete'; ?>">
                                <?php echo $data_completion_status['outputs'] ? '✓' : '!'; ?>
                            </div>
                            ผลผลิต (<?php echo count($project_outputs); ?> รายการ)
                        </div>
                        <?php if (!empty($project_outputs)): ?>
                            <?php foreach (array_slice($project_outputs, 0, 3) as $output): ?>
                                <div class="data-item">
                                    <div class="data-code"><?php echo htmlspecialchars($output['output_sequence']); ?></div>
                                    <div><?php echo htmlspecialchars(substr($output['output_description'], 0, 50)); ?>
                                        <?php if (strlen($output['output_description']) > 50) echo '...'; ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($project_outputs) > 3): ?>
                                <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                                    และอีก <?php echo count($project_outputs) - 3; ?> รายการ
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--text-muted); font-style: italic;">ยังไม่มีการกำหนดผลผลิต</div>
                        <?php endif; ?>
                    </div>

                    <!-- Outcomes -->
                    <div class="data-status-card <?php echo $data_completion_status['outcomes'] ? 'complete' : 'incomplete'; ?>">
                        <div class="data-status-header">
                            <div class="status-icon <?php echo $data_completion_status['outcomes'] ? 'complete' : 'incomplete'; ?>">
                                <?php echo $data_completion_status['outcomes'] ? '✓' : '!'; ?>
                            </div>
                            ผลลัพธ์ (<?php echo count($project_outcomes); ?> รายการ)
                        </div>
                        <?php if (!empty($project_outcomes)): ?>
                            <?php foreach (array_slice($project_outcomes, 0, 3) as $outcome): ?>
                                <div class="data-item">
                                    <div class="data-code"><?php echo htmlspecialchars($outcome['outcome_sequence']); ?></div>
                                    <div><?php echo htmlspecialchars(substr($outcome['outcome_description'], 0, 50)); ?>
                                        <?php if (strlen($outcome['outcome_description']) > 50) echo '...'; ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($project_outcomes) > 3): ?>
                                <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                                    และอีก <?php echo count($project_outcomes) - 3; ?> รายการ
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--text-muted); font-style: italic;">ยังไม่มีการกำหนดผลลัพธ์</div>
                        <?php endif; ?>
                    </div>

                    <!-- Impact Ratios -->
                    <div class="data-status-card <?php echo $data_completion_status['impact_ratios'] ? 'complete' : 'incomplete'; ?>">
                        <div class="data-status-header">
                            <div class="status-icon <?php echo $data_completion_status['impact_ratios'] ? 'complete' : 'incomplete'; ?>">
                                <?php echo $data_completion_status['impact_ratios'] ? '✓' : '!'; ?>
                            </div>
                            Impact Ratios (<?php echo count($project_impact_ratios); ?> รายการ)
                        </div>
                        <?php if (!empty($project_impact_ratios)): ?>
                            <?php foreach (array_slice($project_impact_ratios, 0, 3) as $ratio): ?>
                                <div class="data-item">
                                    <div class="data-code">Ratio ID: <?php echo htmlspecialchars($ratio['ratio_id']); ?></div>
                                    <div>Outcome ID: <?php echo htmlspecialchars($ratio['outcome_id']); ?> |
                                        จำนวน: <?php echo htmlspecialchars($ratio['benefit_note']); ?></div>
                                </div>
                            <?php endforeach; ?>
                            <?php if (count($project_impact_ratios) > 3): ?>
                                <div style="text-align: center; color: var(--text-muted); font-size: 0.8rem;">
                                    และอีก <?php echo count($project_impact_ratios) - 3; ?> รายการ
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div style="color: var(--text-muted); font-style: italic;">ยังไม่มีการคำนวณ Impact Ratios</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Warning if data incomplete -->
                <?php
                $incomplete_count = 5 - array_sum(array_values($data_completion_status));
                if ($incomplete_count > 0):
                ?>
                    <div class="alert alert-warning">
                        <strong>⚠️ หมายเหตุ:</strong> โครงการนี้ยังมีข้อมูลไม่ครบถ้วนใน <?php echo $incomplete_count; ?> ส่วน
                        คุณสามารถสร้าง Impact Pathway ได้ แต่ควรเพิ่มข้อมูลให้ครบถ้วนเพื่อผลลัพธ์ที่แม่นยำ
                    </div>
                <?php endif; ?>

                <!-- Existing Pathways -->
                <?php if (!empty($existing_pathways)): ?>
                    <div class="alert alert-success">
                        <strong>📋 มีข้อมูล Impact Pathway แล้ว <?php echo count($existing_pathways); ?> รายการ</strong>
                        <ul style="margin-top: 0.5rem; margin-bottom: 0;">
                            <?php foreach ($existing_pathways as $pathway): ?>
                                <li>ลำดับ <?php echo htmlspecialchars($pathway['pathway_sequence']); ?>:
                                    <?php echo htmlspecialchars(substr($pathway['outcome_description'], 0, 100)); ?>
                                    <?php if (strlen($pathway['outcome_description']) > 100) echo '...'; ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <!-- Form Section -->
                <form method="POST" id="createPathwayForm">
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                    <h3 style="margin: 2rem 0 1.5rem 0; color: var(--primary-color);">📝 สร้าง Impact Pathway ใหม่</h3>

                    <div class="form-group">
                        <label class="form-label">
                            ลำดับของห่วงโซ่ <span style="color: var(--danger-color);">*</span>
                        </label>
                        <input type="text" class="form-input" id="pathway_sequence" name="pathway_sequence"
                            placeholder="เช่น 1.1, 1.2, 2.1" required maxlength="10">
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="step-number">1</span>
                            ปัจจัยนำเข้า (Input)
                        </label>
                        <div>
                            <input type="text" class="form-input" name="input_description"
                                placeholder="ระบุทรัพยากรและปัจจัยนำเข้าที่ใช้ในโครงการ">
                            <div class="form-help">เช่น บุคลากร งบประมาณ อุปกรณ์ เวลา</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="step-number">2</span>
                            กิจกรรม (Activities)
                        </label>
                        <div>
                            <select class="form-select" name="activity_id" id="activity_id">
                                <option value="">เลือกกิจกรรม (ถ้าต้องการ)</option>
                                <?php foreach ($project_activities as $activity): ?>
                                    <option value="<?php echo $activity['activity_id']; ?>">
                                        <?php echo htmlspecialchars($activity['activity_code'] . ' - ' . $activity['activity_name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-input" name="activities_description"
                                placeholder="หรือระบุกิจกรรมเพิ่มเติม" style="margin-top: 0.5rem;">
                            <div class="form-help">เลือกจากกิจกรรมที่โครงการได้เลือกไว้ หรือระบุเพิ่มเติม</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="step-number">3</span>
                            ผลผลิต (Output)
                        </label>
                        <div>
                            <select class="form-select" name="output_id" id="output_id">
                                <option value="">เลือกผลผลิต (ถ้าต้องการ)</option>
                                <?php foreach ($project_outputs as $output): ?>
                                    <option value="<?php echo $output['output_id']; ?>">
                                        <?php echo htmlspecialchars($output['output_sequence'] . ' - ' . substr($output['output_description'], 0, 80)); ?>
                                        <?php if (strlen($output['output_description']) > 80) echo '...'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-input" name="output_description"
                                placeholder="หรือระบุผลผลิตเพิ่มเติม" style="margin-top: 0.5rem;">
                            <div class="form-help">เลือกจากผลผลิตที่โครงการได้กำหนดไว้ หรือระบุเพิ่มเติม</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="step-number">4</span>
                            ผู้ใช้ประโยชน์ (User)
                        </label>
                        <div>
                            <input type="text" class="form-input" name="user_description"
                                placeholder="ระบุกลุ่มเป้าหมายที่ได้รับประโยชน์จากผลผลิต">
                            <div class="form-help">เช่น นักเรียน ครู ชุมชน เกษตรกร ผู้ประกอบการ</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="step-number">5</span>
                            การนำไปใช้ (Adoption)
                        </label>
                        <div>
                            <input type="text" class="form-input" name="adoption_description"
                                placeholder="อธิบายวิธีการและกระบวนการนำผลผลิตไปใช้">
                            <div class="form-help">อธิบายว่าผู้ใช้ประโยชน์จะนำผลผลิตไปใช้อย่างไร</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="step-number">6</span>
                            ผลลัพธ์ (Outcome)
                        </label>
                        <div>
                            <select class="form-select" name="outcome_id" id="outcome_id">
                                <option value="">เลือกผลลัพธ์ (ถ้าต้องการ)</option>
                                <?php foreach ($project_outcomes as $outcome): ?>
                                    <option value="<?php echo $outcome['outcome_id']; ?>">
                                        <?php echo htmlspecialchars($outcome['outcome_sequence'] . ' - ' . substr($outcome['outcome_description'], 0, 80)); ?>
                                        <?php if (strlen($outcome['outcome_description']) > 80) echo '...'; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <input type="text" class="form-input" name="outcome_description"
                                placeholder="หรือระบุผลลัพธ์เพิ่มเติม" style="margin-top: 0.5rem;">
                            <div class="form-help">เลือกจากผลลัพธ์ที่โครงการได้กำหนดไว้ หรือระบุเพิ่มเติม</div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            <span class="step-number">7</span>
                            ผลกระทบ (Impact)
                        </label>
                        <div>
                            <input type="text" class="form-input" name="impact_description"
                                placeholder="อธิบายผลกระทบระยะยาวต่อสังคมและสิ่งแวดล้อม">
                            <div class="form-help">อธิบายผลกระทบเชิงบวกระยะยาวที่เกิดขึ้นกับสังคม</div>
                        </div>
                    </div>

                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="goBack()">
                            ← กลับ
                        </button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            💾 บันทึก Impact Pathway
                        </button>
                    </div>
                </form>

            <?php else: ?>
                <div class="alert alert-warning">
                    <strong>📝 กรุณาเลือกโครงการ</strong> เพื่อดูข้อมูลที่สัมพันธ์กันและสร้าง Social Impact Pathway
                </div>
            <?php endif; ?>

        </div>
    </div>

    <script>
        function loadProjectData() {
            const projectId = document.getElementById('project_id').value;
            if (projectId) {
                window.location.href = 'impact_pathway_fixed.php?project_id=' + projectId;
            }
        }

        function goBack() {
            if (confirm('คุณต้องการกลับหรือไม่?')) {
                window.location.href = '../dashboard.php';
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createPathwayForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const pathwaySequence = document.getElementById('pathway_sequence');
                    if (pathwaySequence && !pathwaySequence.value.trim()) {
                        e.preventDefault();
                        alert('กรุณากรอกลำดับของห่วงโซ่');
                        return false;
                    }
                });
            }
        });

        console.log('🔗 Enhanced Social Impact Pathway Form loaded successfully!');
    </script>
</body>

</html>