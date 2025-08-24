<?php
session_start();
require_once 'config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ดึงข้อมูล session ที่จำเป็น
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'];
$user_department = $_SESSION['department'];

// รับข้อความสำเร็จจาก session
$success_message = '';
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// รับข้อความข้อผิดพลาดจาก session
$error_message = '';
if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// ตั้งค่าการแบ่งหน้า
$limit = 10; // จำนวนรายการต่อหน้า
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// ตั้งค่าการค้นหา
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : '';

// สร้าง WHERE clause สำหรับการกรอง
$where_conditions = [];
$params = [];
$param_types = '';

// เพิ่มเงื่อนไขให้แสดงเฉพาะโครงการของผู้ใช้ที่ login
$where_conditions[] = "created_by = ?";
$params[] = $user_id;
$param_types .= 's';

if (!empty($search)) {
    $where_conditions[] = "(project_code LIKE ? OR name LIKE ? OR organization LIKE ? OR project_manager LIKE ?)";
    $search_param = "%{$search}%";
    $params = array_merge($params, [$search_param, $search_param, $search_param, $search_param]);
    $param_types .= 'ssss';
}

if (!empty($status_filter)) {
    $where_conditions[] = "status = ?";
    $params[] = $status_filter;
    $param_types .= 's';
}

$where_clause = !empty($where_conditions) ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// นับจำนวนรายการทั้งหมด
$count_query = "SELECT COUNT(*) as total FROM projects {$where_clause}";
$count_stmt = mysqli_prepare($conn, $count_query);
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $param_types, ...$params);
}
mysqli_stmt_execute($count_stmt);
$count_result = mysqli_stmt_get_result($count_stmt);
$total_records = mysqli_fetch_assoc($count_result)['total'];
$total_pages = ceil($total_records / $limit);
mysqli_stmt_close($count_stmt);

// ดึงข้อมูลโครงการ
$query = "
    SELECT 
        id, project_code, name, organization, project_manager, 
        budget, status, created_by, created_at, updated_at
    FROM projects 
    {$where_clause}
    ORDER BY created_at DESC
    LIMIT ? OFFSET ?
";

