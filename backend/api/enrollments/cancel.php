<?php
/**
 * File: api/enrollments/cancel.php
 * Mục đích: API hủy đăng ký khóa học
 * Method: POST/DELETE
 * Parameters: 
 *   - course_id (required): ID khóa học
 *   - student_id (required): ID học viên
 * Response: JSON
 */

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Enrollment.php';

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

// Lấy dữ liệu từ POST hoặc GET
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Kiểm tra _method để hỗ trợ DELETE
if (isset($data['_method']) && $data['_method'] === 'DELETE') {
    // Xử lý như DELETE request
}

// Validate và lấy dữ liệu
$course_id = isset($data['course_id']) ? intval($data['course_id']) : (isset($_GET['course_id']) ? intval($_GET['course_id']) : 0);
$student_id = isset($data['student_id']) ? intval($data['student_id']) : (isset($_GET['student_id']) ? intval($_GET['student_id']) : 0);

// Validate dữ liệu bắt buộc
if (empty($course_id) || $course_id <= 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Course ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

if (empty($student_id) || $student_id <= 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Student ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra enrollment có tồn tại không
$enrollment->student_id = $student_id;
$enrollment->course_id = $course_id;
if (!$enrollment->readByStudentAndCourse()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy đăng ký khóa học"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Xóa enrollment
if ($enrollment->deleteByStudentAndCourse()) {
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Đã hủy đăng ký khóa học thành công!"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể hủy đăng ký. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

