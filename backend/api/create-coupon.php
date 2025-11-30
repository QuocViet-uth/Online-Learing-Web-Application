<?php
/**
 * File: api/create-coupon.php
 * Mục đích: API tạo mã giảm giá mới
 * Method: POST
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Coupon.php';
require_once __DIR__ . '/../models/Notification.php';

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

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

if (!$data) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Dữ liệu không hợp lệ."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Validate và lấy dữ liệu
$coupon->code = isset($data['code']) ? strtoupper(trim($data['code'])) : '';
$coupon->discount_percent = isset($data['discount_percent']) ? floatval($data['discount_percent']) : 0;
$coupon->description = isset($data['description']) ? trim($data['description']) : null;
$coupon->valid_from = isset($data['valid_from']) ? trim($data['valid_from']) : '';
$coupon->valid_until = isset($data['valid_until']) ? trim($data['valid_until']) : '';
$coupon->max_uses = isset($data['max_uses']) && !empty(trim($data['max_uses'])) ? intval($data['max_uses']) : null;
$coupon->status = isset($data['status']) ? trim($data['status']) : 'active';

$errors = array();

if (empty($coupon->code)) {
    $errors[] = "Mã giảm giá không được để trống";
}

if ($coupon->discount_percent <= 0 || $coupon->discount_percent > 100) {
    $errors[] = "Phần trăm giảm giá phải từ 1 đến 100";
}

if (empty($coupon->valid_from)) {
    $errors[] = "Ngày bắt đầu không được để trống";
}

if (empty($coupon->valid_until)) {
    $errors[] = "Ngày hết hạn không được để trống";
}

if (!empty($coupon->valid_from) && !empty($coupon->valid_until)) {
    if (strtotime($coupon->valid_from) > strtotime($coupon->valid_until)) {
        $errors[] = "Ngày bắt đầu phải trước ngày hết hạn";
    }
}

if (!in_array($coupon->status, ['active', 'inactive', 'expired'])) {
    $errors[] = "Trạng thái không hợp lệ";
}

// Kiểm tra mã code đã tồn tại chưa
if (!empty($coupon->code)) {
    $check_coupon = new Coupon($db);
    $check_coupon->code = $coupon->code;
    if ($check_coupon->codeExists()) {
        $errors[] = "Mã giảm giá đã tồn tại";
    }
}

if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => implode(", ", $errors)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($coupon->create()) {
    // Tạo thông báo cho tất cả users (teacher và student) về mã giảm giá mới
    try {
        // Lấy admin_id từ request hoặc tìm admin đầu tiên
        $admin_id = isset($data['admin_id']) ? intval($data['admin_id']) : null;
        
        if (!$admin_id) {
            // Tìm admin đầu tiên
            $admin_stmt = $db->prepare("SELECT id FROM users WHERE role = 'admin' LIMIT 1");
            $admin_stmt->execute();
            $admin = $admin_stmt->fetch(PDO::FETCH_ASSOC);
            $admin_id = $admin ? intval($admin['id']) : 1; // Default to 1 if no admin found
        }
        
        if ($admin_id) {
            $notification = new Notification($db);
            $title = "Mã giảm giá mới: " . $coupon->code;
            $content = "Có mã giảm giá mới \"" . $coupon->code . "\" với mức giảm " . number_format($coupon->discount_percent, 0) . "%. ";
            if ($coupon->description) {
                $content .= $coupon->description . ". ";
            }
            $valid_from_str = date('d/m/Y', strtotime($coupon->valid_from));
            $valid_until_str = date('d/m/Y', strtotime($coupon->valid_until));
            $content .= "Áp dụng từ " . $valid_from_str . " đến " . $valid_until_str . ". Hãy sử dụng ngay!";
            
            $notification->createForAllUsers($admin_id, $title, $content);
        }
    } catch (Exception $e) {
        // Log lỗi nhưng không ảnh hưởng đến response
        error_log("Error creating notification for coupon: " . $e->getMessage());
    }
    
    if (!headers_sent()) {
        http_response_code(201);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Tạo mã giảm giá thành công",
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
    echo json_encode(array("success" => false, "message" => "Không thể tạo mã giảm giá."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

