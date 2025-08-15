<?php if ($selected_project && (!empty($project_costs) || !empty($project_benefits))): ?>

    <?php
    // คำนวณข้อมูลสรุป
    $total_costs = 0;
    $total_benefits = 0;
    $costs_by_year = [];
    $benefits_by_year = [];
    $present_costs_by_year = [];
    $present_benefits_by_year = [];

    // คำนวณต้นทุนรวมและ Present Value
    foreach ($project_costs as $cost) {
        foreach ($available_years as $year_index => $year) {
            $amount = isset($cost['amounts'][$year['year_be']]) ? $cost['amounts'][$year['year_be']] : 0;
            $present_value = $amount / pow(1 + $default_settings['discount_rate'], $year_index);

            $total_costs += $amount;
            if (!isset($costs_by_year[$year['year_be']])) {
                $costs_by_year[$year['year_be']] = 0;
                $present_costs_by_year[$year['year_be']] = 0;
            }
            $costs_by_year[$year['year_be']] += $amount;
            $present_costs_by_year[$year['year_be']] += $present_value;
        }
    }

    // คำนวณผลประโยชน์รวมและ Present Value
    foreach ($project_benefits as $index => $benefit) {
        $benefit_number = $index + 1;
        foreach ($available_years as $year_index => $year) {
            $amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
            // ใช้ discount rate จากฐานข้อมูลเหมือนกับ Present Cost
            $present_value = $amount / pow(1 + ($default_settings['discount_rate']), $year_index);

            $total_benefits += $amount;
            if (!isset($benefits_by_year[$year['year_be']])) {
                $benefits_by_year[$year['year_be']] = 0;
                $present_benefits_by_year[$year['year_be']] = 0;
            }
            $benefits_by_year[$year['year_be']] += $amount;
            $present_benefits_by_year[$year['year_be']] += $present_value;
        }
    }

    $total_present_costs = array_sum($present_costs_by_year);
    $total_present_benefits = array_sum($present_benefits_by_year);
    $sroi_ratio = calculateSROIRatio($total_present_benefits, $total_present_costs);
    $npv = $total_present_benefits - $total_present_costs;
    $sensitivity = calculateSensitivityAnalysis($sroi_ratio, $default_settings['sensitivity_range']);

    // คำนวณ Base Case Impact จากข้อมูลจริงในฐานข้อมูล
    $base_case_impact = 0;
    foreach ($project_benefits as $index => $benefit) {
        $benefit_number = $index + 1;
        foreach ($available_years as $year_index => $year) {
            $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
            
            // ดึงค่า base case factors จากฐานข้อมูล
            $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
            $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
            $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;
            
            // คำนวณ present value ของ base case impact
            $attribution = $benefit_amount * ($attribution_rate / 100);
            $deadweight = $benefit_amount * ($deadweight_rate / 100);
            $displacement = $benefit_amount * ($displacement_rate / 100);
            
            $impact_amount = $attribution + $deadweight + $displacement;
            $present_impact = $impact_amount / pow(1 + ($default_settings['discount_rate']), $year_index);
            
            $base_case_impact += $present_impact;
        }
    }
    
    $net_social_benefit = $total_present_benefits - $base_case_impact;
    ?>



    <!-- Benefit Section -->
    <div class="section">
        <h2 class="section-title">🎁 ผลประโยชน์ของโครงการ (Benefit)</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>รายการผลประโยชน์</th>
                    <?php foreach ($available_years as $year): ?>
                        <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($project_benefits as $index => $benefit): ?>
                    <?php $benefit_number = $index + 1; ?>
                    <tr class="benefit-row">
                        <td>
                            <?php echo htmlspecialchars($benefit['detail']); ?>
                            <?php if ($benefit['beneficiary']): ?>
                                <br><small>ผู้รับ: <?php echo htmlspecialchars($benefit['beneficiary']); ?></small>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                echo $amount > 0 ? formatNumber($amount, 0) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>รวม (Benefit)</td>
                    <?php foreach ($available_years as $year): ?>
                        <td><?php echo formatNumber($benefits_by_year[$year['year_be']] ?? 0, 0); ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr class="total-row">
                    <td>ผลประโยชน์ปัจจุบันสุทธิ (Present Benefit)</td>
                    <?php foreach ($available_years as $year): ?>
                        <td><?php echo formatNumber($present_benefits_by_year[$year['year_be']] ?? 0, 0); ?></td>
                    <?php endforeach; ?>
                </tr>
                
                <!-- แถวรวมผลประโยชน์ปัจจุบันสุทธิ (Total Present Benefit) -->
                <tr class="total-present-benefit-row" style="background-color: #e8f5e8; font-weight: bold; border-top: 3px solid #28a745;">
                    <td>รวมผลประโยชน์ปัจจุบันสุทธิ (Total Present Benefit)</td>
                    <td id="total-present-benefit-summary">
                        <?php echo formatNumber($total_present_benefits, 0); ?>
                    </td>
                    <?php 
                    // แสดงเครื่องหมาย "-" ในคอลัมน์ปีอื่นๆ
                    for ($i = 1; $i < count($available_years); $i++): ?>
                        <td>-</td>
                    <?php endfor; ?>
                </tr>
            </tbody>
        </table>
        <div class="metric-cards">
            <div class="metric-card">
                <div class="metric-value"><?php echo formatNumber($total_present_benefits, 0); ?></div>
                <div class="metric-label">รวมผลประโยชน์ปัจจุบันสุทธิ (บาท)</div>
            </div>
        </div>
    </div>

    <!-- Base Case Impact Section -->
    <div class="section">
        <h2 class="section-title">⚖️ ผลกระทบกรณีฐาน (Base Case Impact)</h2>

        <h3 style="color: #667eea; margin-bottom: 15px;">ผลจากปัจจัยอื่นๆ (Attribution)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <?php foreach ($available_years as $year): ?>
                        <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($project_benefits as $index => $benefit): ?>
                    <?php $benefit_number = $index + 1; ?>
                    <tr class="impact-row">
                        <td><?php echo htmlspecialchars($benefit['detail']); ?> 
                            <?php 
                            // แสดงค่า attribution เฉลี่ยจากฐานข้อมูล
                            $attribution_avg = 0;
                            $count = 0;
                            if (isset($base_case_factors[$benefit_number])) {
                                foreach ($base_case_factors[$benefit_number] as $year_data) {
                                    $attribution_avg += $year_data['attribution'];
                                    $count++;
                                }
                                $attribution_avg = $count > 0 ? $attribution_avg / $count : 0;
                            }
                            echo "(Attribution " . number_format($attribution_avg, 1) . "%)";
                            ?>
                        </td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                $attribution_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                    ? $base_case_factors[$benefit_number][$year['year_be']]['attribution'] : 0;
                                $attribution = $benefit_amount * ($attribution_rate / 100); // ใช้ค่าจากฐานข้อมูล
                                echo $attribution > 0 ? formatNumber($attribution, 0) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ส่วนเกิน (Deadweight)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <?php foreach ($available_years as $year): ?>
                        <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($project_benefits as $index => $benefit): ?>
                    <?php $benefit_number = $index + 1; ?>
                    <tr class="impact-row">
                        <td><?php echo htmlspecialchars($benefit['detail']); ?> 
                            <?php 
                            // แสดงค่า deadweight เฉลี่ยจากฐานข้อมูล
                            $deadweight_avg = 0;
                            $count = 0;
                            if (isset($base_case_factors[$benefit_number])) {
                                foreach ($base_case_factors[$benefit_number] as $year_data) {
                                    $deadweight_avg += $year_data['deadweight'];
                                    $count++;
                                }
                                $deadweight_avg = $count > 0 ? $deadweight_avg / $count : 0;
                            }
                            echo "(Deadweight " . number_format($deadweight_avg, 1) . "%)";
                            ?>
                        </td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                $deadweight_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                    ? $base_case_factors[$benefit_number][$year['year_be']]['deadweight'] : 0;
                                $deadweight = $benefit_amount * ($deadweight_rate / 100); // ใช้ค่าจากฐานข้อมูล
                                echo $deadweight > 0 ? formatNumber($deadweight, 0) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <h3 style="color: #667eea; margin-bottom: 15px; margin-top: 20px;">ผลลัพธ์ทดแทน (Displacement)</h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <?php foreach ($available_years as $year): ?>
                        <th><?php echo htmlspecialchars($year['year_display']); ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($project_benefits as $index => $benefit): ?>
                    <?php $benefit_number = $index + 1; ?>
                    <tr class="impact-row">
                        <td><?php echo htmlspecialchars($benefit['detail']); ?> 
                            <?php 
                            // แสดงค่า displacement เฉลี่ยจากฐานข้อมูล
                            $displacement_avg = 0;
                            $count = 0;
                            if (isset($base_case_factors[$benefit_number])) {
                                foreach ($base_case_factors[$benefit_number] as $year_data) {
                                    $displacement_avg += $year_data['displacement'];
                                    $count++;
                                }
                                $displacement_avg = $count > 0 ? $displacement_avg / $count : 0;
                            }
                            echo "(Displacement " . number_format($displacement_avg, 1) . "%)";
                            ?>
                        </td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                $displacement_rate = isset($base_case_factors[$benefit_number]) && isset($base_case_factors[$benefit_number][$year['year_be']])
                                    ? $base_case_factors[$benefit_number][$year['year_be']]['displacement'] : 0;
                                $displacement = $benefit_amount * ($displacement_rate / 100); // ใช้ค่าจากฐานข้อมูล
                                echo $displacement > 0 ? formatNumber($displacement, 0) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="metric-cards">
            <div class="metric-card">
                <div class="metric-value"><?php echo formatNumber($base_case_impact, 0); ?></div>
                <div class="metric-label">ผลกระทบกรณีฐานรวมปัจจุบัน (บาท)</div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="section">
        <h2 class="section-title">📊 ผลการวิเคราะห์ SROI</h2>

        <h3 style="color: #667eea; margin-bottom: 15px;">ข้อมูลการประเมินโครงการ ปี พ.ศ. <?php echo (date('Y') + 543); ?></h3>
        <table class="data-table">
            <thead>
                <tr>
                    <th>รายการ</th>
                    <th>มูลค่า</th>
                    <th>หน่วย</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>โครงการนี้เริ่มดำเนินกิจกรรมปีแรก ณ ปี พ.ศ.</td>
                    <td class="number"><?php echo (date('Y') + 543); ?></td>
                    <td class="unit">-</td>
                </tr>
                <tr>
                    <td>ใช้งบประมาณทั้งหมด (Cost)</td>
                    <td class="number"><?php echo formatNumber($total_costs, 2); ?></td>
                    <td class="unit">บาท</td>
                </tr>
                <tr>
                    <td>มูลค่าปัจจุบันของต้นทุนรวม (Total Present Cost)</td>
                    <td class="number"><?php echo formatNumber($total_present_costs, 2); ?></td>
                    <td class="unit">บาท</td>
                </tr>
                <tr>
                    <td>มูลค่าปัจจุบันของส่วนมาตรฐานทั่วไปของโครงการและก่อนทั้งลบมูลค่าการณ์ฐาน (Total Present Benefit)</td>
                    <td class="number"><?php echo formatNumber($total_present_benefits, 2); ?></td>
                    <td class="unit">บาท</td>
                </tr>
                <tr>
                    <td>มูลค่าปัจจุบันของผลกระทบกรณ์ฐาน (Total Present Base Case Impact)</td>
                    <td class="number"><?php echo formatNumber($base_case_impact, 2); ?></td>
                    <td class="unit">บาท</td>
                </tr>
                <tr>
                    <td>มูลค่าผลประโยชน์ปัจจุบันสุทธิที่เกิดขึ้นแก่สังคมจากเงินลงทุนของโครงการอนุทกลบต้นทุน (Net Present Social Benefit)</td>
                    <td class="number <?php echo $net_social_benefit >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatNumber($net_social_benefit, 2); ?></td>
                    <td class="unit">บาท</td>
                </tr>
                <tr>
                    <td>มูลค่าผลประโยชน์ปัจจุบันสุทธิของโครงการ (Net Present Value หรือ NPV)</td>
                    <td class="number <?php echo $npv >= 0 ? 'positive' : 'negative'; ?>"><?php echo formatNumber($npv, 2); ?></td>
                    <td class="unit">บาท</td>
                </tr>
                <tr>
                    <td>ผลตอบแทนทางสังคมจากการลงทุน (Social Return of Investment หรือ SROI)</td>
                    <td class="number"><?php echo formatNumber($sroi_ratio, 2); ?></td>
                    <td class="unit">เท่า</td>
                </tr>
                <tr>
                    <td>อัตราผลตอบแทนภายใน (Internal Rate of Return หรือ IRR)</td>
                    <td class="number positive">N/A</td>
                    <td class="unit">%</td>
                </tr>
                <tr>
                    <td>โครงการนี้คำนวณมูลค่าผลประโยชน์ปัจจุบันสุทธิ (NPV) โดยใช้อัตราคิดลดร้อยละ</td>
                    <td class="number"><?php echo formatNumber($default_settings['discount_rate'] * 100, 2); ?></td>
                    <td class="unit">%</td>
                </tr>
                <tr>
                    <td>โดยปรับปรุงค่า ณ ปี ฐาน พ.ศ.</td>
                    <td class="number"><?php echo (date('Y') + 543); ?></td>
                    <td class="unit">-</td>
                </tr>
            </tbody>
        </table>

        <div class="formula-box" style="margin-top: 20px;">
            <h3>สูตรการคำนวณ SROI</h3>
            <div class="formula">
                SROI = (ผลประโยชน์ปัจจุบันสุทธิ - ผลกระทบกรณีฐาน) ÷ ต้นทุนปัจจุบันสุทธิ
            </div>
            <div class="formula" style="margin-top: 10px;">
                SROI = (<?php echo formatNumber($total_present_benefits, 0); ?> - <?php echo formatNumber($base_case_impact, 0); ?>) ÷ <?php echo formatNumber($total_present_costs, 0); ?> = <?php echo formatNumber($sroi_ratio, 4); ?> เท่า
            </div>
        </div>
    </div>

    <!-- Charts Section -->
    <div class="chart-container">
        <h2 class="section-title">📈 กราฟแสดงผลการวิเคราะห์</h2>
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
        <h2 class="section-title">🎯 การวิเคราะห์ความไว (Sensitivity Analysis)</h2>
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
                            <td><?php echo formatNumber($sensitivity['best_case'], 4); ?></td>
                            <td><?php echo formatNumber($npv * 1.2, 0); ?></td>
                        </tr>
                        <tr class="highlight-positive">
                            <td>ปัจจุบัน</td>
                            <td><?php echo ($default_settings['discount_rate'] * 100); ?>%</td>
                            <td><?php echo formatNumber($sroi_ratio, 4); ?></td>
                            <td><?php echo formatNumber($npv, 0); ?></td>
                        </tr>
                        <tr>
                            <td>เลวที่สุด</td>
                            <td>5%</td>
                            <td><?php echo formatNumber($sensitivity['worst_case'], 4); ?></td>
                            <td><?php echo formatNumber($npv * 0.8, 0); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Impact Pathway Section -->
    <div class="section">
        <h2 class="section-title">🗺️ เส้นทางผลกระทบ (Impact Pathway)</h2>
        <div class="impact-breakdown" style="grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));">
            <div class="impact-item">
                <h4>🎯 Input</h4>
                <div class="impact-value">ทรัพยากร</div>
                <p>งบประมาณ: <?php echo formatNumber($total_present_costs, 0); ?> บาท</p>
            </div>
            <div class="impact-item">
                <h4>⚙️ Activities</h4>
                <div class="impact-value">กิจกรรม</div>
                <p>การดำเนินงานตามแผน</p>
            </div>
            <div class="impact-item">
                <h4>📦 Output</h4>
                <div class="impact-value">ผลผลิต</div>
                <p>ผลงานที่เกิดขึ้น</p>
            </div>
            <div class="impact-item">
                <h4>🎁 Outcome</h4>
                <div class="impact-value">ผลลัพธ์</div>
                <p>การเปลี่ยนแปลงที่เกิดขึ้น</p>
            </div>
            <div class="impact-item">
                <h4>🌟 Impact</h4>
                <div class="impact-value">ผลกระทบ</div>
                <p><?php echo formatNumber($net_social_benefit, 0); ?> บาท</p>
            </div>
        </div>
    </div>

    <div class="section">
        <h2 class="section-title">📋 สรุปและข้อเสนอแนะ</h2>

        <?php if ($sroi_ratio > 1): ?>
            <div class="impact-breakdown">
                <div class="impact-item">
                    <h4>✅ ผลการประเมิน</h4>
                    <div class="impact-value highlight-positive">โครงการมีความคุ้มค่า</div>
                    <p>SROI Ratio = <?php echo formatNumber($sroi_ratio, 4); ?> หมายถึง การลงทุน 1 บาท สร้างผลประโยชน์ทางสังคม <?php echo formatNumber($sroi_ratio, 4); ?> บาท</p>
                </div>

                <div class="impact-item">
                    <h4>💰 ผลประโยชน์สุทธิ</h4>
                    <div class="impact-value highlight-positive"><?php echo formatNumber($npv, 0); ?></div>
                    <p>โครงการสร้างมูลค่าเพิ่มให้กับสังคมสุทธิ</p>
                </div>

                <div class="impact-item">
                    <h4>🎯 ข้อเสนอแนะ</h4>
                    <div class="impact-value">ควรดำเนินการต่อ</div>
                    <p>โครงการแสดงผลตอบแทนทางสังคมที่ดี ควรพิจารณาขยายผลหรือทำซ้ำในพื้นที่อื่น</p>
                </div>
            </div>
        <?php else: ?>
            <div class="impact-breakdown">
                <div class="impact-item">
                    <h4>⚠️ ผลการประเมิน</h4>
                    <div class="impact-value highlight-negative">โครงการอาจไม่คุ้มค่า</div>
                    <p>SROI Ratio = <?php echo formatNumber($sroi_ratio, 4); ?> หมายถึง การลงทุน 1 บาท สร้างผลประโยชน์ทางสังคม <?php echo formatNumber($sroi_ratio, 4); ?> บาท</p>
                </div>

                <div class="impact-item">
                    <h4>🎯 ข้อเสนอแนะ</h4>
                    <div class="impact-value">ควรปรับปรุง</div>
                    <p>ควรทบทวนการดำเนินงานเพื่อเพิ่มผลประโยชน์หรือลดต้นทุน</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

