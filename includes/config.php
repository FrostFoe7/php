<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'mnr_question_bank');
define('DB_USER', 'root'); // Change for cPanel
define('DB_PASS', '');     // Change for cPanel
define('DB_CHARSET', 'utf8mb4');

// App Configuration
define('APP_NAME', 'Universal Question Bank Manager');
define('BASE_URL', '/php-api'); // Adjust based on where it's hosted

// Error Reporting (Disable in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
