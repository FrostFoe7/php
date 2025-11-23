<?php
// Error handling first
require_once __DIR__ . '/error-handler.php';

// Session configuration
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 'localhost');
define('DB_USER', 'zxtfmwrs_zxtfmwrs');
define('DB_PASS', 'ws;0V;5YG2p0Az');
define('DB_NAME', 'zxtfmwrs_mnr_course');

define('API_KEY', 'frostfoe1337');

define('ADMIN_USERNAME', 'admin');

define('ADMIN_PASSWORD', 'password123');

// Database connection with error handling
try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        throw new Exception("Database Connection Error: " . $conn->connect_error);
    }
    
    $conn->set_charset("utf8mb4");
    
    // Enable error reporting
    $conn->report_mode = MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT;
    
} catch (Exception $e) {
    error_log("DB Error: " . $e->getMessage());
    die("Database connection failed. Please check your credentials.");
}

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

/**
 * Generate a unique file UUID in YYMMDDHHMI format
 * With collision handling by appending sequence number
 * 
 * @param mysqli $conn Database connection
 * @return string 10-digit UUID (YYMMDDHHMI)
 */
function generateFileUUID($conn) {
    $timestamp = date('ymdHi'); // YYMMDDHHMI format
    
    // Check if this timestamp already exists
    $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM csv_files WHERE file_uuid LIKE ?");
    $pattern = $timestamp . '%';
    $stmt->bind_param("s", $pattern);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $count = $row['cnt'];
    $stmt->close();
    
    // If no collisions, use timestamp as-is
    if ($count === 0) {
        return $timestamp;
    }
    
    // If collisions exist, try appending random 2-digit suffix
    for ($i = 0; $i < 100; $i++) {
        $suffix = str_pad(rand(0, 99), 2, '0', STR_PAD_LEFT);
        $uuid = $timestamp . $suffix;
        
        $stmt = $conn->prepare("SELECT COUNT(*) as cnt FROM csv_files WHERE file_uuid = ?");
        $stmt->bind_param("s", $uuid);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        
        if ($row['cnt'] === 0) {
            $stmt->close();
            return $uuid;
        }
        $stmt->close();
    }
    
    // Fallback: use timestamp + random
    return $timestamp . rand(10, 99);
}
