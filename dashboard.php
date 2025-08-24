<?php
session_start();
require_once "config.php";

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ดึงข้อมูลสถิติและโครงการของผู้ใช้
$user_id = $_SESSION['user_id'];

// ดึงโครงการล่าสุดของผู้ใช้ (5 โครงการ)
$projects_query = "SELECT id, project_code, name, status, budget, created_at, updated_at 
                   FROM projects 
                   WHERE created_by = ? 
                   ORDER BY updated_at DESC 
                   LIMIT 5";
$projects_stmt = mysqli_prepare($conn, $projects_query);
mysqli_stmt_bind_param($projects_stmt, 's', $user_id);
mysqli_stmt_execute($projects_stmt);
$projects_result = mysqli_stmt_get_result($projects_stmt);
$user_projects = mysqli_fetch_all($projects_result, MYSQLI_ASSOC);
mysqli_stmt_close($projects_stmt);

// ดึงสถิติของผู้ใช้
$stats_query = "SELECT 
                    COUNT(*) as total_projects,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_projects,
                    SUM(CASE WHEN status = 'incompleted' THEN 1 ELSE 0 END) as incompleted_projects,
                    SUM(budget) as total_budget
                FROM projects 
                WHERE created_by = ?";
$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, 's', $user_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$user_stats = mysqli_fetch_assoc($stats_result);
mysqli_stmt_close($stats_stmt);

// กำหนดค่าเริ่มต้นหากไม่มีข้อมูล
$user_stats = $user_stats ?? [
    'total_projects' => 0,
    'completed_projects' => 0,
    'incompleted_projects' => 0,
    'total_budget' => 0
];

// ดึงกิจกรรมล่าสุดของผู้ใช้
$activity_query = "
    SELECT 
        'project_created' as type,
        CONCAT('สร้างโครงการ \"', LEFT(name, 30), '...\"') as text,
        created_at as timestamp,
        '➕' as icon
    FROM projects 
    WHERE created_by = ?
    
    UNION ALL
    
    SELECT 
        'project_updated' as type,
        CONCAT('อัปเดตโครงการ \"', LEFT(name, 30), '...\"') as text,
        updated_at as timestamp,
        '📊' as icon
    FROM projects 
    WHERE created_by = ? AND updated_at > created_at
    
    ORDER BY timestamp DESC 
    LIMIT 5
";

$activity_stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($activity_stmt, 'ss', $user_id, $user_id);
mysqli_stmt_execute($activity_stmt);
$activities_result = mysqli_stmt_get_result($activity_stmt);
$recent_activities = [];
while ($row = mysqli_fetch_assoc($activities_result)) {
    $recent_activities[] = [
        'type' => $row['type'],
        'text' => $row['text'],
        'icon' => $row['icon']
    ];
}

// ฟังก์ชันแปลงสถานะ
function getStatusText($status)
{
    switch ($status) {
        case 'completed':
            return 'เสร็จสิ้น';
        case 'incompleted':
            return 'ยังไม่เสร็จ';
        default:
            return 'ไม่ระบุ';
    }
}

function getStatusClass($status)
{
    switch ($status) {
        case 'completed':
            return 'status-completed';
        case 'incompleted':
            return 'status-planning';
        default:
            return 'status-active';
    }
}

