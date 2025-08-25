<?php
session_start();
require_once '../config.php';

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตั้งค่าตัวแปรสำหรับข้อความแจ้งเตือน
$message = '';
$error = '';

// ดึงข้อมูล session ที่จำเป็น
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// รับ project_id จาก URL
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : 0;

// ดึงข้อมูลจาก step4 session (ถ้ามี)
$step4_data = isset($_SESSION['step4_data']) ? $_SESSION['step4_data'] : null;
$step4_info = '';
if ($step4_data && $step4_data['project_id'] == $project_id) {
    $step4_info = [
        'selected_outcome' => $step4_data['selected_outcome'],
        'outcome_details' => $step4_data['outcome_details'],
        'evaluation_year' => $step4_data['evaluation_year'],
        'benefit_data' => json_decode($step4_data['benefit_data'], true) ?: [],
        'timestamp' => $step4_data['timestamp']
    ];
}

// ดึงข้อมูลโครงการที่เลือก
$selected_project = null;
if ($project_id > 0) {
    $project_query = "SELECT id, project_code, name FROM projects WHERE id = ?";
    $project_stmt = mysqli_prepare($conn, $project_query);
    mysqli_stmt_bind_param($project_stmt, "i", $project_id);
    mysqli_stmt_execute($project_stmt);
    $project_result = mysqli_stmt_get_result($project_stmt);
    $selected_project = mysqli_fetch_assoc($project_result);
    mysqli_stmt_close($project_stmt);
}

// ดึงข้อมูล impact pathway ที่มีอยู่แล้วสำหรับโครงการนี้
$existing_pathways = [];
if ($project_id > 0) {
    $pathway_query = "SELECT * FROM social_impact_pathway WHERE project_id = ? ORDER BY created_at DESC";
    $pathway_stmt = mysqli_prepare($conn, $pathway_query);
    mysqli_stmt_bind_param($pathway_stmt, "i", $project_id);
    mysqli_stmt_execute($pathway_stmt);
    $pathway_result = mysqli_stmt_get_result($pathway_stmt);
    while ($pathway = mysqli_fetch_assoc($pathway_result)) {
        $existing_pathways[] = $pathway;
    }
    mysqli_stmt_close($pathway_stmt);
}

// ดึงข้อมูลจากทุกขั้นตอนของโครงการ
$project_strategies = [];  // Step 1
$project_activities = [];  // Step 2
$project_outputs = [];     // Step 3
$project_outcomes = [];    // Step 4
$project_beneficiaries = [];  // จากการคำนวณ

