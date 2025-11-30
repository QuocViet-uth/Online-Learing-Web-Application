<?php
/**
 * Script cập nhật password cho tất cả users mẫu thành "123456"
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("❌ Không thể kết nối database\n");
}

echo "🔧 Đang cập nhật password cho users...\n\n";

// Hash password "123456"
$password_hash = password_hash('123456', PASSWORD_BCRYPT);

// Danh sách users cần update
$users = ['admin', 'teacher1', 'teacher2', 'student1', 'student2'];

try {
    $db->beginTransaction();
    
    $stmt = $db->prepare("UPDATE users SET password = ? WHERE username = ?");
    
    foreach ($users as $username) {
        $stmt->execute([$password_hash, $username]);
        echo "✅ Đã cập nhật password cho: $username\n";
    }
    
    $db->commit();
    echo "\n✅ Hoàn tất! Tất cả users đã có password: 123456\n";
    
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

?>


