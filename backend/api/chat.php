<?php
/**
 * File: api/chat.php
 * Mục đích: API xử lý chat giữa giảng viên và học viên
 * Method: GET, POST
 * Parameters: 
 *   - course_id (optional): Lọc theo khóa học
 *   - receiver_id (optional): Lọc theo người nhận
 *   - sender_id (optional): ID người gửi (từ token/session)
 * Response: JSON
 */

// Include headers và database
require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Chat.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$chat = new Chat($db);
$method = $_SERVER['REQUEST_METHOD'];

// Lấy sender_id từ token/session (hiện tại dùng GET parameter để test)
// TODO: Thay thế bằng JWT token hoặc session
$sender_id = isset($_GET['sender_id']) ? intval($_GET['sender_id']) : 
             (isset($_POST['sender_id']) ? intval($_POST['sender_id']) : null);

// GET: Lấy tin nhắn
if ($method === 'GET') {
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    $receiver_id = isset($_GET['receiver_id']) ? intval($_GET['receiver_id']) : null;
    $conversations_only = isset($_GET['conversations_only']) ? $_GET['conversations_only'] === 'true' : false;
    
    try {
        if ($conversations_only && $sender_id) {
            // Lấy danh sách cuộc trò chuyện
            $chat->sender_id = $sender_id;
            $stmt = $chat->getConversations();
            $num = $stmt->rowCount();
            
            if ($num > 0) {
                $conversations_arr = array();
                $conversations_arr["success"] = true;
                $conversations_arr["message"] = "Lấy danh sách cuộc trò chuyện thành công";
                $conversations_arr["data"] = array();
                
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    $conversation_item = array(
                        "other_user_id" => intval($row['other_user_id']),
                        "other_user_name" => $row['other_user_name'],
                        "other_user_avatar" => $row['other_user_avatar'],
                        "other_user_role" => $row['other_user_role'],
                        "course_id" => $row['course_id'] ? intval($row['course_id']) : null,
                        "course_name" => $row['course_name'],
                        "last_message" => $row['last_message'],
                        "last_message_time" => $row['last_message_time'],
                        "unread_count" => intval($row['unread_count'])
                    );
                    
                    array_push($conversations_arr["data"], $conversation_item);
                }
                
                http_response_code(200);
                if (!headers_sent()) {
                    http_response_code(200);
                }
                echo json_encode($conversations_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            } else {
                if (!headers_sent()) {
                    http_response_code(200);
                }
                echo json_encode(array(
                    "success" => true,
                    "message" => "Chưa có cuộc trò chuyện nào",
                    "data" => array()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            
        } elseif ($course_id && !$receiver_id) {
            // Lấy tất cả tin nhắn trong course (group chat)
            // Không cần kiểm tra sender_id - tất cả user trong course đều có thể xem group chat
            $chat->course_id = $course_id;
            $stmt = $chat->getByCourse();
            $num = $stmt->rowCount();
            
            // Debug log
            error_log("Chat API - getByCourse: course_id=$course_id, num_messages=$num");
            
            if ($num > 0) {
                $messages_arr = array();
                $messages_arr["success"] = true;
                $messages_arr["message"] = "Lấy tin nhắn thành công";
                $messages_arr["course_id"] = $course_id;
                $messages_arr["total"] = $num;
                $messages_arr["data"] = array();
                
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Convert sent_at từ MySQL datetime sang ISO 8601 format với timezone
                    // Sử dụng utility function từ timezone.php
                    $sent_at = $row['sent_at'];
                    if ($sent_at) {
                        $sent_at = formatDateTimeISO($sent_at); // ISO 8601 với timezone
                    }
                    
                    $message_item = array(
                        "id" => intval($row['id']),
                        "sender_id" => intval($row['sender_id']),
                        "receiver_id" => intval($row['receiver_id']) ? intval($row['receiver_id']) : null,
                        "course_id" => intval($row['course_id']),
                        "content" => $row['content'],
                        "is_read" => (bool)$row['is_read'],
                        "sent_at" => $sent_at,
                        "sender_name" => $row['sender_name'],
                        "sender_avatar" => $row['sender_avatar'],
                        "sender_role" => $row['sender_role']
                    );
                    
                    array_push($messages_arr["data"], $message_item);
                }
                
                if (!headers_sent()) {
                    http_response_code(200);
                }
                echo json_encode($messages_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            } else {
                // Trả về empty array ngay cả khi không có tin nhắn
                if (!headers_sent()) {
                    http_response_code(200);
                }
                echo json_encode(array(
                    "success" => true,
                    "message" => "Chưa có tin nhắn nào",
                    "course_id" => $course_id,
                    "total" => 0,
                    "data" => array()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
            
        } elseif ($sender_id && $receiver_id) {
            // Lấy cuộc trò chuyện giữa 2 người
            $chat->sender_id = $sender_id;
            $chat->receiver_id = $receiver_id;
            $chat->course_id = $course_id;
            $stmt = $chat->getConversation();
            $num = $stmt->rowCount();
            
            if ($num > 0) {
                $messages_arr = array();
                $messages_arr["success"] = true;
                $messages_arr["message"] = "Lấy tin nhắn thành công";
                $messages_arr["total"] = $num;
                $messages_arr["data"] = array();
                
                while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                    // Convert sent_at từ MySQL datetime sang ISO 8601 format với timezone
                    // Sử dụng utility function từ timezone.php
                    $sent_at = $row['sent_at'];
                    if ($sent_at) {
                        $sent_at = formatDateTimeISO($sent_at); // ISO 8601 với timezone
                    }
                    
                    $message_item = array(
                        "id" => intval($row['id']),
                        "sender_id" => intval($row['sender_id']),
                        "receiver_id" => intval($row['receiver_id']),
                        "course_id" => $row['course_id'] ? intval($row['course_id']) : null,
                        "content" => $row['content'],
                        "is_read" => (bool)$row['is_read'],
                        "sent_at" => $sent_at,
                        "sender_name" => $row['sender_name'],
                        "sender_avatar" => $row['sender_avatar'],
                        "sender_role" => isset($row['sender_role']) ? $row['sender_role'] : null
                    );
                    
                    array_push($messages_arr["data"], $message_item);
                }
                
                // Đánh dấu đã đọc
                $chat->markConversationAsRead();
                
                if (!headers_sent()) {
                    http_response_code(200);
                }
                echo json_encode($messages_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            } else {
                if (!headers_sent()) {
                    http_response_code(200);
                }
                echo json_encode(array(
                    "success" => true,
                    "message" => "Chưa có tin nhắn nào",
                    "total" => 0,
                    "data" => array()
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
        } else {
            if (!headers_sent()) {
                http_response_code(400);
            }
            echo json_encode(array(
                "success" => false,
                "message" => "Thiếu tham số. Cần course_id hoặc (sender_id và receiver_id)"
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
        
    } catch (Exception $e) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi server: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

// POST: Gửi tin nhắn
elseif ($method === 'POST') {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    // Nếu không parse được JSON, thử dùng $_POST
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }
    
    // Nếu vẫn không có, thử parse lại
    if (empty($data) && !empty($raw_input)) {
        parse_str($raw_input, $data);
    }
    
    $chat->sender_id = isset($data['sender_id']) ? intval($data['sender_id']) : (isset($sender_id) ? $sender_id : null);
    
    // Xử lý receiver_id và course_id
    // Nếu có course_id, đây là group chat - receiver_id phải là NULL
    if (isset($data['course_id']) && !empty($data['course_id']) && intval($data['course_id']) > 0) {
        $chat->course_id = intval($data['course_id']);
        // Trong group chat, receiver_id luôn là NULL (không quan trọng giá trị từ frontend)
        $chat->receiver_id = null;
    } else {
        // Chat 1-1 (không có course_id) - cần receiver_id
        $chat->course_id = null;
        $chat->receiver_id = isset($data['receiver_id']) && $data['receiver_id'] !== null && $data['receiver_id'] !== '' && intval($data['receiver_id']) > 0 ? intval($data['receiver_id']) : null;
    }
    // Map content to message for backward compatibility
    $chat->message = isset($data['content']) ? trim($data['content']) : (isset($data['message']) ? trim($data['message']) : '');
    $chat->content = $chat->message; // For backward compatibility
    
    if (empty($chat->sender_id)) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu sender_id"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    
    if (empty($chat->message)) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Nội dung tin nhắn không được để trống"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    
    // Validation: cần có course_id (group chat) hoặc receiver_id (chat 1-1)
    if (empty($chat->course_id) && empty($chat->receiver_id)) {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Cần có receiver_id (chat 1-1) hoặc course_id (group chat)"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    
    // Validation: Chỉ student mới có thể bắt đầu chat với teacher (chat 1-1)
    if (!empty($chat->receiver_id) && empty($chat->course_id)) {
        // Lấy role của sender và receiver
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$chat->sender_id]);
        $sender = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $stmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
        $stmt->execute([$chat->receiver_id]);
        $receiver = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($sender && $receiver) {
            $sender_role = $sender['role'];
            $receiver_role = $receiver['role'];
            
            // Kiểm tra xem đã có conversation chưa
            $check_stmt = $db->prepare("SELECT COUNT(*) as count FROM chats 
                                        WHERE ((sender_id = ? AND receiver_id = ?) 
                                           OR (sender_id = ? AND receiver_id = ?))
                                        AND course_id IS NULL");
            $check_stmt->execute([$chat->sender_id, $chat->receiver_id, $chat->receiver_id, $chat->sender_id]);
            $check_result = $check_stmt->fetch(PDO::FETCH_ASSOC);
            $has_existing_conversation = $check_result['count'] > 0;
            
            // Nếu teacher muốn bắt đầu chat với student (chưa có conversation)
            if ($sender_role === 'teacher' && $receiver_role === 'student' && !$has_existing_conversation) {
                if (!headers_sent()) {
                    http_response_code(403);
                }
                echo json_encode(array(
                    "success" => false,
                    "message" => "Chỉ học viên mới có thể bắt đầu cuộc trò chuyện với giảng viên"
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                exit();
            }
        }
    }
    
    try {
        if ($chat->create()) {
            // Tạo notification sau khi gửi tin nhắn thành công (non-blocking)
            // Gọi API notification trong background để không block response
            try {
                // Tạo notification data
                $notification_data = array(
                    'sender_id' => $chat->sender_id,
                    'receiver_id' => $chat->receiver_id,
                    'course_id' => $chat->course_id,
                    'content' => $chat->message
                );
                
                // Gọi API notification (non-blocking - không đợi response)
                // Sử dụng file_get_contents với timeout ngắn để không block
                $notification_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') 
                    . '://' . $_SERVER['HTTP_HOST'] . '/api/create-notification.php';
                
                $context = stream_context_create(array(
                    'http' => array(
                        'method' => 'POST',
                        'header' => 'Content-Type: application/json',
                        'content' => json_encode($notification_data),
                        'timeout' => 1, // Timeout 1 giây - không block quá lâu
                        'ignore_errors' => true // Không throw error nếu notification fail
                    )
                ));
                
                // Gọi async (không đợi response)
                @file_get_contents($notification_url, false, $context);
            } catch (Exception $notif_error) {
                // Log lỗi notification nhưng không ảnh hưởng đến response
                error_log("Error creating notification: " . $notif_error->getMessage());
            }
            
            if (!headers_sent()) {
                http_response_code(201);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Gửi tin nhắn thành công",
                "data" => array(
                    "id" => $chat->id,
                    "sender_id" => $chat->sender_id,
                    "receiver_id" => $chat->receiver_id,
                    "course_id" => $chat->course_id,
                    "content" => $chat->message
                )
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode(array(
                "success" => false,
                "message" => "Không thể gửi tin nhắn"
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi server: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

// PUT: Đánh dấu đã đọc
elseif ($method === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        parse_str(file_get_contents("php://input"), $data);
    }
    
    $message_id = isset($data['message_id']) ? intval($data['message_id']) : null;
    
    if ($message_id) {
        $chat->id = $message_id;
        if ($chat->markAsRead()) {
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Đánh dấu đã đọc thành công"
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode(array(
                "success" => false,
                "message" => "Không thể đánh dấu đã đọc"
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
    } else {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu message_id"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

else {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Method không được hỗ trợ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

