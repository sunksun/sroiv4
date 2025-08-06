<?php
session_start();
require_once "config.php";

// CSRF Token Generation
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// ตรวจสอบการล็อกอิน
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: login.php");
    exit;
}

// ดึงข้อมูลผู้ใช้จาก session
$user_id = $_SESSION['user_id'];

// ดึงข้อมูลล่าสุดจากฐานข้อมูล
$sql = "SELECT * FROM users WHERE id = ?";
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) == 1) {
            $user = mysqli_fetch_assoc($result);
        }
    }
    mysqli_stmt_close($stmt);
}

// จัดการการอัพเดทข้อมูล
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header("HTTP/1.1 403 Forbidden");
        echo "Invalid CSRF token";
        exit;
    }
    $errors = array();

    // รับและตรวจสอบข้อมูล
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);

    // ตรวจสอบอีเมล
    if (empty($email)) {
        $errors[] = "กรุณากรอกอีเมล";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    } else {
        // ตรวจสอบว่าอีเมลซ้ำหรือไม่ (ยกเว้นอีเมลปัจจุบันของผู้ใช้)
        $check_email = "SELECT id FROM users WHERE email = ? AND id != ?";
        if ($stmt = mysqli_prepare($conn, $check_email)) {
            mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
            mysqli_stmt_execute($stmt);
            $result = mysqli_stmt_get_result($stmt);
            if (mysqli_num_rows($result) > 0) {
                $errors[] = "อีเมลนี้ถูกใช้งานแล้ว";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // ถ้าไม่มีข้อผิดพลาด อัพเดทข้อมูล
    if (empty($errors)) {
        $update_sql = "UPDATE users SET full_name_th = ?, email = ?, department = ? WHERE id = ?";
        if ($stmt = mysqli_prepare($conn, $update_sql)) {
            mysqli_stmt_bind_param($stmt, "sssi", $fullname, $email, $department, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $success_msg = "อัพเดทข้อมูลสำเร็จ";
                // อัพเดทข้อมูลในตัวแปร user
                $user['full_name_th'] = $fullname;
                $user['email'] = $email;
                $user['department'] = $department;
            } else {
                $errors[] = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>โปรไฟล์ผู้ใช้ - SROI Management System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
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
            margin: 0;
            padding: 0;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
            position: relative;
        }

        .nav-link:hover,
        .nav-link.active {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .profile-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 2rem;
            background-color: white;
            border-radius: 15px;
            box-shadow: var(--shadow-medium);
        }

        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding: 2rem;
            background: linear-gradient(135deg, rgba(102, 126, 234, 0.1), rgba(118, 75, 162, 0.1));
            border-radius: 12px;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-size: 48px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            transition: transform 0.3s ease;
        }

        .profile-avatar:hover {
            transform: scale(1.1);
        }

        .user-role-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 500;
            margin-top: 0.75rem;
            background: var(--primary-color);
            color: white;
            box-shadow: var(--shadow-light);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            box-shadow: var(--shadow-light);
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .alert {
            border: none;
            border-radius: 10px;
            box-shadow: var(--shadow-light);
        }

        @media (max-width: 768px) {
            .nav-container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 1rem;
            }

            .nav-menu {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                box-shadow: var(--shadow-medium);
                border-radius: 0 0 15px 15px;
                padding: 1rem;
                flex-direction: column;
            }

            .nav-menu.mobile-open {
                display: flex;
            }

            .profile-container {
                margin: 1rem;
                padding: 1rem;
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
            <ul class="nav-menu" role="navigation">
                <li><a href="dashboard.php" class="nav-link">📊 Dashboard</a></li>
                <li><a href="project-list.php" class="nav-link">📋 โครงการ</a></li>
                <li><a href="impact_pathway/impact_pathway.php" class="nav-link">📈 การวิเคราะห์</a></li>
                <li><a href="reports.php" class="nav-link">📄 รายงาน</a></li>
                <li><a href="settings.php" class="nav-link">⚙️ ตั้งค่า</a></li>
            </ul>
            <?php include 'user-menu.php'; ?>
        </div>
    </nav>

    <div class="container position-relative">
        <a href="#" onclick="history.back(); return false;" class="back-button" title="กลับไปหน้าก่อนหน้า">
            <i class="fas fa-arrow-left"></i>
            <span>กลับ</span>
        </a>

        <div class="profile-container" role="main">
            <div class="profile-header">
                <div class="profile-avatar">
                    <i class="fas fa-user"></i>
                </div>
                <h2><?php echo htmlspecialchars($user['full_name_th']); ?></h2>
                <span class="user-role-badge badge bg-primary"><?php echo htmlspecialchars($user['role']); ?></span>
            </div>

            <?php
            if (!empty($errors)) {
                foreach ($errors as $error) {
                    echo '<div class="alert alert-danger">' . $error . '</div>';
                }
            }
            if (isset($success_msg)) {
                echo '<div class="alert alert-success">' . $success_msg . '</div>';
            }
            ?>

            <form method="post" class="needs-validation" novalidate>
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="username" class="form-label">ชื่อผู้ใช้</label>
                        <input type="text" class="form-control" id="username" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        <div class="form-text">ไม่สามารถเปลี่ยนชื่อผู้ใช้ได้</div>
                    </div>
                    <div class="col-md-6">
                        <label for="fullname" class="form-label">ชื่อ-นามสกุล *</label>
                        <input type="text" class="form-control" id="fullname" name="fullname" value="<?php echo htmlspecialchars($user['full_name_th']); ?>" required>
                        <div class="invalid-feedback">กรุณากรอกชื่อ-นามสกุล</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">อีเมล *</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                        <div class="invalid-feedback">กรุณากรอกอีเมลให้ถูกต้อง</div>
                    </div>
                    <div class="col-md-6">
                        <label for="department" class="form-label">คณะ *</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="">เลือกคณะ</option>
                            <?php
                            $departments = [
                                "คณะครุศาสตร์",
                                "คณะวิทยาศาสตร์และเทคโนโลยี",
                                "คณะมนุษยศาสตร์และสังคมศาสตร์",
                                "คณะวิทยาการจัดการ",
                                "คณะเทคโนโลยีอุตสาหกรรม"
                            ];
                            foreach ($departments as $dept) {
                                $selected = ($user['department'] == $dept) ? 'selected' : '';
                                echo "<option value=\"" . htmlspecialchars($dept) . "\" $selected>" . htmlspecialchars($dept) . "</option>";
                            }
                            ?>
                        </select>
                        <div class="invalid-feedback">กรุณาเลือกคณะ</div>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">วันที่สมัครสมาชิก</label>
                        <input type="text" class="form-control" value="<?php echo date('d/m/Y H:i', strtotime($user['created_at'])); ?>" disabled>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">เข้าสู่ระบบล่าสุด</label>
                        <input type="text" class="form-control" value="<?php echo $user['last_login'] ? date('d/m/Y H:i', strtotime($user['last_login'])) : '-'; ?>" disabled>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>บันทึกการเปลี่ยนแปลง
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-home me-2"></i>กลับหน้าหลัก
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable Bootstrap form validation
        (function() {
            'use strict'
            var forms = document.querySelectorAll('.needs-validation')
            Array.prototype.slice.call(forms)
                .forEach(function(form) {
                    form.addEventListener('submit', function(event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
        })()

        // Mobile menu toggle
        function toggleMobileMenu() {
            const navMenu = document.querySelector('.nav-menu')
            navMenu.classList.toggle('mobile-open')
        }

        // Add mobile menu toggle button
        const navContainer = document.querySelector('.nav-container')
        const mobileToggle = document.createElement('button')
        mobileToggle.className = 'mobile-menu-toggle'
        mobileToggle.innerHTML = '☰'
        mobileToggle.setAttribute('aria-label', 'Toggle navigation menu')
        mobileToggle.onclick = toggleMobileMenu
        navContainer.insertBefore(mobileToggle, navContainer.querySelector('.nav-menu'))

        // Accessibility enhancements
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Tab') {
                document.body.classList.add('keyboard-navigation')
            }
        })

        document.addEventListener('mousedown', function() {
            document.body.classList.remove('keyboard-navigation')
        })
    </script>
</body>

</html>