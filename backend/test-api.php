<?php
/**
 * Test API endpoint to verify backend is working
 * Access: https://your-backend.onrender.com/test-api.php
 */

header('Content-Type: application/json; charset=UTF-8');
header('Access-Control-Allow-Origin: *');

$response = [
    'success' => true,
    'message' => 'Backend API is working!',
    'timestamp' => date('Y-m-d H:i:s'),
    'server_info' => [
        'php_version' => phpversion(),
        'request_uri' => $_SERVER['REQUEST_URI'] ?? 'N/A',
        'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'N/A',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'N/A',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'N/A',
        '__DIR__' => __DIR__,
    ],
    'file_checks' => [
        'index.php_exists' => file_exists(__DIR__ . '/index.php'),
        'api_directory_exists' => is_dir(__DIR__ . '/api'),
        'get_courses_exists' => file_exists(__DIR__ . '/api/get-courses.php'),
        'config_directory_exists' => is_dir(__DIR__ . '/config'),
    ],
    'environment' => [
        'port' => getenv('PORT') ?: 'NOT SET',
        'db_host' => getenv('DB_HOST') ? 'SET' : 'NOT SET',
        'db_name' => getenv('DB_NAME') ? 'SET' : 'NOT SET',
        'app_url' => getenv('APP_URL') ?: 'NOT SET',
    ],
];

http_response_code(200);
echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
exit;

