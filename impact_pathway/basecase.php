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

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // รับข้อมูลจากฟอร์ม
        $beneficiaries = $_POST['beneficiary'] ?? [];

        // ตรวจสอบข้อมูลและบันทึก
        foreach ($beneficiaries as $index => $beneficiary) {
            if (!empty($beneficiary)) {
                $attribution = floatval($_POST['attribution_' . $index] ?? 0);
                $deadweight = floatval($_POST['deadweight_' . $index] ?? 0);
                $displacement = floatval($_POST['displacement_' . $index] ?? 0);

                // คำนวณสัดส่วนผลกระทบ = 1 - (Attribution + Deadweight + Displacement) / 100
                $impact_proportion = 1 - (($attribution + $deadweight + $displacement) / 100);
                $impact_percentage = $impact_proportion * 100;

                // บันทึกข้อมูลลงฐานข้อมูล (ใส่โค้ดบันทึกตรงนี้)
            }
        }

        $message = "บันทึกข้อมูลเรียบร้อยแล้ว";
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
    <title>สัดส่วนผลกระทบจากโครงการ - SROI System</title>
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
            max-width: 1400px;
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

        /* Impact Table */
        .impact-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-medium);
            border-radius: 12px;
            overflow: hidden;
        }

        .impact-table th,
        .impact-table td {
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

        .header-attribution {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 1rem;
        }

        .header-deadweight {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 1rem;
        }

        .header-displacement {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 1rem;
        }

        .header-result {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 1rem;
            color: #155724;
        }

        /* Row Headers - Beneficiary Column */
        .beneficiary-header {
            background-color: #e7f3ff;
            font-weight: bold;
            padding: 0.75rem;
            text-align: left;
            min-width: 150px;
            color: #0056b3;
        }

        /* Value Cells */
        .value-cell {
            background-color: #fff9c4;
            padding: 0.5rem;
            min-width: 100px;
        }

        .value-cell input {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem;
            text-align: center;
            font-size: 0.9rem;
        }

        .value-cell input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 2px rgba(102, 126, 234, 0.1);
        }

        /* Result Cell */
        .result-cell {
            background-color: #e8f5e8;
            padding: 0.5rem;
            font-weight: bold;
            color: #155724;
            font-size: 1rem;
        }

        /* Formula Display */
        .formula-display {
            background-color: #e9ecef;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 2rem;
            text-align: center;
            font-family: 'Courier New', monospace;
            font-size: 1.1rem;
            color: var(--text-dark);
            border: 2px solid var(--border-color);
        }

        .formula-title {
            font-weight: bold;
            margin-bottom: 0.5rem;
            font-family: 'Segoe UI', sans-serif;
            color: var(--primary-color);
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

            .impact-table {
                font-size: 0.85rem;
            }
        }

        @media (max-width: 768px) {
            .impact-table {
                font-size: 0.75rem;
            }

            .value-cell,
            .beneficiary-header {
                min-width: 80px;
            }

            .value-cell input {
                font-size: 0.8rem;
                padding: 0.3rem;
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

            .formula-display {
                font-size: 0.9rem;
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
            <h2 class="form-title">สัดส่วนผลกระทบจากโครงการ</h2>

            <!-- Formula Display -->
            <div class="formula-display">
                <div class="formula-title">สูตรคำนวณ:</div>
                สัดส่วนผลกระทบจากโครงการ = 1 - (Attribution + Deadweight + Displacement) / 100
            </div>

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
            <form method="POST" id="impactForm">
                <!-- Impact Table -->
                <table class="impact-table">
                    <thead>
                        <tr>
                            <th class="header-main">ผลประโยชน์</th>
                            <th class="header-attribution">Attribution</th>
                            <th class="header-deadweight">Deadweight</th>
                            <th class="header-displacement">Displacement</th>
                            <th class="header-result">สัดส่วนผลกระทบจาก<br>โครงการ ฯ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <tr>
                                <td class="beneficiary-header">ผลประโยชน์ <?php echo $i; ?></td>
                                <td class="value-cell">
                                    <input type="number" name="attribution_<?php echo $i; ?>"
                                        step="0.01" min="0" max="100"
                                        value="<?php echo $i == 1 ? '20' : '0'; ?>"
                                        onchange="calculateImpact(<?php echo $i; ?>)">
                                </td>
                                <td class="value-cell">
                                    <input type="number" name="deadweight_<?php echo $i; ?>"
                                        step="0.01" min="0" max="100"
                                        value="<?php echo $i == 1 ? '10' : '0'; ?>"
                                        onchange="calculateImpact(<?php echo $i; ?>)">
                                </td>
                                <td class="value-cell">
                                    <input type="number" name="displacement_<?php echo $i; ?>"
                                        step="0.01" min="0" max="100"
                                        value="<?php echo $i == 1 ? '30' : '0'; ?>"
                                        onchange="calculateImpact(<?php echo $i; ?>)">
                                </td>
                                <td class="result-cell">
                                    <span id="result_<?php echo $i; ?>"><?php echo $i == 1 ? '40%' : '100%'; ?></span>
                                </td>
                            </tr>
                        <?php endfor; ?>
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
            const form = document.getElementById('impactForm');
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loadingSpinner');

            // Handle form submission
            form.addEventListener('submit', function(e) {
                // Show loading state
                submitBtn.disabled = true;
                loading.style.display = 'flex';
                submitBtn.style.display = 'none';
            });

            // Calculate all impacts on page load
            for (let i = 1; i <= 10; i++) {
                calculateImpact(i);
            }
        });

        function calculateImpact(row) {
            const attribution = parseFloat(document.querySelector(`input[name="attribution_${row}"]`).value) || 0;
            const deadweight = parseFloat(document.querySelector(`input[name="deadweight_${row}"]`).value) || 0;
            const displacement = parseFloat(document.querySelector(`input[name="displacement_${row}"]`).value) || 0;

            // คำนวณตามสูตร: 1 - (Attribution + Deadweight + Displacement) / 100
            const impactProportion = 1 - ((attribution + deadweight + displacement) / 100);
            const impactPercentage = Math.max(0, impactProportion * 100); // ไม่ให้ติดลบ

            // แสดงผลลัพธ์
            document.getElementById(`result_${row}`).textContent = impactPercentage.toFixed(0) + '%';

            // เปลี่ยนสีตามค่า
            const resultElement = document.getElementById(`result_${row}`);
            if (impactPercentage >= 70) {
                resultElement.style.color = '#155724'; // เขียว
            } else if (impactPercentage >= 40) {
                resultElement.style.color = '#856404'; // เหลือง
            } else {
                resultElement.style.color = '#721c24'; // แดง
            }
        }

        function goBack() {
            if (confirm('คุณต้องการยกเลิกการกรอกข้อมูลหรือไม่? ข้อมูลที่กรอกจะไม่ถูกบันทึก')) {
                window.history.back();
            }
        }

        console.log('📊 Impact Proportion Calculation Form initialized successfully!');
    </script>
</body>

</html>