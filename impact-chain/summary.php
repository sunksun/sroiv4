<?php
session_start();
require_once '../config.php';

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

// ดึงข้อมูลจากฐานข้อมูล
$selected_strategies = [];
$selected_activity = null;
$selected_outputs = [];

// ดึงยุทธศาสตร์ที่เลือก
$strategy_query = "SELECT ps.strategy_id, s.strategy_name 
                   FROM project_strategies ps 
                   JOIN strategies s ON ps.strategy_id = s.strategy_id 
                   WHERE ps.project_id = ?";
$strategy_stmt = mysqli_prepare($conn, $strategy_query);
mysqli_stmt_bind_param($strategy_stmt, 'i', $project_id);
mysqli_stmt_execute($strategy_stmt);
$strategy_result = mysqli_stmt_get_result($strategy_stmt);
while ($strategy = mysqli_fetch_assoc($strategy_result)) {
    $selected_strategies[] = $strategy;
}
mysqli_stmt_close($strategy_stmt);

// ดึงกิจกรรมที่เลือก
$activity_query = "SELECT pa.activity_id, a.activity_name, a.activity_code, a.activity_description, s.strategy_name 
                   FROM project_activities pa 
                   JOIN activities a ON pa.activity_id = a.activity_id 
                   JOIN strategies s ON a.strategy_id = s.strategy_id 
                   WHERE pa.project_id = ?";
$activity_stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($activity_stmt, 'i', $project_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);
if (mysqli_num_rows($activity_result) > 0) {
    $selected_activity = mysqli_fetch_assoc($activity_result);
}
mysqli_stmt_close($activity_stmt);

// ดึงผลผลิตที่เลือก (รวมรายละเอียดเพิ่มเติม)
$output_query = "SELECT po.output_id, po.output_details, o.output_description, o.target_details, o.output_sequence, a.activity_name, s.strategy_name, s.strategy_id
                 FROM project_outputs po 
                 JOIN outputs o ON po.output_id = o.output_id 
                 JOIN activities a ON o.activity_id = a.activity_id 
                 JOIN strategies s ON a.strategy_id = s.strategy_id
                 WHERE po.project_id = ?
                 ORDER BY o.output_sequence ASC, o.output_id ASC";
$output_stmt = mysqli_prepare($conn, $output_query);
mysqli_stmt_bind_param($output_stmt, 'i', $project_id);
mysqli_stmt_execute($output_stmt);
$output_result = mysqli_stmt_get_result($output_stmt);
while ($output = mysqli_fetch_assoc($output_result)) {
    $selected_outputs[] = $output;
}
mysqli_stmt_close($output_stmt);

// ไม่มี outcomes และ financial_proxies ในโครงสร้างใหม่
$selected_outcomes = [];
$financial_proxies = [];

