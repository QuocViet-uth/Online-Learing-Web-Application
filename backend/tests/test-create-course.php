<?php
/**
 * Test script để kiểm tra API create-course
 * Chạy: php tests/test-create-course.php
 */

// Include files
$base_dir = dirname(__DIR__);
include_once $base_dir . '/config/database.php';
include_once $base_dir . '/models/Course.php';

echo "=== TEST CREATE COURSE ===\n\n";

// Khởi tạo database
$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo "❌ Không thể kết nối database!\n";
    exit(1);
}

echo "✅ Kết nối database thành công\n\n";

$course = new Course($db);

// Test data
$test_data = array(
    'course_name' => 'TEST-001',
    'title' => 'Khóa học test',
    'description' => 'Mô tả khóa học test',
    'price' => 100000,
    'teacher_id' => 2, // teacher1
    'start_date' => '2024-12-01',
    'end_date' => '2024-12-31',
    'status' => 'upcoming',
    'thumbnail' => ''
);

echo "Test data:\n";
print_r($test_data);
echo "\n";

// Set data
$course->course_name = $test_data['course_name'];
$course->title = $test_data['title'];
$course->description = $test_data['description'];
$course->price = $test_data['price'];
$course->teacher_id = $test_data['teacher_id'];
$course->start_date = $test_data['start_date'];
$course->end_date = $test_data['end_date'];
$course->status = $test_data['status'];
$course->thumbnail = $test_data['thumbnail'];

echo "Attempting to create course...\n";

try {
    if ($course->create()) {
        echo "✅ Tạo khóa học thành công!\n";
        echo "Course ID: " . $course->id . "\n";
        
        // Đọc lại để kiểm tra
        $course->id = $course->id;
        if ($course->readOne()) {
            echo "\nCourse details:\n";
            echo "  ID: " . $course->id . "\n";
            echo "  Course Name: " . $course->course_name . "\n";
            echo "  Title: " . $course->title . "\n";
            echo "  Teacher ID: " . $course->teacher_id . "\n";
            echo "  Price: " . $course->price . "\n";
            echo "  Start Date: " . $course->start_date . "\n";
            echo "  End Date: " . $course->end_date . "\n";
            echo "  Status: " . $course->status . "\n";
        }
    } else {
        echo "❌ Không thể tạo khóa học\n";
        $error_info = $db->errorInfo();
        echo "Error info: " . print_r($error_info, true) . "\n";
    }
} catch (Exception $e) {
    echo "❌ Exception: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}

echo "\n=== END TEST ===\n";
?>

