<?php
/**
 * File: test-assignment.php
 * Mục đích: Test Model Assignment
 */

require_once 'config/database.php';
require_once 'models/Assignment.php';

$database = new Database();
$db = $database->getConnection();

$assignment = new Assignment($db);

echo "<h2>TEST MODEL ASSIGNMENT</h2>";

// Test 1: Tạo bài tập mới
echo "<h3>📝 Test 1: Tạo bài tập mới</h3>";

$assignment->course_id = 1; // Lớp PHP-101
$assignment->title = "Bài tập tuần 3";
$assignment->description = "Viết chương trình PHP để tính tổng các số từ 1 đến 100";
$assignment->assignment_type = "homework";
$assignment->attachment_file = "";
$assignment->deadline = "2024-12-31 23:59:59";
$assignment->max_score = 10.00;

if($assignment->create()) {
    echo "✅ Tạo bài tập thành công!<br>";
    echo "ID: " . $assignment->id . "<br>";
} else {
    echo "❌ Tạo bài tập thất bại!<br>";
}

echo "<hr>";

// Test 2: Danh sách bài tập của lớp học
echo "<h3>📚 Test 2: Danh sách bài tập lớp PHP-101</h3>";

$assignment->course_id = 1;
$stmt = $assignment->readByCourse();
$num = $stmt->rowCount();

if($num > 0) {
    echo "Số bài tập: <strong>" . $num . "</strong><br><br>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #FF9800; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>Tiêu đề</th>";
    echo "<th>Loại</th>";
    echo "<th>Deadline</th>";
    echo "<th>Điểm tối đa</th>";
    echo "<th>Trạng thái</th>";
    echo "</tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Kiểm tra đã quá hạn chưa
        $is_overdue = strtotime($row['deadline']) < time();
        $status = $is_overdue ? "🔴 Quá hạn" : "🟢 Còn hạn";
        $status_color = $is_overdue ? "red" : "green";
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td><strong>" . $row['title'] . "</strong></td>";
        echo "<td>" . strtoupper($row['assignment_type']) . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['deadline'])) . "</td>";
        echo "<td align='center'>" . $row['max_score'] . " điểm</td>";
        echo "<td style='color: " . $status_color . "; font-weight: bold;'>" . $status . "</td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br>";
    echo "📊 Tổng số bài tập: " . $assignment->countByCourse();
    
} else {
    echo "Chưa có bài tập nào!<br>";
}

echo "<hr>";

// Test 3: Chi tiết bài tập
echo "<h3>📖 Test 3: Chi tiết bài tập ID=1</h3>";

$assignment->id = 1;

if($assignment->readOne()) {
    echo "<div style='border: 2px solid #FF9800; padding: 15px; border-radius: 5px;'>";
    echo "<h4>📋 " . $assignment->title . "</h4>";
    echo "<p><strong>Khóa học:</strong> " . $assignment->course_name . "</p>";
    echo "<p><strong>Loại:</strong> " . strtoupper($assignment->assignment_type) . "</p>";
    echo "<p><strong>Mô tả:</strong><br>" . nl2br($assignment->description) . "</p>";
    echo "<p><strong>⏰ Deadline:</strong> " . date('d/m/Y H:i', strtotime($assignment->deadline)) . "</p>";
    echo "<p><strong>📊 Điểm tối đa:</strong> " . $assignment->max_score . " điểm</p>";
    
    if($assignment->attachment_file) {
        echo "<p><strong>📎 File đính kèm:</strong> <a href='" . $assignment->attachment_file . "'>Tải về</a></p>";
    }
    
    echo "</div>";
} else {
    echo "❌ Không tìm thấy bài tập!<br>";
}
?>