<?php
/**
 * Machform CRM - Database Configuration
 * Pure PHP CRM for managing Machform forms and entries
 */

// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'jmaheryc_forms');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Application Configuration
define('APP_NAME', 'Machform CRM');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/machform_crm');

// Path Configuration
define('BASE_PATH', dirname(__DIR__));
define('INCLUDES_PATH', BASE_PATH . '/includes');
define('MODULES_PATH', BASE_PATH . '/modules');
define('TEMPLATES_PATH', BASE_PATH . '/templates');
define('UPLOADS_PATH', BASE_PATH . '/uploads');
define('ASSETS_PATH', BASE_PATH . '/assets');

// Session Configuration
define('SESSION_NAME', 'MACHFORM_CRM_SESSION');
define('SESSION_LIFETIME', 3600); // 1 hour

// Security Configuration
define('HASH_COST', 12);
define('CSRF_TOKEN_NAME', 'csrf_token');

// Pagination Configuration
define('DEFAULT_PAGE_SIZE', 15);
define('MAX_PAGE_SIZE', 100);

// Date & Time Configuration
define('DEFAULT_TIMEZONE', 'UTC');
define('DATE_FORMAT', 'Y-m-d');
define('DATETIME_FORMAT', 'Y-m-d H:i:s');
define('DISPLAY_DATE_FORMAT', 'M d, Y');
define('DISPLAY_DATETIME_FORMAT', 'M d, Y h:i A');

// Enable error reporting in development (disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', BASE_PATH . '/logs/error.log');

// Set timezone
date_default_timezone_set(DEFAULT_TIMEZONE);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.name', SESSION_NAME);
    ini_set('session.gc_maxlifetime', SESSION_LIFETIME);
    session_start();
}
