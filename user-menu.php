<?php
// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // ไม่แสดง user menu ถ้าไม่ได้ login
    return;
}

// ใช้ข้อมูลจาก session
$user_full_name = $_SESSION['full_name'] ?? $_SESSION['username'] ?? '';
$user_email = $_SESSION['email'] ?? '';
$user_role = $_SESSION['role'] ?? '';
$username = $_SESSION['username'] ?? '';

// สร้างชื่อย่อสำหรับ avatar
$initials = '';
if (!empty($user_full_name)) {
    $name_parts = explode(' ', trim($user_full_name));
    $initials = strtoupper(substr($name_parts[0], 0, 1));
    if (count($name_parts) > 1) {
        $initials .= strtoupper(substr(end($name_parts), 0, 1));
    }
} else {
    $initials = strtoupper(substr($username, 0, 2));
}
?>

<!-- Bootstrap JavaScript Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="user-menu">
    <div class="dropdown">
        <button class="user-avatar" type="button" id="userMenuButton" data-bs-toggle="dropdown" aria-expanded="false" title="<?php echo htmlspecialchars($user_full_name); ?>">
            <?php echo $initials; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenuButton">
            <li class="dropdown-header">
                <div class="fw-bold"><?php echo htmlspecialchars($user_full_name); ?></div>
                <small class="text-muted"><?php echo htmlspecialchars($user_email); ?></small>
            </li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item" href="profile.php">
                    <i class="fas fa-user-circle me-2"></i>จัดการข้อมูลส่วนตัว
                </a></li>
            <li><a class="dropdown-item" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-2"></i>Dashboard
                </a></li>
            <li>
                <hr class="dropdown-divider">
            </li>
            <li><a class="dropdown-item text-danger" href="logout.php">
                    <i class="fas fa-sign-out-alt me-2"></i>ออกจากระบบ
                </a></li>
        </ul>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const dropdownToggle = document.getElementById('userMenuButton');
        if (dropdownToggle) {
            const dropdown = new bootstrap.Dropdown(dropdownToggle);
        }
    });
</script>

<style>
    .user-menu {
        display: flex;
        align-items: center;
        gap: 1rem;
    }


    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        border: none;
        font-weight: bold;
        font-size: 0.85rem;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
    }

    .user-avatar:hover {
        transform: scale(1.05);
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .dropdown-menu {
        min-width: 250px;
        border: none;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15);
        border-radius: 12px;
        padding: 0.5rem 0;
    }

    .dropdown-header {
        padding: 0.75rem 1rem;
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border-radius: 12px 12px 0 0;
        margin: -0.5rem -0rem 0.5rem -0rem;
    }

    .dropdown-header .fw-bold {
        font-size: 0.95rem;
        margin-bottom: 2px;
    }

    .dropdown-header .text-muted {
        color: rgba(255, 255, 255, 0.8) !important;
        font-size: 0.8rem;
    }

    .dropdown-item {
        padding: 0.6rem 1rem;
        font-size: 0.9rem;
        transition: all 0.3s ease;
    }

    .dropdown-item:hover {
        background: linear-gradient(90deg, rgba(102, 126, 234, 0.1), transparent);
        color: #667eea;
    }

    .dropdown-item.text-danger:hover {
        background: linear-gradient(90deg, rgba(220, 53, 69, 0.1), transparent);
        color: #dc3545;
    }

    .dropdown-item i {
        width: 16px;
        text-align: center;
    }

    /* ปรับแต่งสำหรับ mobile */
    @media (max-width: 767px) {
        .dropdown-menu {
            min-width: 200px;
        }
    }
</style>