<?php
require_once __DIR__ . '/../config/database.php';

echo "Testing database connection...\n";

$db = new Database();
$conn = $db->getConnection();

if ($conn) {
    echo "✓ Connected to MySQL successfully!\n";
    echo "Database: " . $db->getDbName() . "\n";
    
    // Test query
    try {
        $stmt = $conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Found " . $result['count'] . " users in database\n";
    } catch (Exception $e) {
        echo "✗ Query error: " . $e->getMessage() . "\n";
    }
} else {
    echo "✗ Failed to connect to database\n";
    echo "Check error log for details\n";
}
?>
