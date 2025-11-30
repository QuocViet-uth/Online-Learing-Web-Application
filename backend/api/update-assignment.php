<?php
/**
 * File: api/update-assignment.php
 * Mục đích: API cập nhật bài tập
 * Method: PUT, POST
 * Parameters: 
 *   - id (required): ID của bài tập
 *   - title (optional): Tiêu đề
 *   - description (optional): Mô tả
 *   - assignment_type (optional): Loại bài tập
 *   - attachment_file (optional): File đính kèm
 *   - start_date (optional): Thời gian bắt đầu làm bài
 *   - deadline (optional): Hạn nộp
 *   - max_score (optional): Điểm tối đa
 * Response: JSON
 */

// Tắt error display để tránh output HTML trước JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Include common headers
require_once __DIR__ . '/../config/headers.php';

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Assignment.php';
require_once __DIR__ . '/../models/QuizQuestion.php';
require_once __DIR__ . '/../models/QuizAnswer.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$assignment = new Assignment($db);

// Lấy dữ liệu từ request
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    // Nếu có $_POST data, dùng nó
    if (!empty($_POST)) {
        $data = $_POST;
    } else {
        // Nếu không có, thử parse_str
        if (!empty($raw_input)) {
            parse_str($raw_input, $data);
        } else {
            $data = array();
        }
    }
}

// Đảm bảo $data là array
if (!is_array($data)) {
    $data = array();
}

// Lấy method - hỗ trợ method override từ _method parameter
$method = $_SERVER['REQUEST_METHOD'];

// Debug: Log method và data (chỉ trong development)
// error_log("Request method: " . $method);
// error_log("POST data: " . print_r($_POST, true));
// error_log("Parsed data: " . print_r($data, true));

// Kiểm tra _method trong POST data hoặc JSON data
if ($method === 'POST') {
    // Kiểm tra trong parsed JSON data trước (vì axios gửi JSON)
    if (isset($data['_method']) && !empty($data['_method'])) {
        $method = strtoupper(trim($data['_method']));
        unset($data['_method']); // Remove _method from data
    }
    // Sau đó kiểm tra trong $_POST (cho form data)
    elseif (isset($_POST['_method']) && !empty($_POST['_method'])) {
        $method = strtoupper(trim($_POST['_method']));
    }
}

// Chỉ xử lý POST và PUT methods
if ($method !== 'POST' && $method !== 'PUT') {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Method not allowed. Use POST or PUT.",
        "received_method" => $_SERVER['REQUEST_METHOD'],
        "parsed_method" => $method
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Lấy ID từ URL hoặc data
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($data['id']) ? intval($data['id']) : 0);

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

