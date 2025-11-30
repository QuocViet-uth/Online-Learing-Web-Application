<?php
/**
 * File: api/grade-submission.php
 * Mục đích: API chấm điểm bài nộp (Teacher)
 * Method: POST (với _method=PUT) hoặc PUT
 * Parameters: 
 *   - submission_id (required): ID của bài nộp
 *   - score (required): Điểm số (0 - max_score)
 *   - feedback (optional): Nhận xét
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Submission.php';
require_once __DIR__ . '/../models/Assignment.php';
require_once __DIR__ . '/../models/Notification.php';
require_once __DIR__ . '/../models/Course.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$submission = new Submission($db);
$assignment = new Assignment($db);

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'POST' && isset($_POST['_method']) && $_POST['_method'] === 'PUT') {
    $data = $_POST;
} else {
    $data = json_decode(file_get_contents("php://input"), true);
}

if (!$data) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Dữ liệu không hợp lệ."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : (isset($data['submission_id']) ? intval($data['submission_id']) : 0);
$score = isset($data['score']) ? floatval($data['score']) : null;
$feedback = isset($data['feedback']) ? trim($data['feedback']) : '';

if ($submission_id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu submission_id."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($score === null) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu điểm số."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Lấy thông tin submission
$submission->id = $submission_id;
if (!$submission->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array("success" => false, "message" => "Không tìm thấy bài nộp."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Lấy thông tin assignment để kiểm tra max_score
$assignment->id = $submission->assignment_id;
if (!$assignment->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array("success" => false, "message" => "Không tìm thấy bài tập."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Validate score
if ($score < 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Điểm số phải >= 0."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($score > $assignment->max_score) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Điểm số không được vượt quá điểm tối đa ({$assignment->max_score})."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

// Chấm điểm
$submission->score = $score;
$submission->feedback = $feedback;

if ($submission->grade()) {
    // Đọc lại thông tin đầy đủ
    $submission->readOne();
    
    // Tạo thông báo cho student khi được chấm điểm
    try {
        // Lấy thông tin course để lấy teacher_id
        $course = new Course($db);
        $course->id = $assignment->course_id;
        if ($course->readOne()) {
            $notification = new Notification($db);
            $teacher_id = $course->teacher_id;
            $course_name = $course->course_name ? $course->course_name : $course->title;
            
            // Lấy thông tin student
            $student_stmt = $db->prepare("SELECT username, full_name FROM users WHERE id = ? LIMIT 1");
            $student_stmt->execute([$submission->student_id]);
            $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
            $student_name = $student ? ($student['full_name'] ? $student['full_name'] : $student['username']) : 'Bạn';
            
            $title = "Điểm bài tập: " . $assignment->title;
            $content = $student_name . " đã được chấm điểm bài tập \"" . $assignment->title . "\" trong khóa học \"" . $course_name . "\". Điểm số: " . number_format($score, 1) . "/" . number_format($assignment->max_score, 1);
            if (!empty($feedback)) {
                $content .= ". Nhận xét: " . mb_substr($feedback, 0, 100) . (mb_strlen($feedback) > 100 ? '...' : '');
            }
            
            $notification->sender_id = $teacher_id;
            $notification->receiver_id = $submission->student_id;
            $notification->course_id = $assignment->course_id;
            $notification->title = $title;
            $notification->content = $content;
            $notification->create();
        }
    } catch (Exception $e) {
        // Log lỗi nhưng không ảnh hưởng đến response
        error_log("Error creating notification for grade: " . $e->getMessage());
    }
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Chấm điểm thành công",
        "data" => array(
            "id" => intval($submission->id),
            "assignment_id" => intval($submission->assignment_id),
            "student_id" => intval($submission->student_id),
            "score" => floatval($submission->score),
            "max_score" => floatval($assignment->max_score),
            "feedback" => $submission->feedback,
            "status" => $submission->status,
            "graded_at" => $submission->graded_at
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể chấm điểm."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

