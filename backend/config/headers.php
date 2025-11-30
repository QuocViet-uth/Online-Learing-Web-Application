<?php
/**
 * File: config/headers.php
 * Mục đích: Cấu hình headers chung cho tất cả API
 */

// Load environment variables from .env file FIRST
require_once __DIR__ . '/env_loader.php';

// Include timezone config trước tiên để set timezone cho toàn bộ ứng dụng
require_once __DIR__ . '/timezone.php';

// Error reporting - Bật trong development để debug
// Trong production, nên tắt display_errors và chỉ log
$is_development = (getenv('NODE_ENV') !== 'production' && getenv('APP_ENV') !== 'production');
if ($is_development) {
    ini_set('display_errors', '0'); // Tắt display để tránh HTML trong JSON response
    ini_set('display_startup_errors', '0');
} else {
    ini_set('display_errors', '0');
}
// Suppress deprecation warnings in JSON responses (log them instead)
// E_STRICT was removed in PHP 8.4, so we don't need to handle it
$error_reporting = E_ALL & ~E_DEPRECATED;
// Note: E_STRICT was removed in PHP 8.4, no need to exclude it
error_reporting($error_reporting);
ini_set('log_errors', '1');

// Set UTF-8 encoding (if mbstring is available)
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
    mb_http_output('UTF-8');
}

// CORS Headers - Allow all origins including Laragon virtual domains
$allowed_origins = [
    'http://localhost:3000',
    'http://localhost:8000',
    'http://learningweb.test',
    'http://127.0.0.1:3000',
    'http://127.0.0.1:8000',
    'https://learningweb.vercel.app', // Production frontend
    'https://learningweb-git-main.vercel.app', // Vercel preview
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
} else {
    // Fallback to wildcard for other origins (less secure but more flexible)
    header("Access-Control-Allow-Origin: *");
}

header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Connection header - giữ connection mở để tối ưu performance
// Lưu ý: PHP built-in server vẫn có thể đóng connection, nhưng sẽ tối ưu khi dùng Apache/Nginx
header("Connection: keep-alive");
header("Keep-Alive: timeout=5, max=100");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if (!headers_sent()) {
        http_response_code(200);
    }
    exit();
}
