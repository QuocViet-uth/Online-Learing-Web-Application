<?php
/**
 * File: scripts/fix-passwords.php
 * Mục đích: Fix password cho các users test
 */

// Tạo hash mới cho password "1111"
$password = '1111';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "Password: $password\n";
echo "Hash: $hash\n\n";

// Verify hash
$verify = password_verify($password, $hash);
echo "Verify: " . ($verify ? 'TRUE' : 'FALSE') . "\n\n";

// SQL để update
$users = ['quocthanh', 'khanhngan', 'tuandai', 'trinang', 'quocviet'];

echo "Chạy các lệnh SQL sau:\n\n";
echo "USE online_learning;\n\n";

foreach ($users as $user) {
    echo "UPDATE users SET password = '$hash' WHERE username = '$user';\n";
}

echo "\nHoặc update tất cả cùng lúc:\n";
echo "UPDATE users SET password = '$hash' WHERE username IN ('" . implode("', '", $users) . "');\n";
?>

