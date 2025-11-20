<?php
require_once __DIR__ . '/includes/config.php';

$_SESSION = array();

if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

session_destroy();

session_start();
$_SESSION['message'] = "You have been successfully logged out.";
$_SESSION['message_type'] = "success";

header("Location: login.php");
exit;
