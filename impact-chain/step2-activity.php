<?php
session_start();
require_once '../config.php';
require_once '../includes/progress_bar.php';

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

// ตรวจสอบว่าได้เลือกยุทธศาสตร์แล้วหรือยัง
$selected_strategies = [];
$strategy_query = "SELECT ps.strategy_id, s.strategy_name 
                   FROM project_strategies ps 
                   JOIN strategies s ON ps.strategy_id = s.strategy_id 
                   WHERE ps.project_id = ?";
$strategy_stmt = mysqli_prepare($conn, $strategy_query);
mysqli_stmt_bind_param($strategy_stmt, 'i', $project_id);
mysqli_stmt_execute($strategy_stmt);
$strategy_result = mysqli_stmt_get_result($strategy_stmt);

if (mysqli_num_rows($strategy_result) > 0) {
    while ($strategy = mysqli_fetch_assoc($strategy_result)) {
        $selected_strategies[] = $strategy;
    }
} else {
    $_SESSION['error_message'] = "กรุณาเลือกยุทธศาสตร์ก่อน";
    header("location: step1-strategy.php?project_id=" . $project_id);
    exit;
}
mysqli_stmt_close($strategy_stmt);

if (empty($selected_strategies)) {
    $_SESSION['error_message'] = "กรุณาเลือกยุทธศาสตร์ก่อน";
    header("location: step1-strategy.php?project_id=" . $project_id);
    exit;
}

// ดึงกิจกรรมที่เกี่ยวข้องกับยุทธศาสตร์ที่เลือก (ทั้ง level 1 และ 2)
$strategy_ids = array_column($selected_strategies, 'strategy_id');
$activities = [];

if (!empty($strategy_ids)) {
    $strategy_ids_str = implode(',', array_map('intval', $strategy_ids));

    $activities_query = "SELECT a.*, s.strategy_name 
                         FROM activities a 
                         JOIN strategies s ON a.strategy_id = s.strategy_id 
                         WHERE a.strategy_id IN ($strategy_ids_str) 
                         ORDER BY a.activity_code ASC";
    $activities_result = mysqli_query($conn, $activities_query);
    $activities = mysqli_fetch_all($activities_result, MYSQLI_ASSOC);
}

// ดึงกิจกรรมที่เลือกไว้แล้วจากฐานข้อมูล
$selected_activities = [];
$selected_activity_ids = [];
$selected_activity_query = "SELECT pa.activity_id, a.activity_name, a.activity_code 
                           FROM project_activities pa 
                           JOIN activities a ON pa.activity_id = a.activity_id 
                           WHERE pa.project_id = ?";
$selected_activity_stmt = mysqli_prepare($conn, $selected_activity_query);
mysqli_stmt_bind_param($selected_activity_stmt, 'i', $project_id);
mysqli_stmt_execute($selected_activity_stmt);
$selected_activity_result = mysqli_stmt_get_result($selected_activity_stmt);

while ($activity = mysqli_fetch_assoc($selected_activity_result)) {
    $selected_activities[] = $activity;
    $selected_activity_ids[] = $activity['activity_id'];
}
mysqli_stmt_close($selected_activity_stmt);

// จัดกลุ่มกิจกรรมตามยุทธศาสตร์และระดับ (ใช้ activity_code แยกระดับ)
$activities_by_strategy = [];
$level1_activities = [];
$level2_activities = [];

foreach ($activities as $activity) {
    // แยกระดับจาก activity_code (เช่น "1", "2", "2.1", "2.2" เป็นต้น)
    $code_parts = explode('.', $activity['activity_code']);
    if (count($code_parts) == 1) {
        // Level 1 activities (รหัสไม่มีจุด เช่น "1", "2", "3")
        $level1_activities[$activity['strategy_name']][] = $activity;
    } else {
        // Level 2 activities (รหัสมีจุด เช่น "2.1", "2.2", "3.1")
        $parent_code = $code_parts[0]; // เอาเฉพาะตัวเลขหน้าจุด
        $level2_activities[$parent_code][] = $activity;
    }
}

