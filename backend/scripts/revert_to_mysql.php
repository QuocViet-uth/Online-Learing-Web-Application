<?php
/**
 * Revert all models back to MySQL INSERT SET syntax
 */

$modelsDir = __DIR__ . '/../models';
$filesToRevert = [
    'User.php' => [
        'from' => '(username, full_name, date_of_birth, gender, school, password, email, phone, avatar, role)
                VALUES (:username, :full_name, :date_of_birth, :gender, :school, :password, :email, :phone, :avatar, :role)',
        'to' => 'SET username = :username, 
                    full_name = :full_name,
                    date_of_birth = :date_of_birth,
                    gender = :gender,
                    school = :school,
                    password = :password, 
                    email = :email, 
                    phone = :phone, 
                    avatar = :avatar,
                    role = :role'
    ],
    'Course.php' => [
        'from' => '(course_name, title, description, price, teacher_id, start_date, end_date, status, thumbnail, online_link)
                VALUES (:course_name, :title, :description, :price, :teacher_id, :start_date, :end_date, :status, :thumbnail, :online_link)',
        'to' => 'SET course_name = :course_name,
                    title = :title,
                    description = :description,
                    price = :price,
                    teacher_id = :teacher_id,
                    start_date = :start_date,
                    end_date = :end_date,
                    status = :status,
                    thumbnail = :thumbnail,
                    online_link = :online_link'
    ],
    'Lesson.php' => [
        'from' => '(course_id, title, content, video_url, attachment_file, order_number, duration)
                VALUES (:course_id, :title, :content, :video_url, :attachment_file, :order_number, :duration)',
        'to' => 'SET course_id = :course_id,
                    title = :title,
                    content = :content,
                    video_url = :video_url,
                    attachment_file = :attachment_file,
                    order_number = :order_number,
                    duration = :duration'
    ]
];

foreach ($filesToRevert as $fileName => $replacement) {
    $file = $modelsDir . '/' . $fileName;
    if (!file_exists($file)) {
        echo "Skipping $fileName - not found\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    $content = str_replace($replacement['from'], $replacement['to'], $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Reverted $fileName to MySQL syntax\n";
    } else {
        echo "✗ No changes in $fileName\n";
    }
}

// Auto-revert other files
$files = glob($modelsDir . '/*.php');
foreach ($files as $file) {
    $content = file_get_contents($file);
    $original = $content;
    
    // Pattern to convert VALUES back to SET
    $pattern = '/(\$query\s*=\s*"INSERT\s+INTO\s+"\s*\.\s*\$this->table_name\s*\.\s*"\s*\n\s+)\(([^)]+)\)\s+VALUES\s+\(([^)]+)\)/i';
    
    $content = preg_replace_callback($pattern, function($matches) {
        $prefix = $matches[1];
        $columns = array_map('trim', explode(',', $matches[2]));
        $values = array_map('trim', explode(',', $matches[3]));
        
        $setPairs = [];
        for ($i = 0; $i < count($columns); $i++) {
            if (isset($values[$i])) {
                $setPairs[] = $columns[$i] . ' = ' . $values[$i];
            }
        }
        
        return $prefix . 'SET ' . implode(",\n                    ", $setPairs);
    }, $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Auto-reverted " . basename($file) . "\n";
    }
}

echo "\nAll models reverted to MySQL syntax!\n";
?>
