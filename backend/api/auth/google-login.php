<?php
/**
 * File: api/auth/google-login.php
 * Mục đích: API xử lý đăng nhập bằng Google
 * Method: POST
 * Parameters: 
 *   - id_token (required): Google ID token
 * Response: JSON
 */

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';

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

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$id_token = isset($data['id_token']) ? trim($data['id_token']) : '';

if (empty($id_token)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu Google ID token"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Verify Google ID token
$google_config_file = __DIR__ . '/../../config/google_oauth.php';
if (!file_exists($google_config_file)) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Chưa cấu hình Google OAuth"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$google_config = require $google_config_file;
$client_id = $google_config['client_id'] ?? '';

// Verify token với Google
$url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($id_token);
$response = @file_get_contents($url);

if ($response === false) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Token không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$token_data = json_decode($response, true);

if (!$token_data || !isset($token_data['email'])) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể xác thực Google token"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Kiểm tra client_id (nếu có)
if (!empty($client_id) && isset($token_data['aud']) && $token_data['aud'] !== $client_id) {
    http_response_code(401);
    echo json_encode(array(
        "success" => false,
        "message" => "Token không khớp với ứng dụng"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$email = $token_data['email'];
$name = $token_data['name'] ?? '';
$picture = $token_data['picture'] ?? 'default-avatar.png';
$google_id = $token_data['sub'] ?? '';

// Kiểm tra user đã tồn tại chưa (theo email)
$user = new User($db);
$user->email = $email;

if ($user->emailExists()) {
    // User đã tồn tại - đăng nhập
    $user->readByEmail();
    
    // Cập nhật avatar nếu có
    if (!empty($picture) && $picture !== 'default-avatar.png') {
        $user->avatar = $picture;
        $user->update();
    }
} else {
    // User chưa tồn tại - tạo mới
    // Tạo username từ email (phần trước @)
    $email_parts = explode('@', $email);
    $base_username = preg_replace('/[^a-zA-Z0-9_]/', '', $email_parts[0]); // Loại bỏ ký tự đặc biệt
    if (empty($base_username)) {
        $base_username = 'user' . substr(md5($email), 0, 8); // Fallback nếu email không hợp lệ
    }
    
    $username = $base_username;
    $counter = 1;
    
    // Đảm bảo username unique
    $check_user = new User($db);
    $check_user->username = $username;
    while ($check_user->usernameExists()) {
        $username = $base_username . $counter;
        $check_user->username = $username;
        $counter++;
        if ($counter > 1000) {
            // Tránh vòng lặp vô hạn
            $username = $base_username . '_' . time();
            break;
        }
    }
    
    $user->username = $username;
    $user->full_name = $name ? $name : $username;
    $user->email = $email;
    $user->avatar = $picture;
    $user->role = 'student'; // Mặc định là student
    $user->password = password_hash(uniqid(rand(), true), PASSWORD_BCRYPT); // Random password (không dùng được)
    
    if (!$user->create()) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể tạo tài khoản"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Đọc lại user vừa tạo
    $user->readOne();
}

// Tạo token đăng nhập
$token = base64_encode($user->id . ':' . $user->username . ':' . time());

http_response_code(200);
echo json_encode(array(
    "success" => true,
    "message" => "Đăng nhập thành công",
    "data" => array(
        "user" => array(
            "id" => intval($user->id),
            "username" => $user->username,
            "full_name" => $user->full_name,
            "email" => $user->email,
            "role" => $user->role,
            "avatar" => $user->avatar
        ),
        "token" => $token
    )
), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
?>

