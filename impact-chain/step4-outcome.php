<?php
session_start();
require_once '../config.php';

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

// ตรวจสอบว่าได้เลือกกิจกรรมและผลผลิตแล้วหรือยัง
$selected_activity = null;
$selected_outputs = [];

// ดึงข้อมูลกิจกรรมที่เลือก
if (isset($_SESSION['test_selected_activity_detail'])) {
    $selected_activity = $_SESSION['test_selected_activity_detail'];
} else {
    // ดึงจากฐานข้อมูล (โหมดปกติ)
    $activity_query = "SELECT pa.master_activity_id as id, ma.name, ma.level, ms.name as strategy_name
                       FROM project_activities pa 
                       JOIN master_activities ma ON pa.master_activity_id = ma.id 
                       JOIN master_strategies ms ON ma.strategy_id = ms.id
                       WHERE pa.project_id = ? 
                       ORDER BY pa.created_at DESC 
                       LIMIT 1";
    $activity_stmt = mysqli_prepare($conn, $activity_query);
    mysqli_stmt_bind_param($activity_stmt, 'i', $project_id);
    mysqli_stmt_execute($activity_stmt);
    $activity_result = mysqli_stmt_get_result($activity_stmt);
    $selected_activity = mysqli_fetch_assoc($activity_result);
    mysqli_stmt_close($activity_stmt);
}

// ดึงข้อมูลผลผลิตที่เลือก
if (isset($_SESSION['test_selected_outputs_detail'])) {
    // ใช้ข้อมูลจาก session (โหมดทดสอบ)
    $selected_outputs = $_SESSION['test_selected_outputs_detail'];
} else {
    // ดึงจากฐานข้อมูล (โหมดปกติ)
    $outputs_query = "SELECT po.master_output_id as id, mo.description_template, ma.name as activity_name, ms.name as strategy_name
                      FROM project_outputs po 
                      JOIN master_outputs mo ON po.master_output_id = mo.id 
                      JOIN master_activities ma ON mo.activity_id = ma.id
                      JOIN master_strategies ms ON ma.strategy_id = ms.id
                      WHERE po.project_id = ?";
    $outputs_stmt = mysqli_prepare($conn, $outputs_query);
    mysqli_stmt_bind_param($outputs_stmt, 'i', $project_id);
    mysqli_stmt_execute($outputs_stmt);
    $outputs_result = mysqli_stmt_get_result($outputs_stmt);
    $selected_outputs = mysqli_fetch_all($outputs_result, MYSQLI_ASSOC);
    mysqli_stmt_close($outputs_stmt);
}

// ตรวจสอบว่ามีข้อมูลครบถ้วน
if (!$selected_activity) {
    $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อน";
    header("location: step2-activity.php?project_id=" . $project_id);
    exit;
}

if (empty($selected_outputs)) {
    $_SESSION['error_message'] = "กรุณาเลือกผลผลิตก่อน";
    header("location: step3-output.php?project_id=" . $project_id);
    exit;
}

// ดึงผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก
$output_ids = array_column($selected_outputs, 'id');
$outcomes = [];

if (!empty($output_ids)) {
    $output_ids_str = implode(',', array_map('intval', $output_ids));

    $outcomes_query = "SELECT moc.*, mo.name as output_name, ma.name as activity_name, ms.name as strategy_name
                       FROM master_outcomes moc 
                       JOIN master_outputs mo ON moc.output_id = mo.id 
                       JOIN master_activities ma ON mo.activity_id = ma.id 
                       JOIN master_strategies ms ON ma.strategy_id = ms.id
                       WHERE moc.output_id IN ($output_ids_str) AND moc.is_active = 1 
                       ORDER BY ms.id ASC, ma.id ASC, mo.id ASC, moc.id ASC";
    $outcomes_result = mysqli_query($conn, $outcomes_query);
    $outcomes = mysqli_fetch_all($outcomes_result, MYSQLI_ASSOC);
}

