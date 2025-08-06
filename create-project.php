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

// ตั้งค่าตัวแปรสำหรับข้อความแจ้งเตือน
$message = '';
$error = '';

// ดึงข้อมูล session ที่จำเป็น
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$user_email = $_SESSION['email'];
$user_role = $_SESSION['role'];
$user_department = $_SESSION['department'];

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // เพิ่ม logging
        error_log("เริ่มการบันทึกโครงการใหม่...");

        // รับข้อมูลจากฟอร์ม
        $project_code = trim($_POST['project_code']);
        $project_name = trim($_POST['project_name']);
        $organization = trim($_POST['organization']);
        $project_manager = trim($_POST['project_manager']);
        $budget = floatval($_POST['budget']); // แปลงเป็นตัวเลข

        // ตรวจสอบข้อมูลที่จำเป็น
        $required_fields = [
            'project_code' => 'รหัสโครงการ',
            'project_name' => 'ชื่อโครงการ',
            'organization' => 'หน่วยงาน',
            'project_manager' => 'หัวหน้าโครงการ'
        ];

        foreach ($required_fields as $field => $label) {
            if (empty($_POST[$field])) {
                throw new Exception("กรุณากรอก{$label}");
            }
        }

        // ตรวจสอบรหัสโครงการซ้ำ
        $check_query = "SELECT COUNT(*) as count FROM projects WHERE project_code = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, "s", $project_code);
        mysqli_stmt_execute($check_stmt);
        $result = mysqli_stmt_get_result($check_stmt);
        $row = mysqli_fetch_assoc($result);
        if ($row['count'] > 0) {
            mysqli_stmt_close($check_stmt);
            throw new Exception("รหัสโครงการนี้มีอยู่แล้ว กรุณาใช้รหัสอื่น");
        }
        mysqli_stmt_close($check_stmt);

        mysqli_begin_transaction($conn);

        // บันทึกโครงการใหม่
        $query = "
            INSERT INTO projects (
                project_code, name, budget, organization, 
                project_manager, status, created_by
            ) VALUES (?, ?, ?, ?, ?, 'incompleted', ?)
        ";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "ssdsss",
            $project_code,
            $project_name,
            $budget,
            $organization,
            $project_manager,
            $user_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกโครงการ: " . mysqli_error($conn));
        }

        $project_id = mysqli_insert_id($conn);
        mysqli_stmt_close($stmt);

        // บันทึก log
        $message = "สร้างโครงการใหม่: {$project_name} (รหัส: {$project_code})";
        $query = "
            INSERT INTO system_logs (log_level, module, message, user_id, project_id, timestamp)
            VALUES ('INFO', 'PROJECT', ?, ?, ?, NOW())
        ";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sii", $message, $user_id, $project_id);

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึก log: " . mysqli_error($conn));
        }
        mysqli_stmt_close($stmt);

        mysqli_commit($conn);
        $_SESSION['success_message'] = "สร้างโครงการ '{$project_name}' เรียบร้อยแล้ว";

        // Redirect ไปที่หน้ารายการโครงการ
        header("Location: project-list.php");
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }

    // ปิดการเชื่อมต่อ statement ที่อาจค้างอยู่
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้างโครงการใหม่ - SROI System</title>
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
            max-width: 800px;
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
            padding: 2rem;
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--border-color);
        }

        .form-title {
            font-size: 1.5rem;
            color: var(--text-dark);
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            text-align: center;
            justify-content: center;
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

        /* Form Groups */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .required {
            color: var(--danger-color);
        }

        .form-input,
        .form-select,
        .form-textarea {
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
            font-family: inherit;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-textarea {
            resize: vertical;
            min-height: 120px;
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.25rem;
        }

        .form-error {
            font-size: 0.85rem;
            color: var(--danger-color);
            margin-top: 0.25rem;
            display: none;
        }

        .form-group.error .form-input,
        .form-group.error .form-select,
        .form-group.error .form-textarea {
            border-color: var(--danger-color);
        }

        .form-group.error .form-error {
            display: block;
        }

        /* Buttons */
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2rem;
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

        /* Loading States */
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

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .form-actions {
                flex-direction: column;
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

            .page-title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar">
        <div class="nav-container">
            <a href="index.php" class="logo">
                🎯 SROI System
            </a>
            <ul class="nav-menu">
                <li><a href="dashboard.php" class="nav-link">📊 Dashboard</a></li>
                <li><a href="project-list.php" class="nav-link active">📋 โครงการ</a></li>
                <li><a href="impact_pathway/impact_pathway.php" class="nav-link">📈 การวิเคราะห์</a></li>
                <li><a href="reports.php" class="nav-link">📄 รายงาน</a></li>
                <li><a href="settings.php" class="nav-link">⚙️ ตั้งค่า</a></li>
            </ul>
            <?php include 'user-menu.php'; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="breadcrumb">
                <a href="index.php">🏠 หน้าแรก</a>
                <span>›</span>
                <a href="dashboard.php">📊 Dashboard</a>
                <span>›</span>
                <a href="project-list.php">📋 โครงการ</a>
                <span>›</span>
                <span>สร้างโครงการใหม่</span>
            </div>
            <h1 class="page-title">สร้างโครงการใหม่</h1>
            <p class="page-subtitle">เริ่มต้นการประเมินผลกระทบทางสังคม (SROI)</p>
        </div>

        <!-- Form Container -->
        <div class="form-container">
            <h2 class="form-title">
                📝 ข้อมูลโครงการ
            </h2>

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
            <form method="POST" id="createProjectForm">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">
                            รหัสโครงการ <span class="required">*</span>
                        </label>
                        <input type="text" class="form-input" id="project_code" name="project_code"
                            placeholder="กรอกรหัสโครงการ" required maxlength="50"
                            value="<?php echo htmlspecialchars($_POST['project_code'] ?? ''); ?>">
                        <div class="form-help">สามารถกรอกได้ทั้งตัวเลขและตัวอักษร (สูงสุด 50 ตัวอักษร)</div>
                        <div class="form-error">กรุณากรอกรหัสโครงการ</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            หน่วยงาน <span class="required">*</span>
                        </label>
                        <input type="text" class="form-input" id="organization" name="organization"
                            placeholder="หน่วยงาน" required maxlength="300" readonly
                            value="<?php echo htmlspecialchars($user_department); ?>">
                        <div class="form-help">หน่วยงานจากข้อมูลผู้ใช้ (สูงสุด 300 ตัวอักษร)</div>
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label">
                            ชื่อโครงการ <span class="required">*</span>
                        </label>
                        <input type="text" class="form-input" id="project_name" name="project_name"
                            placeholder="ชื่อโครงการภาษาไทย" required maxlength="500"
                            value="<?php echo htmlspecialchars($_POST['project_name'] ?? ''); ?>">
                        <div class="form-help">ชื่อโครงการเต็ม (สูงสุด 500 ตัวอักษร)</div>
                        <div class="form-error">กรุณากรอกชื่อโครงการ</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            หัวหน้าโครงการ <span class="required">*</span>
                        </label>
                        <input type="text" class="form-input" id="project_manager" name="project_manager"
                            placeholder="ชื่อ-นามสกุล หัวหน้าโครงการ" required maxlength="200"
                            value="<?php echo htmlspecialchars($_POST['project_manager'] ?? ''); ?>">
                        <div class="form-help">ชื่อเต็มของหัวหน้าโครงการ (สูงสุด 200 ตัวอักษร)</div>
                        <div class="form-error">กรุณากรอกชื่อหัวหน้าโครงการ</div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">
                            งบประมาณ (บาท) <span class="required">*</span>
                        </label>
                        <input type="number" class="form-input" id="budget" name="budget"
                            placeholder="0" min="0" step="0.01" required
                            value="<?php echo htmlspecialchars($_POST['budget'] ?? ''); ?>">
                        <div class="form-help">งบประมาณรวมของโครงการ (กรอกเฉพาะตัวเลข)</div>
                        <div class="form-error">กรุณากรอกงบประมาณ</div>
                    </div>

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
        // Form validation and submission
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createProjectForm');
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loadingSpinner');

            // Setup form validation
            setupFormValidation();

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
        });

        function setupFormValidation() {
            const inputs = document.querySelectorAll('.form-input, .form-select, .form-textarea');
            inputs.forEach(input => {
                input.addEventListener('blur', validateField);
                input.addEventListener('input', clearFieldError);
            });
        }

        function validateField(e) {
            const field = e.target;
            const formGroup = field.closest('.form-group');

            if (field.hasAttribute('required') && !field.value.trim()) {
                formGroup.classList.add('error');
                return false;
            }

            // ตรวจสอบรหัสโครงการ
            if (field.name === 'project_code') {
                if (field.value.trim().length === 0 || field.value.trim().length > 50) {
                    formGroup.classList.add('error');
                    return false;
                }
            }

            // ตรวจสอบชื่อโครงการ
            if (field.name === 'project_name') {
                if (field.value.trim().length > 500) {
                    formGroup.classList.add('error');
                    return false;
                }
            }

            // ตรวจสอบหน่วยงาน
            if (field.name === 'organization') {
                if (field.value.trim().length > 300) {
                    formGroup.classList.add('error');
                    return false;
                }
            }

            // ตรวจสอบหัวหน้าโครงการ
            if (field.name === 'project_manager') {
                if (field.value.trim().length > 200) {
                    formGroup.classList.add('error');
                    return false;
                }
            }

            // ตรวจสอบงบประมาณ
            if (field.name === 'budget') {
                const budget = parseFloat(field.value);
                if (isNaN(budget) || budget < 0) {
                    formGroup.classList.add('error');
                    return false;
                }
            }

            formGroup.classList.remove('error');
            return true;
        }

        function clearFieldError(e) {
            const formGroup = e.target.closest('.form-group');
            formGroup.classList.remove('error');
        }

        function validateForm() {
            const requiredFields = document.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!validateField({
                        target: field
                    })) {
                    isValid = false;
                }
            });

            return isValid;
        }

        function goBack() {
            if (confirm('คุณต้องการยกเลิกการสร้างโครงการหรือไม่? ข้อมูลที่กรอกจะไม่ถูกบันทึก')) {
                window.location.href = 'project-list.php';
            }
        }

        // Check duplicate project code
        document.getElementById('project_code').addEventListener('blur', function() {
            const code = this.value.trim();
            if (code) {
                fetch('api/check-project-code.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                        },
                        body: JSON.stringify({
                            project_code: code
                        })
                    })
                    .then(response => response.json())
                    .then(data => {
                        const formGroup = this.closest('.form-group');
                        if (data.exists) {
                            formGroup.classList.add('error');
                            formGroup.querySelector('.form-error').textContent = 'รหัสโครงการนี้มีอยู่แล้ว';
                            formGroup.querySelector('.form-error').style.display = 'block';
                        } else {
                            formGroup.classList.remove('error');
                        }
                    })
                    .catch(error => {
                        console.error('Error checking project code:', error);
                    });
            }
        });

        console.log('🎯 Create Project Form initialized successfully!');
    </script>
</body>

</html>