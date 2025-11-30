<?php
/**
 * File: api/reviews.php
 * Mục đích: API xử lý reviews (đánh giá khóa học)
 * Method: GET, POST, PUT, DELETE
 * Parameters: 
 *   - course_id (required for GET): ID khóa học
 *   - student_id (required for POST/PUT/DELETE): ID học viên
 *   - rating (required for POST/PUT): Điểm đánh giá 1-5
 *   - comment (optional): Bình luận
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Review.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể kết nối database"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra bảng reviews có tồn tại không
try {
    $checkTable = $db->query("SHOW TABLES LIKE 'reviews'");
    if ($checkTable->rowCount() === 0) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Bảng reviews chưa được tạo. Vui lòng truy cập: http://localhost:8000/api/create-reviews-table.php để tạo bảng.",
            "fix_url" => "http://localhost:8000/api/create-reviews-table.php"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi khi kiểm tra bảng reviews: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

$review = new Review($db);
$method = $_SERVER['REQUEST_METHOD'];

// GET: Lấy đánh giá
if ($method === 'GET') {
    $course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : null;
    $student_id = isset($_GET['student_id']) ? intval($_GET['student_id']) : null;
    $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
    $offset = isset($_GET['offset']) ? intval($_GET['offset']) : 0;
    $stats_only = isset($_GET['stats_only']) ? $_GET['stats_only'] === 'true' : false;
    
    if (!$course_id) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu course_id"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    $review->course_id = $course_id;
    
    try {
        if ($stats_only) {
            // Chỉ lấy thống kê (average rating, total reviews, distribution)
            $stats = $review->getAverageRating();
            $distribution = $review->getRatingDistribution();
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "data" => array(
                    "average_rating" => $stats['average_rating'],
                    "total_reviews" => $stats['total_reviews'],
                    "distribution" => $distribution
                )
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } elseif ($student_id) {
            // Lấy đánh giá của một student cụ thể cho course
            $review->student_id = $student_id;
            $stmt = $review->getByStudentAndCourse();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($row) {
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "data" => array(
                        "id" => intval($row['id']),
                        "course_id" => intval($row['course_id']),
                        "student_id" => intval($row['student_id']),
                        "rating" => intval($row['rating']),
                        "comment" => $row['comment'],
                        "created_at" => formatDateTimeISO($row['created_at']),
                        "updated_at" => $row['updated_at'] ? formatDateTimeISO($row['updated_at']) : null,
                        "student_name" => $row['full_name'] ? $row['full_name'] : $row['username'],
                        "student_avatar" => $row['avatar']
                    )
                ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "data" => null
                ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        } else {
            // Lấy tất cả đánh giá của course
            $stmt = $review->getByCourse($limit, $offset);
            $reviews_arr = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $reviews_arr[] = array(
                    "id" => intval($row['id']),
                    "course_id" => intval($row['course_id']),
                    "student_id" => intval($row['student_id']),
                    "rating" => intval($row['rating']),
                    "comment" => $row['comment'],
                    "created_at" => formatDateTimeISO($row['created_at']),
                    "updated_at" => $row['updated_at'] ? formatDateTimeISO($row['updated_at']) : null,
                    "student_name" => $row['full_name'] ? $row['full_name'] : $row['username'],
                    "student_avatar" => $row['avatar']
                );
            }
            
            $total = $review->countByCourse();
            
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "data" => $reviews_arr,
                "total" => $total,
                "limit" => $limit,
                "offset" => $offset
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi server: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

// POST: Tạo đánh giá mới
elseif ($method === 'POST') {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }
    
    $review->course_id = isset($data['course_id']) ? intval($data['course_id']) : null;
    $review->student_id = isset($data['student_id']) ? intval($data['student_id']) : null;
    $review->rating = isset($data['rating']) ? intval($data['rating']) : null;
    $review->comment = isset($data['comment']) ? trim($data['comment']) : null;
    
    // Validation
    if (empty($review->course_id)) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu course_id"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    if (empty($review->student_id)) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu student_id"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    if (empty($review->rating) || $review->rating < 1 || $review->rating > 5) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Rating phải từ 1 đến 5"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    // Kiểm tra xem học viên đã đăng ký course chưa
    $check_enrollment = $db->prepare("SELECT id FROM enrollments 
                                       WHERE course_id = ? AND student_id = ? AND status = 'active' 
                                       LIMIT 1");
    $check_enrollment->execute([$review->course_id, $review->student_id]);
    if (!$check_enrollment->fetch()) {
        http_response_code(403);
        echo json_encode(array(
            "success" => false,
            "message" => "Bạn phải đăng ký khóa học trước khi đánh giá"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    try {
        // Kiểm tra xem đã có đánh giá chưa
        $check_review = $review->getByStudentAndCourse();
        $existing_review = $check_review->fetch(PDO::FETCH_ASSOC);
        
        if ($existing_review) {
            // Nếu đã có, cập nhật thay vì tạo mới
            $review->id = intval($existing_review['id']);
            if ($review->update()) {
                http_response_code(200);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Cập nhật đánh giá thành công",
                    "data" => array(
                        "id" => $review->id,
                        "course_id" => $review->course_id,
                        "student_id" => $review->student_id,
                        "rating" => $review->rating,
                        "comment" => $review->comment
                    )
                ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(500);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể cập nhật đánh giá"
                ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        } else {
            // Tạo mới
            if ($review->create()) {
                http_response_code(201);
                echo json_encode(array(
                    "success" => true,
                    "message" => "Tạo đánh giá thành công",
                    "data" => array(
                        "id" => $review->id,
                        "course_id" => $review->course_id,
                        "student_id" => $review->student_id,
                        "rating" => $review->rating,
                        "comment" => $review->comment
                    )
                ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            } else {
                http_response_code(500);
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không thể tạo đánh giá"
                ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            }
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi server: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

// PUT: Cập nhật đánh giá
elseif ($method === 'PUT') {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        parse_str($raw_input, $data);
    }
    
    $review->id = isset($data['id']) ? intval($data['id']) : null;
    $review->student_id = isset($data['student_id']) ? intval($data['student_id']) : null;
    $review->rating = isset($data['rating']) ? intval($data['rating']) : null;
    $review->comment = isset($data['comment']) ? trim($data['comment']) : null;
    
    if (empty($review->id) || empty($review->student_id)) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu id hoặc student_id"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    if (empty($review->rating) || $review->rating < 1 || $review->rating > 5) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Rating phải từ 1 đến 5"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    try {
        if ($review->update()) {
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Cập nhật đánh giá thành công"
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Không thể cập nhật đánh giá"
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi server: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

// DELETE: Xóa đánh giá
elseif ($method === 'DELETE') {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        parse_str($raw_input, $data);
    }
    
    // Hoặc lấy từ query string
    if (empty($data)) {
        $data = $_GET;
    }
    
    $review->id = isset($data['id']) ? intval($data['id']) : null;
    $review->student_id = isset($data['student_id']) ? intval($data['student_id']) : null;
    
    if (empty($review->id) || empty($review->student_id)) {
        http_response_code(400);
        echo json_encode(array(
            "success" => false,
            "message" => "Thiếu id hoặc student_id"
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    try {
        if ($review->delete()) {
            http_response_code(200);
            echo json_encode(array(
                "success" => true,
                "message" => "Xóa đánh giá thành công"
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        } else {
            http_response_code(500);
            echo json_encode(array(
                "success" => false,
                "message" => "Không thể xóa đánh giá"
            ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi server: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
}

else {
    http_response_code(405);
    echo json_encode(array(
        "success" => false,
        "message" => "Method không được hỗ trợ"
    ), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}
?>

