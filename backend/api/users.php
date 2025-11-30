<?php
/**
 * File: api/users.php
 * Mục đích: API quản lý users (lấy danh sách, lấy theo ID)
 * Method: GET
 * Parameters:
 *   - id (optional): ID của user cần lấy
 * Response: JSON
 */

require_once __DIR__ . '/../config/headers.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../models/User.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Không thể kết nối database"
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit();
}

$user = new User($db);
$method = $_SERVER['REQUEST_METHOD'];

// GET: Lấy danh sách users hoặc 1 user theo ID
if ($method === 'GET') {
    try {
        $id = isset($_GET['id']) ? intval($_GET['id']) : null;
        
        if ($id) {
            // Lấy 1 user theo ID
            $user->id = $id;
            if ($user->readOne()) {
                if (!headers_sent()) {
                    http_response_code(200);
                }
                echo json_encode(array(
                    "success" => true,
                    "message" => "Lấy thông tin user thành công",
                    "data" => array(
                        "id" => intval($user->id),
                        "username" => $user->username,
                        "full_name" => $user->full_name,
                        "date_of_birth" => $user->date_of_birth,
                        "gender" => $user->gender,
                        "school" => $user->school,
                        "email" => $user->email,
                        "phone" => $user->phone,
                        "avatar" => $user->avatar,
                        "role" => $user->role,
                        "created_at" => $user->created_at,
                        "updated_at" => $user->updated_at
                    )
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            } else {
                if (!headers_sent()) {
                    http_response_code(404);
                }
                echo json_encode(array(
                    "success" => false,
                    "message" => "Không tìm thấy user"
                ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        } else {
            // Lấy tất cả users
            $stmt = $user->readAll();
            $num = $stmt->rowCount();
            
            $users_arr = array();
            $users_arr["success"] = true;
            $users_arr["message"] = "Lấy danh sách users thành công";
            $users_arr["data"] = array();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $user_item = array(
                    "id" => intval($row['id']),
                    "username" => $row['username'],
                    "full_name" => isset($row['full_name']) ? $row['full_name'] : null,
                    "date_of_birth" => isset($row['date_of_birth']) ? $row['date_of_birth'] : null,
                    "gender" => isset($row['gender']) ? $row['gender'] : null,
                    "school" => isset($row['school']) ? $row['school'] : null,
                    "email" => $row['email'],
                    "phone" => $row['phone'],
                    "avatar" => $row['avatar'],
                    "role" => $row['role'],
                    "created_at" => $row['created_at'],
                    "updated_at" => $row['updated_at']
                );
                array_push($users_arr["data"], $user_item);
            }
            
            if (!headers_sent()) {
                http_response_code(200);
            }
            echo json_encode($users_arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    } catch (Exception $e) {
        error_log("Users API - Exception: " . $e->getMessage());
        if (!headers_sent()) {
            http_response_code(500);
        }
        echo json_encode(array(
            "success" => false,
            "message" => "Lỗi server khi lấy danh sách users: " . $e->getMessage()
        ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
} else {
    if (!headers_sent()) {
        http_response_code(405);
    }
    echo json_encode(array(
        "success" => false,
        "message" => "Method Not Allowed. Chỉ hỗ trợ GET."
    ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
?>

