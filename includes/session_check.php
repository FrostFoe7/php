<?php
require_once __DIR__ . '/config.php';

if (!isset($_SESSION['user_id'])) {
    $_SESSION['message'] = "You must be logged in to view this page.";
    $_SESSION['message_type'] = "warning";

    header("Location: login.php");
    exit;
}
