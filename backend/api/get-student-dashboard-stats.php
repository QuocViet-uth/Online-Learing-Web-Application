<?php
/**
 * File: api/get-student-dashboard-stats.php
 * Mục đích: API lấy thống kê cho student dashboard
 * Method: GET
 * Parameters: 
 *   - student_id (required): ID học viên
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể kết nối database"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (empty($student_id) || $student_id <= 0) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Student ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // 1. Số khóa học đã đăng ký (active)
    $stmt_enrolled = $db->prepare("
        SELECT COUNT(*) as total 
        FROM enrollments 
        WHERE student_id = ? AND status = 'active'
    ");
    $stmt_enrolled->execute([$student_id]);
    $enrolled_courses = intval($stmt_enrolled->fetch(PDO::FETCH_ASSOC)['total']);
    
    // 2. Số bài học đã hoàn thành
    $stmt_completed = $db->prepare("
        SELECT COUNT(*) as total 
        FROM progress 
        WHERE student_id = ? AND completed = 1
    ");
    $stmt_completed->execute([$student_id]);
    $completed_lessons = intval($stmt_completed->fetch(PDO::FETCH_ASSOC)['total']);
    
    // 3. Điểm trung bình từ tất cả submissions đã được chấm
    $stmt_avg_score = $db->prepare("
        SELECT AVG(s.score / a.max_score * 10) as average_score
        FROM submissions s
        INNER JOIN assignments a ON s.assignment_id = a.id
        WHERE s.student_id = ? 
        AND s.status = 'graded' 
        AND s.score IS NOT NULL
    ");
    $stmt_avg_score->execute([$student_id]);
    $avg_row = $stmt_avg_score->fetch(PDO::FETCH_ASSOC);
    $average_score = $avg_row['average_score'] ? round(floatval($avg_row['average_score']), 1) : 0;
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "data" => array(
            "enrolled_courses" => $enrolled_courses,
            "completed_lessons" => $completed_lessons,
            "average_score" => $average_score
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Student Dashboard Stats API - Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

