<?php
session_start();
require_once '../config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ตั้งค่าตัวแปรสำหรับข้อความแจ้งเตือน
$message = '';
$error = '';

// ดึงข้อมูล session ที่จำเป็น
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// รับ project_id จาก URL
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// ดึงข้อมูลโครงการที่เลือก
$selected_project = null;
if ($project_id > 0) {
    $project_query = "SELECT id, project_code, name FROM projects WHERE id = ?";
    $project_stmt = mysqli_prepare($conn, $project_query);
    mysqli_stmt_bind_param($project_stmt, "i", $project_id);
    mysqli_stmt_execute($project_stmt);
    $project_result = mysqli_stmt_get_result($project_stmt);
    $selected_project = mysqli_fetch_assoc($project_result);
    mysqli_stmt_close($project_stmt);
}

// ดึงข้อมูล impact pathway ที่มีอยู่แล้วสำหรับโครงการนี้
$existing_pathways = [];
if ($project_id > 0) {
    $pathway_query = "SELECT * FROM social_impact_pathway WHERE project_id = ? ORDER BY pathway_sequence";
    $pathway_stmt = mysqli_prepare($conn, $pathway_query);
    mysqli_stmt_bind_param($pathway_stmt, "i", $project_id);
    mysqli_stmt_execute($pathway_stmt);
    $pathway_result = mysqli_stmt_get_result($pathway_stmt);
    while ($pathway = mysqli_fetch_assoc($pathway_result)) {
        $existing_pathways[] = $pathway;
    }
    mysqli_stmt_close($pathway_stmt);
}

