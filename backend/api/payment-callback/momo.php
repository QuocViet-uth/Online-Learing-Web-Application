<?php
/**
 * File: api/payment-callback/momo.php
 * Mục đích: Xử lý callback từ MoMo sau khi thanh toán
 * Method: GET
 * Response: Redirect đến frontend
 */

require_once __DIR__ . '/../../config/headers.php';
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../models/Payment.php';
require_once __DIR__ . '/../../services/PaymentGatewayService.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    header('Location: /checkout/error?message=' . urlencode('Database connection error'));
    exit();
}

$payment = new Payment($db);
$gatewayService = new PaymentGatewayService($db);

// Lấy dữ liệu từ MoMo callback
$momoData = $_GET;

// Xác thực callback
$verifyResult = $gatewayService->verifyMoMoCallback($momoData);

if ($verifyResult['success']) {
    // Tìm payment theo transaction_id
    $transaction_id = $verifyResult['transaction_id'];
    
    // Query payment
    $stmt = $db->prepare("SELECT * FROM payments WHERE transaction_id = ? LIMIT 1");
    $stmt->execute([$transaction_id]);
    $paymentRow = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($paymentRow) {
        $payment->id = $paymentRow['id'];
        
        // Cập nhật status payment
        if ($verifyResult['transaction_status'] === 'success') {
            $payment->status = 'success';
            $payment->updateStatus();
            
            // Tự động enroll nếu chưa enroll
            $check_enrollment = $db->prepare("SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?");
            $check_enrollment->execute([$paymentRow['student_id'], $paymentRow['course_id']]);
            
            if ($check_enrollment->rowCount() == 0) {
                $enrollment_query = "INSERT INTO enrollments (student_id, course_id, status) 
                                    VALUES (?, ?, 'active')";
                $enrollment_stmt = $db->prepare($enrollment_query);
                $enrollment_stmt->execute([$paymentRow['student_id'], $paymentRow['course_id']]);
                
                // Tạo thông báo cho teacher khi có học viên đăng ký mới
                try {
                    require_once __DIR__ . '/../../models/Notification.php';
                    require_once __DIR__ . '/../../models/Course.php';
                    
                    $course = new Course($db);
                    $course->id = $paymentRow['course_id'];
                    if ($course->readOne()) {
                        $notification = new Notification($db);
                        $teacher_id = $course->teacher_id;
                        $course_name = $course->course_name ? $course->course_name : $course->title;
                        
                        // Lấy thông tin student
                        $student_stmt = $db->prepare("SELECT username, full_name FROM users WHERE id = ? LIMIT 1");
                        $student_stmt->execute([$paymentRow['student_id']]);
                        $student = $student_stmt->fetch(PDO::FETCH_ASSOC);
                        $student_name = $student ? ($student['full_name'] ? $student['full_name'] : $student['username']) : 'Học viên';
                        
                        $title = "Học viên đăng ký mới: " . $course_name;
                        $content = $student_name . " đã đăng ký khóa học \"" . $course_name . "\" của bạn.";
                        
                        $notification->sender_id = $paymentRow['student_id'];
                        $notification->receiver_id = $teacher_id;
                        $notification->course_id = $paymentRow['course_id'];
                        $notification->title = $title;
                        $notification->content = $content;
                        $notification->create();
                    }
                } catch (Exception $e) {
                    error_log("Error creating notification for enrollment (momo): " . $e->getMessage());
                }
            }
            
            // Redirect đến trang thành công
            header('Location: /checkout/success?payment_id=' . $payment->id . '&course_id=' . $paymentRow['course_id']);
        } else {
            $payment->status = 'failed';
            $payment->updateStatus();
            
            // Redirect đến trang thất bại
            header('Location: /checkout/failed?payment_id=' . $payment->id);
        }
    } else {
        header('Location: /checkout/error?message=' . urlencode('Payment not found'));
    }
} else {
    header('Location: /checkout/error?message=' . urlencode($verifyResult['message'] ?? 'Payment verification failed'));
}

exit();
?>

