<?php
/**
 * File: api/get-student-progress.php
 * Mục đích: API lấy progress của student cho một course
 * Method: GET
 * Parameters: 
 *   - student_id (required)
 *   - course_id (required)
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

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($student_id === 0 || $course_id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu student_id hoặc course_id."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Lấy tất cả progress của student cho course này
    $stmt = $db->prepare("
        SELECT 
            lesson_id,
            completed as is_completed,
            completed_at as completion_date,
            last_accessed_at as last_accessed
        FROM progress
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->execute([$student_id, $course_id]);
    $progress_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Chuyển đổi thành dạng dễ sử dụng: { lesson_id: { is_completed, ... } }
    $progress_map = array();
    foreach ($progress_list as $progress) {
        $progress_map[$progress['lesson_id']] = array(
            "is_completed" => (bool)$progress['is_completed'],
            "completion_date" => $progress['completion_date'],
            "last_accessed" => $progress['last_accessed']
        );
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Lấy progress thành công",
        "data" => $progress_map
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Student Progress API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

