<?php
require_once '../config.php';
session_start();

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    if ($_POST['action'] === 'save_pvf') {
        // Save PVF data - always use "current" as set name for latest data
        $set_name = 'current';
        $discount_rate = floatval($_POST['discount_rate']);
        $pvf_data = json_decode($_POST['pvf_data'], true);
        
        // Start transaction
        mysqli_begin_transaction($conn);
        
        try {
            // Insert or update PVF set
            $set_query = "INSERT INTO pvf_sets (set_name, discount_rate, total_years, is_active, is_default) 
                         VALUES ('$set_name', $discount_rate, 6, 1, 1)
                         ON DUPLICATE KEY UPDATE 
                         discount_rate = $discount_rate, updated_at = NOW(), is_default = 1";
            
            if (!mysqli_query($conn, $set_query)) {
                throw new Exception('ไม่สามารถอัปเดตชุดข้อมูลได้: ' . mysqli_error($conn));
            }
            
            // Delete existing PVF data for current set
            $delete_query = "DELETE FROM present_value_factors WHERE pvf_name = '$set_name'";
            if (!mysqli_query($conn, $delete_query)) {
                throw new Exception('ไม่สามารถลบข้อมูลเก่าได้: ' . mysqli_error($conn));
            }
            
            // Insert new PVF data
            foreach ($pvf_data as $data) {
                $year_id = intval($data['year_id']);
                $time_period = intval($data['time_period']);
                $pvf_value = floatval($data['pvf_value']);
                
                // Validate year_id exists
                $year_check = mysqli_query($conn, "SELECT year_id FROM years WHERE year_id = $year_id AND is_active = 1");
                if (!$year_check || mysqli_num_rows($year_check) == 0) {
                    throw new Exception("Invalid year_id: $year_id for time_period: $time_period");
                }
                
                $insert_query = "INSERT INTO present_value_factors 
                                (pvf_name, discount_rate, year_id, time_period, pvf_value, is_active) 
                                VALUES ('$set_name', $discount_rate, $year_id, $time_period, $pvf_value, 1)";
                
                if (!mysqli_query($conn, $insert_query)) {
                    throw new Exception('ไม่สามารถบันทึกข้อมูล PVF ได้: ' . mysqli_error($conn) . " (year_id: $year_id, time_period: $time_period)");
                }
            }
            
            // Commit transaction
            mysqli_commit($conn);
            echo json_encode(['status' => 'success', 'message' => 'บันทึกข้อมูลสำเร็จ']);
            
        } catch (Exception $e) {
            // Rollback transaction
            mysqli_rollback($conn);
            echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
        }
        exit;
    }
    
    if ($_POST['action'] === 'load_pvf') {
        // Load PVF data
        $set_name = mysqli_real_escape_string($conn, $_POST['set_name']);
        
        $query = "SELECT pvf.*, y.year_display 
                 FROM present_value_factors pvf 
                 LEFT JOIN years y ON pvf.year_id = y.year_id 
                 WHERE pvf.pvf_name = '$set_name' AND pvf.is_active = 1 
                 ORDER BY pvf.time_period";
        
        $result = mysqli_query($conn, $query);
        $pvf_data = [];
        $discount_rate = 2.0;
        
        if ($result && mysqli_num_rows($result) > 0) {
            while ($row = mysqli_fetch_assoc($result)) {
                $pvf_data[] = $row;
                $discount_rate = $row['discount_rate'];
            }
            echo json_encode(['status' => 'success', 'data' => $pvf_data, 'discount_rate' => $discount_rate]);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'ไม่พบข้อมูลชุดที่เลือก']);
        }
        exit;
    }
    
    if ($_POST['action'] === 'get_pvf_sets') {
        // Get all PVF sets
        $query = "SELECT set_id, set_name, discount_rate, created_at, is_default 
                 FROM pvf_sets 
                 WHERE is_active = 1 
                 ORDER BY is_default DESC, created_at DESC";
        
        $result = mysqli_query($conn, $query);
        $sets = [];
        
        if ($result) {
            while ($row = mysqli_fetch_assoc($result)) {
                $sets[] = $row;
            }
        }
        
        echo json_encode(['status' => 'success', 'sets' => $sets]);
        exit;
    }
}

