<?php
/**
 * File: api/create-course.php
 * Mục đích: API tạo khóa học mới
 * Method: POST
 * Parameters: 
 *   - course_name (required): Mã khóa học
 *   - title (required): Tiêu đề khóa học
 *   - description (optional): Mô tả
 *   - price (required): Giá
 *   - teacher_id (required): ID giảng viên
 *   - start_date (required): Ngày bắt đầu
 *   - end_date (required): Ngày kết thúc
 *   - status (optional): Trạng thái (active, upcoming, closed)
 *   - thumbnail (optional): URL ảnh đại diện
 * Response: JSON
 */

// Include common headers
require_once __DIR__ . '/../config/headers.php';

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Course.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

// Kiểm tra connection
if (!$db) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể kết nối database. Vui lòng kiểm tra cấu hình database."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

$course = new Course($db);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Debug: Log raw input để kiểm tra
error_log("Raw input: " . $raw_input);
error_log("Parsed data: " . print_r($data, true));

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
    error_log("Using $_POST data: " . print_r($data, true));
}

// Debug: Log dữ liệu nhận được
error_log("Received data keys: " . implode(', ', array_keys($data ? $data : [])));
error_log("Teacher ID received: " . (isset($data['teacher_id']) ? $data['teacher_id'] : 'NOT SET'));

// Validate và lấy dữ liệu
$course->course_name = isset($data['course_name']) ? trim($data['course_name']) : '';
$course->title = isset($data['title']) ? trim($data['title']) : '';
$course->description = isset($data['description']) ? trim($data['description']) : '';
$course->price = isset($data['price']) ? floatval($data['price']) : 0;
$course->teacher_id = isset($data['teacher_id']) ? intval($data['teacher_id']) : 0;
$course->start_date = isset($data['start_date']) ? trim($data['start_date']) : '';
$course->end_date = isset($data['end_date']) ? trim($data['end_date']) : '';
$course->status = isset($data['status']) ? trim($data['status']) : 'upcoming';
$course->thumbnail = isset($data['thumbnail']) ? trim($data['thumbnail']) : '';
$course->online_link = isset($data['online_link']) ? trim($data['online_link']) : '';

// Debug: Log dữ liệu sau khi parse
error_log("Parsed teacher_id: " . $course->teacher_id);
error_log("Course name: " . $course->course_name);
error_log("Title: " . $course->title);

// Validate dữ liệu bắt buộc
$errors = array();

if (empty($course->course_name)) {
    $errors[] = "Mã khóa học không được để trống";
}

if (empty($course->title)) {
    $errors[] = "Tiêu đề khóa học không được để trống";
}

if ($course->price < 0) {
    $errors[] = "Giá khóa học phải >= 0";
}

if (empty($course->teacher_id) || $course->teacher_id <= 0) {
    $errors[] = "Teacher ID không hợp lệ";
}

if (empty($course->start_date)) {
    $errors[] = "Ngày bắt đầu không được để trống";
}

if (empty($course->end_date)) {
    $errors[] = "Ngày kết thúc không được để trống";
}

if (!empty($course->start_date) && !empty($course->end_date)) {
    if (strtotime($course->start_date) > strtotime($course->end_date)) {
        $errors[] = "Ngày bắt đầu phải trước ngày kết thúc";
    }
}

if (!in_array($course->status, ['active', 'upcoming', 'closed'])) {
    $course->status = 'upcoming';
}

// Nếu có lỗi, trả về lỗi
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Dữ liệu không hợp lệ",
        "errors" => $errors
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Thực hiện tạo khóa học
try {
    if ($course->create()) {
        // Lấy thông tin khóa học vừa tạo
        $new_course_id = $course->id;
        
        if (empty($new_course_id)) {
            throw new Exception("Không thể lấy ID của khóa học vừa tạo");
        }
        
        $course->id = $new_course_id;
        
        // Lấy thông tin đầy đủ từ database
        $course_data = array(
            "id" => intval($new_course_id),
            "course_name" => $course->course_name,
            "title" => $course->title,
            "description" => $course->description,
            "price" => floatval($course->price),
            "teacher_id" => intval($course->teacher_id),
            "start_date" => $course->start_date,
            "end_date" => $course->end_date,
            "status" => $course->status,
            "thumbnail" => $course->thumbnail ? $course->thumbnail : '',
            "online_link" => $course->online_link ? $course->online_link : null
        );
        
        if ($course->readOne()) {
            $course_data["created_at"] = $course->created_at;
            $course_data["teacher_name"] = isset($course->teacher_name) ? $course->teacher_name : null;
        }
        
        // Luôn trả về 201 Created khi tạo thành công
        http_response_code(201);
            echo json_encode(array(
                "success" => true,
                "message" => "Tạo khóa học thành công",
                "data" => $course_data
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    } else {
        // Lấy thông tin lỗi từ database
        $error_info = $db->errorInfo();
        $error_message = "Không thể tạo khóa học. Vui lòng thử lại sau.";
        
        if (!empty($error_info[2])) {
            $error_message .= " Chi tiết: " . $error_info[2];
        }
        
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => $error_message,
            "error_info" => $error_info
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    http_response_code(500);
    $error_message = "Lỗi khi tạo khóa học: " . $e->getMessage();
    error_log("Create course error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    echo json_encode(array(
        "success" => false,
        "message" => $error_message,
        "error_details" => $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

