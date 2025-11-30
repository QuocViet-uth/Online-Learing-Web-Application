<?php
/**
 * File: api/update-course.php
 * Mục đích: API cập nhật khóa học
 * Method: PUT
 * Parameters: 
 *   - id (required): ID khóa học
 *   - course_name (optional): Mã khóa học
 *   - title (optional): Tiêu đề khóa học
 *   - description (optional): Mô tả
 *   - price (optional): Giá
 *   - start_date (optional): Ngày bắt đầu
 *   - end_date (optional): Ngày kết thúc
 *   - status (optional): Trạng thái
 *   - thumbnail (optional): URL ảnh đại diện
 * Response: JSON
 */

// Set UTF-8 encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
}

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Course.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$course = new Course($db);

// Lấy course ID từ URL hoặc POST
$course_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($_POST['id']) ? intval($_POST['id']) : 0);

if (empty($course_id)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu ID khóa học"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra course có tồn tại không
$course->id = $course_id;
if (!$course->readOne()) {
    http_response_code(404);
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy khóa học"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Lấy dữ liệu từ PUT/POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Lấy method - hỗ trợ method override từ _method parameter
$method = $_SERVER['REQUEST_METHOD'];
if (($method === 'POST' || $method === 'PUT') && isset($data['_method'])) {
    $method = strtoupper($data['_method']);
    unset($data['_method']); // Remove _method from data
}

// Cập nhật các trường được gửi lên
if (isset($data['course_name']) && !empty(trim($data['course_name']))) {
    $course->course_name = trim($data['course_name']);
}

if (isset($data['title']) && !empty(trim($data['title']))) {
    $course->title = trim($data['title']);
}

if (isset($data['description'])) {
    $course->description = trim($data['description']);
}

if (isset($data['price'])) {
    $course->price = floatval($data['price']);
}

if (isset($data['start_date']) && !empty(trim($data['start_date']))) {
    $course->start_date = trim($data['start_date']);
}

if (isset($data['end_date']) && !empty(trim($data['end_date']))) {
    $course->end_date = trim($data['end_date']);
}

if (isset($data['status']) && in_array($data['status'], ['active', 'upcoming', 'closed'])) {
    $course->status = trim($data['status']);
}

if (isset($data['thumbnail'])) {
    $course->thumbnail = trim($data['thumbnail']);
}

if (isset($data['online_link'])) {
    $course->online_link = trim($data['online_link']);
}

// Validate dữ liệu
$errors = array();

if (isset($data['price']) && $course->price < 0) {
    $errors[] = "Giá khóa học phải >= 0";
}

if (isset($data['start_date']) && isset($data['end_date'])) {
    if (strtotime($course->start_date) > strtotime($course->end_date)) {
        $errors[] = "Ngày bắt đầu phải trước ngày kết thúc";
    }
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

// Thực hiện cập nhật
if ($course->update()) {
    // Đọc lại thông tin đã cập nhật
    $course->readOne();
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Cập nhật khóa học thành công",
        "data" => array(
            "id" => intval($course->id),
            "course_name" => $course->course_name,
            "title" => $course->title,
            "description" => $course->description,
            "price" => floatval($course->price),
            "teacher_id" => intval($course->teacher_id),
            "start_date" => $course->start_date,
            "end_date" => $course->end_date,
            "status" => $course->status,
            "thumbnail" => $course->thumbnail ? $course->thumbnail : '',
            "online_link" => $course->online_link ? $course->online_link : null,
            "updated_at" => $course->updated_at,
            "teacher_name" => isset($course->teacher_name) ? $course->teacher_name : null
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể cập nhật khóa học. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>


