<?php
/**
 * Router file for Render deployment
 * Routes requests to the appropriate API endpoint
 */

// Load environment variables from .env file
require_once __DIR__ . '/config/env_loader.php';

// Get the request URI
$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);

// Remove query string for routing
$path = strtok($request_path, '?');

// Remove leading slash
$path = ltrim($path, '/');

// Remove 'backend/' prefix if present (for Laragon virtual host routing)
if (strpos($path, 'backend/') === 0) {
    $path = substr($path, 8); // Remove 'backend/'
}

// If path is empty, return API info
if (empty($path)) {
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'message' => 'Online Learning API',
        'version' => '1.0.0'
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

// If path starts with 'api/uploads/', serve static files
if (strpos($path, 'api/uploads/') === 0) {
    // Remove 'api/' prefix
    $file_path = __DIR__ . '/' . substr($path, 4);
    
    // Check if file exists and is a file (not directory)
    if (file_exists($file_path) && is_file($file_path)) {
        // Get file extension to set proper content type
        $ext = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
        $mime_types = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'pdf' => 'application/pdf',
            'mp4' => 'video/mp4',
            'mp3' => 'audio/mpeg',
        ];
        
        $content_type = isset($mime_types[$ext]) ? $mime_types[$ext] : 'application/octet-stream';
        header('Content-Type: ' . $content_type);
        header('Content-Length: ' . filesize($file_path));
        readfile($file_path);
        exit;
    }
}

// If path starts with 'api/', route to API files
if (strpos($path, 'api/') === 0) {
    // Remove 'api/' prefix
    $api_path = substr($path, 4);
    
    // Construct the full file path
    $file_path = __DIR__ . '/api/' . $api_path;
    
    // Try with .php extension if file doesn't exist
    if (!file_exists($file_path) || !is_file($file_path)) {
        $file_path .= '.php';
    }
    
    // Check if file exists
    if (file_exists($file_path) && is_file($file_path)) {
        // Include the API file (relative paths in the file will work because __DIR__ in the included file will be the file's directory)
        require_once $file_path;
        exit;
    }
}

// Try direct file access (for files in api/ directory)
$direct_path = __DIR__ . '/' . $path;
if (file_exists($direct_path) && is_file($direct_path)) {
    require_once $direct_path;
    exit;
}

// If no matching route, return 404
http_response_code(404);
header('Content-Type: application/json');
echo json_encode([
    'success' => false,
    'message' => 'API endpoint not found',
    'path' => $path
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
exit;

