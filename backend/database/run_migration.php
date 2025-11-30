<?php
/**
 * Script chạy migration để thêm loại bài tập (homework/quiz)
 * Chạy file này một lần để cập nhật database
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Không thể kết nối database. Vui lòng kiểm tra cấu hình.\n");
}

echo "Bắt đầu chạy migration...\n\n";

try {
    // Kiểm tra xem cột type đã tồn tại chưa
    $checkColumn = $db->query("SHOW COLUMNS FROM assignments LIKE 'type'");
    if ($checkColumn->rowCount() > 0) {
        echo "✓ Cột 'type' đã tồn tại trong bảng assignments\n";
    } else {
        echo "Thêm cột 'type' vào bảng assignments...\n";
        $db->exec("ALTER TABLE assignments 
                   ADD COLUMN type ENUM('homework', 'quiz') DEFAULT 'homework' AFTER description");
        echo "✓ Đã thêm cột 'type'\n";
    }
    
    // Kiểm tra và tạo bảng quiz_questions
    $checkTable = $db->query("SHOW TABLES LIKE 'quiz_questions'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Bảng 'quiz_questions' đã tồn tại\n";
    } else {
        echo "Tạo bảng quiz_questions...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS quiz_questions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            assignment_id INT NOT NULL,
            question_text TEXT NOT NULL,
            order_number INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
            INDEX idx_assignment_id (assignment_id),
            INDEX idx_order_number (order_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ Đã tạo bảng 'quiz_questions'\n";
    }
    
    // Kiểm tra và tạo bảng quiz_answers
    $checkTable = $db->query("SHOW TABLES LIKE 'quiz_answers'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Bảng 'quiz_answers' đã tồn tại\n";
    } else {
        echo "Tạo bảng quiz_answers...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS quiz_answers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            question_id INT NOT NULL,
            answer_text TEXT NOT NULL,
            is_correct BOOLEAN DEFAULT FALSE,
            order_number INT NOT NULL DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
            INDEX idx_question_id (question_id),
            INDEX idx_order_number (order_number)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ Đã tạo bảng 'quiz_answers'\n";
    }
    
    // Kiểm tra và tạo bảng quiz_submissions
    $checkTable = $db->query("SHOW TABLES LIKE 'quiz_submissions'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Bảng 'quiz_submissions' đã tồn tại\n";
    } else {
        echo "Tạo bảng quiz_submissions...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS quiz_submissions (
            id INT PRIMARY KEY AUTO_INCREMENT,
            assignment_id INT NOT NULL,
            student_id INT NOT NULL,
            submitted_at TIMESTAMP NULL,
            score DECIMAL(5, 2),
            status ENUM('in_progress', 'submitted', 'graded') DEFAULT 'in_progress',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (assignment_id) REFERENCES assignments(id) ON DELETE CASCADE,
            FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE CASCADE,
            UNIQUE KEY unique_assignment_student (assignment_id, student_id),
            INDEX idx_assignment_id (assignment_id),
            INDEX idx_student_id (student_id),
            INDEX idx_status (status)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ Đã tạo bảng 'quiz_submissions'\n";
    }
    
    // Kiểm tra và tạo bảng quiz_submission_answers
    $checkTable = $db->query("SHOW TABLES LIKE 'quiz_submission_answers'");
    if ($checkTable->rowCount() > 0) {
        echo "✓ Bảng 'quiz_submission_answers' đã tồn tại\n";
    } else {
        echo "Tạo bảng quiz_submission_answers...\n";
        $db->exec("CREATE TABLE IF NOT EXISTS quiz_submission_answers (
            id INT PRIMARY KEY AUTO_INCREMENT,
            submission_id INT NOT NULL,
            question_id INT NOT NULL,
            answer_id INT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (submission_id) REFERENCES quiz_submissions(id) ON DELETE CASCADE,
            FOREIGN KEY (question_id) REFERENCES quiz_questions(id) ON DELETE CASCADE,
            FOREIGN KEY (answer_id) REFERENCES quiz_answers(id) ON DELETE SET NULL,
            UNIQUE KEY unique_submission_question (submission_id, question_id),
            INDEX idx_submission_id (submission_id),
            INDEX idx_question_id (question_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        echo "✓ Đã tạo bảng 'quiz_submission_answers'\n";
    }
    
    echo "\n✅ Migration hoàn tất thành công!\n";
    echo "Bây giờ bạn có thể tạo bài tập quiz.\n";
    
} catch (PDOException $e) {
    echo "\n❌ Lỗi khi chạy migration: " . $e->getMessage() . "\n";
    echo "SQL State: " . $e->getCode() . "\n";
    exit(1);
}
?>

