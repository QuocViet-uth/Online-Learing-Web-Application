<?php
/**
 * File: test-submission.php
 * Mục đích: Test Model Submission
 */

require_once 'config/database.php';
require_once 'models/Submission.php';

$database = new Database();
$db = $database->getConnection();

$submission = new Submission($db);

echo "<h2>TEST MODEL SUBMISSION</h2>";

// Test 1: Nộp bài
echo "<h3>📝 Test 1: Học viên nộp bài</h3>";

$submission->assignment_id = 1; // Bài tập tuần 1
$submission->student_id = 4; // student1
$submission->content = "Đây là bài làm của em. Em đã hoàn thành tất cả các yêu cầu trong đề bài.";
$submission->attachment_file = "uploads/student1_assignment1.zip";

// Kiểm tra đã nộp chưa
if($submission->hasSubmitted()) {
    echo "⚠️ Học viên đã nộp bài này rồi!<br>";
} else {
    if($submission->create()) {
        echo "✅ Nộp bài thành công!<br>";
        echo "ID: " . $submission->id . "<br>";
    } else {
        echo "❌ Nộp bài thất bại!<br>";
    }
}

echo "<hr>";

// Test 2: Danh sách bài nộp theo assignment
echo "<h3>📚 Test 2: Danh sách bài nộp của bài tập ID=1</h3>";

$submission->assignment_id = 1;
$stmt = $submission->readByAssignment();
$num = $stmt->rowCount();

if($num > 0) {
    echo "Số bài nộp: <strong>" . $num . "</strong><br><br>";
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background-color: #9C27B0; color: white;'>";
    echo "<th>ID</th>";
    echo "<th>Học viên</th>";
    echo "<th>Nộp lúc</th>";
    echo "<th>Trạng thái</th>";
    echo "<th>Điểm</th>";
    echo "</tr>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Màu status
        $status_color = '';
        $status_text = '';
        switch($row['status']) {
            case 'submitted':
                $status_color = 'orange';
                $status_text = '🟠 Chưa chấm';
                break;
            case 'graded':
                $status_color = 'green';
                $status_text = '🟢 Đã chấm';
                break;
            case 'late':
                $status_color = 'red';
                $status_text = '🔴 Nộp trễ';
                break;
        }
        
        $score_display = $row['score'] ? $row['score'] . " điểm" : "-";
        
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>👨‍🎓 " . $row['student_name'] . "</td>";
        echo "<td>" . date('d/m/Y H:i', strtotime($row['submit_date'])) . "</td>";
        echo "<td style='color: " . $status_color . "; font-weight: bold;'>" . $status_text . "</td>";
        echo "<td align='center'><strong>" . $score_display . "</strong></td>";
        echo "</tr>";
    }
    
    echo "</table>";
    
    echo "<br>";
    
    // Thống kê
    $total = $submission->countByAssignment();
    $ungraded = $submission->countUngraded();
    $avg_score = $submission->getAverageScore();
    
    echo "<h4>📊 Thống kê:</h4>";
    echo "<ul>";
    echo "<li>Tổng số bài nộp: <strong>" . $total . "</strong></li>";
    echo "<li>Bài chưa chấm: <strong>" . $ungraded . "</strong></li>";
    echo "<li>Điểm trung bình: <strong>" . $avg_score . " điểm</strong></li>";
    echo "</ul>";
    
} else {
    echo "Chưa có bài nộp nào!<br>";
}

echo "<hr>";

// Test 3: Chấm điểm
echo "<h3>✍️ Test 3: Giảng viên chấm điểm</h3>";

$submission->id = 1; // Bài nộp ID = 1
$submission->score = 9.5;
$submission->feedback = "Bài làm rất tốt! Code sạch sẽ, logic đúng. Chỉ cần cải thiện thêm phần comments.";

if($submission->grade()) {
    echo "✅ Chấm điểm thành công!<br>";
    echo "Điểm: " . $submission->score . "<br>";
    echo "Feedback: " . $submission->feedback . "<br>";
} else {
    echo "❌ Chấm điểm thất bại!<br>";
}

echo "<hr>";

// Test 4: Chi tiết bài nộp
echo "<h3>📖 Test 4: Chi tiết bài nộp ID=1</h3>";

$submission->id = 1;

if($submission->readOne()) {
    echo "<div style='border: 2px solid #9C27B0; padding: 15px; border-radius: 5px;'>";
    
    echo "<h4>📝 " . $submission->assignment_title . "</h4>";
    
    echo "<p><strong>Khóa học:</strong> " . $submission->course_name . "</p>";
    echo "<p><strong>Học viên:</strong> 👨‍🎓 " . $submission->student_name . "</p>";
    
    echo "<hr>";
    
    echo "<h4>Nội dung bài làm:</h4>";
    echo "<p>" . nl2br($submission->content) . "</p>";
    
    if($submission->attachment_file) {
        echo "<p><strong>📎 File đính kèm:</strong> <a href='" . $submission->attachment_file . "'>Tải về</a></p>";
    }
    
    echo "<p><strong>⏰ Nộp lúc:</strong> " . date('d/m/Y H:i', strtotime($submission->submit_date)) . "</p>";
    
    echo "<hr>";
    
    if($submission->status == 'graded') {
        echo "<div style='background-color: #E8F5E9; padding: 10px; border-radius: 5px;'>";
        echo "<h4 style='color: green;'>✅ Đã chấm điểm</h4>";
        echo "<p><strong>Điểm:</strong> <span style='font-size: 24px; color: green;'>" . $submission->score . "</span></p>";
        echo "<p><strong>Nhận xét:</strong><br>" . nl2br($submission->feedback) . "</p>";
        echo "<p style='font-size: 12px; color: #999;'>Chấm lúc: " . date('d/m/Y H:i', strtotime($submission->graded_at)) . "</p>";
        echo "</div>";
    } else {
        echo "<p style='color: orange; font-weight: bold;'>🟠 Chưa chấm điểm</p>";
    }
    
    echo "</div>";
} else {
    echo "❌ Không tìm thấy bài nộp!<br>";
}

echo "<hr>";

// Test 5: Xem bài nộp của 1 học viên
echo "<h3>👨‍🎓 Test 5: Xem tất cả bài nộp của student1</h3>";

$submission->student_id = 4;
$stmt = $submission->readByStudent();
$num = $stmt->rowCount();

if($num > 0) {
    echo "Tổng số bài đã nộp: <strong>" . $num . "</strong><br><br>";
    
    while($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<div style='border: 1px solid #ddd; padding: 10px; margin-bottom: 10px; border-radius: 5px;'>";
        echo "<strong>📚 " . $row['course_name'] . "</strong><br>";
        echo "Bài tập: " . $row['assignment_title'] . "<br>";
        echo "Nộp lúc: " . date('d/m/Y H:i', strtotime($row['submit_date'])) . "<br>";
        
        if($row['score']) {
            echo "Điểm: <strong style='color: green;'>" . $row['score'] . "/" . $row['max_score'] . "</strong><br>";
        } else {
            echo "Điểm: <span style='color: orange;'>Chưa chấm</span><br>";
        }
        
        echo "</div>";
    }
} else {
    echo "Học viên chưa nộp bài nào!<br>";
}
?>