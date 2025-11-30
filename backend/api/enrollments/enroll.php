<?php
/**
 * File: api/enrollments/enroll.php
 * Mục đích: API đăng ký khóa học
 * Method: POST
 * Parameters: 
 *   - course_id (required): ID khóa học
 *   - student_id (required): ID học viên
 * Response: JSON
 */

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';
require_once __DIR__ . '/../../models/Course.php';
require_once __DIR__ . '/../../models/Notification.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$enrollment = new Enrollment($db);
$course = new Course($db);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Validate và lấy dữ liệu
$course_id = isset($data['course_id']) ? intval($data['course_id']) : 0;
$student_id = isset($data['student_id']) ? intval($data['student_id']) : 0;

// Validate dữ liệu bắt buộc
$errors = array();

if (empty($course_id) || $course_id <= 0) {
    $errors[] = "Course ID không hợp lệ";
}

if (empty($student_id) || $student_id <= 0) {
    $errors[] = "Student ID không hợp lệ";
}

// Nếu có lỗi, trả về lỗi
if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Dữ liệu không hợp lệ",
        "errors" => $errors
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra course có tồn tại không
$course->id = $course_id;
if (!$course->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy khóa học"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra đã enroll chưa
$enrollment->student_id = $student_id;
$enrollment->course_id = $course_id;
if ($enrollment->exists()) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Bạn đã đăng ký khóa học này rồi!"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra nếu course có giá (price > 0), cần thanh toán trước
// Chỉ course miễn phí (price = 0) mới được enroll trực tiếp qua API này
if ($course->price > 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Khóa học này có phí. Vui lòng thanh toán trước khi đăng ký.",
        "requires_payment" => true,
        "course_price" => floatval($course->price)
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Tạo enrollment (chỉ cho course miễn phí)
$enrollment->status = 'active';

try {
    if ($enrollment->create()) {
        // Lấy thông tin enrollment vừa tạo
        $enrollment->readByStudentAndCourse();
        
        // Tạo thông báo cho teacher khi có học viên đăng ký mới
        try {
            $notification = new Notification($db);
            $teacher_id = $course->teacher_id;
            $course_name = $course->course_name ? $course->course_name : $course->title;
            
            // Lấy thông tin student
            $student_stmt = $db->prepare("SELECT username, full_name FROM users WHERE id = ? LIMIT 1");
            $student_stmt->execute([$student_id]);
            $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
            $student_name = $student ? ($student['full_name'] ? $student['full_name'] : $student['username']) : 'Học viên';
            
            $title = "Học viên đăng ký mới: " . $course_name;
            $content = $student_name . " đã đăng ký khóa học \"" . $course_name . "\" của bạn.";
            
            $notification->sender_id = $student_id; // Student là người gửi
            $notification->receiver_id = $teacher_id;
            $notification->course_id = $course_id;
            $notification->title = $title;
            $notification->content = $content;
            $notification->create();
        } catch (Exception $e) {
            // Log lỗi nhưng không ảnh hưởng đến response
            error_log("Error creating notification for enrollment: " . $e->getMessage());
        }
        
        $enrollment_data = array(
            "id" => intval($enrollment->id),
            "student_id" => intval($enrollment->student_id),
            "course_id" => intval($enrollment->course_id),
            "enrollment_date" => $enrollment->enrolled_at,
            "status" => $enrollment->status,
            "course_name" => $course->course_name,
            "course_title" => $course->title
        );
        
        if (!headers_sent()) {
            http_response_code(201);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Đăng ký khóa học thành công!",
            "data" => $enrollment_data
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể đăng ký khóa học. Vui lòng thử lại sau."
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    error_log("Enroll error: " . $e->getMessage());
    echo json_encode(array(
        "success" => false,
        "message" => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

