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

// ตรวจสอบว่าได้เลือกกิจกรรมและผลผลิตแล้วหรือยัง
$selected_activity = null;
$selected_outputs = [];

// ดึงข้อมูลกิจกรรมที่เลือก
$activity_query = "SELECT pa.activity_id, a.activity_name, a.activity_code, a.activity_description, s.strategy_id, s.strategy_name 
                   FROM project_activities pa 
                   JOIN activities a ON pa.activity_id = a.activity_id 
                   JOIN strategies s ON a.strategy_id = s.strategy_id 
                   WHERE pa.project_id = ? 
                   ORDER BY pa.created_at DESC 
                   LIMIT 1";
$activity_stmt = mysqli_prepare($conn, $activity_query);
mysqli_stmt_bind_param($activity_stmt, 'i', $project_id);
mysqli_stmt_execute($activity_stmt);
$activity_result = mysqli_stmt_get_result($activity_stmt);
$selected_activity = mysqli_fetch_assoc($activity_result);
mysqli_stmt_close($activity_stmt);

// ดึงข้อมูลผลผลิตที่เลือก
$outputs_query = "SELECT po.output_id, o.output_description, o.output_sequence, po.output_details, a.activity_name, s.strategy_name
                  FROM project_outputs po 
                  JOIN outputs o ON po.output_id = o.output_id 
                  JOIN activities a ON o.activity_id = a.activity_id
                  JOIN strategies s ON a.strategy_id = s.strategy_id
                  WHERE po.project_id = ?";
$outputs_stmt = mysqli_prepare($conn, $outputs_query);
mysqli_stmt_bind_param($outputs_stmt, 'i', $project_id);
mysqli_stmt_execute($outputs_stmt);
$outputs_result = mysqli_stmt_get_result($outputs_stmt);
$selected_outputs = mysqli_fetch_all($outputs_result, MYSQLI_ASSOC);
mysqli_stmt_close($outputs_stmt);

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
$output_ids = array_column($selected_outputs, 'output_id');
$outcomes = [];

if (!empty($output_ids)) {
    $output_ids_str = implode(',', array_map('intval', $output_ids));

    $outcomes_query = "SELECT oc.*, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
                       FROM outcomes oc 
                       JOIN outputs o ON oc.output_id = o.output_id 
                       JOIN activities a ON o.activity_id = a.activity_id 
                       JOIN strategies s ON a.strategy_id = s.strategy_id
                       WHERE oc.output_id IN ($output_ids_str) 
                       ORDER BY s.strategy_id ASC, a.activity_id ASC, o.output_id ASC, oc.outcome_sequence ASC";
    $outcomes_result = mysqli_query($conn, $outcomes_query);
    $outcomes = mysqli_fetch_all($outcomes_result, MYSQLI_ASSOC);
}

// ดึงผลลัพธ์ที่เลือกไว้แล้ว (ถ้ามี)
$selected_outcomes = [];
$selected_outcomes_query = "SELECT outcome_id FROM project_outcomes WHERE project_id = ?";
$selected_stmt = mysqli_prepare($conn, $selected_outcomes_query);
mysqli_stmt_bind_param($selected_stmt, 'i', $project_id);
mysqli_stmt_execute($selected_stmt);
$selected_result = mysqli_stmt_get_result($selected_stmt);
while ($row = mysqli_fetch_assoc($selected_result)) {
    $selected_outcomes[] = $row['outcome_id'];
}
mysqli_stmt_close($selected_stmt);

// จัดกลุ่มผลลัพธ์ตามผลผลิต
$outcomes_by_output = [];
foreach ($outcomes as $outcome) {
    $outcomes_by_output[$outcome['output_description']][] = $outcome;
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
                    <div class="progress-bar bg-success" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemax="100"></div>
                    <div class="progress-bar bg-success" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemax="100"></div>
                    <div class="progress-bar bg-success" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemax="100"></div>
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemax="100">
                        Step 4: ผลลัพธ์
                    </div>
                    <div class="progress-bar bg-light border" role="progressbar" style="width: 20%" aria-valuenow="20" aria-valuemax="100">
                        <span class="text-dark">สรุป</span>
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-success">✓ 1. ยุทธศาสตร์</small>
                    <small class="text-success">✓ 2. กิจกรรม</small>
                    <small class="text-success">✓ 3. ผลผลิต</small>
                    <small class="text-primary fw-bold">4. ผลลัพธ์</small>
                    <small class="text-muted">5. สรุป</small>
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
                            <?php echo htmlspecialchars($selected_activity['activity_name']); ?>
                            <span class="badge bg-info ms-2"><?php echo htmlspecialchars($selected_activity['activity_code']); ?></span>
                            <br><small class="text-muted">
                                <i class="fas fa-bullseye"></i> ยุทธศาสตร์: <?php echo $selected_activity['strategy_id']; ?>. <?php echo htmlspecialchars($selected_activity['strategy_name']); ?>
                            </small>
                        <?php else: ?>
                            <span class="text-danger">ไม่มีกิจกรรมที่เลือกไว้</span>
                        <?php endif; ?>
                    </div>

                    <!-- แสดงผลผลิตที่เลือก -->
                    <div class="mb-0">
                        <strong><i class="fas fa-cube"></i> ผลผลิต:</strong>
                        <?php if (!empty($selected_outputs)): ?>
                            <ul class="mb-0 mt-2">
                                <?php foreach ($selected_outputs as $output): ?>
                                    <li>
                                        <strong><?php echo htmlspecialchars($output['output_description']); ?></strong>
                                        <?php if (!empty($output['output_details'])): ?>
                                            <br><small class="text-muted">รายละเอียด: <?php echo htmlspecialchars($output['output_details']); ?></small>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <span class="text-danger">ไม่มีผลผลิตที่เลือกไว้</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Debug Information -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-info">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-bug"></i> Debug Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>ข้อมูลการตรวจสอบ:</h6>
                        <ul>
                            <li><strong>Project ID:</strong> <?php echo $project_id; ?></li>
                            <li><strong>User ID:</strong> <?php echo $_SESSION['user_id']; ?></li>
                            <li><strong>มีกิจกรรม:</strong> <?php echo $selected_activity ? '✅ Yes' : '❌ No'; ?></li>
                            <li><strong>จำนวนผลผลิต:</strong> <?php echo count($selected_outputs); ?></li>
                            <li><strong>จำนวนผลลัพธ์:</strong> <?php echo count($outcomes); ?></li>
                        </ul>

                        <?php if (!empty($selected_outputs)): ?>
                            <h6 class="mt-3">ผลผลิตที่เลือก:</h6>
                            <pre style="font-size: 12px; max-height: 200px; overflow-y: auto;"><?php var_dump($selected_outputs); ?></pre>
                        <?php endif; ?>

                        <?php if (!empty($outcomes)): ?>
                            <h6 class="mt-3">ผลลัพธ์ที่พบ:</h6>
                            <pre style="font-size: 12px; max-height: 200px; overflow-y: auto;"><?php var_dump($outcomes); ?></pre>
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
                        <h5><i class="fas fa-bullseye"></i> เลือกผลลัพธ์ (Coming Soon)</h5>
                        <small class="text-muted">ระบบกำลังตรวจสอบข้อมูล...</small>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle"></i>
                            หน้านี้อยู่ระหว่างการพัฒนา กรุณาตรวจสอบข้อมูล Debug ด้านบน
                        </div>

                        <div class="d-flex justify-content-between mt-4">
                            <a href="step3-output.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> ย้อนกลับ
                            </a>
                            <a href="summary.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-primary">
                                ข้ามไปสรุป <i class="fas fa-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>