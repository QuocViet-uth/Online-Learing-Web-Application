<?php
/**
 * File: api/update-user-fullname.php
 * Mục đích: API để user cập nhật full_name (cho user cũ chưa có full_name)
 * Method: POST
 * Headers: Authorization: Bearer {token}
 * Parameters:
 *   - full_name (required): Họ và tên mới
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể kết nối database"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Lấy token từ header
$headers = getallheaders();
$token = null;

if (isset($headers['Authorization'])) {
    $authHeader = $headers['Authorization'];
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        $token = $matches[1];
    }
}

if (!$token) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Token không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Decode token
$decoded = base64_decode($token);
$parts = explode(':', $decoded);

if (count($parts) < 2) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Token không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$user_id = intval($parts[0]);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$full_name = isset($data['full_name']) ? trim($data['full_name']) : '';

if (empty($full_name)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Họ và tên không được để trống"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Cập nhật user
$user = new User($db);
$user->id = $user_id;

if (!$user->readOne()) {
    http_response_code(404);
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy user"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$user->full_name = $full_name;

if ($user->update()) {
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Cập nhật họ và tên thành công",
        "data" => array(
            "id" => intval($user->id),
            "username" => $user->username,
            "full_name" => $user->full_name,
            "email" => $user->email,
            "role" => $user->role,
            "avatar" => $user->avatar
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể cập nhật họ và tên"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

