<?php
/**
 * Migration Script: Thêm cột online_link vào bảng courses
 * File: run_add_online_link_migration.php
 * Cách chạy: php run_add_online_link_migration.php
 */

require_once __DIR__ . '/../config/database.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "Đang thêm cột online_link vào bảng courses...\n";
    
    // Kiểm tra xem cột đã tồn tại chưa
    $checkQuery = "SELECT COUNT(*) as count 
                   FROM INFORMATION_SCHEMA.COLUMNS 
                   WHERE TABLE_SCHEMA = DATABASE() 
                   AND TABLE_NAME = 'courses' 
                   AND COLUMN_NAME = 'online_link'";
    
    $stmt = $db->prepare($checkQuery);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($result['count'] > 0) {
        echo "Cột online_link đã tồn tại trong bảng courses.\n";
        exit(0);
    }
    
    // Thêm cột online_link
    $alterQuery = "ALTER TABLE courses 
                   ADD COLUMN online_link VARCHAR(500) NULL 
                   COMMENT 'Link học online (Zoom, Google Meet, etc.)' 
                   AFTER thumbnail";
    
    $db->exec($alterQuery);
    
    echo "Đã thêm cột online_link thành công!\n";
    echo "Migration hoàn tất.\n";
    
} catch (PDOException $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "Lỗi: " . $e->getMessage() . "\n";
    exit(1);
}
?>

