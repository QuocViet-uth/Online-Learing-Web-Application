<?php
/**
 * File: api/create-lesson.php
 * Mục đích: API tạo bài giảng mới
 * Method: POST
 * Parameters: 
 *   - course_id (required): ID khóa học
 *   - title (required): Tiêu đề bài giảng
 *   - content (optional): Nội dung
 *   - video_url (optional): URL video
 *   - order_number (optional): Thứ tự
 *   - duration (optional): Thời lượng (phút)
 * Response: JSON
 */

// Include headers FIRST to handle OPTIONS preflight requests
require_once __DIR__ . '/../config/headers.php';

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Lesson.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Course.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$lesson = new Lesson($db);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Validate và lấy dữ liệu
$lesson->course_id = isset($data['course_id']) ? intval($data['course_id']) : 0;
$lesson->title = isset($data['title']) ? trim($data['title']) : '';
$lesson->content = isset($data['content']) ? trim($data['content']) : '';
$lesson->video_url = isset($data['video_url']) ? trim($data['video_url']) : '';
$lesson->attachment_file = isset($data['attachment_file']) ? trim($data['attachment_file']) : null;
$lesson->order_number = isset($data['order_number']) ? intval($data['order_number']) : 0;
$lesson->duration = isset($data['duration']) ? intval($data['duration']) : 0;

// Validate dữ liệu bắt buộc
$errors = array();

if (empty($lesson->course_id) || $lesson->course_id <= 0) {
    $errors[] = "Course ID không hợp lệ";
}

if (empty($lesson->title)) {
    $errors[] = "Tiêu đề bài giảng không được để trống";
}

if ($lesson->duration < 0) {
    $errors[] = "Thời lượng phải >= 0";
}

if ($lesson->order_number < 0) {
    $errors[] = "Thứ tự phải >= 0";
}

// Nếu có lỗi, trả về lỗi
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Dữ liệu không hợp lệ",
        "errors" => $errors
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Nếu không có order_number, tự động tạo
if ($lesson->order_number == 0) {
    $query = "SELECT MAX(order_number) as max_order FROM lessons WHERE course_id = :course_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(":course_id", $lesson->course_id);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    $lesson->order_number = ($row['max_order'] ? intval($row['max_order']) : 0) + 1;
}

// Thực hiện tạo bài giảng
if ($lesson->create()) {
    // Lấy thông tin bài giảng vừa tạo
    $new_lesson_id = $lesson->id;
    $lesson->id = $new_lesson_id;
    
    // Lấy thông tin đầy đủ từ database
    if ($lesson->readOne()) {
        // Tạo thông báo cho tất cả students đã đăng ký course (sau khi đã set response)
        // Sử dụng output buffering để tránh "headers already sent"
        try {
            // Lấy thông tin course để lấy teacher_id
            $course = new Course($db);
            $course->id = $lesson->course_id;
            if ($course->readOne()) {
                $notification = new Notification($db);
                $teacher_id = $course->teacher_id;
                $course_name = $course->course_name ? $course->course_name : $course->title;
                
                $title = "Bài giảng mới: " . $lesson->title;
                $content = "Giảng viên đã thêm bài giảng mới \"" . $lesson->title . "\" vào khóa học \"" . $course_name . "\". Hãy vào xem ngay!";
                
                $created_count = $notification->createForCourseStudents($teacher_id, $lesson->course_id, $title, $content);
                // Log vào error log, không output
                if (function_exists('error_log')) {
                    error_log("Created {$created_count} notifications for lesson {$lesson->id} in course {$lesson->course_id}");
                }
            }
        } catch (Exception $e) {
            // Log lỗi nhưng không ảnh hưởng đến response
            if (function_exists('error_log')) {
                error_log("Error creating notification for lesson: " . $e->getMessage());
            }
        }
        
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Tạo bài giảng thành công",
            "data" => array(
                "id" => intval($lesson->id),
                "course_id" => intval($lesson->course_id),
                "title" => $lesson->title,
                "content" => $lesson->content,
                "video_url" => $lesson->video_url ? $lesson->video_url : '',
                "attachment_file" => $lesson->attachment_file ? $lesson->attachment_file : null,
                "order_number" => intval($lesson->order_number),
                "duration" => intval($lesson->duration),
                "created_at" => $lesson->created_at
            )
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        // Nếu không đọc được, vẫn trả về success với thông tin cơ bản
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Tạo bài giảng thành công",
            "data" => array(
                "id" => intval($new_lesson_id),
                "course_id" => intval($lesson->course_id),
                "title" => $lesson->title,
                "content" => $lesson->content,
                "video_url" => $lesson->video_url ? $lesson->video_url : '',
                "attachment_file" => $lesson->attachment_file ? $lesson->attachment_file : null,
                "order_number" => intval($lesson->order_number),
                "duration" => intval($lesson->duration)
            )
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể tạo bài giảng. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>

