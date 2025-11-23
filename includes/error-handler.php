<?php
/**
 * Global Error Handler for cPanel hosting
 * Place this in includes/error-handler.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 0);  // Don't expose errors to users
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/php-errors.log');

// Create logs directory if it doesn't exist
if (!is_dir(__DIR__ . '/../logs')) {
    mkdir(__DIR__ . '/../logs', 0755, true);
}

// Custom error handler
function handleError($errno, $errstr, $errfile, $errline) {
    $error_types = [
        E_ERROR => 'ERROR',
        E_WARNING => 'WARNING',
        E_PARSE => 'PARSE ERROR',
        E_NOTICE => 'NOTICE',
        E_CORE_ERROR => 'CORE ERROR',
        E_CORE_WARNING => 'CORE WARNING',
        E_COMPILE_ERROR => 'COMPILE ERROR',
        E_COMPILE_WARNING => 'COMPILE WARNING',
        E_USER_ERROR => 'USER ERROR',
        E_USER_WARNING => 'USER WARNING',
        E_USER_NOTICE => 'USER NOTICE',
        E_STRICT => 'STRICT',
        E_RECOVERABLE_ERROR => 'RECOVERABLE ERROR',
        E_DEPRECATED => 'DEPRECATED',
        E_USER_DEPRECATED => 'USER DEPRECATED',
    ];
    
    $type = $error_types[$errno] ?? 'UNKNOWN ERROR';
    $message = "[$type] $errstr in $errfile on line $errline";
    
    error_log($message);
    
    // Don't show errors to users, log internally
    if (ini_get('display_errors')) {
        echo "An error occurred. Please contact support if problems persist.";
    }
    
    return true;
}

// Custom exception handler
function handleException($exception) {
    $message = "Exception: " . $exception->getMessage() . 
               " in " . $exception->getFile() . 
               " on line " . $exception->getLine();
    error_log($message);
    
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/html; charset=utf-8');
    }
    
    echo "An unexpected error occurred. Please try again later.";
}

// Fatal error handler
function handleShutdown() {
    $error = error_get_last();
    if ($error !== null) {
        handleError($error['type'], $error['message'], $error['file'], $error['line']);
    }
}

set_error_handler('handleError');
set_exception_handler('handleException');
register_shutdown_function('handleShutdown');

// Security headers
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('X-XSS-Protection: 1; mode=block');
    header('Referrer-Policy: strict-origin-when-cross-origin');
}

// Session configuration for cPanel
if (session_status() == PHP_SESSION_NONE) {
    // Set secure session path
    $session_path = sys_get_temp_dir();
    if (is_writable($session_path)) {
        session_save_path($session_path);
    }
    
    // Session cookie settings
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'domain' => '',
        'secure' => isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on',
        'httponly' => true,
        'samesite' => 'Strict'
    ]);
    
    session_start();
}
?>
