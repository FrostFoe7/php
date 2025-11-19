/* FILE: public_html/includes/config.php */
<?php
// Start the session at the very beginning.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// -- DATABASE SETTINGS -- //
// Replace with your cPanel MySQL database details.
define('DB_HOST', 's2bd-stable.whiteservers.net');
define('DB_USER', 'zxtfmwrs_zxtfmwrs');
define('DB_PASS', 'ws;0V;5YG2p0Az');
define('DB_NAME', 'zxtfmwrs_mnr_course');

// -- API KEY -- //
// A secret key for the API endpoint. Replace with a long, random string.
define('API_KEY', 'frostfoe1337');

// -- ADMIN CREDENTIALS -- //
// The username for the admin login.
define('ADMIN_USERNAME', 'admin');

// The password for the admin login.
// IMPORTANT: For production, you should use a hashed password.
// You can generate a hash using password_hash('your_password', PASSWORD_DEFAULT);
// Then, store the hash here and use password_verify() in the login script.
// For this project's requirement of no-tooling, we will use plain text and hash it on first login
// or use a known hash. For simplicity here, we use a plain password but check for a hash.
define('ADMIN_PASSWORD', 'password123');


// -- DATABASE CONNECTION -- //
// Create a new mysqli connection.
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Check the connection.
if ($conn->connect_error) {
    // If connection fails, stop the script and display an error.
    die("Connection failed: " . $conn->connect_error);
}

// Set the character set to utf8mb4 for full Unicode support.
$conn->set_charset("utf8mb4");

// Set error reporting for development.
// In a production environment, you should log errors instead of displaying them.
error_reporting(E_ALL);
ini_set('display_errors', 1);
