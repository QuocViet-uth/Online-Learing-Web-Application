<?php
/**
 * File: api/get-teacher-dashboard-stats.php
 * Mục đích: API lấy thống kê cho teacher dashboard
 * Method: GET
 * Parameters: 
 *   - teacher_id (required): ID giảng viên
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

$teacher_id = isset($_GET['teacher_id']) ? intval($_GET['teacher_id']) : 0;

if (empty($teacher_id) || $teacher_id <= 0) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Teacher ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // 1. Số khóa học của teacher
    $stmt_courses = $db->prepare("
        SELECT COUNT(*) as total 
        FROM courses 
        WHERE teacher_id = ?
    ");
    $stmt_courses->execute([$teacher_id]);
    $total_courses = intval($stmt_courses->fetch(PDO::FETCH_ASSOC)['total']);
    
    // 2. Tổng số học viên đã đăng ký các khóa học của teacher (unique students)
    $stmt_students = $db->prepare("
        SELECT COUNT(DISTINCT e.student_id) as total 
        FROM enrollments e
        INNER JOIN courses c ON e.course_id = c.id
        WHERE c.teacher_id = ? AND e.status = 'active'
    ");
    $stmt_students->execute([$teacher_id]);
    $total_students = intval($stmt_students->fetch(PDO::FETCH_ASSOC)['total']);
    
    // 3. Tổng số bài tập trong các khóa học của teacher
    $stmt_assignments = $db->prepare("
        SELECT COUNT(*) as total 
        FROM assignments a
        INNER JOIN courses c ON a.course_id = c.id
        WHERE c.teacher_id = ?
    ");
    $stmt_assignments->execute([$teacher_id]);
    $total_assignments = intval($stmt_assignments->fetch(PDO::FETCH_ASSOC)['total']);
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "data" => array(
            "total_courses" => $total_courses,
            "total_students" => $total_students,
            "total_assignments" => $total_assignments
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Teacher Dashboard Stats API - Exception: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

