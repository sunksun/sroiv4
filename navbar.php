<?php
// ตรวจสอบการเข้าสู่ระบบ
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    // ไม่แสดง navbar ถ้าไม่ได้ login
    return;
}
?>

<!-- Navigation -->
<nav class="navbar">
    <div class="nav-container">
        <div class="nav-left">
            <div class="logo">
                <img src="<?php echo isset($navbar_root) ? $navbar_root . 'assets/imgs/lru.png' : 'assets/imgs/lru.png'; ?>" alt="LRU Logo" class="logo-img">
                SROI LRU
            </div>
            <ul class="nav-menu">
                <li><a href="<?php echo isset($navbar_root) ? $navbar_root : ''; ?>dashboard.php" class="nav-link">Dashboard</a></li>
                <li><a href="<?php echo isset($navbar_root) ? $navbar_root : ''; ?>project-list.php" class="nav-link">โครงการ</a></li>
                <li><a href="<?php echo isset($navbar_root) ? $navbar_root : ''; ?>reports.php" class="nav-link">รายงาน</a></li>
            </ul>
        </div>
        <?php
        // กำหนด path สำหรับ user-menu.php
        $user_menu_path = isset($navbar_root) ? $navbar_root . 'user-menu.php' : 'user-menu.php';
        include $user_menu_path;
        ?>
    </div>
</nav>

<style>
    /* Navigation */
    .navbar {
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        padding: 1rem 0;
        box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
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

    .nav-left {
        display: flex;
        align-items: center;
        gap: 2rem;
    }

    .logo {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        font-size: 1.5rem;
        font-weight: bold;
        background: linear-gradient(45deg, #667eea, #764ba2);
        -webkit-background-clip: text;
        -webkit-text-fill-color: transparent;
        background-clip: text;
    }

    .logo-img {
        width: 40px;
        height: 40px;
        object-fit: contain;
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
        color: #333;
        text-decoration: none;
        font-weight: 500;
        padding: 0.5rem 1rem;
        border-radius: 8px;
        transition: all 0.3s ease;
        position: relative;
    }

    .nav-link:hover,
    .nav-link.active {
        background: linear-gradient(45deg, #667eea, #764ba2);
        color: white;
        transform: translateY(-2px);
    }

    /* Responsive Design */
    @media (max-width: 768px) {
        .nav-container {
            flex-direction: column;
            gap: 1rem;
            padding: 0 1rem;
        }

        .nav-left {
            flex-direction: column;
            gap: 1rem;
        }

        .nav-menu {
            flex-direction: column;
            gap: 0.5rem;
        }

        .logo {
            font-size: 1.2rem;
        }
    }

    @media (max-width: 480px) {
        .nav-menu {
            flex-wrap: wrap;
            justify-content: center;
        }

        .nav-link {
            font-size: 0.9rem;
            padding: 0.4rem 0.8rem;
        }
    }
</style>