$stmt = mysqli_prepare($conn, $query);
$limit_params = array_merge($params, [$limit, $offset]);
$limit_param_types = $param_types . 'ii';
mysqli_stmt_bind_param($stmt, $limit_param_types, ...$limit_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$projects = mysqli_fetch_all($result, MYSQLI_ASSOC);
mysqli_stmt_close($stmt);

// คำนวณ statistics ของผู้ใช้
$stats_query = "
    SELECT 
        COUNT(*) as total_projects,
        SUM(CASE WHEN status = 'incompleted' THEN 1 ELSE 0 END) as incompleted_count,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_count,
        SUM(budget) as total_budget
    FROM projects 
    WHERE created_by = ?
";
$stats_stmt = mysqli_prepare($conn, $stats_query);
mysqli_stmt_bind_param($stats_stmt, 's', $user_id);
mysqli_stmt_execute($stats_stmt);
$stats_result = mysqli_stmt_get_result($stats_stmt);
$stats = mysqli_fetch_assoc($stats_result);
mysqli_stmt_close($stats_stmt);

// ฟังก์ชันแปลงสถานะ
function getStatusText($status)
{
    $statuses = [
        'incompleted' => 'ยังไม่เสร็จสิ้น',
        'completed' => 'เสร็จสิ้น'
    ];
    return $statuses[$status] ?? $status;
}

function getStatusBadge($status)
{
    $badges = [
        'incompleted' => 'badge-warning',
        'completed' => 'badge-success'
    ];
    return $badges[$status] ?? 'badge-secondary';
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โครงการของฉัน - SROI System</title>
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
            padding-top: 80px;
        }


        /* Main Content */
        .main-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 2rem;
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

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            justify-content: center;
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: white;
        }

        /* Content Container */
        .content-container {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--border-color);
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

        /* Search and Filter */
        .search-filter {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
            flex-wrap: wrap;
            align-items: center;
        }

        .search-box {
            flex: 1;
            min-width: 300px;
        }

        .filter-box {
            min-width: 200px;
        }

        .btn-group {
            display: flex;
            gap: 1rem;
        }

        .btn {
            padding: 0.5rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
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

        .btn-outline {
            background: transparent;
            color: var(--primary-color);
            border: 2px solid var(--primary-color);
        }

        .btn-outline:hover {
            background: var(--primary-color);
            color: white;
        }

        .btn-success {
            background: linear-gradient(45deg, var(--success-color), #a8e6cf);
            color: white;
        }

        /* Table */
        .table-container {
            overflow-x: auto;
            margin-bottom: 2rem;
        }

        .projects-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }

        .projects-table th,
        .projects-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid var(--border-color);
        }

        .projects-table th {
            background: linear-gradient(45deg, #f8f9fa, #e9ecef);
            font-weight: 600;
            color: var(--text-dark);
            position: sticky;
            top: 0;
        }

        .projects-table tr:hover {
            background: rgba(102, 126, 234, 0.05);
        }

        .project-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            color: var(--primary-color);
        }

        .project-name {
            font-weight: 600;
            max-width: 300px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .budget {
            text-align: right;
            font-weight: 600;
            color: var(--success-color);
        }

        /* Status Badges */
        .badge {
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .badge-success {
            background: linear-gradient(45deg, var(--success-color), #a8e6cf);
            color: white;
        }

        .badge-warning {
            background: linear-gradient(45deg, #ffc107, #ffeb3b);
            color: #333;
        }

        .badge-danger {
            background: linear-gradient(45deg, var(--danger-color), #ff8a80);
            color: white;
        }

        .badge-secondary {
            background: linear-gradient(45deg, #6c757d, #95a5a6);
            color: white;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 0.5rem;
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.8rem;
        }

        .btn-view {
            background: var(--info-color);
            color: white;
        }

        .btn-edit {
            background: var(--warning-color);
            color: white;
        }

        .btn-delete {
            background: var(--danger-color);
            color: white;
        }

        /* Pagination */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 0.5rem;
            margin-top: 2rem;
        }

        .pagination a,
        .pagination span {
            padding: 0.5rem 1rem;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            color: var(--text-dark);
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .pagination a:hover {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        .pagination .current {
            background: var(--primary-color);
            color: white;
            border-color: var(--primary-color);
        }

        /* Statistics */
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            border-radius: 15px;
            text-align: center;
            box-shadow: var(--shadow-medium);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .content-container {
                padding: 1rem;
            }

            .search-filter {
                flex-direction: column;
            }

            .search-box,
            .filter-box {
                min-width: 100%;
            }

            .btn-group {
                flex-direction: column;
            }

            .projects-table {
                font-size: 0.8rem;
            }

            .projects-table th,
            .projects-table td {
                padding: 0.5rem;
            }

            .page-title {
                font-size: 2rem;
            }

            .stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>

<body>
    <?php include 'navbar.php'; ?>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Content Container -->
        <div class="content-container">
            <!-- Success Message -->
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Error Message -->
            <?php if ($error_message): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <!-- Statistics -->
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total_projects']; ?></div>
                    <div class="stat-label">โครงการของฉัน</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['incompleted_count']; ?></div>
                    <div class="stat-label">ยังไม่เสร็จสิ้น</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['completed_count']; ?></div>
                    <div class="stat-label">เสร็จสิ้นแล้ว</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo number_format($stats['total_budget'], 0); ?></div>
                    <div class="stat-label">งบประมาณรวม (บาท)</div>
                </div>
            </div>

            <!-- Search and Filter -->
            <div class="search-filter">
                <form method="GET" class="search-box" style="flex: 1;">
                    <input type="text" name="search" class="form-control" placeholder="ค้นหาตามรหัส ชื่อโครงการ หน่วยงาน หรือหัวหน้าโครงการ..." value="<?php echo htmlspecialchars($search); ?>">
                    <?php if (!empty($status_filter)): ?>
                        <input type="hidden" name="status" value="<?php echo htmlspecialchars($status_filter); ?>">
                    <?php endif; ?>
                </form>

                <form method="GET" class="filter-box">
                    <select name="status" class="form-select" onchange="this.form.submit()">
                        <option value="">สถานะทั้งหมด</option>
                        <option value="incompleted" <?php echo $status_filter === 'incompleted' ? 'selected' : ''; ?>>ยังไม่เสร็จสิ้น</option>
                        <option value="completed" <?php echo $status_filter === 'completed' ? 'selected' : ''; ?>>เสร็จสิ้น</option>
                    </select>
                    <?php if (!empty($search)): ?>
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                    <?php endif; ?>
                </form>

                <div class="btn-group">
                    <a href="create-project.php" class="btn btn-success">
                        <i class="fas fa-plus"></i> สร้างโครงการใหม่
                    </a>
                    <a href="?<?php echo http_build_query(['search' => '', 'status' => '']); ?>" class="btn btn-outline">
                        <i class="fas fa-refresh"></i> รีเซ็ต
                    </a>
                </div>
            </div>

            <!-- Projects Table -->
            <div class="table-container">
                <?php if (empty($projects)): ?>
                    <div style="text-align: center; padding: 3rem; color: var(--text-muted);">
                        <i class="fas fa-folder-open" style="font-size: 3rem; margin-bottom: 1rem; opacity: 0.5;"></i>
                        <h3>ยังไม่มีโครงการ</h3>
                        <p>คุณยังไม่ได้สร้างโครงการ หรือไม่พบโครงการที่ตรงกับเงื่อนไขการค้นหา</p>
                        <a href="create-project.php" class="btn btn-primary" style="margin-top: 1rem;">
                            <i class="fas fa-plus"></i> สร้างโครงการแรกของคุณ
                        </a>
                    </div>
                <?php else: ?>
                    <table class="projects-table">
                        <thead>
                            <tr>
                                <th>รหัสโครงการ</th>
                                <th>ชื่อโครงการ</th>
                                <th>หน่วยงาน</th>
                                <th>หัวหน้าโครงการ</th>
                                <th>งบประมาณ</th>
                                <th>สถานะ</th>
                                <th>วันที่สร้าง</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($projects as $project): ?>
                                <tr>
                                    <td>
                                        <span class="project-code"><?php echo htmlspecialchars($project['project_code']); ?></span>
                                    </td>
                                    <td>
                                        <div class="project-name" title="<?php echo htmlspecialchars($project['name']); ?>">
                                            <?php echo htmlspecialchars($project['name']); ?>
                                        </div>
                                    </td>
                                    <td><?php echo htmlspecialchars($project['organization']); ?></td>
                                    <td><?php echo htmlspecialchars($project['project_manager']); ?></td>
                                    <td class="budget">
                                        ฿<?php echo number_format($project['budget'], 2); ?>
                                    </td>
                                    <td>
                                        <span class="badge <?php echo getStatusBadge($project['status']); ?>">
                                            <?php echo getStatusText($project['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php echo date('d/m/Y H:i', strtotime($project['created_at'])); ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <a href="project-detail.php?id=<?php echo $project['id']; ?>" class="btn btn-view btn-sm" title="ดูรายละเอียด">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="impact-chain/step1-strategy.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary btn-sm" title="สร้าง Impact Chain">
                                                <i class="fas fa-link"></i>
                                            </a>
                                            <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="btn btn-edit btn-sm" title="แก้ไข">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="deleteProject(<?php echo $project['id']; ?>)" class="btn btn-delete btn-sm" title="ลบ">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <div class="pagination">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">« ก่อนหน้า</a>
                            <?php endif; ?>

                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <?php if ($i == $page): ?>
                                    <span class="current"><?php echo $i; ?></span>
                                <?php else: ?>
                                    <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                <?php endif; ?>
                            <?php endfor; ?>

                            <?php if ($page < $total_pages): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">ถัดไป »</a>
                            <?php endif; ?>
                        </div>

                        <div style="text-align: center; margin-top: 1rem; color: var(--text-muted);">
                            แสดง <?php echo ($offset + 1); ?> - <?php echo min($offset + $limit, $total_records); ?> จาก <?php echo $total_records; ?> รายการ
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteProject(projectId) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบโครงการนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
                // สร้างฟอร์มสำหรับลบ
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'delete-project.php';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'project_id';
                input.value = projectId;

                form.appendChild(input);
                document.body.appendChild(form);
                form.submit();
            }
        }

        // Auto submit search form on input
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                this.form.submit();
            }, 500);
        });

        console.log('🎯 Project List initialized successfully!');
    </script>
</body>

</html>