<?php
/**
 * File: test-course-detail.php
 * Mục đích: Test lấy chi tiết lớp học
 */

require_once 'config/database.php';
require_once 'models/Course.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);

echo "<h2>CHI TIẾT LỚP HỌC</h2>";

// Lấy lớp học có ID = 1
$course->id = 1;

if($course->readOne()) {
    echo "<div style='border: 2px solid #4CAF50; padding: 20px; border-radius: 10px; background-color: #f9f9f9;'>";
    
    echo "<h3 style='color: #4CAF50;'>📚 " . $course->title . "</h3>";
    
    echo "<p><strong>Course Name:</strong> " . $course->course_name . "</p>";
    
    echo "<p><strong>Mô tả:</strong><br>" . nl2br($course->description) . "</p>";
    
    echo "<p><strong>💰 Học phí:</strong> " . number_format($course->price, 0, ',', '.') . " VNĐ</p>";
    
    echo "<hr>";
    
    echo "<h4>👨‍🏫 Thông tin giảng viên:</h4>";
    echo "<p><strong>Tên:</strong> " . $course->teacher_name . "</p>";
    echo "<p><strong>Email:</strong> " . $course->teacher_email . "</p>";
    
    echo "<hr>";
    
    echo "<p><strong>📅 Thời gian:</strong></p>";
    echo "<p>Bắt đầu: " . date('d/m/Y', strtotime($course->start_date)) . "</p>";
    echo "<p>Kết thúc: " . date('d/m/Y', strtotime($course->end_date)) . "</p>";
    
    $status_text = '';
    switch($course->status) {
        case 'active':
            $status_text = '🟢 Đang mở';
            break;
        case 'upcoming':
            $status_text = '🟠 Sắp mở';
            break;
        case 'closed':
            $status_text = '🔴 Đã đóng';
            break;
    }
    echo "<p><strong>Trạng thái:</strong> " . $status_text . "</p>";
    
    echo "<hr>";
    
    echo "<p style='font-size: 12px; color: #999;'>";
    echo "Tạo lúc: " . date('d/m/Y H:i', strtotime($course->created_at)) . "<br>";
    echo "Cập nhật: " . date('d/m/Y H:i', strtotime($course->updated_at));
    echo "</p>";
    
    echo "</div>";
    
} else {
    echo "❌ Không tìm thấy lớp học với ID = " . $course->id;
}
?>