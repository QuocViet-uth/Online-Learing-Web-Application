<?php
/**
 * Script cập nhật dữ liệu courses với encoding UTF-8 đúng
 */

// Set UTF-8 encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("❌ Không thể kết nối database\n");
}

echo "🔧 Đang cập nhật dữ liệu courses với UTF-8...\n\n";

// Dữ liệu đúng (UTF-8)
$courses = [
    [
        'id' => 1,
        'title' => 'Lập trình PHP cơ bản',
        'description' => 'Khóa học PHP từ cơ bản đến nâng cao'
    ],
    [
        'id' => 2,
        'title' => 'JavaScript nâng cao',
        'description' => 'Khóa học JavaScript và ES6+'
    ],
    [
        'id' => 3,
        'title' => 'Cơ sở dữ liệu MySQL',
        'description' => 'Học MySQL từ đầu'
    ]
];

try {
    $db->beginTransaction();
    
    $stmt = $db->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ?");
    
    foreach ($courses as $course) {
        $stmt->execute([
            $course['title'],
            $course['description'],
            $course['id']
        ]);
        echo "✅ Updated course ID {$course['id']}: {$course['title']}\n";
    }
    
    $db->commit();
    echo "\n✅ Hoàn tất! Đã cập nhật " . count($courses) . " khóa học.\n";
    
    // Verify
    echo "\n📋 Kiểm tra dữ liệu sau khi cập nhật:\n";
    $stmt = $db->query("SELECT id, title, description FROM courses ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  ID {$row['id']}: {$row['title']}\n";
    }
    
} catch (Exception $e) {
    $db->rollBack();
    echo "❌ Lỗi: " . $e->getMessage() . "\n";
}

?>

