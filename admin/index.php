<?php
session_start();
require_once '../config.php';

// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบสิทธิ์ Admin (ชั่วคราวให้ teacher เข้าได้)
if (!isset($_SESSION["role"]) || ($_SESSION["role"] !== 'admin' && $_SESSION["role"] !== 'teacher')) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงหน้านี้";
    header("location: ../dashboard.php");
    exit;
}

// ดึงสถิติของระบบ
$system_stats_query = "SELECT 
                         (SELECT COUNT(*) FROM users) as total_users,
                         (SELECT COUNT(*) FROM users WHERE role = 'admin') as admin_users,
                         (SELECT COUNT(*) FROM users WHERE role = 'teacher') as teacher_users,
                         (SELECT COUNT(*) FROM users WHERE is_active = 1) as active_users,
                         (SELECT COUNT(*) FROM projects) as total_projects,
                         (SELECT COUNT(*) FROM projects WHERE status = 'completed') as completed_projects,
                         (SELECT COUNT(*) FROM social_impact_pathway) as total_chains,
                         (SELECT COUNT(*) FROM projects WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as projects_this_week";

$system_stats_result = mysqli_query($conn, $system_stats_query);
$system_stats = mysqli_fetch_assoc($system_stats_result);

// ดึงรายการผู้ใช้ล่าสุด
$recent_users_query = "SELECT id, username, full_name_th, role, created_at, last_login, is_active 
                       FROM users 
                       ORDER BY created_at DESC 
                       LIMIT 10";
$recent_users_result = mysqli_query($conn, $recent_users_query);

// ดึงโครงการล่าสุด
$recent_projects_query = "SELECT p.id, p.name, p.status, p.created_at, u.full_name_th as creator_name
                          FROM projects p 
                          LEFT JOIN users u ON p.created_by = u.id 
                          ORDER BY p.created_at DESC 
                          LIMIT 10";
$recent_projects_result = mysqli_query($conn, $recent_projects_query);

$user_full_name = $_SESSION['full_name_th'] ?? $_SESSION['username'];
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการ - SROI LRU</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Sarabun', sans-serif;
        }

        .admin-container {
            padding: 2rem 0;
            margin-top: 100px;
        }

        .admin-header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .admin-title {
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 0.5rem;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, #667eea, #764ba2);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            color: #333;
            margin-bottom: 0.5rem;
        }

        .stat-label {
            color: #666;
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .stat-icon {
            position: absolute;
            top: 1.5rem;
            right: 1.5rem;
            font-size: 2rem;
            color: #667eea;
            opacity: 0.7;
        }

        .admin-section {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #333;
            font-size: 1.5rem;
            font-weight: bold;
            margin-bottom: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e9ecef;
        }

        .admin-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }

        .admin-menu-item {
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid transparent;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            text-decoration: none;
            color: #333;
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
        }

        .admin-menu-item:hover {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            transform: translateY(-2px);
            border-color: rgba(255, 255, 255, 0.3);
        }

        .admin-menu-item i {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .table-responsive {
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }

        .table {
            margin-bottom: 0;
        }

        .table thead {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
        }

        .badge-admin {
            background: linear-gradient(45deg, #667eea, #764ba2);
        }

        .badge-teacher {
            background: #28a745;
        }

        .badge-completed {
            background: #28a745;
        }

        .badge-incompleted {
            background: #ffc107;
            color: #333;
        }

        @media (max-width: 768px) {
            .admin-title {
                font-size: 2rem;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .admin-menu {
                grid-template-columns: repeat(2, 1fr);
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php 
    $navbar_root = '../';
    include '../navbar.php'; 
    ?>

    <div class="admin-container">
        <div class="container">
            <!-- Admin Header -->
            <div class="admin-header text-center">
                <div class="admin-title">
                    <i class="fas fa-cogs me-3"></i>ระบบจัดการ SROI
                </div>
                <p class="lead mb-0">ยินดีต้อนรับ คุณ<?php echo htmlspecialchars($user_full_name); ?> 
                    (<?php echo $_SESSION["role"] == 'admin' ? 'ผู้ดูแลระบบ' : 'อาจารย์'; ?>)
                </p>
            </div>

            <!-- System Stats -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($system_stats['total_users']); ?></div>
                    <div class="stat-label">ผู้ใช้ทั้งหมด</div>
                    <small class="text-muted">
                        <i class="fas fa-user-shield me-1"></i><?php echo $system_stats['admin_users']; ?> ผู้ดูแล
                        <i class="fas fa-chalkboard-teacher ms-2 me-1"></i><?php echo $system_stats['teacher_users']; ?> อาจารย์
                    </small>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-check"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($system_stats['active_users']); ?></div>
                    <div class="stat-label">ผู้ใช้ที่ใช้งานอยู่</div>
                    <small class="text-muted">
                        จาก <?php echo number_format($system_stats['total_users']); ?> ผู้ใช้ทั้งหมด
                    </small>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-project-diagram"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($system_stats['total_projects']); ?></div>
                    <div class="stat-label">โครงการทั้งหมด</div>
                    <small class="text-muted">
                        <i class="fas fa-check-circle me-1 text-success"></i><?php echo $system_stats['completed_projects']; ?> เสร็จสิ้น
                    </small>
                </div>

                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="stat-number"><?php echo number_format($system_stats['total_chains']); ?></div>
                    <div class="stat-label">Impact Pathways</div>
                    <small class="text-muted">
                        <i class="fas fa-calendar-week me-1"></i><?php echo $system_stats['projects_this_week']; ?> โครงการสัปดาห์นี้
                    </small>
                </div>
            </div>

            <!-- Admin Menu -->
            <div class="admin-section">
                <h3 class="section-title">
                    <i class="fas fa-tools me-2"></i>เครื่องมือจัดการระบบ
                </h3>
                <div class="admin-menu">
                    <a href="reset-data.php" class="admin-menu-item">
                        <i class="fas fa-database"></i>
                        <span>รีเซ็ตข้อมูล</span>
                    </a>
                    <a href="value_factor.php" class="admin-menu-item">
                        <i class="fas fa-calculator"></i>
                        <span>ตัวคูณมูลค่า</span>
                    </a>
                    <a href="users.php" class="admin-menu-item">
                        <i class="fas fa-users-cog"></i>
                        <span>จัดการผู้ใช้</span>
                    </a>
                    <a href="projects.php" class="admin-menu-item">
                        <i class="fas fa-folder-open"></i>
                        <span>จัดการโครงการ</span>
                    </a>
                    <a href="system-settings.php" class="admin-menu-item">
                        <i class="fas fa-cog"></i>
                        <span>ตั้งค่าระบบ</span>
                    </a>
                    <a href="backup.php" class="admin-menu-item">
                        <i class="fas fa-shield-alt"></i>
                        <span>สำรองข้อมูล</span>
                    </a>
                    <a href="reports.php" class="admin-menu-item">
                        <i class="fas fa-chart-bar"></i>
                        <span>รายงานระบบ</span>
                    </a>
                    <a href="logs.php" class="admin-menu-item">
                        <i class="fas fa-file-alt"></i>
                        <span>บันทึกการใช้งาน</span>
                    </a>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="admin-section">
                <h3 class="section-title">
                    <i class="fas fa-user-plus me-2"></i>ผู้ใช้ล่าสุด
                </h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ชื่อผู้ใช้</th>
                                <th>ชื่อจริง</th>
                                <th>บทบาท</th>
                                <th>สถานะ</th>
                                <th>วันที่สร้าง</th>
                                <th>เข้าสู่ระบบล่าสุด</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = mysqli_fetch_assoc($recent_users_result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($user['full_name_th'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-teacher'; ?>">
                                        <?php echo $user['role'] == 'admin' ? 'ผู้ดูแล' : 'อาจารย์'; ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="badge <?php echo $user['is_active'] ? 'bg-success' : 'bg-secondary'; ?>">
                                        <?php echo $user['is_active'] ? 'ใช้งาน' : 'ปิดใช้งาน'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php 
                                    if ($user['last_login']) {
                                        echo date('d/m/Y H:i', strtotime($user['last_login']));
                                    } else {
                                        echo '<span class="text-muted">ยังไม่เคยเข้าสู่ระบบ</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Recent Projects -->
            <div class="admin-section">
                <h3 class="section-title">
                    <i class="fas fa-folder-plus me-2"></i>โครงการล่าสุด
                </h3>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ชื่อโครงการ</th>
                                <th>ผู้สร้าง</th>
                                <th>สถานะ</th>
                                <th>วันที่สร้าง</th>
                                <th>การจัดการ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($project = mysqli_fetch_assoc($recent_projects_result)): ?>
                            <tr>
                                <td>
                                    <strong><?php echo htmlspecialchars($project['name']); ?></strong>
                                </td>
                                <td><?php echo htmlspecialchars($project['creator_name'] ?? '-'); ?></td>
                                <td>
                                    <span class="badge <?php echo $project['status'] == 'completed' ? 'badge-completed' : 'badge-incompleted'; ?>">
                                        <?php echo $project['status'] == 'completed' ? 'เสร็จสิ้น' : 'ยังไม่เสร็จ'; ?>
                                    </span>
                                </td>
                                <td><?php echo date('d/m/Y', strtotime($project['created_at'])); ?></td>
                                <td>
                                    <a href="../project-detail.php?id=<?php echo $project['id']; ?>" 
                                       class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-eye"></i> ดู
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate stats on load
            const statNumbers = document.querySelectorAll('.stat-number');
            statNumbers.forEach(stat => {
                const finalNumber = parseInt(stat.textContent.replace(/,/g, ''));
                let currentNumber = 0;
                const increment = finalNumber / 50;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        currentNumber = finalNumber;
                        clearInterval(timer);
                    }
                    stat.textContent = Math.floor(currentNumber).toLocaleString();
                }, 20);
            });
        });
    </script>
</body>
</html>