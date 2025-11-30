<?php
/**
 * File: api/get-assignment-submissions.php
 * Mục đích: API lấy danh sách bài nộp của một assignment (cho teacher)
 * Method: GET
 * Parameters: 
 *   - assignment_id (required): ID của assignment
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Submission.php';
require_once __DIR__ . '/../models/Assignment.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;

if ($assignment_id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu assignment_id."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

try {
    // Kiểm tra assignment có tồn tại không
    $assignment = new Assignment($db);
    $assignment->id = $assignment_id;
    if (!$assignment->readOne()) {
        if (!headers_sent()) {
            http_response_code(404);
        }
        echo json_encode(array("success" => false, "message" => "Không tìm thấy bài tập."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit();
    }
    
    // Lấy danh sách submissions
    $submission = new Submission($db);
    $submission->assignment_id = $assignment_id;
    $stmt = $submission->readByAssignment();
    
    $submissions = array();
    $graded_count = 0;
    $ungraded_count = 0;
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $is_graded = ($row['status'] === 'graded');
        if ($is_graded) {
            $graded_count++;
        } else {
            $ungraded_count++;
        }
        
        $submissions[] = array(
            "id" => intval($row['id']),
            "assignment_id" => intval($row['assignment_id']),
            "student_id" => intval($row['student_id']),
            "student_name" => $row['student_name'],
            "student_email" => $row['student_email'],
            "content" => $row['content'],
            "attachment_file" => $row['attachment_file'],
            "submit_date" => $row['submit_date'],
            "score" => $row['score'] !== null ? floatval($row['score']) : null,
            "max_score" => floatval($assignment->max_score),
            "status" => $row['status'],
            "feedback" => $row['feedback'],
            "graded_at" => $row['graded_at']
        );
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Lấy danh sách bài nộp thành công",
        "data" => array(
            "assignment" => array(
                "id" => intval($assignment->id),
                "title" => $assignment->title,
                "description" => $assignment->description,
                "max_score" => floatval($assignment->max_score),
                "deadline" => $assignment->deadline
            ),
            "statistics" => array(
                "total_submissions" => count($submissions),
                "graded_count" => $graded_count,
                "ungraded_count" => $ungraded_count
            ),
            "submissions" => $submissions
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    
} catch (PDOException $e) {
    error_log("Get Assignment Submissions API - Exception: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

