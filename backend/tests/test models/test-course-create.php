<?php
/**
 * File: test-course-create.php
 * Mục đích: Test tạo lớp học mới
 */

require_once 'config/database.php';
require_once 'models/Course.php';

$database = new Database();
$db = $database->getConnection();

$course = new Course($db);

echo "<h2>TEST TẠO LỚP HỌC MỚI</h2>";

// Set giá trị cho lớp học mới
$course->course_name = "ReactJS-301";
$course->title = "Lập trình ReactJS nâng cao";
$course->description = "Khóa học ReactJS từ cơ bản đến nâng cao, bao gồm Hooks, Redux, và các best practices";
$course->price = 799000.00;
$course->teacher_id = 2; // teacher1 (id=2)
$course->start_date = "2024-06-01";
$course->end_date = "2024-08-31";
$course->status = "upcoming";
$course->thumbnail = "react-course.jpg";

if($course->create()) {
    echo "✅ Tạo lớp học THÀNH CÔNG!<br>";
    echo "🆔 Course ID: " . $course->id . "<br>";
    echo "📚 Course Name: " . $course->course_name . "<br>";
    echo "📖 Title: " . $course->title . "<br>";
    echo "💰 Price: " . number_format($course->price, 0, ',', '.') . " VNĐ<br>";
    echo "👨‍🏫 Teacher ID: " . $course->teacher_id . "<br>";
    echo "📅 Start Date: " . $course->start_date . "<br>";
    echo "📅 End Date: " . $course->end_date . "<br>";
    echo "🎯 Status: " . $course->status . "<br>";
} else {
    echo "❌ Tạo lớp học THẤT BẠI!<br>";
}
?>