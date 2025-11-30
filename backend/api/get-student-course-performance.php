<?php
/**
 * File: api/get-student-course-performance.php
 * Mục đích: API lấy hiệu suất học tập của một student cho một course
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
    // Kiểm tra student có đăng ký course không
    $stmt_enrollment = $db->prepare("
        SELECT id, enrolled_at as enrollment_date, status 
        FROM enrollments 
        WHERE student_id = ? AND course_id = ? AND status = 'active'
        LIMIT 1
    ");
    $stmt_enrollment->execute([$student_id, $course_id]);
    $enrollment = $stmt_enrollment->fetch(PDO::FETCH_ASSOC);
    
    if (!$enrollment) {
        if (!headers_sent()) {
            http_response_code(404);
        }
        echo json_encode(array("success" => false, "message" => "Học viên chưa đăng ký khóa học này."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Lấy thông tin course
    $stmt_course = $db->prepare("SELECT id, course_name, title, description FROM courses WHERE id = ?");
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
    
    // Tính điểm trung bình từ submissions có score
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
    
    $overall_progress = 0;
    if ($total_items > 0) {
        $overall_progress = round(($completed_items / $total_items) * 100, 2);
    }
    
    // Tính % cho từng phần
    $lesson_progress_percent = $total_lessons > 0 ? round(($completed_lessons / $total_lessons) * 100, 2) : 0;
    $assignment_progress_percent = $total_assignments > 0 ? round(($submitted_assignments / $total_assignments) * 100, 2) : 0;
    
    // Lấy thời gian học gần nhất
    $stmt_last_activity = $db->prepare("
        SELECT MAX(last_accessed_at) as last_activity 
        FROM progress 
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt_last_activity->execute([$student_id, $course_id]);
    $last_activity_result = $stmt_last_activity->fetch(PDO::FETCH_ASSOC);
    $last_activity = $last_activity_result['last_activity'];
    
    // Lấy danh sách assignments với điểm số
    $stmt_assignments = $db->prepare("
        SELECT 
            a.id,
            a.title,
            a.max_score,
            a.deadline,
            s.id as submission_id,
            s.score,
            s.status as submission_status,
            s.submitted_at as submit_date
        FROM assignments a
        LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
        WHERE a.course_id = ?
        ORDER BY a.deadline ASC
    ");
    $stmt_assignments->execute([$student_id, $course_id]);
    $assignments_list = $stmt_assignments->fetchAll(PDO::FETCH_ASSOC);
    
    // Lấy danh sách lessons với trạng thái hoàn thành
    $stmt_lessons = $db->prepare("
        SELECT 
            l.id,
            l.title,
            l.duration,
            l.order_number,
            p.completed as is_completed,
            p.last_accessed_at as last_accessed
        FROM lessons l
        LEFT JOIN progress p ON l.id = p.lesson_id AND p.student_id = ? AND p.course_id = ?
        WHERE l.course_id = ?
        ORDER BY l.order_number ASC
    ");
    $stmt_lessons->execute([$student_id, $course_id, $course_id]);
    $lessons_list = $stmt_lessons->fetchAll(PDO::FETCH_ASSOC);
    
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
                "title" => $course['title'],
                "description" => $course['description']
            ),
            "enrollment" => array(
                "enrollment_date" => $enrollment['enrollment_date'],
                "status" => $enrollment['status']
            ),
            "statistics" => array(
                "lessons" => array(
                    "completed" => intval($completed_lessons),
                    "total" => intval($total_lessons),
                    "progress_percent" => $lesson_progress_percent
                ),
                "assignments" => array(
                    "submitted" => intval($submitted_assignments),
                    "total" => intval($total_assignments),
                    "progress_percent" => $assignment_progress_percent
                ),
                "average_grade" => $avg_grade,
                "overall_progress" => $overall_progress,
                "last_activity" => $last_activity
            ),
            "lessons" => array_map(function($lesson) {
                return array(
                    "id" => intval($lesson['id']),
                    "title" => $lesson['title'],
                    "duration" => intval($lesson['duration']),
                    "order_number" => intval($lesson['order_number']),
                    "is_completed" => (bool)$lesson['is_completed'],
                    "last_accessed" => $lesson['last_accessed']
                );
            }, $lessons_list),
            "assignments" => array_map(function($assignment) {
                return array(
                    "id" => intval($assignment['id']),
                    "title" => $assignment['title'],
                    "max_score" => floatval($assignment['max_score']),
                    "deadline" => $assignment['deadline'],
                    "submission_id" => $assignment['submission_id'] ? intval($assignment['submission_id']) : null,
                    "score" => $assignment['score'] !== null ? floatval($assignment['score']) : null,
                    "submission_status" => $assignment['submission_status'],
                    "submit_date" => $assignment['submit_date']
                );
            }, $assignments_list)
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Student Course Performance API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server khi lấy hiệu suất học tập: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

