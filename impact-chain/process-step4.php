<?php
session_start();
require_once '../config.php';
require_once '../includes/impact_chain_status.php';
require_once '../includes/impact_chain_manager.php';

// ตรวจสอบการ login
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true) {
    header("location: ../login.php");
    exit;
}

// ตรวจสอบการเชื่อมต่อฐานข้อมูล
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// ตรวจสอบ POST data
if ($_SERVER["REQUEST_METHOD"] != "POST") {
    header("location: ../project-list.php");
    exit;
}

// ตรวจสอบว่าเป็น action save_to_session หรือไม่
if (isset($_POST['action']) && $_POST['action'] == 'save_to_session') {
    // เก็บข้อมูลลง session แล้ว redirect
    $project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
    $selected_outcome = isset($_POST['selected_outcome']) ? (int)$_POST['selected_outcome'] : 0;
    $outcome_details = isset($_POST['outcome_details']) ? trim($_POST['outcome_details']) : '';
    $evaluation_year = isset($_POST['evaluation_year']) ? trim($_POST['evaluation_year']) : '';
    $benefit_data = isset($_POST['benefit_data']) ? $_POST['benefit_data'] : '';
    
    // เก็บข้อมูลใน session
    $_SESSION['step4_data'] = [
        'project_id' => $project_id,
        'selected_outcome' => $selected_outcome,
        'outcome_details' => $outcome_details,
        'evaluation_year' => $evaluation_year,
        'benefit_data' => $benefit_data,
        'timestamp' => time()
    ];
    
    // ส่ง JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'ข้อมูลถูกเก็บใน session แล้ว',
        'data' => $_SESSION['step4_data']
    ]);
    exit;
}

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;
$selected_outcome = isset($_POST['selected_outcome']) ? (int)$_POST['selected_outcome'] : 0;
$outcome_details = isset($_POST['outcome_details']) ? trim($_POST['outcome_details']) : '';

// รับค่าปีที่ต้องการประเมิน (จาก radio button)
$evaluation_year = isset($_POST['evaluation_year']) ? trim($_POST['evaluation_year']) : '';
$chain_id = isset($_POST['chain_id']) ? (int)$_POST['chain_id'] : (isset($_GET['chain_id']) ? (int)$_GET['chain_id'] : null);

// ตรวจสอบว่าเป็นการบันทึกรายละเอียดเท่านั้นหรือไม่
$save_details_only = isset($_POST['save_details_only']) && $_POST['save_details_only'] == '1';

if ($project_id == 0) {
    $_SESSION['error_message'] = "ไม่พบข้อมูลโครงการ";
    header("location: ../project-list.php");
    exit;
}

// ตรวจสอบว่าได้เลือกปีที่ต้องการประเมินหรือไม่
if (empty($evaluation_year)) {
    $_SESSION['error_message'] = "กรุณาเลือกปีที่ต้องการประเมิน";
    header("location: step4-outcome.php?project_id=" . $project_id);
    exit;
}

// ตรวจสอบสิทธิ์เข้าถึงโครงการ
$user_id = $_SESSION['user_id'];
$check_query = "SELECT * FROM projects WHERE id = ? AND created_by = ?";
$check_stmt = mysqli_prepare($conn, $check_query);
mysqli_stmt_bind_param($check_stmt, 'ii', $project_id, $user_id);
mysqli_stmt_execute($check_stmt);
$project_result = mysqli_stmt_get_result($check_stmt);
$project = mysqli_fetch_assoc($project_result);
mysqli_stmt_close($check_stmt);

if (!$project) {
    $_SESSION['error_message'] = "คุณไม่มีสิทธิ์เข้าถึงโครงการนี้";
    header("location: ../project-list.php");
    exit;
}

if ($selected_outcome == 0) {
    // อัปเดตสถานะ Impact Chain - Step 4 เสร็จสิ้น
    updateMultipleImpactChainStatus($project_id, $chain_id, 4, true);
    
    // ไปยังหน้า completion แม้ไม่เลือกผลลัพธ์
    $_SESSION['completed_impact_chain'] = [
        'outcome_id' => 0,
        'outcome_details' => 'ไม่ได้เลือกผลลัพธ์',
        'evaluation_year' => '',
        'completed_at' => date('Y-m-d H:i:s')
    ];
    header("location: step4-completion.php?project_id=" . $project_id);
    exit;
}

// ตรวจสอบข้อมูลรายละเอียดเพิ่มเติม
if (empty($outcome_details)) {
    $_SESSION['error_message'] = "กรุณาระบุรายละเอียดเพิ่มเติมเกี่ยวกับผลลัพธ์";
    header("location: step4-outcome.php?project_id=" . $project_id);
    exit;
}

