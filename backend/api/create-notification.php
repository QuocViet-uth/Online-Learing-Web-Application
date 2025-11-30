<?php
/**
 * File: api/create-notification.php
 * Mục đích: Tạo notification cho chat message (được gọi từ chat.php sau khi gửi tin nhắn thành công)
 * Method: POST
 * Parameters:
 *   - sender_id: ID người gửi
 *   - receiver_id: ID người nhận (cho chat 1-1)
 *   - course_id: ID khóa học (cho group chat)
 *   - content: Nội dung tin nhắn
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

$sender_id = isset($data['sender_id']) ? intval($data['sender_id']) : null;
$receiver_id = isset($data['receiver_id']) ? intval($data['receiver_id']) : null;
$course_id = isset($data['course_id']) ? intval($data['course_id']) : null;
$content = isset($data['content']) ? trim($data['content']) : '';

if (empty($sender_id) || empty($content)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu sender_id hoặc content"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

try {
    // Lấy thông tin sender
    $sender_stmt = $db->prepare("SELECT username, role FROM users WHERE id = ? LIMIT 1");
    $sender_stmt->execute([$sender_id]);
    $sender = $sender_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$sender) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Sender không tồn tại"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    
    $content_preview = mb_substr($content, 0, 100) . (mb_strlen($content) > 100 ? '...' : '');
    $created_count = 0;
    
    // Nếu là group chat (có course_id)
    if ($course_id) {
        // Lấy thông tin course
        $course_stmt = $db->prepare("SELECT teacher_id, course_name FROM courses WHERE id = ? LIMIT 1");
        $course_stmt->execute([$course_id]);
        $course = $course_stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($course) {
            $title = "Tin nhắn mới từ " . $sender['username'];
            $full_content = $sender['username'] . " đã gửi tin nhắn trong khóa học " . $course['course_name'] . ": " . $content_preview;
            
            // Lấy danh sách receivers (teacher + tất cả students enrolled, trừ sender)
            $receivers = array();
            
            // Thêm teacher nếu không phải sender
            if ($course['teacher_id'] != $sender_id) {
                $receivers[] = intval($course['teacher_id']);
            }
            
            // Lấy tất cả students enrolled trong course (trừ sender)
            $enrollment_stmt = $db->prepare("SELECT student_id FROM enrollments WHERE course_id = ? AND student_id != ? AND status = 'active'");
            $enrollment_stmt->execute([$course_id, $sender_id]);
            $enrollments = $enrollment_stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($enrollments as $enrollment) {
                $receivers[] = intval($enrollment['student_id']);
            }
            
            // Batch create notifications
            if (!empty($receivers)) {
                $created_count = $notification->createBatchForGroupChat(
                    $sender_id,
                    $course_id,
                    $title,
                    $full_content,
                    $receivers
                );
            }
        }
    } 
    // Nếu là chat 1-1 (có receiver_id)
    else if ($receiver_id) {
        $notification->sender_id = $sender_id;
        $notification->receiver_id = $receiver_id;
        $notification->course_id = null;
        $notification->title = "Tin nhắn mới từ " . $sender['username'];
        $notification->content = $sender['username'] . ": " . $content_preview;
        
        if ($notification->create()) {
            $created_count = 1;
        }
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Tạo notification thành công",
        "created_count" => $created_count
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    error_log("Error creating notification: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi khi tạo notification: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