// ดึงรายการโครงการสำหรับ dropdown
$projects_query = "SELECT id, project_code, name FROM projects WHERE status = 'incompleted' ORDER BY project_code";
$projects_result = mysqli_query($conn, $projects_query);

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $project_id = intval($_POST['project_id']);
        $pathway_sequence = trim($_POST['pathway_sequence']);
        $from_modal = isset($_POST['from_modal']) ? true : false;

        // รับข้อมูลจากแต่ละขั้นตอน
        $input_description = trim($_POST['input_description']);
        $activities_description = trim($_POST['activities_description']);
        $output_description = trim($_POST['output_description']);
        $user_description = trim($_POST['user_description']);
        $outcome_description = trim($_POST['outcome_description']);
        $impact_description = trim($_POST['impact_description']);

        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($project_id)) {
            throw new Exception("กรุณาเลือกโครงการ");
        }
        if (empty($pathway_sequence)) {
            throw new Exception("กรุณากรอกลำดับของห่วงโซ่");
        }

        mysqli_begin_transaction($conn);

        // บันทึกข้อมูล Social Impact Pathway (ลบ adoption_description ออก)
        $query = "
            INSERT INTO social_impact_pathway (
                project_id, pathway_sequence, input_description, activities_description, 
                output_description, user_description, 
                outcome_description, impact_description, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "issssssss",
            $project_id,
            $pathway_sequence,
            $input_description,
            $activities_description,
            $output_description,
            $user_description,
            $outcome_description,
            $impact_description,
            $user_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn));
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conn);

        $_SESSION['success_message'] = "บันทึกข้อมูล Social Impact Pathway เรียบร้อยแล้ว";

        // ถ้ามาจาก modal ใน step4 ให้กลับไปหน้า impact chain
        if ($from_modal) {
            header("Location: ../impact-chain/step4-outcome.php?project_id=" . $project_id);
        } else {
            header("Location: impact_pathway.php?project_id=" . $project_id);
        }
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้าง Social Impact Pathway - SROI System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #56ab2f;
            --warning-color: #f093fb;
            --danger-color: #f5576c;
            --info-color: #4ecdc4;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --border-color: #e0e0e0;
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.15);
            --shadow-heavy: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            justify-content: center;
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: white;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--border-color);
        }

        /* Pathway Display Table */
        .pathway-display-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-medium);
            border-radius: 12px;
            overflow: hidden;
        }

        .pathway-display-table th {
            padding: 1rem;
            text-align: center;
            font-weight: bold;
            font-size: 1rem;
            border: 2px solid #333;
            vertical-align: middle;
        }

        .pathway-display-table td {
            padding: 1rem;
            border: 2px solid #333;
            height: 80px;
            vertical-align: top;
            font-size: 0.9rem;
            line-height: 1.4;
        }

        /* Header Colors */
        .header-input {
            background-color: #e8f5e8;
        }

        .header-activities {
            background-color: #fff2cc;
        }

        .header-output {
            background-color: #e1f5fe;
        }

        .header-user {
            background-color: #fce4ec;
        }

        .header-outcome {
            background-color: #e8eaf6;
        }

        .header-impact {
            background-color: #e3f2fd;
        }

        /* Empty cells */
        .pathway-display-table td {
            background-color: #fafafa;
        }

        /* Data cells */
        .pathway-display-table td.data-cell {
            background-color: #ffffff;
            border: 2px solid #4CAF50;
        }

        .form-title {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 2rem;
            text-align: center;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            padding-top: 0.75rem;
        }

        .step-number {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .required {
            color: var(--danger-color);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        /* Basic Info Section */
        .basic-info {
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            background: var(--light-bg);
            border-radius: 12px;
        }

        .basic-info .form-group {
            margin-bottom: 1.5rem;
        }

        .basic-info .form-group:last-child {
            margin-bottom: 0;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(45deg, rgba(86, 171, 47, 0.1), rgba(168, 230, 207, 0.1));
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background: linear-gradient(45deg, rgba(245, 87, 108, 0.1), rgba(240, 147, 251, 0.1));
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* Buttons */
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid var(--light-bg);
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-muted);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--text-muted);
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Project Info */
        .project-info {
            background: var(--info-color);
            color: white;
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-group {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .form-label {
                padding-top: 0;
                margin-bottom: 0.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .page-title {
                font-size: 2rem;
            }

            .nav-container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 1rem;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="../index.php" class="logo">
                🎯 SROI System
            </a>
            <ul class="nav-menu">
                <li><a href="../dashboard.php" class="nav-link">📊 Dashboard</a></li>
                <li><a href="../project-list.php" class="nav-link">📋 โครงการ</a></li>
                <li><a href="impact_pathway.php" class="nav-link active">📈 การวิเคราะห์</a></li>
                <li><a href="../reports.php" class="nav-link">📄 รายงาน</a></li>
                <li><a href="../settings.php" class="nav-link">⚙️ ตั้งค่า</a></li>
            </ul>
            <?php include '../user-menu.php'; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Form Container -->
        <div class="form-container">
            <h2 class="form-title">ห่วงโซ่ผลกระทบทางสังคม</h2>

            <!-- Project Info -->
            <?php if ($selected_project): ?>
                <div class="project-info">
                    <i class="fas fa-project-diagram"></i>
                    <strong>โครงการ:</strong> <?php echo htmlspecialchars($selected_project['project_code'] . ' - ' . $selected_project['name']); ?>
                </div>
            <?php endif; ?>

            <!-- Pathway Display Table -->
            <table class="pathway-display-table">
                <thead>
                    <tr>
                        <th class="header-input">ปัจจัยนำเข้า<br><small>Input</small></th>
                        <th class="header-activities">กิจกรรม<br><small>Activities</small></th>
                        <th class="header-output">ผลผลิต<br><small>Output</small></th>
                        <th class="header-user">ผู้ใช้ประโยชน์<br><small>User</small></th>
                        <th class="header-outcome">ผลลัพธ์<br><small>Outcome</small></th>
                        <th class="header-impact">ผลกระทบ<br><small>Impact</small></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($existing_pathways) > 0): ?>
                        <?php foreach ($existing_pathways as $pathway): ?>
                            <tr>
                                <td class="data-cell"><?php echo htmlspecialchars($pathway['input_description']); ?></td>
                                <td class="data-cell"><?php echo htmlspecialchars($pathway['activities_description']); ?></td>
                                <td class="data-cell"><?php echo htmlspecialchars($pathway['output_description']); ?></td>
                                <td class="data-cell"><?php echo htmlspecialchars($pathway['user_description']); ?></td>
                                <td class="data-cell"><?php echo htmlspecialchars($pathway['outcome_description']); ?></td>
                                <td class="data-cell"><?php echo htmlspecialchars($pathway['impact_description']); ?></td>
                            </tr>
                        <?php endforeach; ?>

                        <!-- เพิ่มแถวว่างเพิ่มเติมถ้ามีน้อยกว่า 3 แถว -->
                        <?php for ($i = count($existing_pathways); $i < 3; $i++): ?>
                            <tr>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                                <td></td>
                            </tr>
                        <?php endfor; ?>
                    <?php else: ?>
                        <!-- แสดงแถวว่าง 3 แถวถ้าไม่มีข้อมูล -->
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                            <td></td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" id="createPathwayForm">
                <!-- Basic Information -->
                <div class="basic-info">
                    <div class="form-group">
                        <label class="form-label">
                            โครงการ <span class="required">*</span>
                        </label>
                        <select class="form-select" id="project_id" name="project_id" required>
                            <option value="">เลือกโครงการ</option>
                            <?php
                            // เก็บ project_result ไว้เพื่อใช้ใน select
                            if ($projects_result) {
                                mysqli_data_seek($projects_result, 0); // รีเซ็ต pointer
                                while ($project = mysqli_fetch_assoc($projects_result)):
                            ?>
                                    <option value="<?php echo $project['id']; ?>"
                                        <?php echo (isset($_POST['project_id']) && $_POST['project_id'] == $project['id']) || ($project_id == $project['id']) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($project['project_code'] . ' - ' . $project['name']); ?>
                                    </option>
                            <?php
                                endwhile;
                            }
                            ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">
                            ลำดับของห่วงโซ่ <span class="required">*</span>
                        </label>
                        <input type="text" class="form-input" id="pathway_sequence" name="pathway_sequence"
                            placeholder="เช่น 1.1, 1.2, 2.1" required maxlength="10"
                            value="<?php echo htmlspecialchars($_POST['pathway_sequence'] ?? ''); ?>">
                    </div>
                </div>

                <!-- Pathway Steps -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">1</span>
                        ปัจจัยนำเข้า (Input)
                    </label>
                    <input type="text" class="form-input" name="input_description" value="<?php echo htmlspecialchars($_POST['input_description'] ?? ''); ?>">
                    <div class="form-help">ระบุทรัพยากรและปัจจัยนำเข้าที่ใช้ในโครงการ</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">2</span>
                        กิจกรรม (Activities)
                    </label>
                    <input type="text" class="form-input" name="activities_description" value="<?php echo htmlspecialchars($_POST['activities_description'] ?? ''); ?>">
                    <div class="form-help">อธิบายกิจกรรมหลักที่ดำเนินการในโครงการ</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">3</span>
                        ผลผลิต (Output)
                    </label>
                    <input type="text" class="form-input" name="output_description" value="<?php echo htmlspecialchars($_POST['output_description'] ?? ''); ?>">
                    <div class="form-help">ระบุผลผลิตที่เกิดขึ้นโดยตรงจากกิจกรรม</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">4</span>
                        ผู้ใช้ประโยชน์ (User)
                    </label>
                    <input type="text" class="form-input" name="user_description" value="<?php echo htmlspecialchars($_POST['user_description'] ?? ''); ?>">
                    <div class="form-help">ระบุกลุ่มเป้าหมายที่ได้รับประโยชน์จากผลผลิต</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">5</span>
                        ผลลัพธ์ (Outcome)
                    </label>
                    <input type="text" class="form-input" name="outcome_description" value="<?php echo htmlspecialchars($_POST['outcome_description'] ?? ''); ?>">
                    <div class="form-help">ระบุผลลัพธ์ที่เกิดขึ้นจากการนำไปใช้</div>
                </div>

                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">6</span>
                        ผลกระทบ (Impact)
                    </label>
                    <input type="text" class="form-input" name="impact_description" value="<?php echo htmlspecialchars($_POST['impact_description'] ?? ''); ?>">
                    <div class="form-help">อธิบายผลกระทบระยะยาวต่อสังคมและสิ่งแวดล้อม</div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="goBack()">
                        ← ยกเลิก
                    </button>

                    <div class="loading" id="loadingSpinner">
                        <div class="spinner"></div>
                        <span>กำลังบันทึกข้อมูล...</span>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submitBtn">
                        💾 บันทึกข้อมูล
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createPathwayForm');
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loadingSpinner');
            const projectSelect = document.getElementById('project_id');

            // Handle project selection change
            projectSelect.addEventListener('change', function() {
                const selectedProjectId = this.value;
                if (selectedProjectId) {
                    // Redirect to same page with project_id parameter
                    window.location.href = 'impact_pathway.php?project_id=' + selectedProjectId;
                }
            });

            // Handle form submission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                loading.style.display = 'flex';
                submitBtn.style.display = 'none';
            });

            function validateForm() {
                const projectId = document.getElementById('project_id').value;
                const pathwaySequence = document.getElementById('pathway_sequence').value;

                if (!projectId) {
                    alert('กรุณาเลือกโครงการ');
                    return false;
                }

                if (!pathwaySequence.trim()) {
                    alert('กรุณากรอกลำดับของห่วงโซ่');
                    return false;
                }

                return true;
            }
        });

        function goBack() {
            if (confirm('คุณต้องการยกเลิกการสร้าง Social Impact Pathway หรือไม่? ข้อมูลที่กรอกจะไม่ถูกบันทึก')) {
                window.location.href = '../dashboard.php';
            }
        }

        console.log('🔗 Social Impact Pathway Form with data display initialized successfully!');
    </script>
</body>

</html>