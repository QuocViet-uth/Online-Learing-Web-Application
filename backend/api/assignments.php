<?php
/**
 * File: api/assignments.php
 * Mục đích: API lấy danh sách bài tập
 * Method: GET
 * Parameters: 
 *   - course_id (optional): Lọc theo khóa học
 *   - id (optional): Lấy chi tiết 1 bài tập
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
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Assignment.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$assignment = new Assignment($db);

// Lấy parameters từ GET
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if(!empty($assignment_id)) {
    // Lấy chi tiết 1 bài tập
    $assignment->id = $assignment_id;
    
    if($assignment->readOne()) {
        $assignment_arr = array();
        $assignment_arr["success"] = true;
        $assignment_arr["message"] = "Lấy thông tin bài tập thành công";
        $assignment_arr["data"] = array(
            "id" => intval($assignment->id),
            "course_id" => intval($assignment->course_id),
            "course_name" => $assignment->course_name,
            "title" => $assignment->title,
            "description" => $assignment->description,
            "type" => isset($assignment->type) ? $assignment->type : 'homework',
            "time_limit" => isset($assignment->time_limit) && $assignment->time_limit > 0 ? intval($assignment->time_limit) : null,
            "assignment_type" => isset($assignment->type) ? $assignment->type : 'homework', // Backward compatibility
            "attachment_file" => null, // Column does not exist in database
            "start_date" => isset($assignment->start_date) && !empty($assignment->start_date) ? $assignment->start_date : null,
            "deadline" => $assignment->deadline,
            "max_score" => floatval($assignment->max_score),
            "allow_late_submission" => true, // Column does not exist in database, default to true
            "created_at" => $assignment->created_at,
            "updated_at" => $assignment->updated_at
        );
        
        http_response_code(200);
        echo json_encode($assignment_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        // Không tìm thấy bài tập
        http_response_code(404);
        
        echo json_encode(array(
            "success" => false,
            "message" => "Không tìm thấy bài tập với ID: " . $assignment_id
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} elseif(!empty($course_id)) {
    // Lấy tất cả bài tập của 1 khóa học
    $assignment->course_id = $course_id;
    $stmt = $assignment->readByCourse();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $assignments_arr = array();
        $assignments_arr["success"] = true;
        $assignments_arr["message"] = "Lấy danh sách bài tập thành công";
        $assignments_arr["total"] = $num;
        $assignments_arr["course_id"] = intval($course_id);
        $assignments_arr["data"] = array();
        
        // Lấy dữ liệu
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            extract($row);
            
            $assignment_item = array(
                "id" => intval($id),
                "course_id" => intval($course_id),
                "course_name" => $course_name,
                "title" => $title,
                "description" => $description,
                "type" => isset($type) ? $type : 'homework',
                "time_limit" => isset($time_limit) && $time_limit > 0 ? intval($time_limit) : null,
                "assignment_type" => isset($type) ? $type : 'homework', // Backward compatibility
                "attachment_file" => null, // Column does not exist in database
                "start_date" => isset($start_date) && !empty($start_date) ? $start_date : null,
                "deadline" => $deadline,
                "max_score" => floatval($max_score),
                "allow_late_submission" => true, // Column does not exist in database, default to true
                "created_at" => $created_at
            );
            
            array_push($assignments_arr["data"], $assignment_item);
        }
        
        // Set response code - 200 OK
        http_response_code(200);
        
        echo json_encode($assignments_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
    } else {
        // Không có bài tập - trả về 200 với empty array
        http_response_code(200);
        
        echo json_encode(array(
            "success" => true,
            "message" => "Khóa học này chưa có bài tập nào",
            "total" => 0,
            "course_id" => intval($course_id),
            "data" => array()
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} else {
    // Thiếu parameter
    http_response_code(400);
    
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu tham số. Cần course_id hoặc id"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>


