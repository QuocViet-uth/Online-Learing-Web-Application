<?php
/**
 * File: api/get-notifications.php
 * Mục đích: Lấy danh sách notifications của user
 * Method: GET
 * Parameters:
 *   - receiver_id: ID người nhận (required)
 *   - limit: Số lượng notifications (default: 50)
 *   - offset: Offset (default: 0)
 *   - unread_only: Chỉ lấy notifications chưa đọc (default: false)
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

// Chỉ chấp nhận GET
if ($method !== 'GET') {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Method not allowed"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

$receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;
$limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
$offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
$unread_only = isset($_GET['unread_only']) ? ($_GET['unread_only'] === 'true' || $_GET['unread_only'] === '1') : false;

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
    // Đếm số notifications chưa đọc
    $unread_count = $notification->countUnread($receiver_id);
    
    // Lấy notifications
    $stmt = $notification->getByReceiver($receiver_id, $limit, $offset);
    $num = $stmt->rowCount();
    
    $notifications_arr = array();
    $notifications_arr["success"] = true;
    $notifications_arr["message"] = "Lấy notifications thành công";
    $notifications_arr["total"] = $num;
    $notifications_arr["unread_count"] = $unread_count;
    $notifications_arr["data"] = array();
    
    if ($num > 0) {
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            // Convert created_at từ MySQL datetime sang ISO 8601 format với timezone
            // Sử dụng utility function từ timezone.php
            $created_at = $row['created_at'];
            if ($created_at) {
                $created_at = formatDateTimeISO($created_at); // ISO 8601 với timezone
            }
            
            // Nếu unread_only = true, chỉ lấy notifications chưa đọc
            if ($unread_only && $row['is_read']) {
                continue;
            }
            
            $notification_item = array(
                "id" => intval($row['id']),
                "user_id" => $row['user_id'] ? intval($row['user_id']) : null,
                "receiver_id" => $row['user_id'] ? intval($row['user_id']) : null, // For backward compatibility
                "course_id" => ($row['related_type'] === 'course' && $row['related_id']) ? intval($row['related_id']) : null,
                "title" => $row['title'],
                "content" => $row['content'],
                "type" => $row['type'] ?? 'info',
                "is_read" => (bool)$row['is_read'],
                "created_at" => $created_at,
                "course_name" => $row['course_name'] ?? null
            );
            
            array_push($notifications_arr["data"], $notification_item);
        }
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode($notifications_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error getting notifications: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi khi lấy notifications: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

