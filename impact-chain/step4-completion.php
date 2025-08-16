<?php
session_start();
require_once '../config.php';
require_once '../includes/impact_chain_manager.php';
require_once '../includes/progress_bar.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
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
mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$project_result = mysqli_stmt_get_result($check_stmt);
$project = mysqli_fetch_assoc($project_result);
mysqli_stmt_close($check_stmt);

if (!$project) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงโครงการนี้";
    header("location: ../project-list.php");
    exit;
}

// ดึงข้อมูล Impact Chains ทั้งหมด
$chains = getProjectImpactChains($project_id);
$status = getMultipleImpactChainStatus($project_id);

// คำนวณข้อมูลสถิติ
$total_chains = 0;
$completed_chains = 0;

// นับ Impact Chain เดิม (ตารางเดิม)
if ($status['step4_completed']) {
    $total_chains++;
    $completed_chains++;
}

// นับ Impact Chains ใหม่ (ตารางใหม่)
if (isset($status['multiple_chains'])) {
    $total_chains += $status['multiple_chains']['total_chains'];
    $completed_chains += $status['multiple_chains']['completed_chains'];
}

$progress_data = [
    'total_chains' => $total_chains,
    'completed_chains' => $completed_chains,
    'progress_percentage' => $total_chains > 0 ? ($completed_chains / $total_chains) * 100 : 0
];

