<?php
/**
 * File: api/auth/login.php
 * Mục đích: API đăng nhập
 * Method: POST
 * Parameters: 
 *   - username (required): Tên đăng nhập
 *   - password (required): Mật khẩu
 * Response: JSON
 */

// Tắt error display
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Set UTF-8 encoding (if mbstring is available)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
}

// Include common headers
require_once __DIR__ . '/../../config/headers.php';

// Include files
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

// Kiểm tra kết nối database
if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    
    // Check if pdo_mysql extension is missing
    $errorMsg = "Không thể kết nối đến database.";
    if (!extension_loaded('pdo_mysql')) {
        $errorMsg = "Lỗi: PHP extension 'pdo_mysql' chưa được bật. Vui lòng enable extension này trong php.ini và restart PHP server.";
    }
    
    echo json_encode(array(
        "success" => false,
        "message" => $errorMsg
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

$user = new User($db);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Debug: Log raw input (chỉ trong development)
// error_log("Raw input: " . $raw_input);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Nếu vẫn không có, thử parse lại
if (empty($data) && !empty($raw_input)) {
    parse_str($raw_input, $data);
}

// Nếu vẫn không có, thử decode lại với các options
if (empty($data) && !empty($raw_input)) {
    $data = json_decode($raw_input, true, 512, JSON_BIGINT_AS_STRING);
}

$username = isset($data['username']) ? trim($data['username']) : '';
$password = isset($data['password']) ? $data['password'] : '';

// Debug output (chỉ trong development)
// error_log("Username: " . $username . ", Password length: " . strlen($password));

// Kiểm tra dữ liệu đầu vào
if (empty($username) || empty($password)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Vui lòng nhập đầy đủ username và password"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Thực hiện đăng nhập
$user->username = $username;
$user->password = $password;

if ($user->login()) {
    // Đăng nhập thành công
    // Tạo token đơn giản (trong production nên dùng JWT)
    $token = base64_encode($user->id . ':' . $user->username . ':' . time());
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Đăng nhập thành công",
        "data" => array(
            "user" => array(
                "id" => intval($user->id),
                "username" => $user->username,
                "full_name" => $user->full_name ? $user->full_name : null,
                "email" => $user->email,
                "role" => $user->role,
                "avatar" => $user->avatar ? $user->avatar : 'default-avatar.png'
            ),
            "token" => $token
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} else {
    // Đăng nhập thất bại
    if (!headers_sent()) {
        http_response_code(401);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Tên đăng nhập hoặc mật khẩu không đúng"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

