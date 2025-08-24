<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/tmp/process_basecase.log');

session_start();
require_once '../config.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    http_response_code(401);
    echo "Unauthorized";
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    http_response_code(500);
    echo "Database connection failed";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $chain_id = isset($_POST['chain_id']) ? (int)$_POST['chain_id'] : 0;
    $evaluation_year = isset($_POST['evaluation_year']) ? trim($_POST['evaluation_year']) : '';
    $user_id = $_SESSION['user_id'];
    
    // Debug logging
    file_put_contents('/tmp/process_basecase.log', date('Y-m-d H:i:s') . " === process-basecase.php START ===\n", FILE_APPEND);
    file_put_contents('/tmp/process_basecase.log', date('Y-m-d H:i:s') . " project_id=$project_id, chain_id=$chain_id, evaluation_year=$evaluation_year\n", FILE_APPEND);
    file_put_contents('/tmp/process_basecase.log', date('Y-m-d H:i:s') . " POST data: " . print_r($_POST, true) . "\n", FILE_APPEND);
    file_put_contents('/tmp/process_basecase.log', date('Y-m-d H:i:s') . " REQUEST_METHOD=" . $_SERVER["REQUEST_METHOD"] . "\n", FILE_APPEND);

    if ($project_id == 0) {
        http_response_code(400);
        echo "Invalid project ID";
        exit;
    }

    // ตรวจสอบสิทธิ์เข้าถึงโครงการ
    $check_query = "SELECT id FROM projects WHERE id = ? AND created_by = ?";
    $check_stmt = mysqli_prepare($conn, $check_query);
    mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
    mysqli_stmt_execute($check_stmt);
    $result = mysqli_stmt_get_result($check_stmt);

    if (mysqli_num_rows($result) == 0) {
        mysqli_stmt_close($check_stmt);
        http_response_code(403);
        echo "Access denied";
        exit;
    }
    mysqli_stmt_close($check_stmt);

    // ตรวจสอบว่าเป็นระบบเดิม (legacy) หรือระบบใหม่ (new chain)
    $is_legacy_system = true;
    if ($chain_id && $chain_id > 0) {
        // ตรวจสอบว่า chain_id นี้มีอยู่ในระบบใหม่หรือไม่
        $check_new_chain = "SELECT id FROM impact_chains WHERE id = ?";
        $check_stmt = mysqli_prepare($conn, $check_new_chain);
        if ($check_stmt) {
            mysqli_stmt_bind_param($check_stmt, 'i', $chain_id);
            mysqli_stmt_execute($check_stmt);
            $check_result = mysqli_stmt_get_result($check_stmt);
            $is_legacy_system = (mysqli_num_rows($check_result) == 0);
            mysqli_stmt_close($check_stmt);
            file_put_contents('/tmp/process_basecase.log', date('Y-m-d H:i:s') . " chain_id=$chain_id exists in impact_chains: " . (mysqli_num_rows($check_result) > 0 ? 'YES' : 'NO') . "\n", FILE_APPEND);
            file_put_contents('/tmp/process_basecase.log', date('Y-m-d H:i:s') . " is_legacy_system=" . ($is_legacy_system ? 'true' : 'false') . "\n", FILE_APPEND);
        }
    } else {
        file_put_contents('/tmp/process_basecase.log', date('Y-m-d H:i:s') . " No chain_id provided or chain_id=0, using legacy system\n", FILE_APPEND);
    }

    // เตรียมข้อมูลสำหรับบันทึก
    $impact_data = array();

    // วนลูปเพื่อเก็บข้อมูลทุกรายการที่มีการส่งมา
    for ($i = 1; $i <= 20; $i++) { // เพิ่มจำนวนรายการสูงสุดเป็น 20
        if (isset($_POST["attribution_$i"]) || isset($_POST["benefit_detail_$i"])) {
            $attribution = isset($_POST["attribution_$i"]) ? (float)$_POST["attribution_$i"] : 0;
            $deadweight = isset($_POST["deadweight_$i"]) ? (float)$_POST["deadweight_$i"] : 0;
            $displacement = isset($_POST["displacement_$i"]) ? (float)$_POST["displacement_$i"] : 0;
            $benefit_detail = isset($_POST["benefit_detail_$i"]) ? trim($_POST["benefit_detail_$i"]) : '';
            $beneficiary = isset($_POST["beneficiary_$i"]) ? trim($_POST["beneficiary_$i"]) : '';
            $benefit_note = isset($_POST["benefit_note_$i"]) ? trim($_POST["benefit_note_$i"]) : '';

            // คำนวณสัดส่วนผลกระทบ
            $impact_ratio = 1 - ($attribution + $deadweight + $displacement) / 100;
            $impact_ratio = max(0, $impact_ratio); // ไม่ให้ต่ำกว่า 0

            // บันทึกเฉพาะรายการที่มีข้อมูล
            if ($attribution > 0 || $deadweight > 0 || $displacement > 0 || !empty($benefit_detail) || !empty($beneficiary) || !empty($benefit_note)) {
                $impact_data[] = array(
                    'benefit_number' => $i,
                    'attribution' => $attribution,
                    'deadweight' => $deadweight,
                    'displacement' => $displacement,
                    'impact_ratio' => $impact_ratio,
                    'benefit_detail' => $benefit_detail,
                    'beneficiary' => $beneficiary,
                    'benefit_note' => $benefit_note
                );
            }
        }
    }

    if (!$is_legacy_system && $chain_id) {
        // New Chain - ใช้ตาราง impact_chain_ratios
        
        // ลบข้อมูลเก่า (ถ้ามี)
        $delete_query = "DELETE FROM impact_chain_ratios WHERE impact_chain_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'i', $chain_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        // เตรียม SQL สำหรับ insert (outcome_id = NULL สำหรับ basecase data)
        $insert_query = "INSERT INTO impact_chain_ratios (impact_chain_id, outcome_id, deadweight, attribution, displacement, drop_off, created_by, benefit_number, benefit_detail, beneficiary, benefit_note, year, impact_ratio) VALUES (?, NULL, ?, ?, ?, 0.00, ?, ?, ?, ?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);

        $success_count = 0;
        foreach ($impact_data as $data) {
            mysqli_stmt_bind_param(
                $insert_stmt,
                'idddsissssd',
                $chain_id,
                $data['deadweight'],
                $data['attribution'],
                $data['displacement'],
                $user_id,
                $data['benefit_number'],
                $data['benefit_detail'],
                $data['beneficiary'],
                $data['benefit_note'],
                $evaluation_year,
                $data['impact_ratio']
            );

            if (mysqli_stmt_execute($insert_stmt)) {
                $success_count++;
            }
        }

        mysqli_stmt_close($insert_stmt);
        
    } else {
        // Legacy - ใช้ตาราง project_impact_ratios
        
        // ลบข้อมูลเก่า (ถ้ามี)
        $delete_query = "DELETE FROM project_impact_ratios WHERE project_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        // เตรียม SQL สำหรับ insert (รวม year และ beneficiary)
        $insert_query = "INSERT INTO project_impact_ratios (project_id, benefit_number, attribution, deadweight, displacement, impact_ratio, benefit_detail, beneficiary, benefit_note, year, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $insert_stmt = mysqli_prepare($conn, $insert_query);

        $success_count = 0;
        foreach ($impact_data as $data) {
            mysqli_stmt_bind_param(
                $insert_stmt,
                'iiddddssss',
                $project_id,
                $data['benefit_number'],
                $data['attribution'],
                $data['deadweight'],
                $data['displacement'],
                $data['impact_ratio'],
                $data['benefit_detail'],
                $data['beneficiary'],
                $data['benefit_note'],
                $evaluation_year
            );

            if (mysqli_stmt_execute($insert_stmt)) {
                $success_count++;
            }
        }

        mysqli_stmt_close($insert_stmt);
    }

    if ($success_count > 0) {
        echo "Success: Saved $success_count impact ratio records";
    } else {
        http_response_code(500);
        echo "Error: Failed to save impact ratio data";
    }
} else {
    http_response_code(405);
    echo "Method not allowed";
}

mysqli_close($conn);