// คำนวณมูลค่าทางการเงินรวม (ประมาณการ) - ตอนนี้ยังไม่มีข้อมูล
$total_estimated_value = 0;
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สรุป Impact Chain - SROI System</title>
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
                        <li class="breadcrumb-item active">สรุป Impact Chain</li>
                    </ol>
                </nav>
                <h2>สรุป Impact Chain: <?php echo htmlspecialchars($project['name']); ?></h2>
                <p class="text-muted">รหัสโครงการ: <?php echo htmlspecialchars($project['project_code']); ?></p>
            </div>
        </div>

        <!-- Success Message -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $_SESSION['success_message'];
                                                    unset($_SESSION['success_message']); ?>
            </div>
        <?php endif; ?>

        <!-- Overall Summary -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <h5 class="card-title"><i class="fas fa-check-circle"></i> Impact Chain สร้างเสร็จสิ้น</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <h6>ยุทธศาสตร์</h6>
                                <h4><?php echo count($selected_strategies); ?> รายการ</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>กิจกรรม</h6>
                                <h4><?php echo $selected_activity ? '1' : '0'; ?> รายการ</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>ผลผลิต</h6>
                                <h4><?php echo count($selected_outputs); ?> รายการ</h4>
                            </div>
                            <div class="col-md-3">
                                <h6>ผลลัพธ์</h6>
                                <h4><span class="text-muted">ไม่มีข้อมูล</span></h4>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Financial Proxies ทั้งหมด</h6>
                                <h4><span class="text-muted">ไม่มีข้อมูล</span></h4>
                            </div>
                            <div class="col-md-6">
                                <h6>มูลค่าทางสังคมประมาณการ</h6>
                                <h4><span class="text-muted">ไม่มีข้อมูล</span></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Detailed Impact Chain -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-sitemap"></i> รายละเอียด Impact Chain</h5>
                    </div>
                    <div class="card-body">
                        <!-- Strategies -->
                        <div class="mb-4">
                            <h6 class="text-primary"><i class="fas fa-bullseye"></i> ยุทธศาสตร์ที่เลือก</h6>
                            <ul class="list-group">
                                <?php foreach ($selected_strategies as $strategy): ?>
                                    <li class="list-group-item">
                                        <strong><?php echo $strategy['strategy_id']; ?>. <?php echo htmlspecialchars($strategy['strategy_name']); ?></strong>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Activities -->
                        <div class="mb-4">
                            <h6 class="text-info"><i class="fas fa-tasks"></i> กิจกรรมที่เลือก</h6>
                            <?php if ($selected_activity): ?>
                                <ul class="list-group">
                                    <li class="list-group-item">
                                        <strong><?php echo htmlspecialchars($selected_activity['activity_name']); ?></strong>
                                        <span class="badge bg-secondary ms-2"><?php echo htmlspecialchars($selected_activity['activity_code']); ?></span>
                                        <br><small class="text-muted">ยุทธศาสตร์: <?php echo htmlspecialchars($selected_activity['strategy_name']); ?></small>
                                        <?php if (!empty($selected_activity['activity_description'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($selected_activity['activity_description']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-info">ยังไม่ได้เลือกกิจกรรม</div>
                            <?php endif; ?>
                        </div>

                        <!-- Outputs -->
                        <div class="mb-4">
                            <h6 class="text-warning"><i class="fas fa-cube"></i> ผลผลิตที่เลือก</h6>
                            <?php if (!empty($selected_outputs)): ?>
                                <ul class="list-group">
                                    <?php foreach ($selected_outputs as $output): ?>
                                        <li class="list-group-item">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div class="flex-grow-1">
                                                    <strong><?php echo htmlspecialchars($output['output_description']); ?></strong>
                                                    <?php if ($output['output_sequence'] == 1): ?>
                                                        <span class="badge bg-primary ms-2">ผลผลิตหลัก</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-info ms-2">ผลผลิตย่อย</span>
                                                    <?php endif; ?>
                                                    <br><small class="text-muted">กิจกรรม: <?php echo htmlspecialchars($output['activity_name']); ?></small>
                                                    <br><small class="text-muted">ยุทธศาสตร์: <?php echo $output['strategy_id']; ?>. <?php echo htmlspecialchars($output['strategy_name']); ?></small>

                                                    <?php if (!empty($output['target_details'])): ?>
                                                        <div class="mt-2">
                                                            <small class="text-primary"><strong>เป้าหมาย:</strong></small>
                                                            <br><small class="text-muted"><?php echo nl2br(htmlspecialchars($output['target_details'])); ?></small>
                                                        </div>
                                                    <?php endif; ?>

                                                    <?php if (!empty($output['output_details'])): ?>
                                                        <div class="mt-2">
                                                            <small class="text-success"><strong>รายละเอียดเพิ่มเติม:</strong></small>
                                                            <br><small class="text-muted"><?php echo nl2br(htmlspecialchars($output['output_details'])); ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <div class="alert alert-info">ยังไม่ได้เลือกผลผลิต</div>
                            <?php endif; ?>
                        </div>

                        <!-- Outcomes -->
                        <div class="mb-4">
                            <h6 class="text-success"><i class="fas fa-bullseye"></i> ผลลัพธ์ที่เลือก</h6>
                            <ul class="list-group">
                                <?php foreach ($selected_outcomes as $outcome): ?>
                                    <li class="list-group-item">
                                        <strong><?php echo htmlspecialchars($outcome['name']); ?></strong>
                                        <br><small class="text-muted">ผลผลิต: <?php echo htmlspecialchars($outcome['output_name']); ?></small>
                                        <?php if (!empty($outcome['description'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($outcome['description']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>

                        <!-- Financial Proxies -->
                        <?php if (!empty($financial_proxies)): ?>
                            <div class="mb-4">
                                <h6 class="text-danger"><i class="fas fa-dollar-sign"></i> Financial Proxies</h6>
                                <div class="table-responsive">
                                    <table class="table table-striped">
                                        <thead>
                                            <tr>
                                                <th>ชื่อ</th>
                                                <th>ผลลัพธ์</th>
                                                <th>ผลผลิต</th>
                                                <th>หน่วย</th>
                                                <th class="text-end">มูลค่าประมาณการ</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($financial_proxies as $fp): ?>
                                                <tr>
                                                    <td><strong><?php echo htmlspecialchars($fp['name']); ?></strong></td>
                                                    <td><?php echo htmlspecialchars($fp['outcome_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($fp['output_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($fp['unit'] ?? '-'); ?></td>
                                                    <td class="text-end">
                                                        <?php if ($fp['estimated_value']): ?>
                                                            <span class="text-success fw-bold">฿<?php echo number_format($fp['estimated_value'], 2); ?></span>
                                                        <?php else: ?>
                                                            <span class="text-muted">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                        <tfoot>
                                            <tr class="table-success">
                                                <th colspan="4">รวมมูลค่าประมาณการทั้งหมด</th>
                                                <th class="text-end">฿<?php echo number_format($total_estimated_value, 2); ?></th>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="d-flex justify-content-between">
                    <a href="../project-list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> กลับไปรายการโครงการ
                    </a>
                    <div>
                        <a href="step1-strategy.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-primary me-2">
                            <i class="fas fa-edit"></i> แก้ไข Impact Chain
                        </a>
                        <button class="btn btn-success" onclick="alert('ฟีเจอร์บันทึกข้อมูลจริงจะพัฒนาต่อไป')">
                            <i class="fas fa-save"></i> บันทึกข้อมูลจริง
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Test Mode Notice -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert alert-warning">
                    <h6><i class="fas fa-exclamation-triangle"></i> โหมดทดสอบ</h6>
                    <p class="mb-0">ขณะนี้ระบบอยู่ในโหมดทดสอบ ข้อมูลที่แสดงยังไม่ได้บันทึกลงในฐานข้อมูลจริง
                        เมื่อพัฒนาเสร็จสิ้นจะสามารถบันทึกข้อมูลลง database ได้</p>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>