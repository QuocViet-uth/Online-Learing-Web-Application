<?php
/**
 * File: test-course-list.php
 * Mục đích: Test lấy danh sách lớp học
 */

require_once 'config/database.php';
require_once 'models/Course.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);

echo "<h2>DANH SÁCH TẤT CẢ LỚP HỌC</h2>";

$stmt = $course->readAll();
$num = $stmt->rowCount();

if($num > 0) {
    echo "📊 Tổng số lớp học: <strong>" . $num . "</strong><br><br>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #4CAF50; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>Course Name</th>";
    echo "<th>Title</th>";
    echo "<th>Price</th>";
    echo "<th>Teacher</th>";
    echo "<th>Status</th>";
    echo "<th>Start Date</th>";
    echo "<th>End Date</th>";
    echo "</tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Màu status
        $status_color = '';
        switch($row['status']) {
            case 'active':
                $status_color = 'green';
                break;
            case 'upcoming':
                $status_color = 'orange';
                break;
            case 'closed':
                $status_color = 'red';
                break;
        }
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . $row['course_name'] . "</strong></td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . number_format($row['price'], 0, ',', '.') . " đ</td>";
        echo "<td>👨‍🏫 " . $row['teacher_name'] . "</td>";
        echo "<td style='color: " . $status_color . "; font-weight: bold;'>" . strtoupper($row['status']) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($row['start_date'])) . "</td>";
        echo "<td>" . date('d/m/Y', strtotime($row['end_date'])) . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br>";
    
    // Thống kê
    echo "<h3>📊 Thống kê:</h3>";
    echo "📚 Tổng số lớp: " . $course->countAll() . "<br>";
    
    $course->status = "active";
    echo "🟢 Lớp đang mở: " . $course->countByStatus() . "<br>";
    
    $course->status = "upcoming";
    echo "🟠 Lớp sắp mở: " . $course->countByStatus() . "<br>";
    
    $course->status = "closed";
    echo "🔴 Lớp đã đóng: " . $course->countByStatus() . "<br>";
    
} else {
    echo "❌ Không có lớp học nào!<br>";
}
?>