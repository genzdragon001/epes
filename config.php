<?php
/**
 * EPES System Configuration
 * Loads environment variables and provides configuration helpers
 */

// Load environment variables from .env file
function loadEnv($path = null) {
    $envFile = $path ?? __DIR__ . '/.env';
    
    if (!file_exists($envFile)) {
        return false;
    }
    
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) {
            continue;
        }
        
        // Parse key=value
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set environment variable
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
    
    return true;
}

// Load .env file
loadEnv();

// Configuration constants
define('EPES_VERSION', '2.0.0');
define('EPES_BUILD_DATE', '2025-04-01');

// Database configuration
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'epes_db');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');
define('DB_CHARSET', 'utf8mb4');

// SMTP Configuration
define('SMTP_HOST', getenv('SMTP_HOST') ?: 'smtp.gmail.com');
define('SMTP_PORT', getenv('SMTP_PORT') ?: 587);
define('SMTP_USER', getenv('SMTP_USER') ?: '');
define('SMTP_PASS', getenv('SMTP_PASS') ?: '');
define('SMTP_FROM', getenv('SMTP_FROM') ?: 'noreply@epes.edu.ph');
define('SMTP_FROM_NAME', getenv('SMTP_FROM_NAME') ?: 'EPES System');

// System Configuration
define('SYSTEM_URL', getenv('SYSTEM_URL') ?: 'http://localhost/epes');
define('ENCRYPTION_KEY', getenv('ENCRYPTION_KEY') ?: 'default-key-change-me');
define('CSRF_SECRET', getenv('CSRF_SECRET') ?: 'default-csrf-secret');

// Session Configuration
define('SESSION_TIMEOUT', (int)(getenv('SESSION_TIMEOUT') ?: 900));

// File Upload Configuration
define('UPLOAD_DIR', __DIR__ . '/uploads/');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png', 'gif', 'xls', 'xlsx', 'ppt', 'pptx']);

// Security Settings
define('PASSWORD_MIN_LENGTH', 8);
define('MAX_LOGIN_ATTEMPTS', 5);
define('ACCOUNT_LOCKOUT_DURATION', 3600); // 1 hour

// Performance Settings
define('CACHE_ENABLED', true);
define('CACHE_DIR', __DIR__ . '/cache/');

// Logging
define('LOG_ENABLED', true);
define('LOG_DIR', __DIR__ . '/logs/');
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR, CRITICAL

/**
 * Get configuration value
 * @param string $key Configuration key
 * @param mixed $default Default value if not set
 * @return mixed
 */
function config($key, $default = null) {
    $config = [
        'db.host' => DB_HOST,
        'db.name' => DB_NAME,
        'db.user' => DB_PASS,
        'db.pass' => DB_PASS,
        'smtp.host' => SMTP_HOST,
        'smtp.port' => SMTP_PORT,
        'smtp.user' => SMTP_USER,
        'smtp.pass' => SMTP_PASS,
        'smtp.from' => SMTP_FROM,
        'system.url' => SYSTEM_URL,
        'upload.dir' => UPLOAD_DIR,
        'version' => EPES_VERSION,
    ];
    
    return $config[$key] ?? $default;
}

/**
 * Get environment variable with default
 * @param string $key Environment variable name
 * @param mixed $default Default value
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);
    return $value !== false ? $value : $default;
}
