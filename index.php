<?php
require_once 'includes/bootstrap.php';
requireLogin();

$stmt = $pdo->query("SELECT * FROM files ORDER BY uploaded_at DESC");
$files = $stmt->fetchAll();
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<h2>Uploaded Question Banks</h2>
<a href="file-upload.php" class="btn btn-success mb-3">Upload New CSV</a>

<table class="table table-striped">
    <thead>
        <tr>
            <th>Filename</th>
            <th>Questions</th>
            <th>Uploaded At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($files as $file): ?>
        <tr>
            <td><?php echo h($file['original_filename']); ?></td>
            <td><?php echo h($file['total_questions']); ?></td>
            <td><?php echo h($file['uploaded_at']); ?></td>
            <td>
                <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-info">View</a>
                <a href="file-edit.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="file-delete.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php include 'templates/footer.php'; ?>