// ฟังก์ชันจัดรูปแบบวันที่
function formatThaiDate($date)
{
    $thai_months = [
        '01' => 'ม.ค.',
        '02' => 'ก.พ.',
        '03' => 'มี.ค.',
        '04' => 'เม.ย.',
        '05' => 'พ.ค.',
        '06' => 'มิ.ย.',
        '07' => 'ก.ค.',
        '08' => 'ส.ค.',
        '09' => 'ก.ย.',
        '10' => 'ต.ค.',
        '11' => 'พ.ย.',
        '12' => 'ธ.ค.'
    ];

    $timestamp = strtotime($date);
    $day = date('j', $timestamp);
    $month = $thai_months[date('m', $timestamp)];
    $year = date('Y', $timestamp) + 543; // แปลงเป็น พ.ศ.

    return "$day $month $year";
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SROI Dashboard - ระบบประเมินผลกระทบทางสังคม</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
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
            transition: transform 0.3s ease;
        }

        .logo:hover {
            transform: scale(1.05);
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
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateY(-2px);
        }

        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            margin-bottom: 2rem;
            text-align: center;
        }

        .page-title {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
            text-align: right;
        }

        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--shadow-medium);
            transition: all 0.3s ease;
            border: 1px solid var(--border-color);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--secondary-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-heavy);
        }

        .stat-card.success::before {
            background: linear-gradient(90deg, var(--success-color), #a8e6cf);
        }

        .stat-card.warning::before {
            background: linear-gradient(90deg, var(--warning-color), #f5576c);
        }

        .stat-card.info::before {
            background: linear-gradient(90deg, var(--info-color), #44a08d);
        }

        .stat-header {
            display: flex;
            justify-content: flex-end;
            align-items: flex-start;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        }

        .stat-icon.success {
            background: linear-gradient(45deg, var(--success-color), #a8e6cf);
        }

        .stat-icon.warning {
            background: linear-gradient(45deg, var(--warning-color), #f5576c);
        }

        .stat-icon.info {
            background: linear-gradient(45deg, var(--info-color), #44a08d);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--text-dark);
            line-height: 1;
        }

        .stat-label {
            font-size: 0.9rem;
            color: var(--text-muted);
            margin-bottom: 0.5rem;
        }

        .stat-change {
            font-size: 0.8rem;
            font-weight: 500;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .stat-change.positive {
            background: rgba(86, 171, 47, 0.1);
            color: var(--success-color);
        }

        .stat-change.negative {
            background: rgba(245, 87, 108, 0.1);
            color: var(--danger-color);
        }

        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .content-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: var(--shadow-medium);
            border: 1px solid var(--border-color);
        }

        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid var(--light-bg);
        }

        .card-title {
            font-size: 1.4rem;
            font-weight: 600;
            color: var(--text-dark);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-action {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 500;
            font-size: 0.9rem;
            transition: color 0.3s ease;
        }

        .card-action:hover {
            color: var(--secondary-color);
        }

        /* Project List */
        .project-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .project-list .table {
            margin-bottom: 0;
        }

        .project-list .table th {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            font-weight: 600;
            border: none;
            padding: 1rem 0.75rem;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .project-list .table td {
            padding: 0.75rem;
            vertical-align: middle;
            border: none;
            border-bottom: 1px solid var(--border-color);
        }

        .project-list .table tr:hover {
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), transparent);
        }

        .project-code {
            font-family: 'Courier New', monospace;
            background: var(--light-bg);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-size: 0.85rem;
            color: var(--text-dark);
            font-weight: 500;
        }

        .project-name {
            font-weight: 600;
            color: var(--text-dark);
        }

        .btn-sm {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
        }

        .project-item {
            padding: 1rem;
            border: 1px solid var(--border-color);
            border-radius: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .project-item:hover {
            border-color: var(--primary-color);
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), transparent);
            transform: translateX(5px);
        }

        .project-header {
            display: flex;
            justify-content: between;
            align-items: flex-start;
            margin-bottom: 0.5rem;
        }

        .project-name {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .project-code {
            font-size: 0.8rem;
            color: var(--text-muted);
            background: var(--light-bg);
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            font-family: monospace;
        }

        .project-status {
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-active {
            background: rgba(86, 171, 47, 0.1);
            color: var(--success-color);
        }

        .status-planning {
            background: rgba(240, 147, 251, 0.1);
            color: var(--warning-color);
        }

        .status-completed {
            background: rgba(78, 205, 196, 0.1);
            color: var(--info-color);
        }

        .project-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        .project-budget {
            font-weight: 500;
        }

        .project-sroi {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-weight: 600;
        }

        .sroi-ratio {
            color: var(--success-color);
        }

        /* Quick Actions */
        .quick-actions {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .action-button {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            background: white;
            text-decoration: none;
            color: var(--text-dark);
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .action-button:hover {
            border-color: var(--primary-color);
            background: linear-gradient(90deg, rgba(102, 126, 234, 0.05), transparent);
            transform: translateY(-2px);
            box-shadow: var(--shadow-light);
        }

        .action-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            color: white;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
        }

        .action-content h4 {
            margin-bottom: 0.25rem;
            font-weight: 600;
        }

        .action-content p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Recent Activity */
        .activity-list {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 1rem;
            padding: 1rem;
            border-radius: 8px;
            transition: background 0.3s ease;
        }

        .activity-item:hover {
            background: var(--light-bg);
        }

        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            color: white;
            background: linear-gradient(45deg, var(--info-color), #44a08d);
            flex-shrink: 0;
        }

        .activity-content {
            flex: 1;
        }

        .activity-text {
            font-size: 0.9rem;
            margin-bottom: 0.25rem;
        }

        .activity-time {
            font-size: 0.8rem;
            color: var(--text-muted);
        }

        /* Loading Animation */
        .loading {
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .spinner {
            width: 40px;
            height: 40px;
            border: 4px solid var(--border-color);
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 1rem;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }

            .main-container {
                padding: 1rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .content-grid {
                grid-template-columns: 1fr;
            }

            .project-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }

            /* Table responsive */
            .project-list .table {
                font-size: 0.8rem;
            }

            .project-list .table th,
            .project-list .table td {
                padding: 0.5rem 0.25rem;
            }

            .project-name {
                max-width: 120px !important;
            }

            .d-flex.gap-1 {
                flex-direction: column;
                gap: 0.25rem !important;
            }

            .btn-sm {
                font-size: 0.7rem;
                padding: 0.2rem 0.4rem;
            }
        }

        /* Chart Container */
        .chart-container {
            height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 8px;
            margin: 1rem 0;
        }

        .chart-placeholder {
            text-align: center;
            color: var(--text-muted);
        }

        /* Notification Badge */
        .notification-badge {
            position: relative;
        }

        .notification-badge::after {
            content: '3';
            position: absolute;
            top: -8px;
            right: -8px;
            background: var(--danger-color);
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">ระบบประเมินผลกระทบทางสังคม</h1>
            <p class="page-subtitle">Social Return on Investment (SROI) Management System</p>
        </div>

        <!-- Stats Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">โครงการทั้งหมด</div>
                        <div class="stat-number" id="totalProjects"><?php echo $user_stats['total_projects']; ?></div>
                        <div class="stat-change positive">
                            📁 โครงการของคุณ
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card success">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">โครงการที่เสร็จสิ้น</div>
                        <div class="stat-number" id="completedProjects"><?php echo $user_stats['completed_projects']; ?></div>
                        <div class="stat-change positive">
                            ✅ เสร็จแล้ว
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card warning">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">งบประมาณรวม</div>
                        <div class="stat-number" id="totalBudget"><?php echo $user_stats['total_budget'] ? number_format($user_stats['total_budget'], 0) : '0'; ?></div>
                        <div class="stat-change positive">
                            💰 บาท
                        </div>
                    </div>
                </div>
            </div>

            <div class="stat-card info">
                <div class="stat-header">
                    <div>
                        <div class="stat-label">โครงการที่ยังไม่เสร็จ</div>
                        <div class="stat-number" id="incompletedProjects"><?php echo $user_stats['incompleted_projects']; ?></div>
                        <div class="stat-change <?php echo $user_stats['incompleted_projects'] > 0 ? 'negative' : 'positive'; ?>">
                            🔄 ดำเนินการอยู่
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Content Grid -->
        <div class="content-grid">
            <!-- Recent Projects -->
            <div class="content-card">
                <div class="card-header">
                    <h3 class="card-title">
                        📋 โครงการล่าสุด
                    </h3>
                    <a href="project-list.php" class="card-action">ดูทั้งหมด →</a>
                </div>
                <div class="project-list" id="recentProjects">
                    <?php if (empty($user_projects)): ?>
                        <div class="text-center text-muted py-4">
                            <i class="fas fa-folder-open fa-3x mb-3" style="color: #ddd;"></i>
                            <p>ยังไม่มีโครงการ</p>
                            <a href="create-project.php" class="btn btn-primary">
                                <i class="fas fa-plus"></i> สร้างโครงการแรก
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Project Table -->
                        <div style="overflow-x: auto;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>รหัสโครงการ</th>
                                        <th>ชื่อโครงการ</th>
                                        <th>งบประมาณ</th>
                                        <th>สถานะ</th>
                                        <th>วันที่อัปเดต</th>
                                        <th>การจัดการ</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($user_projects as $project): ?>
                                        <tr>
                                            <td>
                                                <span class="project-code"><?php echo htmlspecialchars($project['project_code']); ?></span>
                                            </td>
                                            <td>
                                                <div class="project-name" style="max-width: 200px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;"
                                                    title="<?php echo htmlspecialchars($project['name']); ?>">
                                                    <?php echo htmlspecialchars($project['name']); ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">
                                                    ฿<?php echo number_format($project['budget']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?php echo $project['status'] == 'completed' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                                    <?php echo getStatusText($project['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small class="text-muted"><?php echo formatThaiDate($project['updated_at']); ?></small>
                                            </td>
                                            <td>
                                                <div class="d-flex gap-1">
                                                    <a href="impact-chain/step1-strategy.php?project_id=<?php echo $project['id']; ?>"
                                                        class="btn btn-primary btn-sm" title="สร้าง Impact Chain">
                                                        <i class="fas fa-link"></i>
                                                    </a>
                                                    <button onclick="viewProject(<?php echo $project['id']; ?>)"
                                                        class="btn btn-outline-secondary btn-sm" title="ดูรายละเอียด">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Quick Actions & Recent Activity -->
            <div style="display: flex; flex-direction: column; gap: 2rem;">
                <!-- Quick Actions -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            ⚡ การดำเนินการด่วน
                        </h3>
                    </div>
                    <div class="quick-actions">
                        <a href="create-project.php" class="action-button">
                            <div class="action-icon">1</div>
                            <div class="action-content">
                                <h4>สร้างโครงการใหม่</h4>
                                <p>เริ่มต้นการประเมิน SROI</p>
                            </div>
                        </a>
                        <?php if (!empty($user_projects)): ?>
                            <a href="impact-chain/step1-strategy.php?project_id=<?php echo $user_projects[0]['id']; ?>" class="action-button">
                                <div class="action-icon">2</div>
                                <div class="action-content">
                                    <h4>สร้าง Impact Chain</h4>
                                    <p>โครงการ: <?php echo htmlspecialchars(mb_substr($user_projects[0]['name'], 0, 25)) . (mb_strlen($user_projects[0]['name']) > 25 ? '...' : ''); ?></p>
                                </div>
                            </a>
                        <?php else: ?>
                            <a href="create-project.php" class="action-button">
                                <div class="action-icon">2</div>
                                <div class="action-content">
                                    <h4>สร้าง Impact Chain</h4>
                                    <p>สร้างโครงการก่อนเพื่อดำเนินการ</p>
                                </div>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($user_projects)): ?>
                            <a href="impact_pathway/impact_pathway.php?project_id=<?php echo $user_projects[0]['id']; ?>" class="action-button">
                                <div class="action-icon">3</div>
                                <div class="action-content">
                                    <h4>สร้าง Impact Pathway</h4>
                                    <p>โครงการ: <?php echo htmlspecialchars(mb_substr($user_projects[0]['name'], 0, 25)) . (mb_strlen($user_projects[0]['name']) > 25 ? '...' : ''); ?></p>
                                </div>
                            </a>
                        <?php else: ?>
                            <a href="create-project.php" class="action-button">
                                <div class="action-icon">3</div>
                                <div class="action-content">
                                    <h4>สร้าง Impact Pathway</h4>
                                    <p>สร้างโครงการก่อนเพื่อดำเนินการ</p>
                                </div>
                            </a>
                        <?php endif; ?>
                        <?php if (!empty($user_projects)): ?>
                            <a href="sroi-expost/report-sroi.php?project_id=<?php echo $user_projects[0]['id']; ?>" class="action-button">
                                <div class="action-icon">4</div>
                                <div class="action-content">
                                    <h4>สร้างรายงาน</h4>
                                    <p>โครงการ: <?php echo htmlspecialchars(mb_substr($user_projects[0]['name'], 0, 25)) . (mb_strlen($user_projects[0]['name']) > 25 ? '...' : ''); ?></p>
                                </div>
                            </a>
                        <?php else: ?>
                            <a href="create-project.php" class="action-button">
                                <div class="action-icon">4</div>
                                <div class="action-content">
                                    <h4>สร้างรายงาน</h4>
                                    <p>สร้างโครงการก่อนเพื่อดำเนินการ</p>
                                </div>
                            </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="content-card">
                    <div class="card-header">
                        <h3 class="card-title">
                            🕒 กิจกรรมล่าสุด
                        </h3>
                    </div>
                    <div class="activity-list" id="recentActivity">
                        <!-- Activities will be loaded here -->
                        <div class="loading">
                            <div class="spinner"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="content-card">
            <div class="card-header">
                <h3 class="card-title">
                    📈 แนวโน้ม SROI Ratio
                </h3>
                <select id="chartPeriod" onchange="updateChart()">
                    <option value="6months">6 เดือนล่าสุด</option>
                    <option value="1year" selected>1 ปีล่าสุด</option>
                    <option value="2years">2 ปีล่าสุด</option>
                </select>
            </div>
            <div class="chart-container" id="sroiChart">
                <div class="chart-placeholder">
                    📊 กราฟแสดงแนวโน้ม SROI Ratio<br>
                    <small>จะแสดงเมื่อมีข้อมูลโครงการ</small>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ข้อมูลกิจกรรมล่าสุดจากฐานข้อมูล
        const recentActivities = <?php echo json_encode($recent_activities); ?>;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            loadRecentActivity();
        });

        function loadRecentActivity() {
            const container = document.getElementById('recentActivity');
            container.innerHTML = '';

            if (recentActivities.length === 0) {
                container.innerHTML = '<div class="no-activity">ยังไม่มีกิจกรรม</div>';
                return;
            }

            recentActivities.forEach(activity => {
                const activityItem = createActivityItem(activity);
                container.appendChild(activityItem);
            });
        }

        function createActivityItem(activity) {
            const item = document.createElement('div');
            item.className = 'activity-item';

            item.innerHTML = `
                <div class="activity-icon">${activity.icon}</div>
                <div class="activity-content">
                    <div class="activity-text">${activity.text}</div>
                </div>
            `;

            return item;
        }

        // Navigation functions
        function openProject(projectId) {
            // ไปยังหน้ารายละเอียดโครงการ หรือหน้าจัดการโครงการ
            window.location.href = `project-list.php?project_id=${projectId}`;
        }

        function viewProject(projectId) {
            // ไปยังหน้าดูรายละเอียดโครงการ
            window.location.href = `project-detail.php?id=${projectId}`;
        }

        function createNewProject() {
            console.log('Creating new project');
            alert('เปิดหน้าสร้างโครงการใหม่\n(จะไปหน้าฟอร์มสร้างโครงการ)');
        }

        function importData() {
            console.log('Importing data');
            alert('เปิดหน้านำเข้าข้อมูล\n(จะไปหน้าอัพโหลดไฟล์)');
        }

        function generateReport() {
            console.log('Generating report');
            alert('เปิดหน้าสร้างรายงาน\n(จะไปหน้าเลือกประเภทรายงาน)');
        }

        function updateChart() {
            const period = document.getElementById('chartPeriod').value;
            console.log('Updating chart for period:', period);

            // Simulate chart update
            const chartContainer = document.getElementById('sroiChart');
            chartContainer.innerHTML = `
                <div class="chart-placeholder">
                    📊 กำลังโหลดข้อมูล ${period}...<br>
                    <div class="spinner" style="margin: 1rem auto;"></div>
                </div>
            `;

            setTimeout(() => {
                chartContainer.innerHTML = `
                    <div class="chart-placeholder">
                        📈 กราฟแนวโน้ม SROI Ratio (${period})<br>
                        <small>ข้อมูลจำลอง: แนวโน้มเพิ่มขึ้น 15% ในช่วง ${period}</small>
                    </div>
                `;
            }, 1000);
        }

        // Real-time updates simulation
        function simulateRealTimeUpdates() {
            setInterval(() => {
                // Simulate notification badge update
                const badge = document.querySelector('.notification-badge::after');
                // Could update notification count here

                // Simulate new activity
                if (Math.random() > 0.95) { // 5% chance every 5 seconds
                    const newActivity = {
                        type: 'system_update',
                        text: 'อัพเดทข้อมูลโครงการอัตโนมัติ',
                        time: 'เมื่อสักครู่',
                        icon: '🔄'
                    };

                    const container = document.getElementById('recentActivity');
                    const newItem = createActivityItem(newActivity);
                    container.insertBefore(newItem, container.firstChild);

                    // Remove last item if more than 4 activities
                    if (container.children.length > 4) {
                        container.removeChild(container.lastChild);
                    }
                }
            }, 5000);
        }

        // Start real-time updates
        simulateRealTimeUpdates();

        // Keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            // Ctrl/Cmd + N for new project
            if ((e.ctrlKey || e.metaKey) && e.key === 'n') {
                e.preventDefault();
                createNewProject();
            }

            // Ctrl/Cmd + R for refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                location.reload();
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add loading states for better UX
        function showLoading(elementId) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = '<div class="loading"><div class="spinner"></div></div>';
            }
        }

        function hideLoading(elementId, content) {
            const element = document.getElementById(elementId);
            if (element) {
                element.innerHTML = content;
            }
        }

        // Error handling
        window.addEventListener('error', function(e) {
            console.error('Dashboard error:', e.error);
            // Could show user-friendly error message
        });

        // Responsive navigation toggle
        function toggleMobileMenu() {
            const navMenu = document.querySelector('.nav-menu');
            navMenu.classList.toggle('mobile-open');
        }

        // Add mobile menu styles
        const mobileStyles = `
            @media (max-width: 768px) {
                .nav-menu {
                    display: none;
                    position: absolute;
                    top: 100%;
                    left: 0;
                    right: 0;
                    background: white;
                    box-shadow: var(--shadow-medium);
                    border-radius: 0 0 15px 15px;
                    padding: 1rem;
                }
                
                .nav-menu.mobile-open {
                    display: flex;
                }
                
                .mobile-menu-toggle {
                    display: block;
                    background: none;
                    border: none;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: var(--text-dark);
                }
            }
            
            @media (min-width: 769px) {
                .mobile-menu-toggle {
                    display: none;
                }
            }
        `;

        // Add mobile styles to head
        const styleSheet = document.createElement('style');
        styleSheet.textContent = mobileStyles;
        document.head.appendChild(styleSheet);

        // Add mobile menu toggle button
        const navContainer = document.querySelector('.nav-container');
        const mobileToggle = document.createElement('button');
        mobileToggle.className = 'mobile-menu-toggle';
        mobileToggle.innerHTML = '☰';
        mobileToggle.onclick = toggleMobileMenu;
        navContainer.insertBefore(mobileToggle, navContainer.querySelector('.nav-menu'));

        // Local storage for user preferences
        function saveUserPreference(key, value) {
            localStorage.setItem(`sroi_${key}`, JSON.stringify(value));
        }

        function getUserPreference(key, defaultValue) {
            const stored = localStorage.getItem(`sroi_${key}`);
            return stored ? JSON.parse(stored) : defaultValue;
        }

        // Apply saved preferences
        const savedChartPeriod = getUserPreference('chartPeriod', '1year');
        document.getElementById('chartPeriod').value = savedChartPeriod;

        // Save preferences on change
        document.getElementById('chartPeriod').addEventListener('change', function() {
            saveUserPreference('chartPeriod', this.value);
        });

        // Performance monitoring
        function measurePerformance() {
            if ('performance' in window) {
                window.addEventListener('load', () => {
                    const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
                    console.log(`Dashboard loaded in ${loadTime}ms`);

                    // Could send to analytics
                    if (loadTime > 3000) {
                        console.warn('Slow dashboard load time detected');
                    }
                });
            }
        }

        measurePerformance();

        // Accessibility improvements
        function enhanceAccessibility() {
            // Add ARIA labels
            document.querySelector('.nav-menu').setAttribute('role', 'navigation');
            document.querySelector('.stats-grid').setAttribute('role', 'region');
            document.querySelector('.stats-grid').setAttribute('aria-label', 'สถิติภาพรวม');

            // Focus management
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Tab') {
                    document.body.classList.add('keyboard-navigation');
                }
            });

            document.addEventListener('mousedown', function() {
                document.body.classList.remove('keyboard-navigation');
            });
        }

        enhanceAccessibility();

        // Add focus styles for keyboard navigation
        const a11yStyles = `
            .keyboard-navigation .nav-link:focus,
            .keyboard-navigation .action-button:focus,
            .keyboard-navigation .project-item:focus {
                outline: 2px solid var(--primary-color);
                outline-offset: 2px;
            }
        `;

        const a11yStyleSheet = document.createElement('style');
        a11yStyleSheet.textContent = a11yStyles;
        document.head.appendChild(a11yStyleSheet);

        console.log('🎯 SROI Dashboard initialized successfully!');
    </script>
</body>

</html>