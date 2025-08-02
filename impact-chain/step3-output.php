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

// ตรวจสอบว่าได้เลือกกิจกรรมแล้วหรือยัง
$selected_activity = null;
$activity_query = "SELECT pa.activity_id, a.activity_name, a.activity_code, a.activity_description, s.strategy_id, s.strategy_name 
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
} else {
    $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อน";
    header("location: step2-activity.php?project_id=" . $project_id);
    exit;
}
mysqli_stmt_close($activity_stmt);

if (!$selected_activity) {
    $_SESSION['error_message'] = "กรุณาเลือกกิจกรรมก่อน";
    header("location: step2-activity.php?project_id=" . $project_id);
    exit;
}

// ดึงผลผลิตที่เกี่ยวข้องกับกิจกรรมที่เลือก
$outputs = [];
$activity_id = $selected_activity['activity_id'];

$outputs_query = "SELECT o.*, a.activity_name, s.strategy_name
                  FROM outputs o 
                  JOIN activities a ON o.activity_id = a.activity_id 
                  JOIN strategies s ON a.strategy_id = s.strategy_id
                  WHERE o.activity_id = ? 
                  ORDER BY o.output_sequence ASC, o.output_id ASC";
$outputs_stmt = mysqli_prepare($conn, $outputs_query);
mysqli_stmt_bind_param($outputs_stmt, 's', $activity_id);
mysqli_stmt_execute($outputs_stmt);
$outputs_result = mysqli_stmt_get_result($outputs_stmt);
$outputs = mysqli_fetch_all($outputs_result, MYSQLI_ASSOC);
mysqli_stmt_close($outputs_stmt);

// Debug: ตรวจสอบข้อมูลผลผลิต
// echo "<pre>Selected Activity: "; print_r($selected_activity); echo "</pre>";
// echo "<pre>Activity ID for query: " . $activity_id . "</pre>";
// echo "<pre>Outputs found: "; print_r($outputs); echo "</pre>";

// ดึงผลผลิตที่เลือกไว้แล้วจากฐานข้อมูล
$selected_outputs = [];
$selected_output_ids = [];
$selected_output_query = "SELECT po.output_id, o.output_description
                         FROM project_outputs po 
                         JOIN outputs o ON po.output_id = o.output_id 
                         WHERE po.project_id = ?";
$selected_output_stmt = mysqli_prepare($conn, $selected_output_query);
mysqli_stmt_bind_param($selected_output_stmt, 'i', $project_id);
mysqli_stmt_execute($selected_output_stmt);
$selected_output_result = mysqli_stmt_get_result($selected_output_stmt);

while ($output = mysqli_fetch_assoc($selected_output_result)) {
    $selected_outputs[] = $output;
    $selected_output_ids[] = $output['output_id'];
}
mysqli_stmt_close($selected_output_stmt);
if (isset($_SESSION['test_selected_outputs'])) {
    $selected_outputs = $_SESSION['test_selected_outputs'];
}

// จัดกลุ่มผลผลิตตาม output_sequence (หลัก/ย่อย)
$grouped_outputs = [];
foreach ($outputs as $output) {
    $sequence = $output['output_sequence'];

    // ตรวจสอบว่าเป็นผลผลิตหลัก (เลขจำนวนเต็ม เช่น 1, 2, 3) หรือผลผลิตย่อย (เช่น 1.1, 1.2, 2.1)
    if (strpos($sequence, '.') === false) {
        // ผลผลิตหลัก
        $grouped_outputs[$sequence]['main'] = $output;
    } else {
        // ผลผลิตย่อย
        $main_sequence = explode('.', $sequence)[0];
        $grouped_outputs[$main_sequence]['sub'][] = $output;
    }
}

