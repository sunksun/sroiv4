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
    // ใช้ prepared statement แทนการสร้าง query string โดยตรง
    $placeholders = str_repeat('?,', count($output_ids) - 1) . '?';
    $outcomes_query = "SELECT oc.*, o.output_description, o.output_sequence, a.activity_name, s.strategy_name
                       FROM outcomes oc 
                       JOIN outputs o ON oc.output_id = o.output_id 
                       JOIN activities a ON o.activity_id = a.activity_id 
                       JOIN strategies s ON a.strategy_id = s.strategy_id
                       WHERE oc.output_id IN ($placeholders) 
                       ORDER BY o.output_sequence ASC, oc.outcome_sequence ASC";

    $outcomes_stmt = mysqli_prepare($conn, $outcomes_query);
    $types = str_repeat('i', count($output_ids));
    mysqli_stmt_bind_param($outcomes_stmt, $types, ...$output_ids);
    mysqli_stmt_execute($outcomes_stmt);
    $outcomes_result = mysqli_stmt_get_result($outcomes_stmt);
    $outcomes = mysqli_fetch_all($outcomes_result, MYSQLI_ASSOC);
    mysqli_stmt_close($outcomes_stmt);
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
    <style>
        .outcome-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid #e9ecef;
        }

        .outcome-card:hover {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.05);
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .outcome-card.selected {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .form-check-input:checked {
            background-color: #0d6efd;
            border-color: #0d6efd;
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

        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-bullseye"></i> เลือกผลลัพธ์ที่คาดว่าจะเกิดขึ้น</h5>
                        <small class="text-muted">เลือกผลลัพธ์ที่คาดว่าจะเกิดขึ้นจากผลผลิตที่เลือกไว้</small>

                        <?php if (!empty($selected_outcomes)): ?>
                            <div class="mt-2">
                                <div class="alert alert-success mb-0">
                                    <i class="fas fa-check-circle"></i>
                                    <strong>ผลลัพธ์ที่เลือกไว้:</strong> <?php echo count($selected_outcomes); ?> รายการ
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($outcomes)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i>
                                <strong>ไม่พบผลลัพธ์ที่เกี่ยวข้อง</strong><br>
                                ไม่พบข้อมูลผลลัพธ์ที่เกี่ยวข้องกับผลผลิตที่เลือก:<br>
                                <?php foreach ($selected_outputs as $output): ?>
                                    <small class="text-muted">• <?php echo htmlspecialchars($output['output_description']); ?></small><br>
                                <?php endforeach; ?>
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="step3-output.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                </a>
                                <a href="summary.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-primary">
                                    ข้ามไปสรุป <i class="fas fa-arrow-right"></i>
                                </a>
                            </div>
                        <?php else: ?>
                            <form action="process-step4.php" method="POST" id="outcomeForm">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                                <div class="mb-4">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle"></i>
                                        <strong>คำแนะนำ:</strong> เลือกผลลัพธ์ที่คาดว่าจะเกิดขึ้นจากผลผลิตที่คุณเลือกไว้ สามารถเลือกได้หลายรายการ
                                    </div>
                                </div>

                                <?php foreach ($outcomes_by_output as $output_name => $output_outcomes): ?>
                                    <div class="mb-5">
                                        <h6 class="text-primary border-bottom pb-2 mb-3">
                                            <i class="fas fa-cube"></i> ผลผลิต: <?php echo htmlspecialchars($output_name); ?>
                                        </h6>

                                        <?php if (empty($output_outcomes)): ?>
                                            <div class="alert alert-light">
                                                <i class="fas fa-info-circle"></i> ไม่พบผลลัพธ์ที่เกี่ยวข้องกับผลผลิตนี้
                                            </div>
                                        <?php else: ?>
                                            <div class="row">
                                                <?php foreach ($output_outcomes as $outcome): ?>
                                                    <div class="col-md-6 col-lg-4 mb-3">
                                                        <div class="card outcome-card h-100 <?php echo in_array($outcome['outcome_id'], $selected_outcomes) ? 'selected' : ''; ?>">
                                                            <div class="card-body">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="checkbox"
                                                                        name="outcomes[]" value="<?php echo $outcome['outcome_id']; ?>"
                                                                        id="outcome_<?php echo $outcome['outcome_id']; ?>"
                                                                        <?php echo in_array($outcome['outcome_id'], $selected_outcomes) ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label w-100" for="outcome_<?php echo $outcome['outcome_id']; ?>">
                                                                        <div class="fw-bold text-primary mb-2">
                                                                            <?php echo htmlspecialchars($outcome['outcome_sequence']); ?>
                                                                        </div>
                                                                        <div class="small text-dark">
                                                                            <?php echo htmlspecialchars($outcome['outcome_description']); ?>
                                                                        </div>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="step3-output.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                    </a>
                                    <button type="submit" class="btn btn-success" id="submitBtn">
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
                const card = this.closest('.outcome-card');
                if (this.checked) {
                    card.classList.add('selected');
                } else {
                    card.classList.remove('selected');
                }

                // อัปเดตสถานะปุ่ม Submit
                updateSubmitButton();
            });
        });

        // เพิ่มการคลิกที่ card เพื่อ toggle checkbox
        document.querySelectorAll('.outcome-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // ป้องกันการคลิกซ้ำจาก checkbox
                if (e.target.type !== 'checkbox') {
                    const checkbox = this.querySelector('input[type="checkbox"]');
                    if (checkbox) {
                        checkbox.checked = !checkbox.checked;
                        checkbox.dispatchEvent(new Event('change'));
                    }
                }
            });
        });

        // ฟังก์ชันอัปเดตสถานะปุ่ม Submit
        function updateSubmitButton() {
            const checkboxes = document.querySelectorAll('input[name="outcomes[]"]:checked');
            const submitBtn = document.getElementById('submitBtn');

            if (checkboxes.length > 0) {
                submitBtn.innerHTML = '<i class="fas fa-check"></i> เสร็จสิ้น: ดูสรุป Impact Chain (' + checkboxes.length + ' รายการ)';
                submitBtn.disabled = false;
            } else {
                submitBtn.innerHTML = '<i class="fas fa-check"></i> เสร็จสิ้น: ดูสรุป Impact Chain';
                submitBtn.disabled = false; // ยอมให้ส่งได้แม้ไม่เลือก (จะข้ามไปสรุป)
            }
        }

        // ตรวจสอบเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            updateSubmitButton();
        });

        // ตรวจสอบก่อน submit
        document.getElementById('outcomeForm').addEventListener('submit', function(e) {
            const checkboxes = document.querySelectorAll('input[name="outcomes[]"]:checked');

            if (checkboxes.length === 0) {
                if (!confirm('คุณยังไม่ได้เลือกผลลัพธ์ ต้องการข้ามไปหน้าสรุปหรือไม่?')) {
                    e.preventDefault();
                    return false;
                }
            }
        });
    </script>
</body>

</html>