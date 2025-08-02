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

// รับ ID โครงการ
$project_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$user_id = $_SESSION['user_id'];

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: project-list.php");
    exit;
}

// ดึงข้อมูลโครงการ พร้อมตรวจสอบสิทธิ์
$project_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
$project_stmt = mysqli_prepare($conn, $project_query);
mysqli_stmt_bind_param($project_stmt, 'is', $project_id, $user_id);
mysqli_stmt_execute($project_stmt);
$project_result = mysqli_stmt_get_result($project_stmt);
$project = mysqli_fetch_assoc($project_result);
mysqli_stmt_close($project_stmt);

if (!$project) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงโครงการนี้";
    header("location: project-list.php");
    exit;
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
            return 'text-success';
        case 'incompleted':
            return 'text-warning';
        default:
            return 'text-secondary';
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
    $year = date('Y', $timestamp) + 543;

    return "$day $month $year";
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายละเอียดโครงการ - <?php echo htmlspecialchars($project['name']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #56ab2f;
            --warning-color: #f093fb;
            --info-color: #4ecdc4;
        }

        body {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
        }

        .main-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }

        .project-header {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .detail-section {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.15);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
        }

        .btn-outline-secondary {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-secondary:hover {
            background: var(--primary-color);
            border-color: var(--primary-color);
        }
    </style>
</head>

<body>
    <div class="main-container">
        <!-- Project Header -->
        <div class="project-header">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h1 class="h3 mb-2"><?php echo htmlspecialchars($project['name']); ?></h1>
                    <div class="d-flex align-items-center gap-3">
                        <span class="badge bg-secondary"><?php echo htmlspecialchars($project['project_code']); ?></span>
                        <span class="badge <?php echo $project['status'] == 'completed' ? 'bg-success' : 'bg-warning text-dark'; ?>">
                            <?php echo getStatusText($project['status']); ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="impact-chain/step1-strategy.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary">
                        <i class="fas fa-link"></i> สร้าง Impact Chain
                    </a>
                    <a href="project-list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> กลับ
                    </a>
                </div>
            </div>

            <?php if (!empty($project['description'])): ?>
                <p class="text-muted mb-0"><?php echo nl2br(htmlspecialchars($project['description'])); ?></p>
            <?php endif; ?>
        </div>

        <!-- Project Details -->
        <div class="row">
            <div class="col-md-8">
                <div class="detail-section">
                    <h5 class="mb-3"><i class="fas fa-info-circle text-primary"></i> รายละเอียดโครงการ</h5>
                    <div class="row">
                        <div class="col-sm-6 mb-3">
                            <strong>หน่วยงาน:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($project['organization']); ?></span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <strong>หัวหน้าโครงการ:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($project['project_manager']); ?></span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <strong>ช่วงระยะเวลา:</strong><br>
                            <span class="text-muted">
                                <?php echo formatThaiDate($project['start_date']); ?> -
                                <?php echo formatThaiDate($project['end_date']); ?>
                            </span>
                        </div>
                        <div class="col-sm-6 mb-3">
                            <strong>งบประมาณ:</strong><br>
                            <span class="text-success fw-bold">฿<?php echo number_format($project['budget']); ?></span>
                        </div>
                    </div>
                </div>

                <?php if (!empty($project['objectives'])): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="fas fa-bullseye text-primary"></i> วัตถุประสงค์</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['objectives'])); ?></p>
                    </div>
                <?php endif; ?>

                <?php if (!empty($project['target_group'])): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="fas fa-users text-primary"></i> กลุ่มเป้าหมาย</h5>
                        <p class="text-muted"><?php echo nl2br(htmlspecialchars($project['target_group'])); ?></p>
                    </div>
                <?php endif; ?>
            </div>

            <div class="col-md-4">
                <div class="detail-section">
                    <h5 class="mb-3"><i class="fas fa-clock text-primary"></i> ข้อมูลระบบ</h5>
                    <div class="mb-3">
                        <strong>วันที่สร้าง:</strong><br>
                        <span class="text-muted"><?php echo formatThaiDate($project['created_at']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>อัปเดตล่าสุด:</strong><br>
                        <span class="text-muted"><?php echo formatThaiDate($project['updated_at']); ?></span>
                    </div>
                    <div class="mb-3">
                        <strong>สร้างโดย:</strong><br>
                        <span class="text-muted"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    </div>
                </div>

                <div class="detail-section">
                    <h5 class="mb-3"><i class="fas fa-chart-line text-primary"></i> ความคืบหน้า</h5>
                    <div class="text-center">
                        <div class="progress mb-3" style="height: 20px;">
                            <div class="progress-bar <?php echo $project['status'] == 'completed' ? 'bg-success' : 'bg-warning'; ?>"
                                style="width: <?php echo $project['status'] == 'completed' ? '100' : '50'; ?>%">
                                <?php echo $project['status'] == 'completed' ? '100' : '50'; ?>%
                            </div>
                        </div>
                        <p class="text-muted">
                            <?php echo $project['status'] == 'completed' ? 'โครงการเสร็จสมบูรณ์' : 'โครงการกำลังดำเนินการ'; ?>
                        </p>
                    </div>
                </div>

                <div class="detail-section">
                    <h5 class="mb-3"><i class="fas fa-tools text-primary"></i> การจัดการ</h5>
                    <div class="d-grid gap-2">
                        <a href="impact-chain/step1-strategy.php?project_id=<?php echo $project['id']; ?>" class="btn btn-primary btn-sm">
                            <i class="fas fa-link"></i> จัดการ Impact Chain
                        </a>
                        <a href="project-edit.php?id=<?php echo $project['id']; ?>" class="btn btn-outline-secondary btn-sm">
                            <i class="fas fa-edit"></i> แก้ไขโครงการ
                        </a>
                        <button class="btn btn-outline-danger btn-sm" onclick="confirmDelete()">
                            <i class="fas fa-trash"></i> ลบโครงการ
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function confirmDelete() {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบโครงการนี้? การดำเนินการนี้ไม่สามารถยกเลิกได้')) {
                window.location.href = `delete-project.php?id=<?php echo $project['id']; ?>`;
            }
        }
    </script>
</body>

</html>