<?php elseif ($selected_project): ?>
    <div class="section">
        <div style="text-align: center; padding: 50px; color: #666;">
            <i style="font-size: 4em; margin-bottom: 20px;">📊</i>
            <h3>ไม่พบข้อมูลสำหรับการวิเคราะห์</h3>
            <p>กรุณาเพิ่มข้อมูลต้นทุนและผลประโยชน์ในระบบก่อนสร้างรายงาน</p>
            <div style="margin-top: 20px;">
                <a href="../impact_pathway/cost.php?project_id=<?php echo $selected_project_id; ?>" class="btn">เพิ่มข้อมูลต้นทุน</a>
                <a href="../impact_pathway/benefit.php?project_id=<?php echo $selected_project_id; ?>" class="btn" style="margin-left: 10px;">เพิ่มข้อมูลผลประโยชน์</a>
            </div>
        </div>
    </div>

    <!-- Chart.js Script -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // ข้อมูลจาก PHP สำหรับกราฟ
        const chartData = {
            years: <?php echo json_encode(array_column($available_years, 'year_display')); ?>,
            costs: <?php echo json_encode(array_values($costs_by_year)); ?>,
            benefits: <?php echo json_encode(array_values($benefits_by_year)); ?>,
            presentCosts: <?php echo json_encode(array_values($present_costs_by_year)); ?>,
            presentBenefits: <?php echo json_encode(array_values($present_benefits_by_year)); ?>,
            totalPresentCosts: <?php echo $total_present_costs; ?>,
            totalPresentBenefits: <?php echo $total_present_benefits; ?>,
            baseCaseImpact: <?php echo $base_case_impact; ?>,
            sroiRatio: <?php echo $sroi_ratio; ?>,
            npv: <?php echo $npv; ?>
        };

        // กราฟเปรียบเทียบต้นทุนและผลประโยชน์
        function createCostBenefitChart() {
            const ctx = document.getElementById('costBenefitChart');
            if (!ctx) return;

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: chartData.years,
                    datasets: [
                        {
                            label: 'ต้นทุน (บาท)',
                            data: chartData.costs,
                            backgroundColor: 'rgba(220, 53, 69, 0.8)',
                            borderColor: 'rgba(220, 53, 69, 1)',
                            borderWidth: 2
                        },
                        {
                            label: 'ผลประโยชน์ (บาท)',
                            data: chartData.benefits,
                            backgroundColor: 'rgba(40, 167, 69, 0.8)',
                            borderColor: 'rgba(40, 167, 69, 1)',
                            borderWidth: 2
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'เปรียบเทียบต้นทุนและผลประโยชน์ตามปี'
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('th-TH') + ' บาท';
                                }
                            }
                        }
                    }
                }
            });
        }

        // กราฟแยกส่วนผลประโยชน์
        function createBenefitBreakdownChart() {
            const ctx = document.getElementById('benefitBreakdownChart');
            if (!ctx) return;

            const netBenefit = chartData.totalPresentBenefits - chartData.baseCaseImpact;
            
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: ['ผลประโยชน์สุทธิ', 'ผลกระทบกรณีฐาน'],
                    datasets: [{
                        data: [netBenefit, chartData.baseCaseImpact],
                        backgroundColor: [
                            'rgba(40, 167, 69, 0.8)',
                            'rgba(255, 193, 7, 0.8)'
                        ],
                        borderColor: [
                            'rgba(40, 167, 69, 1)',
                            'rgba(255, 193, 7, 1)'
                        ],
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'การแยกส่วนผลประโยชน์'
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const value = context.parsed;
                                    const total = chartData.totalPresentBenefits;
                                    const percentage = ((value / total) * 100).toFixed(1);
                                    return context.label + ': ' + value.toLocaleString('th-TH') + ' บาท (' + percentage + '%)';
                                }
                            }
                        }
                    }
                }
            });
        }

        // กราฟการกระจายผลกระทบตามปี
        function createImpactDistributionChart() {
            const ctx = document.getElementById('impactDistributionChart');
            if (!ctx) return;

            // คำนวณ Net Impact แต่ละปี (Present Benefit - Present Cost)
            const netImpact = chartData.presentBenefits.map((benefit, index) => 
                benefit - (chartData.presentCosts[index] || 0)
            );

            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: chartData.years,
                    datasets: [
                        {
                            label: 'ผลประโยชน์ปัจจุบันสุทธิ',
                            data: chartData.presentBenefits,
                            borderColor: 'rgba(40, 167, 69, 1)',
                            backgroundColor: 'rgba(40, 167, 69, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'ต้นทุนปัจจุบันสุทธิ',
                            data: chartData.presentCosts,
                            borderColor: 'rgba(220, 53, 69, 1)',
                            backgroundColor: 'rgba(220, 53, 69, 0.1)',
                            fill: true,
                            tension: 0.4
                        },
                        {
                            label: 'ผลกระทบสุทธิ',
                            data: netImpact,
                            borderColor: 'rgba(102, 126, 234, 1)',
                            backgroundColor: 'rgba(102, 126, 234, 0.1)',
                            fill: false,
                            tension: 0.4,
                            borderWidth: 3
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'การกระจายผลกระทบตามปี (Present Value)'
                        },
                        legend: {
                            position: 'top'
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toLocaleString('th-TH') + ' บาท';
                                }
                            }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    }
                }
            });
        }

        // กราห Sensitivity Analysis
        function createSensitivityChart() {
            const ctx = document.getElementById('sensitivityChart');
            if (!ctx) return;

            const scenarios = [
                { name: 'เลวที่สุด', rate: 5, sroi: chartData.sroiRatio * 0.8, color: 'rgba(220, 53, 69, 0.8)' },
                { name: 'ปัจจุบัน', rate: 3, sroi: chartData.sroiRatio, color: 'rgba(102, 126, 234, 0.8)' },
                { name: 'ดีที่สุด', rate: 1, sroi: chartData.sroiRatio * 1.2, color: 'rgba(40, 167, 69, 0.8)' }
            ];

            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: scenarios.map(s => s.name + ' (' + s.rate + '%)'),
                    datasets: [{
                        label: 'SROI Ratio',
                        data: scenarios.map(s => s.sroi),
                        backgroundColor: scenarios.map(s => s.color),
                        borderColor: scenarios.map(s => s.color.replace('0.8', '1')),
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'การวิเคราะห์ความไว (Sensitivity Analysis)'
                        },
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return value.toFixed(2) + ' เท่า';
                                }
                            }
                        }
                    }
                }
            });
        }

        // เรียกใช้ฟังก์ชันสร้างกราฟเมื่อหน้าโหลดเสร็จ
        document.addEventListener('DOMContentLoaded', function() {
            createCostBenefitChart();
            createBenefitBreakdownChart();
            createImpactDistributionChart();
            createSensitivityChart();
        });
    </script>
<?php endif; ?>