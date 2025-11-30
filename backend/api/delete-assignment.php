<?php
/**
 * File: api/delete-assignment.php
 * Mục đích: API xóa bài tập
 * Method: DELETE, POST
 * Parameters: 
 *   - id (required): ID của bài tập
 * Response: JSON
 */

// Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Assignment.php';

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

$assignment = new Assignment($db);

// Lấy method - hỗ trợ method override
$method = $_SERVER['REQUEST_METHOD'];

// Lấy ID từ URL hoặc request
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Nếu không có trong GET, thử lấy từ POST/JSON
if (empty($assignment_id)) {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }
    
    // Hỗ trợ method override
    if (isset($data['_method'])) {
        $method = strtoupper($data['_method']);
        unset($data['_method']);
    }
    
    if (isset($data['id'])) {
        $assignment_id = intval($data['id']);
    }
}

if (empty($assignment_id) || $assignment_id <= 0) {
    http_response_code(400);
    echo json_encode(array(
        "success" => false,
        "message" => "Assignment ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra assignment có tồn tại không
$assignment->id = $assignment_id;
if (!$assignment->readOne()) {
    http_response_code(404);
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy bài tập"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Thực hiện xóa
if ($assignment->delete()) {
    http_response_code(200);
    echo json_encode(array(
        "success" => true,
        "message" => "Xóa bài tập thành công"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
} else {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể xóa bài tập. Vui lòng thử lại sau."
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>

