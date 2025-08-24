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

// ดึงข้อมูลปี พ.ศ. จากตาราง years
$available_years = [];
$years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC";
$years_result = mysqli_query($conn, $years_query);
if ($years_result) {
    while ($year = mysqli_fetch_assoc($years_result)) {
        $available_years[] = $year;
    }
}

// ดึงข้อมูลต้นทุนที่บันทึกไว้แล้ว
$existing_costs = [];
if ($project_id > 0) {
    $cost_query = "SELECT cost_name, yearly_amounts FROM project_costs WHERE project_id = ? ORDER BY id ASC";
    $cost_stmt = mysqli_prepare($conn, $cost_query);
    mysqli_stmt_bind_param($cost_stmt, 'i', $project_id);
    mysqli_stmt_execute($cost_stmt);
    $cost_result = mysqli_stmt_get_result($cost_stmt);
    
    while ($cost_row = mysqli_fetch_assoc($cost_result)) {
        $yearly_data = json_decode($cost_row['yearly_amounts'], true);
        $existing_costs[] = [
            'name' => $cost_row['cost_name'],
            'amounts' => $yearly_data ? $yearly_data : []
        ];
    }
    mysqli_stmt_close($cost_stmt);
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

        // รับข้อมูลจากฟอร์ม
        $costs = $_POST['cost'] ?? [];
        
        // ใช้ปีจากฐานข้อมูล
        $years = [];
        foreach ($available_years as $year) {
            $years[] = $year['year_be'];
        }

        // ลบข้อมูลเดิมก่อน
        $delete_query = "DELETE FROM project_costs WHERE project_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        // ตรวจสอบข้อมูลและบันทึก
        $saved_count = 0;
        foreach ($costs as $index => $cost_name) {
            if (!empty(trim($cost_name))) {
                // รวบรวมข้อมูลจำนวนเงินตามปี
                $yearly_amounts = [];
                foreach ($years as $year) {
                    $amount = $_POST['value_' . $index . '_' . $year] ?? '';
                    if (!empty($amount) && is_numeric($amount)) {
                        $yearly_amounts[$year] = floatval($amount);
                    }
                }
                
                // บันทึกข้อมูลลงฐานข้อมูล (เฉพาะที่มีข้อมูลจำนวนเงิน)
                if (!empty($yearly_amounts)) {
                    $insert_query = "INSERT INTO project_costs (project_id, cost_name, yearly_amounts, created_by) VALUES (?, ?, ?, ?)";
                    $insert_stmt = mysqli_prepare($conn, $insert_query);
                    $yearly_json = json_encode($yearly_amounts, JSON_UNESCAPED_UNICODE);
                    mysqli_stmt_bind_param($insert_stmt, 'issi', $project_id, trim($cost_name), $yearly_json, $user_id);
                    
                    if (mysqli_stmt_execute($insert_stmt)) {
                        $saved_count++;
                    }
                    mysqli_stmt_close($insert_stmt);
                }
            }
        }

        if ($saved_count > 0) {
            $message = "บันทึกข้อมูลต้นทุน " . $saved_count . " รายการเรียบร้อยแล้ว";
        } else {
            $message = "ไม่มีข้อมูลที่จะบันทึก";
        }

        // ลิงค์ไปยังหน้า with-without.php พร้อมส่ง project_id
        header("Location: with-without.php?project_id=" . $project_id);
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
    <title>ต้นทุน/งบประมาณโครงการ (งบ/ปี) - SROI System</title>
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

        /* Cost Table */
        .cost-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-medium);
            border-radius: 12px;
            overflow: hidden;
        }

        .cost-table th,
        .cost-table td {
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

        .header-sub {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 0.9rem;
            padding: 0.75rem;
        }

        .header-year {
            background-color: #d4edda;
            font-weight: bold;
            font-size: 0.85rem;
            padding: 0.5rem;
            writing-mode: vertical-rl;
            text-orientation: mixed;
        }

        /* Row Headers */
        .row-number {
            background-color: #fff3cd;
            font-weight: bold;
            padding: 0.75rem;
            min-width: 60px;
        }

        .cost-input {
            background-color: #fff3cd;
            padding: 0.5rem;
            min-width: 200px;
        }

        .cost-input input {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.5rem;
            font-size: 0.9rem;
        }

        /* Value Cells */
        .value-cell {
            background-color: #fff;
            padding: 0.25rem;
            min-width: 80px;
        }

        .value-cell input {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 4px;
            padding: 0.4rem;
            text-align: right;
            font-size: 0.85rem;
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

            .cost-table {
                font-size: 0.85rem;
            }

            .header-year {
                writing-mode: horizontal-tb;
                text-orientation: initial;
                padding: 0.4rem;
            }
        }

        @media (max-width: 768px) {
            .cost-table {
                font-size: 0.75rem;
            }

            .value-cell,
            .cost-input {
                min-width: 60px;
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
    <?php 
    $navbar_root = '../';
    include '../navbar.php'; 
    ?>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Form Container -->
        <div class="form-container">
            <h2 class="form-title">ต้นทุน/งบประมาณโครงการ (งบ/ปี)</h2>

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
            <form method="POST" id="costForm">
                <!-- Cost Table -->
                <table class="cost-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="header-main">ลำดับที่</th>
                            <th rowspan="2" class="header-main">ต้นทุน/งบประมาณโครงการ</th>
                            <th colspan="<?php echo count($available_years); ?>" class="header-main">ต้นทุน/งบประมาณโครงการ (งบ/ปี)</th>
                        </tr>
                        <tr>
                            <?php foreach ($available_years as $year): ?>
                                <th class="header-year"><?php echo htmlspecialchars($year['year_display']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <?php
                            // ดึงข้อมูลที่บันทึกไว้แล้วสำหรับแถวนี้
                            $existing_name = '';
                            $existing_amounts = [];
                            if (isset($existing_costs[$i-1])) {
                                $existing_name = $existing_costs[$i-1]['name'];
                                $existing_amounts = $existing_costs[$i-1]['amounts'];
                            }
                            ?>
                            <tr>
                                <td class="row-number"><?php echo $i; ?></td>
                                <td class="cost-input">
                                    <input type="text" name="cost[<?php echo $i; ?>]"
                                        placeholder="ต้นทุน <?php echo $i; ?>"
                                        value="<?php echo htmlspecialchars($existing_name); ?>">
                                </td>
                                <?php foreach ($available_years as $year): ?>
                                    <?php
                                    $existing_value = '';
                                    if (isset($existing_amounts[$year['year_be']])) {
                                        $existing_value = number_format($existing_amounts[$year['year_be']], 2, '.', '');
                                    }
                                    ?>
                                    <td class="value-cell">
                                        <input type="text" name="value_<?php echo $i; ?>_<?php echo $year['year_be']; ?>"
                                               value="<?php echo $existing_value; ?>">
                                    </td>
                                <?php endforeach; ?>
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
            const form = document.getElementById('costForm');
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loadingSpinner');

            // Handle form submission
            form.addEventListener('submit', function(e) {
                // Show loading state
                submitBtn.disabled = true;
                loading.style.display = 'flex';
                submitBtn.style.display = 'none';
            });

            // Auto-format text inputs for numbers (optional formatting)
            const valueInputs = document.querySelectorAll('input[name*="value_"]');
            valueInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    if (this.value && !isNaN(this.value)) {
                        this.value = parseFloat(this.value).toFixed(2);
                    }
                });

                // Allow only numbers and decimal point
                input.addEventListener('input', function() {
                    this.value = this.value.replace(/[^0-9.]/g, '');
                    // Prevent multiple decimal points
                    if ((this.value.match(/\./g) || []).length > 1) {
                        this.value = this.value.slice(0, -1);
                    }
                });
            });
        });

        function goBack() {
            if (confirm('คุณต้องการยกเลิกการกรอกข้อมูลหรือไม่? ข้อมูลที่กรอกจะไม่ถูกบันทึก')) {
                window.history.back();
            }
        }

        console.log('💰 Cost Budget Form initialized successfully!');
    </script>
</body>

</html>