<?php
/**
 * File: api/update-coupon.php
 * Mục đích: API cập nhật mã giảm giá
 * Method: POST (với _method=PUT) hoặc PUT
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Coupon.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$coupon = new Coupon($db);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $data = $_POST;
} else {
    $data = json_decode(file_get_contents("php://input"), true);
}

if (!$data) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Dữ liệu không hợp lệ."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($data['id']) ? intval($data['id']) : 0);

if ($id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu ID mã giảm giá."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$coupon->id = $id;

if (!$coupon->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array("success" => false, "message" => "Không tìm thấy mã giảm giá."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$errors = array();

if (isset($data['code']) && !empty(trim($data['code']))) {
    $new_code = strtoupper(trim($data['code']));
    $check_coupon = new Coupon($db);
    $check_coupon->code = $new_code;
    if ($check_coupon->codeExists() && $check_coupon->id != $id) {
        $errors[] = "Mã giảm giá đã tồn tại";
    } else {
        $coupon->code = $new_code;
    }
}

if (isset($data['discount_percent'])) {
    $discount_percent = floatval($data['discount_percent']);
    if ($discount_percent <= 0 || $discount_percent > 100) {
        $errors[] = "Phần trăm giảm giá phải từ 1 đến 100";
    } else {
        $coupon->discount_percent = $discount_percent;
    }
}

if (isset($data['description'])) {
    $coupon->description = !empty(trim($data['description'])) ? trim($data['description']) : null;
}

if (isset($data['valid_from']) && !empty(trim($data['valid_from']))) {
    $coupon->valid_from = trim($data['valid_from']);
}

if (isset($data['valid_until']) && !empty(trim($data['valid_until']))) {
    $coupon->valid_until = trim($data['valid_until']);
}

if (isset($data['max_uses'])) {
    $coupon->max_uses = !empty(trim($data['max_uses'])) ? intval($data['max_uses']) : null;
}

if (isset($data['status']) && in_array($data['status'], ['active', 'inactive', 'expired'])) {
    $coupon->status = trim($data['status']);
}

// Validate ngày tháng
if (isset($coupon->valid_from) && isset($coupon->valid_until)) {
    if (strtotime($coupon->valid_from) > strtotime($coupon->valid_until)) {
        $errors[] = "Ngày bắt đầu phải trước ngày hết hạn";
    }
}

if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => implode(", ", $errors)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($coupon->update()) {
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Cập nhật mã giảm giá thành công",
        "data" => array(
            "id" => intval($coupon->id),
            "code" => $coupon->code,
            "discount_percent" => $coupon->discount_percent,
            "status" => $coupon->status
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể cập nhật mã giảm giá."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

