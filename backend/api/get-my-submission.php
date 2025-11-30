<?php
/**
 * File: api/get-my-submission.php
 * Mục đích: API lấy bài nộp của student cho một assignment
 * Method: GET
 * Parameters: 
 *   - assignment_id (required): ID của bài tập
 *   - student_id (required): ID của học viên
 * Response: JSON
 */

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Submission.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$submission = new Submission($db);

// Lấy parameters từ GET
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : 0;
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

// Validate
if (empty($assignment_id) || $assignment_id <= 0) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Assignment ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

if (empty($student_id) || $student_id <= 0) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Student ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Tìm submission
$submission->assignment_id = $assignment_id;
$submission->student_id = $student_id;

$query = "SELECT 
            s.id,
            s.assignment_id,
            s.student_id,
            s.content,
            s.attachment_file,
            s.submitted_at as submit_date,
            s.score,
            s.status,
            s.feedback,
            s.graded_at,
            a.title as assignment_title,
            a.deadline,
            a.max_score
          FROM submissions s
          LEFT JOIN assignments a ON s.assignment_id = a.id
          WHERE s.assignment_id = :assignment_id
            AND s.student_id = :student_id
          LIMIT 1";

$stmt = $db->prepare($query);
$stmt->bindParam(":assignment_id", $assignment_id);
$stmt->bindParam(":student_id", $student_id);
$stmt->execute();

$row = $stmt->fetch(PDO::FETCH_ASSOC);

if ($row) {
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Lấy thông tin bài nộp thành công",
        "data" => array(
            "id" => intval($row['id']),
            "assignment_id" => intval($row['assignment_id']),
            "student_id" => intval($row['student_id']),
            "content" => $row['content'],
            "attachment_file" => $row['attachment_file'],
            "submit_date" => $row['submit_date'],
            "score" => $row['score'] ? floatval($row['score']) : null,
            "status" => $row['status'],
            "feedback" => $row['feedback'],
            "graded_at" => $row['graded_at'],
            "assignment_title" => $row['assignment_title'],
            "deadline" => $row['deadline'],
            "max_score" => floatval($row['max_score'])
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    // Chưa nộp bài
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Chưa nộp bài",
        "data" => null
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>

