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

// ดึงข้อมูลผลประโยชน์จากตาราง project_impact_ratios
$benefit_data = [];
if ($project_id > 0) {
    $benefit_query = "SELECT benefit_detail, benefit_number FROM project_impact_ratios WHERE project_id = ? ORDER BY benefit_number ASC";
    $benefit_stmt = mysqli_prepare($conn, $benefit_query);
    mysqli_stmt_bind_param($benefit_stmt, 'i', $project_id);
    mysqli_stmt_execute($benefit_stmt);
    $benefit_result = mysqli_stmt_get_result($benefit_stmt);
    
    while ($benefit_row = mysqli_fetch_assoc($benefit_result)) {
        if (!empty($benefit_row['benefit_detail'])) {
            $benefit_data[] = $benefit_row['benefit_detail'];
        }
    }
    mysqli_stmt_close($benefit_stmt);
}

// ดึงข้อมูล with-without ที่บันทึกไว้แล้ว
$existing_data = [];
if ($project_id > 0) {
    $existing_query = "SELECT benefit_detail, with_value, without_value FROM project_with_without WHERE project_id = ? ORDER BY id ASC";
    $existing_stmt = mysqli_prepare($conn, $existing_query);
    mysqli_stmt_bind_param($existing_stmt, 'i', $project_id);
    mysqli_stmt_execute($existing_stmt);
    $existing_result = mysqli_stmt_get_result($existing_stmt);
    
    while ($existing_row = mysqli_fetch_assoc($existing_result)) {
        $existing_data[$existing_row['benefit_detail']] = [
            'with' => $existing_row['with_value'],
            'without' => $existing_row['without_value']
        ];
    }
    mysqli_stmt_close($existing_stmt);
}

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // ตรวจสอบสิทธิ์เข้าถึงโครงการ
        $check_query = "SELECT id FROM projects WHERE id = ? AND created_by = ?";
        $check_stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
        mysqli_stmt_execute($check_stmt);
        $check_result = mysqli_stmt_get_result($check_stmt);
        
        if (mysqli_num_rows($check_result) == 0) {
            throw new Exception("คุณไม่มีสิทธิ์เข้าถึงโครงการนี้");
        }
        mysqli_stmt_close($check_stmt);

        // ลบข้อมูลเดิมก่อน
        $delete_query = "DELETE FROM project_with_without WHERE project_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        // บันทึกข้อมูลใหม่
        $saved_count = 0;
        foreach ($benefit_data as $index => $benefit_detail) {
            $with_value = $_POST['with_' . ($index + 1)] ?? '';
            $without_value = $_POST['without_' . ($index + 1)] ?? '';
            
            // บันทึกข้อมูลลงฐานข้อมูล (เฉพาะที่มีข้อมูลอย่างน้อยหนึ่งช่อง)
            if (!empty($with_value) || !empty($without_value)) {
                $insert_query = "INSERT INTO project_with_without (project_id, benefit_detail, with_value, without_value, created_by) VALUES (?, ?, ?, ?, ?)";
                $insert_stmt = mysqli_prepare($conn, $insert_query);
                mysqli_stmt_bind_param($insert_stmt, 'isssi', $project_id, $benefit_detail, $with_value, $without_value, $user_id);
                
                if (mysqli_stmt_execute($insert_stmt)) {
                    $saved_count++;
                }
                mysqli_stmt_close($insert_stmt);
            }
        }

        if ($saved_count > 0) {
            $message = "บันทึกข้อมูล " . $saved_count . " รายการเรียบร้อยแล้ว";
        } else {
            $message = "ไม่มีข้อมูลที่จะบันทึก";
        }

        // ลิงค์ไปยังหน้า sroi-expost/index.php พร้อมส่ง project_id
        header("Location: ../sroi-expost/index.php?project_id=" . $project_id);
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เปรียบเทียบกรณี มี-ไม่มี โครงการ - SROI System</title>
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

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--border-color);
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

        /* Comparison Table */
        .comparison-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-medium);
            border-radius: 12px;
            overflow: hidden;
        }

        .comparison-table th,
        .comparison-table td {
            border: 2px solid #333;
            text-align: center;
            vertical-align: middle;
        }

        /* Header Styles */
        .header-main {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 1rem;
            padding: 1rem;
        }

        .header-with {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 1rem;
            padding: 1rem;
            color: #155724;
        }

        .header-without {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 1rem;
            padding: 1rem;
            color: #155724;
        }

        /* Row Headers - Beneficiary Column */
        .beneficiary-header {
            background-color: #e7f3ff;
            font-weight: bold;
            padding: 0.75rem;
            text-align: left;
            min-width: 200px;
            color: #0056b3;
        }

        /* Value Cells */
        .value-cell {
            background-color: #fff9c4;
            padding: 0.5rem;
            min-width: 200px;
        }

        .value-cell input {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 0.9rem;
            height: 40px;
        }

        .value-cell input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
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
        @media (max-width: 1200px) {
            .main-container {
                padding: 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .comparison-table {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 768px) {
            .comparison-table {
                font-size: 0.75rem;
            }

            .value-cell,
            .beneficiary-header {
                min-width: 150px;
            }

            .value-cell input {
                height: 35px;
                font-size: 0.8rem;
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
                <li><a href="project-list.php" class="nav-link">📋 โครงการ</a></li>
                <li><a href="analysis.php" class="nav-link active">📈 การวิเคราะห์</a></li>
                <li><a href="reports.php" class="nav-link">📄 รายงาน</a></li>
                <li><a href="settings.php" class="nav-link">⚙️ ตั้งค่า</a></li>
            </ul>
            <?php include '../user-menu.php'; ?>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Form Container -->
        <div class="form-container">
            <h2 class="form-title">เปรียบเทียบกรณี มี-ไม่มี โครงการ</h2>

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
            <form method="POST" id="comparisonForm">
                <!-- Comparison Table -->
                <table class="comparison-table">
                    <thead>
                        <tr>
                            <th class="header-main">ผลประโยชน์</th>
                            <th class="header-with">กรณีที่ "มี" (With)</th>
                            <th class="header-without">กรณีที่ "ไม่มี" (Without)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        // แสดงเฉพาะข้อมูลผลประโยชน์ที่มีในฐานข้อมูล
                        if (count($benefit_data) > 0) {
                            foreach ($benefit_data as $index => $benefit_name): 
                        ?>
                            <tr>
                                <td class="beneficiary-header">
                                    <?php echo htmlspecialchars($benefit_name); ?>
                                </td>
                                <td class="value-cell">
                                    <input type="text" name="with_<?php echo $index + 1; ?>" 
                                           value="<?php echo isset($existing_data[$benefit_name]) ? htmlspecialchars($existing_data[$benefit_name]['with']) : ''; ?>" 
                                           placeholder="">
                                </td>
                                <td class="value-cell">
                                    <input type="text" name="without_<?php echo $index + 1; ?>" 
                                           value="<?php echo isset($existing_data[$benefit_name]) ? htmlspecialchars($existing_data[$benefit_name]['without']) : ''; ?>" 
                                           placeholder="">
                                </td>
                            </tr>
                        <?php 
                            endforeach;
                        } else {
                            // หากไม่มีข้อมูลในฐานข้อมูล แสดงข้อความแจ้ง
                        ?>
                            <tr>
                                <td colspan="3" class="text-center" style="padding: 2rem; color: #6c757d;">
                                    ไม่พบข้อมูลผลประโยชน์ กรุณาเพิ่มข้อมูลในส่วนการวิเคราะห์ Impact Pathway ก่อน
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>

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
            const form = document.getElementById('comparisonForm');
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loadingSpinner');

            // Handle form submission
            form.addEventListener('submit', function(e) {
                // Show loading state
                submitBtn.disabled = true;
                loading.style.display = 'flex';
                submitBtn.style.display = 'none';
            });
        });

        function goBack() {
            if (confirm('คุณต้องการยกเลิกการกรอกข้อมูลหรือไม่? ข้อมูลที่กรอกจะไม่ถูกบันทึก')) {
                window.history.back();
            }
        }

        console.log('⚖️ With-Without Comparison Form initialized successfully!');
    </script>
</body>

</html>