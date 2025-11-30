<?php
/**
 * File: test-user-create.php
 * Mục đích: Test tạo user mới
 */

// Include các file cần thiết
require_once 'config/database.php';
require_once 'models/User.php';

// Kết nối database
$database = new Database();
$db = $database->getConnection();

// Tạo instance của User
$user = new User($db);

// Set giá trị cho user mới
$user->username = "testuser";
$user->password = "123456";
$user->email = "testuser@example.com";
$user->phone = "0123456789";
$user->avatar = "default-avatar.png";
$user->role = "student";

echo "<h2>TEST TẠO USER MỚI</h2>";

// Kiểm tra username đã tồn tại chưa
if($user->usernameExists()) {
    echo "❌ Username 'testuser' đã tồn tại!<br>";
    echo "💡 Thử đổi username khác hoặc xóa user cũ trong database.<br>";
} else {
    // Thực hiện tạo user
    if($user->create()) {
        echo "✅ Tạo user THÀNH CÔNG!<br>";
        echo "🆔 User ID: " . $user->id . "<br>";
        echo "👤 Username: " . $user->username . "<br>";
        echo "📧 Email: " . $user->email . "<br>";
        echo "🎭 Role: " . $user->role . "<br>";
    } else {
        echo "❌ Tạo user THẤT BẠI!<br>";
    }
}
?>