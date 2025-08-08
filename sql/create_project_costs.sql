-- สร้างตาราง project_costs สำหรับเก็บข้อมูลต้นทุน/งบประมาณโครงการ
-- ใช้ JSON approach สำหรับเก็บข้อมูลแยกตามปี

CREATE TABLE IF NOT EXISTS `project_costs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `project_id` int(11) NOT NULL,
  `cost_name` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL COMMENT 'ชื่อรายการต้นทุน/งบประมาณ',
  `yearly_amounts` json NOT NULL COMMENT 'จำนวนเงินแยกตามปี พ.ศ. ในรูปแบบ JSON',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `created_by` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_project_id` (`project_id`),
  KEY `idx_created_by` (`created_by`),
  CONSTRAINT `fk_project_costs_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_project_costs_user` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='ตารางเก็บข้อมูลต้นทุน/งบประมาณโครงการ';

-- ตัวอย่างข้อมูลการใช้งาน
-- INSERT INTO project_costs (project_id, cost_name, yearly_amounts, created_by) 
-- VALUES (1, 'ต้นทุน 1', '{"2567": 100000, "2568": 150000, "2569": 200000}', 1);

-- ตัวอย่างการ Query ข้อมูล
-- SELECT id, project_id, cost_name, 
--        JSON_UNQUOTE(JSON_EXTRACT(yearly_amounts, '$.2567')) as year_2567,
--        JSON_UNQUOTE(JSON_EXTRACT(yearly_amounts, '$.2568')) as year_2568,
--        JSON_UNQUOTE(JSON_EXTRACT(yearly_amounts, '$.2569')) as year_2569
-- FROM project_costs 
-- WHERE project_id = 1;

-- สำหรับการค้นหาตามปี
-- SELECT * FROM project_costs 
-- WHERE project_id = 1 
-- AND JSON_EXTRACT(yearly_amounts, '$.2567') IS NOT NULL;