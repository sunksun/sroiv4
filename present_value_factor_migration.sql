-- Migration for Present Value Factor table
-- สร้างตารางเก็บข้อมูล Present Value Factor

CREATE TABLE `present_value_factors` (
  `pvf_id` int(11) NOT NULL AUTO_INCREMENT,
  `pvf_name` varchar(100) NOT NULL COMMENT 'ชื่อชุดข้อมูล PVF',
  `discount_rate` decimal(5,2) NOT NULL COMMENT 'อัตราคิดลดร้อยละ',
  `year_id` int(11) NOT NULL COMMENT 'ID ของปี (FK จาก years table)',
  `time_period` int(11) NOT NULL COMMENT 'ช่วงเวลา t (0,1,2,3,4,5)',
  `pvf_value` decimal(10,6) NOT NULL COMMENT 'ค่า Present Value Factor',
  `created_by` int(11) DEFAULT NULL COMMENT 'ผู้สร้างข้อมูล (FK จาก users)',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  PRIMARY KEY (`pvf_id`),
  KEY `idx_pvf_name` (`pvf_name`),
  KEY `idx_discount_rate` (`discount_rate`),
  KEY `idx_year_id` (`year_id`),
  KEY `idx_time_period` (`time_period`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_is_active` (`is_active`),
  UNIQUE KEY `unique_pvf_entry` (`pvf_name`, `year_id`, `time_period`),
  FOREIGN KEY (`year_id`) REFERENCES `years` (`year_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บข้อมูล Present Value Factor';

-- สร้างตารางเก็บ PVF Sets (กลุ่มชุดข้อมูล)
CREATE TABLE `pvf_sets` (
  `set_id` int(11) NOT NULL AUTO_INCREMENT,
  `set_name` varchar(100) NOT NULL COMMENT 'ชื่อชุดข้อมูล PVF',
  `description` text DEFAULT NULL COMMENT 'รายละเอียดชุดข้อมูล',
  `discount_rate` decimal(5,2) NOT NULL COMMENT 'อัตราคิดลดร้อยละ',
  `total_years` int(11) NOT NULL DEFAULT 6 COMMENT 'จำนวนปีทั้งหมด',
  `created_by` int(11) DEFAULT NULL COMMENT 'ผู้สร้างข้อมูล',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `is_active` tinyint(1) DEFAULT 1 COMMENT 'สถานะการใช้งาน',
  `is_default` tinyint(1) DEFAULT 0 COMMENT 'เป็นชุดข้อมูลเริ่มต้นหรือไม่',
  PRIMARY KEY (`set_id`),
  KEY `idx_set_name` (`set_name`),
  KEY `idx_discount_rate` (`discount_rate`),
  KEY `idx_created_by` (`created_by`),
  KEY `idx_is_active` (`is_active`),
  KEY `idx_is_default` (`is_default`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บชุดข้อมูล Present Value Factor';

-- Insert default PVF set
INSERT INTO `pvf_sets` (`set_name`, `description`, `discount_rate`, `total_years`, `is_default`, `is_active`) 
VALUES ('ค่าเริ่มต้น', 'ชุดข้อมูล Present Value Factor เริ่มต้น', 2.00, 6, 1, 1);

-- Insert default PVF values (2% discount rate)
INSERT INTO `present_value_factors` (`pvf_name`, `discount_rate`, `year_id`, `time_period`, `pvf_value`) 
SELECT 
    'ค่าเริ่มต้น' as pvf_name,
    2.00 as discount_rate,
    y.year_id,
    (ROW_NUMBER() OVER (ORDER BY y.sort_order) - 1) as time_period,
    ROUND(1 / POWER(1.02, (ROW_NUMBER() OVER (ORDER BY y.sort_order) - 1)), 6) as pvf_value
FROM years y 
WHERE y.is_active = 1 
ORDER BY y.sort_order 
LIMIT 6;