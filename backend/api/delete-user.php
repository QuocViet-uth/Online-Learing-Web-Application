<?php
/**
 * File: api/delete-user.php
 * Mục đích: API xóa user (cho admin)
 * Method: DELETE (POST with _method=DELETE)
 * Parameters:
 *   - id (required): ID của user cần xóa
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể kết nối database"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$user = new User($db);

// Lấy dữ liệu từ POST/DELETE
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Check for method override
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Check _method for DELETE override
if (isset($data['_method'])) {
    $method = strtoupper($data['_method']);
} else {
    $method = $_SERVER['REQUEST_METHOD'];
}

// Chỉ cho phép DELETE
if ($method !== 'DELETE' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Method Not Allowed. Chỉ hỗ trợ DELETE."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Lấy ID từ query string hoặc data
$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($data['id']) ? intval($data['id']) : 0);

if (empty($id)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu ID user"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$user->id = $id;

// Kiểm tra user có tồn tại không
if (!$user->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy user"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Xóa user
try {
    if ($user->delete()) {
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Xóa user thành công"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể xóa user"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (Exception $e) {
    error_log("Delete User API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server khi xóa user: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

