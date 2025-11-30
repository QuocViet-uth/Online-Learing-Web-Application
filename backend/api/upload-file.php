<?php
/**
 * File: api/upload-file.php
 * Mục đích: API upload file (cho lesson, assignment, etc.)
 * Method: POST (multipart/form-data)
 * Parameters: 
 *   - file (required): File cần upload
 *   - type (optional): Loại file (lesson, assignment, submission)
 * Response: JSON
 */

// Include headers FIRST to handle OPTIONS preflight requests
require_once __DIR__ . '/../config/headers.php';

// Cấu hình upload
$upload_dir = __DIR__ . '/../uploads/';
$allowed_types = array('pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'zip', 'rar', 'mp4', 'mp3', 'jpg', 'jpeg', 'png', 'gif');
$max_file_size = 50 * 1024 * 1024; // 50MB

// Tạo thư mục uploads nếu chưa có
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

// Tạo thư mục con theo type nếu có
$file_type = isset($_POST['type']) ? $_POST['type'] : 'general';
$type_dir = $upload_dir . $file_type . '/';
if (!file_exists($type_dir)) {
    mkdir($type_dir, 0755, true);
}

// Kiểm tra file có được upload không
if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Không có file được upload hoặc có lỗi xảy ra"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$file = $_FILES['file'];

// Kiểm tra kích thước file
if ($file['size'] > $max_file_size) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "File quá lớn. Kích thước tối đa là " . ($max_file_size / 1024 / 1024) . "MB"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Lấy extension của file
$file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

// Kiểm tra loại file có được phép không
if (!in_array($file_extension, $allowed_types)) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Loại file không được phép. Các loại file được phép: " . implode(', ', $allowed_types)
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Tạo tên file unique
$file_name = time() . '_' . uniqid() . '_' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $file['name']);
$file_path = $type_dir . $file_name;

// Upload file
if (move_uploaded_file($file['tmp_name'], $file_path)) {
    // Trả về thông tin file
    $file_url = '/api/uploads/' . $file_type . '/' . $file_name;
    
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Upload file thành công",
        "data" => array(
            "file_name" => $file['name'],
            "file_path" => $file_path,
            "file_url" => $file_url,
            "file_size" => $file['size'],
            "file_type" => $file_extension
        )
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể upload file. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>


