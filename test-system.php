<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ทดสอบระบบ Impact Chain - SROI System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body>
    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h2><i class="fas fa-cogs"></i> ทดสอบระบบ Impact Chain</h2>
                <p class="text-muted">ระบบได้รับการปรับปรุงให้บันทึกข้อมูลจริงลงฐานข้อมูลแล้ว</p>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5><i class="fas fa-database"></i> สถานะฐานข้อมูล</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        require_once 'config.php';

                        // ตรวจสอบตารางที่จำเป็น
                        $tables_to_check = [
                            'project_strategies' => 'ตารางเก็บการเลือกยุทธศาสตร์',
                            'project_activities' => 'ตารางเก็บการเลือกกิจกรรม',
                            'project_outputs' => 'ตารางเก็บการเลือกผลผลิต'
                        ];

                        $all_exists = true;
                        foreach ($tables_to_check as $table => $description) {
                            $check_query = "SHOW TABLES LIKE '$table'";
                            $result = mysqli_query($conn, $check_query);

                            if (mysqli_num_rows($result) > 0) {
                                echo "<p class='text-success'><i class='fas fa-check'></i> $description ($table) - พร้อมใช้งาน</p>";
                            } else {
                                echo "<p class='text-danger'><i class='fas fa-times'></i> $description ($table) - ไม่พบตาราง</p>";
                                $all_exists = false;
                            }
                        }

                        // ตรวจสอบ output_details column
                        $check_column = "SHOW COLUMNS FROM project_outputs LIKE 'output_details'";
                        $column_result = mysqli_query($conn, $check_column);
                        if (mysqli_num_rows($column_result) > 0) {
                            echo "<p class='text-success'><i class='fas fa-check'></i> ฟิลด์ output_details - พร้อมใช้งาน</p>";
                        } else {
                            echo "<p class='text-warning'><i class='fas fa-exclamation-triangle'></i> ฟิลด์ output_details - ยังไม่ได้อัปเดต</p>";
                        }

                        if ($all_exists) {
                            echo "<div class='alert alert-success'><i class='fas fa-check-circle'></i> ฐานข้อมูลพร้อมใช้งาน</div>";
                        } else {
                            echo "<div class='alert alert-warning'><i class='fas fa-exclamation-triangle'></i> กรุณารันไฟล์ database-update.sql ก่อนใช้งาน</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5><i class="fas fa-list"></i> การเปลี่ยนแปลงระบบ</h5>
                    </div>
                    <div class="card-body">
                        <h6>✅ สิ่งที่ปรับปรุงแล้ว:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-check text-success"></i> บันทึกการเลือกยุทธศาสตร์ลงฐานข้อมูล</li>
                            <li><i class="fas fa-check text-success"></i> บันทึกการเลือกกิจกรรมลงฐานข้อมูล</li>
                            <li><i class="fas fa-check text-success"></i> บันทึกการเลือกผลผลิตลงฐานข้อมูล</li>
                            <li><i class="fas fa-check text-success"></i> อ่านข้อมูลจากฐานข้อมูลแทน session</li>
                            <li><i class="fas fa-check text-success"></i> ตรวจสอบความถูกต้องของข้อมูล</li>
                        </ul>

                        <h6 class="mt-3">🔄 การทำงานใหม่:</h6>
                        <ul class="list-unstyled">
                            <li><i class="fas fa-arrow-right text-primary"></i> ลบข้อมูลเดิมเมื่อเปลี่ยนแปลงการเลือก</li>
                            <li><i class="fas fa-arrow-right text-primary"></i> ตรวจสอบความสัมพันธ์ของข้อมูล</li>
                            <li><i class="fas fa-arrow-right text-primary"></i> บันทึก audit log อัตโนมัติ</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-info text-white">
                        <h5><i class="fas fa-play-circle"></i> เริ่มทดสอบระบบ</h5>
                    </div>
                    <div class="card-body">
                        <p>เลือกโครงการที่ต้องการทดสอบระบบ Impact Chain:</p>

                        <?php
                        session_start();
                        if (isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true) {
                            $user_id = $_SESSION['user_id'];
                            $projects_query = "SELECT * FROM projects WHERE created_by = ? ORDER BY created_at DESC LIMIT 5";
                            $projects_stmt = mysqli_prepare($conn, $projects_query);
                            mysqli_stmt_bind_param($projects_stmt, 's', $user_id);
                            mysqli_stmt_execute($projects_stmt);
                            $projects_result = mysqli_stmt_get_result($projects_stmt);

                            if (mysqli_num_rows($projects_result) > 0) {
                                echo "<div class='table-responsive'>";
                                echo "<table class='table table-hover'>";
                                echo "<thead><tr><th>รหัสโครงการ</th><th>ชื่อโครงการ</th><th>สถานะ</th><th>การดำเนินการ</th></tr></thead>";
                                echo "<tbody>";

                                while ($project = mysqli_fetch_assoc($projects_result)) {
                                    echo "<tr>";
                                    echo "<td>" . htmlspecialchars($project['project_code']) . "</td>";
                                    echo "<td>" . htmlspecialchars($project['name']) . "</td>";
                                    echo "<td>";
                                    if ($project['status'] == 'completed') {
                                        echo "<span class='badge bg-success'>เสร็จสิ้น</span>";
                                    } else {
                                        echo "<span class='badge bg-warning'>ดำเนินการ</span>";
                                    }
                                    echo "</td>";
                                    echo "<td>";
                                    echo "<a href='impact-chain/step1-strategy.php?project_id=" . $project['id'] . "' class='btn btn-primary btn-sm'>";
                                    echo "<i class='fas fa-play'></i> เริ่มทดสอบ Impact Chain";
                                    echo "</a>";
                                    echo "</td>";
                                    echo "</tr>";
                                }

                                echo "</tbody></table></div>";
                            } else {
                                echo "<div class='alert alert-info'>";
                                echo "<i class='fas fa-info-circle'></i> ไม่พบโครงการ ";
                                echo "<a href='create-project.php' class='btn btn-primary btn-sm ms-2'>สร้างโครงการใหม่</a>";
                                echo "</div>";
                            }
                            mysqli_stmt_close($projects_stmt);
                        } else {
                            echo "<div class='alert alert-warning'>";
                            echo "<i class='fas fa-sign-in-alt'></i> กรุณาเข้าสู่ระบบก่อน ";
                            echo "<a href='login.php' class='btn btn-primary btn-sm ms-2'>เข้าสู่ระบบ</a>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mt-4">
            <div class="col-12">
                <div class="text-center">
                    <a href="project-list.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> กลับไปรายการโครงการ
                    </a>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>