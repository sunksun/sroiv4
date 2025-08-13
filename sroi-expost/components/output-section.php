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
            $present_value = $amount / pow(1 + $default_settings['discount_rate'], $year_index);

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

    // คำนวณ Base Case Impact (สมมติ 10% ของผลประโยชน์)
    $base_case_impact = $total_present_benefits * 0.1;
    $net_social_benefit = $total_present_benefits - $base_case_impact;
    ?>



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
                        <td><?php echo htmlspecialchars($benefit['detail']); ?> (Attribution 5%)</td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                $attribution = $benefit_amount * 0.05; // 5% attribution
                                echo $attribution > 0 ? formatCurrency($attribution, 0) : '-';
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
                        <td><?php echo htmlspecialchars($benefit['detail']); ?> (Deadweight 3%)</td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                $deadweight = $benefit_amount * 0.03; // 3% deadweight
                                echo $deadweight > 0 ? formatCurrency($deadweight, 0) : '-';
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
                        <td><?php echo htmlspecialchars($benefit['detail']); ?> (Displacement 2%)</td>
                        <?php foreach ($available_years as $year): ?>
                            <td>
                                <?php
                                $benefit_amount = isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])
                                    ? $benefit_notes_by_year[$benefit_number][$year['year_be']] : 0;
                                $displacement = $benefit_amount * 0.02; // 2% displacement
                                echo $displacement > 0 ? formatCurrency($displacement, 0) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="metric-cards">
            <div class="metric-card">
                <div class="metric-value"><?php echo formatCurrency($base_case_impact, 0); ?></div>
                <div class="metric-label">ผลกระทบกรณีฐานรวมปัจจุบัน (บาท)</div>
            </div>
        </div>
    </div>

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
                                echo $amount > 0 ? formatCurrency($amount, 0) : '-';
                                ?>
                            </td>
                        <?php endforeach; ?>
                    </tr>
                <?php endforeach; ?>
                <tr class="total-row">
                    <td>รวม (Benefit)</td>
                    <?php foreach ($available_years as $year): ?>
                        <td><?php echo formatCurrency($benefits_by_year[$year['year_be']] ?? 0, 0); ?></td>
                    <?php endforeach; ?>
                </tr>
                <tr class="total-row">
                    <td>ผลประโยชน์ปัจจุบันสุทธิ (Present Benefit)</td>
                    <?php foreach ($available_years as $year): ?>
                        <td><?php echo formatCurrency($present_benefits_by_year[$year['year_be']] ?? 0, 0); ?></td>
                    <?php endforeach; ?>
                </tr>
            </tbody>
        </table>
        <div class="metric-cards">
            <div class="metric-card">
                <div class="metric-value"><?php echo formatCurrency($total_present_benefits, 0); ?></div>
                <div class="metric-label">รวมผลประโยชน์ปัจจุบันสุทธิ (บาท)</div>
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
                SROI = (<?php echo formatCurrency($total_present_benefits, 0); ?> - <?php echo formatCurrency($base_case_impact, 0); ?>) ÷ <?php echo formatCurrency($total_present_costs, 0); ?> = <?php echo formatNumber($sroi_ratio, 4); ?> เท่า
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
                            <td><?php echo formatCurrency($npv * 1.2, 0); ?></td>
                        </tr>
                        <tr class="highlight-positive">
                            <td>ปัจจุบัน</td>
                            <td><?php echo ($default_settings['discount_rate'] * 100); ?>%</td>
                            <td><?php echo formatNumber($sroi_ratio, 4); ?></td>
                            <td><?php echo formatCurrency($npv, 0); ?></td>
                        </tr>
                        <tr>
                            <td>เลวที่สุด</td>
                            <td>5%</td>
                            <td><?php echo formatNumber($sensitivity['worst_case'], 4); ?></td>
                            <td><?php echo formatCurrency($npv * 0.8, 0); ?></td>
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
                <p>งบประมาณ: <?php echo formatCurrency($total_present_costs, 0); ?></p>
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
                <p><?php echo formatCurrency($net_social_benefit, 0); ?> บาท</p>
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
                    <div class="impact-value highlight-positive"><?php echo formatCurrency($npv, 0); ?></div>
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
<?php endif; ?>