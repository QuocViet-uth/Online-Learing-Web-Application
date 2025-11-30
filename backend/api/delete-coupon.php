<?php
/**
 * File: api/delete-coupon.php
 * Mục đích: API xóa mã giảm giá
 * Method: POST (với _method=DELETE) hoặc DELETE
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
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'DELETE') {
    $data = $_POST;
} else {
    $data = json_decode(file_get_contents("php://input"), true);
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

if ($coupon->delete()) {
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array("success" => true, "message" => "Xóa mã giảm giá thành công."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể xóa mã giảm giá."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

