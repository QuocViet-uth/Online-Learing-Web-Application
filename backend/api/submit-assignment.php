<?php
/**
 * File: api/submit-assignment.php
 * Mục đích: API để student nộp bài tập
 * Method: POST, PUT
 * Parameters: 
 *   - assignment_id (required): ID của bài tập
 *   - student_id (required): ID của học viên
 *   - content (optional): Nội dung bài nộp (text)
 *   - attachment_file (optional): URL file đính kèm
 * Response: JSON
 */

// Tắt error display để tránh output HTML trước JSON
// Error reporting được set trong headers.php

// Include common headers
require_once __DIR__ . '/../config/headers.php';

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Submission.php';
require_once __DIR__ . '/../models/Assignment.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$submission = new Submission($db);
$assignment = new Assignment($db);

// Lấy method (hỗ trợ method override cho PUT/DELETE)
$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

// Lấy dữ liệu từ request
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Nếu vẫn không có, thử parse_str
if (empty($data) && !empty($raw_input)) {
    parse_str($raw_input, $data);
}

// Xóa _method khỏi data nếu có
if (isset($data['_method'])) {
    unset($data['_method']);
}

// Validate và lấy dữ liệu
$submission->assignment_id = isset($data['assignment_id']) ? intval($data['assignment_id']) : 0;
$submission->student_id = isset($data['student_id']) ? intval($data['student_id']) : 0;
$submission->content = isset($data['content']) && is_string($data['content']) ? trim($data['content']) : '';
$submission->attachment_file = isset($data['attachment_file']) && is_string($data['attachment_file']) && !empty(trim($data['attachment_file'])) ? trim($data['attachment_file']) : null;

// Validate dữ liệu bắt buộc
$errors = array();

if (empty($submission->assignment_id) || $submission->assignment_id <= 0) {
    $errors[] = "Assignment ID không hợp lệ";
}

if (empty($submission->student_id) || $submission->student_id <= 0) {
    $errors[] = "Student ID không hợp lệ";
}

// Validate: Phải có ít nhất content hoặc attachment_file
if (empty($submission->content) && empty($submission->attachment_file)) {
    $errors[] = "Vui lòng nhập nội dung hoặc đính kèm file";
}

// Kiểm tra assignment có tồn tại không
if (!empty($submission->assignment_id)) {
    $assignment->id = $submission->assignment_id;
    if (!$assignment->readOne()) {
        $errors[] = "Không tìm thấy bài tập";
    } else {
        // Kiểm tra deadline và allow_late_submission
        $deadline = strtotime($assignment->deadline);
        $now = time();
        if ($deadline < $now) {
            // Kiểm tra xem có cho phép nộp khi quá hạn không
            $allow_late = true; // allow_late_submission column does not exist in database, default to true
            if (!$allow_late) {
                $errors[] = "Đã quá hạn nộp bài. Bài tập này không cho phép nộp khi quá hạn.";
            }
            // Nếu cho phép, status sẽ được set là 'late' trong model
        }
    }
}

if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => implode(", ", $errors)
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra xem đã nộp bài chưa
$submission->hasSubmitted();

if ($submission->hasSubmitted()) {
    // Đã nộp rồi - cập nhật bài nộp (nếu PUT method hoặc cho phép update)
    if ($method === 'PUT' || $method === 'POST') {
        // Lấy ID của submission hiện tại
        $stmt = $db->prepare("SELECT id FROM submissions WHERE assignment_id = ? AND student_id = ? LIMIT 1");
        $stmt->execute([$submission->assignment_id, $submission->student_id]);
        $existing = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($existing) {
            $submission->id = $existing['id'];
            
            // Kiểm tra deadline và allow_late_submission
            $deadline = strtotime($assignment->deadline);
            $now = time();
            
            if ($deadline < $now) {
                $allow_late = true; // allow_late_submission column does not exist in database, default to true
                if (!$allow_late) {
                    if (!headers_sent()) {
                        http_response_code(400);
                    }
                    echo json_encode(array(
                        "success" => false,
                        "message" => "Đã quá hạn nộp bài. Bài tập này không cho phép nộp khi quá hạn."
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                    exit();
                }
                // Nếu cho phép nộp muộn, vẫn cho phép sửa (nhưng sẽ đánh dấu là late)
            }
            
            // Cập nhật bài nộp
            if ($submission->update()) {
                // Lấy thông tin đầy đủ
                if ($submission->readOne()) {
                    http_response_code(200);
                    echo json_encode(array(
                        "success" => true,
                        "message" => "Cập nhật bài nộp thành công",
                        "data" => array(
                            "id" => intval($submission->id),
                            "assignment_id" => intval($submission->assignment_id),
                            "student_id" => intval($submission->student_id),
                            "content" => $submission->content,
                            "attachment_file" => $submission->attachment_file,
                            "submit_date" => $submission->submit_date,
                            "status" => $submission->status,
                            "score" => $submission->score ? floatval($submission->score) : null,
                            "feedback" => $submission->feedback
                        )
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                } else {
                    http_response_code(200);
                    echo json_encode(array(
                        "success" => true,
                        "message" => "Cập nhật bài nộp thành công"
                    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
                }
            } else {
                http_response_code(500);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể cập nhật bài nộp"
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Không tìm thấy bài nộp để cập nhật"
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } else {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Bạn đã nộp bài rồi. Sử dụng PUT method để cập nhật."
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} else {
    // Chưa nộp - tạo mới
    // Kiểm tra deadline và allow_late_submission (đã kiểm tra ở trên trong errors)
    // Nếu có lỗi về allow_late_submission, không cho phép nộp
    
    // Kiểm tra lại một lần nữa để chắc chắn
    $deadline = strtotime($assignment->deadline);
    $now = time();
    if ($deadline < $now) {
        $allow_late = isset($assignment->allow_late_submission) ? (bool)$assignment->allow_late_submission : true;
        if (!$allow_late) {
            if (!headers_sent()) {
                http_response_code(400);
            }
            echo json_encode(array(
                "success" => false,
                "message" => "Đã quá hạn nộp bài. Bài tập này không cho phép nộp khi quá hạn."
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            exit();
        }
    }
    
    if ($submission->create()) {
        $new_submission_id = $submission->id;
        $submission->id = $new_submission_id;
        
        // Lấy thông tin đầy đủ từ database
        if ($submission->readOne()) {
            http_response_code(201);
            echo json_encode(array(
                "success" => true,
                "message" => "Nộp bài thành công",
                "data" => array(
                    "id" => intval($submission->id),
                    "assignment_id" => intval($submission->assignment_id),
                    "student_id" => intval($submission->student_id),
                    "content" => $submission->content,
                    "attachment_file" => $submission->attachment_file,
                    "submit_date" => $submission->submit_date,
                    "status" => $submission->status,
                    "score" => $submission->score ? floatval($submission->score) : null,
                    "feedback" => $submission->feedback
                )
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            // Nếu không đọc được, vẫn trả về success với thông tin cơ bản
            http_response_code(201);
            echo json_encode(array(
                "success" => true,
                "message" => "Nộp bài thành công",
                "data" => array(
                    "id" => intval($new_submission_id),
                    "assignment_id" => intval($submission->assignment_id),
                    "student_id" => intval($submission->student_id),
                    "content" => $submission->content,
                    "attachment_file" => $submission->attachment_file
                )
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } else {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể nộp bài. Vui lòng thử lại sau."
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}
?>

