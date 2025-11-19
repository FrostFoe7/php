/* FILE: public_html/public/logout.php */
<?php
// The config file starts the session.
require_once __DIR__ . '/includes/config.php';

// Unset all of the session variables.
$_SESSION = array();

// If it's desired to kill the session, also delete the session cookie.
// Note: This will destroy the session, and not just the session data!
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finally, destroy the session.
session_destroy();

// Redirect to the login page with a success message.
// We have to start a new session to pass a message.
session_start();
$_SESSION['message'] = "You have been successfully logged out.";
$_SESSION['message_type'] = "success";

header("Location: login.php");
exit;
