<?php
/**
 * File: api/mark-notification-read.php
 * Mục đích: Đánh dấu notification là đã đọc
 * Method: POST
 * Parameters:
 *   - notification_id: ID notification (optional - nếu không có thì mark all)
 *   - receiver_id: ID người nhận (required)
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Notification.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$notification = new Notification($db);
$method = $_SERVER['REQUEST_METHOD'];

// Chỉ chấp nhận POST
if ($method !== 'POST') {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Method not allowed"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Parse JSON input
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$notification_id = isset($data['notification_id']) ? intval($data['notification_id']) : null;
$receiver_id = isset($data['receiver_id']) ? intval($data['receiver_id']) : null;

if (empty($receiver_id)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu receiver_id"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

try {
    if ($notification_id) {
        // Mark một notification cụ thể
        $success = $notification->markAsRead($notification_id, $receiver_id);
        $message = $success ? "Đánh dấu notification đã đọc thành công" : "Không tìm thấy notification";
    } else {
        // Mark tất cả notifications
        $success = $notification->markAllAsRead($receiver_id);
        $message = $success ? "Đánh dấu tất cả notifications đã đọc thành công" : "Không có notifications nào để đánh dấu";
    }
    
    if (!headers_sent()) {
        http_response_code($success ? 200 : 404);
    }
    echo json_encode(array(
        "success" => $success,
        "message" => $message
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error marking notification as read: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi khi đánh dấu notification: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

