<?php
session_start();
require_once '../config.php';

// ตรวจสอบการ login (ชั่วคราวไม่เช็ค admin role ในช่วงพัฒนา)
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// TODO: เพิ่มการเช็ค admin role ในอนาคต
// if ($_SESSION['role'] !== 'admin') { ... }

$message = '';
$error = '';

// ตรวจสอบการ submit
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';
    
    try {
        mysqli_autocommit($conn, false);
        
        switch ($action) {
            case 'reset_project_data':
                // ลบข้อมูล Impact Chain ของโครงการที่เลือก
                $project_id = (int)$_POST['project_id'];
                
                if ($project_id > 0) {
                    // ลบข้อมูลตารางเดิม
                    mysqli_query($conn, "DELETE FROM project_strategies WHERE project_id = $project_id");
                    mysqli_query($conn, "DELETE FROM project_activities WHERE project_id = $project_id");
                    mysqli_query($conn, "DELETE FROM project_outputs WHERE project_id = $project_id");
                    mysqli_query($conn, "DELETE FROM project_outcomes WHERE project_id = $project_id");
                    mysqli_query($conn, "DELETE FROM project_impact_ratios WHERE project_id = $project_id");
                    
                    // ลบข้อมูลตารางใหม่
                    mysqli_query($conn, "DELETE FROM impact_chain_activities WHERE impact_chain_id IN (SELECT id FROM impact_chains WHERE project_id = $project_id)");
                    mysqli_query($conn, "DELETE FROM impact_chain_outputs WHERE impact_chain_id IN (SELECT id FROM impact_chains WHERE project_id = $project_id)");
                    mysqli_query($conn, "DELETE FROM impact_chain_outcomes WHERE impact_chain_id IN (SELECT id FROM impact_chains WHERE project_id = $project_id)");
                    mysqli_query($conn, "DELETE FROM impact_chain_ratios WHERE impact_chain_id IN (SELECT id FROM impact_chains WHERE project_id = $project_id)");
                    mysqli_query($conn, "DELETE FROM impact_chains WHERE project_id = $project_id");
                    
                    // รีเซ็ตสถานะโครงการ
                    mysqli_query($conn, "UPDATE projects SET impact_chain_status = NULL, total_impact_chains = 0, current_chain_id = NULL WHERE id = $project_id");
                    
                    $message = "ลบข้อมูล Impact Chain ของโครงการ ID: $project_id เรียบร้อยแล้ว";
                }
                break;
                
            case 'reset_all_impact_chains':
                // ลบข้อมูล Impact Chain ทั้งหมด
                mysqli_query($conn, "DELETE FROM project_strategies");
                mysqli_query($conn, "DELETE FROM project_activities");
                mysqli_query($conn, "DELETE FROM project_outputs");
                mysqli_query($conn, "DELETE FROM project_outcomes");
                mysqli_query($conn, "DELETE FROM project_impact_ratios");
                
                mysqli_query($conn, "DELETE FROM impact_chain_activities");
                mysqli_query($conn, "DELETE FROM impact_chain_outputs");
                mysqli_query($conn, "DELETE FROM impact_chain_outcomes");
                mysqli_query($conn, "DELETE FROM impact_chain_ratios");
                mysqli_query($conn, "DELETE FROM impact_chains");
                
                // รีเซ็ตสถานะทุกโครงการ
                mysqli_query($conn, "UPDATE projects SET impact_chain_status = NULL, total_impact_chains = 0, current_chain_id = NULL");
                
                $message = "ลบข้อมูล Impact Chain ทั้งหมดเรียบร้อยแล้ว";
                break;
                
                
            default:
                throw new Exception("Invalid action");
        }
        
        mysqli_commit($conn);
        
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = "เกิดข้อผิดพลาด: " . $e->getMessage();
    }
    
    mysqli_autocommit($conn, true);
}

// ดึงรายการโครงการ
$projects_query = "SELECT id, project_code, name, created_at FROM projects ORDER BY id DESC";
$projects_result = mysqli_query($conn, $projects_query);
$projects = mysqli_fetch_all($projects_result, MYSQLI_ASSOC);

// ดึงสถิติ
$stats = [];
$stats_queries = [
    'projects' => "SELECT COUNT(*) as count FROM projects",
    'impact_chains_old' => "SELECT COUNT(*) as count FROM project_activities",
    'impact_chains_new' => "SELECT COUNT(*) as count FROM impact_chains",
    'total_outputs' => "SELECT (SELECT COUNT(*) FROM project_outputs) + (SELECT COUNT(*) FROM impact_chain_outputs) as count",
    'total_outcomes' => "SELECT (SELECT COUNT(*) FROM project_outcomes) + (SELECT COUNT(*) FROM impact_chain_outcomes) as count"
];

