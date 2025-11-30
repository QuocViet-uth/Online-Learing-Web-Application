<?php
/**
 * File: api/payments.php
 * Mục đích: API lấy danh sách payments
 * Method: GET
 * Parameters: 
 *   - student_id (optional): Lọc theo học viên
 *   - course_id (optional): Lọc theo khóa học
 *   - status (optional): Lọc theo trạng thái (pending, success, failed, refunded)
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Payment.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$payment = new Payment($db);

// Lấy parameters từ GET
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
$status = isset($_GET['status']) ? trim($_GET['status']) : null;

// Thực hiện query dựa trên parameters
if ($student_id) {
    // Lấy payments theo student
    $payment->student_id = $student_id;
    $stmt = $payment->readByStudent();
} elseif ($course_id) {
    // Lấy payments theo course
    $payment->course_id = $course_id;
    $stmt = $payment->readByCourse();
} elseif ($status) {
    // Lấy payments theo status
    $payment->status = $status;
    $stmt = $payment->readByStatus();
} else {
    // Lấy tất cả payments
    $stmt = $payment->readAll();
}

$num = $stmt->rowCount();

// Kiểm tra có dữ liệu không
if($num > 0) {
    // Mảng chứa payments
    $payments_arr = array();
    $payments_arr["success"] = true;
    $payments_arr["message"] = "Lấy danh sách thanh toán thành công";
    $payments_arr["total"] = $num;
    $payments_arr["data"] = array();
    
    // Lấy dữ liệu
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $payment_item = array(
            "id" => intval($id),
            "student_id" => intval($student_id),
            "course_id" => intval($course_id),
            "amount" => floatval($amount),
            "payment_gateway" => $payment_gateway,
            "transaction_id" => $transaction_id,
            "status" => $status,
            "payment_date" => $payment_date,
            "student_name" => isset($student_name) ? $student_name : null,
            "student_email" => isset($student_email) ? $student_email : null,
            "course_name" => isset($course_name) ? $course_name : null,
            "course_title" => isset($course_title) ? $course_title : null
        );
        
        array_push($payments_arr["data"], $payment_item);
    }
    
    // Set response code - 200 OK
    if (!headers_sent()) {
        http_response_code(200);
    }
    
    // Hiển thị dữ liệu dạng JSON với UTF-8 encoding
    echo json_encode($payments_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} else {
    // Không có dữ liệu - trả về 200 với empty array
    if (!headers_sent()) {
        http_response_code(200);
    }
    
    echo json_encode(array(
        "success" => true,
        "message" => "Không tìm thấy thanh toán nào",
        "total" => 0,
        "data" => array()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

