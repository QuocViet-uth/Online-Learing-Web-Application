<?php
/**
 * File: api/mark-lesson-complete.php
 * Mục đích: API đánh dấu lesson hoàn thành cho student
 * Method: POST
 * Parameters: 
 *   - student_id (required)
 *   - course_id (required)
 *   - lesson_id (required)
 *   - is_completed (required): true/false
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$data = json_decode(file_get_contents("php://input"), true);

if (!$data) {
    $data = $_POST;
}

$student_id = isset($data['student_id']) ? intval($data['student_id']) : 0;
$course_id = isset($data['course_id']) ? intval($data['course_id']) : 0;
$lesson_id = isset($data['lesson_id']) ? intval($data['lesson_id']) : 0;
$is_completed = isset($data['is_completed']) ? (bool)$data['is_completed'] : false;

if ($student_id === 0 || $course_id === 0 || $lesson_id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu thông tin bắt buộc."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Kiểm tra xem progress đã tồn tại chưa
    $stmt_check = $db->prepare("SELECT id, completed FROM progress WHERE student_id = ? AND course_id = ? AND lesson_id = ?");
    $stmt_check->execute([$student_id, $course_id, $lesson_id]);
    $existing = $stmt_check->fetch(PDO::FETCH_ASSOC);
    
    if ($existing) {
        // Cập nhật progress hiện có
        $completion_date = $is_completed ? date('Y-m-d H:i:s') : null;
        $stmt_update = $db->prepare("
            UPDATE progress 
            SET completed = ?, 
                completed_at = ?,
                last_accessed_at = datetime('now')
            WHERE id = ?
        ");
        $stmt_update->execute([$is_completed ? 1 : 0, $completion_date, $existing['id']]);
        
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode(array(
            "success" => true,
            "message" => $is_completed ? "Đã đánh dấu bài học hoàn thành" : "Đã bỏ đánh dấu hoàn thành",
            "data" => array(
                "student_id" => $student_id,
                "course_id" => $course_id,
                "lesson_id" => $lesson_id,
                "is_completed" => $is_completed
            )
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } else {
        // Tạo progress mới
        $completion_date = $is_completed ? date('Y-m-d H:i:s') : null;
        $stmt_insert = $db->prepare("
            INSERT INTO progress (student_id, course_id, lesson_id, completed, completed_at, last_accessed_at)
            VALUES (?, ?, ?, ?, ?, NOW())
        ");
        $stmt_insert->execute([$student_id, $course_id, $lesson_id, $is_completed ? 1 : 0, $completion_date]);
        
        if (!headers_sent()) {
            http_response_code(201);
        }
        echo json_encode(array(
            "success" => true,
            "message" => $is_completed ? "Đã đánh dấu bài học hoàn thành" : "Đã tạo progress",
            "data" => array(
                "student_id" => $student_id,
                "course_id" => $course_id,
                "lesson_id" => $lesson_id,
                "is_completed" => $is_completed
            )
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} catch (PDOException $e) {
    error_log("Mark Lesson Complete API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

