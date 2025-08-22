<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>การประเมินผลกระทบทางสังคม สำหรับโครงการยุทธศาสตร์ มหาวิทยาลัยราชภัฏเลย</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Sarabun:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Sarabun', sans-serif;
            overflow-x: hidden;
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-weight: 700;
            color: #667eea !important;
            font-size: 1.5rem;
        }

        .nav-link {
            font-weight: 500;
            color: #495057 !important;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .nav-link:hover {
            color: #667eea !important;
        }

        .btn-login {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
            border-radius: 25px;
            padding: 8px 25px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
            color: white;
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 120px 0 80px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 100" fill="%23ffffff" opacity="0.1"><polygon points="1000,100 1000,0 0,100"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            line-height: 1.2;
        }

        .hero-subtitle {
            font-size: 1.3rem;
            font-weight: 300;
            margin-bottom: 2rem;
            opacity: 0.9;
        }

        .btn-hero {
            background: white;
            color: #667eea;
            border: none;
            border-radius: 50px;
            padding: 15px 40px;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
            margin: 10px;
        }

        .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            color: #667eea;
        }

        .btn-hero-outline {
            background: transparent;
            color: white;
            border: 2px solid white;
        }

        .btn-hero-outline:hover {
            background: white;
            color: #667eea;
        }

        /* Features Section */
        .features-section {
            padding: 100px 0;
            background: #f8f9fa;
        }

        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 40px 30px;
            text-align: center;
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            border-radius: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            margin: 0 auto 25px;
            color: white;
        }

        .feature-icon.blue {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .feature-icon.green {
            background: linear-gradient(135deg, #56ab2f 0%, #a8e6cf 100%);
        }

        .feature-icon.orange {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }

        .feature-icon.purple {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
        }

        .feature-title {
            font-size: 1.4rem;
            font-weight: 600;
            margin-bottom: 15px;
            color: #2d3748;
        }

        .feature-description {
            color: #718096;
            line-height: 1.6;
        }

        /* About Section */
        .about-section {
            padding: 100px 0;
            background: white;
        }

        .section-title {
            font-size: 2.5rem;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
            color: #2d3748;
        }

        .section-subtitle {
            font-size: 1.2rem;
            text-align: center;
            color: #718096;
            margin-bottom: 60px;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
        }

        .about-content {
            font-size: 1.1rem;
            line-height: 1.8;
            color: #4a5568;
        }

        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 80px 0;
        }

        .stat-item {
            text-align: center;
            margin-bottom: 30px;
        }

        .stat-number {
            font-size: 3rem;
            font-weight: 700;
            display: block;
        }

        .stat-label {
            font-size: 1.1rem;
            opacity: 0.9;
        }

        /* Footer */
        .footer {
            background: #2d3748;
            color: white;
            padding: 50px 0 30px;
        }

        .footer-content {
            border-bottom: 1px solid #4a5568;
            padding-bottom: 30px;
            margin-bottom: 30px;
        }

        .footer-title {
            font-size: 1.3rem;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .footer-link {
            color: #a0aec0;
            text-decoration: none;
            display: block;
            margin-bottom: 10px;
            transition: color 0.3s ease;
        }

        .footer-link:hover {
            color: white;
        }

        .footer-bottom {
            text-align: center;
            color: #a0aec0;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-subtitle {
                font-size: 1.1rem;
            }

            .section-title {
                font-size: 2rem;
            }

            .feature-card {
                margin-bottom: 30px;
            }
        }

        /* Animations */
        .fade-in {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .fade-in.visible {
            opacity: 1;
            transform: translateY(0);
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-light fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home">
                <i class="bi bi-graph-up-arrow me-2"></i>
                การประเมินผลกระทบทางสังคม สำหรับโครงการยุทธศาสตร์ มหาวิทยาลัยราชภัฏเลย
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">หน้าแรก</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">คุณสมบัติ</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#about">เกี่ยวกับ</a>
                    </li>
                </ul>

                <div class="d-flex">
                    <a href="login.php" class="btn btn-login">
                        <i class="bi bi-box-arrow-in-right me-2"></i>
                        เข้าสู่ระบบ
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="hero-section">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="hero-content">
                        <h1 class="hero-title fade-in">
                            การประเมินผลกระทบทางสังคม<br>
                            <span style="color: #ffd700;">สำหรับโครงการยุทธศาสตร์<br>มหาวิทยาลัยราชภัฏเลย</span>
                        </h1>
                        <p class="hero-subtitle fade-in">
                            ระบบสนับสนุนการประเมินผลตอบแทนทางสังคม (Social Return on Investment) 
                            เพื่อวัดผลกระทบทางสังคมของโครงการอย่างเป็นระบบ
                        </p>
                        <div class="fade-in">
                            <a href="login.php" class="btn btn-hero">
                                <i class="bi bi-play-circle me-2"></i>
                                เริ่มต้นใช้งาน
                            </a>
                            <a href="#about" class="btn btn-hero btn-hero-outline">
                                <i class="bi bi-info-circle me-2"></i>
                                เรียนรู้เพิ่มเติม
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center fade-in">
                        <i class="bi bi-graph-up" style="font-size: 15rem; opacity: 0.3;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="features-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title fade-in">คุณสมบัติหลัก</h2>
                <p class="section-subtitle fade-in">
                    เครื่องมือครบครันสำหรับการประเมิน SROI อย่างมีประสิทธิภาพ
                </p>
            </div>

            <div class="row">
                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon blue">
                            <i class="bi bi-diagram-3"></i>
                        </div>
                        <h4 class="feature-title">Impact Chain</h4>
                        <p class="feature-description">
                            สร้างและจัดการเส้นทางผลกระทบจากยุทธศาสตร์สู่ผลลัพธ์ทางการเงิน
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon green">
                            <i class="bi bi-calculator"></i>
                        </div>
                        <h4 class="feature-title">การคำนวณ SROI</h4>
                        <p class="feature-description">
                            คำนวณอัตราผลตอบแทนทางสังคมด้วยระบบที่แม่นยำและเชื่อถือได้
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon orange">
                            <i class="bi bi-bar-chart"></i>
                        </div>
                        <h4 class="feature-title">รายงานและวิเคราะห์</h4>
                        <p class="feature-description">
                            สร้างรายงานและแดชบอร์ดเพื่อวิเคราะห์ผลกระทบของโครงการ
                        </p>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-4">
                    <div class="feature-card fade-in">
                        <div class="feature-icon purple">
                            <i class="bi bi-people"></i>
                        </div>
                        <h4 class="feature-title">การจัดการทีม</h4>
                        <p class="feature-description">
                            ระบบสิทธิ์ผู้ใช้และการทำงานร่วมกันในโครงการ SROI
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number">100+</span>
                        <span class="stat-label">โครงการที่ประเมิน</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number">50+</span>
                        <span class="stat-label">องค์กร</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number">1000+</span>
                        <span class="stat-label">ผู้รับประโยชน์</span>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6">
                    <div class="stat-item fade-in">
                        <span class="stat-number">95%</span>
                        <span class="stat-label">ความพึงพอใจ</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="about-section">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title fade-in">เกี่ยวกับ SROI</h2>
                <p class="section-subtitle fade-in">
                    เข้าใจแนวคิดและความสำคัญของการประเมิน Social Return on Investment
                </p>
            </div>

            <div class="row align-items-center">
                <div class="col-lg-6">
                    <div class="about-content fade-in">
                        <h3 class="mb-4">Social Return on Investment คืออะไร?</h3>
                        <p class="mb-4">
                            SROI เป็นแนวทางการประเมินที่วัดและหาค่าผลกระทบทางสังคม เศรษฐกิจ และสิ่งแวดล้อม
                            ที่เกิดขึ้นจากการดำเนินงานของโครงการหรือองค์กร โดยแสดงเป็นอัตราส่วนระหว่างมูลค่า
                            ของผลกระทบทางสังคมที่เกิดขึ้นกับการลงทุนที่ใช้ไป
                        </p>
                        <p class="mb-4">
                            ระบบนี้ช่วยให้องค์กรสามารถประเมินและสื่อสารคุณค่าของการทำงานได้อย่างเป็นรูปธรรม
                            รวมถึงการปรับปรุงการดำเนินงานเพื่อสร้างผลกระทบทางบวกให้กับสังคมมากยิ่งขึ้น
                        </p>
                        <a href="login.php" class="btn btn-hero" style="color: #667eea; background: white; border: 2px solid #667eea;">
                            <i class="bi bi-arrow-right me-2"></i>
                            เริ่มประเมิน SROI
                        </a>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="text-center fade-in">
                        <i class="bi bi-trophy" style="font-size: 12rem; color: #667eea; opacity: 0.8;"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="row">
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="footer-title">
                            <i class="bi bi-graph-up-arrow me-2"></i>
                            การประเมินผลกระทบทางสังคม สำหรับโครงการยุทธศาสตร์ มหาวิทยาลัยราชภัฏเลย
                        </div>
                        <p class="text-muted">
                            เครื่องมือสำหรับการประเมินผลตอบแทนทางสังคมอย่างเป็นระบบและมีประสิทธิภาพ
                        </p>
                    </div>
                    <div class="col-lg-2 col-md-6 mb-4">
                        <div class="footer-title">เมนูหลัก</div>
                        <a href="#home" class="footer-link">หน้าแรก</a>
                        <a href="#features" class="footer-link">คุณสมบัติ</a>
                        <a href="#about" class="footer-link">เกี่ยวกับ</a>
                        <a href="login.php" class="footer-link">เข้าสู่ระบบ</a>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-title">ติดต่อเรา</div>
                        <p class="text-muted mb-2">
                            <i class="bi bi-envelope me-2"></i>
                            info@sroi-system.com
                        </p>
                        <p class="text-muted mb-2">
                            <i class="bi bi-telephone me-2"></i>
                            02-xxx-xxxx
                        </p>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-4">
                        <div class="footer-title">ช่องทางการติดตาม</div>
                        <div class="d-flex gap-3">
                            <a href="#" class="footer-link">
                                <i class="bi bi-facebook" style="font-size: 1.5rem;"></i>
                            </a>
                            <a href="#" class="footer-link">
                                <i class="bi bi-twitter" style="font-size: 1.5rem;"></i>
                            </a>
                            <a href="#" class="footer-link">
                                <i class="bi bi-linkedin" style="font-size: 1.5rem;"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="footer-bottom">
                <p class="mb-0">&copy; 2024 การประเมินผลกระทบทางสังคม สำหรับโครงการยุทธศาสตร์ มหาวิทยาลัยราชภัฏเลย. สงวนลิขสิทธิ์.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });

        // Fade in animation on scroll
        function animateOnScroll() {
            const elements = document.querySelectorAll('.fade-in');
            const windowHeight = window.innerHeight;

            elements.forEach(element => {
                const elementTop = element.getBoundingClientRect().top;
                const elementVisible = 150;

                if (elementTop < windowHeight - elementVisible) {
                    element.classList.add('visible');
                }
            });
        }

        window.addEventListener('scroll', animateOnScroll);
        window.addEventListener('load', animateOnScroll);

        // Counter animation for stats
        function animateCounters() {
            const counters = document.querySelectorAll('.stat-number');
            const speed = 200;

            counters.forEach(counter => {
                const updateCount = () => {
                    const target = +counter.innerText.replace(/[^\d]/g, '');
                    const count = +counter.getAttribute('data-count') || 0;
                    const increment = target / speed;

                    if (count < target) {
                        counter.setAttribute('data-count', Math.ceil(count + increment));
                        counter.innerText = Math.ceil(count + increment) + (counter.innerText.includes('%') ? '%' : (counter.innerText.includes('+') ? '+' : ''));
                        setTimeout(updateCount, 1);
                    } else {
                        counter.innerText = counter.innerText;
                    }
                };
                updateCount();
            });
        }

        // Trigger counter animation when stats section is visible
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    animateCounters();
                    observer.disconnect();
                }
            });
        });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            observer.observe(statsSection);
        }
    </script>
</body>

</html>