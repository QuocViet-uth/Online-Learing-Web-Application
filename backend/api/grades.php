<?php
/**
 * File: api/grades.php
 * Mục đích: API lấy điểm của học viên
 * Method: GET
 * Parameters: 
 *   - student_id (required): ID của học viên
 *   - course_id (optional): Lọc theo khóa học
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

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

// Lấy parameters
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

if($student_id <= 0) {
    // Thiếu hoặc sai student_id
    http_response_code(400);
    
    echo json_encode(array(
        "success" => false,
        "message" => "Thiếu hoặc sai định dạng tham số student_id"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Build query
if($course_id > 0) {
    // Lọc theo course_id
    $query = "SELECT 
                s.id,
                s.assignment_id,
                s.student_id,
                s.submitted_at as submit_date,
                s.score,
                s.status,
                s.feedback,
                s.graded_at,
                a.title as assignment_title,
                a.max_score,
                a.deadline,
                a.course_id,
                c.course_name
              FROM submissions s
              LEFT JOIN assignments a ON s.assignment_id = a.id
              LEFT JOIN courses c ON a.course_id = c.id
              WHERE s.student_id = :student_id
                AND a.course_id = :course_id
              ORDER BY s.submitted_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
    $stmt->bindParam(":course_id", $course_id, PDO::PARAM_INT);
    
} else {
    // Lấy tất cả
    $query = "SELECT 
                s.id,
                s.assignment_id,
                s.student_id,
                s.submitted_at as submit_date,
                s.score,
                s.status,
                s.feedback,
                s.graded_at,
                a.title as assignment_title,
                a.max_score,
                a.deadline,
                a.course_id,
                c.course_name
              FROM submissions s
              LEFT JOIN assignments a ON s.assignment_id = a.id
              LEFT JOIN courses c ON a.course_id = c.id
              WHERE s.student_id = :student_id
              ORDER BY s.submitted_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(":student_id", $student_id, PDO::PARAM_INT);
}

// Execute query
try {
    $stmt->execute();
    $num = $stmt->rowCount();
    
    if($num > 0) {
        $grades_arr = array();
        $grades_arr["success"] = true;
        $grades_arr["message"] = "Lấy điểm thành công";
        $grades_arr["student_id"] = $student_id;
        
        if($course_id > 0) {
            $grades_arr["course_id"] = $course_id;
        }
        
        $grades_arr["total_submissions"] = $num;
        $grades_arr["data"] = array();
        
        $total_score = 0;
        $total_max_score = 0;
        $graded_count = 0;
        
        while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $grade_item = array(
                "submission_id" => intval($row['id']),
                "assignment_id" => intval($row['assignment_id']),
                "assignment_title" => $row['assignment_title'],
                "course_id" => intval($row['course_id']),
                "course_name" => $row['course_name'],
                "submit_date" => $row['submit_date'],
                "score" => $row['score'] !== null ? floatval($row['score']) : null,
                "max_score" => floatval($row['max_score']),
                "status" => $row['status'],
                "feedback" => $row['feedback'],
                "graded_at" => $row['graded_at']
            );
            
            // Tính tổng điểm
            if($row['score'] !== null) {
                $total_score += floatval($row['score']);
                $total_max_score += floatval($row['max_score']);
                $graded_count++;
            }
            
            array_push($grades_arr["data"], $grade_item);
        }
        
        // Thêm thống kê
        $grades_arr["statistics"] = array(
            "total_assignments" => $num,
            "graded_assignments" => $graded_count,
            "ungraded_assignments" => $num - $graded_count,
            "total_score" => round($total_score, 2),
            "total_max_score" => round($total_max_score, 2),
            "average_score" => $graded_count > 0 ? round($total_score / $graded_count, 2) : 0,
            "percentage" => $total_max_score > 0 ? round(($total_score / $total_max_score) * 100, 2) : 0
        );
        
        http_response_code(200);
        echo json_encode($grades_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        
    } else {
        http_response_code(404);
        
        echo json_encode(array(
            "success" => false,
            "message" => "Học viên chưa nộp bài tập nào",
            "student_id" => $student_id,
            "total_submissions" => 0,
            "data" => array()
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
} catch(PDOException $e) {
    http_response_code(500);
    
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>