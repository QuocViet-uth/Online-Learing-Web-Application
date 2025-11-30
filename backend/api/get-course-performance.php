<?php
/**
 * File: api/get-course-performance.php
 * Mục đích: API lấy hiệu suất của tất cả students trong một course cho teacher
 * Method: GET
 * Parameters: course_id (required)
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

$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if ($course_id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu course_id."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Lấy thông tin course
    $stmt_course = $db->prepare("SELECT id, course_name, title FROM courses WHERE id = ?");
    $stmt_course->execute([$course_id]);
    $course = $stmt_course->fetch(PDO::FETCH_ASSOC);
    
    if (!$course) {
        if (!headers_sent()) {
            http_response_code(404);
        }
        echo json_encode(array("success" => false, "message" => "Không tìm thấy khóa học."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Lấy tổng số lessons trong course
    $stmt_total_lessons = $db->prepare("SELECT COUNT(*) as total FROM lessons WHERE course_id = ?");
    $stmt_total_lessons->execute([$course_id]);
    $total_lessons = $stmt_total_lessons->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Lấy tổng số assignments trong course
    $stmt_total_assignments = $db->prepare("SELECT COUNT(*) as total FROM assignments WHERE course_id = ?");
    $stmt_total_assignments->execute([$course_id]);
    $total_assignments = $stmt_total_assignments->fetch(PDO::FETCH_ASSOC)['total'];
    
    // Lấy danh sách students đã đăng ký course
    $stmt_students = $db->prepare("
        SELECT DISTINCT
            e.student_id,
            u.id,
            u.username,
            u.full_name,
            u.email,
            u.avatar,
            e.enrolled_at as enrollment_date,
            e.status as enrollment_status
        FROM enrollments e
        INNER JOIN users u ON e.student_id = u.id
        WHERE e.course_id = ? AND e.status = 'active'
        ORDER BY u.username
    ");
    $stmt_students->execute([$course_id]);
    $students = $stmt_students->fetchAll(PDO::FETCH_ASSOC);
    
    $performance_data = array();
    
    foreach ($students as $student) {
        $student_id = $student['student_id'];
        
        // Đếm số lessons đã hoàn thành
        $stmt_completed_lessons = $db->prepare("
            SELECT COUNT(*) as total 
            FROM progress 
            WHERE student_id = ? AND course_id = ? AND completed = 1
        ");
        $stmt_completed_lessons->execute([$student_id, $course_id]);
        $completed_lessons = $stmt_completed_lessons->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Đếm số assignments đã nộp
        $stmt_submitted_assignments = $db->prepare("
            SELECT COUNT(DISTINCT assignment_id) as total 
            FROM submissions 
            WHERE student_id = ? AND assignment_id IN (
                SELECT id FROM assignments WHERE course_id = ?
            )
        ");
        $stmt_submitted_assignments->execute([$student_id, $course_id]);
        $submitted_assignments = $stmt_submitted_assignments->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Tính điểm trung bình từ submissions có score (grade)
        $stmt_avg_grade = $db->prepare("
            SELECT AVG(score) as avg_grade 
            FROM submissions 
            WHERE student_id = ? 
            AND assignment_id IN (SELECT id FROM assignments WHERE course_id = ?)
            AND score IS NOT NULL
        ");
        $stmt_avg_grade->execute([$student_id, $course_id]);
        $avg_grade_result = $stmt_avg_grade->fetch(PDO::FETCH_ASSOC);
        $avg_grade = $avg_grade_result['avg_grade'] !== null ? round(floatval($avg_grade_result['avg_grade']), 2) : null;
        
        // Tính tiến độ tổng thể (%)
        // Tiến độ = (số lesson hoàn thành + số assignment đã nộp) / (tổng lesson + tổng assignment) * 100
        $total_items = $total_lessons + $total_assignments;
        $completed_items = $completed_lessons + $submitted_assignments;
        
        if ($total_items > 0) {
            $overall_progress = round(($completed_items / $total_items) * 100, 2);
        } else {
            $overall_progress = 0;
        }
        
        // Lấy thời gian học gần nhất
        $stmt_last_activity = $db->prepare("
            SELECT MAX(last_accessed_at) as last_activity 
            FROM progress 
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt_last_activity->execute([$student_id, $course_id]);
        $last_activity_result = $stmt_last_activity->fetch(PDO::FETCH_ASSOC);
        $last_activity = $last_activity_result['last_activity'];
        
        $performance_data[] = array(
            "student_id" => intval($student_id),
            "username" => $student['username'],
            "full_name" => isset($student['full_name']) ? $student['full_name'] : null,
            "email" => $student['email'],
            "avatar" => $student['avatar'],
            "enrollment_date" => $student['enrollment_date'],
            "enrollment_status" => $student['enrollment_status'],
            "lessons" => array(
                "completed" => intval($completed_lessons),
                "total" => intval($total_lessons),
                "progress_percent" => $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100, 2) : 0
            ),
            "assignments" => array(
                "submitted" => intval($submitted_assignments),
                "total" => intval($total_assignments),
                "progress_percent" => $total_assignments > 0 ? round(($submitted_assignments / $total_assignments) * 100, 2) : 0
            ),
            "average_grade" => $avg_grade !== null ? floatval($avg_grade) : null,
            "overall_progress" => floatval($overall_progress),
            "last_activity" => $last_activity
        );
    }
    
    // Sắp xếp theo overall_progress giảm dần
    usort($performance_data, function($a, $b) {
        return $b['overall_progress'] <=> $a['overall_progress'];
    });
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Lấy hiệu suất học tập thành công",
        "data" => array(
            "course" => array(
                "id" => intval($course['id']),
                "course_name" => $course['course_name'],
                "title" => $course['title']
            ),
            "statistics" => array(
                "total_students" => count($performance_data),
                "total_lessons" => intval($total_lessons),
                "total_assignments" => intval($total_assignments)
            ),
            "students" => $performance_data
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Course Performance API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server khi lấy hiệu suất học tập: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

