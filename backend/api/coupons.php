<?php
/**
 * File: api/coupons.php
 * Mục đích: API lấy danh sách mã giảm giá
 * Method: GET
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/Coupon.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array("success" => false, "message" => "Không thể kết nối database."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$coupon = new Coupon($db);

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Tự động cập nhật trạng thái coupon
    $coupon->updateStatusAutomatically();
    
    $id = isset($_GET['id']) ? intval($_GET['id']) : null;
    $code = isset($_GET['code']) ? trim($_GET['code']) : null;
    
    if ($id) {
        // Lấy 1 coupon theo ID
        $coupon->id = $id;
        if ($coupon->readOne()) {
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Lấy thông tin mã giảm giá thành công",
                "data" => array(
                    "id" => intval($coupon->id),
                    "code" => $coupon->code,
                    "discount_percent" => floatval($coupon->discount_percent),
                    "description" => $coupon->description,
                    "valid_from" => $coupon->valid_from,
                    "valid_until" => $coupon->valid_until,
                    "max_uses" => $coupon->max_uses !== null ? intval($coupon->max_uses) : null,
                    "used_count" => intval($coupon->used_count),
                    "status" => $coupon->status,
                    "created_at" => $coupon->created_at,
                    "updated_at" => $coupon->updated_at
                )
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            if (!headers_sent()) {
                http_response_code(404);
            }
            echo json_encode(array("success" => false, "message" => "Không tìm thấy mã giảm giá."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } elseif ($code) {
        // Lấy coupon theo code
        $coupon->code = $code;
        if ($coupon->readByCode()) {
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode(array(
                "success" => true,
                "message" => "Lấy thông tin mã giảm giá thành công",
                "data" => array(
                    "id" => intval($coupon->id),
                    "code" => $coupon->code,
                    "discount_percent" => floatval($coupon->discount_percent),
                    "description" => $coupon->description,
                    "valid_from" => $coupon->valid_from,
                    "valid_until" => $coupon->valid_until,
                    "max_uses" => $coupon->max_uses !== null ? intval($coupon->max_uses) : null,
                    "used_count" => intval($coupon->used_count),
                    "status" => $coupon->status,
                    "created_at" => $coupon->created_at,
                    "updated_at" => $coupon->updated_at
                )
            ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        } else {
            if (!headers_sent()) {
                http_response_code(404);
            }
            echo json_encode(array("success" => false, "message" => "Không tìm thấy mã giảm giá."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } else {
        // Lấy tất cả coupons
        $stmt = $coupon->readAll();
        $num = $stmt->rowCount();
        
        $coupons_arr = array();
        $coupons_arr["success"] = true;
        $coupons_arr["message"] = "Lấy danh sách mã giảm giá thành công";
        $coupons_arr["data"] = array();
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $coupon_item = array(
                "id" => intval($row['id']),
                "code" => $row['code'],
                "discount_percent" => floatval($row['discount_percent']),
                "description" => $row['description'],
                "valid_from" => $row['valid_from'],
                "valid_until" => $row['valid_until'],
                "max_uses" => $row['max_uses'] !== null ? intval($row['max_uses']) : null,
                "used_count" => intval($row['used_count']),
                "status" => $row['status'],
                "created_at" => $row['created_at'],
                "updated_at" => $row['updated_at']
            );
            array_push($coupons_arr["data"], $coupon_item);
        }
        
        if (!headers_sent()) {
            http_response_code(200);
        }
        echo json_encode($coupons_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} else {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array("success" => false, "message" => "Method Not Allowed."), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

