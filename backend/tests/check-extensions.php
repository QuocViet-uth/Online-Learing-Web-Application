<?php
/**
 * File: tests/check-extensions.php
 * Mục đích: Kiểm tra các PHP extensions cần thiết
 */

echo "<h2>PHP Extensions Check</h2>";
echo "<pre>";

// Kiểm tra PDO
echo "PDO Extension: " . (extension_loaded('pdo') ? "✓ Enabled" : "✗ NOT ENABLED") . "\n";

// Kiểm tra PDO MySQL
echo "PDO MySQL Extension: " . (extension_loaded('pdo_mysql') ? "✓ Enabled" : "✗ NOT ENABLED") . "\n";

// Kiểm tra MySQLi (backup)
echo "MySQLi Extension: " . (extension_loaded('mysqli') ? "✓ Enabled" : "✗ NOT ENABLED") . "\n";

// Kiểm tra các PDO drivers có sẵn
echo "\nAvailable PDO Drivers: " . implode(", ", PDO::getAvailableDrivers()) . "\n";

// Kiểm tra PHP version
echo "\nPHP Version: " . phpversion() . "\n";

// Kiểm tra php.ini location
echo "php.ini Location: " . php_ini_loaded_file() . "\n";

// Kiểm tra extension directory
echo "Extension Directory: " . ini_get('extension_dir') . "\n";

echo "\n</pre>";

// Hướng dẫn
if (!extension_loaded('pdo_mysql')) {
    echo "<h3 style='color: red;'>⚠️ PDO MySQL Extension chưa được bật!</h3>";
    echo "<h4>Cách khắc phục:</h4>";
    echo "<ol>";
    echo "<li>Tìm file php.ini tại: <strong>" . php_ini_loaded_file() . "</strong></li>";
    echo "<li>Mở file php.ini bằng text editor (Notepad++, VS Code, etc.)</li>";
    echo "<li>Tìm dòng: <code>;extension=pdo_mysql</code></li>";
    echo "<li>Xóa dấu <code>;</code> ở đầu dòng để thành: <code>extension=pdo_mysql</code></li>";
    echo "<li>Nếu không tìm thấy, thêm dòng mới: <code>extension=pdo_mysql</code></li>";
    echo "<li>Lưu file và <strong>restart PHP server</strong></li>";
    echo "</ol>";
    
    echo "<h4>Hoặc sử dụng XAMPP/WAMP:</h4>";
    echo "<p>XAMPP/WAMP đã có sẵn PDO MySQL extension được bật.</p>";
}








