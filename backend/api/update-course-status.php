<?php
/**
 * File: api/update-course-status.php
 * Mục đích: API để tự động cập nhật trạng thái tất cả khóa học
 * Method: POST (hoặc GET để trigger)
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
require_once __DIR__ . '/../models/Course.php';

// Khởi tạo database và course object
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$course = new Course($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET' || $method === 'POST') {
    try {
        // Tự động cập nhật trạng thái tất cả khóa học
        $updated_count = $course->updateStatusAutomatically();
        
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode(array(
            "success" => true,
            "message" => "Đã cập nhật trạng thái " . $updated_count . " khóa học",
            "updated_count" => $updated_count
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    } catch (Exception $e) {
        error_log("Error updating course status: " . $e->getMessage());
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi khi cập nhật trạng thái: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} else {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array("success" => false, "message" => "Method Not Allowed."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