foreach ($stats_queries as $key => $query) {
    $result = mysqli_query($conn, $query);
    $row = mysqli_fetch_assoc($result);
    $stats[$key] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset ข้อมูลระบบ - SROI Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .danger-zone {
            border: 2px solid #dc3545;
            background-color: #fff5f5;
        }
        .warning-zone {
            border: 2px solid #ffc107;
            background-color: #fffdf0;
        }
        .stats-card {
            border-left: 4px solid #007bff;
        }
        .reset-btn {
            min-width: 200px;
        }
    </style>
</head>

<body>
    <div class="container mt-4">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h1><i class="fas fa-tools text-danger"></i> Reset ข้อมูลระบบ SROI</h1>
                <p class="text-muted">หน้านี้สำหรับนักพัฒนาเท่านั้น - ใช้สำหรับ reset ข้อมูลระหว่างการทดสอบระบบ</p>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle"></i> 
                    <strong>คำเตือน:</strong> การกระทำในหน้านี้จะลบข้อมูลถาวร กรุณาใช้ความระมัดระวัง
                </div>
            </div>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card stats-card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-chart-bar"></i> สถิติข้อมูลปัจจุบัน</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <h3 class="text-primary"><?php echo $stats['projects']; ?></h3>
                                <small>โครงการทั้งหมด</small>
                            </div>
                            <div class="col-md-2">
                                <h3 class="text-info"><?php echo $stats['impact_chains_old']; ?></h3>
                                <small>Impact Chain เดิม</small>
                            </div>
                            <div class="col-md-2">
                                <h3 class="text-success"><?php echo $stats['impact_chains_new']; ?></h3>
                                <small>Impact Chain ใหม่</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-warning"><?php echo $stats['total_outputs']; ?></h3>
                                <small>ผลผลิตทั้งหมด</small>
                            </div>
                            <div class="col-md-3">
                                <h3 class="text-danger"><?php echo $stats['total_outcomes']; ?></h3>
                                <small>ผลลัพธ์ทั้งหมด</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reset Options -->
        <div class="row">
            <!-- Reset Single Project -->
            <div class="col-md-6 mb-4">
                <div class="card warning-zone">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0"><i class="fas fa-project-diagram"></i> Reset โครงการเดียว</h5>
                    </div>
                    <div class="card-body">
                        <p>ลบข้อมูล Impact Chain ของโครงการที่เลือก (ไม่ลบโครงการ)</p>
                        
                        <form method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบข้อมูล Impact Chain ของโครงการนี้?')">
                            <input type="hidden" name="action" value="reset_project_data">
                            
                            <div class="mb-3">
                                <label class="form-label">เลือกโครงการ:</label>
                                <select name="project_id" class="form-select" required>
                                    <option value="">-- เลือกโครงการ --</option>
                                    <?php foreach ($projects as $project): ?>
                                        <option value="<?php echo $project['id']; ?>">
                                            ID: <?php echo $project['id']; ?> - <?php echo htmlspecialchars($project['name']); ?>
                                            (<?php echo $project['project_code']; ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <button type="submit" class="btn btn-warning reset-btn">
                                <i class="fas fa-eraser"></i> Reset ข้อมูล Impact Chain
                            </button>
                        </form>
                        
                        <hr>
                        <small class="text-muted">
                            <strong>จะลบ:</strong> Impact Chain, Activities, Outputs, Outcomes, Ratios<br>
                            <strong>จะไม่ลบ:</strong> ข้อมูลโครงการหลัก
                        </small>
                    </div>
                </div>
            </div>

            <!-- Reset All Impact Chains -->
            <div class="col-md-6 mb-4">
                <div class="card danger-zone">
                    <div class="card-header bg-danger text-white">
                        <h5 class="mb-0"><i class="fas fa-link"></i> Reset Impact Chains ทั้งหมด</h5>
                    </div>
                    <div class="card-body">
                        <p>ลบข้อมูล Impact Chain ทั้งหมดในระบบ (ไม่ลบโครงการ)</p>
                        
                        <form method="POST" onsubmit="return confirm('คุณแน่ใจหรือไม่ที่จะลบ Impact Chain ทั้งหมด?\n\nการกระทำนี้ไม่สามารถย้อนกลับได้!')">
                            <input type="hidden" name="action" value="reset_all_impact_chains">
                            
                            <button type="submit" class="btn btn-danger reset-btn">
                                <i class="fas fa-trash-alt"></i> ลบ Impact Chain ทั้งหมด
                            </button>
                        </form>
                        
                        <hr>
                        <small class="text-muted">
                            <strong>จะลบ:</strong> Impact Chain ทุกตาราง ของทุกโครงการ<br>
                            <strong>จะไม่ลบ:</strong> ข้อมูลโครงการหลัก
                        </small>
                    </div>
                </div>
            </div>
        </div>


        <!-- Quick Links -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h6 class="mb-3"><i class="fas fa-link"></i> เครื่องมือเพิ่มเติม</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <a href="../test-status-system.php?project_id=1" class="btn btn-outline-info" target="_blank">
                                <i class="fas fa-bug"></i> ทดสอบ Status System
                            </a>
                            <a href="../project-list.php" class="btn btn-outline-primary">
                                <i class="fas fa-list"></i> รายการโครงการ
                            </a>
                            <a href="../dashboard.php" class="btn btn-outline-success">
                                <i class="fas fa-home"></i> Dashboard
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary">
                                <i class="fas fa-cog"></i> Admin Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto refresh stats every 30 seconds
        setTimeout(function() {
            if (confirm('รีเฟรชสถิติหรือไม่?')) {
                location.reload();
            }
        }, 30000);
    </script>
</body>

</html>