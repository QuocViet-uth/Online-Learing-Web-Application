<?php
/**
 * File: api/confirm-payment.php
 * Mục đích: API xác nhận payment (chuyển status từ pending sang success và tự động enroll)
 * Method: POST
 * Parameters: 
 *   - payment_id (required): ID payment
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Payment.php';
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

$payment = new Payment($db);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Validate và lấy dữ liệu
$payment_id = isset($data['payment_id']) ? intval($data['payment_id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);

if (empty($payment_id) || $payment_id <= 0) {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Payment ID không hợp lệ"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Lấy thông tin payment
$payment->id = $payment_id;
if (!$payment->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy thanh toán"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra status phải là pending
if ($payment->status !== 'pending') {
    if (!headers_sent()) {
        http_response_code(400);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Thanh toán đã được xử lý!"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Bắt đầu transaction
$db->beginTransaction();

try {
    // Cập nhật status payment thành completed
    $payment->status = 'completed';
    if (!$payment->updateStatus()) {
        throw new Exception("Không thể cập nhật trạng thái thanh toán");
    }
    
    // Kiểm tra enrollment và update status thành 'active'
    $check_enrollment = $db->prepare("SELECT id, status FROM enrollments WHERE student_id = ? AND course_id = ?");
    $check_enrollment->execute([$payment->student_id, $payment->course_id]);
    $enrollment = $check_enrollment->fetch(PDO::FETCH_ASSOC);
    
    if ($enrollment) {
        // Update enrollment status thành 'active'
        if ($enrollment['status'] !== 'active') {
            $update_enrollment = $db->prepare("UPDATE enrollments SET status = 'active' WHERE id = ?");
            if (!$update_enrollment->execute([$enrollment['id']])) {
                throw new Exception("Không thể cập nhật trạng thái enrollment");
            }
        }
    } else {
        // Trường hợp không tìm thấy enrollment (không nên xảy ra), tạo mới
        $enrollment_query = "INSERT INTO enrollments (student_id, course_id, status) 
                            VALUES (?, ?, 'active')";
        $enrollment_stmt = $db->prepare($enrollment_query);
        
        if (!$enrollment_stmt->execute([$payment->student_id, $payment->course_id])) {
            throw new Exception("Không thể tạo enrollment");
        }
    }
    
    // Tạo thông báo cho teacher khi có học viên đăng ký mới
    try {
            $course = new Course($db);
            $course->id = $payment->course_id;
            if ($course->readOne()) {
                $notification = new Notification($db);
                $teacher_id = $course->teacher_id;
                $course_name = $course->course_name ? $course->course_name : $course->title;
                
                // Lấy thông tin student
                $student_stmt = $db->prepare("SELECT username, full_name FROM users WHERE id = ? LIMIT 1");
                $student_stmt->execute([$payment->student_id]);
                $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
                $student_name = $student ? ($student['full_name'] ? $student['full_name'] : $student['username']) : 'Học viên';
                
                $title = "Học viên đăng ký mới: " . $course_name;
                $content = $student_name . " đã đăng ký khóa học \"" . $course_name . "\" của bạn.";
                
                $notification->sender_id = $payment->student_id;
                $notification->receiver_id = $teacher_id;
                $notification->course_id = $payment->course_id;
                $notification->title = $title;
                $notification->content = $content;
                $notification->create();
            }
    } catch (Exception $e) {
        error_log("Error creating notification for enrollment (confirm-payment): " . $e->getMessage());
    }
    
    // Commit transaction
    $db->commit();
    
    // Lấy lại thông tin payment sau khi update
    $payment->readOne();
    
    $payment_data = array(
        "id" => intval($payment->id),
        "student_id" => intval($payment->student_id),
        "course_id" => intval($payment->course_id),
        "amount" => floatval($payment->amount),
        "payment_gateway" => $payment->payment_gateway,
        "transaction_id" => $payment->transaction_id,
        "status" => $payment->status,
        "payment_date" => $payment->payment_date,
        "course_name" => $payment->course_name,
        "course_title" => $payment->course_title
    );
    
    if (!headers_sent()) {
        http_response_code(200);
    }
    echo json_encode(array(
        "success" => true,
        "message" => "Thanh toán thành công! Bạn đã được đăng ký khóa học.",
        "data" => $payment_data
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    
} catch (Exception $e) {
    // Rollback transaction
    $db->rollBack();
    
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log("Confirm payment error: " . $e->getMessage());
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi khi xác nhận thanh toán: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

