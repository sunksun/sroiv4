<?php
/**
 * Progress Bar Component สำหรับแสดงความคืบหน้า Impact Chain
 * 
 * @author SROIV4 System
 * @created 2025-08-16
 */

require_once __DIR__ . '/impact_chain_status.php';

/**
 * แสดง Progress Bar สำหรับ Impact Chain
 * 
 * @param int $project_id รหัสโครงการ
 * @param int $current_step step ปัจจุบัน (1-4)
 * @param array $status สถานะ Impact Chain (optional - จะ fetch ใหม่หากไม่ระบุ)
 */
function renderImpactChainProgressBar($project_id, $current_step = 1, $status = null) {
    // ดึงสถานะจากฐานข้อมูลหากไม่ได้ระบุ
    if ($status === null) {
        $status = getImpactChainStatus($project_id);
    }
    
    $steps = [
        1 => [
            'name' => 'ยุทธศาสตร์',
            'icon' => 'fas fa-bullseye',
            'description' => 'เลือกยุทธศาสตร์องค์กร'
        ],
        2 => [
            'name' => 'กิจกรรม',
            'icon' => 'fas fa-tasks',
            'description' => 'กำหนดกิจกรรมหลัก'
        ],
        3 => [
            'name' => 'ผลผลิต',
            'icon' => 'fas fa-cube',
            'description' => 'ระบุผลผลิตที่คาดหวัง'
        ],
        4 => [
            'name' => 'ผลลัพธ์',
            'icon' => 'fas fa-chart-line',
            'description' => 'กำหนดผลลัพธ์และการวัดผล'
        ]
    ];
    
    $progress_percentage = calculateProgress($status);
    
    ?>
    <div class="impact-chain-progress mb-4">
        <!-- Progress Bar -->
        <div class="progress" style="height: 35px; background-color: #f8f9fa;">
            <div class="progress-bar bg-gradient-primary" 
                 role="progressbar" 
                 style="width: <?php echo $progress_percentage; ?>%;"
                 aria-valuenow="<?php echo $progress_percentage; ?>" 
                 aria-valuemin="0" 
                 aria-valuemax="100">
            </div>
        </div>
        
        <!-- Step Labels -->
        <div class="row mt-3">
            <?php for ($i = 1; $i <= 4; $i++): 
                $step_completed = $status["step{$i}_completed"];
                $is_current = ($i == $current_step);
                $can_access = canAccessStep($status, $i);
                
                // กำหนดสีและไอคอน
                if ($step_completed) {
                    $class = 'text-success';
                    $icon = 'fas fa-check-circle';
                    $badge_class = 'bg-success';
                } elseif ($is_current) {
                    $class = 'text-primary';
                    $icon = 'fas fa-clock';
                    $badge_class = 'bg-primary';
                } elseif ($can_access) {
                    $class = 'text-info';
                    $icon = 'fas fa-circle';
                    $badge_class = 'bg-light text-dark';
                } else {
                    $class = 'text-muted';
                    $icon = 'fas fa-lock';
                    $badge_class = 'bg-secondary';
                }
            ?>
            <div class="col-3">
                <div class="text-center">
                    <div class="mb-2">
                        <span class="badge <?php echo $badge_class; ?> rounded-circle d-inline-flex align-items-center justify-content-center" 
                              style="width: 40px; height: 40px; font-size: 16px;">
                            <i class="<?php echo $icon; ?>"></i>
                        </span>
                    </div>
                    <h6 class="<?php echo $class; ?> fw-bold mb-1" style="font-size: 14px;">
                        <?php echo $i; ?>. <?php echo $steps[$i]['name']; ?>
                    </h6>
                    <small class="<?php echo $class; ?>" style="font-size: 11px;">
                        <?php echo $steps[$i]['description']; ?>
                    </small>
                    
                    <?php if ($step_completed): ?>
                        <div class="mt-1">
                            <small class="text-success">
                                <i class="fas fa-check"></i> เสร็จสิ้น
                            </small>
                        </div>
                    <?php elseif ($is_current): ?>
                        <div class="mt-1">
                            <small class="text-primary">
                                <i class="fas fa-arrow-right"></i> กำลังดำเนินการ
                            </small>
                        </div>
                    <?php elseif (!$can_access): ?>
                        <div class="mt-1">
                            <small class="text-muted">
                                <i class="fas fa-lock"></i> ล็อค
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endfor; ?>
        </div>
        
        <!-- Progress Info -->
        <div class="row mt-3">
            <div class="col-12">
                <div class="alert alert-info d-flex align-items-center" style="background-color: #e7f3ff; border-color: #b3d9ff;">
                    <i class="fas fa-info-circle me-2"></i>
                    <div>
                        <strong>ความคืบหน้า:</strong> 
                        <?php 
                        $completed_count = 0;
                        for ($i = 1; $i <= 4; $i++) {
                            if ($status["step{$i}_completed"]) $completed_count++;
                        }
                        echo "เสร็จสิ้น {$completed_count} จาก 4 ขั้นตอน";
                        ?>
                        <span class="badge bg-primary ms-2"><?php echo round($progress_percentage); ?>%</span>
                        
                        <?php
                        // แสดงข้อมูล Multiple Impact Chains ถ้ามี
                        try {
                            if (function_exists('getMultipleImpactChainStatus')) {
                                $multi_status = getMultipleImpactChainStatus($project_id);
                                if (isset($multi_status['multiple_chains']) && 
                                    ($multi_status['multiple_chains']['total_chains'] > 0 || $multi_status['multiple_chains']['has_old_chain'])) {
                                    echo "<br><strong>Impact Chains:</strong> ";
                                    if ($multi_status['multiple_chains']['has_old_chain']) {
                                        echo "Chain เดิม: 1 ";
                                    }
                                    if ($multi_status['multiple_chains']['total_chains'] > 0) {
                                        echo "Chain ใหม่: " . $multi_status['multiple_chains']['completed_chains'] . "/" . $multi_status['multiple_chains']['total_chains'] . " เสร็จสิ้น";
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            // ไม่แสดง error เพื่อไม่ให้หน้าเว็บพัง
                            error_log("Error in Multiple Impact Chain status: " . $e->getMessage());
                        }
                        ?>
                        
                        <?php if ($status['last_updated']): ?>
                            <br><small class="text-muted">
                                อัปเดตล่าสุด: <?php echo date('d/m/Y H:i:s', strtotime($status['last_updated'])); ?>
                            </small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    .impact-chain-progress .progress-bar {
        transition: width 0.3s ease-in-out;
    }
    
    .impact-chain-progress .badge {
        transition: all 0.2s ease-in-out;
    }
    
    .impact-chain-progress .badge:hover {
        transform: scale(1.1);
    }
    
    .bg-gradient-primary {
        background: linear-gradient(135deg, #007bff 0%, #0056b3 100%);
    }
    </style>
    <?php
}

/**
 * แสดง Navigation Buttons สำหรับ Impact Chain
 * 
 * @param int $project_id รหัสโครงการ
 * @param int $current_step step ปัจจุบัน
 * @param array $status สถานะ Impact Chain
 * @param string $back_url URL สำหรับปุ่มย้อนกลับ
 * @param string $next_url URL สำหรับปุ่มถัดไป
 */
function renderImpactChainNavigation($project_id, $current_step, $status, $back_url = null, $next_url = null) {
    ?>
    <div class="d-flex justify-content-between mt-4">
        <div>
            <?php if ($back_url): ?>
                <a href="<?php echo htmlspecialchars($back_url); ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> ย้อนกลับ
                </a>
            <?php else: ?>
                <a href="../project-list.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> รายการโครงการ
                </a>
            <?php endif; ?>
        </div>
        
        <div>
            <?php if ($current_step < 4 && $next_url): ?>
                <a href="<?php echo htmlspecialchars($next_url); ?>" class="btn btn-primary">
                    ถัดไป: Step <?php echo ($current_step + 1); ?> <i class="fas fa-arrow-right"></i>
                </a>
            <?php elseif ($current_step == 4): ?>
                <a href="../impact_pathway/impact_pathway.php?project_id=<?php echo $project_id; ?>" class="btn btn-success">
                    <i class="fas fa-calculator"></i> คำนวณ SROI
                </a>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * แสดง Step Access Warning
 * 
 * @param int $requested_step step ที่ต้องการเข้าถึง
 * @param array $status สถานะ Impact Chain
 */
function renderStepAccessWarning($requested_step, $status) {
    if (!canAccessStep($status, $requested_step)) {
        $previous_step = $requested_step - 1;
        ?>
        <div class="alert alert-warning" role="alert">
            <h4 class="alert-heading"><i class="fas fa-exclamation-triangle"></i> ไม่สามารถเข้าถึงขั้นตอนนี้ได้</h4>
            <p>คุณต้องทำให้ขั้นตอนที่ <?php echo $previous_step; ?> เสร็จสิ้นก่อน จึงจะสามารถเข้าถึงขั้นตอนนี้ได้</p>
            <hr>
            <p class="mb-0">
                <a href="step<?php echo $previous_step; ?>-<?php 
                    $step_files = [1 => 'strategy', 2 => 'activity', 3 => 'output', 4 => 'outcome'];
                    echo $step_files[$previous_step]; 
                ?>.php?project_id=<?php echo $_GET['project_id']; ?>" class="btn btn-primary">
                    <i class="fas fa-arrow-left"></i> กลับไปขั้นตอนที่ <?php echo $previous_step; ?>
                </a>
            </p>
        </div>
        <?php
        return false;
    }
    return true;
}
?>