<?php
/**
 * File: scripts/update-all-passwords.php
 * Mục đích: Update password cho tất cả users test thành "1111"
 * Chạy: php scripts/update-all-passwords.php
 */

// Kết nối database trực tiếp
$host = 'localhost';
$port = '3307';
$dbname = 'online_learning';
$username = 'root';
$password = 'rootpassword';

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Đang kết nối database...\n";
    echo "Connected to database successfully!\n\n";
    
    // Tạo hash cho password "1111"
    $password_to_hash = '1111';
    $password_hash = password_hash($password_to_hash, PASSWORD_BCRYPT);
    
    echo "Password: $password_to_hash\n";
    echo "Hash: $password_hash\n";
    echo "Hash length: " . strlen($password_hash) . "\n\n";
    
    // Verify hash
    $verify = password_verify($password_to_hash, $password_hash);
    echo "Verify hash: " . ($verify ? 'TRUE ✓' : 'FALSE ✗') . "\n\n";
    
    if (!$verify) {
        die("ERROR: Hash verification failed!\n");
    }
    
    // Danh sách users cần update
    $users = ['quocthanh', 'khanhngan', 'tuandai', 'trinang', 'quocviet'];
    
    echo "Đang update password cho các users...\n\n";
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    
    foreach ($users as $user) {
        $stmt->execute([$password_hash, $user]);
        $affected = $stmt->rowCount();
        
        if ($affected > 0) {
            echo "✓ Đã update password cho: $user\n";
            
            // Verify lại
            $check_stmt = $pdo->prepare("SELECT password FROM users WHERE username = ?");
            $check_stmt->execute([$user]);
            $db_user = $check_stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($db_user) {
                $verify_db = password_verify($password_to_hash, $db_user['password']);
                echo "  Verify trong DB: " . ($verify_db ? 'TRUE ✓' : 'FALSE ✗') . "\n";
                echo "  Hash length: " . strlen($db_user['password']) . "\n";
            }
        } else {
            echo "✗ Không tìm thấy user: $user\n";
        }
    }
    
    echo "\nHoàn thành!\n";
    echo "\nDanh sách users:\n";
    $list_stmt = $pdo->query("SELECT id, username, email, role FROM users WHERE username IN ('" . implode("', '", $users) . "') ORDER BY role, username");
    $all_users = $list_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_users as $user) {
        echo "  - {$user['username']} ({$user['role']}) - {$user['email']}\n";
    }
    
    echo "\nTất cả password: 1111\n";
    
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    echo "Code: " . $e->getCode() . "\n";
}
?>

