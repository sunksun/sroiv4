-- สร้างตารางสำหรับเก็บข้อมูลการเลือก strategies, activities, และ outputs ของโครงการ
-- ไฟล์นี้ควรถูกรันเพื่อปรับปรุงฐานข้อมูลให้รองรับการบันทึกข้อมูลจริง

-- ตารางเก็บข้อมูลการเลือกยุทธศาสตร์ของโครงการ
CREATE TABLE `project_strategies` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT 'รหัสโครงการ',
  `strategy_id` int(11) NOT NULL COMMENT 'รหัสยุทธศาสตร์',
  `created_by` varchar(100) DEFAULT NULL COMMENT 'ผู้สร้าง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_strategy_id` (`strategy_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`strategy_id`) REFERENCES `strategies`(`strategy_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บการเลือกยุทธศาสตร์ของโครงการ';

-- ตารางเก็บข้อมูลการเลือกกิจกรรมของโครงการ
CREATE TABLE `project_activities` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT 'รหัสโครงการ',
  `activity_id` int(11) NOT NULL COMMENT 'รหัสกิจกรรม',
  `created_by` varchar(100) DEFAULT NULL COMMENT 'ผู้สร้าง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_activity_id` (`activity_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`activity_id`) REFERENCES `activities`(`activity_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บการเลือกกิจกรรมของโครงการ';

-- ตารางเก็บข้อมูลการเลือก outputs ของโครงการ
CREATE TABLE `project_outputs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL COMMENT 'รหัสโครงการ',
  `output_id` int(11) NOT NULL COMMENT 'รหัสผลผลิต',
  `output_details` text DEFAULT NULL COMMENT 'รายละเอียดเพิ่มเติมของผลผลิต',
  `created_by` varchar(100) DEFAULT NULL COMMENT 'ผู้สร้าง',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_output_id` (`output_id`),
  FOREIGN KEY (`project_id`) REFERENCES `projects`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`output_id`) REFERENCES `outputs`(`output_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บการเลือกผลผลิตของโครงการ';

-- เพิ่ม Triggers สำหรับ audit logging (ถ้าต้องการ)
DELIMITER $$
CREATE TRIGGER `audit_project_strategies_insert` AFTER INSERT ON `project_strategies` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (table_name, record_id, action, new_values, user_id, timestamp)
    VALUES ('project_strategies', NEW.id, 'INSERT', JSON_OBJECT(
        'project_id', NEW.project_id,
        'strategy_id', NEW.strategy_id,
        'created_by', NEW.created_by
    ), NULL, NOW());
END$$

CREATE TRIGGER `audit_project_activities_insert` AFTER INSERT ON `project_activities` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (table_name, record_id, action, new_values, user_id, timestamp)
    VALUES ('project_activities', NEW.id, 'INSERT', JSON_OBJECT(
        'project_id', NEW.project_id,
        'activity_id', NEW.activity_id,
        'created_by', NEW.created_by
    ), NULL, NOW());
END$$

CREATE TRIGGER `audit_project_outputs_insert` AFTER INSERT ON `project_outputs` FOR EACH ROW BEGIN
    INSERT INTO audit_logs (table_name, record_id, action, new_values, user_id, timestamp)
    VALUES ('project_outputs', NEW.id, 'INSERT', JSON_OBJECT(
        'project_id', NEW.project_id,
        'output_id', NEW.output_id,
        'created_by', NEW.created_by
    ), NULL, NOW());
END$$
DELIMITER ;

-- หมายเหตุ: หลังจากรันไฟล์นี้แล้ว จึงควรปรับปรุงไฟล์ process-step*.php ให้บันทึกข้อมูลจริงลงตารางเหล่านี้

-- =============================================================================
-- Step 4 UI Update: เปลี่ยนฟิลด์ benefit_note จาก TEXT เป็น INT
-- วันที่: 7 สิงหาคม 2568
-- คำอธิบาย: เปลี่ยนประเภทข้อมูลของฟิลด์ benefit_note จาก TEXT เป็น INT 
--          เพื่อเก็บจำนวนเงินเป็นจำนวนเต็ม (บาท/ปี) โดยไม่รองรับทศนิยม
-- =============================================================================

-- ตรวจสอบข้อมูลที่มีอยู่ก่อนทำการแปลง
-- SELECT benefit_note FROM project_impact_ratios WHERE benefit_note IS NOT NULL AND benefit_note != '';

-- แปลงข้อมูลที่เป็นข้อความให้เป็นตัวเลข (ลบคอมมาและเก็บเฉพาะตัวเลข)
UPDATE project_impact_ratios 
SET benefit_note = CASE 
    WHEN benefit_note IS NULL OR benefit_note = '' THEN '0'
    WHEN benefit_note REGEXP '^[0-9,]+$' THEN REPLACE(benefit_note, ',', '')
    WHEN benefit_note REGEXP '^[0-9]+\.?[0-9]*$' THEN FLOOR(CAST(benefit_note AS DECIMAL(15,2)))
    ELSE '0'
END
WHERE benefit_note IS NOT NULL;

-- เปลี่ยนประเภทข้อมูลจาก TEXT เป็น INT
ALTER TABLE project_impact_ratios 
MODIFY COLUMN benefit_note INT DEFAULT 0 COMMENT 'จำนวนเงิน (บาท/ปี)';

-- หมายเหตุการใช้งาน:
-- 1. ฟิลด์ benefit_note จะเก็บจำนวนเงินเป็นจำนวนเต็ม (ไม่รองรับทศนิยม)
-- 2. ค่าเริ่มต้นคือ 0 หากไม่มีการระบุค่า
-- 3. ใน UI จะแสดงผลพร้อมคอมมาคั่น แต่จัดเก็บเป็นตัวเลขล้วน
-- 4. การคำนวณจะใช้ค่าจำนวนเต็มโดยตรง