// ดึงผลลัพธ์ที่เลือกไว้แล้ว (ถ้ามี) - สำหรับโหมดทดสอบจะใช้ session
$selected_outcomes = [];
if (isset($_SESSION['test_selected_outcomes'])) {
    $selected_outcomes = $_SESSION['test_selected_outcomes'];
} else {
    $selected_outcomes_query = "SELECT master_outcome_id FROM project_outcomes WHERE project_id = ?";
    $selected_stmt = mysqli_prepare($conn, $selected_outcomes_query);
    mysqli_stmt_bind_param($selected_stmt, 'i', $project_id);
    mysqli_stmt_execute($selected_stmt);
    $selected_result = mysqli_stmt_get_result($selected_stmt);
    while ($row = mysqli_fetch_assoc($selected_result)) {
        $selected_outcomes[] = $row['master_outcome_id'];
    }
    mysqli_stmt_close($selected_stmt);
}

// จัดกลุ่มผลลัพธ์ตามผลผลิต
$outcomes_by_output = [];
foreach ($outcomes as $outcome) {
    $outcomes_by_output[$outcome['output_name']][] = $outcome;
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 4: เลือกผลลัพธ์ - SROI System</title>
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
                        <li class="breadcrumb-item"><a href="step1-strategy.php?project_id=<?php echo $project_id; ?>">Step 1</a></li>
                        <li class="breadcrumb-item"><a href="step2-activity.php?project_id=<?php echo $project_id; ?>">Step 2</a></li>
                        <li class="breadcrumb-item"><a href="step3-output.php?project_id=<?php echo $project_id; ?>">Step 3</a></li>
                        <li class="breadcrumb-item active">Step 4: ผลลัพธ์</li>
                    </ol>
                </nav>
                <h2>สร้าง Impact Chain: <?php echo htmlspecialchars($project['name']); ?></h2>
            </div>
        </div>

        <!-- Progress Steps -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="progress" style="height: 30px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemax="100"></div>
                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemax="100"></div>
                    <div class="progress-bar bg-success" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemax="100"></div>
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemax="100">
                        Step 4: ผลลัพธ์
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-success">✓ 1. ยุทธศาสตร์</small>
                    <small class="text-success">✓ 2. กิจกรรม</small>
                    <small class="text-success">✓ 3. ผลผลิต</small>
                    <small class="text-primary fw-bold">4. ผลลัพธ์</small>
                </div>
            </div>
        </div>

        <!-- Selected Activity and Outputs Info -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> กิจกรรมและผลผลิตที่เลือกไว้:</h6>

                    <!-- แสดงกิจกรรมที่เลือก -->
                    <div class="mb-3">
                        <strong><i class="fas fa-tasks"></i> กิจกรรม:</strong>
                        <?php if ($selected_activity): ?>
                            <?php echo htmlspecialchars($selected_activity['name']); ?>
                            <span class="badge bg-info ms-2">ระดับ <?php echo $selected_activity['level']; ?></span>
                            <br><small class="text-muted">ยุทธศาสตร์: <?php echo htmlspecialchars($selected_activity['strategy_name']); ?></small>
                        <?php else: ?>
                            <span class="text-danger">ไม่มีกิจกรรมที่เลือกไว้</span>
                        <?php endif; ?>
                    </div>

                    <!-- แสดงผลผลิตที่เลือก -->
                    <div class="mb-0">
                        <strong><i class="fas fa-cube"></i> ผลผลิต:</strong>
                        <?php if (!empty($selected_outputs)): ?>
                            <ol class="mb-0 mt-2">
                                <?php foreach ($selected_outputs as $output): ?>
                                    <li><?php echo htmlspecialchars($output['description_template']); ?></li>
                                <?php endforeach; ?>
                            </ol>
                        <?php else: ?>
                            <span class="text-danger">ไม่มีผลผลิตที่เลือกไว้</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bullseye"></i> เลือกผลลัพธ์และ Financial Proxies</h5>
                        <small class="text-muted">เลือกผลลัพธ์ที่คาดว่าจะเกิดขึ้นจากผลผลิต และระบบจะแสดง Financial Proxies ที่เกี่ยวข้อง</small>
                    </div>
                    <div class="card-body">
                        <?php if (empty($outcomes)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> ไม่พบข้อมูลผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="step3-output.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                </a>
                            </div>
                        <?php else: ?>
                            <form action="process-step4.php" method="POST">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                                <?php
                                $outcome_counter = 1;
                                foreach ($outcomes_by_output as $output_name => $output_outcomes):
                                ?>
                                    <div class="mb-4">
                                        <h6 class="text-primary border-bottom pb-2">
                                            <i class="fas fa-cube"></i> ผลผลิต: <?php echo htmlspecialchars($output_name); ?>
                                        </h6>
                                        <div class="row">
                                            <?php
                                            $sub_counter = 1;
                                            foreach ($output_outcomes as $outcome):
                                            ?>
                                                <div class="col-md-6 mb-3">
                                                    <div class="card h-100 <?php echo in_array($outcome['id'], $selected_outcomes) ? 'border-primary' : ''; ?>">
                                                        <div class="card-body">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="checkbox"
                                                                    name="outcomes[]" value="<?php echo $outcome['id']; ?>"
                                                                    id="outcome_<?php echo $outcome['id']; ?>"
                                                                    <?php echo in_array($outcome['id'], $selected_outcomes) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label fw-bold" for="outcome_<?php echo $outcome['id']; ?>">
                                                                    <?php echo $outcome_counter; ?>.<?php echo $sub_counter; ?> <?php echo htmlspecialchars($outcome['name']); ?>
                                                                </label>
                                                            </div>
                                                            <?php if (!empty($outcome['description'])): ?>
                                                                <p class="card-text mt-2 text-muted small">
                                                                    <?php echo htmlspecialchars($outcome['description']); ?>
                                                                </p>
                                                            <?php endif; ?>

                                                            <!-- แสดง Financial Proxies ที่เกี่ยวข้อง -->
                                                            <?php
                                                            $fp_query = "SELECT name, unit, estimated_value FROM master_financial_proxies WHERE outcome_id = ? AND is_active = 1";
                                                            $fp_stmt = mysqli_prepare($conn, $fp_query);
                                                            mysqli_stmt_bind_param($fp_stmt, 'i', $outcome['id']);
                                                            mysqli_stmt_execute($fp_stmt);
                                                            $fp_result = mysqli_stmt_get_result($fp_stmt);
                                                            $financial_proxies = mysqli_fetch_all($fp_result, MYSQLI_ASSOC);
                                                            mysqli_stmt_close($fp_stmt);
                                                            ?>

                                                            <?php if (!empty($financial_proxies)): ?>
                                                                <div class="mt-3">
                                                                    <h6 class="text-success small">💰 Financial Proxies:</h6>
                                                                    <?php foreach ($financial_proxies as $fp): ?>
                                                                        <div class="text-success small">
                                                                            • <?php echo htmlspecialchars($fp['name']); ?>
                                                                            <?php if ($fp['estimated_value']): ?>
                                                                                <br>&nbsp;&nbsp;มูลค่าประมาณการ: ฿<?php echo number_format($fp['estimated_value'], 2); ?>
                                                                                <?php if ($fp['unit']): ?>
                                                                                    ต่อ<?php echo htmlspecialchars($fp['unit']); ?>
                                                                                <?php endif; ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php
                                                $sub_counter++;
                                            endforeach;
                                            ?>
                                        </div>
                                    </div>
                                <?php
                                    $outcome_counter++;
                                endforeach;
                                ?>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="step3-output.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                    </a>
                                    <button type="submit" class="btn btn-success">
                                        เสร็จสิ้น: ดูสรุป Impact Chain <i class="fas fa-check"></i>
                                    </button>
                                </div>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // เพิ่ม visual feedback เมื่อเลือก outcome
        document.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const card = this.closest('.card');
                if (this.checked) {
                    card.classList.add('border-primary');
                } else {
                    card.classList.remove('border-primary');
                }
            });
        });
    </script>
</body>

</html>