// Fetch years data from database
$years_query = "SELECT year_id, year_be, year_ad, year_display, year_description, sort_order 
                FROM years 
                WHERE is_active = 1 
                ORDER BY sort_order ASC";
$years_result = mysqli_query($conn, $years_query);

$years_data = [];
if ($years_result) {
    while ($row = mysqli_fetch_assoc($years_result)) {
        $years_data[] = $row;
    }
}

// If no years found, use default data
if (empty($years_data)) {
    $years_data = [
        ['year_id' => 1, 'year_be' => '2567', 'year_display' => '2567'],
        ['year_id' => 2, 'year_be' => '2568', 'year_display' => '2568'],
        ['year_id' => 3, 'year_be' => '2569', 'year_display' => '2569'],
        ['year_id' => 4, 'year_be' => '2570', 'year_display' => '2570'],
        ['year_id' => 5, 'year_be' => '25xx', 'year_display' => '25xx'],
        ['year_id' => 6, 'year_be' => '25xx', 'year_display' => '25xx']
    ];
}

// ดึงข้อมูล PVF ที่บันทึกไว้จากฐานข้อมูล
$saved_pvf_query = "SELECT pvf.*, y.year_display 
                    FROM present_value_factors pvf 
                    LEFT JOIN years y ON pvf.year_id = y.year_id 
                    WHERE pvf.pvf_name = 'current' AND pvf.is_active = 1 
                    ORDER BY pvf.time_period ASC";
$saved_pvf_result = mysqli_query($conn, $saved_pvf_query);

$saved_pvf_data = [];
$current_discount_rate = 2.0; // ค่าเริ่มต้น

if ($saved_pvf_result && mysqli_num_rows($saved_pvf_result) > 0) {
    while ($row = mysqli_fetch_assoc($saved_pvf_result)) {
        $saved_pvf_data[$row['time_period']] = [
            'pvf_value' => $row['pvf_value'],
            'year_display' => $row['year_display']
        ];
        $current_discount_rate = $row['discount_rate']; // ใช้ค่าจากฐานข้อมูล
    }
}

// Use only actual years from database, no extra 25xx years

// Fetch existing PVF sets for dropdown
$sets_query = "SELECT set_name, discount_rate, is_default 
              FROM pvf_sets 
              WHERE is_active = 1 
              ORDER BY is_default DESC, created_at DESC";
