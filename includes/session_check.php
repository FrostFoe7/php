/* FILE: public_html/includes/session_check.php */
<?php
// The config file starts the session.
require_once __DIR__ . '/config.php';

// Check if the user is logged in by looking for the session variable.
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, set a flash message.
    $_SESSION['message'] = "You must be logged in to view this page.";
    $_SESSION['message_type'] = "warning";

    // Redirect them to the login page.
    // Note: Adjust the path if your file structure is different.
    header("Location: login.php");
    exit; // Stop script execution after redirect.
}
