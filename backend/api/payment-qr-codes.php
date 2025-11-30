<?php
/**
 * File: api/payment-qr-codes.php
 * Mục đích: API quản lý QR codes thanh toán
 * Method: GET, POST, PUT, DELETE
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/PaymentQRCode.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$qrCode = new PaymentQRCode($db);
$method = $_SERVER['REQUEST_METHOD'];

// GET - Lấy danh sách hoặc chi tiết
if ($method === 'GET') {
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $payment_gateway = isset($_GET['payment_gateway']) ? trim($_GET['payment_gateway']) : null;
    
    if ($id) {
        // Lấy 1 QR code theo ID
        $qrCode->id = $id;
        if ($qrCode->readOne()) {
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Lấy thông tin QR code thành công",
                "data" => array(
                    "id" => intval($qrCode->id),
                    "payment_gateway" => $qrCode->payment_gateway,
                    "qr_code_image" => $qrCode->qr_code_image,
                    "account_number" => $qrCode->account_number,
                    "account_name" => $qrCode->account_name,
                    "bank_name" => $qrCode->bank_name,
                    "phone_number" => $qrCode->phone_number,
                    "description" => $qrCode->description,
                    "status" => $qrCode->status,
                    "created_at" => $qrCode->created_at,
                    "updated_at" => $qrCode->updated_at
                )
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            if (!headers_sent()) {
                http_response_code(404);
            }
            echo json_encode(array("success" => false, "message" => "Không tìm thấy QR code."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } elseif ($payment_gateway) {
        // Lấy QR code theo payment gateway (active)
        $qrCode->payment_gateway = $payment_gateway;
        if ($qrCode->readByGateway()) {
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Lấy thông tin QR code thành công",
                "data" => array(
                    "id" => intval($qrCode->id),
                    "payment_gateway" => $qrCode->payment_gateway,
                    "qr_code_image" => $qrCode->qr_code_image,
                    "account_number" => $qrCode->account_number,
                    "account_name" => $qrCode->account_name,
                    "bank_name" => $qrCode->bank_name,
                    "phone_number" => $qrCode->phone_number,
                    "description" => $qrCode->description,
                    "status" => $qrCode->status
                )
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            if (!headers_sent()) {
                http_response_code(404);
            }
            echo json_encode(array("success" => false, "message" => "Không tìm thấy QR code cho phương thức thanh toán này."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        // Lấy tất cả QR codes
        $stmt = $qrCode->readAll();
        $num = $stmt->rowCount();
        
        $qr_codes_arr = array();
        $qr_codes_arr["success"] = true;
        $qr_codes_arr["message"] = "Lấy danh sách QR codes thành công";
        $qr_codes_arr["total"] = $num;
        $qr_codes_arr["data"] = array();
        
        if($num > 0) {
            while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                extract($row);
                
                $qr_code_item = array(
                    "id" => intval($id),
                    "payment_gateway" => $payment_gateway,
                    "qr_code_image" => $qr_code_image,
                    "account_number" => $account_number,
                    "account_name" => $account_name,
                    "bank_name" => $bank_name,
                    "phone_number" => $phone_number,
                    "description" => $description,
                    "status" => $status,
                    "created_at" => $created_at,
                    "updated_at" => $updated_at
                );
                
                array_push($qr_codes_arr["data"], $qr_code_item);
            }
        }
        
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode($qr_codes_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

// POST - Tạo mới hoặc cập nhật
elseif ($method === 'POST') {
    $raw_input = file_get_contents("php://input");
    $data = json_decode($raw_input, true);
    
    if (!$data || json_last_error() !== JSON_ERROR_NONE) {
        $data = $_POST;
    }
    
    // Kiểm tra _method để hỗ trợ PUT/DELETE
    $isUpdate = isset($data['_method']) && $data['_method'] === 'PUT';
    $isDelete = isset($data['_method']) && $data['_method'] === 'DELETE';
    
    if ($isDelete) {
        // DELETE
        $id = isset($data['id']) ? intval($data['id']) : (isset($_GET['id']) ? intval($_GET['id']) : 0);
        
        if (empty($id) || $id <= 0) {
            if (!headers_sent()) {
                http_response_code(400);
            }
            echo json_encode(array("success" => false, "message" => "ID không hợp lệ"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        
        $qrCode->id = $id;
        if (!$qrCode->readOne()) {
            if (!headers_sent()) {
                http_response_code(404);
            }
            echo json_encode(array("success" => false, "message" => "Không tìm thấy QR code"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        
        if ($qrCode->delete()) {
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode(array("success" => true, "message" => "Xóa QR code thành công!"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode(array("success" => false, "message" => "Không thể xóa QR code"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } elseif ($isUpdate) {
        // UPDATE
        $id = isset($data['id']) ? intval($data['id']) : 0;
        
        if (empty($id) || $id <= 0) {
            if (!headers_sent()) {
                http_response_code(400);
            }
            echo json_encode(array("success" => false, "message" => "ID không hợp lệ"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        
        $qrCode->id = $id;
        if (!$qrCode->readOne()) {
            if (!headers_sent()) {
                http_response_code(404);
            }
            echo json_encode(array("success" => false, "message" => "Không tìm thấy QR code"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            exit();
        }
        
        // Validate và cập nhật
        $qrCode->payment_gateway = isset($data['payment_gateway']) ? trim($data['payment_gateway']) : $qrCode->payment_gateway;
        $qrCode->qr_code_image = isset($data['qr_code_image']) ? trim($data['qr_code_image']) : $qrCode->qr_code_image;
        $qrCode->account_number = isset($data['account_number']) ? trim($data['account_number']) : $qrCode->account_number;
        $qrCode->account_name = isset($data['account_name']) ? trim($data['account_name']) : $qrCode->account_name;
        $qrCode->bank_name = isset($data['bank_name']) ? trim($data['bank_name']) : $qrCode->bank_name;
        $qrCode->phone_number = isset($data['phone_number']) ? trim($data['phone_number']) : $qrCode->phone_number;
        $qrCode->description = isset($data['description']) ? trim($data['description']) : $qrCode->description;
        $qrCode->status = isset($data['status']) ? trim($data['status']) : $qrCode->status;
        
        if ($qrCode->update()) {
            $qrCode->readOne();
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Cập nhật QR code thành công!",
                "data" => array(
                    "id" => intval($qrCode->id),
                    "payment_gateway" => $qrCode->payment_gateway,
                    "qr_code_image" => $qrCode->qr_code_image,
                    "account_number" => $qrCode->account_number,
                    "account_name" => $qrCode->account_name,
                    "bank_name" => $qrCode->bank_name,
                    "phone_number" => $qrCode->phone_number,
                    "description" => $qrCode->description,
                    "status" => $qrCode->status
                )
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        } else {
            if (!headers_sent()) {
                http_response_code(500);
            }
            echo json_encode(array("success" => false, "message" => "Không thể cập nhật QR code"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        // CREATE
        $errors = array();
        
        if (empty($data['payment_gateway'])) {
            $errors[] = "Phương thức thanh toán không được để trống";
        } elseif (!in_array($data['payment_gateway'], ['momo', 'vnpay', 'bank_transfer'])) {
            $errors[] = "Phương thức thanh toán không hợp lệ";
        }
        
        if (empty($data['qr_code_image'])) {
            $errors[] = "Ảnh QR code không được để trống";
        }
        
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
        
        $qrCode->payment_gateway = trim($data['payment_gateway']);
        $qrCode->qr_code_image = trim($data['qr_code_image']);
        $qrCode->account_number = isset($data['account_number']) ? trim($data['account_number']) : null;
        $qrCode->account_name = isset($data['account_name']) ? trim($data['account_name']) : null;
        $qrCode->bank_name = isset($data['bank_name']) ? trim($data['bank_name']) : null;
        $qrCode->phone_number = isset($data['phone_number']) ? trim($data['phone_number']) : null;
        $qrCode->description = isset($data['description']) ? trim($data['description']) : null;
        $qrCode->status = isset($data['status']) ? trim($data['status']) : 'active';
        
        try {
            if ($qrCode->create()) {
                $qrCode->readOne();
                if (!headers_sent()) {
                    http_response_code(201);
                }
                echo json_encode(array(
                    "success" => true,
                    "message" => "Tạo QR code thành công!",
                    "data" => array(
                        "id" => intval($qrCode->id),
                        "payment_gateway" => $qrCode->payment_gateway,
                        "qr_code_image" => $qrCode->qr_code_image,
                        "account_number" => $qrCode->account_number,
                        "account_name" => $qrCode->account_name,
                        "bank_name" => $qrCode->bank_name,
                        "phone_number" => $qrCode->phone_number,
                        "description" => $qrCode->description,
                        "status" => $qrCode->status
                    )
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
            } else {
                if (!headers_sent()) {
                    http_response_code(500);
                }
                echo json_encode(array("success" => false, "message" => "Không thể tạo QR code"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } catch (Exception $e) {
            if (!headers_sent()) {
                http_response_code(500);
            }
            error_log("Create QR code error: " . $e->getMessage());
            echo json_encode(array(
                "success" => false,
                "message" => "Lỗi khi tạo QR code: " . $e->getMessage()
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
        }
    }
} else {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array("success" => false, "message" => "Method not allowed"), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

