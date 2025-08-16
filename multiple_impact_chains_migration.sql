-- Migration สำหรับระบบ Multiple Impact Chains
-- วันที่: 2025-08-16

-- สร้างตาราง impact_chains สำหรับเก็บ Impact Chain หลายๆ อัน
CREATE TABLE impact_chains (
    id INT AUTO_INCREMENT PRIMARY KEY,
    project_id INT NOT NULL,
    chain_name VARCHAR(500) NOT NULL COMMENT 'ชื่อ Impact Chain (ใช้ชื่อกิจกรรม)',
    activity_id INT NOT NULL COMMENT 'กิจกรรมหลักของ Chain นี้',
    sequence_order INT DEFAULT 1 COMMENT 'ลำดับการสร้าง',
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (project_id) REFERENCES projects(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id),
    INDEX idx_project_chains (project_id, status),
    INDEX idx_chain_sequence (project_id, sequence_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- สร้างตาราง impact_chain_activities (แทน project_activities)
CREATE TABLE impact_chain_activities (
    id INT AUTO_INCREMENT PRIMARY KEY,
    impact_chain_id INT NOT NULL,
    activity_id INT NOT NULL,
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (impact_chain_id) REFERENCES impact_chains(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(activity_id),
    INDEX idx_chain_activities (impact_chain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- สร้างตาราง impact_chain_outputs (แทน project_outputs)
CREATE TABLE impact_chain_outputs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    impact_chain_id INT NOT NULL,
    output_id INT NOT NULL,
    output_details TEXT COMMENT 'รายละเอียดเพิ่มเติมของผลผลิต',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (impact_chain_id) REFERENCES impact_chains(id) ON DELETE CASCADE,
    FOREIGN KEY (output_id) REFERENCES outputs(output_id),
    INDEX idx_chain_outputs (impact_chain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- สร้างตาราง impact_chain_outcomes (แทน project_outcomes)
CREATE TABLE impact_chain_outcomes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    impact_chain_id INT NOT NULL,
    outcome_id INT NOT NULL,
    outcome_details TEXT COMMENT 'รายละเอียดเพิ่มเติมของผลลัพธ์',
    evaluation_year VARCHAR(10) COMMENT 'ปีที่ประเมิน',
    benefit_data LONGTEXT COMMENT 'ข้อมูลผลประโยชน์ในรูปแบบ JSON',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (impact_chain_id) REFERENCES impact_chains(id) ON DELETE CASCADE,
    FOREIGN KEY (outcome_id) REFERENCES outcomes(outcome_id),
    INDEX idx_chain_outcomes (impact_chain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- สร้างตาราง impact_chain_ratios (แทน project_impact_ratios)
CREATE TABLE impact_chain_ratios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    impact_chain_id INT NOT NULL,
    outcome_id INT NOT NULL,
    deadweight DECIMAL(5,2) DEFAULT 0.00 COMMENT 'สัดส่วน Deadweight (%)',
    attribution DECIMAL(5,2) DEFAULT 0.00 COMMENT 'สัดส่วน Attribution (%)',
    displacement DECIMAL(5,2) DEFAULT 0.00 COMMENT 'สัดส่วน Displacement (%)',
    drop_off DECIMAL(5,2) DEFAULT 0.00 COMMENT 'สัดส่วน Drop-off (%)',
    created_by VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (impact_chain_id) REFERENCES impact_chains(id) ON DELETE CASCADE,
    FOREIGN KEY (outcome_id) REFERENCES outcomes(outcome_id),
    UNIQUE KEY unique_chain_outcome (impact_chain_id, outcome_id),
    INDEX idx_chain_ratios (impact_chain_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- เพิ่มฟิลด์ในตาราง projects สำหรับติดตาม Impact Chain
ALTER TABLE projects 
ADD COLUMN total_impact_chains INT DEFAULT 0 COMMENT 'จำนวน Impact Chain ทั้งหมด',
ADD COLUMN current_chain_id INT NULL COMMENT 'Impact Chain ที่กำลังทำอยู่',
ADD INDEX idx_current_chain (current_chain_id);

-- อัปเดต impact_chain_status ให้รองรับ multiple chains
UPDATE projects 
SET impact_chain_status = JSON_SET(
    COALESCE(impact_chain_status, '{}'),
    '$.multiple_chains_enabled', true,
    '$.total_chains', 0,
    '$.current_chain', 1,
    '$.chains', JSON_OBJECT()
)
WHERE impact_chain_status IS NOT NULL;

-- Migration ข้อมูลเดิมจาก project_* ไป impact_chain_*
-- สร้าง Impact Chain แรกสำหรับโครงการที่มีข้อมูลอยู่แล้ว

INSERT INTO impact_chains (project_id, chain_name, activity_id, sequence_order, created_by)
SELECT DISTINCT 
    pa.project_id,
    CONCAT('Impact Chain 1: ', a.activity_name),
    pa.activity_id,
    1,
    pa.created_by
FROM project_activities pa
JOIN activities a ON pa.activity_id = a.activity_id
WHERE pa.project_id IN (
    SELECT DISTINCT project_id 
    FROM project_activities 
    WHERE project_id IS NOT NULL
);

-- Migration activities
INSERT INTO impact_chain_activities (impact_chain_id, activity_id, created_by)
SELECT 
    ic.id,
    pa.activity_id,
    pa.created_by
FROM impact_chains ic
JOIN project_activities pa ON ic.project_id = pa.project_id AND ic.activity_id = pa.activity_id;

-- Migration outputs
INSERT INTO impact_chain_outputs (impact_chain_id, output_id, output_details, created_by)
SELECT 
    ic.id,
    po.output_id,
    po.output_details,
    po.created_by
FROM impact_chains ic
JOIN project_outputs po ON ic.project_id = po.project_id;

-- Migration outcomes
INSERT INTO impact_chain_outcomes (impact_chain_id, outcome_id, outcome_details, created_by)
SELECT 
    ic.id,
    poc.outcome_id,
    poc.outcome_details,
    poc.created_by
FROM impact_chains ic
JOIN project_outcomes poc ON ic.project_id = poc.project_id;

-- Migration impact ratios
INSERT INTO impact_chain_ratios (impact_chain_id, outcome_id, deadweight, attribution, displacement, drop_off, created_by)
SELECT 
    ic.id,
    pir.outcome_id,
    pir.deadweight,
    pir.attribution,
    pir.displacement,
    pir.drop_off,
    pir.created_by
FROM impact_chains ic
JOIN project_impact_ratios pir ON ic.project_id = pir.project_id;

-- อัปเดตจำนวน Impact Chain ในตาราง projects
UPDATE projects p
SET 
    total_impact_chains = (
        SELECT COUNT(*) 
        FROM impact_chains ic 
        WHERE ic.project_id = p.id AND ic.status = 'active'
    ),
    current_chain_id = (
        SELECT id 
        FROM impact_chains ic 
        WHERE ic.project_id = p.id AND ic.status = 'active' 
        ORDER BY ic.sequence_order DESC 
        LIMIT 1
    );

-- อัปเดตสถานะ JSON
UPDATE projects 
SET impact_chain_status = JSON_SET(
    impact_chain_status,
    '$.total_chains', total_impact_chains,
    '$.current_chain', COALESCE(total_impact_chains, 1)
)
WHERE total_impact_chains > 0;