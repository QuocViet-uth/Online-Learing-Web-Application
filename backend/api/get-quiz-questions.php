<?php
/**
 * File: api/get-quiz-questions.php
 * Mục đích: API lấy danh sách câu hỏi và đáp án của quiz
 * Method: GET
 * Parameters: 
 *   - assignment_id (required): ID của assignment (quiz)
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

try {
    $questionModel = new QuizQuestion($db);
    $answerModel = new QuizAnswer($db);
    
    $questionModel->assignment_id = $assignment_id;
    $stmt = $questionModel->readByAssignment();
    
    $questions = array();
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $question_id = $row['id'];
        
        // Lấy đáp án của câu hỏi
        $answerModel->question_id = $question_id;
        $answerStmt = $answerModel->readByQuestion();
        
        $answers = array();
        while ($answerRow = $answerStmt->fetch(PDO::FETCH_ASSOC)) {
            $answers[] = array(
                "id" => intval($answerRow['id']),
                "answer_text" => $answerRow['answer_text'],
                "is_correct" => (bool)$answerRow['is_correct'],
                "order_number" => intval($answerRow['order_number'])
            );
        }
        
        $questions[] = array(
            "id" => intval($row['id']),
            "question_text" => $row['question_text'],
            "order_number" => intval($row['order_number']),
            "answers" => $answers
        );
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Lấy câu hỏi quiz thành công",
        "data" => $questions
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

