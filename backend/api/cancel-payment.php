<?php
/**
 * File: api/cancel-payment.php
 * Mục đích: API hủy payment (chuyển status từ pending sang failed)
 * Method: POST
 * Parameters: 
 *   - payment_id (required): ID payment
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

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Validate và lấy dữ liệu
$payment_id = isset($data['payment_id']) ? intval($data['payment_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
$student_id = isset($data['student_id']) ? intval($data['student_id']) : 0; // Tạm thời lấy từ request, sau này sẽ lấy từ token

if (empty($payment_id) || $payment_id <= 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Payment ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Lấy thông tin payment
$payment->id = $payment_id;
if (!$payment->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy thanh toán"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra quyền (chỉ student sở hữu payment mới được hủy)
if ($student_id > 0 && $payment->student_id != $student_id) {
    if (!headers_sent()) {
        http_response_code(403);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Bạn không có quyền hủy thanh toán này"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra status phải là pending
if ($payment->status !== 'pending') {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Chỉ có thể hủy thanh toán đang chờ xử lý"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Cập nhật status payment thành failed
$payment->status = 'failed';
if ($payment->updateStatus()) {
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Đã hủy thanh toán!"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể hủy thanh toán. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

