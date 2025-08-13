<!-- PVF Table Section - แทนที่ส่วน "ตั้งค่าการคำนวณ" -->
<?php
// ดึงข้อมูลปีจากฐานข้อมูล สำหรับ PVF Table
$pvf_years_query = "SELECT year_id, year_be, year_ad, year_display, year_description, sort_order 
                    FROM years 
                    WHERE is_active = 1 
                    ORDER BY sort_order ASC";
$pvf_years_result = mysqli_query($conn, $pvf_years_query);

$pvf_years_data = [];
if ($pvf_years_result) {
    while ($row = mysqli_fetch_assoc($pvf_years_result)) {
        $pvf_years_data[] = $row;
    }
}

// Use only actual years from database, no extra 25xx years

// ดึงค่า discount_rate จากฐานข้อมูล present_value_factors
$saved_discount_rate = 3.0; // ค่าเริ่มต้น
$discount_query = "SELECT discount_rate FROM present_value_factors WHERE pvf_name = 'current' AND is_active = 1 LIMIT 1";
$discount_result = mysqli_query($conn, $discount_query);
if ($discount_result && mysqli_num_rows($discount_result) > 0) {
    $row = mysqli_fetch_assoc($discount_result);
    $saved_discount_rate = floatval($row['discount_rate']);
}
?>

<div class="settings-section">
    <!-- PVF Table ภายใน settings section -->
    <div class="pvf-table-container" style="margin-top: 20px;">
        <h3 style="color: #495057; margin-bottom: 15px; font-size: 1.1rem;">📊 ตาราง Present Value Factor</h3>
        <table id="pvfTable" class="pvf-table">
            <thead>
                <tr>
                    <th rowspan="2">ปี พ.ศ.</th>
                    <th class="pvf-highlight-header">กำหนดค่า<br>อัตราคิดลด<br><?php echo $saved_discount_rate; ?>%</th>
                    <?php for ($i = 1; $i < count($pvf_years_data); $i++): ?>
                        <th></th>
                    <?php endfor; ?>
                </tr>
                <tr>
                    <?php foreach ($pvf_years_data as $year): ?>
                        <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td class="pvf-year-cell">t</td>
                    <?php for ($t = 0; $t < count($pvf_years_data); $t++): ?>
                        <td class="pvf-time-cell"><?php echo $t; ?></td>
                    <?php endfor; ?>
                </tr>
                <tr>
                    <td class="pvf-year-cell">Present Value Factor</td>
                    <?php for ($t = 0; $t < count($pvf_years_data); $t++): ?>
                        <td class="pvf-cell" id="pvf<?php echo $t; ?>"><?php echo number_format(1 / pow(1 + ($saved_discount_rate/100), $t), 2); ?></td>
                    <?php endfor; ?>
                </tr>
            </tbody>
        </table>
    </div>

</div>

