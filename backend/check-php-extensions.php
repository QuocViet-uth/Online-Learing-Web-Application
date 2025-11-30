<?php
/**
 * Check PHP Extensions for Database Connection
 */

echo "=== PHP Extensions Check ===\n\n";

$required_extensions = [
    'pdo' => 'PDO',
    'pdo_sqlite' => 'PDO SQLite',
    'mbstring' => 'Multibyte String',
    'curl' => 'cURL',
];

$missing = [];
$installed = [];

foreach ($required_extensions as $ext => $name) {
    if (extension_loaded($ext)) {
        echo "✓ {$name} ({$ext}) - INSTALLED\n";
        $installed[] = $ext;
    } else {
        echo "✗ {$name} ({$ext}) - NOT INSTALLED\n";
        $missing[] = $ext;
    }
}

echo "\n";

if (empty($missing)) {
    echo "✓ All required extensions are installed!\n";
} else {
    echo "✗ Missing extensions: " . implode(', ', $missing) . "\n\n";
    echo "To fix:\n";
    echo "1. Find your php.ini file (run: php --ini)\n";
    echo "2. Open php.ini in a text editor\n";
    echo "3. Find and uncomment (remove ;) these lines:\n";
    echo "   extension=pdo\n";
    echo "   extension=pdo_sqlite\n";
    echo "   extension=mbstring\n";
    echo "   extension=curl\n";
    echo "4. If lines don't exist, add them\n";
    echo "5. Restart PHP server or web server\n";
}

echo "\n=== PHP Info ===\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP ini file: " . php_ini_loaded_file() . "\n";
echo "Extension dir: " . ini_get('extension_dir') . "\n";



