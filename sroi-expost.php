<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>รายงาน SROI Ex-post Analysis</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
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
            padding: 20px;
            color: #333;
        }

        .container {
            max-width: 1400px;
            margin: 0 auto;
        }

        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            border-radius: 20px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.3);
        }

        .header h1 {
            font-size: 2.5em;
            margin-bottom: 10px;
            font-weight: 700;
        }

        .header p {
            font-size: 1.1em;
            opacity: 0.9;
        }

        .project-info {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #667eea;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 20px;
        }

        .info-item {
            padding: 15px;
            background: #f8f9ff;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .info-item label {
            font-weight: 600;
            color: #667eea;
            display: block;
            margin-bottom: 5px;
        }

        .info-item span {
            font-size: 1.1em;
            color: #333;
        }

        .settings-section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .settings-header {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .settings-header h2 {
            color: #667eea;
            margin-right: 15px;
        }

        .discount-rate {
            display: inline-flex;
            align-items: center;
            background: #f0f4ff;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: 600;
            color: #667eea;
        }

        .year-header {
            display: grid;
            grid-template-columns: 200px repeat(6, 1fr);
            gap: 10px;
            background: #667eea;
            color: white;
            padding: 15px;
            border-radius: 10px;
            font-weight: 600;
            margin-bottom: 10px;
        }

        .section {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .section-title {
            color: #667eea;
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #667eea;
        }

        .data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.08);
        }

        .data-table th {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 15px;
            text-align: center;
            font-weight: 600;
        }

        .data-table td {
            padding: 12px 15px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .data-table tr:hover {
            background: #f8f9ff;
        }

        .cost-row {
            background: #fff5f5;
        }

        .benefit-row {
            background: #f0fff4;
        }

        .impact-row {
            background: #fffaf0;
        }

        .total-row {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            font-weight: bold;
        }

        .total-row td {
            border-bottom: none;
        }

        .metric-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .metric-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 25px;
            border-radius: 15px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.3);
            transition: transform 0.3s ease;
        }

        .metric-card:hover {
            transform: translateY(-5px);
        }

        .metric-value {
            font-size: 2.2em;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .metric-label {
            font-size: 0.9em;
            opacity: 0.9;
        }

        .chart-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .chart-wrapper {
            position: relative;
            height: 400px;
            margin-top: 20px;
        }

        .formula-box {
            background: #f0f4ff;
            border: 2px solid #667eea;
            border-radius: 10px;
            padding: 20px;
            margin-top: 20px;
            text-align: center;
        }

        .formula-box h3 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .formula {
            font-size: 1.2em;
            font-weight: 600;
            color: #333;
            font-family: 'Courier New', monospace;
        }

        .highlight-positive {
            color: #28a745;
            font-weight: bold;
        }

        .highlight-negative {
            color: #dc3545;
            font-weight: bold;
        }

        .controls {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .control-group {
            display: flex;
            align-items: center;
            gap: 15px;
            margin-bottom: 15px;
        }

        .control-group label {
            font-weight: 600;
            color: #667eea;
            min-width: 150px;
        }

        .control-group select,
        .control-group input {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .control-group select:focus,
        .control-group input:focus {
            border-color: #667eea;
            outline: none;
        }

        .btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }

        .sensitivity-analysis {
            background: white;
            border-radius: 15px;
            padding: 25px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }

        .analysis-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
        }

        @media (max-width: 768px) {
            .analysis-grid {
                grid-template-columns: 1fr;
            }

            .year-header {
                grid-template-columns: 150px repeat(3, 1fr);
                font-size: 0.8em;
            }

            .metric-cards {
                grid-template-columns: 1fr;
            }

            .header h1 {
                font-size: 1.8em;
            }
        }

        .footer {
            background: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            color: #666;
        }

        .impact-breakdown {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .impact-item {
            background: #f8f9ff;
            padding: 20px;
            border-radius: 10px;
            border-left: 4px solid #667eea;
        }

        .impact-item h4 {
            color: #667eea;
            margin-bottom: 10px;
        }

        .impact-value {
            font-size: 1.3em;
            font-weight: bold;
            color: #333;
        }
    </style>
</head>

<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <h1>รายงาน SROI Ex-post Analysis</h1>
            <p>การวิเคราะห์ผลตอบแทนทางสังคมจากการลงทุนหลังการดำเนินงาน</p>
        </div>

        <!-- Project Info -->
        <div class="project-info">
            <h2 style="color: #667eea; margin-bottom: 20px;">ข้อมูลโครงการ</h2>
            <div class="info-grid">
                <div class="info-item">
                    <label>รหัสโครงการ:</label>
                    <span id="project-code">681234568</span>
                </div>
                <div class="info-item">
                    <label>ชื่อโครงการ:</label>
                    <span id="project-name">โครงการพัฒนาศูนย์การเรียนรู้ด้านพลังงานเพื่อการพัฒนาการบริหารจัดการทรัพยากรชุมชนอย่าง</span>
                </div>
                <div class="info-item">
                    <label>หน่วยงาน:</label>
                    <span id="organization">คณะวิทยาศาสตร์และเทคโนโลยี</span>
                </div>
                <div class="info-item">
                    <label>ผู้จัดการโครงการ:</label>
                    <span id="manager">สังสรรค์ หล้าพันธ์</span>
                </div>
                <div class="info-item">
                    <label>งบประมาณ:</label>
                    <span id="budget">70,000 บาท</span>
                </div>
                <div class="info-item">
                    <label>ปีที่ประเมิน:</label>
                    <span id="evaluation-year">2568</span>
                </div>
            </div>
        </div>

        <!-- Settings -->
        <div class="settings-section">
            <div class="settings-header">
                <h2>การตั้งค่าการคำนวณ</h2>
                <div class="discount-rate">
                    <span>อัตราคิดลด: </span>
                    <strong id="discount-rate">2.0%</strong>
                </div>
            </div>
        </div>

        <!-- Controls -->
        <div class="controls">
            <h2 style="color: #667eea; margin-bottom: 20px;">เลือกโครงการและปีที่ต้องการวิเคราะห์</h2>
            <div class="control-group">
                <label>เลือกโครงการ:</label>
                <select id="project-select">
                    <option value="4">โครงการพัฒนาศูนย์การเรียนรู้ด้านพลังงาน</option>
                    <option value="3">การพัฒนานวัตกรการศึกษาด้วย Mobile Application</option>
                    <option value="7">ส่งเสริมการปลูกผักปลอดสารพิษ</option>
                </select>
            </div>
            <div class="control-group">
                <label>ปีที่ประเมิน:</label>
                <select id="year-select">
                    <option value="2567">2567</option>
                    <option value="2568" selected>2568</option>
                    <option value="2569">2569</option>
                    <option value="2570">2570</option>
                </select>
            </div>
            <div class="control-group">
                <label>อัตราคิดลด (%):</label>
                <input type="number" id="discount-input" value="2.0" min="0" max="20" step="0.1">
            </div>
            <button class="btn" onclick="updateAnalysis()">อัปเดตการวิเคราะห์</button>
        </div>

        <!-- Year Headers -->
        <div class="year-header">
            <div>รายการ</div>
            <div>2567 (t=0)</div>
            <div>2568 (t=1)</div>
            <div>2569 (t=2)</div>
            <div>2570 (t=3)</div>
            <div>2571 (t=4)</div>
            <div>2572 (t=5)</div>
        </div>

        <!-- Cost Section -->
        <div class="section">
            <h2 class="section-title">ต้นทุนโครงการ (Cost)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>รายการต้นทุน</th>
                        <th>2567</th>
                        <th>2568</th>
                        <th>2569</th>
                        <th>2570</th>
                        <th>2571</th>
                        <th>2572</th>
                    </tr>
                </thead>
                <tbody id="cost-table-body">
                    <tr class="cost-row">
                        <td>ต้นทุน 1</td>
                        <td>7,500</td>
                        <td>8,000</td>
                        <td>21,132</td>
                        <td>23,412,312</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                    <tr class="cost-row">
                        <td>ต้นทุน 2</td>
                        <td>1,231</td>
                        <td>3,213</td>
                        <td>212,333</td>
                        <td>21,323,123</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                    <tr class="total-row">
                        <td>รวม (Cost)</td>
                        <td>8,731</td>
                        <td>11,213</td>
                        <td>233,465</td>
                        <td>44,735,435</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                    <tr class="total-row">
                        <td>ต้นทุนปัจจุบันสุทธิ (Present Cost)</td>
                        <td>8,731</td>
                        <td>10,993</td>
                        <td>220,388</td>
                        <td>41,946,528</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                </tbody>
            </table>
            <div class="metric-cards">
                <div class="metric-card">
                    <div class="metric-value" id="total-present-cost">42,186,640</div>
                    <div class="metric-label">ต้นทุนรวมปัจจุบัน (บาท)</div>
                </div>
            </div>
        </div>

        <!-- Base Case Impact Section -->
        <div class="section">
            <h2 class="section-title">ผลกระทบกรณีฐาน (Base Case Impact)</h2>

            <!-- Attribution -->
            <h3 style="color: #667eea; margin-bottom: 15px;">ผลจากปัจจัยอื่นๆ (Attribution)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>รายการ</th>
                        <th>2567</th>
                        <th>2568</th>
                        <th>2569</th>
                        <th>2570</th>
                        <th>2571</th>
                        <th>2572</th>
                    </tr>
                </thead>
                <tbody id="attribution-table-body">
                    <tr class="impact-row">
                        <td>ผลประโยชน์ 1 (Attribution 12%)</td>
                        <td>660</td>
                        <td>660</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                </tbody>
            </table>

            <!-- Deadweight -->
            <h3 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ส่วนเกิน (Deadweight)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>รายการ</th>
                        <th>2567</th>
                        <th>2568</th>
                        <th>2569</th>
                        <th>2570</th>
                        <th>2571</th>
                        <th>2572</th>
                    </tr>
                </thead>
                <tbody id="deadweight-table-body">
                    <tr class="impact-row">
                        <td>ผลประโยชน์ 1 (Deadweight 12%)</td>
                        <td>660</td>
                        <td>660</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                </tbody>
            </table>

            <!-- Displacement -->
            <h3 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ทดแทน (Displacement)</h3>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>รายการ</th>
                        <th>2567</th>
                        <th>2568</th>
                        <th>2569</th>
                        <th>2570</th>
                        <th>2571</th>
                        <th>2572</th>
                    </tr>
                </thead>
                <tbody id="displacement-table-body">
                    <tr class="impact-row">
                        <td>ผลประโยชน์ 1 (Displacement 12%)</td>
                        <td>660</td>
                        <td>660</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                </tbody>
            </table>

            <div class="metric-cards">
                <div class="metric-card">
                    <div class="metric-value" id="total-base-case">2,616</div>
                    <div class="metric-label">ผลกระทบกรณีฐานรวมปัจจุบัน (บาท)</div>
                </div>
            </div>
        </div>

        <!-- Benefit Section -->
        <div class="section">
            <h2 class="section-title">ผลประโยชน์ของโครงการ (Benefit)</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>รายการผลประโยชน์</th>
                        <th>2567</th>
                        <th>2568</th>
                        <th>2569</th>
                        <th>2570</th>
                        <th>2571</th>
                        <th>2572</th>
                    </tr>
                </thead>
                <tbody id="benefit-table-body">
                    <tr class="benefit-row">
                        <td>ผลประโยชน์ 1</td>
                        <td>5,500</td>
                        <td>5,500</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                    <tr class="total-row">
                        <td>รวม (Benefit)</td>
                        <td>5,500</td>
                        <td>5,500</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                    <tr class="total-row">
                        <td>ผลประโยชน์ปัจจุบันสุทธิ (Present Benefit)</td>
                        <td>5,500</td>
                        <td>5,392</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                        <td>0</td>
                    </tr>
                </tbody>
            </table>
            <div class="metric-cards">
                <div class="metric-card">
                    <div class="metric-value" id="total-present-benefit">10,892</div>
                    <div class="metric-label">รวมผลประโยชน์ปัจจุบันสุทธิ (บาท)</div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="section">
            <h2 class="section-title">ผลการวิเคราะห์ SROI</h2>

            <div class="metric-cards">
                <div class="metric-card">
                    <div class="metric-value" id="npv">-42,178,364</div>
                    <div class="metric-label">NPV (บาท)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="sroi-ratio">0.0003</div>
                    <div class="metric-label">SROI (เท่า)</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value" id="irr">N/A</div>
                    <div class="metric-label">IRR (%)</div>
                </div>
            </div>

            <div class="impact-breakdown">
                <div class="impact-item">
                    <h4>ผลประโยชน์รวมก่อนหักลบ</h4>
                    <div class="impact-value" id="gross-benefit">10,892 บาท</div>
                </div>
                <div class="impact-item">
                    <h4>ผลกระทบกรณีฐาน</h4>
                    <div class="impact-value" id="base-case-impact">2,616 บาท</div>
                </div>
                <div class="impact-item">
                    <h4>ผลประโยชน์สุทธิหลังหักลบกรณีฐาน</h4>
                    <div class="impact-value" id="net-social-benefit">8,276 บาท</div>
                </div>
                <div class="impact-item">
                    <h4>ต้นทุนโครงการรวม</h4>
                    <div class="impact-value" id="total-cost">42,186,640 บาท</div>
                </div>
            </div>

            <div class="formula-box">
                <h3>สูตรการคำนวณ SROI</h3>
                <div class="formula">
                    SROI = (ผลประโยชน์ปัจจุบันสุทธิ - ผลกระทบกรณีฐาน) ÷ ต้นทุนปัจจุบันสุทธิ
                </div>
                <div class="formula" style="margin-top: 10px;">
                    SROI = (10,892 - 2,616) ÷ 42,186,640 = 0.0002 เท่า
                </div>
            </div>
        </div>

        <!-- Charts Section -->
        <div class="chart-container">
            <h2 class="section-title">กราฟแสดงผลการวิเคราะห์</h2>
            <div class="analysis-grid">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px;">เปรียบเทียบต้นทุนและผลประโยชน์</h3>
                    <div class="chart-wrapper">
                        <canvas id="costBenefitChart"></canvas>
                    </div>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px;">แยกส่วนผลประโยชน์</h3>
                    <div class="chart-wrapper">
                        <canvas id="benefitBreakdownChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <!-- Impact Distribution Chart -->
        <div class="chart-container">
            <h3 style="color: #667eea; margin-bottom: 15px;">การกระจายผลกระทบตามปี</h3>
            <div class="chart-wrapper">
                <canvas id="impactDistributionChart"></canvas>
            </div>
        </div>

        <!-- Sensitivity Analysis -->
        <div class="sensitivity-analysis">
            <h2 class="section-title">การวิเคราะห์ความไว (Sensitivity Analysis)</h2>
            <div class="analysis-grid">
                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px;">ผลกระทบของอัตราคิดลด</h3>
                    <div class="chart-wrapper">
                        <canvas id="sensitivityChart"></canvas>
                    </div>
                </div>
                <div>
                    <h3 style="color: #667eea; margin-bottom: 15px;">สถานการณ์จำลอง</h3>
                    <table class="data-table" style="font-size: 0.9em;">
                        <thead>
                            <tr>
                                <th>สถานการณ์</th>
                                <th>อัตราคิดลด</th>
                                <th>SROI</th>
                                <th>NPV</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>ดีที่สุด</td>
                                <td>1%</td>
                                <td id="best-sroi">0.0002</td>
                                <td id="best-npv">-42,178,256</td>
                            </tr>
                            <tr class="highlight-positive">
                                <td>ปัจจุบัน</td>
                                <td>2%</td>
                                <td id="current-sroi">0.0002</td>
                                <td id="current-npv">-42,178,364</td>
                            </tr>
                            <tr>
                                <td>เลวที่สุด</td>
                                <td>5%</td>
                                <td id="worst-sroi">0.0002</td>
                                <td id="worst-npv">-42,178,580</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Detailed Impact Pathway -->
        <div class="section">
            <h2 class="section-title">เส้นทางผลกระทบ (Impact Pathway)</h2>
            <div class="impact-breakdown">
                <div class="impact-item">
                    <h4>Input (ปัจจัยนำเข้า)</h4>
                    <div style="margin-top: 10px;">
                        <div>งบประมาณ: 70,000 บาท</div>
                        <div>บุคลากร: 3 คน</div>
                        <div>ระยะเวลา: 12 เดือน</div>
                    </div>
                </div>
                <div class="impact-item">
                    <h4>Activities (กิจกรรม)</h4>
                    <div style="margin-top: 10px;">
                        <div>จัดตั้งกลุ่มวิสาหกิจชุมชน</div>
                        <div>อบรมและพัฒนาทักษะ</div>
                        <div>สร้างเครือข่าย</div>
                    </div>
                </div>
                <div class="impact-item">
                    <h4>Outputs (ผลผลิต)</h4>
                    <div style="margin-top: 10px;">
                        <div>กลุ่มวิสาหกิจ: 22 กลุ่ม</div>
                        <div>ผู้ได้รับการอบรม: 150 คน</div>
                        <div>เครือข่าย: 5 เครือข่าย</div>
                    </div>
                </div>
                <div class="impact-item">
                    <h4>Outcomes (ผลลัพธ์)</h4>
                    <div style="margin-top: 10px;">
                        <div>รายได้เพิ่มขึ้น: 5,500 บาท/ปี</div>
                        <div>ต้นทุนลดลง: 0 บาท/ปี</div>
                        <div>จำนวนผู้ได้ประโยชน์: 100 คน</div>
                    </div>
                </div>
                <div class="impact-item">
                    <h4>Impact (ผลกระทบ)</h4>
                    <div style="margin-top: 10px;">
                        <div>ผลกระทบทางเศรษฐกิจ: 8,276 บาท</div>
                        <div>ผลกระทบทางสังคม: บวก</div>
                        <div>ความยั่งยืน: 2 ปี</div>
                    </div>
                </div>
                <div class="impact-item">
                    <h4>Impact Ratio</h4>
                    <div style="margin-top: 10px;">
                        <div>Attribution: 12%</div>
                        <div>Deadweight: 12%</div>
                        <div>Displacement: 12%</div>
                        <div>Impact Ratio: 0.64</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Assumptions and Risks -->
        <div class="section">
            <h2 class="section-title">สมมติฐานและความเสี่ยง</h2>
            <div class="analysis-grid">
                <div class="impact-item">
                    <h4>สมมติฐานหลัก</h4>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>กลุ่มวิสาหกิจสามารถดำเนินการได้อย่างต่อเนื่อง</li>
                        <li>ผู้ได้รับการอบรมจะนำความรู้ไปใช้ประโยชน์</li>
                        <li>ตลาดมีความต้องการผลิตภัณฑ์จากกลุ่ม</li>
                        <li>การสนับสนุนจากหน่วยงานต่างๆ คงอยู่</li>
                    </ul>
                </div>
                <div class="impact-item">
                    <h4>ความเสี่ยงที่สำคัญ</h4>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>กลุ่มอาจไม่สามารถดำเนินการต่อได้</li>
                        <li>การเปลี่ยนแปลงของสภาวะตลาด</li>
                        <li>การขาดการสนับสนุนจากชุมชน</li>
                        <li>ปัญหาการจัดการและภาวะผู้นำ</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Recommendations -->
        <div class="section">
            <h2 class="section-title">ข้อเสนอแนะ</h2>
            <div class="impact-breakdown">
                <div class="impact-item">
                    <h4>ข้อเสนอแนะเชิงนโยบาย</h4>
                    <div style="margin-top: 10px;">
                        <p>จากผลการวิเคราะห์ SROI ที่มีค่า 0.0002 เท่า แสดงให้เห็นว่าโครงการมีความคุ้มค่าน้อยมาก เนื่องจากต้นทุนที่สูงเกินไปเมื่อเทียบกับผลประโยชน์ที่เกิดขึ้น</p>
                    </div>
                </div>
                <div class="impact-item">
                    <h4>การปรับปรุงที่แนะนำ</h4>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>ทบทวนและลดต้นทุนการดำเนินงาน</li>
                        <li>เพิ่มประสิทธิภาพการใช้ทรัพยากร</li>
                        <li>ขยายผลการดำเนินงานให้มีผู้ได้รับประโยชน์มากขึ้น</li>
                        <li>พัฒนาแนวทางการสร้างผลประโยชน์ที่ยั่งยืน</li>
                    </ul>
                </div>
                <div class="impact-item">
                    <h4>การติดตามและประเมินผล</h4>
                    <ul style="margin-top: 10px; padding-left: 20px;">
                        <li>จัดทำระบบติดตามผลลัพธ์แบบต่อเนื่อง</li>
                        <li>ประเมินผลกระทบระยะยาว</li>
                        <li>ปรับปรุงวิธีการดำเนินงานตามผลการประเมิน</li>
                        <li>สร้างกลไกการเรียนรู้และแบ่งปันประสบการณ์</li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <p><strong>รายงานจัดทำโดย:</strong> ระบบการประเมิน SROI</p>
            <p><strong>วันที่จัดทำ:</strong> <span id="report-date"></span></p>
            <p><strong>หมายเหตุ:</strong> การวิเคราะห์นี้ใช้วิธีการ SROI Ex-post Analysis ตามแนวทางของ Social Value UK</p>
        </div>
    </div>

    <script>
        // Initialize data and charts
        let currentProjectId = 4;
        let currentYear = '2568';
        let currentDiscountRate = 2.0;

        // Sample project data
        const projectData = {
            4: {
                code: '681234568',
                name: 'โครงการพัฒนาศูนย์การเรียนรู้ด้านพลังงานเพื่อการพัฒนาการบริหารจัดการทรัพยากรชุมชนอย่าง',
                organization: 'คณะวิทยาศาสตร์และเทคโนโลยี',
                manager: 'สังสรรค์ หล้าพันธ์',
                budget: 70000,
                costs: {
                    2567: {
                        'ต้นทุน 1': 7500,
                        'ต้นทุน 2': 1231
                    },
                    2568: {
                        'ต้นทุน 1': 8000,
                        'ต้นทุน 2': 3213
                    },
                    2569: {
                        'ต้นทุน 1': 21132,
                        'ต้นทุน 2': 212333
                    },
                    2570: {
                        'ต้นทุน 1': 23412312,
                        'ต้นทุน 2': 21323123
                    }
                },
                benefits: {
                    2567: {
                        'ผลประโยชน์ 1': 5500
                    },
                    2568: {
                        'ผลประโยชน์ 1': 5500
                    }
                },
                impactRatios: {
                    attribution: 12,
                    deadweight: 12,
                    displacement: 12,
                    impactRatio: 0.64
                }
            },
            3: {
                code: '681234567',
                name: 'การพัฒนานวัตกรการศึกษาด้วย Mobile Application',
                organization: 'คณะวิทยาศาสตร์และเทคโนโลยี',
                manager: 'สังสรรค์ หล้าพันธ์',
                budget: 70000,
                costs: {
                    2567: {
                        'ต้นทุน 1': 15000,
                        'ต้นทุน 2': 5000
                    },
                    2568: {
                        'ต้นทุน 1': 20000,
                        'ต้นทุน 2': 10000
                    }
                },
                benefits: {
                    2567: {
                        'ผลประโยชน์ 1': 8000
                    },
                    2568: {
                        'ผลประโยชน์ 1': 12000
                    }
                },
                impactRatios: {
                    attribution: 10,
                    deadweight: 15,
                    displacement: 8,
                    impactRatio: 0.67
                }
            }
        };

        function calculatePresentValue(value, year, baseYear, discountRate) {
            const t = year - baseYear;
            return value / Math.pow(1 + discountRate / 100, t);
        }

        function updateProjectInfo() {
            const project = projectData[currentProjectId];
            if (!project) return;

            document.getElementById('project-code').textContent = project.code;
            document.getElementById('project-name').textContent = project.name;
            document.getElementById('organization').textContent = project.organization;
            document.getElementById('manager').textContent = project.manager;
            document.getElementById('budget').textContent = project.budget.toLocaleString() + ' บาท';
            document.getElementById('evaluation-year').textContent = currentYear;
            document.getElementById('discount-rate').textContent = currentDiscountRate + '%';
        }

        function updateCostTable() {
            const project = projectData[currentProjectId];
            if (!project) return;

            const tbody = document.getElementById('cost-table-body');
            tbody.innerHTML = '';

            let yearTotals = {};
            let presentValueTotals = {};

            // Calculate costs for each year
            Object.keys(project.costs).forEach(year => {
                Object.keys(project.costs[year]).forEach(costItem => {
                    if (!yearTotals[year]) yearTotals[year] = 0;
                    yearTotals[year] += project.costs[year][costItem];
                });
            });

            // Add cost rows
            const costItems = [...new Set(Object.values(project.costs).flatMap(Object.keys))];
            costItems.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'cost-row';
                let rowHtml = `<td>${item}</td>`;

                for (let year = 2567; year <= 2572; year++) {
                    const value = project.costs[year] && project.costs[year][item] || 0;
                    rowHtml += `<td>${value.toLocaleString()}</td>`;
                }
                row.innerHTML = rowHtml;
                tbody.appendChild(row);
            });

            // Add total row
            const totalRow = document.createElement('tr');
            totalRow.className = 'total-row';
            let totalRowHtml = '<td>รวม (Cost)</td>';
            for (let year = 2567; year <= 2572; year++) {
                const total = yearTotals[year] || 0;
                totalRowHtml += `<td>${total.toLocaleString()}</td>`;
            }
            totalRow.innerHTML = totalRowHtml;
            tbody.appendChild(totalRow);

            // Add present value row
            const pvRow = document.createElement('tr');
            pvRow.className = 'total-row';
            let pvRowHtml = '<td>ต้นทุนปัจจุบันสุทธิ (Present Cost)</td>';
            let totalPresentCost = 0;

            for (let year = 2567; year <= 2572; year++) {
                const cost = yearTotals[year] || 0;
                const pv = calculatePresentValue(cost, year, 2567, currentDiscountRate);
                totalPresentCost += pv;
                pvRowHtml += `<td>${Math.round(pv).toLocaleString()}</td>`;
            }
            pvRow.innerHTML = pvRowHtml;
            tbody.appendChild(pvRow);

            // Update total present cost
            document.getElementById('total-present-cost').textContent = Math.round(totalPresentCost).toLocaleString();
        }

        function updateBenefitTable() {
            const project = projectData[currentProjectId];
            if (!project) return;

            const tbody = document.getElementById('benefit-table-body');
            tbody.innerHTML = '';

            let yearTotals = {};
            let totalPresentBenefit = 0;

            // Calculate benefits for each year
            Object.keys(project.benefits).forEach(year => {
                Object.keys(project.benefits[year]).forEach(benefitItem => {
                    if (!yearTotals[year]) yearTotals[year] = 0;
                    yearTotals[year] += project.benefits[year][benefitItem];
                });
            });

            // Add benefit rows
            const benefitItems = [...new Set(Object.values(project.benefits).flatMap(Object.keys))];
            benefitItems.forEach(item => {
                const row = document.createElement('tr');
                row.className = 'benefit-row';
                let rowHtml = `<td>${item}</td>`;

                for (let year = 2567; year <= 2572; year++) {
                    const value = project.benefits[year] && project.benefits[year][item] || 0;
                    rowHtml += `<td>${value.toLocaleString()}</td>`;
                }
                row.innerHTML = rowHtml;
                tbody.appendChild(row);
            });

            // Add total row
            const totalRow = document.createElement('tr');
            totalRow.className = 'total-row';
            let totalRowHtml = '<td>รวม (Benefit)</td>';
            for (let year = 2567; year <= 2572; year++) {
                const total = yearTotals[year] || 0;
                totalRowHtml += `<td>${total.toLocaleString()}</td>`;
            }
            totalRow.innerHTML = totalRowHtml;
            tbody.appendChild(totalRow);

            // Add present value row
            const pvRow = document.createElement('tr');
            pvRow.className = 'total-row';
            let pvRowHtml = '<td>ผลประโยชน์ปัจจุบันสุทธิ (Present Benefit)</td>';

            for (let year = 2567; year <= 2572; year++) {
                const benefit = yearTotals[year] || 0;
                const pv = calculatePresentValue(benefit, year, 2567, currentDiscountRate);
                totalPresentBenefit += pv;
                pvRowHtml += `<td>${Math.round(pv).toLocaleString()}</td>`;
            }
            pvRow.innerHTML = pvRowHtml;
            tbody.appendChild(pvRow);

            // Update total present benefit
            document.getElementById('total-present-benefit').textContent = Math.round(totalPresentBenefit).toLocaleString();
        }

        function updateImpactTables() {
            const project = projectData[currentProjectId];
            if (!project) return;

            const impactRatios = project.impactRatios;

            // Update Attribution table
            updateImpactTable('attribution-table-body', impactRatios.attribution, 'Attribution');

            // Update Deadweight table
            updateImpactTable('deadweight-table-body', impactRatios.deadweight, 'Deadweight');

            // Update Displacement table
            updateImpactTable('displacement-table-body', impactRatios.displacement, 'Displacement');

            // Calculate total base case impact
            const totalBenefit = getTotalPresentBenefit();
            const totalBaseCase = totalBenefit * (impactRatios.attribution + impactRatios.deadweight + impactRatios.displacement) / 100;
            document.getElementById('total-base-case').textContent = Math.round(totalBaseCase).toLocaleString();
        }

        function updateImpactTable(tableId, percentage, type) {
            const tbody = document.getElementById(tableId);
            tbody.innerHTML = '';

            const totalBenefit = getTotalPresentBenefit();
            const impactValue = totalBenefit * (percentage / 100);

            const row = document.createElement('tr');
            row.className = 'impact-row';

            let rowHtml = `<td>ผลประโยชน์ 1 (${type} ${percentage}%)</td>`;

            // Show impact in first two years only (following the Excel pattern)
            for (let year = 2567; year <= 2572; year++) {
                if (year <= 2568) {
                    const yearlyImpact = impactValue / 2; // Split between two years
                    rowHtml += `<td>${Math.round(yearlyImpact).toLocaleString()}</td>`;
                } else {
                    rowHtml += `<td>0</td>`;
                }
            }

            row.innerHTML = rowHtml;
            tbody.appendChild(row);
        }

        function getTotalPresentBenefit() {
            const totalBenefitElement = document.getElementById('total-present-benefit');
            return parseFloat(totalBenefitElement.textContent.replace(/,/g, '')) || 0;
        }

        function getTotalPresentCost() {
            const totalCostElement = document.getElementById('total-present-cost');
            return parseFloat(totalCostElement.textContent.replace(/,/g, '')) || 0;
        }

        function calculateSROI() {
            const totalBenefit = getTotalPresentBenefit();
            const totalCost = getTotalPresentCost();
            const baseCaseElement = document.getElementById('total-base-case');
            const totalBaseCase = parseFloat(baseCaseElement.textContent.replace(/,/g, '')) || 0;

            const netBenefit = totalBenefit - totalBaseCase;
            const npv = netBenefit - totalCost;
            const sroi = totalCost > 0 ? netBenefit / totalCost : 0;

            // Update display
            document.getElementById('npv').textContent = Math.round(npv).toLocaleString();
            document.getElementById('sroi-ratio').textContent = sroi.toFixed(4);

            // Update breakdown values
            document.getElementById('gross-benefit').textContent = Math.round(totalBenefit).toLocaleString() + ' บาท';
            document.getElementById('base-case-impact').textContent = Math.round(totalBaseCase).toLocaleString() + ' บาท';
            document.getElementById('net-social-benefit').textContent = Math.round(netBenefit).toLocaleString() + ' บาท';
            document.getElementById('total-cost').textContent = Math.round(totalCost).toLocaleString() + ' บาท';
        }

        function initializeCharts() {
            // Cost-Benefit Comparison Chart
            const ctx1 = document.getElementById('costBenefitChart').getContext('2d');
            new Chart(ctx1, {
                type: 'bar',
                data: {
                    labels: ['2567', '2568', '2569', '2570'],
                    datasets: [{
                        label: 'ต้นทุน',
                        data: [8731, 11213, 233465, 44735435],
                        backgroundColor: 'rgba(255, 99, 132, 0.8)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }, {
                        label: 'ผลประโยชน์',
                        data: [5500, 5500, 0, 0],
                        backgroundColor: 'rgba(75, 192, 192, 0.8)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Benefit Breakdown Chart
            const ctx2 = document.getElementById('benefitBreakdownChart').getContext('2d');
            new Chart(ctx2, {
                type: 'pie',
                data: {
                    labels: ['ผลประโยชน์สุทธิ', 'Attribution', 'Deadweight', 'Displacement'],
                    datasets: [{
                        data: [8276, 660, 660, 660],
                        backgroundColor: [
                            'rgba(102, 126, 234, 0.8)',
                            'rgba(255, 206, 86, 0.8)',
                            'rgba(255, 99, 132, 0.8)',
                            'rgba(54, 162, 235, 0.8)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            // Impact Distribution Chart
            const ctx3 = document.getElementById('impactDistributionChart').getContext('2d');
            new Chart(ctx3, {
                type: 'line',
                data: {
                    labels: ['2567', '2568', '2569', '2570', '2571', '2572'],
                    datasets: [{
                        label: 'ผลประโยชน์รวม',
                        data: [5500, 5500, 0, 0, 0, 0],
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        borderColor: 'rgba(75, 192, 192, 1)',
                        borderWidth: 2,
                        fill: true
                    }, {
                        label: 'ผลกระทบกรณีฐาน',
                        data: [1980, 1980, 0, 0, 0, 0],
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 2,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Sensitivity Analysis Chart
            const ctx4 = document.getElementById('sensitivityChart').getContext('2d');
            new Chart(ctx4, {
                type: 'line',
                data: {
                    labels: ['1%', '2%', '3%', '4%', '5%'],
                    datasets: [{
                        label: 'SROI',
                        data: [0.0002, 0.0002, 0.0002, 0.0002, 0.0002],
                        backgroundColor: 'rgba(102, 126, 234, 0.2)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 2,
                        fill: false
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(4);
                                }
                            }
                        }
                    }
                }
            });
        }

        function updateAnalysis() {
            // Get current values from controls
            currentProjectId = parseInt(document.getElementById('project-select').value);
            currentYear = document.getElementById('year-select').value;
            currentDiscountRate = parseFloat(document.getElementById('discount-input').value);

            // Update all sections
            updateProjectInfo();
            updateCostTable();
            updateBenefitTable();
            updateImpactTables();
            calculateSROI();

            // Reinitialize charts with new data
            Chart.helpers.each(Chart.instances, function(instance) {
                instance.destroy();
            });
            initializeCharts();
        }

        // Event listeners
        document.getElementById('project-select').addEventListener('change', updateAnalysis);
        document.getElementById('year-select').addEventListener('change', updateAnalysis);
        document.getElementById('discount-input').addEventListener('input', updateAnalysis);

        // Initialize the page
        document.addEventListener('DOMContentLoaded', function() {
            // Set current date
            document.getElementById('report-date').textContent = new Date().toLocaleDateString('th-TH');

            // Initialize with default values
            updateAnalysis();
            initializeCharts();
        });

        // Helper function to format currency
        function formatCurrency(value) {
            return new Intl.NumberFormat('th-TH', {
                style: 'currency',
                currency: 'THB',
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(value);
        }

        // Helper function to export data
        function exportToExcel() {
            // Create a simple CSV export
            const data = [];
            data.push(['SROI Analysis Report']);
            data.push(['Project Code', document.getElementById('project-code').textContent]);
            data.push(['Project Name', document.getElementById('project-name').textContent]);
            data.push(['Total Present Cost', document.getElementById('total-present-cost').textContent]);
            data.push(['Total Present Benefit', document.getElementById('total-present-benefit').textContent]);
            data.push(['NPV', document.getElementById('npv').textContent]);
            data.push(['SROI Ratio', document.getElementById('sroi-ratio').textContent]);

            const csvContent = data.map(row => row.join(',')).join('\n');
            const blob = new Blob([csvContent], {
                type: 'text/csv'
            });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'sroi_analysis_report.csv';
            a.click();
        }

        // Print functionality
        function printReport() {
            window.print();
        }

        // Add export and print buttons functionality
        document.addEventListener('DOMContentLoaded', function() {
            // Add export button
            const exportBtn = document.createElement('button');
            exportBtn.className = 'btn';
            exportBtn.textContent = 'ส่งออก Excel';
            exportBtn.onclick = exportToExcel;
            exportBtn.style.marginRight = '10px';

            // Add print button
            const printBtn = document.createElement('button');
            printBtn.className = 'btn';
            printBtn.textContent = 'พิมพ์รายงาน';
            printBtn.onclick = printReport;

            // Add buttons to controls section
            const controlsSection = document.querySelector('.controls');
            const buttonContainer = document.createElement('div');
            buttonContainer.style.marginTop = '20px';
            buttonContainer.appendChild(exportBtn);
            buttonContainer.appendChild(printBtn);
            controlsSection.appendChild(buttonContainer);
        });

        // Responsive table handling
        function makeTablesResponsive() {
            const tables = document.querySelectorAll('.data-table');
            tables.forEach(table => {
                if (window.innerWidth < 768) {
                    table.style.fontSize = '0.8em';
                } else {
                    table.style.fontSize = '1em';
                }
            });
        }

        window.addEventListener('resize', makeTablesResponsive);

        // Animation for metric cards
        function animateMetricCards() {
            const cards = document.querySelectorAll('.metric-card');
            cards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';

                setTimeout(() => {
                    card.style.transition = 'all 0.6s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        }

        // Initialize animations
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(animateMetricCards, 500);
        });

        // Validation for inputs
        function validateInputs() {
            const discountInput = document.getElementById('discount-input');
            const value = parseFloat(discountInput.value);

            if (value < 0 || value > 20) {
                alert('อัตราคิดลดต้องอยู่ระหว่าง 0-20%');
                discountInput.value = currentDiscountRate;
                return false;
            }
            return true;
        }

        document.getElementById('discount-input').addEventListener('blur', validateInputs);

        // Tooltip functionality for complex terms
        function addTooltips() {
            const tooltipElements = [{
                    element: 'Attribution',
                    text: 'ส่วนของผลลัพธ์ที่เกิดจากปัจจัยอื่นๆ นอกจากโครงการ'
                },
                {
                    element: 'Deadweight',
                    text: 'ส่วนของผลลัพธ์ที่จะเกิดขึ้นอยู่แล้วแม้ไม่มีโครงการ'
                },
                {
                    element: 'Displacement',
                    text: 'ผลลัพธ์ที่ถูกแทนที่หรือย้ายจากที่อื่น'
                },
                {
                    element: 'NPV',
                    text: 'มูลค่าปัจจุบันสุทธิ (Net Present Value)'
                },
                {
                    element: 'SROI',
                    text: 'ผลตอบแทนทางสังคมจากการลงทุน (Social Return on Investment)'
                }
            ];

            tooltipElements.forEach(({
                element,
                text
            }) => {
                const elements = document.querySelectorAll(`[title*="${element}"], td:contains("${element}"), th:contains("${element}")`);
                elements.forEach(el => {
                    el.setAttribute('title', text);
                    el.style.cursor = 'help';
                });
            });
        }

        document.addEventListener('DOMContentLoaded', addTooltips);
    </script>
</body>

</html>