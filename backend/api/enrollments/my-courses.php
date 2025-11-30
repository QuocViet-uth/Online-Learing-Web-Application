<?php
/**
 * File: api/enrollments/my-courses.php
 * Mục đích: API lấy danh sách khóa học của học viên
 * Method: GET
 * Parameters: 
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

// Lấy parameters từ GET
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

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

// Lấy enrollments theo student
$enrollment->student_id = $student_id;
$stmt = $enrollment->readByStudent();
$num = $stmt->rowCount();

// Kiểm tra có dữ liệu không
if($num > 0) {
    // Mảng chứa enrollments
    $enrollments_arr = array();
    $enrollments_arr["success"] = true;
    $enrollments_arr["message"] = "Lấy danh sách khóa học thành công";
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
            "course" => array(
                "id" => intval($course_id),
                "course_name" => $course_name ?? '',
                "title" => $course_title ?? '',
                "price" => floatval($course_price ?? 0),
                "thumbnail" => $thumbnail ?? null,
                "status" => $course_status ?? 'upcoming',
                "start_date" => $start_date ?? null,
                "end_date" => $end_date ?? null
            )
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
        "message" => "Bạn chưa đăng ký khóa học nào",
        "total" => 0,
        "data" => array()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

