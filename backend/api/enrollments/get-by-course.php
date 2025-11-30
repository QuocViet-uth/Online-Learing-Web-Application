<?php
/**
 * File: api/enrollments/get-by-course.php
 * Mục đích: API lấy danh sách enrollments theo course (danh sách học viên trong khóa học)
 * Method: GET
 * Parameters: 
 *   - course_id (required): ID khóa học
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

// Lấy parameters từ GET
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

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

// Lấy enrollments theo course
$enrollment->course_id = $course_id;
$stmt = $enrollment->readByCourse();
$num = $stmt->rowCount();

// Kiểm tra có dữ liệu không
if($num > 0) {
    // Mảng chứa enrollments
    $enrollments_arr = array();
    $enrollments_arr["success"] = true;
    $enrollments_arr["message"] = "Lấy danh sách học viên thành công";
    $enrollments_arr["total"] = $num;
    $enrollments_arr["data"] = array();
    
    // Lấy dữ liệu
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        extract($row);
        
        $enrollment_item = array(
            "id" => intval($id),
            "student_id" => intval($student_id),
            "course_id" => intval($course_id),
            "enrollment_date" => isset($enrolled_at) ? $enrolled_at : null,
            "status" => $status,
            "student_name" => isset($student_name) ? $student_name : null,
            "student_email" => isset($student_email) ? $student_email : null
        );
        
        array_push($enrollments_arr["data"], $enrollment_item);
    }
    
    // Set response code - 200 OK
    if (!headers_sent()) {
        http_response_code(200);
    }
    
    // Hiển thị dữ liệu dạng JSON với UTF-8 encoding
    echo json_encode($enrollments_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} else {
    // Không có dữ liệu - trả về 200 với empty array
    if (!headers_sent()) {
        http_response_code(200);
    }
    
    echo json_encode(array(
        "success" => true,
        "message" => "Không có học viên nào trong khóa học này",
        "total" => 0,
        "data" => array()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

