<?php
// ดึงรายการโครงการ
$projects = getUserProjects($conn, $user_id);
$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (count($projects) > 0 ? $projects[0]['id'] : 0);
$selected_project = $selected_project_id ? getProjectById($conn, $selected_project_id, $user_id) : null;
?>

<div class="controls">
    <div class="control-group">
        <label>โครงการที่เลือก:</label>
        <?php if ($selected_project): ?>
            <div class="project-display">
                <strong><?php echo htmlspecialchars($selected_project['project_code'] . ' - ' . $selected_project['name']); ?></strong>
            </div>
        <?php else: ?>
            <div class="project-display">
                <em>ไม่พบโครงการ</em>
            </div>
        <?php endif; ?>
        <button class="btn" onclick="generateReport()">สร้างรายงาน</button>
    </div>
</div>

<?php if ($selected_project): ?>
<div class="project-info">
    <div class="info-grid">
        <div class="info-item">
            <label>รหัสโครงการ:</label>
            <span><?php echo htmlspecialchars($selected_project['project_code']); ?></span>
        </div>
        <div class="info-item">
            <label>ชื่อโครงการ:</label>
            <span><?php echo htmlspecialchars($selected_project['name']); ?></span>
        </div>
        <div class="info-item">
            <label>งบประมาณ:</label>
            <span><?php echo formatCurrency($selected_project['budget']); ?></span>
        </div>
        <div class="info-item">
            <label>สถานะ:</label>
            <span><?php echo $selected_project['status'] == 'completed' ? 'เสร็จสิ้น' : 'ยังไม่เสร็จ'; ?></span>
        </div>
        <div class="info-item">
            <label>วันที่สร้าง:</label>
            <span><?php echo formatThaiDate($selected_project['created_at']); ?></span>
        </div>
        <div class="info-item">
            <label>อัปเดตล่าสุด:</label>
            <span><?php echo formatThaiDate($selected_project['updated_at']); ?></span>
        </div>
    </div>
</div>
<?php endif; ?>