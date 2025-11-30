<?php
/**
 * File: api/submit-quiz.php
 * Mục đích: API nộp bài quiz và tính điểm tự động
 * Method: POST
 * Parameters: 
 *   - assignment_id (required): ID của assignment (quiz)
 *   - student_id (required): ID học viên
 *   - answers (required): Object { questionId: answerId }
 *   - time_spent (optional): Thời gian làm bài (giây)
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Assignment.php';
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

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : (isset($data['assignment_id']) ? intval($data['assignment_id']) : 0);
$student_id = isset($data['student_id']) ? intval($data['student_id']) : 0;
$answers = isset($data['answers']) ? $data['answers'] : array();
$time_spent = isset($data['time_spent']) ? intval($data['time_spent']) : null;

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
    // Kiểm tra assignment có tồn tại và là quiz không
    $assignment = new Assignment($db);
    $assignment->id = $assignment_id;
    if (!$assignment->readOne()) {
        if (!headers_sent()) {
            http_response_code(404);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không tìm thấy bài quiz"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    
    if ($assignment->type !== 'quiz') {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Đây không phải là bài quiz"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    
    // Kiểm tra đã nộp chưa
    $checkSubmission = $db->prepare("SELECT id, status FROM quiz_submissions WHERE assignment_id = ? AND student_id = ?");
    $checkSubmission->execute([$assignment_id, $student_id]);
    $existingSubmission = $checkSubmission->fetch(PDO::FETCH_ASSOC);
    
    if ($existingSubmission && $existingSubmission['status'] !== 'in_progress') {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Bạn đã nộp bài quiz này rồi"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    
    // Lấy tất cả câu hỏi và đáp án đúng
    $questionModel = new QuizQuestion($db);
    $answerModel = new QuizAnswer($db);
    
    $questionModel->assignment_id = $assignment_id;
    $stmt = $questionModel->readByAssignment();
    
    $questions = array();
    $correctAnswers = array(); // { questionId: correctAnswerId }
    
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $question_id = $row['id'];
        $questions[] = $question_id;
        
        // Lấy đáp án đúng
        $answerModel->question_id = $question_id;
        $answerStmt = $answerModel->readByQuestion();
        
        while ($answerRow = $answerStmt->fetch(PDO::FETCH_ASSOC)) {
            if ($answerRow['is_correct']) {
                $correctAnswers[$question_id] = intval($answerRow['id']);
                break;
            }
        }
    }
    
    // Tính điểm
    $totalQuestions = count($questions);
    $correctCount = 0;
    
    foreach ($questions as $question_id) {
        $studentAnswer = isset($answers[$question_id]) ? intval($answers[$question_id]) : null;
        $correctAnswer = isset($correctAnswers[$question_id]) ? $correctAnswers[$question_id] : null;
        
        if ($studentAnswer && $studentAnswer === $correctAnswer) {
            $correctCount++;
        }
    }
    
    $score = $totalQuestions > 0 ? ($correctCount / $totalQuestions) * $assignment->max_score : 0;
    $score = round($score, 2);
    
    // Tạo hoặc cập nhật submission
    if ($existingSubmission) {
        // Cập nhật submission hiện có
        $submission_id = $existingSubmission['id'];
        $updateSubmission = $db->prepare("
            UPDATE quiz_submissions 
            SET submitted_at = NOW(), 
                score = ?, 
                status = 'submitted',
                updated_at = NOW()
            WHERE id = ?
        ");
        $updateSubmission->execute([$score, $submission_id]);
        
        // Xóa đáp án cũ
        $deleteAnswers = $db->prepare("DELETE FROM quiz_submission_answers WHERE submission_id = ?");
        $deleteAnswers->execute([$submission_id]);
    } else {
        // Tạo submission mới
        $createSubmission = $db->prepare("
            INSERT INTO quiz_submissions (assignment_id, student_id, submitted_at, score, status)
            VALUES (?, ?, NOW(), ?, 'submitted')
        ");
        $createSubmission->execute([$assignment_id, $student_id, $score]);
        $submission_id = $db->lastInsertId();
    }
    
    // Lưu đáp án học viên chọn
    foreach ($questions as $question_id) {
        $studentAnswer = isset($answers[$question_id]) ? intval($answers[$question_id]) : null;
        if ($studentAnswer) {
            $insertAnswer = $db->prepare("
                INSERT INTO quiz_submission_answers (submission_id, question_id, answer_id)
                VALUES (?, ?, ?)
            ");
            $insertAnswer->execute([$submission_id, $question_id, $studentAnswer]);
        }
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Nộp bài quiz thành công",
        "data" => array(
            "submission_id" => intval($submission_id),
            "score" => $score,
            "max_score" => floatval($assignment->max_score),
            "correct_count" => $correctCount,
            "total_questions" => $totalQuestions,
            "percentage" => $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    error_log("PDO Error in submit-quiz.php: " . $e->getMessage());
    
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi database: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    error_log("Error in submit-quiz.php: " . $e->getMessage());
    
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

