<?php
require_once __DIR__ . '/includes/config.php';

$error_message = '';

if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            $_SESSION['user_id'] = 'admin';
            $_SESSION['username'] = $username;

            header("Location: index.php");
            exit;
        } else {
            $error_message = "Invalid username or password.";
        }
    }
}

$is_login_page = true;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course MNR World - Admin Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }

        .login-container {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 1rem;
        }

        .login-card {
            width: 100%;
            max-width: 420px;
            box-shadow: 0 0.5rem 1.5rem rgba(0, 0, 0, 0.3);
            border: none;
        }

        .login-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem 1rem;
            text-align: center;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .login-header i {
            font-size: 2.5rem;
            display: block;
            margin-bottom: 0.5rem;
        }

        .login-header h2 {
            margin: 0;
            font-size: 1.5rem;
            font-weight: 700;
        }

        .login-header p {
            margin: 0.5rem 0 0 0;
            font-size: 0.9rem;
            opacity: 0.9;
        }

        .login-body {
            padding: 2rem 1.5rem;
        }

        .form-control {
            border-radius: 0.375rem;
            padding: 0.75rem;
            border: 1px solid #dee2e6;
        }

        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
        }

        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .btn-login {
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 0.375rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.25rem 0.75rem rgba(0, 0, 0, 0.15);
        }

        .alert {
            border-radius: 0.375rem;
            border: none;
            margin-bottom: 1rem;
        }

        @media (max-width: 480px) {
            .login-header {
                padding: 1.5rem 1rem;
            }

            .login-header h2 {
                font-size: 1.25rem;
            }

            .login-body {
                padding: 1.5rem 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card card">
            <div class="login-header">
                <i class="bi bi-shield-lock"></i>
                <h2>Admin Login</h2>
                <p>Course MNR World</p>
            </div>
            <div class="login-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label"><i class="bi bi-person"></i> Username</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username" 
                            placeholder="Enter your username"
                            required
                            autofocus
                        >
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label"><i class="bi bi-key"></i> Password</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Enter your password"
                            required
                        >
                    </div>

                    <button type="submit" class="btn btn-login btn-primary w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Login
                    </button>
                </form>
            </div>
        </div>
    </div>

    <footer class="text-center text-white pb-4 mt-auto">
        <p class="mb-0"><small>&copy; <?php echo date("Y"); ?> Course MNR World. All rights reserved.</small></p>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>