<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
  <div class="container">
    <a class="navbar-brand" href="index.php"><?php echo APP_NAME; ?></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav ms-auto">
        <?php if (isLoggedIn()): ?>
            <li class="nav-item">
            <a class="nav-link" href="index.php">Files</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" href="file-upload.php">Upload</a>
            </li>
            <li class="nav-item">
            <a class="nav-link" href="logout.php">Logout (<?php echo h($_SESSION['user_name'] ?? 'Admin'); ?>)</a>
            </li>
        <?php else: ?>
            <li class="nav-item">
            <a class="nav-link" href="login.php">Login</a>
            </li>
        <?php endif; ?>
      </ul>
    </div>
  </div>
</nav>
<div class="container">