// เรียงลำดับกลุ่มตาม sequence
ksort($grouped_outputs);
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 3: เลือกผลผลิต - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card-body.py-2 {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        .output-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .output-card:hover {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .output-card.selected {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .sub-outputs {
            margin-left: 1.5rem;
            padding-left: 1rem;
            border-left: 3px solid #dee2e6;
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
                        <li class="breadcrumb-item active">Step 3: ผลผลิต</li>
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
                    <div class="progress-bar bg-primary" role="progressbar" style="width: 25%" aria-valuenow="25" aria-valuemax="100">
                        Step 3: ผลผลิต
                    </div>
                </div>
                <div class="d-flex justify-content-between mt-2">
                    <small class="text-success">✓ 1. ยุทธศาสตร์</small>
                    <small class="text-success">✓ 2. กิจกรรม</small>
                    <small class="text-primary fw-bold">3. ผลผลิต</small>
                    <small class="text-muted">4. ผลลัพธ์</small>
                </div>
            </div>
        </div>

        <!-- Selected Activity Info -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> กิจกรรมที่เลือกไว้:</h6>
                    <div class="mb-0">
                        <strong><?php echo htmlspecialchars($selected_activity['activity_name']); ?></strong>
                        <span class="badge bg-info ms-2"><?php echo htmlspecialchars($selected_activity['activity_code']); ?></span>
                        <br>
                        <small class="text-muted">
                            <i class="fas fa-bullseye"></i> ยุทธศาสตร์: <?php echo $selected_activity['strategy_id']; ?>. <?php echo htmlspecialchars($selected_activity['strategy_name']); ?>
                        </small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-cube"></i> เลือกผลผลิตสำหรับโครงการ</h5>
                        <small class="text-muted">เลือกผลผลิตที่คาดว่าจะได้รับจากการดำเนินกิจกรรม</small>

                        <?php if (!empty($selected_outputs)): ?>
                            <div class="mt-2">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>ผลผลิตที่เลือกไว้:</strong> <?php echo count($selected_outputs); ?> รายการ
                                    <ul class="mb-0 mt-1">
                                        <?php foreach ($selected_outputs as $output): ?>
                                            <li><?php echo htmlspecialchars($output['output_description']); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($outputs)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> ไม่พบข้อมูลผลผลิตที่เกี่ยวข้องกับกิจกรรมที่เลือก
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="step2-activity.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <h6 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-cube"></i> ผลผลิตจากกิจกรรม: <?php echo htmlspecialchars($selected_activity['activity_name']); ?>
                                </h6>

                                <?php
                                $output_counter = 1;
                                foreach ($grouped_outputs as $main_sequence => $group):
                                ?>
                                    <!-- ผลผลิตหลัก -->
                                    <?php if (isset($group['main'])): ?>
                                        <div class="mb-4">
                                            <div class="row">
                                                <div class="col-12 mb-3">
                                                    <div class="card border-info output-card <?php echo in_array($group['main']['output_id'], $selected_output_ids) ? 'border-primary selected' : ''; ?>">
                                                        <div class="card-body">
                                                            <div class="form-check">
                                                                <input class="form-check-input" type="radio"
                                                                    name="selected_output" value="<?php echo $group['main']['output_id']; ?>"
                                                                    id="output_<?php echo $group['main']['output_id']; ?>"
                                                                    data-output-description="<?php echo htmlspecialchars($group['main']['output_description']); ?>"
                                                                    <?php echo in_array($group['main']['output_id'], $selected_output_ids) ? 'checked' : ''; ?>>
                                                                <label class="form-check-label fw-bold text-info" for="output_<?php echo $group['main']['output_id']; ?>">
                                                                    <i class="fas fa-layer-group"></i> <?php echo $group['main']['output_sequence']; ?>. <?php echo htmlspecialchars($group['main']['output_description']); ?>
                                                                </label>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- ผลผลิตย่อย (ถ้ามี) -->
                                            <?php if (isset($group['sub']) && !empty($group['sub'])): ?>
                                                <div class="ms-4">
                                                    <h6 class="text-secondary mb-2">
                                                        <i class="fas fa-list"></i> ผลผลิตย่อย:
                                                    </h6>
                                                    <div class="row">
                                                        <?php foreach ($group['sub'] as $sub_output): ?>
                                                            <div class="col-md-6 mb-2">
                                                                <div class="card border-secondary output-card <?php echo in_array($sub_output['output_id'], $selected_output_ids) ? 'border-primary selected' : ''; ?>">
                                                                    <div class="card-body py-2">
                                                                        <div class="form-check">
                                                                            <input class="form-check-input" type="radio"
                                                                                name="selected_output" value="<?php echo $sub_output['output_id']; ?>"
                                                                                id="output_<?php echo $sub_output['output_id']; ?>"
                                                                                data-output-description="<?php echo htmlspecialchars($sub_output['output_description']); ?>"
                                                                                <?php echo in_array($sub_output['output_id'], $selected_output_ids) ? 'checked' : ''; ?>>
                                                                            <label class="form-check-label text-secondary" for="output_<?php echo $sub_output['output_id']; ?>" style="font-size: 0.9rem;">
                                                                                <i class="fas fa-arrow-right"></i> <?php echo $sub_output['output_sequence']; ?>. <?php echo htmlspecialchars($sub_output['output_description']); ?>
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>

                                    <!-- ถ้ามีเฉพาะผลผลิตย่อยโดยไม่มีหลัก -->
                                    <?php if (!isset($group['main']) && isset($group['sub'])): ?>
                                        <div class="mb-4">
                                            <div class="row">
                                                <?php foreach ($group['sub'] as $sub_output): ?>
                                                    <div class="col-md-6 mb-3">
                                                        <div class="card output-card <?php echo in_array($sub_output['output_id'], $selected_output_ids) ? 'border-primary selected' : ''; ?>">
                                                            <div class="card-body">
                                                                <div class="form-check">
                                                                    <input class="form-check-input" type="radio"
                                                                        name="selected_output" value="<?php echo $sub_output['output_id']; ?>"
                                                                        id="output_<?php echo $sub_output['output_id']; ?>"
                                                                        data-output-description="<?php echo htmlspecialchars($sub_output['output_description']); ?>"
                                                                        <?php echo in_array($sub_output['output_id'], $selected_output_ids) ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label fw-bold" for="output_<?php echo $sub_output['output_id']; ?>">
                                                                        <?php echo $sub_output['output_sequence']; ?>. <?php echo htmlspecialchars($sub_output['output_description']); ?>
                                                                    </label>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>

                                <div class="alert alert-info mt-4">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>คำแนะนำ:</strong> กรุณาเลือกผลผลิตที่ต้องการ ระบบจะเปิดหน้าต่างให้กรอกรายละเอียดเพิ่มเติม
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal สำหรับกรอกรายละเอียดผลผลิต -->
    <div class="modal fade" id="outputModal" tabindex="-1" aria-labelledby="outputModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="outputForm" action="process-step3.php" method="POST">
                    <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                    <input type="hidden" name="selected_output_id" id="selected_output_id" value="">

                    <div class="modal-header">
                        <h5 class="modal-title" id="outputModalLabel">
                            <i class="fas fa-cube"></i> รายละเอียดผลผลิต
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>

                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-bold">ผลผลิตที่เลือก:</label>
                            <div class="p-3 bg-light rounded">
                                <span id="selectedOutputText"></span>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="output_details" class="form-label fw-bold">
                                รายละเอียดเพิ่มเติม <span class="text-danger">*</span>
                            </label>
                            <textarea class="form-control" id="output_details" name="output_details" rows="5"
                                placeholder="กรุณาระบุรายละเอียดเพิ่มเติมเกี่ยวกับผลผลิตนี้ เช่น เป้าหมาย จำนวน คุณภาพ ฯลฯ" required></textarea>
                            <div class="form-text">
                                <i class="fas fa-info-circle"></i> ระบุรายละเอียดที่เป็นประโยชน์สำหรับการประเมินผลผลิตนี้
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times"></i> ยกเลิก
                        </button>
                        <button type="button" class="btn btn-outline-primary" onclick="goBack()">
                            <i class="fas fa-arrow-left"></i> ย้อนกลับ
                        </button>
                        <button type="submit" class="btn btn-primary">
                            เสร็จสิ้น: ดูสรุป Impact Chain <i class="fas fa-check"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // เพิ่ม visual feedback เมื่อเลือก output
        document.querySelectorAll('input[type="radio"]').forEach(radio => {
            radio.addEventListener('change', function() {
                // ลบ selected class จาก card ทั้งหมด
                document.querySelectorAll('.output-card').forEach(card => {
                    card.classList.remove('border-primary', 'selected');
                });

                // เพิ่ม selected class ให้ card ที่เลือก
                const selectedCard = this.closest('.card');
                if (selectedCard) {
                    selectedCard.classList.add('border-primary', 'selected');
                }

                // แสดง modal พร้อมข้อมูลผลผลิตที่เลือก
                showOutputModal(this);
            });
        });

        // เพิ่มการคลิกที่ card เพื่อ select radio
        document.querySelectorAll('.output-card').forEach(card => {
            card.addEventListener('click', function(e) {
                // ป้องกันการคลิกซ้ำจาก radio button
                if (e.target.type !== 'radio') {
                    const radio = this.querySelector('input[type="radio"]');
                    if (radio) {
                        radio.checked = true;
                        radio.dispatchEvent(new Event('change'));
                    }
                }
            });
        });

        // ฟังก์ชันแสดง modal
        function showOutputModal(radioElement) {
            const outputId = radioElement.value;
            const outputDescription = radioElement.getAttribute('data-output-description');

            // เซ็ต ID ของผลผลิตที่เลือก
            document.getElementById('selected_output_id').value = outputId;

            // เซ็ตข้อความของผลผลิตที่เลือก
            document.getElementById('selectedOutputText').textContent = outputDescription;

            // แสดง modal
            const modal = new bootstrap.Modal(document.getElementById('outputModal'));
            modal.show();
        }

        // ฟังก์ชันย้อนกลับ
        function goBack() {
            window.location.href = 'step2-activity.php?project_id=<?php echo $project_id; ?>';
        }

        // ตรวจสอบการ submit form
        document.getElementById('outputForm').addEventListener('submit', function(e) {
            const outputDetails = document.getElementById('output_details').value.trim();
            if (outputDetails === '') {
                e.preventDefault();
                alert('กรุณากรอกรายละเอียดเพิ่มเติม');
                return false;
            }
        });
    </script>
</body>

</html>