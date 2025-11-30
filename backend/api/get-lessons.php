<?php
/**
 * File: api/get-lessons.php
 * Mục đích: API lấy danh sách bài giảng
 * Method: GET
 * Parameters: 
 *   - course_id (required): ID của khóa học
 *   - lesson_id (optional): Lấy chi tiết 1 bài giảng
 * Response: JSON
 */

// Set UTF-8 encoding (if mbstring is available)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
}

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Lesson.php';

// Khởi tạo database và lesson object
$database = new Database();
$db = $database->getConnection();

$lesson = new Lesson($db);

// Lấy parameters từ GET
$course_id = isset($_GET['course_id']) ? $_GET['course_id'] : '';
$lesson_id = isset($_GET['lesson_id']) ? $_GET['lesson_id'] : '';

// Nếu có lesson_id, lấy chi tiết 1 bài giảng
if(!empty($lesson_id)) {
    $lesson->id = $lesson_id;
    
    if($lesson->readOne()) {
        // Set response code - 200 OK
        http_response_code(200);
        
        $lesson_arr = array(
            "success" => true,
            "message" => "Lấy thông tin bài giảng thành công",
            "data" => array(
                "id" => $lesson->id,
                "course_id" => $lesson->course_id,
                "course_name" => $lesson->course_name,
                "title" => $lesson->title,
                "content" => $lesson->content,
                "video_url" => $lesson->video_url,
                "order_number" => intval($lesson->order_number),
                "duration" => intval($lesson->duration),
                "created_at" => $lesson->created_at
            )
        );
        
        echo json_encode($lesson_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        // Không tìm thấy bài giảng
        http_response_code(404);
        
        echo json_encode(array(
            "success" => false,
            "message" => "Không tìm thấy bài giảng với ID: " . $lesson_id
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} elseif(!empty($course_id)) {
    // Lấy tất cả bài giảng của 1 khóa học
    $lesson->course_id = $course_id;
    $stmt = $lesson->readByCourse();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $lessons_arr = array();
        $lessons_arr["success"] = true;
        $lessons_arr["message"] = "Lấy danh sách bài giảng thành công";
        $lessons_arr["total"] = $num;
        $lessons_arr["course_id"] = intval($course_id);
        
        // Thống kê
        $lessons_arr["statistics"] = array(
            "total_lessons" => $lesson->countByCourse(),
            "total_duration" => $lesson->getTotalDuration() . " phút"
        );
        
        $lessons_arr["data"] = array();
        
        // Lấy dữ liệu
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $lesson_item = array(
                "id" => $id,
                "course_id" => $course_id,
                "course_name" => $course_name,
                "title" => $title,
                "content" => $content,
                "video_url" => $video_url,
                "attachment_file" => isset($attachment_file) ? $attachment_file : null,
                "order_number" => intval($order_number),
                "duration" => intval($duration),
                "created_at" => $created_at
            );
            
            array_push($lessons_arr["data"], $lesson_item);
        }
        
        // Set response code - 200 OK
        http_response_code(200);
        
        echo json_encode($lessons_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
    } else {
        // Không có bài giảng - trả về 200 với empty array
        http_response_code(200);
        
        echo json_encode(array(
            "success" => true,
            "message" => "Khóa học này chưa có bài giảng nào",
            "total" => 0,
            "course_id" => intval($course_id),
            "data" => array()
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} else {
    // Thiếu parameter course_id
    http_response_code(400);
    
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu tham số course_id"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>