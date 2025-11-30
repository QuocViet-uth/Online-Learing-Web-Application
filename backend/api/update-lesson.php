<?php
/**
 * File: api/update-lesson.php
 * Mục đích: API cập nhật bài giảng
 * Method: POST (với _method=PUT) hoặc PUT
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Lesson.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$lesson = new Lesson($db);

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

$id = isset($_GET['id']) ? intval($_GET['id']) : (isset($data['id']) ? intval($data['id']) : 0);

if ($id === 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => "Thiếu ID bài giảng."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$lesson->id = $id;

if (!$lesson->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array("success" => false, "message" => "Không tìm thấy bài giảng."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$errors = array();

if (isset($data['title']) && !empty(trim($data['title']))) {
    $lesson->title = trim($data['title']);
} else {
    $errors[] = "Tiêu đề bài giảng không được để trống";
}

if (isset($data['content'])) {
    $lesson->content = trim($data['content']);
}

if (isset($data['video_url'])) {
    $lesson->video_url = trim($data['video_url']);
}

if (isset($data['attachment_file'])) {
    $lesson->attachment_file = !empty(trim($data['attachment_file'])) ? trim($data['attachment_file']) : null;
}

if (isset($data['order_number'])) {
    $order_number = intval($data['order_number']);
    if ($order_number < 0) {
        $errors[] = "Thứ tự phải >= 0";
    } else {
        $lesson->order_number = $order_number;
    }
}

if (isset($data['duration'])) {
    $duration = intval($data['duration']);
    if ($duration < 0) {
        $errors[] = "Thời lượng phải >= 0";
    } else {
        $lesson->duration = $duration;
    }
}

if (!empty($errors)) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array("success" => false, "message" => implode(", ", $errors)), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

if ($lesson->update()) {
    // Đọc lại thông tin đầy đủ
    $lesson->readOne();
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Cập nhật bài giảng thành công",
        "data" => array(
            "id" => intval($lesson->id),
            "course_id" => intval($lesson->course_id),
            "title" => $lesson->title,
            "content" => $lesson->content,
            "video_url" => $lesson->video_url ? $lesson->video_url : '',
            "attachment_file" => $lesson->attachment_file ? $lesson->attachment_file : null,
            "order_number" => intval($lesson->order_number),
            "duration" => intval($lesson->duration),
            "created_at" => $lesson->created_at
        )
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} else {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể cập nhật bài giảng."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

