<?php
session_start();

// ทำลาย session ทั้งหมด
$_SESSION = array();

// ถ้ามีการใช้ cookies ให้ลบด้วย
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params["path"],
        $params["domain"],
        $params["secure"],
        $params["httponly"]
    );
}

// ทำลาย session
session_destroy();

// เปลี่ยนเส้นทางไปหน้า login
header("location: login.php");
exit;
