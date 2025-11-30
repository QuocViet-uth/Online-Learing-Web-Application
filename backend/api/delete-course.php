<?php
/**
 * File: api/delete-course.php
 * Mục đích: API xóa khóa học
 * Method: DELETE
 * Parameters: 
 *   - id (required): ID khóa học
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

// Lấy course ID từ URL, POST hoặc JSON body
$course_id = 0;

// Thử lấy từ GET
if (isset($_GET['id'])) {
    $course_id = intval($_GET['id']);
}

// Thử lấy từ POST
if ($course_id === 0 && isset($_POST['id'])) {
    $course_id = intval($_POST['id']);
}

// Thử lấy từ JSON body
if ($course_id === 0) {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    if ($data && isset($data['id'])) {
        $course_id = intval($data['id']);
    }
}

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

// Thực hiện xóa
if ($course->delete()) {
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Xóa khóa học thành công"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể xóa khóa học. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>

