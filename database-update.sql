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
