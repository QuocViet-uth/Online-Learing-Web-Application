<?php
/**
 * File: scripts/reset-test-users.php
 * Mục đích: Reset các tài khoản test với thông tin mới
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Không thể kết nối database\n");
}

// Hash password: 1111
$password_hash = password_hash('1111', PASSWORD_BCRYPT);

echo "Đang reset các tài khoản test...\n";
echo "Password hash: $password_hash\n\n";

// Xóa các user cũ (nếu có)
$old_users = ['teacher1', 'teacher2', 'student1', 'student2', 'admin'];
foreach ($old_users as $username) {
    $stmt = $db->prepare("DELETE FROM users WHERE username = ?");
    $stmt->execute([$username]);
    echo "Đã xóa user cũ: $username\n";
}

// Tạo các user mới
$users = [
    ['username' => 'quocthanh', 'role' => 'teacher', 'email' => 'quocthanh@example.com'],
    ['username' => 'khanhngan', 'role' => 'teacher', 'email' => 'khanhngan@example.com'],
    ['username' => 'tuandai', 'role' => 'student', 'email' => 'tuandai@example.com'],
    ['username' => 'trinang', 'role' => 'student', 'email' => 'trinang@example.com'],
    ['username' => 'quocviet', 'role' => 'admin', 'email' => 'quocviet@example.com'],
];

$stmt = $db->prepare("INSERT INTO users (username, password, email, role) VALUES (?, ?, ?, ?)");

foreach ($users as $user) {
    try {
        $stmt->execute([
            $user['username'],
            $password_hash,
            $user['email'],
            $user['role']
        ]);
        echo "✓ Đã tạo user: {$user['username']} ({$user['role']})\n";
    } catch (PDOException $e) {
        // Nếu user đã tồn tại, update password
        if ($e->getCode() == 23000) { // Duplicate entry
            $update_stmt = $db->prepare("UPDATE users SET password = ?, email = ?, role = ? WHERE username = ?");
            $update_stmt->execute([
                $password_hash,
                $user['email'],
                $user['role'],
                $user['username']
            ]);
            echo "✓ Đã cập nhật user: {$user['username']} ({$user['role']})\n";
        } else {
            echo "✗ Lỗi khi tạo user {$user['username']}: " . $e->getMessage() . "\n";
        }
    }
}

echo "\nHoàn thành! Danh sách users:\n";
$stmt = $db->query("SELECT id, username, email, role FROM users ORDER BY role, username");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    echo "  - {$user['username']} ({$user['role']}) - {$user['email']}\n";
}

echo "\nTất cả password: 1111\n";
?>

