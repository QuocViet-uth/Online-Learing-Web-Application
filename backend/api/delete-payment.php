<?php
/**
 * File: api/delete-payment.php
 * Mục đích: API xóa payment (chỉ dành cho admin)
 * Method: POST/DELETE
 * Parameters: 
 *   - id (required): ID payment cần xóa
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

// Lấy dữ liệu từ POST hoặc GET
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Kiểm tra _method để hỗ trợ DELETE
if (isset($data['_method']) && $data['_method'] === 'DELETE') {
    // Xử lý như DELETE request
}

// Validate và lấy dữ liệu
$payment_id = isset($data['id']) ? intval($data['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

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

// Kiểm tra payment có tồn tại không
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

// Lưu thông tin payment trước khi xóa (để trả về trong response)
$payment_info = array(
    "id" => intval($payment->id),
    "transaction_id" => $payment->transaction_id,
    "amount" => floatval($payment->amount)
);

// Xóa payment
if ($payment->delete()) {
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Xóa thanh toán thành công!",
        "data" => $payment_info
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể xóa thanh toán. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

