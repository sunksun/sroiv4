<?php
session_start();
require_once '../config.php';
require_once '../includes/progress_bar.php';

// เปิดการแสดง PHP errors ในโหมด development
// ini_set('display_errors', 1);
// error_reporting(E_ALL);

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// รับ project_id จาก URL
$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: ../project-list.php");
    exit;
}

// ตรวจสอบสิทธิ์เข้าถึงโครงการ
$user_id = $_SESSION['user_id'];
$check_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'is', $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$project_result = mysqli_stmt_get_result($check_stmt);
$project = mysqli_fetch_assoc($project_result);
mysqli_stmt_close($check_stmt);

if (!$project) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงโครงการนี้";
    header("location: ../project-list.php");
    exit;
}

// ดึงข้อมูลยุทธศาสตร์ทั้งหมด
$strategies_query = "SELECT * FROM strategies ORDER BY strategy_id ASC";
$strategies_result = mysqli_query($conn, $strategies_query);

if (!$strategies_result) {
    die("Database Error: " . mysqli_error($conn));
}

$strategies = mysqli_fetch_all($strategies_result, MYSQLI_ASSOC);

// Debug: แสดงจำนวนยุทธศาสตร์ที่พบ
if (empty($strategies)) {
    error_log("No strategies found in database");
}

// ดึงยุทธศาสตร์ที่เลือกไว้แล้วจากฐานข้อมูล
$selected_strategies = [];
$selected_strategy_ids = [];
$selected_strategy_query = "SELECT ps.strategy_id, s.strategy_name 
                           FROM project_strategies ps 
                           JOIN strategies s ON ps.strategy_id = s.strategy_id 
                           WHERE ps.project_id = ?";
$selected_strategy_stmt = mysqli_prepare($conn, $selected_strategy_query);
mysqli_stmt_bind_param($selected_strategy_stmt, 'i', $project_id);
mysqli_stmt_execute($selected_strategy_stmt);
$selected_strategy_result = mysqli_stmt_get_result($selected_strategy_stmt);

while ($strategy = mysqli_fetch_assoc($selected_strategy_result)) {
    $selected_strategies[] = $strategy;
    $selected_strategy_ids[] = $strategy['strategy_id'];
}
mysqli_stmt_close($selected_strategy_stmt);

// เก็บ project_id ใน session
$_SESSION['impact_chain_project_id'] = $project_id;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 1: เลือกยุทธศาสตร์ - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../project-list.php">โครงการ</a></li>
                        <li class="breadcrumb-item active">สร้าง Impact Chain</li>
                    </ol>
                </nav>
                <h2>สร้าง Impact Chain: <?php echo htmlspecialchars($project['name']); ?></h2>
                <p class="text-muted">รหัสโครงการ: <?php echo htmlspecialchars($project['project_code']); ?></p>
            </div>
        </div>

        <!-- Progress Steps -->
        <?php 
        try {
            $status = getImpactChainStatus($project_id);
            renderImpactChainProgressBar($project_id, 1, $status); 
        } catch (Exception $e) {
            echo "<div class='alert alert-danger'>Error in progress bar: " . $e->getMessage() . "</div>";
        }
        ?>

        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bullseye"></i> เลือกยุทธศาสตร์สำหรับโครงการ</h5>
                        <small class="text-muted">เลือกยุทธศาสตร์หนึ่งรายการที่เกี่ยวข้องกับโครงการของคุณ</small>

                        <?php if (!empty($selected_strategies)): ?>
                            <div class="mt-2">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>ยุทธศาสตร์ปัจจุบัน:</strong>
                                    <?php echo htmlspecialchars($selected_strategies[0]['strategy_name']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form action="process-step1.php" method="POST">
                            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                            <?php if (empty($strategies)): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> ไม่พบข้อมูลยุทธศาสตร์ในระบบ
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php $counter = 1; foreach ($strategies as $strategy): ?>
                                        <div class="col-md-6 mb-3">
                                            <div class="card h-100 <?php echo in_array($strategy['strategy_id'], $selected_strategy_ids) ? 'border-primary' : ''; ?>">
                                                <div class="card-body">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="radio"
                                                            name="strategy" value="<?php echo $strategy['strategy_id']; ?>"
                                                            id="strategy_<?php echo $strategy['strategy_id']; ?>"
                                                            <?php echo in_array($strategy['strategy_id'], $selected_strategy_ids) ? 'checked' : ''; ?>>
                                                        <label class="form-check-label fw-bold" for="strategy_<?php echo $strategy['strategy_id']; ?>">
                                                            <?php echo $counter; ?>. <?php echo htmlspecialchars($strategy['strategy_name']); ?>
                                                        </label>
                                                    </div>
                                                    <?php if (!empty($strategy['description'])): ?>
                                                        <p class="card-text mt-2 text-muted small">
                                                            <?php echo htmlspecialchars($strategy['description']); ?>
                                                        </p>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php $counter++; endforeach; ?>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="../project-list.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> ยกเลิก
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        ถัดไป: เลือกกิจกรรม <i class="fas fa-arrow-right"></i>
                                    </button>
                                </div>
                            <?php endif; ?>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // เพิ่ม visual feedback เมื่อเลือก strategy
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // ลบ border-primary จากทุก card
                document.querySelectorAll('.card').forEach(card => {
                    card.classList.remove('border-primary');
                });
                // เพิ่ม border-primary ให้ card ที่เลือก
                if (this.checked) {
                    this.closest('.card').classList.add('border-primary');
                }
            });
        });
    </script>
</body>

</html>