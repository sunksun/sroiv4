<?php
session_start();
require_once "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // รับค่าจากฟอร์ม
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $confirm_password = mysqli_real_escape_string($conn, $_POST['confirm_password']);
    $fullname = mysqli_real_escape_string($conn, $_POST['fullname']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $department = mysqli_real_escape_string($conn, $_POST['department']);

    // ตรวจสอบความถูกต้องของข้อมูล
    $errors = array();

    // ตรวจสอบ username
    if (empty($username)) {
        $errors[] = "กรุณากรอกชื่อผู้ใช้";
    } elseif (!preg_match('/^[a-zA-Z\.]+$/', $username)) {
        $errors[] = "ชื่อผู้ใช้ต้องเป็นภาษาอังกฤษและสามารถมีจุด (.) ได้";
    }

    // ตรวจสอบ password
    if (empty($password)) {
        $errors[] = "กรุณากรอกรหัสผ่าน";
    } elseif (strlen($password) < 6) {
        $errors[] = "รหัสผ่านต้องมีความยาวอย่างน้อย 6 ตัวอักษร";
    }

    // ตรวจสอบ confirm password
    if ($password != $confirm_password) {
        $errors[] = "รหัสผ่านไม่ตรงกัน";
    }

    // ตรวจสอบ email
    if (empty($email)) {
        $errors[] = "กรุณากรอกอีเมล";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "รูปแบบอีเมลไม่ถูกต้อง";
    }

    // ตรวจสอบ fullname
    if (empty($fullname)) {
        $errors[] = "กรุณากรอกชื่อ-นามสกุล";
    }

    // ตรวจสอบ department
    if (empty($department)) {
        $errors[] = "กรุณาเลือกคณะ";
    }

    // ตรวจสอบว่า username ซ้ำหรือไม่
    $check_username = "SELECT * FROM users WHERE username = ?";
    if ($stmt = mysqli_prepare($conn, $check_username)) {
        mysqli_stmt_bind_param($stmt, "s", $username);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "ชื่อผู้ใช้นี้ถูกใช้งานแล้ว";
        }
        mysqli_stmt_close($stmt);
    }

    // ตรวจสอบว่า email ซ้ำหรือไม่
    $check_email = "SELECT * FROM users WHERE email = ?";
    if ($stmt = mysqli_prepare($conn, $check_email)) {
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        if (mysqli_num_rows($result) > 0) {
            $errors[] = "อีเมลนี้ถูกใช้งานแล้ว";
        }
        mysqli_stmt_close($stmt);
    }

    // ถ้าไม่มีข้อผิดพลาด ดำเนินการบันทึกข้อมูล
    if (empty($errors)) {
        // เข้ารหัสรหัสผ่าน
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);

        // เพิ่มข้อมูลลงในฐานข้อมูล
        $sql = "INSERT INTO users (username, password_hash, full_name_th, email, department, role, is_active) VALUES (?, ?, ?, ?, ?, 'teacher', 1)";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "sssss", $username, $hashed_password, $fullname, $email, $department);

            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_msg'] = "ลงทะเบียนสำเร็จ กรุณาเข้าสู่ระบบ";
                header("location: login.php");
                exit();
            } else {
                $register_err = "เกิดข้อผิดพลาด กรุณาลองใหม่อีกครั้ง";
            }
            mysqli_stmt_close($stmt);
        }
    } else {
        $register_err = implode("<br>", $errors);
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ลงทะเบียน</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .register-container {
            max-width: 600px;
            margin: 50px auto;
            padding: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="register-container">
            <h2 class="text-center mb-4">ลงทะเบียนผู้ใช้งาน</h2>

            <?php
            if (!empty($register_err)) {
                echo '<div class="alert alert-danger">' . $register_err . '</div>';
            }
            ?>

            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="needs-validation" novalidate>
                <div class="mb-3">
                    <label for="username" class="form-label">ชื่อผู้ใช้ (ภาษาอังกฤษและจุด) *</label>
                    <input type="text" class="form-control" id="username" name="username" pattern="[A-Za-z\.]+" required>
                    <div class="form-text">ใช้ตัวอักษรภาษาอังกฤษและสามารถมีจุด (.) เช่น sunksun.lap</div>
                    <div class="invalid-feedback">
                        กรุณากรอกชื่อผู้ใช้เป็นภาษาอังกฤษ (สามารถมีจุดได้)
                    </div>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label">รหัสผ่าน *</label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback">
                        กรุณากรอกรหัสผ่าน
                    </div>
                </div>

                <div class="mb-3">
                    <label for="confirm_password" class="form-label">ยืนยันรหัสผ่าน *</label>
                    <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    <div class="invalid-feedback">
                        กรุณายืนยันรหัสผ่าน
                    </div>
                </div>

                <div class="mb-3">
                    <label for="fullname" class="form-label">ชื่อ-นามสกุล *</label>
                    <input type="text" class="form-control" id="fullname" name="fullname" required>
                    <div class="invalid-feedback">
                        กรุณากรอกชื่อ-นามสกุล
                    </div>
                </div>

                <div class="mb-3">
                    <label for="department" class="form-label">คณะ *</label>
                    <select class="form-select" id="department" name="department" required>
                        <option value="">เลือกคณะ</option>
                        <option value="คณะครุศาสตร์">คณะครุศาสตร์</option>
                        <option value="คณะวิทยาศาสตร์และเทคโนโลยี">คณะวิทยาศาสตร์และเทคโนโลยี</option>
                        <option value="คณะมนุษยศาสตร์และสังคมศาสตร์">คณะมนุษยศาสตร์และสังคมศาสตร์</option>
                        <option value="คณะวิทยาการจัดการ">คณะวิทยาการจัดการ</option>
                        <option value="คณะเทคโนโลยีอุตสาหกรรม">คณะเทคโนโลยีอุตสาหกรรม</option>
                    </select>
                    <div class="invalid-feedback">
                        กรุณาเลือกคณะ
                    </div>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label">อีเมล *</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                    <div class="invalid-feedback">
                        กรุณากรอกอีเมลให้ถูกต้อง
                    </div>
                </div>

                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary">ลงทะเบียน</button>
                    <a href="login.php" class="btn btn-secondary">กลับไปหน้าเข้าสู่ระบบ</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Form Validation Script -->
    <script>
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
    </script>
</body>

</html>