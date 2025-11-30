<?php
/**
 * Script để sửa encoding cho dữ liệu trong database
 */

require_once __DIR__ . '/../config/database.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    die("Cannot connect to database\n");
}

echo "Fixing encoding for courses table...\n";

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
    
    foreach ($courses as $course) {
        $stmt = $db->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([
            $course['title'],
            $course['description'],
            $course['id']
        ]);
        echo "Updated course ID {$course['id']}: {$course['title']}\n";
    }
    
    $db->commit();
    echo "\n✅ All courses updated successfully!\n";
    
    // Verify
    echo "\nVerifying data...\n";
    $stmt = $db->query("SELECT id, title, description FROM courses ORDER BY id");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "ID {$row['id']}: {$row['title']}\n";
    }
    
} catch (Exception $e) {
    $db->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}

?>

