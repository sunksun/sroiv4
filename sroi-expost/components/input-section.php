<?php if ($selected_project): ?>
<div class="settings-section">
    <div class="settings-header">
        <h2>⚙️ ตั้งค่าการคำนวณ</h2>
        <div class="discount-rate">
            อัตราคิดลด: <span id="discountRateValue">3.0%</span>
        </div>
    </div>
    
    <div class="control-group">
        <label for="discountRate">อัตราคิดลด (Discount Rate):</label>
        <input type="range" id="discountRate" min="0" max="10" step="0.1" value="3" 
               oninput="updateDiscountRate(this.value)">
        <span id="discountRateInput">3.0%</span>
    </div>
    
    <div class="control-group">
        <label for="analysisPeriod">ระยะเวลาการวิเคราะห์ (ปี):</label>
        <select id="analysisPeriod" onchange="updateAnalysis()">
            <option value="3">3 ปี</option>
            <option value="5" selected>5 ปี</option>
            <option value="10">10 ปี</option>
        </select>
    </div>
</div>

<?php
// ดึงข้อมูลต้นทุนและผลประโยชน์
$project_costs = getProjectCosts($conn, $selected_project_id);
$benefit_data = getProjectBenefits($conn, $selected_project_id);
$project_benefits = $benefit_data['benefits'];
$benefit_notes_by_year = $benefit_data['benefit_notes_by_year'];

// ดึงข้อมูลปีจากฐานข้อมูล
$years_query = "SELECT year_be, year_display FROM years WHERE is_active = 1 ORDER BY sort_order ASC LIMIT 6";
$years_result = mysqli_query($conn, $years_query);
$available_years = [];
while ($year_row = mysqli_fetch_assoc($years_result)) {
    $available_years[] = $year_row;
}
?>

<div class="section">
    <h2 class="section-title">💰 ข้อมูลต้นทุนและผลประโยชน์</h2>
    
    <div class="year-header">
        <div>รายการ</div>
        <?php foreach ($available_years as $year): ?>
            <div><?php echo htmlspecialchars($year['year_display']); ?></div>
        <?php endforeach; ?>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th>ประเภท</th>
                <th>รายการ</th>
                <?php foreach ($available_years as $year): ?>
                    <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <!-- ส่วนต้นทุน -->
            <?php if (!empty($project_costs)): ?>
                <?php foreach ($project_costs as $index => $cost): ?>
                    <tr class="cost-row">
                        <td><?php echo $index == 0 ? 'ต้นทุน' : ''; ?></td>
                        <td><?php echo htmlspecialchars($cost['name']); ?></td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php 
                                $amount = isset($cost['amounts'][$year['year_be']]) ? $cost['amounts'][$year['year_be']] : 0;
                                echo $amount > 0 ? formatCurrency($amount) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
            
            <!-- ส่วนผลประโยชน์ -->
            <?php if (!empty($project_benefits)): ?>
                <?php foreach ($project_benefits as $index => $benefit): ?>
                    <tr class="benefit-row">
                        <td><?php echo $index == 0 ? 'ผลประโยชน์' : ''; ?></td>
                        <td>
                            <?php echo htmlspecialchars($benefit['detail']); ?>
                            <?php if ($benefit['beneficiary']): ?>
                                <br><small>ผู้รับ: <?php echo htmlspecialchars($benefit['beneficiary']); ?></small>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php 
                                $benefit_number = $index + 1;
                                $amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']]) 
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                echo $amount > 0 ? formatCurrency($amount) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Cost Section -->
<div class="section">
    <h2 class="section-title">💰 ต้นทุนโครงการ (Cost)</h2>
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
            <?php foreach ($project_costs as $cost): ?>
                <tr class="cost-row">
                    <td><?php echo htmlspecialchars($cost['name']); ?></td>
                    <?php foreach ($available_years as $year): ?>
                        <td>
                            <?php 
                            $amount = isset($cost['amounts'][$year['year_be']]) ? $cost['amounts'][$year['year_be']] : 0;
                            echo $amount > 0 ? formatCurrency($amount, 0) : '-';
                            ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!empty($project_costs)): ?>
            <tr class="total-row">
                <td>รวม (Cost)</td>
                <?php 
                $costs_by_year = [];
                foreach ($project_costs as $cost) {
                    foreach ($available_years as $year) {
                        $amount = isset($cost['amounts'][$year['year_be']]) ? $cost['amounts'][$year['year_be']] : 0;
                        if (!isset($costs_by_year[$year['year_be']])) {
                            $costs_by_year[$year['year_be']] = 0;
                        }
                        $costs_by_year[$year['year_be']] += $amount;
                    }
                }
                foreach ($available_years as $year): ?>
                    <td><?php echo formatCurrency($costs_by_year[$year['year_be']] ?? 0, 0); ?></td>
                <?php endforeach; ?>
            </tr>
            <tr class="total-row">
                <td>ต้นทุนปัจจุบันสุทธิ (Present Cost)</td>
                <?php 
                $present_costs_by_year = [];
                foreach ($project_costs as $cost) {
                    foreach ($available_years as $year_index => $year) {
                        $amount = isset($cost['amounts'][$year['year_be']]) ? $cost['amounts'][$year['year_be']] : 0;
                        $present_value = $amount / pow(1 + $default_settings['discount_rate'], $year_index);
                        if (!isset($present_costs_by_year[$year['year_be']])) {
                            $present_costs_by_year[$year['year_be']] = 0;
                        }
                        $present_costs_by_year[$year['year_be']] += $present_value;
                    }
                }
                foreach ($available_years as $year): ?>
                    <td><?php echo formatCurrency($present_costs_by_year[$year['year_be']] ?? 0, 0); ?></td>
                <?php endforeach; ?>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
    
    <?php if (!empty($project_costs)): ?>
    <div class="metric-cards">
        <div class="metric-card">
            <div class="metric-value"><?php echo formatCurrency(array_sum($present_costs_by_year), 0); ?></div>
            <div class="metric-label">ต้นทุนรวมปัจจุบัน (บาท)</div>
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
<?php endif; ?>