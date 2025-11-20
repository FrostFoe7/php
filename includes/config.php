<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

define('DB_HOST', 's2bd-stable.whiteservers.net');
define('DB_USER', 'zxtfmwrs_zxtfmwrs');
define('DB_PASS', 'ws;0V;5YG2p0Az');
define('DB_NAME', 'zxtfmwrs_mnr_course');

define('API_KEY', 'frostfoe1337');

define('ADMIN_USERNAME', 'admin');

define('ADMIN_PASSWORD', 'password123');

$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

error_reporting(E_ALL);
ini_set('display_errors', 1);
