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
            padding-top: 80px;
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
    <?php include 'navbar.php'; ?>
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

                <!-- Impact Chain Information -->
                <?php
                // ดึงข้อมูล Impact Chain
                $strategies_query = "SELECT ps.*, s.strategy_name 
                                   FROM project_strategies ps 
                                   JOIN strategies s ON ps.strategy_id = s.strategy_id 
                                   WHERE ps.project_id = ?";
                $strategies_stmt = mysqli_prepare($conn, $strategies_query);
                mysqli_stmt_bind_param($strategies_stmt, 'i', $project_id);
                mysqli_stmt_execute($strategies_stmt);
                $strategies_result = mysqli_stmt_get_result($strategies_stmt);
                $strategies = mysqli_fetch_all($strategies_result, MYSQLI_ASSOC);
                mysqli_stmt_close($strategies_stmt);

                $activities_query = "SELECT pa.*, a.activity_name 
                                   FROM project_activities pa 
                                   JOIN activities a ON pa.activity_id = a.activity_id 
                                   WHERE pa.project_id = ?";
                $activities_stmt = mysqli_prepare($conn, $activities_query);
                mysqli_stmt_bind_param($activities_stmt, 'i', $project_id);
                mysqli_stmt_execute($activities_stmt);
                $activities_result = mysqli_stmt_get_result($activities_stmt);
                $activities = mysqli_fetch_all($activities_result, MYSQLI_ASSOC);
                mysqli_stmt_close($activities_stmt);

                $outputs_query = "SELECT po.*, o.output_description as output_name 
                                FROM project_outputs po 
                                JOIN outputs o ON po.output_id = o.output_id 
                                WHERE po.project_id = ?";
                $outputs_stmt = mysqli_prepare($conn, $outputs_query);
                mysqli_stmt_bind_param($outputs_stmt, 'i', $project_id);
                mysqli_stmt_execute($outputs_stmt);
                $outputs_result = mysqli_stmt_get_result($outputs_stmt);
                $outputs = mysqli_fetch_all($outputs_result, MYSQLI_ASSOC);
                mysqli_stmt_close($outputs_stmt);

                $outcomes_query = "SELECT po.*, o.outcome_description as outcome_name 
                                 FROM project_outcomes po 
                                 JOIN outcomes o ON po.outcome_id = o.outcome_id 
                                 WHERE po.project_id = ?";
                $outcomes_stmt = mysqli_prepare($conn, $outcomes_query);
                mysqli_stmt_bind_param($outcomes_stmt, 'i', $project_id);
                mysqli_stmt_execute($outcomes_stmt);
                $outcomes_result = mysqli_stmt_get_result($outcomes_stmt);
                $outcomes = mysqli_fetch_all($outcomes_result, MYSQLI_ASSOC);
                mysqli_stmt_close($outcomes_stmt);
                ?>

                <?php if (!empty($strategies) || !empty($activities) || !empty($outputs) || !empty($outcomes)): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="fas fa-link text-primary"></i> Impact Chain</h5>
                        
                        <?php if (!empty($strategies)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary"><i class="fas fa-chess-king"></i> กลยุทธ์ที่เลือก</h6>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($strategies as $strategy): ?>
                                        <div class="list-group-item border-0 ps-0">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <?php echo htmlspecialchars($strategy['strategy_name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($activities)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary"><i class="fas fa-tasks"></i> กิจกรรมที่เลือก</h6>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($activities as $activity): ?>
                                        <div class="list-group-item border-0 ps-0">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <?php echo htmlspecialchars($activity['activity_name']); ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($outputs)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary"><i class="fas fa-box-open"></i> ผลผลิตที่เลือก</h6>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($outputs as $output): ?>
                                        <div class="list-group-item border-0 ps-0">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <strong><?php echo htmlspecialchars($output['output_name']); ?></strong>
                                            <?php if (!empty($output['output_details'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($output['output_details']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($outcomes)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary"><i class="fas fa-trophy"></i> ผลลัพธ์ที่เลือก</h6>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($outcomes as $outcome): ?>
                                        <div class="list-group-item border-0 ps-0">
                                            <i class="fas fa-check-circle text-success me-2"></i>
                                            <strong><?php echo htmlspecialchars($outcome['outcome_name']); ?></strong>
                                            <?php if (!empty($outcome['outcome_details'])): ?>
                                                <br><small class="text-muted"><?php echo htmlspecialchars($outcome['outcome_details']); ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
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

                <!-- Impact Pathway Analysis -->
                <?php
                // ดึงข้อมูล Impact Pathway
                $costs_query = "SELECT * FROM project_costs WHERE project_id = ?";
                $costs_stmt = mysqli_prepare($conn, $costs_query);
                mysqli_stmt_bind_param($costs_stmt, 'i', $project_id);
                mysqli_stmt_execute($costs_stmt);
                $costs_result = mysqli_stmt_get_result($costs_stmt);
                $costs = mysqli_fetch_all($costs_result, MYSQLI_ASSOC);
                mysqli_stmt_close($costs_stmt);

                $impact_ratios_query = "SELECT * FROM project_impact_ratios WHERE project_id = ?";
                $impact_ratios_stmt = mysqli_prepare($conn, $impact_ratios_query);
                mysqli_stmt_bind_param($impact_ratios_stmt, 'i', $project_id);
                mysqli_stmt_execute($impact_ratios_stmt);
                $impact_ratios_result = mysqli_stmt_get_result($impact_ratios_stmt);
                $impact_ratios = mysqli_fetch_all($impact_ratios_result, MYSQLI_ASSOC);
                mysqli_stmt_close($impact_ratios_stmt);

                $with_without_query = "SELECT * FROM project_with_without WHERE project_id = ?";
                $with_without_stmt = mysqli_prepare($conn, $with_without_query);
                mysqli_stmt_bind_param($with_without_stmt, 'i', $project_id);
                mysqli_stmt_execute($with_without_stmt);
                $with_without_result = mysqli_stmt_get_result($with_without_stmt);
                $with_without = mysqli_fetch_all($with_without_result, MYSQLI_ASSOC);
                mysqli_stmt_close($with_without_stmt);
                ?>

                <?php if (!empty($costs) || !empty($impact_ratios) || !empty($with_without)): ?>
                    <div class="detail-section">
                        <h5 class="mb-3"><i class="fas fa-chart-line text-primary"></i> Impact Pathway Analysis</h5>
                        
                        <?php if (!empty($costs)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary"><i class="fas fa-money-bill-wave"></i> ต้นทุนโครงการ</h6>
                                <div class="list-group list-group-flush">
                                    <?php foreach ($costs as $cost): ?>
                                        <div class="list-group-item border-0 ps-0">
                                            <strong><?php echo htmlspecialchars($cost['cost_name']); ?></strong>
                                            <?php if (!empty($cost['yearly_amounts'])): ?>
                                                <br><small class="text-muted">
                                                    จำนวนเงินรายปี: <?php echo htmlspecialchars($cost['yearly_amounts']); ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($impact_ratios)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary"><i class="fas fa-calculator"></i> อัตราส่วนผลกระทบ</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>ผู้รับประโยชน์</th>
                                                <th>Attribution</th>
                                                <th>Deadweight</th>
                                                <th>Displacement</th>
                                                <th>Impact Ratio</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($impact_ratios as $ratio): ?>
                                                <tr>
                                                    <td>
                                                        <?php if (!empty($ratio['beneficiary'])): ?>
                                                            <small><?php echo htmlspecialchars($ratio['beneficiary']); ?></small>
                                                        <?php else: ?>
                                                            <small class="text-muted">ไม่ระบุ</small>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo number_format($ratio['attribution'], 2); ?>%</td>
                                                    <td><?php echo number_format($ratio['deadweight'], 2); ?>%</td>
                                                    <td><?php echo number_format($ratio['displacement'], 2); ?>%</td>
                                                    <td><strong><?php echo number_format($ratio['impact_ratio'], 4); ?></strong></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($with_without)): ?>
                            <div class="mb-4">
                                <h6 class="text-primary"><i class="fas fa-balance-scale"></i> การวิเคราะห์ With-Without</h6>
                                <div class="alert alert-info">
                                    <small><i class="fas fa-info-circle"></i> พบข้อมูลการวิเคราะห์ With-Without จำนวน <?php echo count($with_without); ?> รายการ</small>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="text-center">
                            <a href="impact_pathway/impact_pathway.php?project_id=<?php echo $project['id']; ?>" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-chart-bar"></i> ดูรายละเอียด Impact Pathway
                            </a>
                        </div>
                    </div>
                <?php endif; ?>

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