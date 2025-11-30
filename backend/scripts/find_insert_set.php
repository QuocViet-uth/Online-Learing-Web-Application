<?php
/**
 * Script to find all INSERT INTO ... SET statements in models
 */

$modelsDir = __DIR__ . '/../models';
$files = glob($modelsDir . '/*.php');

foreach ($files as $file) {
    $content = file_get_contents($file);
    
    // Find INSERT INTO ... SET pattern
    if (preg_match('/INSERT\s+INTO.*?SET\s+/is', $content)) {
        echo basename($file) . " - Contains INSERT SET\n";
        
        // Show the query
        preg_match('/\$query\s*=\s*"INSERT\s+INTO.*?";/is', $content, $matches);
        if ($matches) {
            echo "  Query: " . substr($matches[0], 0, 100) . "...\n\n";
        }
    }
}
?>
