<?php
// config/init.php

// Error reporting - set to 0 in production
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define base constants
define('BASE_PATH', dirname(__DIR__));
define('CONFIG_PATH', __DIR__);
define('UPLOAD_PATH', BASE_PATH . DIRECTORY_SEPARATOR . 'uploads');
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif']);
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Load configuration files in the correct order
require_once CONFIG_PATH . DIRECTORY_SEPARATOR . 'database.php';  // First database
require_once CONFIG_PATH . DIRECTORY_SEPARATOR . 'session.php';   // Then session
require_once CONFIG_PATH . DIRECTORY_SEPARATOR . 'helpers.php';   // Then helpers

// Initialize session
SessionManager::start();

// Set default timezone
date_default_timezone_set('UTC');

// Custom error handler
function customErrorHandler($errno, $errstr, $errfile, $errline) {
    $error_message = date('Y-m-d H:i:s') . " Error: [$errno] $errstr in $errfile on line $errline\n";
    error_log($error_message, 3, BASE_PATH . DIRECTORY_SEPARATOR . 'logs' . DIRECTORY_SEPARATOR . 'error.log');
    
    if (ini_get('display_errors')) {
        printf("<pre>Error: %s\nFile: %s\nLine: %d</pre>", $errstr, $errfile, $errline);
    }
    
    return true;
}

// Set custom error handler
set_error_handler('customErrorHandler');