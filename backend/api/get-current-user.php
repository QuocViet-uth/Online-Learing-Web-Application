<?php
/**
 * File: api/get-current-user.php
 * Mục đích: API lấy thông tin user hiện tại từ token
 * Method: GET
 * Headers: Authorization: Bearer {token}
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

// Decode token (token format: base64(id:username:timestamp))
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

// Lấy thông tin user
$user = new User($db);
$user->id = $user_id;

if ($user->readOne()) {
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "data" => array(
            "id" => intval($user->id),
            "username" => $user->username,
            "full_name" => $user->full_name,
            "date_of_birth" => $user->date_of_birth,
            "gender" => $user->gender,
            "email" => $user->email,
            "phone" => $user->phone,
            "role" => $user->role,
            "avatar" => $user->avatar
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    http_response_code(404);
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy user"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

