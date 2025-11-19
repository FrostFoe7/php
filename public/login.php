/* FILE: public_html/public/login.php */
<?php
// The config file is needed for database connection and admin credentials.
require_once __DIR__ . '/../includes/config.php';

$error_message = '';

// If the user is already logged in, redirect them to the dashboard.
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $password = $_POST['password'];

    // Basic validation
    if (empty($username) || empty($password)) {
        $error_message = "Username and password are required.";
    } else {
        // Check if the provided credentials match the ones in the config file.
        // This is a simple check. For a real-world application, you MUST hash passwords.
        if ($username === ADMIN_USERNAME && $password === ADMIN_PASSWORD) {
            // On successful login, store a user identifier in the session.
            $_SESSION['user_id'] = 'admin'; // A simple identifier
            $_SESSION['username'] = $username;

            // Redirect to the main dashboard page.
            header("Location: index.php");
            exit;
        } else {
            $error_message = "Invalid username or password.";
        }
    }
}

// This flag tells the header not to include the nav bar.
$is_login_page = true;
include_once __DIR__ . '/../templates/header.php';
?>

<div class="row justify-content-center">
    <div class="col-md-6 col-lg-4">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title text-center">Admin Login</h3>
            </div>
            <div class="card-body">
                <?php if ($error_message): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error_message); ?></div>
                <?php endif; ?>
                <form action="login.php" method="post">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Login</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
// We don't need the full footer, just the closing tags.
?>
</div> <!-- /container -->
<!-- Bootstrap 5.3 JS Bundle with Popper -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
