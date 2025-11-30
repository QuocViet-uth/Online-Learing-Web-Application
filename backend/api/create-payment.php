<?php
/**
 * File: api/create-payment.php
 * Mục đích: API tạo payment mới
 * Method: POST
 * Parameters: 
 *   - course_id (required): ID khóa học
 *   - payment_gateway (required): Phương thức thanh toán (momo, vnpay, bank_transfer)
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Payment.php';
require_once __DIR__ . '/../models/Course.php';
require_once __DIR__ . '/../services/PaymentGatewayService.php';

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
$course = new Course($db);

// Lấy dữ liệu từ POST
$raw_input = file_get_contents("php://input");
$data = json_decode($raw_input, true);

// Nếu không parse được JSON, thử dùng $_POST
if (!$data || json_last_error() !== JSON_ERROR_NONE) {
    $data = $_POST;
}

// Validate và lấy dữ liệu
$course_id = isset($data['course_id']) ? intval($data['course_id']) : 0;
$payment_gateway = isset($data['payment_gateway']) ? trim($data['payment_gateway']) : '';
$student_id = isset($data['student_id']) ? intval($data['student_id']) : 0; // Tạm thời lấy từ request, sau này sẽ lấy từ token

// Validate dữ liệu bắt buộc
$errors = array();

if (empty($course_id) || $course_id <= 0) {
    $errors[] = "Course ID không hợp lệ";
}

if (empty($payment_gateway)) {
    $errors[] = "Phương thức thanh toán không được để trống";
}

if (!in_array($payment_gateway, ['momo', 'vnpay', 'bank_transfer'])) {
    $errors[] = "Phương thức thanh toán không hợp lệ. Chỉ chấp nhận: momo, vnpay, bank_transfer";
}

if (empty($student_id) || $student_id <= 0) {
    $errors[] = "Student ID không hợp lệ";
}

// Nếu có lỗi, trả về lỗi
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

// Kiểm tra course có tồn tại không và lấy giá
$course->id = $course_id;
if (!$course->readOne()) {
    if (!headers_sent()) {
        http_response_code(404);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không tìm thấy khóa học"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    exit();
}

// Kiểm tra đã enroll chưa (bao gồm cả pending và active)
$check_enrollment = $db->prepare("SELECT id, status FROM enrollments WHERE student_id = ? AND course_id = ?");
$check_enrollment->execute([$student_id, $course_id]);
$existing_enrollment = $check_enrollment->fetch(PDO::FETCH_ASSOC);

if ($existing_enrollment) {
    if ($existing_enrollment['status'] === 'active') {
        if (!headers_sent()) {
            http_response_code(400);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Bạn đã đăng ký khóa học này rồi!"
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
    // Nếu status là 'pending', sử dụng enrollment_id hiện có
    $enrollment_id = $existing_enrollment['id'];
} else {
    // Tạo enrollment mới với status='pending'
    try {
        $create_enrollment = $db->prepare("INSERT INTO enrollments (student_id, course_id, status) VALUES (?, ?, 'pending')");
        $create_enrollment->execute([$student_id, $course_id]);
        $enrollment_id = $db->lastInsertId();
    } catch (PDOException $e) {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể tạo enrollment: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        exit();
    }
}

// Tạo transaction_id
$transaction_id = strtoupper($payment_gateway) . '_' . bin2hex(random_bytes(6));

// Kiểm tra transaction_id đã tồn tại chưa (rất hiếm nhưng vẫn kiểm tra)
$payment->transaction_id = $transaction_id;
$max_retries = 5;
$retry_count = 0;
while ($payment->transactionExists() && $retry_count < $max_retries) {
    $transaction_id = strtoupper($payment_gateway) . '_' . bin2hex(random_bytes(6));
    $payment->transaction_id = $transaction_id;
    $retry_count++;
}

// Gán giá trị cho payment (bao gồm enrollment_id)
$payment->enrollment_id = $enrollment_id;
$payment->student_id = $student_id;
$payment->course_id = $course_id;
$payment->amount = $course->price;
$payment->payment_gateway = $payment_gateway;
$payment->transaction_id = $transaction_id;
$payment->status = 'pending';

// Thực hiện tạo payment
try {
    if ($payment->create()) {
        // Lấy thông tin payment vừa tạo (id đã được set trong create())
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
        
        // Nếu là bank_transfer, không cần tạo payment URL
        if ($payment_gateway === 'bank_transfer') {
            if (!headers_sent()) {
                http_response_code(201);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Tạo thanh toán thành công! Vui lòng quét mã QR để thanh toán.",
                "data" => $payment_data,
                "payment_type" => "qr_code" // Cho biết cần hiển thị QR code
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            // Tích hợp với payment gateway (VNPay hoặc MoMo)
            $gatewayService = new PaymentGatewayService($db);
            $paymentRequestData = array(
                'transaction_id' => $transaction_id,
                'amount' => floatval($course->price),
                'order_info' => 'Thanh toan khoa hoc: ' . $course->title
            );
            
            $gatewayResponse = null;
            $gatewayError = null;
            
            try {
                if ($payment_gateway === 'vnpay') {
                    $gatewayResponse = $gatewayService->createVNPayPayment($paymentRequestData);
                } elseif ($payment_gateway === 'momo') {
                    $gatewayResponse = $gatewayService->createMoMoPayment($paymentRequestData);
                }
            } catch (Exception $e) {
                error_log("Payment gateway error: " . $e->getMessage());
                $gatewayError = $e->getMessage();
            }
            
            if ($gatewayResponse && isset($gatewayResponse['success']) && $gatewayResponse['success']) {
                // Có payment URL từ gateway
                if (!headers_sent()) {
                    http_response_code(201);
                }
                echo json_encode(array(
                    "success" => true,
                    "message" => "Tạo thanh toán thành công! Đang chuyển hướng đến trang thanh toán...",
                    "data" => $payment_data,
                    "payment_url" => $gatewayResponse['payment_url'],
                    "payment_type" => "redirect" // Cho biết cần redirect
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            } else {
                // Nếu không tạo được payment URL (chưa config hoặc lỗi)
                $errorMessage = $gatewayError ?? ($gatewayResponse['message'] ?? 'Payment gateway chưa được cấu hình');
                
                // Log lỗi để debug
                error_log("Payment gateway failed: " . $errorMessage);
                
                // Luôn trả về payment_type là "qr_code" để frontend hiển thị QR code
                if (!headers_sent()) {
                    http_response_code(201);
                }
                echo json_encode(array(
                    "success" => true,
                    "message" => "Tạo thanh toán thành công! Vui lòng quét mã QR để thanh toán.",
                    "data" => $payment_data,
                    "payment_type" => "qr_code", // Luôn là qr_code khi gateway chưa config
                    "warning" => $errorMessage . ". Hệ thống sẽ sử dụng QR code để thanh toán."
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            }
        }
    } else {
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Không thể tạo thanh toán. Vui lòng thử lại sau."
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    error_log("Create payment error: " . $e->getMessage());
    echo json_encode(array(
        "success" => false,
        "message" => "Lỗi khi tạo thanh toán: " . $e->getMessage()
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
}
?>

