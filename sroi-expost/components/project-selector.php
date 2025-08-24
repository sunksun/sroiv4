<?php
// ดึงรายการโครงการ
$projects = getUserProjects($conn, $user_id);
$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (count($projects) > 0 ? $projects[0]['id'] : 0);
$selected_project = $selected_project_id ? getProjectById($conn, $selected_project_id, $user_id) : null;
?>

<div class="controls">
    <div class="control-group">
        <?php if ($selected_project): ?>
            <div class="project-display">
                <strong><?php echo htmlspecialchars($selected_project['project_code'] . ' - ' . $selected_project['name']); ?></strong>
            </div>
        <?php else: ?>
            <div class="project-display">
                <em>ไม่พบโครงการ</em>
            </div>
        <?php endif; ?>
        <div class="button-group">
            <button class="btn btn-secondary" onclick="goToDashboard()">
                <i class="fas fa-arrow-left"></i> กลับไปหน้า Dashboard
            </button>
            <button class="btn btn-primary" onclick="generateReport()">
                <i class="fas fa-chart-bar"></i> สร้างรายงาน
            </button>
            <button class="btn btn-success" onclick="exportToExcel()">
                <i class="fas fa-file-excel"></i> ส่งออก Excel
            </button>
        </div>
    </div>
</div>