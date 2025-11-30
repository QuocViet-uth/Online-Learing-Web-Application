<?php
/**
 * File: api/get-my-quiz-submission.php
 * Mục đích: API lấy bài làm quiz của học viên
 * Method: GET
 * Parameters: 
 *   - assignment_id (required): ID của assignment (quiz)
 *   - student_id (required): ID học viên
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/QuizQuestion.php';
require_once __DIR__ . '/../models/QuizAnswer.php';

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
$student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : 0;

if (empty($assignment_id) || $assignment_id <= 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Assignment ID không hợp lệ"
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

try {
    // Lấy submission
    $stmt = $db->prepare("
        SELECT 
            id,
            assignment_id,
            student_id,
            submitted_at,
            score,
            status,
            created_at
        FROM quiz_submissions
        WHERE assignment_id = ? AND student_id = ?
        LIMIT 1
    ");
    $stmt->execute([$assignment_id, $student_id]);
    $submission = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($submission) {
        // Lấy đáp án học viên đã chọn
        $answersStmt = $db->prepare("
            SELECT question_id, answer_id
            FROM quiz_submission_answers
            WHERE submission_id = ?
        ");
        $answersStmt->execute([$submission['id']]);
        $answers = array();
        while ($row = $answersStmt->fetch(PDO::FETCH_ASSOC)) {
            $answers[$row['question_id']] = intval($row['answer_id']);
        }
        
        // Đếm số câu đúng
        $questionModel = new QuizQuestion($db);
        $answerModel = new QuizAnswer($db);
        
        $questionModel->assignment_id = $assignment_id;
        $stmt = $questionModel->readByAssignment();
        
        $totalQuestions = 0;
        $correctCount = 0;
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $question_id = $row['id'];
            $totalQuestions++;
            
            // Lấy đáp án đúng
            $answerModel->question_id = $question_id;
            $answerStmt = $answerModel->readByQuestion();
            
            $correctAnswerId = null;
            while ($answerRow = $answerStmt->fetch(PDO::FETCH_ASSOC)) {
                if ($answerRow['is_correct']) {
                    $correctAnswerId = intval($answerRow['id']);
                    break;
                }
            }
            
            // Kiểm tra đáp án học viên
            $studentAnswerId = isset($answers[$question_id]) ? $answers[$question_id] : null;
            if ($studentAnswerId && $studentAnswerId === $correctAnswerId) {
                $correctCount++;
            }
        }
        
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Lấy bài làm quiz thành công",
            "data" => array(
                "id" => intval($submission['id']),
                "assignment_id" => intval($submission['assignment_id']),
                "student_id" => intval($submission['student_id']),
                "submitted_at" => $submission['submitted_at'],
                "submit_date" => $submission['submitted_at'], // Alias
                "score" => $submission['score'] !== null ? floatval($submission['score']) : null,
                "status" => $submission['status'],
                "correct_count" => $correctCount,
                "total_questions" => $totalQuestions,
                "answers" => $answers
            )
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Chưa có bài làm",
            "data" => null
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
    
} catch (PDOException $e) {
    error_log("PDO Error in get-my-quiz-submission.php: " . $e->getMessage());
    
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi database: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