// บันทึกข้อมูลการเลือกผลลัพธ์และปีที่ประเมินลงฐานข้อมูล
try {
    // ตรวจสอบว่าโครงการนี้ได้เลือกผลผลิตแล้วหรือไม่
    $check_output_query = "SELECT output_id FROM project_outputs WHERE project_id = ?";
    $check_output_stmt = mysqli_prepare($conn, $check_output_query);
    mysqli_stmt_bind_param($check_output_stmt, 'i', $project_id);
    mysqli_stmt_execute($check_output_stmt);
    $output_result = mysqli_stmt_get_result($check_output_stmt);

    if (mysqli_num_rows($output_result) == 0) {
        $_SESSION['error_message'] = "กรุณาเลือกผลผลิตก่อน";
        header("location: step3-output.php?project_id=" . $project_id);
        exit;
    }

    // ดึง output_id ที่เลือก
    $output_row = mysqli_fetch_assoc($output_result);
    $selected_output_id = $output_row['output_id'];
    mysqli_stmt_close($check_output_stmt);

    // ตรวจสอบว่า outcome_id ที่เลือกมีจริงในฐานข้อมูลและเกี่ยวข้องกับผลผลิตที่เลือก
    $verify_query = "SELECT oc.outcome_id, oc.outcome_description, oc.outcome_sequence, 
                            o.output_description, a.activity_name, s.strategy_name
                     FROM outcomes oc 
                     JOIN outputs o ON oc.output_id = o.output_id 
                     JOIN activities a ON o.activity_id = a.activity_id 
                     JOIN strategies s ON a.strategy_id = s.strategy_id
                     WHERE oc.outcome_id = ? AND oc.output_id = ?";
    $verify_stmt = mysqli_prepare($conn, $verify_query);
    mysqli_stmt_bind_param($verify_stmt, 'ii', $selected_outcome, $selected_output_id);
    mysqli_stmt_execute($verify_stmt);
    $verify_result = mysqli_stmt_get_result($verify_stmt);

    if (!($outcome = mysqli_fetch_assoc($verify_result))) {
        $_SESSION['error_message'] = "ไม่พบผลลัพธ์ที่เลือกหรือผลลัพธ์ไม่สอดคล้องกับผลผลิต";
        header("location: step4-outcome.php?project_id=" . $project_id);
        exit;
    }
    mysqli_stmt_close($verify_stmt);

    if ($chain_id) {
        // Impact Chain ใหม่ - ใช้ตารางใหม่
        $result = addOutcomeToChain($chain_id, $selected_outcome, $outcome_details, $evaluation_year, $benefit_data, $user_id);
        
        if (!$result) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกผลลัพธ์: " . $outcome['outcome_description']);
        }
    } else {
        // Impact Chain เดิม - ใช้ตารางเดิม
        // ลบข้อมูลการเลือกผลลัพธ์เดิม (ถ้ามี)
        $delete_query = "DELETE FROM project_outcomes WHERE project_id = ?";
        $delete_stmt = mysqli_prepare($conn, $delete_query);
        mysqli_stmt_bind_param($delete_stmt, 'i', $project_id);
        mysqli_stmt_execute($delete_stmt);
        mysqli_stmt_close($delete_stmt);

        // บันทึกการเลือกผลลัพธ์ใหม่
        $insert_query = "INSERT INTO project_outcomes (project_id, outcome_id, outcome_details, created_by) VALUES (?, ?, ?, ?)";
        $insert_stmt = mysqli_prepare($conn, $insert_query);

        mysqli_stmt_bind_param($insert_stmt, 'iisi', $project_id, $selected_outcome, $outcome_details, $user_id);
        if (!mysqli_stmt_execute($insert_stmt)) {
            throw new Exception("เกิดข้อผิดพลาดในการบันทึกผลลัพธ์: " . $outcome['outcome_description']);
        }
        mysqli_stmt_close($insert_stmt);
    }

    // บันทึกปีที่ต้องการประเมินในขั้นตอนบันทึกข้อมูลสัดส่วนผลกระทบเท่านั้น
    // (ไม่บันทึกตอนบันทึกรายละเอียดเพิ่มเติม)
    if (!$save_details_only) {
        // ตรวจสอบว่ามีข้อมูลปีนี้อยู่แล้วหรือไม่
        $check_year_query = "SELECT id FROM project_impact_ratios WHERE project_id = ? AND year = ?";
        $check_year_stmt = mysqli_prepare($conn, $check_year_query);
        mysqli_stmt_bind_param($check_year_stmt, 'is', $project_id, $evaluation_year);
        mysqli_stmt_execute($check_year_stmt);
        $check_year_result = mysqli_stmt_get_result($check_year_stmt);
        
        if (mysqli_num_rows($check_year_result) > 0) {
            // มีข้อมูลแล้ว - อัปเดตเฉพาะปี (เก็บค่า attribution, deadweight, displacement ไว้)
            $update_year_query = "UPDATE project_impact_ratios SET year = ? WHERE project_id = ?";
            $update_year_stmt = mysqli_prepare($conn, $update_year_query);
            mysqli_stmt_bind_param($update_year_stmt, 'si', $evaluation_year, $project_id);
            if (!mysqli_stmt_execute($update_year_stmt)) {
                throw new Exception("เกิดข้อผิดพลาดในการอัปเดตปีที่ต้องการประเมิน");
            }
            mysqli_stmt_close($update_year_stmt);
        } else {
            // ไม่มีข้อมูล - เพิ่มข้อมูลใหม่
            $insert_year_query = "INSERT INTO project_impact_ratios (project_id, year, benefit_number, benefit_note) VALUES (?, ?, ?, ?)";
            $insert_year_stmt = mysqli_prepare($conn, $insert_year_query);
            $benefit_number = 1; // ค่าเริ่มต้นสำหรับการบันทึกข้อมูลสัดส่วนผลกระทบ
            $benefit_note = 0;   // ค่าเริ่มต้นสำหรับจำนวนเงิน (บาท/ปี)
            mysqli_stmt_bind_param($insert_year_stmt, 'isii', $project_id, $evaluation_year, $benefit_number, $benefit_note);
            if (!mysqli_stmt_execute($insert_year_stmt)) {
                throw new Exception("เกิดข้อผิดพลาดในการบันทึกปีที่ต้องการประเมิน");
            }
            mysqli_stmt_close($insert_year_stmt);
        }
        mysqli_stmt_close($check_year_stmt);
    }

    // เก็บปีที่เลือกใน session เพื่อใช้ในหน้าอื่น
    $_SESSION['evaluation_year'] = $evaluation_year;

    // เก็บข้อมูลใน session เพื่อใช้ในการแสดงผล
    $_SESSION['selected_outcome'] = $selected_outcome;
    $_SESSION['selected_outcome_detail'] = $outcome;
    $_SESSION['success_message'] = "บันทึกการเลือกผลลัพธ์และปีที่ต้องการประเมินสำเร็จ";

    // ถ้าเป็นการบันทึกรายละเอียดเท่านั้น ให้ return JSON response
    if ($save_details_only) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'message' => 'บันทึกรายละเอียดผลลัพธ์สำเร็จ',
            'outcome_id' => $selected_outcome,
            'outcome_details' => $outcome_details,
            'evaluation_year' => $evaluation_year
        ]);
        exit;
    }

    // อัปเดตสถานะ Impact Chain - Step 4 เสร็จสิ้น
    updateMultipleImpactChainStatus($project_id, $chain_id, 4, true);

    // ตรวจสอบว่าต้องการเพิ่ม Impact Chain ใหม่หรือไม่
    if (isset($_GET['save_and_new_chain']) && $_GET['save_and_new_chain'] == '1') {
        // บันทึกข้อมูลแล้วไป step2 เพื่อสร้าง Impact Chain ใหม่
        $_SESSION['success_message'] = "บันทึก Impact Chain เรียบร้อยแล้ว กำลังสร้าง Impact Chain ใหม่";
        header("location: step2-activity.php?project_id=" . $project_id . "&new_chain=1");
        exit;
    }

    // ไปยังหน้าสรุป Impact Chain เพื่อเลือกว่าจะเพิ่มอีกหรือไปขั้นตอนต่อไป
    $_SESSION['completed_impact_chain'] = [
        'outcome_id' => $selected_outcome,
        'outcome_details' => $outcome_details,
        'evaluation_year' => $evaluation_year,
        'completed_at' => date('Y-m-d H:i:s')
    ];
    header("location: step4-completion.php?project_id=" . $project_id);
    exit;
} catch (Exception $e) {
    // ถ้าเป็นการบันทึกรายละเอียดเท่านั้น ให้ return JSON error response
    if ($save_details_only) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'เกิดข้อผิดพลาดในการบันทึกข้อมูล: ' . $e->getMessage(),
            'error' => $e->getMessage()
        ]);
        exit;
    }
    
    $_SESSION['error_message'] = "เกิดข้อผิดพลาดในการบันทึกข้อมูล: " . $e->getMessage();
    header("location: step4-outcome.php?project_id=" . $project_id);
    exit;
}
