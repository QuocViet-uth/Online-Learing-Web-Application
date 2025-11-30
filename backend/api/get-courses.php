<?php
/**
 * File: api/get-courses.php
 * Mục đích: API lấy danh sách khóa học
 * Method: GET
 * Parameters: 
 *   - status (optional): Lọc theo trạng thái (active, upcoming, closed)
 *   - teacher_id (optional): Lọc theo giảng viên
 * Response: JSON
 */

// Tắt error display để tránh output HTML trước JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Include common headers
require_once __DIR__ . '/../config/headers.php';

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/Lesson.php';

// Khởi tạo database và course object
$database = new Database();
$db = $database->getConnection();

// Kiểm tra database connection
if ($db === null) {
    http_response_code(503);
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed. Please check database configuration.',
        'error' => 'Service Unavailable'
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

$course = new Course($db);
$lesson = new Lesson($db);

// Tự động cập nhật trạng thái khóa học dựa trên ngày bắt đầu và kết thúc
// Bỏ qua nếu có lỗi (đã được handle trong method)
$course->updateStatusAutomatically();

// Lấy parameters từ GET
$status = isset($_GET['status']) ? $_GET['status'] : '';
$teacher_id = isset($_GET['teacher_id']) ? $_GET['teacher_id'] : '';

// Thực hiện query dựa trên parameters
if(!empty($teacher_id)) {
    // Lấy courses theo teacher
    $course->teacher_id = $teacher_id;
    $stmt = $course->readByTeacher();
} elseif(!empty($status)) {
    // Lấy courses theo status
    $course->status = $status;
    $stmt = $course->readByStatus();
} else {
    // Lấy tất cả courses
    $stmt = $course->readAll();
}

$num = $stmt->rowCount();

// Kiểm tra có dữ liệu không
if($num > 0) {
    // Mảng chứa courses
    $courses_arr = array();
    $courses_arr["success"] = true;
    $courses_arr["message"] = "Lấy danh sách khóa học thành công";
    $courses_arr["total"] = $num;
    $courses_arr["data"] = array();
    
    // Lấy dữ liệu
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        // Lấy danh sách lessons cho course này
        $lesson->course_id = $id;
        $lessons_stmt = $lesson->readByCourse();
        $lessons_count = $lessons_stmt->rowCount();
        
        $lessons_array = array();
        $total_duration = 0;
        
        if($lessons_count > 0) {
            while($lesson_row = $lessons_stmt->fetch(PDO::FETCH_ASSOC)) {
                $lesson_item = array(
                    "id" => intval($lesson_row['id']),
                    "course_id" => intval($lesson_row['course_id']),
                    "title" => $lesson_row['title'] ?? '',
                    "content" => $lesson_row['content'] ?? '',
                    "video_url" => $lesson_row['video_url'] ?? null,
                    "order_number" => intval($lesson_row['order_number']),
                    "duration" => intval($lesson_row['duration']),
                    "created_at" => $lesson_row['created_at']
                );
                
                $total_duration += intval($lesson_row['duration']);
                array_push($lessons_array, $lesson_item);
            }
        }
        
        // Lấy số lượng học viên đã đăng ký (enrollments)
        $stmt_enrollments = $db->prepare("SELECT COUNT(*) as total FROM enrollments WHERE course_id = ? AND status = 'active'");
        $stmt_enrollments->execute([$id]);
        $enrollments_count = $stmt_enrollments->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Lấy số lượng assignments
        $stmt_assignments = $db->prepare("SELECT COUNT(*) as total FROM assignments WHERE course_id = ?");
        $stmt_assignments->execute([$id]);
        $assignments_count = $stmt_assignments->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Thống kê lessons
        $lessons_stats = array(
            "total_lessons" => $lessons_count,
            "total_duration" => $total_duration > 0 ? $total_duration . " phút" : "0 phút"
        );
        
        // Lấy thống kê reviews
        $review_stats = array(
            "average_rating" => 0,
            "total_reviews" => 0
        );
        try {
            $stmt_reviews = $db->prepare("SELECT AVG(rating) as average_rating, COUNT(*) as total_reviews 
                                         FROM reviews 
                                         WHERE course_id = ?");
            $stmt_reviews->execute([$id]);
            $review_row = $stmt_reviews->fetch(PDO::FETCH_ASSOC);
            if ($review_row && $review_row['total_reviews'] > 0) {
                $review_stats["average_rating"] = round(floatval($review_row['average_rating']), 1);
                $review_stats["total_reviews"] = intval($review_row['total_reviews']);
            }
        } catch (Exception $e) {
            // Ignore error, use default values
            error_log("Error getting review stats: " . $e->getMessage());
        }
        
        $course_item = array(
            "id" => intval($id),
            "course_name" => $course_name ?? '',
            "title" => $title ?? '',
            "description" => $description ?? '',
            "price" => floatval($price),
            "teacher_id" => intval($teacher_id),
            "teacher_name" => isset($teacher_name) ? $teacher_name : null,
            "teacher_full_name" => isset($teacher_full_name) ? $teacher_full_name : null,
            "teacher_avatar" => isset($teacher_avatar) ? $teacher_avatar : null,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "status" => $status,
            "thumbnail" => $thumbnail,
            "online_link" => isset($online_link) ? $online_link : null,
            "created_at" => $created_at,
            "reviews" => $review_stats,
            "lessons" => array(
                "total" => $lessons_count,
                "total_duration" => $total_duration,
                "statistics" => $lessons_stats,
                "data" => $lessons_array
            ),
            "enrollments_count" => intval($enrollments_count),
            "assignments_count" => intval($assignments_count)
        );
        
        array_push($courses_arr["data"], $course_item);
    }
    
    // Set response code - 200 OK
    if (!headers_sent()) {
        http_response_code(200);
    }
    
    // Hiển thị dữ liệu dạng JSON với UTF-8 encoding
    echo json_encode($courses_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} else {
    // Không có dữ liệu - trả về 200 với empty array (không phải 404)
    if (!headers_sent()) {
        http_response_code(200);
    }
    
    echo json_encode(array(
        "success" => true,
        "message" => "Không tìm thấy khóa học nào",
        "total" => 0,
        "data" => array()
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>