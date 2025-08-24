<?php
session_start();
// เชื่อมต่อฐานข้อมูล
require_once '../config.php';

// ดึงข้อมูลโครงการ
$projects = [];
$selected_project = null;
$project_id = $_GET['project_id'] ?? null;

// ดึงรายการโครงการทั้งหมด
$query = "SELECT id, project_code, name, description, budget, organization, project_manager, 
                 start_date, end_date, YEAR(start_date) + 543 AS start_year_thai 
          FROM projects 
          ORDER BY created_at DESC";
$result = mysqli_query($conn, $query);
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $projects[] = $row;
    }
}

// ดึงข้อมูลโครงการที่เลือก
$project_not_found = false;
if ($project_id) {
    $query = "SELECT id, project_code, name, description, objective, budget, organization, 
                     project_manager, start_date, end_date, 
                     YEAR(start_date) + 543 AS start_year_thai,
                     YEAR(end_date) + 543 AS end_year_thai
              FROM projects 
              WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $project_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $selected_project = mysqli_fetch_assoc($result);
    
    // ตรวจสอบว่าพบโครงการหรือไม่
    if (!$selected_project) {
        $project_not_found = true;
        $project_id = null; // รีเซ็ต project_id
    }
}

// ตรวจสอบว่ามีการส่งข้อมูลผ่าน POST หรือไม่
$submitted = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submitted = true;
    
    // รับข้อมูลจากฟอร์ม
    $project_name = $_POST['project_name'] ?? '';
    $area = $_POST['area'] ?? '';
    $budget = $_POST['budget'] ?? '';
    $activities = $_POST['activities'] ?? '';
    $target_group = $_POST['target_group'] ?? '';
    $evaluation_project = $_POST['evaluation_project'] ?? '';
    $project_year = $_POST['project_year'] ?? '';
    $step1 = $_POST['step1'] ?? '';
    $step2 = $_POST['step2'] ?? '';
    $step3 = $_POST['step3'] ?? '';
    $analysis_project = $_POST['analysis_project'] ?? '';
    $impact_activities = $_POST['impact_activities'] ?? '';
    $social_impact = $_POST['social_impact'] ?? '';
    $economic_impact = $_POST['economic_impact'] ?? '';
    $environmental_impact = $_POST['environmental_impact'] ?? '';
    $evaluation_project2 = $_POST['evaluation_project2'] ?? '';
    $npv_value = $_POST['npv_value'] ?? '';
    $npv_status = $_POST['npv_status'] ?? '';
    $sroi_value = $_POST['sroi_value'] ?? '';
    $social_return = $_POST['social_return'] ?? '';
    $investment_status = $_POST['investment_status'] ?? '';
    $irr_value = $_POST['irr_value'] ?? '';
    $irr_compare = $_POST['irr_compare'] ?? '';
    $interview_project = $_POST['interview_project'] ?? '';
    $interviewees = $_POST['interviewees'] ?? '';
    $interview_count = $_POST['interview_count'] ?? '';
    $project_pathway = $_POST['project_pathway'] ?? '';
    $benefit_project = $_POST['benefit_project'] ?? '';
    $operation_year = $_POST['operation_year'] ?? '';
    $evaluation_project3 = $_POST['evaluation_project3'] ?? '';
    $evaluation_year = $_POST['evaluation_year'] ?? '';
    $sroi_final = $_POST['sroi_final'] ?? '';
    $sroi_compare = $_POST['sroi_compare'] ?? '';
    $npv_final = $_POST['npv_final'] ?? '';
    $npv_compare_final = $_POST['npv_compare_final'] ?? '';
    $irr_final = $_POST['irr_final'] ?? '';
    $irr_compare_final = $_POST['irr_compare_final'] ?? '';
    $investment_return = $_POST['investment_return'] ?? '';
    $investment_worthiness = $_POST['investment_worthiness'] ?? '';
    $reason = $_POST['reason'] ?? '';

    // รับข้อมูลตารางเปรียบเทียบ
    $with_scenarios = $_POST['with_scenario'] ?? [];
    $without_scenarios = $_POST['without_scenario'] ?? [];

    // รับข้อมูลตาราง Social Impact Pathway
    $pathway_input = $_POST['pathway_input'] ?? [];
    $pathway_activities = $_POST['pathway_activities'] ?? [];
    $pathway_output = $_POST['pathway_output'] ?? [];
    $pathway_user = $_POST['pathway_user'] ?? [];
    $pathway_outcome = $_POST['pathway_outcome'] ?? [];
    $pathway_indicator = $_POST['pathway_indicator'] ?? [];
    $pathway_financial = $_POST['pathway_financial'] ?? [];
    $pathway_source = $_POST['pathway_source'] ?? [];
    $pathway_impact = $_POST['pathway_impact'] ?? [];

    // รับข้อมูลตารางผลประโยชน์
    $benefit_item = $_POST['benefit_item'] ?? [];
    $benefit_calculated = $_POST['benefit_calculated'] ?? [];
    $benefit_attribution = $_POST['benefit_attribution'] ?? [];
    $benefit_deadweight = $_POST['benefit_deadweight'] ?? [];
    $benefit_displacement = $_POST['benefit_displacement'] ?? [];
    $benefit_impact = $_POST['benefit_impact'] ?? [];
    $benefit_category = $_POST['benefit_category'] ?? [];

    // รับข้อมูลตารางที่ 4 ผลการประเมิน SROI
    $sroi_impact = $_POST['sroi_impact'] ?? [];
    $sroi_npv = $_POST['sroi_npv'] ?? [];
    $sroi_ratio = $_POST['sroi_ratio'] ?? [];
    $sroi_irr = $_POST['sroi_irr'] ?? [];
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงานผลการประเมินผลตอบแทนทางสังคม (SROI)</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    
    <style>
                                body {
                                    font-family: 'Sarabun', Arial, sans-serif;
                                    line-height: 1.6;
                                    margin: 0;
                                    padding: 20px;
                                    background-color: #f5f5f5;
                                    padding-top: 80px;
                                }


                                .container {
                                    max-width: 1200px;
                                    margin: 0 auto;
                                    background: white;
                                    padding: 30px;
                                    border-radius: 10px;
                                    box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
                                }

                                .header {
                                    text-align: center;
                                    margin-bottom: 30px;
                                    padding: 20px;
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                    border-radius: 10px;
                                }

                                .form-group {
                                    margin-bottom: 20px;
                                }

                                label {
                                    display: block;
                                    margin-bottom: 5px;
                                    font-weight: bold;
                                    color: #333;
                                }

                                input[type="text"],
                                input[type="number"],
                                textarea,
                                select {
                                    width: 100%;
                                    padding: 10px;
                                    border: 2px solid #ddd;
                                    border-radius: 5px;
                                    font-size: 16px;
                                    transition: border-color 0.3s;
                                    box-sizing: border-box;
                                }

                                input[type="text"]:focus,
                                input[type="number"]:focus,
                                textarea:focus,
                                select:focus {
                                    outline: none;
                                    border-color: #667eea;
                                }

                                textarea {
                                    height: 80px;
                                    resize: vertical;
                                }

                                .btn {
                                    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                    color: white;
                                    padding: 12px 30px;
                                    border: none;
                                    border-radius: 5px;
                                    cursor: pointer;
                                    font-size: 16px;
                                    margin: 10px 5px;
                                    transition: transform 0.2s;
                                }

                                .btn:hover {
                                    transform: translateY(-2px);
                                }

                                .section {
                                    margin: 30px 0;
                                    padding: 20px;
                                    background: #f8f9fa;
                                    border-left: 4px solid #667eea;
                                    border-radius: 5px;
                                }

                                .section h3 {
                                    color: #667eea;
                                    margin-top: 0;
                                }

                                .two-column {
                                    display: grid;
                                    grid-template-columns: 1fr 1fr;
                                    gap: 20px;
                                }

                                .three-column {
                                    display: grid;
                                    grid-template-columns: 1fr 1fr 1fr;
                                    gap: 20px;
                                }

                                .report {
                                    background: white;
                                    border: 1px solid #ddd;
                                    padding: 30px;
                                    margin-top: 20px;
                                    border-radius: 10px;
                                    line-height: 1.8;
                                }

                                .report h2 {
                                    text-align: center;
                                    color: #333;
                                    margin-bottom: 30px;
                                    padding-bottom: 10px;
                                    border-bottom: 2px solid #667eea;
                                }

                                .highlight {
                                    background: #e3f2fd;
                                    padding: 3px 6px;
                                    border-radius: 3px;
                                    font-weight: bold;
                                }

                                @media (max-width: 768px) {

                                    .two-column,
                                    .three-column {
                                        grid-template-columns: 1fr;
                                    }
                                }
                            </style>
                        </head>

                        <body>
                            <?php 
                            // กำหนด root path สำหรับ navbar
                            $navbar_root = '../';
                            include '../navbar.php'; 
                            ?>
                            <div class="container">
                                <div class="header">
                                    <h1>รายงานผลการประเมินผลตอบแทนทางสังคม</h1>
                                    <h2>(Social Return On Investment : SROI)</h2>
                                </div>

                                <?php if ($project_not_found): ?>
                                    <div style="margin: 20px 0; padding: 15px; background: #f8d7da; border: 1px solid #f5c6cb; border-radius: 5px; color: #721c24;">
                                        <strong>ไม่พบโครงการ!</strong> โครงการที่คุณต้องการดูไม่มีอยู่ในระบบ กรุณาเลือกโครงการใหม่
                                    </div>
                                <?php endif; ?>

                                <?php if (!$submitted): ?>
                                    <form method="POST" action="<?php echo $project_id ? '?project_id='.$project_id : ''; ?>">
                                        <?php if ($project_id): ?>
                                            <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">
                                        <?php endif; ?>
                                        <div class="section">
                                            <h3>ข้อมูลทั่วไปของโครงการ</h3>
                                            

                                            <div class="two-column">
                                                <div class="form-group">
                                                    <label for="project_name">ชื่อโครงการ:</label>
                                                    <input type="text" id="project_name" name="project_name" 
                                                           value="<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="area">พื้นที่ดำเนินการ / หน่วยงาน:</label>
                                                    <input type="text" id="area" name="area" 
                                                           value="<?php echo $selected_project ? htmlspecialchars($selected_project['organization']) : ''; ?>" required>
                                                </div>
                                            </div>
                                            <div class="two-column">
                                                <div class="form-group">
                                                    <label for="budget">งบประมาณ (บาท):</label>
                                                    <input type="number" id="budget" name="budget" 
                                                           value="<?php echo $selected_project ? $selected_project['budget'] : ''; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="activities">ผู้จัดการโครงการ:</label>
                                                    <input type="text" id="activities" name="activities" 
                                                           value="<?php echo $selected_project ? htmlspecialchars($selected_project['project_manager']) : ''; ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="target_group">รายละเอียดโครงการ / วัตถุประสงค์:</label>
                                                <textarea id="target_group" name="target_group" required><?php 
                                                    echo $selected_project ? htmlspecialchars($selected_project['description'] ?? $selected_project['objective'] ?? '') : ''; 
                                                ?></textarea>
                                            </div>
                                            
                                            <?php if ($selected_project): ?>
                                            <div class="two-column">
                                                <div class="form-group">
                                                    <label>รหัสโครงการ:</label>
                                                    <input type="text" value="<?php echo htmlspecialchars($selected_project['project_code']); ?>" readonly style="background: #f8f9fa;">
                                                </div>
                                                <div class="form-group">
                                                    <label>ปีที่ดำเนินการ:</label>
                                                    <input type="text" value="พ.ศ. <?php echo $selected_project['start_year_thai'] ?? ''; ?>" readonly style="background: #f8f9fa;">
                                                </div>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="section">
                                            <h3>การประเมินผลตอบแทนทางสังคม</h3>
                                            <div class="two-column">
                                                <div class="form-group">
                                                    <label for="evaluation_project">ชื่อโครงการที่ประเมิน:</label>
                                                    <input type="text" id="evaluation_project" name="evaluation_project" 
                                                           value="<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="project_year">ปีที่ดำเนินการ (พ.ศ.):</label>
                                                    <input type="number" id="project_year" name="project_year" 
                                                           value="<?php echo $selected_project ? $selected_project['start_year_thai'] : ''; ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="step1">ขั้นตอนที่ 1:</label>
                                                <input type="text" id="step1" name="step1" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="step2">ขั้นตอนที่ 2:</label>
                                                <input type="text" id="step2" name="step2" required>
                                            </div>
                                            <div class="form-group">
                                                <label for="step3">ขั้นตอนที่ 3:</label>
                                                <input type="text" id="step3" name="step3" required>
                                            </div>
                                        </div>

                                        <div class="section">
                                            <h3>การเปลี่ยนแปลงในมิติทางสังคม</h3>
                                            <div class="two-column">
                                                <div class="form-group">
                                                    <label for="analysis_project">ชื่อโครงการที่วิเคราะห์:</label>
                                                    <input type="text" id="analysis_project" name="analysis_project" 
                                                           value="<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="impact_activities">กิจกรรมที่ก่อให้เกิดผลกระทบ:</label>
                                                    <input type="text" id="impact_activities" name="impact_activities" 
                                                           value="<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>" required>
                                                </div>
                                            </div>
                                            <div class="form-group">
                                                <label for="social_impact">ผลกระทบด้านสังคม:</label>
                                                <textarea id="social_impact" name="social_impact" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="economic_impact">ผลกระทบด้านเศรษฐกิจ:</label>
                                                <textarea id="economic_impact" name="economic_impact" required></textarea>
                                            </div>
                                            <div class="form-group">
                                                <label for="environmental_impact">ผลกระทบด้านสิ่งแวดล้อม:</label>
                                                <textarea id="environmental_impact" name="environmental_impact" required></textarea>
                                            </div>
                                        </div>

                                        <div class="section">
                                            <h3>ผลการประเมิน SROI</h3>
                                            <div class="two-column">
                                                <div class="form-group">
                                                    <label for="evaluation_project2">ชื่อโครงการที่ประเมิน:</label>
                                                    <input type="text" id="evaluation_project2" name="evaluation_project2" 
                                                           value="<?php echo $selected_project ? htmlspecialchars($selected_project['name']) : ''; ?>" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="npv_value">มูลค่า NPV:</label>
                                                    <input type="number" step="0.01" id="npv_value" name="npv_value" required>
                                                </div>
                                            </div>
                                            <div class="three-column">
                                                <div class="form-group">
                                                    <label for="npv_status">สถานะ NPV:</label>
                                                    <select id="npv_status" name="npv_status" required>
                                                        <option value="">เลือก</option>
                                                        <option value="มากกว่า 0">มากกว่า 0</option>
                                                        <option value="น้อยกว่า 0">น้อยกว่า 0</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="sroi_value">ค่า SROI:</label>
                                                    <input type="number" step="0.01" id="sroi_value" name="sroi_value" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="social_return">ผลตอบแทนทางสังคม (บาท):</label>
                                                    <input type="number" step="0.01" id="social_return" name="social_return" required>
                                                </div>
                                            </div>
                                            <div class="three-column">
                                                <div class="form-group">
                                                    <label for="investment_status">สถานะการลงทุน:</label>
                                                    <select id="investment_status" name="investment_status" required>
                                                        <option value="">เลือก</option>
                                                        <option value="คุ้มค่าการลงทุน">คุ้มค่าการลงทุน</option>
                                                        <option value="ไม่คุ้มค่าการลงทุน">ไม่คุ้มค่าการลงทุน</option>
                                                    </select>
                                                </div>
                                                <div class="form-group">
                                                    <label for="irr_value">ค่า IRR (%):</label>
                                                    <input type="number" step="0.01" id="irr_value" name="irr_value" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="irr_compare">การเปรียบเทียบ IRR:</label>
                                                    <select id="irr_compare" name="irr_compare" required>
                                                        <option value="">เลือก</option>
                                                        <option value="มากกว่า">มากกว่า</option>
                                                        <option value="น้อยกว่า">น้อยกว่า</option>
                                                    </select>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section">
                                            <h3>ตารางการเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h3>
                                            <div class="form-group">
                                                <label>เพิ่มรายการเปรียบเทียบ:</label>
                                                <div style="margin-bottom: 20px;">
                                                    <button type="button" class="btn" onclick="addComparisonRow()" style="font-size: 14px; padding: 8px 16px;">+ เพิ่มรายการ</button>
                                                </div>
                                                <div style="overflow-x: auto;">
                                                    <table id="comparisonTable" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                                                        <thead>
                                                            <tr style="background: #667eea; color: white;">
                                                                <th style="border: 2px solid #ddd; padding: 12px; text-align: center; width: 45%;">การเปลี่ยนแปลงหลังจากมีโครงการ (with)</th>
                                                                <th style="border: 2px solid #ddd; padding: 12px; text-align: center; width: 45%;">กรณีที่ยังไม่มีโครงการ (without)</th>
                                                                <th style="border: 2px solid #ddd; padding: 12px; text-align: center; width: 10%;">ลบ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="comparisonTableBody">
                                                            <tr>
                                                                <td style="border: 1px solid #ddd; padding: 8px;">
                                                                    <textarea name="with_scenario[]" style="width: 100%; border: none; resize: vertical; min-height: 60px;" placeholder="อธิบายสถานการณ์หลังมีโครงการ"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 8px;">
                                                                    <textarea name="without_scenario[]" style="width: 100%; border: none; resize: vertical; min-height: 60px;" placeholder="อธิบายสถานการณ์หากไม่มีโครงการ"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                                                                    <button type="button" onclick="removeComparisonRow(this)" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">ลบ</button>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section">
                                            <h3>ตารางที่ 2 เส้นทางผลกระทบทางสังคม (Social Impact Pathway)</h3>
                                            <div class="form-group">
                                                <label>เพิ่มรายการเส้นทางผลกระทบ:</label>
                                                <div style="margin-bottom: 20px;">
                                                    <button type="button" class="btn" onclick="addPathwayRow()" style="font-size: 14px; padding: 8px 16px;">+ เพิ่มรายการ</button>
                                                </div>
                                                <div style="overflow-x: auto;">
                                                    <table id="pathwayTable" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">
                                                        <thead>
                                                            <tr style="background: #667eea; color: white;">
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 10%;">ปัจจัยนำเข้า<br>Input</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 10%;">กิจกรรม<br>Activities</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 10%;">ผลผลิต<br>Output</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 10%;">ผู้ใช้ประโยชน์<br>User</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์<br>Outcome</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 10%;">ตัวชี้วัด<br>Indicator</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 15%;">ตัวแทนค่าทางการเงิน<br>(Financial Proxy)</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 8%;">ที่มา</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 12%;">ผลกระทบ<br>Impact</th>
                                                                <th style="border: 1px solid #333; padding: 8px; text-align: center; width: 5%;">ลบ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="pathwayTableBody">
                                                            <tr>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_input[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น งบประมาณ, ผู้ดำเนินโครงการ, นักศึกษา, องค์ความรู้, ภูมิปัญญาท้องถิ่น"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_activities[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น การยกระดับผลิตภัณฑ์"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_output[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น กลุ่มมีความรู้ในเรื่องการพัฒนาผลิตภัณฑ์"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_user[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น กลุ่มวิสาหกิจชุมชน"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_outcome[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น ทักษะความสามารถที่เพิ่มขึ้น"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_indicator[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น จำนวนคนที่ได้รับเชิญเป็นวิทยากร"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_financial[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น ค่าตอบแทนวิทยากร 1200 บาท/ชั่วโมง"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_source[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น การสัมภาษณ์ตัวแทนกลุ่ม"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px;">
                                                                    <textarea name="pathway_impact[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น สังคม: สามารถพัฒนาอาชีพ, เศรษฐกิจ: มีรายได้เพิ่มขึ้น, สิ่งแวดล้อม: อนุรักษ์ธรรมชาติ"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #ddd; padding: 4px; text-align: center;">
                                                                    <button type="button" onclick="removePathwayRow(this)" style="background: #dc3545; color: white; border: none; padding: 3px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section">
                                            <h3>ข้อมูลเพิ่มเติมสำหรับรายงาน</h3>
                                            <div class="two-column">
                                                <div class="form-group">
                                                    <label for="interview_project">ชื่อโครงการที่สัมภาษณ์:</label>
                                                    <input type="text" id="interview_project" name="interview_project" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="interviewees">ผู้ให้สัมภาษณ์:</label>
                                                    <input type="text" id="interviewees" name="interviewees" required>
                                                </div>
                                            </div>
                                            <div class="three-column">
                                                <div class="form-group">
                                                    <label for="interview_count">จำนวนผู้ให้สัมภาษณ์:</label>
                                                    <input type="number" id="interview_count" name="interview_count" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="benefit_project">ชื่อโครงการที่วิเคราะห์ผลประโยชน์:</label>
                                                    <input type="text" id="benefit_project" name="benefit_project" required>
                                                </div>
                                                <div class="form-group">
                                                    <label for="operation_year">ปีที่ดำเนินงาน (พ.ศ.):</label>
                                                    <input type="number" id="operation_year" name="operation_year" required>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section">
                                            <h3>ตารางที่ 3 ผลประโยชน์ที่เกิดขึ้นจากดำเนินโครงการ</h3>
                                            <div class="form-group">
                                                <label>เพิ่มรายการผลประโยชน์:</label>
                                                <div style="margin-bottom: 20px;">
                                                    <button type="button" class="btn" onclick="addBenefitRow()" style="font-size: 14px; padding: 8px 16px;">+ เพิ่มรายการ</button>
                                                </div>
                                                <div style="overflow-x: auto;">
                                                    <table id="benefitTable" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px;">
                                                        <thead>
                                                            <tr style="background: #667eea; color: white;">
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 30%;">รายการ</th>
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 12%;">ผลประโยชน์<br>ที่คำนวณได้ (บาท)</th>
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลจากปัจจัยอื่น<br>(Attribution)</th>
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์ส่วนเกิน<br>(Deadweight)</th>
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์ทดแทน<br>(Displacement)</th>
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 12%;">ผลกระทบจาก<br>โครงการ (บาท)</th>
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 8%;">ผลกระทบ<br>ด้าน</th>
                                                                <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 8%;">ลบ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="benefitTableBody">
                                                            <tr>
                                                                <td style="border: 1px solid #333; padding: 6px;">
                                                                    <textarea name="benefit_item[]" style="width: 100%; border: none; resize: vertical; min-height: 50px; font-size: 11px;" placeholder="เช่น รายได้สุทธิจากการจำหน่ายผลิตภัณฑ์"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 6px;">
                                                                    <input type="number" name="benefit_calculated[]" style="width: 100%; border: none; font-size: 11px;" placeholder="15900" step="0.01" onchange="calculateTotal()">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 6px;">
                                                                    <input type="text" name="benefit_attribution[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 6px;">
                                                                    <input type="text" name="benefit_deadweight[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 6px;">
                                                                    <input type="text" name="benefit_displacement[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 6px;">
                                                                    <input type="number" name="benefit_impact[]" style="width: 100%; border: none; font-size: 11px;" placeholder="15900" step="0.01" onchange="calculateTotal()">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 6px;">
                                                                    <select name="benefit_category[]" style="width: 100%; border: none; font-size: 11px;">
                                                                        <option value="">เลือก</option>
                                                                        <option value="เศรษฐกิจ">เศรษฐกิจ</option>
                                                                        <option value="สังคม">สังคม</option>
                                                                        <option value="สิ่งแวดล้อม">สิ่งแวดล้อม</option>
                                                                        <option value="เศรษฐกิจ/สังคม">เศรษฐกิจ/สังคม</option>
                                                                    </select>
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                                                                    <button type="button" onclick="removeBenefitRow(this)" style="background: #dc3545; color: white; border: none; padding: 3px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                        <tfoot>
                                                            <tr style="background: #f8f9fa; font-weight: bold;">
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center; font-size: 12px;">รวม</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: right; font-size: 12px;" id="totalCalculated">0</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: right; font-size: 12px;" id="totalImpact">0</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                            </tr>
                                                        </tfoot>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="section">
                                            <h3>ตารางที่ 4 ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI)</h3>
                                            <div class="form-group">
                                                <label>เพิ่มข้อมูลผลการประเมิน SROI:</label>
                                                <div style="margin-bottom: 20px;">
                                                    <button type="button" class="btn" onclick="addSroiRow()" style="font-size: 14px; padding: 8px 16px;">+ เพิ่มรายการ</button>
                                                </div>
                                                <div style="overflow-x: auto;">
                                                    <table id="sroiTable" style="width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 12px;">
                                                        <thead>
                                                            <tr style="background: #667eea; color: white;">
                                                                <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 40%;">ผลกระทบทางสังคม</th>
                                                                <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">NPV (บาท)</th>
                                                                <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">SROI</th>
                                                                <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 15%;">IRR (%)</th>
                                                                <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 5%;">ลบ</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody id="sroiTableBody">
                                                            <tr>
                                                                <td style="border: 1px solid #333; padding: 8px;">
                                                                    <textarea name="sroi_impact[]" style="width: 100%; border: none; resize: vertical; min-height: 60px; font-size: 12px;" placeholder="เช่น ผลกระทบด้านสังคม: พัฒนาคุณภาพชีวิต, ด้านเศรษฐกิจ: เพิ่มรายได้, ด้านสิ่งแวดล้อม: อนุรักษ์ทรัพยากร"></textarea>
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 8px;">
                                                                    <input type="number" name="sroi_npv[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="100000" step="0.01">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 8px;">
                                                                    <input type="number" name="sroi_ratio[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="1.25" step="0.01">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 8px;">
                                                                    <input type="number" name="sroi_irr[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="5.50" step="0.01">
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 8px; text-align: center;">
                                                                    <button type="button" onclick="removeSroiRow(this)" style="background: #dc3545; color: white; border: none; padding: 5px 8px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                                                                </td>
                                                            </tr>
                                                        </tbody>
                                                    </table>
                                                </div>
                                            </div>
                                        </div>

                                        <div style="text-align: center;">
                                            <button type="submit" class="btn">สร้างรายงาน SROI</button>
                                            <button type="reset" class="btn" style="background: linear-gradient(135deg, #ff6b6b 0%, #ee5a24 100%);">ล้างข้อมูล</button>
                                        </div>
                                    </form>

                                <?php else: ?>
                                    <div class="report">
                                        <h2>ส่วนที่ 4<br>ผลการประเมินผลตอบแทนทางสังคม (Social Return On Investment : SROI)</h2>

                                        <p>โครงการ<span class="highlight"><?php echo htmlspecialchars($project_name); ?></span>ในพื้นที่ <span class="highlight"><?php echo htmlspecialchars($area); ?></span> ได้รับการจัดสรรงบประมาณ <span class="highlight"><?php echo number_format($budget); ?></span> บาท ดำเนินการ<span class="highlight"><?php echo htmlspecialchars($activities); ?></span> ให้กับ<span class="highlight"><?php echo htmlspecialchars($target_group); ?></span></p>

                                        <p>การประเมินผลตอบแทนทางสังคม (SROI) โครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span> ทำการประเมินผลหลังโครงการเสร็จสิ้น (Ex-post Evaluation) ในปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> โดยใช้อัตราดอกเบี้ยพันธบัตรรัฐบาลในปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> ร้อยละ 2.00 เป็นอัตราคิดลด (ธนาคารแห่งประเทศไทย, <?php echo htmlspecialchars($project_year); ?>) และกำหนดให้ปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> เป็นปีฐาน มีขั้นตอนการดำเนินงาน ดังนี้</p>

                                        <p>1. <?php echo htmlspecialchars($step1); ?></p>
                                        <p>2. <?php echo htmlspecialchars($step2); ?></p>
                                        <p>3. <?php echo htmlspecialchars($step3); ?></p>

                                        <h3>การเปลี่ยนแปลงในมิติทางสังคม</h3>
                                        <p>จากการวิเคราะห์การเปลี่ยนแปลงในมิติสังคม (Social Impact Assessment : SIA) ของโครงการ<span class="highlight"><?php echo htmlspecialchars($analysis_project); ?></span>มิติการวิเคราะห์ประกอบด้วย ปัจจัยจำเข้า (Input) กิจกรรม (Activity) ผลผลิต (Output) ผลลัพธ์(Outcome) และผลกระทบของโครงการ (Impact)</p>

                                        <p>โดยผลกระทบที่เกิดจากการดำเนินกิจกรรมภายใต้โครงการ<span class="highlight"><?php echo htmlspecialchars($impact_activities); ?></span>สรุปออกเป็น ผลกระทบ 3 ด้านหลัก ดังนี้</p>

                                        <p>1) ผลกระทบด้านสังคม <span class="highlight"><?php echo htmlspecialchars($social_impact); ?></span></p>
                                        <p>2) ผลกระทบด้านเศรษฐกิจ <span class="highlight"><?php echo htmlspecialchars($economic_impact); ?></span></p>
                                        <p>3) ผลกระทบด้านสิ่งแวดล้อม <span class="highlight"><?php echo htmlspecialchars($environmental_impact); ?></span></p>

                                        <h3>ผลการประเมินผลตอบแทนทางสังคม (SROI)</h3>
                                        <p>พบว่า โครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project2); ?></span>มีมูลค่าผลประโยชน์ปัจจุบันสุทธิของโครงการ (Net Present Value หรือ NPV โดยอัตราคิดลด 2.00) <span class="highlight"><?php echo number_format($npv_value, 2); ?></span> (ซึ่งมีค่า<span class="highlight"><?php echo htmlspecialchars($npv_status); ?></span>) และค่าผลตอบแทนทางสังคมจากการลงทุน <span class="highlight"><?php echo number_format($sroi_value, 2); ?></span> หมายความว่าเงินลงทุนของโครงการ 1 บาท จะสามารถสร้างผลตอบแทนทางสังคมเป็นเงิน <span class="highlight"><?php echo number_format($social_return, 2); ?></span> บาท ซึ่งถือว่า<span class="highlight"><?php echo htmlspecialchars($investment_status); ?></span> และมีอัตราผลตอบแทนภายใน (Internal Rate of Return หรือ IRR) ร้อยละ <span class="highlight"><?php echo number_format($irr_value, 2); ?></span>ซึ่ง<span class="highlight"><?php echo htmlspecialchars($irr_compare); ?></span>อัตราคิดลดร้อยละ 2.00</p>

                                        <h3>การสัมภาษณ์ผู้ได้รับประโยชน์</h3>
                                        <p>จากการสัมภาษณ์ผู้ได้รับประโยชน์โดยตรงจากโครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span> สามารถเปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without) ได้ดังตารางที่ 1</p>

                                        <div style="margin: 20px 0;">
                                            <h4>ตารางที่ 1 เปรียบเทียบการเปลี่ยนแปลงก่อนและหลังการเกิดขึ้นของโครงการ (With and Without)</h4>
                                            <table style="width: 100%; border-collapse: collapse; margin: 15px 0;">
                                                <thead>
                                                    <tr style="background: #e3f2fd;">
                                                        <th style="border: 2px solid #333; padding: 12px; text-align: center; width: 50%;">การเปลี่ยนแปลงหลังจากมีโครงการ (with)</th>
                                                        <th style="border: 2px solid #333; padding: 12px; text-align: center; width: 50%;">กรณีที่ยังไม่มีโครงการ (without)</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (!empty($with_scenarios) && !empty($without_scenarios)): ?>
                                                        <?php for ($i = 0; $i < max(count($with_scenarios), count($without_scenarios)); $i++): ?>
                                                            <tr>
                                                                <td style="border: 1px solid #333; padding: 10px; vertical-align: top;">
                                                                    <?php echo isset($with_scenarios[$i]) ? nl2br(htmlspecialchars($with_scenarios[$i])) : ''; ?>
                                                                </td>
                                                                <td style="border: 1px solid #333; padding: 10px; vertical-align: top;">
                                                                    <?php echo isset($without_scenarios[$i]) ? nl2br(htmlspecialchars($without_scenarios[$i])) : ''; ?>
                                                                </td>
                                                            </tr>
                                                        <?php endfor; ?>
                                                    <?php else: ?>
                                                        <tr>
                                                            <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="2">
                                                                <em>ไม่มีข้อมูลการเปรียบเทียบ</em>
                                                            </td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>

                                        <div style="margin: 20px 0;">
                                            <h4>ตารางที่ 2 เส้นทางผลกระทบทางสังคม (Social Impact Pathway) โครงการ<?php echo htmlspecialchars($evaluation_project); ?></h4>
                                            <div style="overflow-x: auto;">
                                                <table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11px;">
                                                    <thead>
                                                        <tr style="background: #e3f2fd;">
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ปัจจัยนำเข้า<br>Input</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">กิจกรรม<br>Activities</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลผลิต<br>Output</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผู้ใช้ประโยชน์<br>User</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์<br>Outcome</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ตัวชี้วัด<br>Indicator</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 15%;">ตัวแทนค่าทางการเงิน<br>(Financial Proxy)</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 8%;">ที่มา</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 17%;">ผลกระทบ<br>Impact</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($pathway_input)): ?>
                                                            <?php for ($i = 0; $i < count($pathway_input); $i++): ?>
                                                                <tr>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_input[$i]) ? nl2br(htmlspecialchars($pathway_input[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_activities[$i]) ? nl2br(htmlspecialchars($pathway_activities[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_output[$i]) ? nl2br(htmlspecialchars($pathway_output[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_user[$i]) ? nl2br(htmlspecialchars($pathway_user[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_outcome[$i]) ? nl2br(htmlspecialchars($pathway_outcome[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_indicator[$i]) ? nl2br(htmlspecialchars($pathway_indicator[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_financial[$i]) ? nl2br(htmlspecialchars($pathway_financial[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_source[$i]) ? nl2br(htmlspecialchars($pathway_source[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($pathway_impact[$i]) ? nl2br(htmlspecialchars($pathway_impact[$i])) : ''; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endfor; ?>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="9">
                                                                    <em>ไม่มีข้อมูล Social Impact Pathway</em>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <h3>ผลประโยชน์ที่เกิดขึ้นจากดำเนินโครงการ</h3>
                                        <p><span class="highlight"><?php echo htmlspecialchars($benefit_project); ?></span> จากการวิเคราะห์เส้นทางผลกระทบทางสังคม (Social Impact Pathway) สามารถนำมาคำนวณผลประโยชน์ที่เกิดขึ้นของโครงการปี พ.ศ. <?php echo htmlspecialchars($operation_year); ?> ได้ดังนี้</p>

                                        <div style="margin: 20px 0;">
                                            <h4>ตารางที่ 3 ผลประโยชน์ที่เกิดขึ้นจากดำเนินโครงการ</h4>
                                            <div style="overflow-x: auto;">
                                                <table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 11px;">
                                                    <thead>
                                                        <tr style="background: #e3f2fd;">
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 35%;">รายการ</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 12%;">ผลประโยชน์<br>ที่คำนวณได้</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลจากปัจจัยอื่น<br>(Attribution)</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์ส่วนเกิน<br>(Deadweight)</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลลัพธ์ทดแทน<br>(Displacement)</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 10%;">ผลกระทบจาก<br>โครงการ</th>
                                                            <th style="border: 2px solid #333; padding: 8px; text-align: center; width: 8%;">ผลกระทบ<br>ด้าน</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($benefit_item)): ?>
                                                            <?php
                                                            $totalCalculated = 0;
                                                            $totalImpact = 0;
                                                            for ($i = 0; $i < count($benefit_item); $i++):
                                                                if (isset($benefit_calculated[$i]) && is_numeric($benefit_calculated[$i])) {
                                                                    $totalCalculated += floatval($benefit_calculated[$i]);
                                                                }
                                                                if (isset($benefit_impact[$i]) && is_numeric($benefit_impact[$i])) {
                                                                    $totalImpact += floatval($benefit_impact[$i]);
                                                                }
                                                            ?>
                                                                <tr>
                                                                    <td style="border: 1px solid #333; padding: 6px; vertical-align: top; font-size: 10px;">
                                                                        <?php echo isset($benefit_item[$i]) ? nl2br(htmlspecialchars($benefit_item[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; text-align: right; font-size: 10px;">
                                                                        <?php echo isset($benefit_calculated[$i]) && is_numeric($benefit_calculated[$i]) ? number_format($benefit_calculated[$i]) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                                        <?php echo isset($benefit_attribution[$i]) ? htmlspecialchars($benefit_attribution[$i]) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                                        <?php echo isset($benefit_deadweight[$i]) ? htmlspecialchars($benefit_deadweight[$i]) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                                        <?php echo isset($benefit_displacement[$i]) ? htmlspecialchars($benefit_displacement[$i]) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; text-align: right; font-size: 10px;">
                                                                        <?php echo isset($benefit_impact[$i]) && is_numeric($benefit_impact[$i]) ? number_format($benefit_impact[$i]) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 6px; text-align: center; font-size: 10px;">
                                                                        <?php echo isset($benefit_category[$i]) ? htmlspecialchars($benefit_category[$i]) : ''; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endfor; ?>
                                                            <tr style="background: #f8f9fa; font-weight: bold;">
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center; font-size: 12px;">รวม</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: right; font-size: 12px;">
                                                                    <?php echo number_format($totalCalculated); ?>
                                                                </td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: right; font-size: 12px;">
                                                                    <?php echo number_format($totalImpact); ?>
                                                                </td>
                                                                <td style="border: 2px solid #333; padding: 8px; text-align: center;">-</td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="7">
                                                                    <em>ไม่มีข้อมูลผลประโยชน์</em>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div style="margin: 20px 0;">
                                            <h4>ตารางที่ 4 ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI)</h4>
                                            <div style="overflow-x: auto;">
                                                <table style="width: 100%; border-collapse: collapse; margin: 15px 0; font-size: 12px;">
                                                    <thead>
                                                        <tr style="background: #e3f2fd;">
                                                            <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 40%;">ผลกระทบทางสังคม</th>
                                                            <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">NPV (บาท)</th>
                                                            <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">SROI</th>
                                                            <th style="border: 2px solid #333; padding: 10px; text-align: center; width: 20%;">IRR (%)</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php if (!empty($sroi_impact)): ?>
                                                            <?php for ($i = 0; $i < count($sroi_impact); $i++): ?>
                                                                <tr>
                                                                    <td style="border: 1px solid #333; padding: 10px; vertical-align: top; font-size: 11px;">
                                                                        <?php echo isset($sroi_impact[$i]) ? nl2br(htmlspecialchars($sroi_impact[$i])) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 10px; text-align: right; font-size: 11px;">
                                                                        <?php echo isset($sroi_npv[$i]) && is_numeric($sroi_npv[$i]) ? number_format($sroi_npv[$i], 2) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 10px; text-align: right; font-size: 11px;">
                                                                        <?php echo isset($sroi_ratio[$i]) && is_numeric($sroi_ratio[$i]) ? number_format($sroi_ratio[$i], 2) : ''; ?>
                                                                    </td>
                                                                    <td style="border: 1px solid #333; padding: 10px; text-align: right; font-size: 11px;">
                                                                        <?php echo isset($sroi_irr[$i]) && is_numeric($sroi_irr[$i]) ? number_format($sroi_irr[$i], 2) : ''; ?>
                                                                    </td>
                                                                </tr>
                                                            <?php endfor; ?>
                                                            <tr style="background: #f8f9fa; font-weight: bold;">
                                                                <td style="border: 2px solid #333; padding: 10px; text-align: center; font-size: 12px;">รวม/เฉลี่ย</td>
                                                                <td style="border: 2px solid #333; padding: 10px; text-align: right; font-size: 12px;">
                                                                    <?php 
                                                                    $total_npv = 0;
                                                                    foreach($sroi_npv as $npv) {
                                                                        if (is_numeric($npv)) $total_npv += floatval($npv);
                                                                    }
                                                                    echo number_format($total_npv, 2);
                                                                    ?>
                                                                </td>
                                                                <td style="border: 2px solid #333; padding: 10px; text-align: right; font-size: 12px;">
                                                                    <?php 
                                                                    $avg_sroi = 0;
                                                                    $count_sroi = 0;
                                                                    foreach($sroi_ratio as $ratio) {
                                                                        if (is_numeric($ratio)) {
                                                                            $avg_sroi += floatval($ratio);
                                                                            $count_sroi++;
                                                                        }
                                                                    }
                                                                    if ($count_sroi > 0) {
                                                                        echo number_format($avg_sroi / $count_sroi, 2);
                                                                    } else {
                                                                        echo '0.00';
                                                                    }
                                                                    ?>
                                                                </td>
                                                                <td style="border: 2px solid #333; padding: 10px; text-align: right; font-size: 12px;">
                                                                    <?php 
                                                                    $avg_irr = 0;
                                                                    $count_irr = 0;
                                                                    foreach($sroi_irr as $irr) {
                                                                        if (is_numeric($irr)) {
                                                                            $avg_irr += floatval($irr);
                                                                            $count_irr++;
                                                                        }
                                                                    }
                                                                    if ($count_irr > 0) {
                                                                        echo number_format($avg_irr / $count_irr, 2);
                                                                    } else {
                                                                        echo '0.00';
                                                                    }
                                                                    ?>
                                                                </td>
                                                            </tr>
                                                        <?php else: ?>
                                                            <tr>
                                                                <td style="border: 1px solid #333; padding: 10px; text-align: center;" colspan="4">
                                                                    <em>ไม่มีข้อมูลผลการประเมิน SROI</em>
                                                                </td>
                                                            </tr>
                                                        <?php endif; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <h3>ผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI)</h3>
                                        <p>โครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span>ประเมินหลังจากการดำเนินโครงการเสร็จสิ้น (Ex-Post Evaluation) ณ ปี พ.ศ. <?php echo htmlspecialchars($project_year); ?></p>

                                        <p>เมื่อทราบถึงผลประโยชน์ที่เกิดขึ้นหลังจากหักกรณีฐานแล้วนำมาเปรียบเทียบกับต้นทุน เพื่อประเมินผลตอบแทนทางสังคมจากการลงทุน โดยใช้อัตราคิดลดร้อยละ 2.00 ซึ่งคิดจากค่าเสียโอกาสในการลงทุนด้วยอัตราดอกเบี้ยพันธบัตรออมทรัพย์เฉลี่ยในปี พ.ศ. <?php echo htmlspecialchars($project_year); ?> (ธนาคารแห่งประเทศไทย, <?php echo htmlspecialchars($project_year); ?>) ซึ่งเป็นปีที่ดำเนินการ มีผลการวิเคราะห์โดยใช้โปรแกรมการวิเคราะห์ของ เศรษฐภูมิ บัวทอง และคณะ (2566)</p>

                                        <h3>สรุปผลการประเมิน</h3>
                                        <p>จากการวิเคราะห์พบว่าเมื่อผลการประเมินผลตอบแทนทางสังคมจากการลงทุน (SROI) มีค่า <span class="highlight"><?php echo number_format($sroi_value, 2); ?></span> ซึ่งมีค่า<span class="highlight"><?php echo htmlspecialchars($investment_status == 'คุ้มค่าการลงทุน' ? 'มากกว่า 1' : 'น้อยกว่า 1'); ?></span> ค่า NPV เท่ากับ <span class="highlight"><?php echo number_format($npv_value, 2); ?></span> มีค่า<span class="highlight"><?php echo htmlspecialchars($npv_status); ?></span> และค่า IRR มีค่าร้อยละ<span class="highlight"><?php echo number_format($irr_value, 2); ?></span> ซึ่ง<span class="highlight"><?php echo htmlspecialchars($irr_compare); ?></span>อัตราคิดลด ร้อยละ 2.00</p>

                                        <p>ซึ่งแสดงให้เห็นว่าเงินลงทุน 1 บาทจะได้ผลตอบแทนทางสังคมกลับมา <span class="highlight"><?php echo number_format($social_return, 2); ?></span> บาท แสดงให้เห็นว่าการดำเนินโครงการ<span class="highlight"><?php echo htmlspecialchars($evaluation_project); ?></span><span class="highlight"><?php echo htmlspecialchars($investment_status); ?></span></p>

                                        <div style="margin-top: 40px; padding: 20px; background: #f0f8ff; border-radius: 10px; border-left: 5px solid #2196F3;">
                                            <h4 style="color: #1976D2; margin-top: 0;">สรุปผลการประเมิน SROI</h4>
                                            <table style="width: 100%; border-collapse: collapse; margin-top: 15px;">
                                                <tr style="background: #e3f2fd;">
                                                    <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">รายการ</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd; font-weight: bold;">ผลการประเมิน</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px; border: 1px solid #ddd;">โครงการ</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo htmlspecialchars($project_name); ?></td>
                                                </tr>
                                                <tr style="background: #f8f9fa;">
                                                    <td style="padding: 10px; border: 1px solid #ddd;">งบประมาณ</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo number_format($budget); ?> บาท</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px; border: 1px solid #ddd;">ค่า SROI</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo number_format($sroi_value, 2); ?></td>
                                                </tr>
                                                <tr style="background: #f8f9fa;">
                                                    <td style="padding: 10px; border: 1px solid #ddd;">ค่า NPV</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo number_format($npv_value, 2); ?> บาท</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px; border: 1px solid #ddd;">ค่า IRR</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo number_format($irr_value, 2); ?>%</td>
                                                </tr>
                                                <tr style="background: #f8f9fa;">
                                                    <td style="padding: 10px; border: 1px solid #ddd;">ผลตอบแทนต่อการลงทุน 1 บาท</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd;"><?php echo number_format($social_return, 2); ?> บาท</td>
                                                </tr>
                                                <tr>
                                                    <td style="padding: 10px; border: 1px solid #ddd;">สรุปความคุ้มค่า</td>
                                                    <td style="padding: 10px; border: 1px solid #ddd; <?php echo ($investment_status == 'คุ้มค่าการลงทุน') ? 'color: green; font-weight: bold;' : 'color: red; font-weight: bold;'; ?>"><?php echo htmlspecialchars($investment_status); ?></td>
                                                </tr>
                                            </table>
                                        </div>

                                        <div style="margin-top: 30px; text-align: center;">
                                            <button onclick="window.print()" class="btn">พิมพ์รายงาน</button>
                                            <button onclick="window.location.href=''" class="btn" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">สร้างรายงานใหม่</button>
                                            <button onclick="generatePDF()" class="btn" style="background: linear-gradient(135deg, #fd7e14 0%, #e63946 100%);">ดาวน์โหลด PDF</button>
                                        </div>
                                    </div>

                                    <script>
                                        // เพิ่ม JavaScript สำหรับจัดการตารางเปรียบเทียบ
                                        function addComparisonRow() {
                                            const tableBody = document.getElementById('comparisonTableBody');
                                            const newRow = document.createElement('tr');
                                            newRow.innerHTML = `
                <td style="border: 1px solid #ddd; padding: 8px;">
                    <textarea name="with_scenario[]" style="width: 100%; border: none; resize: vertical; min-height: 60px;" placeholder="อธิบายสถานการณ์หลังมีโครงการ"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 8px;">
                    <textarea name="without_scenario[]" style="width: 100%; border: none; resize: vertical; min-height: 60px;" placeholder="อธิบายสถานการณ์หากไม่มีโครงการ"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 8px; text-align: center;">
                    <button type="button" onclick="removeComparisonRow(this)" style="background: #dc3545; color: white; border: none; padding: 5px 10px; border-radius: 3px; cursor: pointer;">ลบ</button>
                </td>
            `;
                                            tableBody.appendChild(newRow);
                                        }

                                        function removeComparisonRow(button) {
                                            const tableBody = document.getElementById('comparisonTableBody');
                                            if (tableBody.children.length > 1) {
                                                button.closest('tr').remove();
                                            } else {
                                                alert('ต้องมีรายการเปรียบเทียบอย่างน้อย 1 รายการ');
                                            }
                                        }

                                        // เพิ่ม JavaScript สำหรับจัดการตาราง Social Impact Pathway
                                        function addPathwayRow() {
                                            const tableBody = document.getElementById('pathwayTableBody');
                                            const newRow = document.createElement('tr');
                                            newRow.innerHTML = `
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_input[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น งบประมาณ, ผู้ดำเนินโครงการ, นักศึกษา, องค์ความรู้, ภูมิปัญญาท้องถิ่น"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_activities[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น การยกระดับผลิตภัณฑ์"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_output[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น กลุ่มมีความรู้ในเรื่องการพัฒนาผลิตภัณฑ์"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_user[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น กลุ่มวิสาหกิจชุมชน"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_outcome[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น ทักษะความสามารถที่เพิ่มขึ้น"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_indicator[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น จำนวนคนที่ได้รับเชิญเป็นวิทยากร"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_financial[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น ค่าตอบแทนวิทยากร 1200 บาท/ชั่วโมง"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_source[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น การสัมภาษณ์ตัวแทนกลุ่ม"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px;">
                    <textarea name="pathway_impact[]" style="width: 100%; border: none; resize: vertical; min-height: 80px; font-size: 11px;" placeholder="เช่น สังคม: สามารถพัฒนาอาชีพ, เศรษฐกิจ: มีรายได้เพิ่มขึ้น, สิ่งแวดล้อม: อนุรักษ์ธรรมชาติ"></textarea>
                </td>
                <td style="border: 1px solid #ddd; padding: 4px; text-align: center;">
                    <button type="button" onclick="removePathwayRow(this)" style="background: #dc3545; color: white; border: none; padding: 3px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                </td>
            `;
                                            tableBody.appendChild(newRow);
                                        }

                                        function removePathwayRow(button) {
                                            const tableBody = document.getElementById('pathwayTableBody');
                                            if (tableBody.children.length > 1) {
                                                button.closest('tr').remove();
                                            } else {
                                                alert('ต้องมีรายการ Social Impact Pathway อย่างน้อย 1 รายการ');
                                            }
                                        }

                                        // เพิ่ม JavaScript สำหรับจัดการตารางผลประโยชน์
                                        function addBenefitRow() {
                                            const tableBody = document.getElementById('benefitTableBody');
                                            const newRow = document.createElement('tr');
                                            newRow.innerHTML = `
                <td style="border: 1px solid #333; padding: 6px;">
                    <textarea name="benefit_item[]" style="width: 100%; border: none; resize: vertical; min-height: 50px; font-size: 11px;" placeholder="เช่น รายได้สุทธิจากการจำหน่ายผลิตภัณฑ์"></textarea>
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="number" name="benefit_calculated[]" style="width: 100%; border: none; font-size: 11px;" placeholder="15,900" step="0.01" onchange="calculateTotal()">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="text" name="benefit_attribution[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="text" name="benefit_deadweight[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="text" name="benefit_displacement[]" style="width: 100%; border: none; font-size: 11px;" placeholder="0%">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <input type="number" name="benefit_impact[]" style="width: 100%; border: none; font-size: 11px;" placeholder="15,900" step="0.01" onchange="calculateTotal()">
                </td>
                <td style="border: 1px solid #333; padding: 6px;">
                    <select name="benefit_category[]" style="width: 100%; border: none; font-size: 11px;">
                        <option value="">เลือก</option>
                        <option value="เศรษฐกิจ">เศรษฐกิจ</option>
                        <option value="สังคม">สังคม</option>
                        <option value="สิ่งแวดล้อม">สิ่งแวดล้อม</option>
                        <option value="เศรษฐกิจ/สังคม">เศรษฐกิจ/สังคม</option>
                    </select>
                </td>
                <td style="border: 1px solid #333; padding: 6px; text-align: center;">
                    <button type="button" onclick="removeBenefitRow(this)" style="background: #dc3545; color: white; border: none; padding: 3px 6px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                </td>
            `;
                                            tableBody.appendChild(newRow);
                                        }

                                        function removeBenefitRow(button) {
                                            const tableBody = document.getElementById('benefitTableBody');
                                            if (tableBody.children.length > 1) {
                                                button.closest('tr').remove();
                                                calculateTotal();
                                            } else {
                                                alert('ต้องมีรายการผลประโยชน์อย่างน้อย 1 รายการ');
                                            }
                                        }

                                        function calculateTotal() {
                                            const calculatedInputs = document.querySelectorAll('input[name="benefit_calculated[]"]');
                                            const impactInputs = document.querySelectorAll('input[name="benefit_impact[]"]');

                                            let totalCalculated = 0;
                                            let totalImpact = 0;

                                            calculatedInputs.forEach(input => {
                                                if (input.value && !isNaN(input.value)) {
                                                    totalCalculated += parseFloat(input.value);
                                                }
                                            });

                                            impactInputs.forEach(input => {
                                                if (input.value && !isNaN(input.value)) {
                                                    totalImpact += parseFloat(input.value);
                                                }
                                            });

                                            document.getElementById('totalCalculated').textContent = totalCalculated.toLocaleString();
                                            document.getElementById('totalImpact').textContent = totalImpact.toLocaleString();
                                        }

                                        // เพิ่ม JavaScript สำหรับจัดการตารางที่ 4 ผลการประเมิน SROI
                                        function addSroiRow() {
                                            const tableBody = document.getElementById('sroiTableBody');
                                            const newRow = document.createElement('tr');
                                            newRow.innerHTML = `
                <td style="border: 1px solid #333; padding: 8px;">
                    <textarea name="sroi_impact[]" style="width: 100%; border: none; resize: vertical; min-height: 60px; font-size: 12px;" placeholder="เช่น ผลกระทบด้านสังคม: พัฒนาคุณภาพชีวิต, ด้านเศรษฐกิจ: เพิ่มรายได้, ด้านสิ่งแวดล้อม: อนุรักษ์ทรัพยากร"></textarea>
                </td>
                <td style="border: 1px solid #333; padding: 8px;">
                    <input type="number" name="sroi_npv[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="100000" step="0.01">
                </td>
                <td style="border: 1px solid #333; padding: 8px;">
                    <input type="number" name="sroi_ratio[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="1.25" step="0.01">
                </td>
                <td style="border: 1px solid #333; padding: 8px;">
                    <input type="number" name="sroi_irr[]" style="width: 100%; border: none; font-size: 12px; text-align: right;" placeholder="5.50" step="0.01">
                </td>
                <td style="border: 1px solid #333; padding: 8px; text-align: center;">
                    <button type="button" onclick="removeSroiRow(this)" style="background: #dc3545; color: white; border: none; padding: 5px 8px; border-radius: 3px; cursor: pointer; font-size: 10px;">ลบ</button>
                </td>
            `;
                                            tableBody.appendChild(newRow);
                                        }

                                        function removeSroiRow(button) {
                                            const tableBody = document.getElementById('sroiTableBody');
                                            if (tableBody.children.length > 1) {
                                                button.closest('tr').remove();
                                            } else {
                                                alert('ต้องมีรายการผลการประเมิน SROI อย่างน้อย 1 รายการ');
                                            }
                                        }

                                        function generatePDF() {
                                            // สำหรับการสร้าง PDF (ต้องเพิ่ม library เช่น jsPDF)
                                            alert('ฟีเจอร์การสร้าง PDF จะพัฒนาในเวอร์ชันต่อไป\nสามารถใช้ฟังก์ชัน "พิมพ์รายงาน" แล้วเลือก "Save as PDF" แทน');
                                        }

                                        // เพิ่ม function สำหรับโหลดข้อมูลโครงการ
                                        function loadProjectData(projectId) {
                                            if (projectId) {
                                                window.location.href = 'report-sroi.php?project_id=' + projectId;
                                            }
                                        }

                                        // function สำหรับเคลียร์การเลือกโครงการ
                                        function clearProject() {
                                            window.location.href = 'report-sroi.php';
                                        }

                                        // เพิ่มการตรวจสอบก่อนส่งฟอร์ม
                                        document.addEventListener('DOMContentLoaded', function() {
                                            const form = document.querySelector('form');
                                            if (form) {
                                                form.addEventListener('submit', function(e) {
                                                    const requiredFields = form.querySelectorAll('[required]');
                                                    let hasEmpty = false;

                                                    requiredFields.forEach(field => {
                                                        if (!field.value.trim()) {
                                                            hasEmpty = true;
                                                            field.style.borderColor = '#dc3545';
                                                        } else {
                                                            field.style.borderColor = '#ddd';
                                                        }
                                                    });

                                                    if (hasEmpty) {
                                                        e.preventDefault();
                                                        alert('กรุณากรอกข้อมูลให้ครบถ้วน');
                                                        window.scrollTo(0, 0);
                                                    }
                                                });
                                            }
                                        });
                                    </script>

                                    <style>
                                        @media print {
                                            .btn {
                                                display: none;
                                            }

                                            .container {
                                                box-shadow: none;
                                                max-width: none;
                                                margin: 0;
                                                padding: 0;
                                            }

                                            .header {
                                                background: none !important;
                                                color: black !important;
                                            }
                                        }
                                    </style>

                                <?php endif; ?>
                            </div>
                        </body>

                        </html>