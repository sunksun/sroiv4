<?php
// เรียกใช้งานไฟล์ config.php สำหรับการเชื่อมต่อฐานข้อมูล
require_once 'config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SROI Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 20px;
        }

        .header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .header h1 {
            color: #2d3748;
            font-size: 2.5rem;
            margin-bottom: 10px;
            background: linear-gradient(45deg, #667eea, #764ba2);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: #718096;
            font-size: 1.1rem;
        }

        .navigation {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 30px;
            box-shadow: 0 4px 20px rgba(31, 38, 135, 0.2);
        }

        .nav-tabs {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            border-bottom: 2px solid #e2e8f0;
            margin-bottom: 20px;
            padding-bottom: 15px;
        }

        .nav-tab {
            padding: 12px 20px;
            background: #f7fafc;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            color: #4a5568;
            font-size: 0.95rem;
        }

        .nav-tab:hover {
            background: #e2e8f0;
            transform: translateY(-2px);
        }

        .nav-tab.active {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .content-section {
            display: none;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(31, 38, 135, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
        }

        .content-section.active {
            display: block;
            animation: fadeInUp 0.5s ease;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .section-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }

        .section-title {
            font-size: 1.8rem;
            color: #2d3748;
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .section-title i {
            color: #667eea;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }

        .btn-secondary {
            background: #e2e8f0;
            color: #4a5568;
        }

        .btn-secondary:hover {
            background: #cbd5e0;
        }

        .btn-danger {
            background: #fc8181;
            color: white;
        }

        .btn-danger:hover {
            background: #f56565;
            transform: translateY(-1px);
        }

        .btn-edit {
            background: #68d391;
            color: white;
            padding: 8px 12px;
            font-size: 0.85rem;
        }

        .btn-edit:hover {
            background: #48bb78;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            background: white;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .data-table th {
            background: linear-gradient(45deg, #667eea, #764ba2);
            color: white;
            padding: 15px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .data-table td {
            padding: 12px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.9rem;
        }

        .data-table tr:hover {
            background: #f7fafc;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(5px);
        }

        .modal-content {
            background: white;
            margin: 5% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        .modal-header {
            display: flex;
            justify-content: between;
            align-items: center;
            margin-bottom: 25px;
            padding-bottom: 15px;
            border-bottom: 2px solid #e2e8f0;
        }

        .modal-header h2 {
            color: #2d3748;
            margin: 0;
        }

        .close {
            color: #a0aec0;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }

        .close:hover {
            color: #2d3748;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 500;
            color: #2d3748;
        }

        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }

        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 80px;
        }

        .search-bar {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }

        .search-input {
            flex: 1;
            min-width: 250px;
            padding: 12px;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            font-size: 1rem;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 20px rgba(102, 126, 234, 0.3);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .stat-label {
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .alert {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            display: none;
        }

        .alert-success {
            background: #c6f6d5;
            color: #276749;
            border: 1px solid #9ae6b4;
        }

        .alert-error {
            background: #fed7d7;
            color: #c53030;
            border: 1px solid #feb2b2;
        }

        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 768px) {
            .container {
                padding: 10px;
            }

            .header h1 {
                font-size: 2rem;
            }

            .nav-tabs {
                flex-direction: column;
            }

            .nav-tab {
                width: 100%;
            }

            .data-table {
                font-size: 0.8rem;
            }

            .data-table th,
            .data-table td {
                padding: 8px 6px;
            }

            .modal-content {
                margin: 10% auto;
                width: 95%;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1><i class="fas fa-chart-line"></i> SROI Management System</h1>
            <p>ระบบจัดการข้อมูลการวิเคราะห์ผลตอบแทนทางสังคม (Social Return on Investment)</p>
        </div>

        <!-- Navigation -->
        <div class="navigation">
            <div class="nav-tabs">
                <button class="nav-tab active" onclick="showSection('dashboard')">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </button>
                <button class="nav-tab" onclick="showSection('projects')">
                    <i class="fas fa-project-diagram"></i> โครงการ
                </button>
                <button class="nav-tab" onclick="showSection('strategies')">
                    <i class="fas fa-bullseye"></i> ยุทธศาสตร์
                </button>
                <button class="nav-tab" onclick="showSection('activities')">
                    <i class="fas fa-tasks"></i> กิจกรรม
                </button>
                <button class="nav-tab" onclick="showSection('outputs')">
                    <i class="fas fa-cube"></i> ผลผลิต
                </button>
                <button class="nav-tab" onclick="showSection('outcomes')">
                    <i class="fas fa-trophy"></i> ผลลัพธ์
                </button>
                <button class="nav-tab" onclick="showSection('proxies')">
                    <i class="fas fa-dollar-sign"></i> ตัวแทนมูลค่า
                </button>
            </div>
        </div>

        <!-- Alert Messages -->
        <div id="alertSuccess" class="alert alert-success">
            <i class="fas fa-check-circle"></i> <span id="successMessage"></span>
        </div>
        <div id="alertError" class="alert alert-error">
            <i class="fas fa-exclamation-circle"></i> <span id="errorMessage"></span>
        </div>

        <!-- Dashboard Section -->
        <div id="dashboard" class="content-section active">
            <div class="section-header">
                <div class="section-title">
                    <i class="fas fa-tachometer-alt"></i>
                    Dashboard
                </div>
            </div>
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-number" id="projectCount">3</div>
                    <div class="stat-label">โครงการทั้งหมด</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="strategyCount">2</div>
                    <div class="stat-label">ยุทธศาสตร์</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="activityCount">22</div>
                    <div class="stat-label">กิจกรรม</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="outputCount">42</div>
                    <div class="stat-label">ผลผลิต</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="outcomeCount">84</div>
                    <div class="stat-label">ผลลัพธ์</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="proxyCount">74</div>
                    <div class="stat-label">ตัวแทนมูลค่า</div>
                </div>
            </div>
        </div>

        <!-- Projects Section -->
        <div id="projects" class="content-section">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fas fa-project-diagram"></i>
                        จัดการโครงการ
                    </div>
                </div>
                <button class="btn btn-primary" onclick="openProjectModal()">
                    <i class="fas fa-plus"></i> เพิ่มโครงการใหม่
                </button>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="ค้นหาโครงการ..." onkeyup="searchProjects(this.value)">
                <select class="btn btn-secondary" onchange="filterProjectsByStatus(this.value)">
                    <option value="">สถานะทั้งหมด</option>
                    <option value="incompleted">ไม่เสร็จสิ้น</option>
                    <option value="completed">เสร็จสิ้น</option>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>รหัสโครงการ</th>
                        <th>ชื่อโครงการ</th>
                        <th>หน่วยงาน</th>
                        <th>ผู้จัดการโครงการ</th>
                        <th>งบประมาณ</th>
                        <th>สถานะ</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="projectsTableBody">
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Strategies Section -->
        <div id="strategies" class="content-section">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fas fa-bullseye"></i>
                        จัดการยุทธศาสตร์
                    </div>
                </div>
                <button class="btn btn-primary" onclick="openStrategyModal()">
                    <i class="fas fa-plus"></i> เพิ่มยุทธศาสตร์ใหม่
                </button>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>รหัสยุทธศาสตร์</th>
                        <th>ชื่อยุทธศาสตร์</th>
                        <th>รายละเอียด</th>
                        <th>วันที่สร้าง</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="strategiesTableBody">
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Activities Section -->
        <div id="activities" class="content-section">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fas fa-tasks"></i>
                        จัดการกิจกรรม
                    </div>
                </div>
                <button class="btn btn-primary" onclick="openActivityModal()">
                    <i class="fas fa-plus"></i> เพิ่มกิจกรรมใหม่
                </button>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="ค้นหากิจกรรม..." onkeyup="searchActivities(this.value)">
                <select class="btn btn-secondary" onchange="filterActivitiesByStrategy(this.value)">
                    <option value="">ยุทธศาสตร์ทั้งหมด</option>
                    <option value="1">พัฒนาท้องถิ่น</option>
                    <option value="2">ผลิตและพัฒนาครู</option>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>รหัสกิจกรรม</th>
                        <th>ชื่อกิจกรรม</th>
                        <th>ยุทธศาสตร์</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="activitiesTableBody">
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Outputs Section -->
        <div id="outputs" class="content-section">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fas fa-cube"></i>
                        จัดการผลผลิต
                    </div>
                </div>
                <button class="btn btn-primary" onclick="openOutputModal()">
                    <i class="fas fa-plus"></i> เพิ่มผลผลิตใหม่
                </button>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="ค้นหาผลผลิต..." onkeyup="searchOutputs(this.value)">
                <select class="btn btn-secondary" onchange="filterOutputsByActivity(this.value)">
                    <option value="">กิจกรรมทั้งหมด</option>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>รายละเอียดผลผลิต</th>
                        <th>กิจกรรม</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="outputsTableBody">
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Outcomes Section -->
        <div id="outcomes" class="content-section">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fas fa-trophy"></i>
                        จัดการผลลัพธ์
                    </div>
                </div>
                <button class="btn btn-primary" onclick="openOutcomeModal()">
                    <i class="fas fa-plus"></i> เพิ่มผลลัพธ์ใหม่
                </button>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="ค้นหาผลลัพธ์..." onkeyup="searchOutcomes(this.value)">
                <select class="btn btn-secondary" onchange="filterOutcomesByOutput(this.value)">
                    <option value="">ผลผลิตทั้งหมด</option>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>รายละเอียดผลลัพธ์</th>
                        <th>ผลผลิต</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="outcomesTableBody">
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>

        <!-- Proxies Section -->
        <div id="proxies" class="content-section">
            <div class="section-header">
                <div>
                    <div class="section-title">
                        <i class="fas fa-dollar-sign"></i>
                        จัดการตัวแทนมูลค่า
                    </div>
                </div>
                <button class="btn btn-primary" onclick="openProxyModal()">
                    <i class="fas fa-plus"></i> เพิ่มตัวแทนมูลค่าใหม่
                </button>
            </div>

            <div class="search-bar">
                <input type="text" class="search-input" placeholder="ค้นหาตัวแทนมูลค่า..." onkeyup="searchProxies(this.value)">
                <select class="btn btn-secondary" onchange="filterProxiesByOutcome(this.value)">
                    <option value="">ผลลัพธ์ทั้งหมด</option>
                </select>
            </div>

            <table class="data-table">
                <thead>
                    <tr>
                        <th>ลำดับ</th>
                        <th>ชื่อตัวแทนมูลค่า</th>
                        <th>สูตรคำนวณ</th>
                        <th>แหล่งข้อมูล</th>
                        <th>ผลลัพธ์</th>
                        <th>การจัดการ</th>
                    </tr>
                </thead>
                <tbody id="proxiesTableBody">
                    <!-- Data will be populated by JavaScript -->
                </tbody>
            </table>
        </div>
    </div>

    <!-- Project Modal -->
    <div id="projectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="projectModalTitle">เพิ่มโครงการใหม่</h2>
                <span class="close" onclick="closeProjectModal()">&times;</span>
            </div>
            <form id="projectForm">
                <div class="form-group">
                    <label for="projectCode">รหัสโครงการ *</label>
                    <input type="text" id="projectCode" name="project_code" required>
                </div>
                <div class="form-group">
                    <label for="projectName">ชื่อโครงการ *</label>
                    <textarea id="projectName" name="name" required rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="projectDescription">รายละเอียดโครงการ</label>
                    <textarea id="projectDescription" name="description" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="projectObjective">วัตถุประสงค์โครงการ</label>
                    <textarea id="projectObjective" name="objective" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="projectBudget">งบประมาณ</label>
                    <input type="number" id="projectBudget" name="budget" step="0.01" min="0">
                </div>
                <div class="form-group">
                    <label for="projectOrganization">หน่วยงาน/องค์กร</label>
                    <input type="text" id="projectOrganization" name="organization">
                </div>
                <div class="form-group">
                    <label for="projectManager">ผู้จัดการโครงการ</label>
                    <input type="text" id="projectManager" name="project_manager">
                </div>
                <div class="form-group">
                    <label for="projectStatus">สถานะโครงการ</label>
                    <select id="projectStatus" name="status">
                        <option value="incompleted">ไม่เสร็จสิ้น</option>
                        <option value="completed">เสร็จสิ้น</option>
                    </select>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeProjectModal()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Strategy Modal -->
    <div id="strategyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="strategyModalTitle">เพิ่มยุทธศาสตร์ใหม่</h2>
                <span class="close" onclick="closeStrategyModal()">&times;</span>
            </div>
            <form id="strategyForm">
                <div class="form-group">
                    <label for="strategyCode">รหัสยุทธศาสตร์ *</label>
                    <input type="text" id="strategyCode" name="strategy_code" required>
                </div>
                <div class="form-group">
                    <label for="strategyName">ชื่อยุทธศาสตร์ *</label>
                    <input type="text" id="strategyName" name="strategy_name" required>
                </div>
                <div class="form-group">
                    <label for="strategyDescription">รายละเอียด</label>
                    <textarea id="strategyDescription" name="description" rows="4"></textarea>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeStrategyModal()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Activity Modal -->
    <div id="activityModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="activityModalTitle">เพิ่มกิจกรรมใหม่</h2>
                <span class="close" onclick="closeActivityModal()">&times;</span>
            </div>
            <form id="activityForm">
                <div class="form-group">
                    <label for="activityStrategy">ยุทธศาสตร์ *</label>
                    <select id="activityStrategy" name="strategy_id" required>
                        <option value="">เลือกยุทธศาสตร์</option>
                        <option value="1">พัฒนาท้องถิ่น</option>
                        <option value="2">ผลิตและพัฒนาครู</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="activityCode">รหัสกิจกรรม *</label>
                    <input type="text" id="activityCode" name="activity_code" required>
                </div>
                <div class="form-group">
                    <label for="activityName">ชื่อกิจกรรม *</label>
                    <textarea id="activityName" name="activity_name" required rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="activityDescription">รายละเอียดกิจกรรม</label>
                    <textarea id="activityDescription" name="activity_description" rows="4"></textarea>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeActivityModal()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Output Modal -->
    <div id="outputModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="outputModalTitle">เพิ่มผลผลิตใหม่</h2>
                <span class="close" onclick="closeOutputModal()">&times;</span>
            </div>
            <form id="outputForm">
                <div class="form-group">
                    <label for="outputActivity">กิจกรรม *</label>
                    <select id="outputActivity" name="activity_id" required>
                        <option value="">เลือกกิจกรรม</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="outputSequence">ลำดับ</label>
                    <input type="text" id="outputSequence" name="output_sequence">
                </div>
                <div class="form-group">
                    <label for="outputDescription">รายละเอียดผลผลิต *</label>
                    <textarea id="outputDescription" name="output_description" required rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="outputTargetDetails">รายละเอียดกลุ่มเป้าหมาย</label>
                    <textarea id="outputTargetDetails" name="target_details" rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeOutputModal()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Outcome Modal -->
    <div id="outcomeModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="outcomeModalTitle">เพิ่มผลลัพธ์ใหม่</h2>
                <span class="close" onclick="closeOutcomeModal()">&times;</span>
            </div>
            <form id="outcomeForm">
                <div class="form-group">
                    <label for="outcomeOutput">ผลผลิต *</label>
                    <select id="outcomeOutput" name="output_id" required>
                        <option value="">เลือกผลผลิต</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="outcomeSequence">ลำดับผลลัพธ์ *</label>
                    <input type="text" id="outcomeSequence" name="outcome_sequence" required>
                </div>
                <div class="form-group">
                    <label for="outcomeDescription">รายละเอียดผลลัพธ์ *</label>
                    <textarea id="outcomeDescription" name="outcome_description" required rows="4"></textarea>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeOutcomeModal()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Proxy Modal -->
    <div id="proxyModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="proxyModalTitle">เพิ่มตัวแทนมูลค่าใหม่</h2>
                <span class="close" onclick="closeProxyModal()">&times;</span>
            </div>
            <form id="proxyForm">
                <div class="form-group">
                    <label for="proxyOutcome">ผลลัพธ์ *</label>
                    <select id="proxyOutcome" name="outcome_id" required>
                        <option value="">เลือกผลลัพธ์</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="proxySequence">ลำดับตัวแทนมูลค่า *</label>
                    <input type="text" id="proxySequence" name="proxy_sequence" required>
                </div>
                <div class="form-group">
                    <label for="proxyName">ชื่อตัวแทนมูลค่า *</label>
                    <textarea id="proxyName" name="proxy_name" required rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label for="proxyFormula">สูตรการคำนวณ *</label>
                    <textarea id="proxyFormula" name="calculation_formula" required rows="4"></textarea>
                </div>
                <div class="form-group">
                    <label for="proxyDescription">รายละเอียดแหล่งที่มาของข้อมูล *</label>
                    <textarea id="proxyDescription" name="proxy_description" required rows="3"></textarea>
                </div>
                <div style="display: flex; gap: 15px; justify-content: flex-end; margin-top: 25px;">
                    <button type="button" class="btn btn-secondary" onclick="closeProxyModal()">
                        <i class="fas fa-times"></i> ยกเลิก
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> บันทึก
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        // ดึงข้อมูลจากฐานข้อมูลผ่าน AJAX
        async function fetchProjects() {
            try {
                const response = await fetch('get-projects-data.php');
                const data = await response.json();
                return data.projects || [];
            } catch (error) {
                console.error('Error fetching projects:', error);
                return [];
            }
        }

        async function fetchStrategies() {
            try {
                const response = await fetch('get-strategies-data.php');
                const data = await response.json();
                return data.strategies || [];
            } catch (error) {
                console.error('Error fetching strategies:', error);
                return [];
            }
        }

        // Sample data - In real application, this would come from a database
        let mockData = {
            projects: [],
            strategies: [],
            activities: [],
            outputs: [],
            outcomes: [],
            proxies: []
        };

        // โหลดข้อมูลเริ่มต้นจากฐานข้อมูล
        async function initializeData() {
            mockData.projects = await fetchProjects();
            mockData.strategies = await fetchStrategies();

            // ข้อมูลตัวอย่างสำหรับส่วนอื่นๆ (จะปรับปรุงให้ดึงจากฐานข้อมูลจริงในภายหลัง)
            mockData.activities = [{
                    activity_id: 1,
                    strategy_id: 1,
                    activity_code: '1',
                    activity_name: 'พัฒนาความรู้/ทักษะ/ศักยภาพ/ทักษะอาชีพ'
                },
                {
                    activity_id: 2,
                    strategy_id: 1,
                    activity_code: '2',
                    activity_name: 'ออกแบบ/พัฒนา/เพิ่มมูลค่าผลิตภัณฑ์'
                },
                {
                    activity_id: 3,
                    strategy_id: 1,
                    activity_code: '2.1',
                    activity_name: 'แปรรูปผลิตภัณฑ์/พัฒนาผลิตภัณฑ์ใหม่/ผลิตภัณฑ์สร้างสรรค์ (พัฒนาบรรจุภัณฑ์ โลโก้แบรนด์)'
                },
                // More activities would be here...
            ];

            mockData.outputs = [{
                    output_id: 1,
                    activity_id: 1,
                    output_sequence: '1',
                    output_description: 'กลุ่มเป้าหมาย จำนวน.........คน มีความรู้ ความเข้าใจ และทักษะ/ศักยภาพ/สมรรถนะ เกี่ยวกับ.....................'
                },
                {
                    output_id: 2,
                    activity_id: 1,
                    output_sequence: '1.1',
                    output_description: 'กลุ่มเป้าหมาย จำนวน.........คน มีความรู้ ความเข้าใจ และทักษะ เกี่ยวกับ.....................'
                }
                // More outputs would be here...
            ];

            mockData.outcomes = [{
                    outcome_id: 1,
                    output_id: 2,
                    outcome_sequence: '1.1.1',
                    outcome_description: 'กลุ่มเป้าหมาย จำนวน..........คน สามารถถ่ายทอดความรู้ เกี่ยวกับ.......................ให้กับ..................จำนวน..........คน (กลุ่ม/ชุมชน)'
                },
                {
                    outcome_id: 2,
                    output_id: 2,
                    outcome_sequence: '1.1.2',
                    outcome_description: 'กลุ่มเป้าหมาย จำนวน..........คน สามารถนำความรู้ ด้าน.......................มา....(ใช้ประโยชน์อย่างไร)....ทำให้สร้างรายได้เพิ่มขึ้น'
                }
                // More outcomes would be here...
            ];

            mockData.proxies = [{
                    proxy_id: 1,
                    outcome_id: 1,
                    proxy_sequence: '1',
                    proxy_name: '1.รายได้จากค่าตอบแทนในการถ่ายทอดความรู้ทักษะเกี่ยวกับ.........................................',
                    calculation_formula: '(ค่าตอบแทน/ครั้ง/คน x จำนวนครั้ง/ปี x จำนวนคน = xxxxx บาท/ปี)',
                    proxy_description: '(จากการสัมภาษณ์ค่าตอบที่ได้รับ หรือระเบียบค่าตอบแทนวิทยากรในเรื่องที่คล้ายกัน)'
                }
                // More proxies would be here...
            ];
        }

        // Current editing item
        let currentEditingItem = null;
        let currentEditingType = null;

        // Show different sections
        function showSection(sectionName) {
            // Hide all sections
            const sections = document.querySelectorAll('.content-section');
            sections.forEach(section => {
                section.classList.remove('active');
            });

            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.nav-tab');
            tabs.forEach(tab => {
                tab.classList.remove('active');
            });

            // Show selected section
            document.getElementById(sectionName).classList.add('active');

            // Add active class to selected tab
            event.target.classList.add('active');

            // Load data for the section
            switch (sectionName) {
                case 'projects':
                    loadProjects();
                    break;
                case 'strategies':
                    loadStrategies();
                    break;
                case 'activities':
                    loadActivities();
                    break;
                case 'outputs':
                    loadOutputs();
                    break;
                case 'outcomes':
                    loadOutcomes();
                    break;
                case 'proxies':
                    loadProxies();
                    break;
            }
        }

        // Alert functions
        function showSuccess(message) {
            document.getElementById('successMessage').textContent = message;
            document.getElementById('alertSuccess').style.display = 'block';
            setTimeout(() => {
                document.getElementById('alertSuccess').style.display = 'none';
            }, 3000);
        }

        function showError(message) {
            document.getElementById('errorMessage').textContent = message;
            document.getElementById('alertError').style.display = 'block';
            setTimeout(() => {
                document.getElementById('alertError').style.display = 'none';
            }, 3000);
        }

        // Project functions
        function loadProjects() {
            const tbody = document.getElementById('projectsTableBody');
            tbody.innerHTML = '';

            mockData.projects.forEach(project => {
                const row = document.createElement('tr');
                const statusText = project.status === 'completed' ? 'เสร็จสิ้น' : 'ไม่เสร็จสิ้น';
                const statusClass = project.status === 'completed' ? 'success' : 'warning';

                row.innerHTML = `
                    <td>${project.project_code}</td>
                    <td title="${project.name}">${truncateText(project.name, 50)}</td>
                    <td>${project.organization || '-'}</td>
                    <td>${project.project_manager || '-'}</td>
                    <td>${formatCurrency(project.budget)}</td>
                    <td><span class="status ${statusClass}">${statusText}</span></td>
                    <td>
                        <button class="btn btn-edit" onclick="editProject(${project.id})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button class="btn btn-danger" onclick="deleteProject(${project.id})" style="margin-left: 5px;">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openProjectModal(projectId = null) {
            const modal = document.getElementById('projectModal');
            const form = document.getElementById('projectForm');
            const title = document.getElementById('projectModalTitle');

            form.reset();
            currentEditingItem = null;
            currentEditingType = 'project';

            if (projectId) {
                const project = mockData.projects.find(p => p.id === projectId);
                if (project) {
                    title.textContent = 'แก้ไขโครงการ';
                    document.getElementById('projectCode').value = project.project_code;
                    document.getElementById('projectName').value = project.name;
                    document.getElementById('projectDescription').value = project.description || '';
                    document.getElementById('projectObjective').value = project.objective || '';
                    document.getElementById('projectBudget').value = project.budget || '';
                    document.getElementById('projectOrganization').value = project.organization || '';
                    document.getElementById('projectManager').value = project.project_manager || '';
                    document.getElementById('projectStatus').value = project.status;
                    currentEditingItem = project;
                }
            } else {
                title.textContent = 'เพิ่มโครงการใหม่';
            }

            modal.style.display = 'block';
        }

        function closeProjectModal() {
            document.getElementById('projectModal').style.display = 'none';
        }

        function editProject(id) {
            openProjectModal(id);
        }

        function deleteProject(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบโครงการนี้?')) {
                mockData.projects = mockData.projects.filter(p => p.id !== id);
                loadProjects();
                showSuccess('ลบโครงการเรียบร้อยแล้ว');
            }
        }

        // Strategy functions
        function loadStrategies() {
            const tbody = document.getElementById('strategiesTableBody');
            tbody.innerHTML = '';

            mockData.strategies.forEach(strategy => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${strategy.strategy_code}</td>
                    <td>${strategy.strategy_name}</td>
                    <td>${truncateText(strategy.description || '', 50)}</td>
                    <td>-</td>
                    <td>
                        <button class="btn btn-edit" onclick="editStrategy(${strategy.strategy_id})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button class="btn btn-danger" onclick="deleteStrategy(${strategy.strategy_id})" style="margin-left: 5px;">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openStrategyModal(strategyId = null) {
            const modal = document.getElementById('strategyModal');
            const form = document.getElementById('strategyForm');
            const title = document.getElementById('strategyModalTitle');

            form.reset();
            currentEditingItem = null;
            currentEditingType = 'strategy';

            if (strategyId) {
                const strategy = mockData.strategies.find(s => s.strategy_id === strategyId);
                if (strategy) {
                    title.textContent = 'แก้ไขยุทธศาสตร์';
                    document.getElementById('strategyCode').value = strategy.strategy_code;
                    document.getElementById('strategyName').value = strategy.strategy_name;
                    document.getElementById('strategyDescription').value = strategy.description || '';
                    currentEditingItem = strategy;
                }
            } else {
                title.textContent = 'เพิ่มยุทธศาสตร์ใหม่';
            }

            modal.style.display = 'block';
        }

        function closeStrategyModal() {
            document.getElementById('strategyModal').style.display = 'none';
        }

        function editStrategy(id) {
            openStrategyModal(id);
        }

        function deleteStrategy(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบยุทธศาสตร์นี้?')) {
                mockData.strategies = mockData.strategies.filter(s => s.strategy_id !== id);
                loadStrategies();
                showSuccess('ลบยุทธศาสตร์เรียบร้อยแล้ว');
            }
        }

        // Activity functions
        function loadActivities() {
            const tbody = document.getElementById('activitiesTableBody');
            tbody.innerHTML = '';

            mockData.activities.forEach(activity => {
                const strategy = mockData.strategies.find(s => s.strategy_id === activity.strategy_id);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${activity.activity_code}</td>
                    <td title="${activity.activity_name}">${truncateText(activity.activity_name, 60)}</td>
                    <td>${strategy ? strategy.strategy_name : '-'}</td>
                    <td>
                        <button class="btn btn-edit" onclick="editActivity(${activity.activity_id})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button class="btn btn-danger" onclick="deleteActivity(${activity.activity_id})" style="margin-left: 5px;">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openActivityModal(activityId = null) {
            const modal = document.getElementById('activityModal');
            const form = document.getElementById('activityForm');
            const title = document.getElementById('activityModalTitle');

            form.reset();
            currentEditingItem = null;
            currentEditingType = 'activity';

            if (activityId) {
                const activity = mockData.activities.find(a => a.activity_id === activityId);
                if (activity) {
                    title.textContent = 'แก้ไขกิจกรรม';
                    document.getElementById('activityStrategy').value = activity.strategy_id;
                    document.getElementById('activityCode').value = activity.activity_code;
                    document.getElementById('activityName').value = activity.activity_name;
                    document.getElementById('activityDescription').value = activity.activity_description || '';
                    currentEditingItem = activity;
                }
            } else {
                title.textContent = 'เพิ่มกิจกรรมใหม่';
            }

            modal.style.display = 'block';
        }

        function closeActivityModal() {
            document.getElementById('activityModal').style.display = 'none';
        }

        function editActivity(id) {
            openActivityModal(id);
        }

        function deleteActivity(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบกิจกรรมนี้?')) {
                mockData.activities = mockData.activities.filter(a => a.activity_id !== id);
                loadActivities();
                showSuccess('ลบกิจกรรมเรียบร้อยแล้ว');
            }
        }

        // Output functions
        function loadOutputs() {
            const tbody = document.getElementById('outputsTableBody');
            tbody.innerHTML = '';

            mockData.outputs.forEach(output => {
                const activity = mockData.activities.find(a => a.activity_id === output.activity_id);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${output.output_sequence || '-'}</td>
                    <td title="${output.output_description}">${truncateText(output.output_description, 80)}</td>
                    <td>${activity ? truncateText(activity.activity_name, 40) : '-'}</td>
                    <td>
                        <button class="btn btn-edit" onclick="editOutput(${output.output_id})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button class="btn btn-danger" onclick="deleteOutput(${output.output_id})" style="margin-left: 5px;">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openOutputModal(outputId = null) {
            const modal = document.getElementById('outputModal');
            const form = document.getElementById('outputForm');
            const title = document.getElementById('outputModalTitle');
            const activitySelect = document.getElementById('outputActivity');

            // Populate activity dropdown
            activitySelect.innerHTML = '<option value="">เลือกกิจกรรม</option>';
            mockData.activities.forEach(activity => {
                const option = document.createElement('option');
                option.value = activity.activity_id;
                option.textContent = `${activity.activity_code} - ${truncateText(activity.activity_name, 50)}`;
                activitySelect.appendChild(option);
            });

            form.reset();
            currentEditingItem = null;
            currentEditingType = 'output';

            if (outputId) {
                const output = mockData.outputs.find(o => o.output_id === outputId);
                if (output) {
                    title.textContent = 'แก้ไขผลผลิต';
                    document.getElementById('outputActivity').value = output.activity_id;
                    document.getElementById('outputSequence').value = output.output_sequence || '';
                    document.getElementById('outputDescription').value = output.output_description;
                    document.getElementById('outputTargetDetails').value = output.target_details || '';
                    currentEditingItem = output;
                }
            } else {
                title.textContent = 'เพิ่มผลผลิตใหม่';
            }

            modal.style.display = 'block';
        }

        function closeOutputModal() {
            document.getElementById('outputModal').style.display = 'none';
        }

        function editOutput(id) {
            openOutputModal(id);
        }

        function deleteOutput(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบผลผลิตนี้?')) {
                mockData.outputs = mockData.outputs.filter(o => o.output_id !== id);
                loadOutputs();
                showSuccess('ลบผลผลิตเรียบร้อยแล้ว');
            }
        }

        // Outcome functions
        function loadOutcomes() {
            const tbody = document.getElementById('outcomesTableBody');
            tbody.innerHTML = '';

            mockData.outcomes.forEach(outcome => {
                const output = mockData.outputs.find(o => o.output_id === outcome.output_id);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${outcome.outcome_sequence}</td>
                    <td title="${outcome.outcome_description}">${truncateText(outcome.outcome_description, 80)}</td>
                    <td>${output ? truncateText(output.output_description, 40) : '-'}</td>
                    <td>
                        <button class="btn btn-edit" onclick="editOutcome(${outcome.outcome_id})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button class="btn btn-danger" onclick="deleteOutcome(${outcome.outcome_id})" style="margin-left: 5px;">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openOutcomeModal(outcomeId = null) {
            const modal = document.getElementById('outcomeModal');
            const form = document.getElementById('outcomeForm');
            const title = document.getElementById('outcomeModalTitle');
            const outputSelect = document.getElementById('outcomeOutput');

            // Populate output dropdown
            outputSelect.innerHTML = '<option value="">เลือกผลผลิต</option>';
            mockData.outputs.forEach(output => {
                const option = document.createElement('option');
                option.value = output.output_id;
                option.textContent = `${output.output_sequence || 'N/A'} - ${truncateText(output.output_description, 50)}`;
                outputSelect.appendChild(option);
            });

            form.reset();
            currentEditingItem = null;
            currentEditingType = 'outcome';

            if (outcomeId) {
                const outcome = mockData.outcomes.find(o => o.outcome_id === outcomeId);
                if (outcome) {
                    title.textContent = 'แก้ไขผลลัพธ์';
                    document.getElementById('outcomeOutput').value = outcome.output_id;
                    document.getElementById('outcomeSequence').value = outcome.outcome_sequence;
                    document.getElementById('outcomeDescription').value = outcome.outcome_description;
                    currentEditingItem = outcome;
                }
            } else {
                title.textContent = 'เพิ่มผลลัพธ์ใหม่';
            }

            modal.style.display = 'block';
        }

        function closeOutcomeModal() {
            document.getElementById('outcomeModal').style.display = 'none';
        }

        function editOutcome(id) {
            openOutcomeModal(id);
        }

        function deleteOutcome(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบผลลัพธ์นี้?')) {
                mockData.outcomes = mockData.outcomes.filter(o => o.outcome_id !== id);
                loadOutcomes();
                showSuccess('ลบผลลัพธ์เรียบร้อยแล้ว');
            }
        }

        // Proxy functions
        function loadProxies() {
            const tbody = document.getElementById('proxiesTableBody');
            tbody.innerHTML = '';

            mockData.proxies.forEach(proxy => {
                const outcome = mockData.outcomes.find(o => o.outcome_id === proxy.outcome_id);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>${proxy.proxy_sequence}</td>
                    <td title="${proxy.proxy_name}">${truncateText(proxy.proxy_name, 50)}</td>
                    <td title="${proxy.calculation_formula}">${truncateText(proxy.calculation_formula, 40)}</td>
                    <td title="${proxy.proxy_description}">${truncateText(proxy.proxy_description, 30)}</td>
                    <td>${outcome ? truncateText(outcome.outcome_description, 40) : '-'}</td>
                    <td>
                        <button class="btn btn-edit" onclick="editProxy(${proxy.proxy_id})">
                            <i class="fas fa-edit"></i> แก้ไข
                        </button>
                        <button class="btn btn-danger" onclick="deleteProxy(${proxy.proxy_id})" style="margin-left: 5px;">
                            <i class="fas fa-trash"></i> ลบ
                        </button>
                    </td>
                `;
                tbody.appendChild(row);
            });
        }

        function openProxyModal(proxyId = null) {
            const modal = document.getElementById('proxyModal');
            const form = document.getElementById('proxyForm');
            const title = document.getElementById('proxyModalTitle');
            const outcomeSelect = document.getElementById('proxyOutcome');

            // Populate outcome dropdown
            outcomeSelect.innerHTML = '<option value="">เลือกผลลัพธ์</option>';
            mockData.outcomes.forEach(outcome => {
                const option = document.createElement('option');
                option.value = outcome.outcome_id;
                option.textContent = `${outcome.outcome_sequence} - ${truncateText(outcome.outcome_description, 50)}`;
                outcomeSelect.appendChild(option);
            });

            form.reset();
            currentEditingItem = null;
            currentEditingType = 'proxy';

            if (proxyId) {
                const proxy = mockData.proxies.find(p => p.proxy_id === proxyId);
                if (proxy) {
                    title.textContent = 'แก้ไขตัวแทนมูลค่า';
                    document.getElementById('proxyOutcome').value = proxy.outcome_id;
                    document.getElementById('proxySequence').value = proxy.proxy_sequence;
                    document.getElementById('proxyName').value = proxy.proxy_name;
                    document.getElementById('proxyFormula').value = proxy.calculation_formula;
                    document.getElementById('proxyDescription').value = proxy.proxy_description;
                    currentEditingItem = proxy;
                }
            } else {
                title.textContent = 'เพิ่มตัวแทนมูลค่าใหม่';
            }

            modal.style.display = 'block';
        }

        function closeProxyModal() {
            document.getElementById('proxyModal').style.display = 'none';
        }

        function editProxy(id) {
            openProxyModal(id);
        }

        function deleteProxy(id) {
            if (confirm('คุณแน่ใจหรือไม่ที่จะลบตัวแทนมูลค่านี้?')) {
                mockData.proxies = mockData.proxies.filter(p => p.proxy_id !== id);
                loadProxies();
                showSuccess('ลบตัวแทนมูลค่าเรียบร้อยแล้ว');
            }
        }

        // Form submissions
        document.getElementById('projectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            if (currentEditingItem) {
                // Update existing project
                Object.assign(currentEditingItem, data);
                showSuccess('แก้ไขโครงการเรียบร้อยแล้ว');
            } else {
                // Add new project
                const newId = Math.max(...mockData.projects.map(p => p.id)) + 1;
                data.id = newId;
                data.budget = parseFloat(data.budget) || 0;
                mockData.projects.push(data);
                showSuccess('เพิ่มโครงการใหม่เรียบร้อยแล้ว');
            }

            closeProjectModal();
            loadProjects();
        });

        document.getElementById('strategyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            if (currentEditingItem) {
                Object.assign(currentEditingItem, data);
                showSuccess('แก้ไขยุทธศาสตร์เรียบร้อยแล้ว');
            } else {
                const newId = Math.max(...mockData.strategies.map(s => s.strategy_id)) + 1;
                data.strategy_id = newId;
                mockData.strategies.push(data);
                showSuccess('เพิ่มยุทธศาสตร์ใหม่เรียบร้อยแล้ว');
            }

            closeStrategyModal();
            loadStrategies();
        });

        document.getElementById('activityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            if (currentEditingItem) {
                Object.assign(currentEditingItem, data);
                data.strategy_id = parseInt(data.strategy_id);
                showSuccess('แก้ไขกิจกรรมเรียบร้อยแล้ว');
            } else {
                const newId = Math.max(...mockData.activities.map(a => a.activity_id)) + 1;
                data.activity_id = newId;
                data.strategy_id = parseInt(data.strategy_id);
                mockData.activities.push(data);
                showSuccess('เพิ่มกิจกรรมใหม่เรียบร้อยแล้ว');
            }

            closeActivityModal();
            loadActivities();
        });

        document.getElementById('outputForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            if (currentEditingItem) {
                Object.assign(currentEditingItem, data);
                data.activity_id = parseInt(data.activity_id);
                showSuccess('แก้ไขผลผลิตเรียบร้อยแล้ว');
            } else {
                const newId = Math.max(...mockData.outputs.map(o => o.output_id)) + 1;
                data.output_id = newId;
                data.activity_id = parseInt(data.activity_id);
                mockData.outputs.push(data);
                showSuccess('เพิ่มผลผลิตใหม่เรียบร้อยแล้ว');
            }

            closeOutputModal();
            loadOutputs();
        });

        document.getElementById('outcomeForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            if (currentEditingItem) {
                Object.assign(currentEditingItem, data);
                data.output_id = parseInt(data.output_id);
                showSuccess('แก้ไขผลลัพธ์เรียบร้อยแล้ว');
            } else {
                const newId = Math.max(...mockData.outcomes.map(o => o.outcome_id)) + 1;
                data.outcome_id = newId;
                data.output_id = parseInt(data.output_id);
                mockData.outcomes.push(data);
                showSuccess('เพิ่มผลลัพธ์ใหม่เรียบร้อยแล้ว');
            }

            closeOutcomeModal();
            loadOutcomes();
        });

        document.getElementById('proxyForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());

            if (currentEditingItem) {
                Object.assign(currentEditingItem, data);
                data.outcome_id = parseInt(data.outcome_id);
                showSuccess('แก้ไขตัวแทนมูลค่าเรียบร้อยแล้ว');
            } else {
                const newId = Math.max(...mockData.proxies.map(p => p.proxy_id)) + 1;
                data.proxy_id = newId;
                data.outcome_id = parseInt(data.outcome_id);
                mockData.proxies.push(data);
                showSuccess('เพิ่มตัวแทนมูลค่าใหม่เรียบร้อยแล้ว');
            }

            closeProxyModal();
            loadProxies();
        });

        // Search functions
        function searchProjects(query) {
            const tbody = document.getElementById('projectsTableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchActivities(query) {
            const tbody = document.getElementById('activitiesTableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchOutputs(query) {
            const tbody = document.getElementById('outputsTableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchOutcomes(query) {
            const tbody = document.getElementById('outcomesTableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function searchProxies(query) {
            const tbody = document.getElementById('proxiesTableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                if (text.includes(query.toLowerCase())) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        // Filter functions
        function filterProjectsByStatus(status) {
            const tbody = document.getElementById('projectsTableBody');
            const rows = tbody.querySelectorAll('tr');

            rows.forEach(row => {
                if (!status) {
                    row.style.display = '';
                    return;
                }

                const statusCell = row.cells[5];
                const statusText = statusCell.textContent.includes('เสร็จสิ้น') ? 'completed' : 'incompleted';

                if (statusText === status) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }

        function filterActivitiesByStrategy(strategyId) {
            // Implementation for filtering activities by strategy
            loadActivities();
        }

        function filterOutputsByActivity(activityId) {
            // Implementation for filtering outputs by activity
            loadOutputs();
        }

        function filterOutcomesByOutput(outputId) {
            // Implementation for filtering outcomes by output
            loadOutcomes();
        }

        function filterProxiesByOutcome(outcomeId) {
            // Implementation for filtering proxies by outcome
            loadProxies();
        }

        // Utility functions
        function truncateText(text, maxLength) {
            if (text.length <= maxLength) return text;
            return text.substring(0, maxLength) + '...';
        }

        function formatCurrency(amount) {
            if (!amount) return '-';
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB',
                minimumFractionDigits: 0
            }).format(amount);
        }

        // Close modals when clicking outside
        window.addEventListener('click', function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Initialize
        document.addEventListener('DOMContentLoaded', async function() {
            // โหลดข้อมูลจากฐานข้อมูลก่อน
            await initializeData();

            // จากนั้นโหลดข้อมูลโครงการ
            loadProjects();

            // Add some CSS for status indicators
            const style = document.createElement('style');
            style.textContent = `
                .status {
                    padding: 4px 8px;
                    border-radius: 4px;
                    font-size: 0.85rem;
                    font-weight: 500;
                }
                .status.success {
                    background: #c6f6d5;
                    color: #276749;
                }
                .status.warning {
                    background: #faf089;
                    color: #744210;
                }
                .btn-edit {
                    font-size: 0.8rem;
                }
                .btn-danger {
                    font-size: 0.8rem;
                }
            `;
            document.head.appendChild(style);
        });
    </script>
</body>

</html>