/* FILE: public_html/templates/header.php */
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course MNR World - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <style>
        /* Mobile Navigation */
        .navbar {
            background-color: white;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
        }

        .navbar-brand {
            font-weight: 700;
            color: #0d6efd !important;
            font-size: 1.25rem;
        }

        .nav-link {
            margin: 0.5rem 0;
            transition: color 0.2s;
        }

        .nav-link:hover {
            color: #0d6efd !important;
        }

        .nav-link.active {
            color: #0d6efd !important;
            font-weight: 600;
        }

        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1rem;
            }

            .nav-link {
                padding: 0.5rem 0 !important;
            }

            .container-fluid {
                padding-left: 0.75rem;
                padding-right: 0.75rem;
            }
        }
    </style>
</head>
<body>

<?php
if (!isset($is_login_page) || $is_login_page === false) {
    if (file_exists(__DIR__ . '/includes/config.php')) {
        require_once __DIR__ . '/includes/config.php';
    }
    if (isset($_SESSION['user_id'])) {
        // Mobile-responsive navbar
        ?>
        <nav class="navbar navbar-expand-md navbar-light bg-white mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="/"><i class="bi bi-book"></i> Course Admin</a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="/">📊 Dashboard</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/upload.php">📤 Upload</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        <?php
    }
}
?>

<div class="container-fluid p-3 p-md-4">
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