// Debug: ตรวจสอบข้อมูล
// echo "<pre>Selected Strategies: "; print_r($selected_strategies); echo "</pre>";
// echo "<pre>All Activities: "; print_r($activities); echo "</pre>";
// echo "<pre>Level 1 Activities: "; print_r($level1_activities); echo "</pre>";
// echo "<pre>Level 2 Activities: "; print_r($level2_activities); echo "</pre>";
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Step 2: เลือกกิจกรรม - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .card {
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }

        .ms-4 {
            border-left: 3px solid #dee2e6;
            padding-left: 1rem;
        }

        .badge {
            font-size: 0.7em;
        }

        .card-body.py-2 {
            padding-top: 0.5rem !important;
            padding-bottom: 0.5rem !important;
        }

        .activity-card {
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .activity-card:hover {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.05);
        }

        .activity-card.selected {
            border-color: #0d6efd !important;
            background-color: rgba(13, 110, 253, 0.1);
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
        }

        .activity-radio {
            transform: scale(1.2);
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
                        <li class="breadcrumb-item active">Step 2: กิจกรรม</li>
                    </ol>
                </nav>
                <h2>สร้าง Impact Chain: <?php echo htmlspecialchars($project['name']); ?></h2>
            </div>
        </div>

        <!-- Progress Steps -->
        <?php 
        $status = getImpactChainStatus($project_id);
        renderImpactChainProgressBar($project_id, 2, $status); 
        ?>

        <!-- Selected Strategies Info -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-info">
                    <h6><i class="fas fa-info-circle"></i> ยุทธศาสตร์ที่เลือกไว้:</h6>
                    <div class="mb-0">
                        <?php foreach ($selected_strategies as $strategy): ?>
                            <div><?php echo htmlspecialchars($strategy['strategy_id']); ?>. <?php echo htmlspecialchars($strategy['strategy_name']); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5><i class="fas fa-tasks"></i> กิจกรรมที่เกี่ยวข้องกับยุทธศาสตร์</h5>
                        <small class="text-muted">รายการกิจกรรมที่สามารถดำเนินการได้จากยุทธศาสตร์ที่เลือก</small>

                        <?php if (!empty($selected_activities)): ?>
                            <div class="mt-2">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle"></i>
                                    <strong>กิจกรรมปัจจุบัน:</strong>
                                    <?php echo htmlspecialchars($selected_activities[0]['activity_name']); ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <?php if (empty($level1_activities)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> ไม่พบข้อมูลกิจกรรมที่เกี่ยวข้องกับยุทธศาสตร์ที่เลือก
                            </div>
                            <div class="d-flex justify-content-between">
                                <a href="step1-strategy.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                </a>
                            </div>
                        <?php else: ?>
                            <form id="activityForm" method="POST" action="process-step2.php">
                                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                                <!-- คำแนะนำ -->
                                <div class="alert alert-light border-primary">
                                    <i class="fas fa-lightbulb text-warning"></i>
                                    <strong>คำแนะนำ:</strong> เลือกกิจกรรมที่จะดำเนินการในโครงการนี้ (เลือกได้เพียง 1 กิจกรรม)
                                </div>

                                <?php foreach ($level1_activities as $strategy_name => $strategy_activities): ?>
                                    <div class="mb-4">
                                        <h6 class="text-primary border-bottom pb-2">
                                            <i class="fas fa-bullseye"></i> <?php echo htmlspecialchars($strategy_name); ?>
                                        </h6>

                                        <?php $activity_counter = 1; ?>
                                        <?php foreach ($strategy_activities as $main_activity): ?>
                                            <!-- กิจกรรมหลัก (Level 1) -->
                                            <div class="mb-3">
                                                <div class="card border-info activity-card <?php echo in_array($main_activity['activity_id'], $selected_activity_ids) ? 'selected' : ''; ?>"
                                                    onclick="selectActivity('<?php echo $main_activity['activity_id']; ?>')">
                                                    <div class="card-body">
                                                        <div class="d-flex align-items-start">
                                                            <input type="radio"
                                                                class="form-check-input activity-radio me-3 mt-1"
                                                                name="selected_activity"
                                                                value="<?php echo $main_activity['activity_id']; ?>"
                                                                id="activity_<?php echo $main_activity['activity_id']; ?>"
                                                                <?php echo in_array($main_activity['activity_id'], $selected_activity_ids) ? 'checked' : ''; ?>>
                                                            <div class="flex-grow-1">
                                                                <h6 class="card-title text-info mb-1">
                                                                    <i class="fas fa-layer-group"></i> <?php echo $main_activity['activity_code']; ?>. <?php echo htmlspecialchars($main_activity['activity_name']); ?>
                                                                </h6>
                                                                <?php if (!empty($main_activity['activity_description'])): ?>
                                                                    <p class="card-text text-muted small mb-0">
                                                                        <?php echo htmlspecialchars($main_activity['activity_description']); ?>
                                                                    </p>
                                                                <?php endif; ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- กิจกรรมย่อย (Level 2) -->
                                                <?php if (isset($level2_activities[$main_activity['activity_code']])): ?>
                                                    <div class="ms-4 mt-2">
                                                        <h6 class="text-secondary mb-2">
                                                            <i class="fas fa-list"></i> กิจกรรมย่อย:
                                                        </h6>
                                                        <div class="row">
                                                            <?php
                                                            $sub_counter = 1;
                                                            foreach ($level2_activities[$main_activity['activity_code']] as $sub_activity):
                                                            ?>
                                                                <div class="col-md-6 mb-2">
                                                                    <div class="card border-secondary activity-card <?php echo in_array($sub_activity['activity_id'], $selected_activity_ids) ? 'selected' : ''; ?>"
                                                                        onclick="selectActivity('<?php echo $sub_activity['activity_id']; ?>')">
                                                                        <div class="card-body py-2">
                                                                            <div class="d-flex align-items-start">
                                                                                <input type="radio"
                                                                                    class="form-check-input activity-radio me-2 mt-0"
                                                                                    name="selected_activity"
                                                                                    value="<?php echo $sub_activity['activity_id']; ?>"
                                                                                    id="activity_<?php echo $sub_activity['activity_id']; ?>"
                                                                                    <?php echo in_array($sub_activity['activity_id'], $selected_activity_ids) ? 'checked' : ''; ?>>
                                                                                <div class="flex-grow-1">
                                                                                    <h6 class="card-title text-secondary mb-1" style="font-size: 0.9rem;">
                                                                                        <i class="fas fa-arrow-right"></i> <?php echo $sub_activity['activity_code']; ?>. <?php echo htmlspecialchars($sub_activity['activity_name']); ?>
                                                                                    </h6>
                                                                                    <?php if (!empty($sub_activity['activity_description'])): ?>
                                                                                        <p class="card-text text-muted mb-0" style="font-size: 0.75rem;">
                                                                                            <?php echo htmlspecialchars($sub_activity['activity_description']); ?>
                                                                                        </p>
                                                                                    <?php endif; ?>
                                                                                </div>
                                                                            </div>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            <?php
                                                                $sub_counter++;
                                                            endforeach;
                                                            ?>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <?php $activity_counter++; ?>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endforeach; ?>

                                <!-- Selected Activity Summary -->
                                <div id="selectedSummary" class="alert alert-success d-none mt-4">
                                    <h6><i class="fas fa-check-circle"></i> กิจกรรมที่เลือก:</h6>
                                    <div id="selectedActivityName"></div>
                                </div>

                                <!-- Action Buttons -->
                                <div class="d-flex justify-content-between mt-4">
                                    <a href="step1-strategy.php?project_id=<?php echo $project_id; ?>" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left"></i> ย้อนกลับ
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="nextBtn" disabled>
                                        ถัดไป: เลือกผลผลิต <i class="fas fa-arrow-right"></i>
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
        // กิจกรรมทั้งหมดใน JavaScript
        const activities = <?php echo json_encode($activities); ?>;

        function selectActivity(activityId) {
            // เลือก radio button
            document.getElementById('activity_' + activityId).checked = true;

            // อัปเดต UI
            updateUI();
        }

        function updateUI() {
            const selectedRadio = document.querySelector('input[name="selected_activity"]:checked');
            const nextBtn = document.getElementById('nextBtn');
            const summary = document.getElementById('selectedSummary');
            const summaryName = document.getElementById('selectedActivityName');

            // ลบ class selected จากทุก card
            document.querySelectorAll('.activity-card').forEach(card => {
                card.classList.remove('selected');
            });

            if (selectedRadio) {
                // เพิ่ม class selected ให้ card ที่เลือก
                const selectedCard = selectedRadio.closest('.activity-card');
                selectedCard.classList.add('selected');

                // แสดงชื่อกิจกรรมที่เลือก
                const selectedActivity = activities.find(activity => activity.activity_id === selectedRadio.value);
                if (selectedActivity) {
                    summaryName.textContent = selectedActivity.activity_name;
                    summary.classList.remove('d-none');
                }

                // เปิดใช้งานปุ่มถัดไป
                nextBtn.disabled = false;
            } else {
                // ซ่อน summary และปิดใช้งานปุ่มถัดไป
                summary.classList.add('d-none');
                nextBtn.disabled = true;
            }
        }

        // Event listeners
        document.addEventListener('DOMContentLoaded', function() {
            // เพิ่ม event listener ให้ radio buttons
            document.querySelectorAll('input[name="selected_activity"]').forEach(radio => {
                radio.addEventListener('change', updateUI);
            });

            // อัปเดต UI เริ่มต้น
            updateUI();

            // ตรวจสอบการ submit form
            document.getElementById('activityForm').addEventListener('submit', function(e) {
                const selectedRadio = document.querySelector('input[name="selected_activity"]:checked');
                if (!selectedRadio) {
                    e.preventDefault();
                    alert('กรุณาเลือกกิจกรรมก่อนดำเนินการต่อ');
                }
            });
        });
    </script>
</body>

</html>