if ($project_id > 0) {
    // Step 1: ดึงยุทธศาสตร์ที่โครงการเลือกใช้
    $strategies_query = "
        SELECT DISTINCT s.strategy_id, s.strategy_code, s.strategy_name, s.description
        FROM strategies s
        INNER JOIN project_strategies ps ON s.strategy_id = ps.strategy_id
        WHERE ps.project_id = ?
        ORDER BY s.strategy_code
    ";
    $strategies_stmt = mysqli_prepare($conn, $strategies_query);
    mysqli_stmt_bind_param($strategies_stmt, "i", $project_id);
    mysqli_stmt_execute($strategies_stmt);
    $strategies_result = mysqli_stmt_get_result($strategies_stmt);
    while ($strategy = mysqli_fetch_assoc($strategies_result)) {
        $project_strategies[] = $strategy;
    }
    mysqli_stmt_close($strategies_stmt);

    // Step 2: ดึงกิจกรรมที่โครงการเลือกใช้ (ทั้งระบบเดิมและใหม่)
    // ระบบเดิม - จาก project_activities
    $activities_query_legacy = "
        SELECT DISTINCT a.activity_id, a.activity_code, a.activity_name, a.activity_description, 'legacy' as source_type
        FROM activities a
        INNER JOIN project_activities pa ON a.activity_id = pa.activity_id
        WHERE pa.project_id = ?
    ";

    // ระบบใหม่ - จาก impact_chains
    $activities_query_new = "
        SELECT DISTINCT a.activity_id, a.activity_code, a.activity_name, a.activity_description, 'new_chain' as source_type
        FROM activities a
        INNER JOIN impact_chains ic ON a.activity_id = ic.activity_id
        WHERE ic.project_id = ?
    ";

    // รวมกิจกรรมทั้งหมด
    $combined_activities_query = "
        ($activities_query_legacy)
        UNION ALL
        ($activities_query_new)
        ORDER BY activity_code
    ";

    $activities_stmt = mysqli_prepare($conn, $combined_activities_query);
    mysqli_stmt_bind_param($activities_stmt, "ii", $project_id, $project_id);
    mysqli_stmt_execute($activities_stmt);
    $activities_result = mysqli_stmt_get_result($activities_stmt);

    // เก็บกิจกรรมไม่ให้ซ้ำ
    $activity_ids_seen = [];
    while ($activity = mysqli_fetch_assoc($activities_result)) {
        // หากเป็น activity_id เดียวกัน ให้แสดงเฉพาะครั้งแรก
        if (!in_array($activity['activity_id'], $activity_ids_seen)) {
            $project_activities[] = $activity;
            $activity_ids_seen[] = $activity['activity_id'];
        }
    }
    mysqli_stmt_close($activities_stmt);

    // ดึงผลผลิตและผลลัพธ์ที่เกี่ยวข้องกับแต่ละกิจกรรม (ทั้งระบบเดิมและใหม่)
    $project_outputs = [];
    $project_outcomes = [];

    // ดึงผลผลิตจากระบบเดิม (project_outputs)
    $outputs_query_legacy = "
        SELECT DISTINCT o.output_id, o.output_sequence, o.output_description, o.target_details, o.activity_id,
               po.output_details as project_output_details, a.activity_code, a.activity_name,
               'legacy' as source_type
        FROM outputs o
        INNER JOIN project_outputs po ON o.output_id = po.output_id
        INNER JOIN activities a ON o.activity_id = a.activity_id
        WHERE po.project_id = ?
        ORDER BY a.activity_code, o.output_sequence
    ";

    $outputs_stmt_legacy = mysqli_prepare($conn, $outputs_query_legacy);
    mysqli_stmt_bind_param($outputs_stmt_legacy, "i", $project_id);
    mysqli_stmt_execute($outputs_stmt_legacy);
    $outputs_result_legacy = mysqli_stmt_get_result($outputs_stmt_legacy);
    while ($output = mysqli_fetch_assoc($outputs_result_legacy)) {
        $project_outputs[] = $output;
    }
    mysqli_stmt_close($outputs_stmt_legacy);

    // ดึงผลผลิตจากระบบใหม่ (impact_chain_outputs)
    $outputs_query_new = "
        SELECT DISTINCT o.output_id, o.output_sequence, o.output_description, o.target_details, o.activity_id,
               ico.output_details as project_output_details, a.activity_code, a.activity_name,
               'new_chain' as source_type, ic.id as chain_id
        FROM outputs o
        INNER JOIN impact_chain_outputs ico ON o.output_id = ico.output_id
        INNER JOIN impact_chains ic ON ico.impact_chain_id = ic.id
        INNER JOIN activities a ON ic.activity_id = a.activity_id
        WHERE ic.project_id = ?
        ORDER BY a.activity_code, o.output_sequence
    ";

    $outputs_stmt_new = mysqli_prepare($conn, $outputs_query_new);
    mysqli_stmt_bind_param($outputs_stmt_new, "i", $project_id);
    mysqli_stmt_execute($outputs_stmt_new);
    $outputs_result_new = mysqli_stmt_get_result($outputs_stmt_new);
    while ($output = mysqli_fetch_assoc($outputs_result_new)) {
        // ตรวจสอบไม่ให้ซ้ำ
        $found = false;
        foreach ($project_outputs as $existing_output) {
            if ($existing_output['output_id'] == $output['output_id']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $project_outputs[] = $output;
        }
    }
    mysqli_stmt_close($outputs_stmt_new);

    // ดึงผลลัพธ์จากระบบเดิม (project_outcomes)
    $outcomes_query_legacy = "
        SELECT DISTINCT oc.outcome_id, oc.outcome_sequence, oc.outcome_description, 
               o.output_sequence, o.output_description as output_desc, o.activity_id,
               po_custom.outcome_details as project_outcome_details,
               a.activity_code, a.activity_name, 'legacy' as source_type
        FROM project_outcomes po_custom
        INNER JOIN outcomes oc ON po_custom.outcome_id = oc.outcome_id
        INNER JOIN outputs o ON oc.output_id = o.output_id
        INNER JOIN activities a ON o.activity_id = a.activity_id
        WHERE po_custom.project_id = ?
    ";

    $outcomes_stmt_legacy = mysqli_prepare($conn, $outcomes_query_legacy);
    mysqli_stmt_bind_param($outcomes_stmt_legacy, "i", $project_id);
    mysqli_stmt_execute($outcomes_stmt_legacy);
    $outcomes_result_legacy = mysqli_stmt_get_result($outcomes_stmt_legacy);
    while ($outcome = mysqli_fetch_assoc($outcomes_result_legacy)) {
        $project_outcomes[] = $outcome;
    }
    mysqli_stmt_close($outcomes_stmt_legacy);

    // ดึงผลลัพธ์จากระบบใหม่ (impact_chain_outcomes)
    $outcomes_query_new = "
        SELECT DISTINCT oc.outcome_id, oc.outcome_sequence, oc.outcome_description, 
               o.output_sequence, o.output_description as output_desc, o.activity_id,
               ico.outcome_details as project_outcome_details,
               a.activity_code, a.activity_name, 'new_chain' as source_type, ic.id as chain_id
        FROM impact_chain_outcomes ico
        INNER JOIN outcomes oc ON ico.outcome_id = oc.outcome_id
        INNER JOIN outputs o ON oc.output_id = o.output_id
        INNER JOIN impact_chains ic ON ico.impact_chain_id = ic.id
        INNER JOIN activities a ON ic.activity_id = a.activity_id
        WHERE ic.project_id = ?
    ";

    $outcomes_stmt_new = mysqli_prepare($conn, $outcomes_query_new);
    mysqli_stmt_bind_param($outcomes_stmt_new, "i", $project_id);
    mysqli_stmt_execute($outcomes_stmt_new);
    $outcomes_result_new = mysqli_stmt_get_result($outcomes_stmt_new);
    while ($outcome = mysqli_fetch_assoc($outcomes_result_new)) {
        // ตรวจสอบไม่ให้ซ้ำ
        $found = false;
        foreach ($project_outcomes as $existing_outcome) {
            if ($existing_outcome['outcome_id'] == $outcome['outcome_id']) {
                $found = true;
                break;
            }
        }
        if (!$found) {
            $project_outcomes[] = $outcome;
        }
    }
    mysqli_stmt_close($outcomes_stmt_new);

    // ดึงผู้ใช้ประโยชน์จากทั้งสองตาราง และจับคู่กับกิจกรรม
    
    // จาก project_impact_ratios (Legacy system)
    $legacy_beneficiaries_query = "
        SELECT DISTINCT pir.beneficiary, pir.benefit_number, pir.benefit_detail, 
               NULL as activity_id, 'legacy' as source_type
        FROM project_impact_ratios pir
        WHERE pir.project_id = ? AND pir.beneficiary IS NOT NULL AND pir.beneficiary != ''
        ORDER BY pir.benefit_number ASC
    ";
    $legacy_stmt = mysqli_prepare($conn, $legacy_beneficiaries_query);
    mysqli_stmt_bind_param($legacy_stmt, "i", $project_id);
    mysqli_stmt_execute($legacy_stmt);
    $legacy_result = mysqli_stmt_get_result($legacy_stmt);
    while ($beneficiary = mysqli_fetch_assoc($legacy_result)) {
        $project_beneficiaries[] = $beneficiary;
    }
    mysqli_stmt_close($legacy_stmt);

    // จาก impact_chain_ratios (New chain system)
    $new_beneficiaries_query = "
        SELECT DISTINCT icr.beneficiary, icr.benefit_number, icr.benefit_detail,
               ic.activity_id, 'new_chain' as source_type
        FROM impact_chain_ratios icr
        INNER JOIN impact_chains ic ON icr.impact_chain_id = ic.id
        WHERE ic.project_id = ? AND icr.beneficiary IS NOT NULL AND icr.beneficiary != ''
        ORDER BY icr.benefit_number ASC
    ";
    $new_stmt = mysqli_prepare($conn, $new_beneficiaries_query);
    mysqli_stmt_bind_param($new_stmt, "i", $project_id);
    mysqli_stmt_execute($new_stmt);
    $new_result = mysqli_stmt_get_result($new_stmt);
    while ($beneficiary = mysqli_fetch_assoc($new_result)) {
        $project_beneficiaries[] = $beneficiary;
    }
    mysqli_stmt_close($new_stmt);
}



// ดึงรายการกิจกรรมทั้งหมดสำหรับ dropdown
$all_activities_query = "SELECT activity_id, activity_code, activity_name FROM activities ORDER BY activity_code";
$all_activities_result = mysqli_query($conn, $all_activities_query);

// ดึงรายการผลลัพธ์ทั้งหมดสำหรับ dropdown
$all_outcomes_query = "SELECT outcome_id, outcome_sequence, outcome_description FROM outcomes ORDER BY outcome_sequence";
$all_outcomes_result = mysqli_query($conn, $all_outcomes_query);

// จัดการการส่งฟอร์ม
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $project_id = intval($_POST['project_id']);
        $from_modal = isset($_POST['from_modal']) ? true : false;

        // รับข้อมูลจากแต่ละขั้นตอน
        $input_description = trim($_POST['input_description']);
        $impact_description = trim($_POST['impact_description']);

        // ตรวจสอบข้อมูลที่จำเป็น
        if (empty($project_id)) {
            throw new Exception("ไม่พบข้อมูลโครงการ");
        }

        mysqli_begin_transaction($conn);

        // บันทึกข้อมูล Social Impact Pathway
        // ตรวจสอบว่ามีข้อมูลจาก step4 session หรือไม่
        $step4_session_data = isset($_SESSION['step4_data']) && $_SESSION['step4_data']['project_id'] == $project_id
            ? $_SESSION['step4_data'] : null;

        $selected_outcome = $step4_session_data ? $step4_session_data['selected_outcome'] : null;
        $outcome_details = $step4_session_data ? $step4_session_data['outcome_details'] : '';
        $evaluation_year = $step4_session_data ? $step4_session_data['evaluation_year'] : '';
        $benefit_data_json = $step4_session_data ? $step4_session_data['benefit_data'] : '';

        // สร้าง pathway_sequence อัตโนมัติ
        $sequence_query = "SELECT IFNULL(MAX(CAST(pathway_sequence AS UNSIGNED)), 0) + 1 AS next_sequence FROM social_impact_pathway WHERE project_id = ?";
        $sequence_stmt = mysqli_prepare($conn, $sequence_query);
        mysqli_stmt_bind_param($sequence_stmt, "i", $project_id);
        mysqli_stmt_execute($sequence_stmt);
        $sequence_result = mysqli_stmt_get_result($sequence_stmt);
        $sequence_row = mysqli_fetch_assoc($sequence_result);
        $pathway_sequence = (string)$sequence_row['next_sequence'];
        mysqli_stmt_close($sequence_stmt);

        $query = "
            INSERT INTO social_impact_pathway (
                project_id, pathway_sequence, input_description, impact_description, 
                selected_outcome, outcome_details, evaluation_year, 
                benefit_data, created_by, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";

        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param(
            $stmt,
            "isssisssi",
            $project_id,
            $pathway_sequence,
            $input_description,
            $impact_description,
            $selected_outcome,
            $outcome_details,
            $evaluation_year,
            $benefit_data_json,
            $user_id
        );

        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . mysqli_error($conn));
        }

        mysqli_stmt_close($stmt);
        mysqli_commit($conn);

        // ลบข้อมูล step4 session หลังจากบันทึกสำเร็จ
        if (isset($_SESSION['step4_data'])) {
            unset($_SESSION['step4_data']);
        }

        $_SESSION['success_message'] = "บันทึกข้อมูล Social Impact Pathway เรียบร้อยแล้ว";

        // ถ้ามาจาก modal ใน step4 ให้กลับไปหน้า impact chain
        if ($from_modal) {
            header("Location: ../impact-chain/step4-outcome.php?project_id=" . $project_id);
        } else {
            // redirect กลับมาหน้าเดิมเพื่อแสดงข้อมูลที่บันทึกแล้ว
            header("Location: impact_pathway.php?project_id=" . $project_id . "&saved=1");
        }
        exit();
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="th">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>สร้าง Social Impact Pathway - SROI System</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary-color: #667eea;
            --secondary-color: #764ba2;
            --success-color: #56ab2f;
            --warning-color: #f093fb;
            --danger-color: #f5576c;
            --info-color: #4ecdc4;
            --light-bg: #f8f9fa;
            --white: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --border-color: #e0e0e0;
            --shadow-light: 0 2px 10px rgba(0, 0, 0, 0.1);
            --shadow-medium: 0 4px 20px rgba(0, 0, 0, 0.15);
            --shadow-heavy: 0 8px 30px rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            min-height: 100vh;
            color: var(--text-dark);
        }

        /* Navigation */
        .navbar {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 1rem 0;
            box-shadow: var(--shadow-light);
            position: sticky;
            top: 0;
            z-index: 100;
        }

        .nav-container {
            max-width: 1400px;
            margin: 0 auto;
            padding: 0 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.5rem;
            font-weight: bold;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }

        .nav-menu {
            display: flex;
            list-style: none;
            gap: 2rem;
            align-items: center;
        }

        .nav-link {
            color: var(--text-dark);
            text-decoration: none;
            font-weight: 500;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            transform: translateY(-2px);
        }

        .nav-link.active {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }

        /* Main Content */
        .main-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .page-header {
            text-align: center;
            margin-bottom: 3rem;
        }

        .page-title {
            font-size: 2.5rem;
            color: white;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
        }

        .page-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 300;
        }

        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-bottom: 1rem;
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.8);
            justify-content: center;
        }

        .breadcrumb a {
            color: rgba(255, 255, 255, 0.8);
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .breadcrumb a:hover {
            color: white;
        }

        /* Form Container */
        .form-container {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-heavy);
            border: 1px solid var(--border-color);
        }

        /* Pathway Display Table */
        .pathway-display-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 3rem;
            box-shadow: var(--shadow-medium);
            border-radius: 12px;
            overflow: hidden;
        }

        .pathway-display-table th {
            padding: 1rem;
            text-align: center;
            font-weight: bold;
            font-size: 1rem;
            border: 2px solid #333;
            vertical-align: middle;
        }

        .pathway-display-table td {
            padding: 1rem;
            border: 2px solid #333;
            height: 80px;
            vertical-align: top;
            font-size: 0.9rem;
        }

        /* Header Colors */
        .header-input {
            background-color: #e8f5e8;
        }


        .header-activities {
            background-color: #fff2cc;
        }

        .header-output {
            background-color: #e1f5fe;
        }

        .header-user {
            background-color: #fce4ec;
        }

        .header-outcome {
            background-color: #e8eaf6;
        }

        .header-impact {
            background-color: #e3f2fd;
        }

        /* Data cells */
        .pathway-display-table tbody td {
            background-color: #fafafa;
        }

        /* Activity items */
        .activity-item,
        .output-item,
        .outcome-item,
        .input-item,
        .user-item,
        .impact-item {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 0.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.85rem;
        }

        .activity-item:last-child,
        .output-item:last-child,
        .outcome-item:last-child,
        .input-item:last-child,
        .user-item:last-child,
        .impact-item:last-child {
            margin-bottom: 0;
        }

        .activity-code,
        .output-sequence,
        .outcome-sequence,
        .input-budget,
        .user-info,
        .impact-benefit {
            font-weight: bold;
            color: var(--primary-color);
        }

        .activity-name,
        .output-description,
        .outcome-description,
        .user-detail,
        .impact-detail {
            color: var(--text-dark);
            margin-top: 0.25rem;
        }

        .impact-ratio {
            font-size: 0.75rem;
            color: var(--success-color);
            font-weight: bold;
            margin-top: 0.25rem;
        }

        .form-title {
            font-size: 1.8rem;
            color: var(--text-dark);
            margin-bottom: 2rem;
            text-align: center;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            font-weight: bold;
        }

        /* Form Groups */
        .form-group {
            margin-bottom: 2rem;
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.5rem;
            align-items: start;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
            padding-top: 0.75rem;
        }

        .step-number {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.9rem;
            font-weight: bold;
        }

        .required {
            color: var(--danger-color);
        }

        .form-input,
        .form-select,
        .form-textarea {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 1rem;
            font-family: inherit;
            transition: all 0.3s ease;
            background: white;
        }

        .form-textarea {
            min-height: 100px;
            resize: vertical;
        }

        .form-input:focus,
        .form-select:focus,
        .form-textarea:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .form-help {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-top: 0.5rem;
        }

        /* Basic Info Section */
        .basic-info {
            margin-bottom: 2.5rem;
            padding: 1.5rem;
            background: var(--light-bg);
            border-radius: 12px;
        }

        .basic-info .form-group {
            margin-bottom: 1.5rem;
        }

        .basic-info .form-group:last-child {
            margin-bottom: 0;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }

        .alert-success {
            background: linear-gradient(45deg, rgba(86, 171, 47, 0.1), rgba(168, 230, 207, 0.1));
            color: var(--success-color);
            border: 1px solid var(--success-color);
        }

        .alert-error {
            background: linear-gradient(45deg, rgba(245, 87, 108, 0.1), rgba(240, 147, 251, 0.1));
            color: var(--danger-color);
            border: 1px solid var(--danger-color);
        }

        /* Buttons */
        .form-actions {
            display: flex;
            justify-content: center;
            gap: 1rem;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 2px solid var(--light-bg);
        }

        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-medium);
        }

        .btn-secondary {
            background: transparent;
            color: var(--text-muted);
            border: 2px solid var(--border-color);
        }

        .btn-secondary:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }

        .loading {
            display: none;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--text-muted);
        }

        .spinner {
            width: 20px;
            height: 20px;
            border: 2px solid var(--border-color);
            border-top: 2px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .main-container {
                padding: 1rem;
            }

            .form-container {
                padding: 1.5rem;
            }

            .form-group {
                grid-template-columns: 1fr;
                gap: 0.5rem;
            }

            .form-label {
                padding-top: 0;
                margin-bottom: 0.5rem;
            }

            .form-actions {
                flex-direction: column;
            }

            .page-title {
                font-size: 2rem;
            }

            .nav-container {
                flex-direction: column;
                gap: 1rem;
                padding: 0 1rem;
            }

            .nav-menu {
                flex-direction: column;
                gap: 0.5rem;
            }

            .pathway-display-table th,
            .pathway-display-table td {
                padding: 0.5rem;
                font-size: 0.8rem;
            }
        }
    </style>
