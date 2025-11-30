<?php
/**
 * File: api/get-teacher-course-chats.php
 * Mục đích: API lấy danh sách courses của teacher có chat
 * Method: GET
 * Parameters: 
 *   - teacher_id (required): ID của teacher
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/timezone.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;

if ($teacher_id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu teacher_id."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Lấy danh sách courses của teacher có chat (group chat)
    $stmt = $db->prepare("
        SELECT DISTINCT
            c.id,
            c.course_name,
            c.title,
            c.description,
            c.thumbnail,
            COUNT(DISTINCT ch.id) as total_messages,
            COUNT(DISTINCT CASE WHEN ch.sender_id != ? THEN ch.sender_id ELSE NULL END) as total_students,
            MAX(ch.created_at) as last_message_time,
            (SELECT message FROM chats ch2 
             WHERE ch2.course_id = c.id 
             AND ch2.receiver_id IS NULL
             ORDER BY ch2.created_at DESC LIMIT 1) as last_message,
            (SELECT COUNT(*) FROM chats ch3 
             WHERE ch3.course_id = c.id 
             AND ch3.receiver_id IS NULL
             AND ch3.sender_id != ?
             AND ch3.is_read = FALSE) as unread_count
        FROM courses c
        INNER JOIN chats ch ON c.id = ch.course_id
        WHERE c.teacher_id = ?
          AND ch.receiver_id IS NULL
        GROUP BY c.id, c.course_name, c.title, c.description, c.thumbnail
        ORDER BY last_message_time DESC, c.created_at DESC
    ");
    $stmt->execute([$teacher_id, $teacher_id, $teacher_id]);
    $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $courses_data = array();
    foreach ($courses as $course) {
        // Convert last_message_time sang ISO 8601 format với timezone
        $last_message_time = $course['last_message_time'];
        if ($last_message_time) {
            $last_message_time = formatDateTimeISO($last_message_time);
        }
        
        $courses_data[] = array(
            "id" => intval($course['id']),
            "course_name" => $course['course_name'],
            "title" => $course['title'],
            "description" => $course['description'],
            "thumbnail" => $course['thumbnail'],
            "total_students" => intval($course['total_students']),
            "total_messages" => intval($course['total_messages']),
            "unread_count" => intval($course['unread_count']),
            "last_message_time" => $last_message_time,
            "last_message" => $course['last_message']
        );
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Lấy danh sách courses thành công",
        "data" => $courses_data
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Teacher Course Chats API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

