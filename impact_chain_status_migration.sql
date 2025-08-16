-- Migration สำหรับเพิ่มระบบสถานะ Impact Chain
-- วันที่: 2025-08-16

-- เพิ่ม column สำหรับ status ในตาราง projects
ALTER TABLE projects ADD COLUMN impact_chain_status JSON DEFAULT NULL;

-- อัปเดตข้อมูลเดิมให้มี default status
UPDATE projects 
SET impact_chain_status = JSON_OBJECT(
    'step1_completed', false,
    'step2_completed', false, 
    'step3_completed', false,
    'step4_completed', false,
    'current_step', 1,
    'last_updated', NOW()
)
WHERE impact_chain_status IS NULL;

-- ตรวจสอบและอัปเดตสถานะสำหรับโครงการที่มีข้อมูล Impact Chain อยู่แล้ว
UPDATE projects p
SET impact_chain_status = JSON_OBJECT(
    'step1_completed', EXISTS(SELECT 1 FROM project_strategies ps WHERE ps.project_id = p.id),
    'step2_completed', EXISTS(SELECT 1 FROM project_activities pa WHERE pa.project_id = p.id),
    'step3_completed', EXISTS(SELECT 1 FROM project_outputs po WHERE po.project_id = p.id),
    'step4_completed', EXISTS(SELECT 1 FROM project_outcomes poc WHERE poc.project_id = p.id),
    'current_step', CASE 
        WHEN EXISTS(SELECT 1 FROM project_outcomes poc WHERE poc.project_id = p.id) THEN 4
        WHEN EXISTS(SELECT 1 FROM project_outputs po WHERE po.project_id = p.id) THEN 4
        WHEN EXISTS(SELECT 1 FROM project_activities pa WHERE pa.project_id = p.id) THEN 3
        WHEN EXISTS(SELECT 1 FROM project_strategies ps WHERE ps.project_id = p.id) THEN 2
        ELSE 1
    END,
    'last_updated', NOW()
)
WHERE p.impact_chain_status IS NOT NULL;

-- เพิ่ม index สำหรับการค้นหาที่เร็วขึ้น
CREATE INDEX idx_projects_impact_chain_status ON projects(impact_chain_status);