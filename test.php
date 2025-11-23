<?php
// Simple test to verify PHP is working
echo "PHP is working correctly.\n";
echo "Session status: " . (session_status() == PHP_SESSION_NONE ? "Not started" : "Started") . "\n";

if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

$_SESSION['test'] = 'working';
echo "Session test: " . $_SESSION['test'] . "\n";

// Check database connection
require_once __DIR__ . '/includes/config.php';

if ($conn->connect_error) {
    echo "Database connection failed: " . $conn->connect_error . "\n";
} else {
    echo "Database connection successful.\n";
}

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    echo "User is logged in (ID: " . $_SESSION['user_id'] . ")\n";
} else {
    echo "User is NOT logged in. This is expected if not authenticated.\n";
}
?>
