<?php
// SROI Ex-post Analysis - Step 1: Project Information
require_once 'includes/config.php';
require_once 'includes/functions.php';

include 'components/header.php';

// Get project data
$projects = getUserProjects($conn, $user_id);
$selected_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : (count($projects) > 0 ? $projects[0]['id'] : 0);
$selected_project = $selected_project_id ? getProjectById($conn, $selected_project_id, $user_id) : null;
?>

<div class="controls">
    <h2 style="color: #667eea; margin-bottom: 20px;">เลือกโครงการและปีที่ต้องการวิเคราะห์</h2>
    <div class="control-group">
        <label>เลือกโครงการ:</label>
        <select id="project-select" onchange="selectProject(this.value)">
            <option value="">-- เลือกโครงการ --</option>
            <?php foreach ($projects as $project): ?>
                <option value="<?php echo $project['id']; ?>" 
                        <?php echo $selected_project_id == $project['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="control-group">
        <label>ปีที่ประเมิน:</label>
        <select id="year-select">
            <option value="2567">2567</option>
            <option value="2568" selected>2568</option>
            <option value="2569">2569</option>
            <option value="2570">2570</option>
        </select>
    </div>
    <div class="control-group">
        <label>อัตราคิดลด (%):</label>
        <input type="number" id="discount-input" value="2.0" min="0" max="20" step="0.1">
    </div>
    <button class="btn" onclick="updateAnalysis()">อัปเดตการวิเคราะห์</button>
    
    <div style="margin-top: 20px; text-align: center;">
        <a href="step2-costs.php?project_id=<?php echo $selected_project_id; ?>" class="btn">ไปขั้นตอนถัดไป: ต้นทุนโครงการ</a>
    </div>
</div>

<?php if ($selected_project): ?>
<div class="project-info">
    <div class="info-grid">
        <div class="info-item">
            <label>รหัสโครงการ:</label>
            <span id="project-code"><?php echo htmlspecialchars($selected_project['project_code']); ?></span>
        </div>
        <div class="info-item">
            <label>ชื่อโครงการ:</label>
            <span id="project-name"><?php echo htmlspecialchars($selected_project['name']); ?></span>
        </div>
        <div class="info-item">
            <label>หน่วยงาน:</label>
            <span id="organization">คณะวิทยาศาสตร์และเทคโนโลยี</span>
        </div>
        <div class="info-item">
            <label>ผู้จัดการโครงการ:</label>
            <span id="manager"><?php echo htmlspecialchars($username); ?></span>
        </div>
        <div class="info-item">
            <label>งบประมาณ:</label>
            <span id="budget"><?php echo formatCurrency($selected_project['budget']); ?></span>
        </div>
        <div class="info-item">
            <label>ปีที่ประเมิน:</label>
            <span id="evaluation-year">2568</span>
        </div>
    </div>
</div>

<!-- Settings -->
<div class="settings-section">
    <div class="settings-header">
        <h2>การตั้งค่าการคำนวณ</h2>
        <div class="discount-rate">
            <span>อัตราคิดลด: </span>
            <strong id="discount-rate">2.0%</strong>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function selectProject(projectId) {
    if (projectId) {
        window.location.href = `?project_id=${projectId}`;
    }
}

function updateAnalysis() {
    const projectId = document.getElementById('project-select').value;
    const year = document.getElementById('year-select').value;
    const discount = document.getElementById('discount-input').value;
    
    // อัพเดตการแสดงผล
    document.getElementById('discount-rate').textContent = discount + '%';
    document.getElementById('evaluation-year').textContent = year;
    
    console.log('Updated:', { projectId, year, discount });
}
</script>

<?php include 'components/footer.php'; ?>