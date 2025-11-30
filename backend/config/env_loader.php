<?php
/**
 * File: config/env_loader.php
 * Mục đích: Load environment variables từ file .env
 * PHP không tự động load .env files, cần parse thủ công
 */

/**
 * Load environment variables from .env file
 * @param string $env_file_path - Path to .env file (relative to this file or absolute)
 */
function loadEnvFile($env_file_path = null) {
    // Default to .env in backend directory
    if ($env_file_path === null) {
        $env_file_path = dirname(__DIR__) . '/.env';
    }
    
    // If path is relative, make it absolute from backend directory
    if (!is_file($env_file_path) && $env_file_path[0] !== '/') {
        $env_file_path = dirname(__DIR__) . '/' . $env_file_path;
    }
    
    // Check if .env file exists
    if (!file_exists($env_file_path)) {
        // Try .env.example as fallback (but don't overwrite existing env vars)
        $env_example = dirname($env_file_path) . '/.env.example';
        if (file_exists($env_example)) {
            $env_file_path = $env_example;
        } else {
            return false; // No .env file found
        }
    }
    
    // Read .env file
    $lines = file($env_file_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    if ($lines === false) {
        return false;
    }
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Skip empty lines
        if (trim($line) === '') {
            continue;
        }
        
        // Parse KEY=VALUE format
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            
            // Trim whitespace
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            if ((substr($value, 0, 1) === '"' && substr($value, -1) === '"') ||
                (substr($value, 0, 1) === "'" && substr($value, -1) === "'")) {
                $value = substr($value, 1, -1);
            }
            
            // Only set if not already set (environment variables take precedence)
            if (!getenv($key)) {
                putenv("$key=$value");
                $_ENV[$key] = $value;
                $_SERVER[$key] = $value;
            }
        }
    }
    
    return true;
}

// Auto-load .env file when this file is included
loadEnvFile();

