        <div class="footer">
            <p>📊 รายงาน SROI Ex-post Analysis | ระบบประเมินผลกระทบทางสังคม</p>
            <p>พัฒนาโดยทีมงาน SROI System | © <?php echo date('Y'); ?></p>
        </div>
    </div>
    
    <script src="assets/js/charts.js"></script>
    <script src="assets/js/main.js"></script>
    
    <script>
        // Initialize charts with actual data if available
        <?php if (isset($present_costs_by_year) && isset($present_benefits_by_year)): ?>
        document.addEventListener('DOMContentLoaded', function() {
            const costsData = <?php echo json_encode(array_values($present_costs_by_year)); ?>;
            const benefitsData = <?php echo json_encode(array_values($present_benefits_by_year)); ?>;
            const yearsData = <?php echo json_encode(array_column($available_years, 'year_display')); ?>;
            
            // สร้างกราฟเปรียบเทียบต้นทุนและผลประโยชน์
            if (costsData.length > 0 && benefitsData.length > 0) {
                createCostBenefitChart(costsData, benefitsData, yearsData);
                createImpactDistributionChart(costsData, benefitsData, yearsData);
            }
            
            // สร้างกราฟแยกส่วนผลประโยชน์
            <?php if (!empty($project_benefits)): ?>
            const benefitLabels = <?php 
                $benefit_labels = [];
                foreach ($project_benefits as $benefit) {
                    $benefit_labels[] = $benefit['detail'];
                }
                echo json_encode($benefit_labels); 
            ?>;
            const individualBenefitsData = [];
            <?php 
            foreach ($project_benefits as $benefit_number => $benefit) {
                $total_benefit = 0;
                foreach ($available_years as $year) {
                    if (isset($benefit_notes_by_year[$benefit_number]) && isset($benefit_notes_by_year[$benefit_number][$year['year_be']])) {
                        $total_benefit += floatval($benefit_notes_by_year[$benefit_number][$year['year_be']]);
                    }
                }
                echo "individualBenefitsData.push(" . $total_benefit . ");\n";
            }
            ?>
            createBenefitBreakdownChart(individualBenefitsData, benefitLabels);
            <?php endif; ?>
            
            // การวิเคราะห์ความอ่อนไหว
            <?php if (isset($sensitivity)): ?>
            createSensitivityChart(
                <?php echo $sensitivity['best_case']; ?>,
                <?php echo $sensitivity['base_case']; ?>,
                <?php echo $sensitivity['worst_case']; ?>
            );
            <?php endif; ?>
        });
        <?php endif; ?>
    </script>
</body>
</html>