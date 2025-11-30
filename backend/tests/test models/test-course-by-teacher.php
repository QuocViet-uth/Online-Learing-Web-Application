<?php
/**
 * File: test-course-by-teacher.php
 * Mục đích: Test lấy lớp học theo giảng viên
 */

require_once 'config/database.php';
require_once 'models/Course.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);

echo "<h2>LỚP HỌC THEO GIẢNG VIÊN</h2>";

// Lấy các lớp của teacher1 (id=2)
$course->teacher_id = 2;

echo "<h3>👨‍🏫 Lớp học của Teacher ID: " . $course->teacher_id . "</h3>";

$stmt = $course->readByTeacher();
$num = $stmt->rowCount();

if($num > 0) {
    echo "📊 Số lớp: <strong>" . $num . "</strong><br><br>";
    
    echo "<ul style='list-style-type: none; padding: 0;'>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<li style='border: 1px solid #ddd; margin-bottom: 10px; padding: 15px; border-radius: 5px;'>";
        echo "<h4 style='margin: 0 0 10px 0;'>📚 " . $row['course_name'] . "</h4>";
        echo "<p style='margin: 5px 0;'><strong>Tiêu đề:</strong> " . $row['title'] . "</p>";
        echo "<p style='margin: 5px 0;'><strong>Giá:</strong> " . number_format($row['price'], 0, ',', '.') . " đ</p>";
        echo "<p style='margin: 5px 0;'><strong>Trạng thái:</strong> " . strtoupper($row['status']) . "</p>";
        echo "</li>";
    }
    
    echo "</ul>";
    
    echo "<br>";
    echo "📈 Tổng số lớp của giảng viên này: " . $course->countByTeacher();
    
} else {
    echo "❌ Giảng viên này chưa có lớp học nào!";
}
?>