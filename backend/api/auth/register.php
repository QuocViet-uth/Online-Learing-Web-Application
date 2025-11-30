<?php
/**
 * File: api/auth/register.php
 * Mục đích: API đăng ký user mới
 * Method: POST
 * Parameters:
 *   - username (required): Tên đăng nhập
 *   - full_name (required): Họ và tên
 *   - password (required): Mật khẩu
 *   - email (required): Email
 *   - phone (optional): Số điện thoại
 *   - role (required): Vai trò (teacher, student)
 * Response: JSON
 */

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/User.php';

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

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Validate và lấy dữ liệu
$user->username = isset($data['username']) ? trim($data['username']) : '';
$user->full_name = isset($data['full_name']) ? trim($data['full_name']) : '';
$user->date_of_birth = isset($data['date_of_birth']) && !empty(trim($data['date_of_birth'])) ? trim($data['date_of_birth']) : null;
$user->school = isset($data['school']) ? trim($data['school']) : null;
$user->password = isset($data['password']) ? trim($data['password']) : '';
$user->email = isset($data['email']) ? trim($data['email']) : '';
$user->phone = isset($data['phone']) ? trim($data['phone']) : '';
$user->role = isset($data['role']) ? trim($data['role']) : 'student';
$user->avatar = isset($data['avatar']) ? trim($data['avatar']) : 'default-avatar.png';

// Validate dữ liệu bắt buộc
$errors = array();

if (empty($user->username)) {
    $errors[] = "Tên đăng nhập không được để trống";
}

if (empty($user->full_name)) {
    $errors[] = "Họ và tên không được để trống";
}

if (empty($user->password)) {
    $errors[] = "Mật khẩu không được để trống";
} elseif (strlen($user->password) < 6) {
    $errors[] = "Mật khẩu phải có ít nhất 6 ký tự";
}

if (empty($user->email)) {
    $errors[] = "Email không được để trống";
} elseif (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email không hợp lệ";
}

if (!in_array($user->role, ['teacher', 'student'])) {
    $errors[] = "Vai trò không hợp lệ. Chỉ cho phép teacher hoặc student";
}

// Kiểm tra username đã tồn tại chưa
if (!empty($user->username)) {
    $check_user = new User($db);
    $check_user->username = $user->username;
    if ($check_user->usernameExists()) {
        $errors[] = "Tên đăng nhập đã tồn tại";
    }
}

// Kiểm tra email đã tồn tại chưa
if (!empty($user->email)) {
    $check_user = new User($db);
    $check_user->email = $user->email;
    if ($check_user->emailExists()) {
        $errors[] = "Email đã tồn tại";
    }
}

if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => implode(", ", $errors)
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Tạo user
try {
    if ($user->create()) {
        if (!headers_sent()) {
            http_response_code(201);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Đăng ký thành công",
            "data" => array(
                "id" => intval($user->id),
                "username" => $user->username,
                "full_name" => $user->full_name,
                "email" => $user->email,
                "role" => $user->role
            )
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể tạo tài khoản. Vui lòng thử lại sau."
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (Exception $e) {
    error_log("Register API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server khi đăng ký: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

