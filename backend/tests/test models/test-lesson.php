<?php
/**
 * File: test-lesson.php
 * Mục đích: Test Model Lesson
 */

require_once 'config/database.php';
require_once 'models/Lesson.php';

$database = new Database();
$db = $database->getConnection();

$lesson = new Lesson($db);

echo "<h2>TEST MODEL LESSON</h2>";

// Test 1: Tạo bài giảng mới
echo "<h3>📝 Test 1: Tạo bài giảng mới</h3>";

$lesson->course_id = 1; // Lớp PHP-101
$lesson->title = "Biến và hằng số trong PHP";
$lesson->content = "Trong bài này chúng ta sẽ học về cách khai báo và sử dụng biến, hằng số trong PHP...";
$lesson->video_url = "https://youtube.com/watch?v=example123";
$lesson->order_number = 4;
$lesson->duration = 45;

if($lesson->create()) {
    echo "✅ Tạo bài giảng thành công!<br>";
    echo "ID: " . $lesson->id . "<br>";
} else {
    echo "❌ Tạo bài giảng thất bại!<br>";
}

echo "<hr>";

// Test 2: Lấy danh sách bài giảng của lớp học
echo "<h3>📚 Test 2: Danh sách bài giảng của lớp PHP-101</h3>";

$lesson->course_id = 1;
$stmt = $lesson->readByCourse();
$num = $stmt->rowCount();

if($num > 0) {
    echo "Số bài giảng: <strong>" . $num . "</strong><br><br>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse;'>";
    echo "<tr style='background-color: #2196F3; color: white;'>";
    echo "<th>Thứ tự</th>";
    echo "<th>Tiêu đề</th>";
    echo "<th>Thời lượng</th>";
    echo "<th>Video URL</th>";
    echo "</tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td align='center'><strong>" . $row['order_number'] . "</strong></td>";
        echo "<td>" . $row['title'] . "</td>";
        echo "<td>" . $row['duration'] . " phút</td>";
        echo "<td><a href='" . $row['video_url'] . "' target='_blank'>Xem video</a></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br>";
    
    // Thống kê
    $lesson->course_id = 1;
    $total_lessons = $lesson->countByCourse();
    $total_duration = $lesson->getTotalDuration();
    
    echo "<p>📊 Thống kê:</p>";
    echo "<ul>";
    echo "<li>Tổng số bài: <strong>" . $total_lessons . "</strong></li>";
    echo "<li>Tổng thời lượng: <strong>" . $total_duration . " phút</strong> (~" . round($total_duration/60, 1) . " giờ)</li>";
    echo "</ul>";
    
} else {
    echo "Chưa có bài giảng nào!<br>";
}

echo "<hr>";

// Test 3: Lấy chi tiết 1 bài giảng
echo "<h3>📖 Test 3: Chi tiết bài giảng ID=1</h3>";

$lesson->id = 1;

if($lesson->readOne()) {
    echo "<div style='border: 2px solid #2196F3; padding: 15px; border-radius: 5px;'>";
    echo "<h4>" . $lesson->title . "</h4>";
    echo "<p><strong>Khóa học:</strong> " . $lesson->course_name . "</p>";
    echo "<p><strong>Thứ tự:</strong> Bài " . $lesson->order_number . "</p>";
    echo "<p><strong>Thời lượng:</strong> " . $lesson->duration . " phút</p>";
    echo "<p><strong>Nội dung:</strong><br>" . nl2br($lesson->content) . "</p>";
    echo "<p><strong>Video:</strong> <a href='" . $lesson->video_url . "' target='_blank'>Xem video</a></p>";
    echo "</div>";
} else {
    echo "❌ Không tìm thấy bài giảng!<br>";
}
?>