$sets_result = mysqli_query($conn, $sets_query);
$pvf_sets = [];
if ($sets_result) {
    while ($row = mysqli_fetch_assoc($sets_result)) {
        $pvf_sets[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ฟอร์มจัดการค่าอัตราคิดลดร้อยละ</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Sarabun', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .header h1 {
            font-size: 2rem;
            margin-bottom: 10px;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        }

        .content {
            padding: 30px;
        }

        .form-section {
            margin-bottom: 30px;
            background: #f8f9fa;
            padding: 25px;
            border-radius: 15px;
            border-left: 5px solid #667eea;
        }

        .form-section h2 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #495057;
            font-size: 1rem;
        }

        .form-group input {
            width: 100%;
            max-width: 300px;
            padding: 12px 16px;
            border: 2px solid #e9ecef;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-group input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
            transform: translateY(-1px);
        }

        .calculate-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px 30px;
            border: none;
            border-radius: 10px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .calculate-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .save-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: white;
            padding: 12px 25px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(40, 167, 69, 0.3);
            margin-left: 10px;
        }

        .save-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(40, 167, 69, 0.4);
        }


        .button-group {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }

        th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
            font-size: 1rem;
        }

        th:first-child {
            border-radius: 15px 0 0 0;
        }

        th:last-child {
            border-radius: 0 15px 0 0;
        }

        td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #e9ecef;
            font-size: 1rem;
        }

        tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        tr:hover {
            background-color: #e3f2fd;
            transition: all 0.3s ease;
        }

        .discount-rate-cell {
            background: #fff3cd !important;
            font-weight: bold;
            color: #856404;
        }

        .pvf-cell {
            background: #d1ecf1 !important;
            font-weight: 600;
            color: #0c5460;
        }

        .highlight-header {
            background: #ffc107 !important;
            color: #212529 !important;
            font-weight: bold;
        }

        .year-cell {
            font-weight: 600;
            color: #495057;
        }

        .time-cell {
            font-weight: 600;
            color: #6c757d;
        }

        .formula-info {
            background: #e7f3ff;
            border: 1px solid #b3d9ff;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
        }

        .formula-info h3 {
            color: #0066cc;
            margin-bottom: 10px;
        }

        .formula {
            font-family: 'Courier New', monospace;
            background: #f8f9fa;
            padding: 10px;
            border-radius: 5px;
            font-size: 1rem;
            color: #495057;
        }

        @media (max-width: 768px) {
            .content {
                padding: 20px;
            }

            .header h1 {
                font-size: 1.5rem;
            }

            .form-group input {
                max-width: 100%;
            }

            table {
                font-size: 0.9rem;
            }

            th,
            td {
                padding: 8px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h1>🎯 ระบบจัดการค่าอัตราคิดลดร้อยละ</h1>
            <p>Present Value Factor Calculator</p>
        </div>

        <div class="content">
            <div class="form-section">
                <h2>📊 กำหนดค่าอัตราคิดลด</h2>
                <div class="form-group">
                    <label for="discountRate">🔢 อัตราคิดลดร้อยละ (Discount Rate %):</label>
                    <input type="number" id="discountRate" value="<?php echo $current_discount_rate; ?>" min="0" max="100" step="0.1" placeholder="กรอกอัตราคิดลด">
                </div>
                <div class="button-group">
                    <button class="calculate-btn" onclick="calculatePVF()">
                        ⚡ คำนวณค่า Present Value Factor
                    </button>
                    <button class="save-btn" onclick="savePVF()">
                        💾 บันทึกข้อมูล
                    </button>
                </div>
            </div>

            <div class="table-container">
                <table id="pvfTable">
                    <thead>
                        <tr>
                            <th rowspan="2">ปี พ.ศ.</th>
                            <th class="highlight-header">กำหนดค่า<br>อัตราคิดลด<br>ร้อยละ</th>
                            <?php for ($i = 1; $i < count($years_data); $i++): ?>
                            <th></th>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <?php foreach ($years_data as $year): ?>
                            <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="year-cell">t</td>
                            <?php for ($t = 0; $t < count($years_data); $t++): ?>
                            <td class="time-cell"><?php echo $t; ?></td>
                            <?php endfor; ?>
                        </tr>
                        <tr>
                            <td class="year-cell">Present Value Factor</td>
                            <?php for ($t = 0; $t < count($years_data); $t++): ?>
                            <td class="pvf-cell" id="pvf<?php echo $t; ?>">
                                <?php 
                                if (isset($saved_pvf_data[$t])) {
                                    // แสดงค่าจากฐานข้อมูล
                                    echo number_format($saved_pvf_data[$t]['pvf_value'], 2);
                                } else {
                                    // คำนวณค่าเริ่มต้นถ้าไม่มีในฐานข้อมูล
                                    echo number_format(1 / pow(1 + ($current_discount_rate/100), $t), 2);
                                }
                                ?>
                            </td>
                            <?php endfor; ?>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="formula-info">
                <h3>📐 สูตรการคำนวณ Present Value Factor</h3>
                <div class="formula">
                    PVF = 1 / (1 + อัตราคิดลด/100)^t
                </div>
                <p style="margin-top: 10px; color: #6c757d;">
                    โดย t = ระยะเวลา (ปี) และ อัตราคิดลด = อัตราส่วนลดในรูปร้อยละ
                </p>
            </div>
        </div>
    </div>

    <script>
        // Get the number of years from PHP
        const totalYears = <?php echo count($years_data); ?>;
        
        // Get years data from PHP
        const yearsData = <?php echo json_encode($years_data); ?>;
        
        // ข้อมูล PVF จากฐานข้อมูล
        const savedPVFData = <?php echo json_encode($saved_pvf_data); ?>;
        
        // อัปเดต header และโหลดข้อมูลเมื่อโหลดหน้า
        window.onload = function() {
            const currentRate = <?php echo $current_discount_rate; ?>;
            const discountRateCell = document.querySelector('.highlight-header');
            if (discountRateCell) {
                discountRateCell.innerHTML = `กำหนดค่า<br>อัตราคิดลด<br>${currentRate}%`;
            }
            
            // แสดงข้อมูล PVF ที่บันทึกไว้ (ถ้ามี)
            loadSavedPVFData();
        };
        
        // ฟังก์ชันโหลดข้อมูล PVF ที่บันทึกไว้
        function loadSavedPVFData() {
            if (Object.keys(savedPVFData).length > 0) {
                for (let t = 0; t < totalYears; t++) {
                    const cell = document.getElementById(`pvf${t}`);
                    if (cell && savedPVFData[t]) {
                        cell.textContent = parseFloat(savedPVFData[t].pvf_value).toFixed(2);
                    }
                }
                showNotification('โหลดข้อมูล PVF จากฐานข้อมูลสำเร็จ', 'info');
            }
        }

        function calculatePVF() {
            const discountRate = parseFloat(document.getElementById('discountRate').value) || 0;

            // อัปเดตค่าใน header
            const discountRateCell = document.querySelector('.highlight-header');
            discountRateCell.innerHTML = `กำหนดค่า<br>อัตราคิดลด<br>${discountRate}%`;

            // คำนวณและอัปเดต Present Value Factor
            for (let t = 0; t < totalYears; t++) {
                const pvf = 1 / Math.pow(1 + (discountRate / 100), t);
                const cell = document.getElementById(`pvf${t}`);
                if (cell) {
                    cell.textContent = pvf.toFixed(2);

                    // เพิ่มเอฟเฟกต์แอนิเมชัน
                    cell.style.background = '#28a745';
                    cell.style.color = 'white';
                    cell.style.transform = 'scale(1.1)';

                    setTimeout(() => {
                        cell.style.background = '#d1ecf1';
                        cell.style.color = '#0c5460';
                        cell.style.transform = 'scale(1)';
                    }, 500);
                }
            }

            // แสดงการแจ้งเตือน
            showNotification('คำนวณค่า Present Value Factor เสร็จสิ้น!');
        }

        function showNotification(message, type = 'success') {
            const notification = document.createElement('div');
            notification.textContent = message;
            
            const bgColor = type === 'success' ? '#28a745' : 
                          type === 'error' ? '#dc3545' : 
                          type === 'warning' ? '#ffc107' : '#17a2b8';
            const textColor = type === 'warning' ? '#212529' : 'white';
            
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${bgColor};
                color: ${textColor};
                padding: 15px 20px;
                border-radius: 10px;
                box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
                z-index: 1000;
                font-weight: 600;
                animation: slideIn 0.3s ease;
                max-width: 300px;
            `;

            // เพิ่ม CSS animation
            const style = document.createElement('style');
            style.textContent = `
                @keyframes slideIn {
                    from { transform: translateX(100%); opacity: 0; }
                    to { transform: translateX(0); opacity: 1; }
                }
            `;
            document.head.appendChild(style);

            document.body.appendChild(notification);

            setTimeout(() => {
                notification.style.animation = 'slideIn 0.3s ease reverse';
                setTimeout(() => notification.remove(), 300);
            }, 3000);
        }

        // Save PVF data
        function savePVF() {
            const discountRate = parseFloat(document.getElementById('discountRate').value) || 0;
            
            if (discountRate <= 0) {
                showNotification('กรุณากรอกอัตราคิดลดที่ถูกต้อง', 'error');
                return;
            }
            
            // Collect PVF data - only for years with valid year_id
            const pvfData = [];
            for (let t = 0; t < totalYears; t++) {
                const cell = document.getElementById(`pvf${t}`);
                if (cell && yearsData[t] && yearsData[t].year_id !== null) {
                    pvfData.push({
                        year_id: yearsData[t].year_id,
                        time_period: t,
                        pvf_value: parseFloat(cell.textContent)
                    });
                }
            }
            
            // Send AJAX request
            const formData = new FormData();
            formData.append('action', 'save_pvf');
            formData.append('discount_rate', discountRate);
            formData.append('pvf_data', JSON.stringify(pvfData));
            
            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    showNotification(`อัปเดตข้อมูลสำเร็จ (อัตราคิดลด ${discountRate}%)`, 'success');
                } else {
                    showNotification(data.message, 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('เกิดข้อผิดพลาดในการบันทึกข้อมูล', 'error');
            });
        }
        

        // เรียกใช้การคำนวณครั้งแรกเมื่อโหลดหน้า
        window.onload = function() {
            calculatePVF();
        };

        // อัปเดตแบบเรียลไทม์เมื่อเปลี่ยนค่า
        document.getElementById('discountRate').addEventListener('input', function() {
            calculatePVF();
        });
    </script>
</body>

</html>