<?php if ($selected_project): ?>

    <?php
    // ดึงข้อมูลต้นทุนและผลประโยชน์
    $project_costs = getProjectCosts($conn, $selected_project_id);
    $benefit_data = getProjectBenefits($conn, $selected_project_id);
    $project_benefits = $benefit_data['benefits'];
    $benefit_notes_by_year = $benefit_data['benefit_notes_by_year'];
    $base_case_factors = $benefit_data['base_case_factors'];

    // ดึงข้อมูลปีสำหรับส่วนอื่นๆ
    $years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6";
    $years_result = mysqli_query($conn, $years_query);
    $available_years = [];
    while ($year_row = mysqli_fetch_assoc($years_result)) {
        $available_years[] = $year_row;
    }
    ?>


    <!-- Cost Section -->
    <div class="section">
        <h2 class="section-title">💰 ต้นทุนโครงการ (Cost)</h2>
        
        <?php
        // ดึงข้อมูลต้นทุนจาก project_costs table
        $cost_query = "SELECT id, cost_name, yearly_amounts 
                       FROM project_costs 
                       WHERE project_id = ? 
                       ORDER BY id ASC";
        $cost_stmt = mysqli_prepare($conn, $cost_query);
        mysqli_stmt_bind_param($cost_stmt, "i", $selected_project_id);
        mysqli_stmt_execute($cost_stmt);
        $cost_result = mysqli_stmt_get_result($cost_stmt);
        
        $project_costs_data = [];
        $total_costs_by_year = [];
        
        if ($cost_result) {
            while ($row = mysqli_fetch_assoc($cost_result)) {
                $yearly_amounts = json_decode($row['yearly_amounts'], true) ?: [];
                $project_costs_data[] = [
                    'id' => $row['id'],
                    'name' => $row['cost_name'],
                    'amounts' => $yearly_amounts
                ];
                
                // คำนวณรวมต้นทุนแต่ละปี
                foreach ($yearly_amounts as $year => $amount) {
                    if (!isset($total_costs_by_year[$year])) {
                        $total_costs_by_year[$year] = 0;
                    }
                    $total_costs_by_year[$year] += floatval($amount);
                }
            }
        }
        mysqli_stmt_close($cost_stmt);
        ?>
        
        <?php if (!empty($project_costs_data)): ?>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>รายการต้นทุน</th>
                        <?php foreach ($available_years as $year): ?>
                            <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($project_costs_data as $cost): ?>
                        <tr class="cost-row">
                            <td><?php echo htmlspecialchars($cost['name']); ?></td>
                            <?php foreach ($available_years as $year): ?>
                                <td>
                                    <?php
                                    $amount = isset($cost['amounts'][$year['year_be']]) ? floatval($cost['amounts'][$year['year_be']]) : 0;
                                    echo $amount > 0 ? number_format($amount, 0) . ' บาท' : '-';
                                    ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                    
                    <!-- แถวรวมต้นทุน -->
                    <tr class="total-row" style="background-color: #f8f9fa; font-weight: bold;">
                        <td>รวมต้นทุนทั้งหมด</td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $total_amount = isset($total_costs_by_year[$year['year_be']]) ? $total_costs_by_year[$year['year_be']] : 0;
                                echo $total_amount > 0 ? number_format($total_amount, 0) . ' บาท' : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- แถวต้นทุนปัจจุบันสุทธิ (Present Value) -->
                    <tr class="present-value-row" style="background-color: #e3f2fd; font-weight: bold;">
                        <td>ต้นทุนปัจจุบันสุทธิ (Present Cost)</td>
                        <?php 
                        $total_present_cost = 0;
                        foreach ($available_years as $year_index => $year): 
                            $total_amount = isset($total_costs_by_year[$year['year_be']]) ? $total_costs_by_year[$year['year_be']] : 0;
                            // ใช้อัตราคิดลดจากฐานข้อมูล (ค่า $saved_discount_rate จาก input-section.php)
                            $present_value = $total_amount / pow(1 + ($saved_discount_rate/100), $year_index);
                            $total_present_cost += $present_value;
                        ?>
                            <td id="present-cost-<?php echo $year_index; ?>">
                                <?php echo $present_value > 0 ? number_format($present_value, 0) . ' บาท' : '-'; ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                    
                    <!-- แถวต้นทุนรวมปัจจุบัน (Total Present Cost) -->
                    <tr class="total-present-cost-row" style="background-color: #fff3cd; font-weight: bold; border-top: 3px solid #ffc107;">
                        <td>ต้นทุนรวมปัจจุบัน (Total Present Cost)</td>
                        <td id="total-present-cost-summary">
                            <?php echo number_format($total_present_cost, 0) . ' บาท'; ?>
                        </td>
                        <?php 
                        // แสดงเครื่องหมาย "-" ในคอลัมน์ปีอื่นๆ
                        for ($i = 1; $i < count($available_years); $i++): ?>
                            <td>-</td>
                        <?php endfor; ?>
                    </tr>
                </tbody>
            </table>
            
            <div class="metric-cards" style="margin-top: 20px;">
                <div class="metric-card">
                    <div class="metric-value" id="total-present-cost">
                        <?php echo number_format($total_present_cost, 0); ?> บาท
                    </div>
                    <div class="metric-label">ต้นทุนปัจจุบันสุทธิรวม</div>
                </div>
                <div class="metric-card">
                    <div class="metric-value">
                        <?php echo number_format(array_sum($total_costs_by_year), 0); ?> บาท
                    </div>
                    <div class="metric-label">ต้นทุนรวมทั้งหมด</div>
                </div>
            </div>
            
        <?php else: ?>
            <div style="text-align: center; padding: 40px; color: #666;">
                <i style="font-size: 3em; margin-bottom: 15px;">💰</i>
                <h4>ไม่พบข้อมูลต้นทุนโครงการ</h4>
                <p>กรุณาเพิ่มข้อมูลต้นทุนในระบบก่อน</p>
                <a href="../impact_pathway/cost.php?project_id=<?php echo $selected_project_id; ?>" class="btn" style="margin-top: 15px;">เพิ่มข้อมูลต้นทุน</a>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // เก็บข้อมูลต้นทุนสำหรับการคำนวณ Present Value
        const costsByYear = <?php echo json_encode($total_costs_by_year); ?>;
        const availableYears = <?php echo json_encode(array_column($available_years, 'year_be')); ?>;
        
        // ฟังก์ชันอัปเดต Present Cost เมื่ออัตราคิดลดเปลี่ยน
        function updatePresentCosts(discountRate) {
            let totalPresentCost = 0;
            
            availableYears.forEach((year, index) => {
                const costAmount = costsByYear[year] || 0;
                
                // ดึงค่า PVF จากตาราง PVF แทนการคำนวณใหม่
                const pvfCell = document.getElementById(`pvf${index}`);
                let pvf = 1 / Math.pow(1 + (discountRate / 100), index); // fallback
                
                if (pvfCell) {
                    pvf = parseFloat(pvfCell.textContent) || pvf;
                }
                
                const presentValue = costAmount * pvf;
                
                const cell = document.getElementById(`present-cost-${index}`);
                if (cell && costAmount > 0) {
                    cell.textContent = presentValue.toLocaleString('th-TH', {minimumFractionDigits: 0}) + ' บาท';
                }
                
                totalPresentCost += presentValue;
            });
            
            // อัปเดตยอดรวมในส่วน metric card
            const totalCell = document.getElementById('total-present-cost');
            if (totalCell) {
                totalCell.textContent = totalPresentCost.toLocaleString('th-TH', {minimumFractionDigits: 0}) + ' บาท';
            }
            
            // อัปเดตยอดรวมในแถว Total Present Cost
            const totalSummaryCell = document.getElementById('total-present-cost-summary');
            if (totalSummaryCell) {
                totalSummaryCell.textContent = totalPresentCost.toLocaleString('th-TH', {minimumFractionDigits: 0}) + ' บาท';
            }
        }
        
        // เชื่อมต่อกับฟังก์ชัน updateDiscountRate ที่มีอยู่แล้ว
        if (typeof window.originalUpdateDiscountRate === 'undefined') {
            window.originalUpdateDiscountRate = window.updateDiscountRate;
            window.updateDiscountRate = function(value) {
                window.originalUpdateDiscountRate(value);
                updatePresentCosts(parseFloat(value));
            };
        }
        
        // อัปเดตค่า discount rate เริ่มต้นจาก PHP
        if (typeof window.currentDiscountRate !== 'undefined') {
            window.currentDiscountRate = <?php echo $saved_discount_rate; ?>;
        }
    </script>
    
    <script>
        // ตั้งค่า discount rate จากฐานข้อมูลเมื่อโหลดหน้า
        document.addEventListener('DOMContentLoaded', function() {
            const savedDiscountRate = <?php echo $saved_discount_rate; ?>;
            
            // อัปเดตค่าใน discount rate slider/input ถ้ามี
            const discountRateInput = document.getElementById('discountRate');
            const discountRateSlider = document.getElementById('discountRateSlider');
            const discountRateValue = document.getElementById('discountRateValue');
            
            if (discountRateInput) {
                discountRateInput.value = savedDiscountRate;
            }
            if (discountRateSlider) {
                discountRateSlider.value = savedDiscountRate;
            }
            if (discountRateValue) {
                discountRateValue.textContent = savedDiscountRate.toFixed(1) + '%';
            }
            
            // อัปเดต currentDiscountRate
            if (typeof window.currentDiscountRate !== 'undefined') {
                window.currentDiscountRate = savedDiscountRate;
            }
            
            // อัปเดต PVF Table header ให้ใช้ค่าจากฐานข้อมูล (delay เล็กน้อยเพื่อให้ DOM โหลดเสร็จ)
            setTimeout(() => {
                const pvfHeaderCell = document.querySelector('.pvf-highlight-header');
                if (pvfHeaderCell) {
                    pvfHeaderCell.innerHTML = `กำหนดค่า<br>อัตราคิดลด<br>${savedDiscountRate.toFixed(1)}%`;
                }
                
                // อัปเดต Present Cost ใหม่ด้วยค่าจากฐานข้อมูล
                if (typeof updatePresentCosts === 'function') {
                    updatePresentCosts(savedDiscountRate);
                }
            }, 100);
        });
    </script>
<?php endif; ?>