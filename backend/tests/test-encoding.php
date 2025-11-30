<?php
/**
 * Test encoding và charset của database connection
 */

// Set UTF-8 encoding
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
}

header("Content-Type: text/html; charset=UTF-8");

include_once '../config/database.php';

$database = new Database();
$db = $database->getConnection();

if ($db) {
    echo "<h2>Database Connection Test</h2>";
    
    // Test charset settings
    $stmt = $db->query("SHOW VARIABLES LIKE 'character_set%'");
    echo "<h3>Character Set Variables:</h3>";
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>Variable</th><th>Value</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr><td>" . $row['Variable_name'] . "</td><td>" . $row['Value'] . "</td></tr>";
    }
    echo "</table>";
    
    // Test data retrieval
    echo "<h3>Test Data from Database:</h3>";
    $stmt = $db->query("SELECT id, course_name, title, description FROM courses LIMIT 3");
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>ID</th><th>Course Name</th><th>Title</th><th>Description</th></tr>";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . $row['id'] . "</td>";
        echo "<td>" . htmlspecialchars($row['course_name'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . htmlspecialchars($row['title'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "<td>" . htmlspecialchars($row['description'], ENT_QUOTES, 'UTF-8') . "</td>";
        echo "</tr>";
    }
    echo "</table>";
    
    // Test JSON encoding
    echo "<h3>JSON Encoding Test:</h3>";
    $stmt = $db->query("SELECT title FROM courses WHERE id = 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "<p>Original: " . $row['title'] . "</p>";
    echo "<p>JSON: " . json_encode($row, JSON_UNESCAPED_UNICODE) . "</p>";
    
} else {
    echo "Failed to connect to database";
}
?>

