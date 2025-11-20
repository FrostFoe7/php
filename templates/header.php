/* FILE: public_html/templates/header.php */
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CSV Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<?php
if (!isset($is_login_page) || $is_login_page === false) {
    if (file_exists(__DIR__ . '/includes/config.php')) {
        require_once __DIR__ . '/includes/config.php';
    }
    if (isset($_SESSION['user_id'])) {
        include_once __DIR__ . '/nav.php';
    }
}
?>

<div class="container mt-4">
    <?php
    if (isset($_SESSION['message'])) {
        $message_type = isset($_SESSION['message_type']) ? $_SESSION['message_type'] : 'info';
        echo '<div class="alert alert-' . htmlspecialchars($message_type) . ' alert-dismissible fade show" role="alert">';
        echo htmlspecialchars($_SESSION['message']);
        echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
        echo '</div>';
        unset($_SESSION['message']);
        unset($_SESSION['message_type']);
    }
    ?>
