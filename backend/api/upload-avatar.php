<?php
/**
 * File: api/upload-avatar.php
 * Mục đích: API upload avatar cho user
 * Method: POST (multipart/form-data)
 * Parameters: 
 *   - avatar (required): File ảnh avatar
 *   - user_id (required): ID của user
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

// Kiểm tra authentication
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

// Decode token để lấy user_id
$decoded = base64_decode($token);
$parts = explode(':', $decoded);
$token_user_id = count($parts) >= 1 ? intval($parts[0]) : 0;

// Cấu hình upload
$upload_dir = __DIR__ . '/../uploads/avatars/';
$allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
$max_file_size = 5 * 1024 * 1024; // 5MB

// Tạo thư mục uploads/avatars nếu chưa có
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Kiểm tra file có được upload không
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Không có file được upload hoặc có lỗi xảy ra"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$file = $_FILES['avatar'];

// Kiểm tra kích thước file
if ($file['size'] > $max_file_size) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "File quá lớn. Kích thước tối đa là 5MB"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Lấy extension của file
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Kiểm tra loại file có được phép không
if (!in_array($file_extension, $allowed_types)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Loại file không được phép. Chỉ chấp nhận: " . implode(', ', $allowed_types)
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Validate file là ảnh thực sự
$image_info = getimagesize($file['tmp_name']);
if ($image_info === false) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "File không phải là ảnh hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Lấy user_id từ POST hoặc từ token
$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : $token_user_id;

if ($user_id <= 0) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "User ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Kiểm tra user có tồn tại không
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

// Xóa avatar cũ nếu có (trừ khi là URL từ bên ngoài)
$old_avatar = $user->avatar;
if ($old_avatar && strpos($old_avatar, '/api/uploads/avatars/') !== false) {
    $old_file_path = __DIR__ . '/../uploads/avatars/' . basename($old_avatar);
    if (file_exists($old_file_path)) {
        @unlink($old_file_path);
    }
}

// Tạo tên file unique
$file_name = 'avatar_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $file_extension;
$file_path = $upload_dir . $file_name;

// Upload file
if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Tạo URL cho avatar (relative path)
    $avatar_url = '/api/uploads/avatars/' . $file_name;
    
    // Cập nhật avatar trong database
    $user->avatar = $avatar_url;
    if ($user->update()) {
        http_response_code(200);
        echo json_encode(array(
            "success" => true,
            "message" => "Upload avatar thành công",
            "data" => array(
                "avatar_url" => $avatar_url,
                "file_name" => $file_name,
                "file_size" => $file['size']
            )
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // Xóa file nếu không cập nhật được database
        @unlink($file_path);
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể cập nhật avatar trong database"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể upload file. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