// Debug: แสดงข้อมูลสถานะ
// echo "<!-- Debug: step4_completed = " . ($status['step4_completed'] ? 'true' : 'false') . " -->";
// echo "<!-- Debug: total_chains = $total_chains, completed_chains = $completed_chains -->";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 4: เสร็จสิ้น Impact Chain - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .completion-card {
            border: 2px solid #28a745;
            background: linear-gradient(135deg, #f8fff9 0%, #f0fff4 100%);
        }
        .option-card {
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
            cursor: pointer;
        }
        .option-card:hover {
            border-color: #007bff;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .chain-summary {
            background-color: #f8f9fa;
            border-left: 4px solid #28a745;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="../project-list.php">โครงการ</a></li>
                        <li class="breadcrumb-item"><a href="step1-strategy.php?project_id=<?php echo $project_id; ?>">Impact Chain</a></li>
                        <li class="breadcrumb-item active">เสร็จสิ้น</li>
                    </ol>
                </nav>
                <h2><i class="fas fa-check-circle text-success"></i> Impact Chain เสร็จสิ้น!</h2>
                <p class="text-muted">โครงการ: <?php echo htmlspecialchars($project['name']); ?></p>
                <p class="text-muted">รหัสโครงการ: <?php echo htmlspecialchars($project['project_code']); ?></p>
            </div>
        </div>

        <!-- Progress Bar -->
        <?php renderImpactChainProgressBar($project_id, 4, $status); ?>

        <!-- Completion Message -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card completion-card">
                    <div class="card-body text-center py-4">
                        <i class="fas fa-check-circle text-success fa-4x mb-3"></i>
                        <h3 class="text-success mb-3">🎉 Impact Chain เสร็จสิ้นแล้ว!</h3>
                        <p class="lead">คุณได้สร้าง Impact Chain เรียบร้อยแล้ว ตอนนี้คุณสามารถเลือกดำเนินการต่อไปได้</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Current Chains Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0"><i class="fas fa-list"></i> สรุป Impact Chains ปัจจุบัน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-3 text-center">
                                <h4 class="text-primary"><?php echo $progress_data['total_chains']; ?></h4>
                                <small class="text-muted">Impact Chains ทั้งหมด</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-success"><?php echo $progress_data['completed_chains']; ?></h4>
                                <small class="text-muted">เสร็จสิ้นแล้ว</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-warning"><?php echo $progress_data['total_chains'] - $progress_data['completed_chains']; ?></h4>
                                <small class="text-muted">ยังไม่เสร็จ</small>
                            </div>
                            <div class="col-md-3 text-center">
                                <h4 class="text-info"><?php echo round($progress_data['progress_percentage']); ?>%</h4>
                                <small class="text-muted">ความคืบหน้า</small>
                            </div>
                        </div>

                        <?php if (!empty($chains)): ?>
                            <h6 class="mb-3">รายการ Impact Chains:</h6>
                            <?php foreach ($chains as $chain): ?>
                                <div class="chain-summary p-3 mb-2 rounded">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <strong><?php echo htmlspecialchars($chain['chain_name']); ?></strong>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($chain['activity_name']); ?></small>
                                        </div>
                                        <div>
                                            <?php if ($chain['is_complete']): ?>
                                                <span class="badge bg-success"><i class="fas fa-check"></i> เสร็จสิ้น</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark"><i class="fas fa-clock"></i> ยังไม่เสร็จ</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Options -->
        <div class="row mb-4">
            <div class="col-12">
                <h4 class="mb-3"><i class="fas fa-directions"></i> เลือกขั้นตอนต่อไป</h4>
                <div class="row">
                    <!-- เพิ่ม Impact Chain -->
                    <div class="col-md-6 mb-3">
                        <div class="card option-card h-100" onclick="addNewChain()">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-plus-circle text-primary fa-3x mb-3"></i>
                                <h5 class="card-title text-primary">เพิ่ม Impact Chain ใหม่</h5>
                                <p class="card-text">
                                    สร้าง Impact Chain เพิ่มเติมเพื่อครอบคลุมกิจกรรมอื่นๆ ในโครงการ
                                </p>
                                <ul class="list-unstyled text-start mt-3">
                                    <li><i class="fas fa-check text-success"></i> เลือกกิจกรรมใหม่</li>
                                    <li><i class="fas fa-check text-success"></i> กำหนดผลผลิตและผลลัพธ์</li>
                                    <li><i class="fas fa-check text-success"></i> เพิ่มความครอบคลุมของการวิเคราะห์</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- ไปขั้นตอนต่อไป -->
                    <div class="col-md-6 mb-3">
                        <div class="card option-card h-100" onclick="proceedToNext()">
                            <div class="card-body text-center py-4">
                                <i class="fas fa-arrow-right text-success fa-3x mb-3"></i>
                                <h5 class="card-title text-success">ไปขั้นตอนต่อไป</h5>
                                <p class="card-text">
                                    ดำเนินการคำนวณและวิเคราะห์ SROI จาก Impact Chains ที่สร้างแล้ว
                                </p>
                                <ul class="list-unstyled text-start mt-3">
                                    <li><i class="fas fa-check text-success"></i> คำนวณ SROI Ratio</li>
                                    <li><i class="fas fa-check text-success"></i> วิเคราะห์ผลกระทบทางสังคม</li>
                                    <li><i class="fas fa-check text-success"></i> สร้างรายงานสรุป</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Options -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-tools"></i> ตัวเลือกเพิ่มเติม</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="chains-summary.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-info">
                                <i class="fas fa-list"></i> จัดการ Impact Chains
                            </a>
                            <a href="step1-strategy.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-edit"></i> แก้ไข Impact Chain
                            </a>
                            <a href="../project-list.php" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> กลับสู่รายการโครงการ
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function addNewChain() {
            if (confirm('คุณต้องการเพิ่ม Impact Chain ใหม่หรือไม่?\n\nระบบจะนำคุณไปยังหน้าเลือกกิจกรรมใหม่')) {
                window.location.href = 'step2-activity.php?project_id=<?php echo $project_id; ?>&new_chain=1';
            }
        }

        function proceedToNext() {
            <?php if ($progress_data['completed_chains'] > 0): ?>
                if (confirm('คุณต้องการไปยังขั้นตอนการคำนวณ SROI หรือไม่?\n\nระบบจะใช้ข้อมูลจาก Impact Chains ที่เสร็จสิ้นแล้วในการคำนวณ')) {
                    window.location.href = '../impact_pathway/impact_pathway.php?project_id=<?php echo $project_id; ?>';
                }
            <?php else: ?>
                alert('กรุณาทำ Impact Chain อย่างน้อย 1 รายการให้เสร็จสิ้นก่อน');
            <?php endif; ?>
        }

        // Auto-redirect message
        <?php if (isset($_SESSION['success_message'])): ?>
            setTimeout(function() {
                alert('<?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>');
            }, 500);
        <?php endif; ?>
    </script>
</body>

</html>