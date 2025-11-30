<?php
/**
 * File: test-connection.php
 * Mục đích: Test kết nối database
 */

// Include file database
require_once 'config/database.php';

// Tạo instance của Database class
$database = new Database();

// Gọi hàm getConnection
$conn = $database->getConnection();

// Kiểm tra kết nối
if($conn != null) {
    echo "✅ Kết nối database THÀNH CÔNG!<br>";
    echo "📊 Database: online_learning<br>";
    echo "🖥️ Server: localhost<br>";
    
    // Test query đơn giản
    try {
        $query = "SELECT COUNT(*) as total FROM users";
        $stmt = $conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch();
        
        echo "👥 Số lượng users trong database: " . $row['total'] . "<br>";
        echo "<br>🎉 Database hoạt động hoàn hảo!";
        
    } catch(PDOException $e) {
        echo "❌ Lỗi khi query: " . $e->getMessage();
    }
    
} else {
    echo "❌ Kết nối database THẤT BẠI!<br>";
    echo "Vui lòng kiểm tra lại thông tin kết nối.";
}

// Đóng kết nối
$database->closeConnection();
?>