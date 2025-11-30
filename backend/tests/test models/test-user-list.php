<?php
/**
 * File: test-user-list.php
 * Mục đích: Test lấy danh sách tất cả users
 */

require_once 'config/database.php';
require_once 'models/User.php';

$database = new Database();
$db = $database->getConnection();

$user = new User($db);

echo "<h2>DANH SÁCH TẤT CẢ USERS</h2>";

// Lấy tất cả users
$stmt = $user->readAll();
$num = $stmt->rowCount();

if($num > 0) {
    echo "📊 Tổng số users: <strong>" . $num . "</strong><br><br>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #4CAF50; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>Username</th>";
    echo "<th>Email</th>";
    echo "<th>Phone</th>";
    echo "<th>Role</th>";
    echo "<th>Created At</th>";
    echo "</tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . $row['username'] . "</td>";
        echo "<td>" . $row['email'] . "</td>";
        echo "<td>" . ($row['phone'] ? $row['phone'] : '-') . "</td>";
        echo "<td><strong>" . strtoupper($row['role']) . "</strong></td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['created_at'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br>";
    
    // Đếm số lượng theo từng role
    echo "<h3>Thống kê theo Role:</h3>";
    
    $user->role = "admin";
    echo "👨‍💼 Admin: " . $user->countByRole() . " người<br>";
    
    $user->role = "teacher";
    echo "👨‍🏫 Teacher: " . $user->countByRole() . " người<br>";
    
    $user->role = "student";
    echo "👨‍🎓 Student: " . $user->countByRole() . " người<br>";
    
} else {
    echo "❌ Không có user nào trong database!<br>";
}
?>