</head>

<body>
    <!-- Navigation -->
    <?php
    $navbar_root = '../';
    include '../navbar.php';
    ?>

    <!-- Main Content -->
    <div class="main-container">
        <!-- Form Container -->
        <div class="form-container">
            <h2 class="form-title">ห่วงโซ่ผลกระทบทางสังคม</h2>

            <!-- Project Info Display -->
            <?php if ($selected_project): ?>
                <div class="alert alert-success">
                    <strong>โครงการที่เลือก:</strong> <?php echo htmlspecialchars($selected_project['project_code'] . ' - ' . $selected_project['name']); ?>
                </div>
            <?php endif; ?>

            <!-- Complete Project Data Display -->
            <div class="alert alert-success">
                <strong>📋 ข้อมูลรายละเอียดทั้งหมดที่บันทึกข้อมูลมาตั้งแต่ Step 1-4</strong>

                <!-- Step 1 Data -->
                <div class="mt-3">
                    <h6><span class="badge bg-primary">Step 1</span> ยุทธศาสตร์ที่เลือก (<?php echo count($project_strategies); ?> รายการ)</h6>
                    <?php if (!empty($project_strategies)): ?>
                        <div class="row">
                            <?php foreach ($project_strategies as $strategy): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="p-2 bg-light rounded">
                                        <strong><?php echo htmlspecialchars($strategy['strategy_code']); ?></strong>:
                                        <?php echo htmlspecialchars($strategy['strategy_name']); ?>
                                        <?php if (!empty($strategy['description'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($strategy['description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <small class="text-muted">ยังไม่มีการเลือกยุทธศาสตร์</small>
                    <?php endif; ?>
                </div>

                <!-- Step 2 Data -->
                <div class="mt-3">
                    <h6><span class="badge bg-warning">Step 2</span> กิจกรรมที่เลือก (<?php echo count($project_activities); ?> รายการ)</h6>
                    <?php if (!empty($project_activities)): ?>
                        <div class="row">
                            <?php foreach ($project_activities as $activity): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="p-2 bg-light rounded">
                                        <strong><?php echo htmlspecialchars($activity['activity_code']); ?></strong>:
                                        <?php echo htmlspecialchars($activity['activity_name']); ?>
                                        <?php if (!empty($activity['activity_description'])): ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($activity['activity_description']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <small class="text-muted">ยังไม่มีการเลือกกิจกรรม</small>
                    <?php endif; ?>
                </div>

                <!-- Step 3 Data -->
                <div class="mt-3">
                    <h6><span class="badge bg-info">Step 3</span> ผลผลิตที่เลือก (<?php echo count($project_outputs); ?> รายการ)</h6>
                    <?php if (!empty($project_outputs)): ?>
                        <div class="row">
                            <?php foreach ($project_outputs as $output): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="p-2 bg-light rounded">
                                        <strong><?php echo htmlspecialchars($output['output_sequence']); ?></strong>:
                                        <?php echo htmlspecialchars($output['output_description']); ?>
                                        <?php if (!empty($output['project_output_details'])): ?>
                                            <br><strong class="text-primary">รายละเอียดเพิ่มเติม:</strong>
                                            <small><?php echo htmlspecialchars($output['project_output_details']); ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <small class="text-muted">ยังไม่มีการเลือกผลผลิต</small>
                    <?php endif; ?>
                </div>

                <!-- Step 4 Data -->
                <div class="mt-3">
                    <h6><span class="badge bg-success">Step 4</span> ผลลัพธ์ที่เลือก (<?php echo count($project_outcomes); ?> รายการ)</h6>
                    <?php if (!empty($project_outcomes)): ?>
                        <div class="row">
                            <?php foreach ($project_outcomes as $outcome): ?>
                                <div class="col-md-6 mb-2">
                                    <div class="p-2 bg-light rounded">
                                        <strong><?php echo htmlspecialchars($outcome['outcome_sequence']); ?></strong>:
                                        <?php echo htmlspecialchars($outcome['outcome_description']); ?>
                                        <?php if (!empty($outcome['project_outcome_details'])): ?>
                                            <br><strong class="text-success">รายละเอียดเพิ่มเติม:</strong>
                                            <small><?php echo htmlspecialchars($outcome['project_outcome_details']); ?></small>
                                        <?php endif; ?>
                                        <br><small class="text-muted">จากผลผลิต: <?php echo htmlspecialchars($outcome['output_sequence']); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <small class="text-muted">ยังไม่มีผลลัพธ์ที่บันทึกไว้</small>
                    <?php endif; ?>
                </div>

                <!-- Session Step 4 Data (if available) -->
                <?php if ($step4_info): ?>
                    <div class="mt-3 p-3 border rounded" style="background: rgba(255,255,0,0.1);">
                        <h6><span class="badge bg-danger">Session Data</span> ข้อมูลจากขั้นตอนที่ 4 (รอการบันทึก)</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <strong>ผลลัพธ์ที่เลือก:</strong> ID <?php echo htmlspecialchars($step4_info['selected_outcome']); ?><br>
                                <strong>ปีที่ประเมิน:</strong> <?php echo htmlspecialchars($step4_info['evaluation_year']); ?>
                            </div>
                            <div class="col-md-6">
                                <strong>รายละเอียดผลลัพธ์:</strong><br>
                                <small><?php echo htmlspecialchars($step4_info['outcome_details']); ?></small>
                            </div>
                        </div>
                        <?php if (!empty($step4_info['benefit_data'])): ?>
                            <div class="mt-2">
                                <strong>ข้อมูลผลประโยชน์และสัดส่วนผลกระทบ:</strong> <?php echo count($step4_info['benefit_data']); ?> รายการ<br>
                                <div class="table-responsive mt-2">
                                    <table class="table table-sm table-bordered">
                                        <thead class="table-light">
                                            <tr>
                                                <th width="25%">ผลประโยชน์</th>
                                                <th width="30%">ผู้ใช้ประโยชน์</th>
                                                <th width="15%">จำนวนเงิน (บาท)</th>
                                                <th width="10%">Attribution (%)</th>
                                                <th width="10%">Deadweight (%)</th>
                                                <th width="10%">Displacement (%)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($step4_info['benefit_data'] as $index => $benefit): ?>
                                                <tr>
                                                    <td><small><strong>ผลประโยชน์ <?php echo ($index + 1); ?></strong></small></td>
                                                    <td><small><?php echo htmlspecialchars($benefit['beneficiary'] ?? 'ไม่ระบุ'); ?></small></td>
                                                    <td><small class="text-end"><?php 
                                                        if (isset($benefit['benefit_note']) && is_numeric($benefit['benefit_note'])) {
                                                            echo number_format((float)$benefit['benefit_note']);
                                                        } else {
                                                            echo 'ไม่ระบุ';
                                                        }
                                                    ?></small></td>
                                                    <td><small class="text-center"><?php echo $benefit['attribution'] ?? '0'; ?></small></td>
                                                    <td><small class="text-center"><?php echo $benefit['deadweight'] ?? '0'; ?></small></td>
                                                    <td><small class="text-center"><?php echo $benefit['displacement'] ?? '0'; ?></small></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>


            <!-- Pathway Display Table -->
            <table class="pathway-display-table">
                <thead>
                    <tr>
                        <th class="header-input">ปัจจัยนำเข้า<br><small>Input</small></th>
                        <th class="header-activities">กิจกรรม<br><small>Activities</small></th>
                        <th class="header-output">ผลผลิต<br><small>Output</small></th>
                        <th class="header-user">ผู้ใช้ประโยชน์<br><small>User</small></th>
                        <th class="header-outcome">ผลลัพธ์<br><small>Outcome</small></th>
                        <th class="header-impact">ผลกระทบ<br><small>Impact</small></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($project_activities)): ?>
                        <?php foreach ($project_activities as $activity_index => $activity): ?>
                            <tr>
                                <!-- ปัจจัยนำเข้า - แสดงเฉพาะแถวแรก -->
                                <?php if ($activity_index == 0): ?>
                                    <td rowspan="<?php echo count($project_activities); ?>">
                                        <?php if (!empty($existing_pathways)): ?>
                                            <?php foreach ($existing_pathways as $pathway): ?>
                                                <div class="input-item mb-2">
                                                    <div class="input-description">
                                                        <?php echo htmlspecialchars($pathway['input_description'] ?: 'ไม่ได้ระบุ'); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <small class="text-muted">ยังไม่มีข้อมูลปัจจัยนำเข้า</small>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>

                                <!-- กิจกรรม - แสดงแต่ละกิจกรรมในแถวต่างกัน -->
                                <td>
                                    <div class="activity-item">
                                        <div class="activity-code"><?php echo htmlspecialchars($activity['activity_code']); ?></div>
                                        <div class="activity-name"><?php echo htmlspecialchars($activity['activity_name']); ?></div>
                                        <?php if (!empty($activity['activity_description'])): ?>
                                            <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                                <?php echo htmlspecialchars($activity['activity_description']); ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (isset($activity['source_type'])): ?>
                                            <div style="font-size: 0.7rem; color: #007bff; margin-top: 0.25rem;">
                                                <i class="fas fa-info-circle"></i>
                                                <?php echo $activity['source_type'] == 'legacy' ? 'ระบบเดิม' : 'Impact Chain'; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>

                                <!-- ผลผลิต - ดึงผลผลิตที่เกี่ยวข้องกับกิจกรรมนี้ -->
                                <td>
                                    <?php
                                    // ค้นหาผลผลิตที่เกี่ยวข้องกับกิจกรรมนี้โดยตรง
                                    $activity_outputs = [];
                                    foreach ($project_outputs as $output) {
                                        // ตรวจสอบว่าผลผลิตนี้มาจากกิจกรรมที่กำลังแสดง
                                        if ($output['activity_id'] == $activity['activity_id']) {
                                            $activity_outputs[] = $output;
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($activity_outputs)): ?>
                                        <?php foreach ($activity_outputs as $output): ?>
                                            <div class="output-item">
                                                <div class="output-sequence"><?php echo htmlspecialchars($output['output_sequence']); ?></div>
                                                <div class="output-description">
                                                    <?php echo htmlspecialchars(
                                                        !empty($output['project_output_details'])
                                                            ? $output['project_output_details']
                                                            : $output['output_description']
                                                    ); ?>
                                                </div>
                                                <?php if (isset($output['source_type'])): ?>
                                                    <div style="font-size: 0.7rem; color: #28a745; margin-top: 0.25rem;">
                                                        <i class="fas fa-link"></i>
                                                        <?php echo $output['source_type'] == 'legacy' ? 'ระบบเดิม' : 'Impact Chain'; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small class="text-muted">ไม่มีผลผลิตสำหรับกิจกรรม <?php echo htmlspecialchars($activity['activity_code']); ?></small>
                                    <?php endif; ?>
                                </td>

                                <!-- ผู้ใช้ประโยชน์ - แสดงตามกิจกรรม -->
                                <td>
                                    <?php
                                    // แสดงผู้ใช้ประโยชน์แยกตามแต่ละกิจกรรม
                                    $activity_beneficiaries = [];
                                    
                                    if ($activity['source_type'] == 'legacy') {
                                        // กิจกรรมระบบเดิม: แสดงเฉพาะข้อมูลจาก project_impact_ratios
                                        foreach ($project_beneficiaries as $beneficiary) {
                                            if ($beneficiary['source_type'] == 'legacy') {
                                                $activity_beneficiaries[] = $beneficiary;
                                            }
                                        }
                                    } else if ($activity['source_type'] == 'new_chain') {
                                        // กิจกรรม Impact Chain: แสดงเฉพาะข้อมูลจาก impact_chain_ratios ที่ตรงกับ activity_id
                                        foreach ($project_beneficiaries as $beneficiary) {
                                            if ($beneficiary['source_type'] == 'new_chain' && $beneficiary['activity_id'] == $activity['activity_id']) {
                                                $activity_beneficiaries[] = $beneficiary;
                                            }
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($activity_beneficiaries)): ?>
                                        <?php foreach ($activity_beneficiaries as $beneficiary): ?>
                                            <div class="user-item">
                                                <div class="user-info">ผลประโยชน์ <?php echo htmlspecialchars($beneficiary['benefit_number']); ?></div>
                                                <div class="user-detail"><?php echo htmlspecialchars($beneficiary['beneficiary']); ?></div>
                                                <?php if (!empty($beneficiary['benefit_detail'])): ?>
                                                    <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                                        รายละเอียด: <?php echo htmlspecialchars($beneficiary['benefit_detail']); ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if (isset($beneficiary['source_type'])): ?>
                                                    <div style="font-size: 0.7rem; color: #dc3545; margin-top: 0.25rem;">
                                                        <i class="fas fa-users"></i>
                                                        <?php echo $beneficiary['source_type'] == 'legacy' ? 'ระบบเดิม' : 'Impact Chain'; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small class="text-muted">ไม่มีผู้ใช้ประโยชน์สำหรับกิจกรรม <?php echo htmlspecialchars($activity['activity_code']); ?></small>
                                    <?php endif; ?>
                                </td>

                                <!-- ผลลัพธ์ - ดึงผลลัพธ์ที่เกี่ยวข้องกับกิจกรรมนี้ -->
                                <td>
                                    <?php
                                    // ค้นหาผลลัพธ์ที่เกี่ยวข้องกับกิจกรรมนี้โดยตรง
                                    $activity_outcomes = [];
                                    foreach ($project_outcomes as $outcome) {
                                        // ตรวจสอบว่าผลลัพธ์นี้มาจากกิจกรรมที่กำลังแสดง
                                        if ($outcome['activity_id'] == $activity['activity_id']) {
                                            $activity_outcomes[] = $outcome;
                                        }
                                    }
                                    ?>
                                    <?php if (!empty($activity_outcomes)): ?>
                                        <?php foreach ($activity_outcomes as $outcome): ?>
                                            <div class="outcome-item">
                                                <div class="outcome-sequence"><?php echo htmlspecialchars($outcome['outcome_sequence']); ?></div>
                                                <div class="outcome-description">
                                                    <?php
                                                    // ใช้ข้อมูลจาก project_outcome_details เท่านั้น
                                                    $display_text = $outcome['project_outcome_details'];
                                                    echo htmlspecialchars($display_text);
                                                    ?>
                                                </div>
                                                <div style="font-size: 0.75rem; color: #6c757d; margin-top: 0.25rem;">
                                                    จากผลผลิต: <?php echo htmlspecialchars($outcome['output_sequence']); ?>
                                                    <?php if (isset($outcome['source_type'])): ?>
                                                        | <span style="color: #17a2b8;">
                                                            <?php echo $outcome['source_type'] == 'legacy' ? 'ระบบเดิม' : 'Impact Chain'; ?>
                                                        </span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <small class="text-muted">ไม่มีผลลัพธ์สำหรับกิจกรรม <?php echo htmlspecialchars($activity['activity_code']); ?></small>
                                    <?php endif; ?>
                                </td>

                                <!-- ผลกระทบ - แสดงเฉพาะแถวแรก -->
                                <?php if ($activity_index == 0): ?>
                                    <td rowspan="<?php echo count($project_activities); ?>">
                                        <?php if (!empty($existing_pathways)): ?>
                                            <?php foreach ($existing_pathways as $pathway): ?>
                                                <div class="impact-item mb-2">
                                                    <div class="impact-description">
                                                        <?php echo htmlspecialchars($pathway['impact_description'] ?: 'ไม่ได้ระบุ'); ?>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <small class="text-muted">ยังไม่มีข้อมูลผลกระทบ</small>
                                        <?php endif; ?>
                                    </td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                ยังไม่มีการเลือกกิจกรรมสำหรับโครงการนี้
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <!-- Existing Pathways -->
            <?php if (!empty($existing_pathways)): ?>
                <div class="alert alert-success">
                    <strong>📋 มีข้อมูล Impact Pathway แล้ว <?php echo count($existing_pathways); ?> รายการ</strong>
                    <div style="margin-top: 1rem;">
                        <?php foreach ($existing_pathways as $index => $pathway): ?>
                            <div class="mb-3 p-3 border rounded" style="background: rgba(255,255,255,0.7);">
                                <h6 class="mb-2"><strong>รายการที่ <?php echo ($index + 1); ?></strong>
                                    <small class="text-muted">(<?php echo date('d/m/Y H:i', strtotime($pathway['created_at'])); ?>)</small>
                                </h6>

                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>📋 ปัจจัยนำเข้า:</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($pathway['input_description'] ?: 'ไม่ได้ระบุ'); ?></span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>💥 ผลกระทบ:</strong><br>
                                        <span class="text-muted"><?php echo htmlspecialchars($pathway['impact_description'] ?: 'ไม่ได้ระบุ'); ?></span>
                                    </div>
                                </div>

                                <?php if ($pathway['selected_outcome'] && $pathway['outcome_details']): ?>
                                    <div class="mt-2 pt-2" style="border-top: 1px solid #eee;">
                                        <small>
                                            <strong>🎯 ผลลัพธ์:</strong> ID <?php echo htmlspecialchars($pathway['selected_outcome']); ?> |
                                            <strong>ปี:</strong> <?php echo htmlspecialchars($pathway['evaluation_year']); ?> |
                                            <strong>สัดส่วนผลกระทบ:</strong> <?php
                                                                                $benefit_data = json_decode($pathway['benefit_data'], true);
                                                                                echo $benefit_data ? count($benefit_data) : 0;
                                                                                ?> รายการ
                                        </small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Alert Messages -->
            <?php if ($message): ?>
                <div class="alert alert-success">
                    ✅ <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    ❌ <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" id="createPathwayForm">
                <!-- Hidden project_id field -->
                <input type="hidden" name="project_id" value="<?php echo $project_id; ?>">

                <!-- Pathway Steps -->
                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">1</span>
                        ปัจจัยนำเข้า (Input)
                    </label>
                    <div>
                        <textarea class="form-textarea" name="input_description" rows="4"
                            placeholder="" required><?php echo htmlspecialchars($_POST['input_description'] ?? ''); ?></textarea>
                        <div class="form-help">ระบุทรัพยากรและปัจจัยนำเข้าที่ใช้ในโครงการ</div>
                    </div>
                </div>


                <div class="form-group">
                    <label class="form-label">
                        <span class="step-number">2</span>
                        ผลกระทบ (Impact)
                    </label>
                    <div>
                        <textarea class="form-textarea" name="impact_description" rows="4"
                            placeholder="" required><?php echo htmlspecialchars($_POST['impact_description'] ?? ''); ?></textarea>
                        <div class="form-help">ผลกระทบด้านสังคม/เศรษฐกิจ/สังคมและสิ่งแวดล้อม</div>
                    </div>
                </div>

                <!-- Form Actions -->
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="goBack()">
                        ← ยกเลิก
                    </button>

                    <div class="loading" id="loadingSpinner">
                        <div class="spinner"></div>
                        <span>กำลังบันทึกข้อมูล...</span>
                    </div>

                    <button type="submit" class="btn btn-success" id="submitBtn">
                        💾 บันทึกข้อมูล
                    </button>
                    <?php if (!empty($existing_pathways)): ?>
                        <button type="button" class="btn btn-primary" onclick="goToNext()">
                            ถัดไป →
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('createPathwayForm');
            const submitBtn = document.getElementById('submitBtn');
            const loading = document.getElementById('loadingSpinner');

            // Handle form submission
            form.addEventListener('submit', function(e) {
                if (!validateForm()) {
                    e.preventDefault();
                    return;
                }

                // Show loading state
                submitBtn.disabled = true;
                loading.style.display = 'flex';
                submitBtn.style.display = 'none';
            });

            function validateForm() {
                const projectId = document.querySelector('input[name="project_id"]').value;

                if (!projectId) {
                    alert('ไม่พบข้อมูลโครงการ');
                    return false;
                }

                return true;
            }
        });


        function goBack() {
            if (confirm('คุณต้องการยกเลิกการสร้าง Social Impact Pathway หรือไม่? ข้อมูลที่กรอกจะไม่ถูกบันทึก')) {
                window.location.href = '../dashboard.php';
            }
        }

        function goToNext() {
            window.location.href = 'cost.php?project_id=<?php echo $project_id; ?>';
        }

        // แสดงข้อความเมื่อบันทึกสำเร็จ
        <?php if (isset($_GET['saved']) && $_GET['saved'] == '1'): ?>
            setTimeout(function() {
                alert('บันทึกข้อมูล Impact Pathway สำเร็จแล้ว! ตอนนี้สามารถกดปุ่ม "ถัดไป" เพื่อไปขั้นตอนต่อไปได้');
            }, 500);
        <?php endif; ?>

        console.log('🔗 Enhanced Social Impact Pathway Form with Activities and Outputs data loaded successfully!');
    </script>
</body>

</html>