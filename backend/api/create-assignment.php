<?php
/**
 * File: api/create-assignment.php
 * Mục đích: API tạo bài tập mới
 * Method: POST
 * Parameters: 
 *   - course_id (required): ID khóa học
 *   - title (required): Tiêu đề bài tập
 *   - description (optional): Mô tả
 *   - assignment_type (optional): Loại bài tập
 *   - attachment_file (optional): File đính kèm
 *   - deadline (required): Hạn nộp
 *   - max_score (optional): Điểm tối đa
 * Response: JSON
 */

// Include headers FIRST to handle OPTIONS preflight requests
require_once __DIR__ . '/../config/headers.php';

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Assignment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../models/QuizQuestion.php';
require_once __DIR__ . '/../models/QuizAnswer.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$assignment = new Assignment($db);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Validate và lấy dữ liệu
$assignment->course_id = isset($data['course_id']) ? intval($data['course_id']) : 0;
$assignment->title = isset($data['title']) ? trim($data['title']) : '';
$assignment->description = isset($data['description']) ? trim($data['description']) : '';

// Debug: Log toàn bộ dữ liệu nhận được
error_log("Received data from frontend: " . print_r($data, true));
error_log("Received type from frontend: " . (isset($data['type']) ? var_export($data['type'], true) : 'NOT SET'));

// Xử lý type - đảm bảo nhận đúng giá trị
$assignment->type = 'homework'; // Default
if (isset($data['type'])) {
    $typeValue = trim($data['type']);
    if (in_array($typeValue, ['homework', 'quiz'], true)) {
        $assignment->type = $typeValue;
        error_log("Setting assignment type to: " . $assignment->type);
    } else {
        error_log("Invalid type value received: " . var_export($typeValue, true));
    }
} else {
    error_log("Type not set in data, using default: homework");
}

// Xử lý time_limit - chuyển chuỗi rỗng thành null
$assignment->time_limit = null;
if (isset($data['time_limit']) && $data['time_limit'] !== '' && $data['time_limit'] !== null) {
    $timeLimit = intval($data['time_limit']);
    if ($timeLimit > 0) {
        $assignment->time_limit = $timeLimit;
    }
}

        // Debug: Log giá trị type sau khi xử lý
        error_log("API - Assignment type after processing: " . $assignment->type);
        error_log("API - Assignment time_limit: " . ($assignment->time_limit ?? 'NULL'));
$assignment->start_date = isset($data['start_date']) && !empty($data['start_date']) ? trim($data['start_date']) : null;
$assignment->deadline = isset($data['deadline']) ? trim($data['deadline']) : '';
$assignment->max_score = isset($data['max_score']) ? floatval($data['max_score']) : 100;

// Validate dữ liệu bắt buộc
$errors = array();

if (empty($assignment->course_id) || $assignment->course_id <= 0) {
    $errors[] = "Course ID không hợp lệ";
}

if (empty($assignment->title)) {
    $errors[] = "Tiêu đề bài tập không được để trống";
}

if (empty($assignment->deadline)) {
    $errors[] = "Hạn nộp không được để trống";
}

// Validate start_date < deadline nếu có start_date
if (!empty($assignment->start_date) && !empty($assignment->deadline)) {
    if (strtotime($assignment->start_date) >= strtotime($assignment->deadline)) {
        $errors[] = "Thời gian bắt đầu phải trước hạn nộp";
    }
}

if ($assignment->max_score < 0) {
    $errors[] = "Điểm tối đa phải >= 0";
}

