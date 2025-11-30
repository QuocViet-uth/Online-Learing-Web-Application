<?php
/**
 * Auto-fix INSERT INTO ... SET to INSERT INTO ... VALUES for SQLite
 */

$modelsDir = __DIR__ . '/../models';
$filesToFix = [
    'Assignment.php',
    'Chat.php',
    'Coupon.php',
    'Enrollment.php',
    'Payment.php',
    'PaymentQRCode.php',
    'QuizAnswer.php',
    'QuizQuestion.php',
    'Submission.php'
];

foreach ($filesToFix as $fileName) {
    $file = $modelsDir . '/' . $fileName;
    if (!file_exists($file)) {
        echo "Skipping $fileName - not found\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $original = $content;
    
    // Pattern to match INSERT INTO ... SET ...
    $pattern = '/(\$query\s*=\s*"INSERT\s+INTO\s+"\s*\.\s*\$this->table_name\s*\.\s*"\s*)\n(\s+)SET\s+(.*?)";/is';
    
    $content = preg_replace_callback($pattern, function($matches) {
        $prefix = $matches[1];
        $indent = $matches[2];
        $setClause = $matches[3];
        
        // Parse SET clause to get columns and values
        $pairs = preg_split('/,\s*/', trim($setClause));
        $columns = [];
        $values = [];
        
        foreach ($pairs as $pair) {
            if (preg_match('/(\w+)\s*=\s*(:?\w+)/', $pair, $m)) {
                $columns[] = $m[1];
                $values[] = $m[2];
            }
        }
        
        $columnsList = implode(', ', $columns);
        $valuesList = implode(', ', $values);
        
        return $prefix . "\n" . $indent . "($columnsList)\n" . $indent . "VALUES ($valuesList)\";";
    }, $content);
    
    if ($content !== $original) {
        file_put_contents($file, $content);
        echo "✓ Fixed $fileName\n";
    } else {
        echo "✗ No changes in $fileName\n";
    }
}

echo "\nDone!\n";
?>
