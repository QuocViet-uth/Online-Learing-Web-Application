<?php
/**
 * File: test-user-login.php
 * Mục đích: Test đăng nhập
 */

require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

echo "<h2>TEST ĐĂNG NHẬP</h2>";

// Test với username và password đúng
$user->username = "admin";
$user->password = "123456";

if($user->login()) {
    echo "✅ Đăng nhập THÀNH CÔNG!<br>";
    echo "🆔 User ID: " . $user->id . "<br>";
    echo "👤 Username: " . $user->username . "<br>";
    echo "📧 Email: " . $user->email . "<br>";
    echo "🎭 Role: " . $user->role . "<br>";
} else {
    echo "❌ Đăng nhập THẤT BẠI!<br>";
    echo "💡 Kiểm tra lại username và password.<br>";
}

echo "<hr>";

// Test với password SAI
echo "<h3>Test với password SAI:</h3>";
$user->username = "admin";
$user->password = "wrong_password";

if($user->login()) {
    echo "✅ Đăng nhập thành công (KHÔNG NÊN XẢY RA!)<br>";
} else {
    echo "✅ Đăng nhập THẤT BẠI (Đúng như mong đợi - password sai)<br>";
}
?>