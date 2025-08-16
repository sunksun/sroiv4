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
$progress_data = getMultiChainProgress($project_id);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุป Impact Chains - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .chain-card {
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
        }
        .chain-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .chain-complete {
            border-color: #28a745;
            background-color: #f8fff8;
        }
        .chain-incomplete {
            border-color: #ffc107;
            background-color: #fffef8;
        }
        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
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
                        <li class="breadcrumb-item active">สรุป Impact Chains</li>
                    </ol>
                </nav>
                <h2><i class="fas fa-sitemap text-primary"></i> สรุป Impact Chains</h2>
                <p class="text-muted">โครงการ: <?php echo htmlspecialchars($project['name']); ?></p>
                <p class="text-muted">รหัสโครงการ: <?php echo htmlspecialchars($project['project_code']); ?></p>
            </div>
        </div>

        <!-- Progress Overview -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-line"></i> ความคืบหน้าโดยรวม</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3 text-center mb-3">
                                <h3 class="text-primary"><?php echo $progress_data['total_chains']; ?></h3>
                                <p class="text-muted mb-0">Impact Chains ทั้งหมด</p>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <h3 class="text-success"><?php echo $progress_data['completed_chains']; ?></h3>
                                <p class="text-muted mb-0">เสร็จสิ้นแล้ว</p>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <h3 class="text-warning"><?php echo $progress_data['total_chains'] - $progress_data['completed_chains']; ?></h3>
                                <p class="text-muted mb-0">ยังไม่เสร็จ</p>
                            </div>
                            <div class="col-md-3 text-center mb-3">
                                <h3 class="text-info"><?php echo round($progress_data['progress_percentage']); ?>%</h3>
                                <p class="text-muted mb-0">ความคืบหน้า</p>
                            </div>
                        </div>
                        
                        <!-- Progress Bar -->
                        <div class="progress" style="height: 20px;">
                            <div class="progress-bar bg-success" role="progressbar" 
                                 style="width: <?php echo $progress_data['progress_percentage']; ?>%"
                                 aria-valuenow="<?php echo $progress_data['progress_percentage']; ?>" 
                                 aria-valuemin="0" aria-valuemax="100">
                                <?php echo round($progress_data['progress_percentage']); ?>%
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Impact Chains List -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4><i class="fas fa-list"></i> รายการ Impact Chains</h4>
                    <a href="step2-activity.php?project_id=<?php echo $project_id; ?>&new_chain=1" class="btn btn-primary">
                        <i class="fas fa-plus"></i> เพิ่ม Impact Chain ใหม่
                    </a>
                </div>

                <?php if (empty($chains)): ?>
                    <div class="alert alert-info text-center">
                        <i class="fas fa-info-circle fa-2x mb-3"></i>
                        <h5>ยังไม่มี Impact Chain</h5>
                        <p>เริ่มสร้าง Impact Chain แรกของคุณได้เลย</p>
                        <a href="step1-strategy.php?project_id=<?php echo $project_id; ?>" class="btn btn-primary">
                            <i class="fas fa-plus"></i> เริ่มสร้าง Impact Chain
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($chains as $index => $chain): ?>
                            <div class="col-md-6 col-lg-4 mb-4">
                                <div class="card chain-card <?php echo $chain['is_complete'] ? 'chain-complete' : 'chain-incomplete'; ?> h-100 position-relative">
                                    <!-- Status Badge -->
                                    <span class="badge status-badge <?php echo $chain['is_complete'] ? 'bg-success' : 'bg-warning text-dark'; ?>">
                                        <?php if ($chain['is_complete']): ?>
                                            <i class="fas fa-check"></i> เสร็จสิ้น
                                        <?php else: ?>
                                            <i class="fas fa-clock"></i> ยังไม่เสร็จ
                                        <?php endif; ?>
                                    </span>

                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <i class="fas fa-link text-primary"></i>
                                            <?php echo htmlspecialchars($chain['chain_name']); ?>
                                        </h5>
                                        
                                        <div class="mb-3">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar"></i> 
                                                สร้างเมื่อ: <?php echo date('d/m/Y H:i', strtotime($chain['created_at'])); ?>
                                            </small>
                                        </div>

                                        <!-- Chain Details -->
                                        <div class="mb-2">
                                            <strong><i class="fas fa-tasks text-success"></i> กิจกรรม:</strong>
                                            <p class="mb-1"><?php echo htmlspecialchars($chain['activity_name']); ?></p>
                                        </div>

                                        <div class="mb-2">
                                            <strong><i class="fas fa-cube text-info"></i> ผลผลิต:</strong>
                                            <span class="badge <?php echo $chain['has_outputs'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $chain['has_outputs'] > 0 ? 'กำหนดแล้ว' : 'ยังไม่กำหนด'; ?>
                                            </span>
                                        </div>

                                        <div class="mb-3">
                                            <strong><i class="fas fa-chart-line text-warning"></i> ผลลัพธ์:</strong>
                                            <span class="badge <?php echo $chain['has_outcomes'] > 0 ? 'bg-success' : 'bg-secondary'; ?>">
                                                <?php echo $chain['has_outcomes'] > 0 ? 'กำหนดแล้ว' : 'ยังไม่กำหนด'; ?>
                                            </span>
                                        </div>
                                    </div>

                                    <div class="card-footer bg-transparent">
                                        <div class="d-flex justify-content-between">
                                            <?php if ($chain['is_complete']): ?>
                                                <a href="../impact_pathway/impact_pathway.php?project_id=<?php echo $project_id; ?>&chain_id=<?php echo $chain['id']; ?>" 
                                                   class="btn btn-success btn-sm">
                                                    <i class="fas fa-calculator"></i> คำนวณ SROI
                                                </a>
                                            <?php else: ?>
                                                <?php if ($chain['has_outputs'] == 0): ?>
                                                    <a href="step3-output.php?project_id=<?php echo $project_id; ?>&chain_id=<?php echo $chain['id']; ?>" 
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> กำหนดผลผลิต
                                                    </a>
                                                <?php else: ?>
                                                    <a href="step4-outcome.php?project_id=<?php echo $project_id; ?>&chain_id=<?php echo $chain['id']; ?>" 
                                                       class="btn btn-warning btn-sm">
                                                        <i class="fas fa-edit"></i> กำหนดผลลัพธ์
                                                    </a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                            
                                            <button class="btn btn-outline-danger btn-sm" onclick="deleteChain(<?php echo $chain['id']; ?>, '<?php echo htmlspecialchars($chain['chain_name'], ENT_QUOTES); ?>')">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <a href="../project-list.php" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left"></i> กลับสู่รายการโครงการ
                    </a>
                    
                    <?php if ($progress_data['completed_chains'] > 0): ?>
                        <a href="../impact_pathway/impact_pathway.php?project_id=<?php echo $project_id; ?>" class="btn btn-success">
                            <i class="fas fa-calculator"></i> คำนวณ SROI รวม
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function deleteChain(chainId, chainName) {
            if (confirm(`คุณต้องการลบ "${chainName}" หรือไม่?\n\nการดำเนินการนี้ไม่สามารถยกเลิกได้`)) {
                fetch('delete-chain.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        chain_id: chainId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('เกิดข้อผิดพลาด: ' + (data.message || 'ไม่สามารถลบ Impact Chain ได้'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('เกิดข้อผิดพลาดในการลบ Impact Chain');
                });
            }
        }
    </script>
</body>

</html>