// Nếu có lỗi, trả về lỗi
if (!empty($errors)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Dữ liệu không hợp lệ",
        "errors" => $errors
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Validate quiz questions nếu là quiz
if ($assignment->type === 'quiz') {
    if (!isset($data['questions']) || !is_array($data['questions']) || empty($data['questions'])) {
        $errors[] = "Quiz phải có ít nhất 1 câu hỏi";
    } else {
        foreach ($data['questions'] as $index => $question) {
            if (empty($question['question_text'])) {
                $errors[] = "Câu hỏi " . ($index + 1) . " không được để trống";
            }
            if (!isset($question['answers']) || !is_array($question['answers']) || count($question['answers']) !== 4) {
                $errors[] = "Câu hỏi " . ($index + 1) . " phải có đúng 4 đáp án";
            } else {
                $has_correct = false;
                foreach ($question['answers'] as $ansIndex => $answer) {
                    if (empty($answer['answer_text'])) {
                        $errors[] = "Đáp án " . ($ansIndex + 1) . " của câu hỏi " . ($index + 1) . " không được để trống";
                    }
                    if (isset($answer['is_correct']) && $answer['is_correct']) {
                        $has_correct = true;
                    }
                }
                if (!$has_correct) {
                    $errors[] = "Câu hỏi " . ($index + 1) . " phải có ít nhất 1 đáp án đúng";
                }
            }
        }
    }
}

// Nếu có lỗi, trả về lỗi
if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Dữ liệu không hợp lệ",
        "errors" => $errors
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Thực hiện tạo bài tập
try {
    if ($assignment->create()) {
        // Lấy thông tin bài tập vừa tạo
        $new_assignment_id = $assignment->id;
        $assignment->id = $new_assignment_id;
        
        // Nếu là quiz, tạo questions và answers
        if ($assignment->type === 'quiz' && isset($data['questions']) && is_array($data['questions'])) {
            try {
                $questionModel = new QuizQuestion($db);
                $answerModel = new QuizAnswer($db);
                
                foreach ($data['questions'] as $order => $questionData) {
                    $questionModel->assignment_id = $new_assignment_id;
                    $questionModel->question_text = trim($questionData['question_text']);
                    $questionModel->order_number = $order;
                    
                    if ($questionModel->create()) {
                        $question_id = $questionModel->id;
                        
                        // Tạo 4 đáp án
                        if (isset($questionData['answers']) && is_array($questionData['answers'])) {
                            foreach ($questionData['answers'] as $ansOrder => $answerData) {
                                $answerModel->question_id = $question_id;
                                $answerModel->answer_text = trim($answerData['answer_text']);
                                $answerModel->is_correct = isset($answerData['is_correct']) && $answerData['is_correct'] === true;
                                $answerModel->order_number = $ansOrder;
                                
                                if (!$answerModel->create()) {
                                    error_log("Failed to create answer for question {$question_id}");
                                }
                            }
                        }
                    } else {
                        error_log("Failed to create question for assignment {$new_assignment_id}");
                    }
                }
            } catch (Exception $e) {
                error_log("Error creating quiz questions: " . $e->getMessage());
                // Không throw để vẫn trả về assignment đã tạo
            }
        }
    
    // Lấy thông tin đầy đủ từ database
    if ($assignment->readOne()) {
        // Tạo thông báo cho tất cả students đã đăng ký course (sau khi đã set response)
        try {
            // Lấy thông tin course để lấy teacher_id
            $course = new Course($db);
            $course->id = $assignment->course_id;
            if ($course->readOne()) {
                $notification = new Notification($db);
                $teacher_id = $course->teacher_id;
                $course_name = $course->course_name ? $course->course_name : $course->title;
                
                $deadline_str = date('d/m/Y H:i', strtotime($assignment->deadline));
                $title = "Bài tập mới: " . $assignment->title;
                $content = "Giảng viên đã thêm bài tập mới \"" . $assignment->title . "\" vào khóa học \"" . $course_name . "\". Hạn nộp: " . $deadline_str . ". Hãy vào xem và nộp bài ngay!";
                
                $created_count = $notification->createForCourseStudents($teacher_id, $assignment->course_id, $title, $content);
                // Log vào error log, không output
                if (function_exists('error_log')) {
                    error_log("Created {$created_count} notifications for assignment {$assignment->id} in course {$assignment->course_id}");
                }
            }
        } catch (Exception $e) {
            // Log lỗi nhưng không ảnh hưởng đến response
            if (function_exists('error_log')) {
                error_log("Error creating notification for assignment: " . $e->getMessage());
            }
        }
        
        // Debug: Log giá trị trước khi trả về
        error_log("Response - Assignment type: " . ($assignment->type ?? 'NULL'));
        error_log("Response - Assignment time_limit: " . ($assignment->time_limit ?? 'NULL'));
        
        http_response_code(201);
        $responseData = array(
            "success" => true,
            "message" => "Tạo bài tập thành công",
            "data" => array(
                "id" => intval($assignment->id),
                "course_id" => intval($assignment->course_id),
                "title" => $assignment->title,
                "description" => $assignment->description,
                "type" => $assignment->type,
                "time_limit" => $assignment->time_limit ? intval($assignment->time_limit) : null,
                "start_date" => $assignment->start_date ? $assignment->start_date : null,
                "deadline" => $assignment->deadline,
                "max_score" => floatval($assignment->max_score),
                "created_at" => $assignment->created_at
            )
        );
        
        error_log("Final response data: " . json_encode($responseData, JSON_UNESCAPED_UNICODE));
        
        echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        // Nếu không đọc được, vẫn trả về success với thông tin cơ bản
        http_response_code(201);
        echo json_encode(array(
            "success" => true,
            "message" => "Tạo bài tập thành công",
            "data" => array(
                "id" => intval($new_assignment_id),
                "course_id" => intval($assignment->course_id),
                "title" => $assignment->title,
                "description" => $assignment->description,
                "type" => $assignment->type,
                "time_limit" => $assignment->time_limit ? intval($assignment->time_limit) : null,
                "deadline" => $assignment->deadline,
                "max_score" => floatval($assignment->max_score)
            )
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể tạo bài tập. Vui lòng thử lại sau."
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} catch (PDOException $e) {
    error_log("PDO Error in create-assignment.php: " . $e->getMessage());
    error_log("SQL State: " . $e->getCode());
    
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi database: " . $e->getMessage() . ". Vui lòng kiểm tra migration đã chạy chưa."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} catch (Exception $e) {
    error_log("Error in create-assignment.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi server: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>


