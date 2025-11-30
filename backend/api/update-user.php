<?php
/**
 * File: api/update-user.php
 * Mục đích: API cập nhật thông tin user (cho admin)
 * Method: PUT (POST with _method=PUT)
 * Parameters:
 *   - id (required): ID của user
 *   - username (optional): Tên đăng nhập
 *   - email (optional): Email
 *   - phone (optional): Số điện thoại
 *   - role (optional): Vai trò
 *   - avatar (optional): URL avatar
 *   - password (optional): Mật khẩu mới (nếu muốn đổi)
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

// Lấy dữ liệu từ POST/PUT
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Check for method override
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Check _method for PUT/DELETE override
if (isset($data['_method'])) {
    $method = strtoupper($data['_method']);
    unset($data['_method']);
} else {
    $method = $_SERVER['REQUEST_METHOD'];
}

// Chỉ cho phép PUT
if ($method !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Method Not Allowed. Chỉ hỗ trợ PUT."
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

// Cập nhật các trường được cung cấp
if (isset($data['username']) && !empty(trim($data['username']))) {
    $new_username = trim($data['username']);
    // Kiểm tra username đã tồn tại chưa (trừ user hiện tại)
    $stmt_check = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ? LIMIT 1");
    $stmt_check->execute([$new_username, $id]);
    if ($stmt_check->rowCount() > 0) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Username đã tồn tại"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    $user->username = $new_username;
}

if (isset($data['email']) && !empty(trim($data['email']))) {
    $new_email = trim($data['email']);
    if (!filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Email không hợp lệ"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    // Kiểm tra email đã tồn tại chưa (trừ user hiện tại)
    $stmt_check = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1");
    $stmt_check->execute([$new_email, $id]);
    if ($stmt_check->rowCount() > 0) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Email đã tồn tại"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    $user->email = $new_email;
}

if (isset($data['full_name'])) {
    // Cho phép cập nhật full_name
    $trimmed_full_name = trim($data['full_name']);
    $user->full_name = $trimmed_full_name; // Giữ nguyên giá trị, kể cả empty string
    // Log để debug
    error_log("Update full_name: " . var_export($data['full_name'], true) . " -> " . var_export($user->full_name, true));
}

if (isset($data['date_of_birth'])) {
    $user->date_of_birth = !empty(trim($data['date_of_birth'])) ? trim($data['date_of_birth']) : null;
}

if (isset($data['gender'])) {
    $gender = trim($data['gender']);
    $user->gender = !empty($gender) && in_array($gender, ['male', 'female', 'other']) ? $gender : null;
}

if (isset($data['school'])) {
    $user->school = !empty(trim($data['school'])) ? trim($data['school']) : null;
}

if (isset($data['phone'])) {
    $user->phone = trim($data['phone']);
}

if (isset($data['avatar'])) {
    $user->avatar = trim($data['avatar']);
}

if (isset($data['role']) && in_array($data['role'], ['admin', 'teacher', 'student'])) {
    // Cập nhật role trực tiếp trong database vì update() không cập nhật role
    $stmt = $db->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->execute([$data['role'], $id]);
    $user->role = $data['role'];
}

// Đổi password nếu có
if (isset($data['password']) && !empty(trim($data['password']))) {
    $user->password = trim($data['password']);
    if (strlen($user->password) < 4) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Password phải có ít nhất 4 ký tự"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    // Đổi password
    if (!$user->changePassword()) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể đổi password"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
}

// Cập nhật thông tin user
try {
    // Đảm bảo tất cả các trường đều được set (giữ nguyên giá trị cũ nếu không được gửi)
    // Các trường này đã được load từ readOne() ở trên, nên chỉ cần set lại nếu có trong $data
    // Username, email, phone, avatar, role đã được xử lý ở trên
    
    // Log để debug
    error_log("Before update - ID: $id");
    error_log("Before update - full_name: " . var_export($user->full_name, true));
    error_log("Before update - username: " . var_export($user->username, true));
    error_log("Before update - email: " . var_export($user->email, true));
    error_log("Before update - phone: " . var_export($user->phone, true));
    error_log("Before update - Data received: " . var_export($data, true));
    
    if ($user->update()) {
        // Đọc lại thông tin user đã cập nhật
        $user->readOne();
        
        // Log để debug
        error_log("Update user ID $id - full_name after: " . var_export($user->full_name, true));
        
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Cập nhật user thành công",
            "data" => array(
                "id" => intval($user->id),
                "username" => $user->username,
                "full_name" => $user->full_name,
                "date_of_birth" => $user->date_of_birth,
                "gender" => $user->gender,
                "school" => $user->school,
                "email" => $user->email,
                "phone" => $user->phone,
                "avatar" => $user->avatar,
                "role" => $user->role,
                "updated_at" => $user->updated_at
            )
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // Lấy thông tin lỗi từ PDO
        $errorInfo = $db->errorInfo();
        $errorMessage = isset($errorInfo[2]) ? $errorInfo[2] : "Không thể cập nhật user";
        
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi khi cập nhật user: " . $errorMessage
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (Exception $e) {
    error_log("Update User API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server khi cập nhật user: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