// Kiểm tra assignment có tồn tại không
$assignment->id = $assignment_id;
if (!$assignment->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy bài tập"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Debug: Log dữ liệu nhận được
error_log("Update assignment - Received data: " . print_r($data, true));
error_log("Update assignment - Current assignment type from DB: " . ($assignment->type ?? 'NULL'));

// Cập nhật dữ liệu (chỉ cập nhật các trường được gửi lên)
if (isset($data['title'])) {
    $assignment->title = trim($data['title']);
}
if (isset($data['description'])) {
    $assignment->description = trim($data['description']);
}

// Xử lý type - LUÔN cập nhật nếu có trong request
if (isset($data['type'])) {
    $typeValue = trim($data['type']);
    error_log("Update assignment - Received type value: " . var_export($typeValue, true));
    if (in_array($typeValue, ['homework', 'quiz'], true)) {
        $assignment->type = $typeValue;
        error_log("Update assignment - Setting type to: " . $assignment->type);
    } else {
        error_log("Update assignment - Invalid type value: " . var_export($typeValue, true) . ", keeping current: " . $assignment->type);
    }
} else {
    error_log("Update assignment - Type NOT PROVIDED in request, keeping current: " . $assignment->type);
}

// Xử lý time_limit - chuyển chuỗi rỗng thành null
if (isset($data['time_limit'])) {
    if ($data['time_limit'] === '' || $data['time_limit'] === null) {
        $assignment->time_limit = null;
    } else {
        $timeLimit = intval($data['time_limit']);
        $assignment->time_limit = $timeLimit > 0 ? $timeLimit : null;
    }
}
if (isset($data['start_date'])) {
    $assignment->start_date = trim($data['start_date']) ? trim($data['start_date']) : null;
}
if (isset($data['deadline'])) {
    $assignment->deadline = trim($data['deadline']);
}
if (isset($data['max_score'])) {
    $assignment->max_score = floatval($data['max_score']);
}

// Validate
$errors = array();

if (isset($data['title']) && empty($assignment->title)) {
    $errors[] = "Tiêu đề bài tập không được để trống";
}

if (isset($data['deadline']) && empty($assignment->deadline)) {
    $errors[] = "Hạn nộp không được để trống";
}

if (isset($data['max_score']) && $assignment->max_score < 0) {
    $errors[] = "Điểm tối đa phải >= 0";
}

// Validate start_date < deadline nếu cả hai đều có
if (isset($data['start_date']) && isset($data['deadline'])) {
    if (!empty($assignment->start_date) && !empty($assignment->deadline)) {
        if (strtotime($assignment->start_date) >= strtotime($assignment->deadline)) {
            $errors[] = "Thời gian bắt đầu phải trước hạn nộp";
        }
    }
} elseif (isset($data['start_date']) && !empty($assignment->start_date)) {
    // Nếu chỉ có start_date, kiểm tra với deadline hiện tại
    if (strtotime($assignment->start_date) >= strtotime($assignment->deadline)) {
        $errors[] = "Thời gian bắt đầu phải trước hạn nộp";
    }
} elseif (isset($data['deadline']) && !empty($assignment->start_date)) {
    // Nếu chỉ có deadline, kiểm tra với start_date hiện tại
    if (strtotime($assignment->start_date) >= strtotime($assignment->deadline)) {
        $errors[] = "Thời gian bắt đầu phải trước hạn nộp";
    }
}

if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Dữ liệu không hợp lệ",
        "errors" => $errors
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Validate quiz questions nếu đang cập nhật thành quiz hoặc đã là quiz
$is_quiz = (isset($data['type']) && $data['type'] === 'quiz') || $assignment->type === 'quiz';
if ($is_quiz && isset($data['questions']) && is_array($data['questions'])) {
    if (empty($data['questions'])) {
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

if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Dữ liệu không hợp lệ",
        "errors" => $errors
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Thực hiện cập nhật
if ($assignment->update()) {
    // Nếu là quiz và có questions, cập nhật questions và answers
    if ($is_quiz && isset($data['questions']) && is_array($data['questions'])) {
        $questionModel = new QuizQuestion($db);
        $answerModel = new QuizAnswer($db);
        
        // Xóa tất cả questions cũ
        $questionModel->assignment_id = $assignment_id;
        $questionModel->deleteByAssignment();
        
        // Tạo questions và answers mới
        foreach ($data['questions'] as $order => $questionData) {
            $questionModel->assignment_id = $assignment_id;
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
                        $answerModel->create();
                    }
                }
            }
        }
    }
    
    // Debug: Log giá trị sau khi update
    error_log("Update assignment - After update, type: " . ($assignment->type ?? 'NULL'));
    
    // Lấy thông tin đầy đủ từ database
    $assignment->id = $assignment_id;
    if ($assignment->readOne()) {
        // Debug: Log giá trị từ database
        error_log("Update assignment - From DB, type: " . ($assignment->type ?? 'NULL'));
        
        if (!headers_sent()) {
            http_response_code(200);
        }
        $responseData = array(
            "success" => true,
            "message" => "Cập nhật bài tập thành công",
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
                "created_at" => $assignment->created_at,
                "updated_at" => $assignment->updated_at
            )
        );
        
        error_log("Update assignment - Final response: " . json_encode($responseData, JSON_UNESCAPED_UNICODE));
        
        echo json_encode($responseData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    } else {
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Cập nhật bài tập thành công"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể cập nhật bài tập. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>


