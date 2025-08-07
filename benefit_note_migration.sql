-- =============================================================================
-- Step 4 UI Update: เปลี่ยนฟิลด์ benefit_note จาก TEXT เป็น INT
-- วันที่: 7 สิงหาคม 2568
-- คำอธิบาย: เปลี่ยนประเภทข้อมูลของฟิลด์ benefit_note จาก TEXT เป็น INT 
--          เพื่อเก็บจำนวนเงินเป็นจำนวนเต็ม (บาท/ปี) โดยไม่รองรับทศนิยม
-- =============================================================================

-- ตรวจสอบข้อมูลที่มีอยู่ก่อนทำการแปลง
SELECT 'Current benefit_note data:' as status;
SELECT id, project_id, benefit_number, benefit_note 
FROM project_impact_ratios 
WHERE benefit_note IS NOT NULL AND benefit_note != '' 
LIMIT 10;

-- แปลงข้อมูลที่เป็นข้อความให้เป็นตัวเลข (ลบคอมมาและเก็บเฉพาะตัวเลข)
UPDATE project_impact_ratios 
SET benefit_note = CASE 
    WHEN benefit_note IS NULL OR benefit_note = '' THEN '0'
    WHEN benefit_note REGEXP '^[0-9,]+$' THEN REPLACE(benefit_note, ',', '')
    WHEN benefit_note REGEXP '^[0-9]+\.?[0-9]*$' THEN FLOOR(CAST(benefit_note AS DECIMAL(15,2)))
    ELSE '0'
END
WHERE benefit_note IS NOT NULL;

SELECT 'Data conversion completed' as status;

-- เปลี่ยนประเภทข้อมูลจาก TEXT เป็น INT
ALTER TABLE project_impact_ratios 
MODIFY COLUMN benefit_note INT DEFAULT 0 COMMENT 'จำนวนเงิน (บาท/ปี)';

SELECT 'Schema update completed' as status;

-- ตรวจสอบข้อมูลหลังการแปลง
SELECT 'Updated benefit_note data:' as status;
SELECT id, project_id, benefit_number, benefit_note 
FROM project_impact_ratios 
WHERE benefit_note IS NOT NULL AND benefit_note != 0 
LIMIT 10;

-- หมายเหตุการใช้งาน:
-- 1. ฟิลด์ benefit_note จะเก็บจำนวนเงินเป็นจำนวนเต็ม (ไม่รองรับทศนิยม)
-- 2. ค่าเริ่มต้นคือ 0 หากไม่มีการระบุค่า
-- 3. ใน UI จะแสดงผลพร้อมคอมมาคั่น แต่จัดเก็บเป็นตัวเลขล้วน
-- 4. การคำนวณจะใช้ค่าจำนวนเต็มโดยตรง
