<?php
require_once __DIR__ . '/includes/session_check.php';

$result = $conn->query("SELECT id, filename, description, row_count, size_kb, created_at FROM csv_files ORDER BY created_at DESC");

include_once __DIR__ . '/templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-speedometer2"></i> Dashboard</h2>
    <a href="upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload New CSV</a>
</div>

<div class="card">
    <div class="card-header">
        Uploaded Files
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Filename</th>
                        <th>Description</th>
                        <th>Rows</th>
                        <th>Size (KB)</th>
                        <th>Uploaded At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($row['id']); ?></td>
                                <td><?php echo htmlspecialchars($row['filename']); ?></td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><?php echo htmlspecialchars($row['row_count']); ?></td>
                                <td><?php echo htmlspecialchars($row['size_kb']); ?></td>
                                <td><?php echo date("Y-m-d H:i:s", strtotime($row['created_at'])); ?></td>
                                <td>
                                    <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i></a>
                                    <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-square"></i></a>
                                    <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure you want to delete this file?');"><i class="bi bi-trash"></i></a>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center">No files uploaded yet.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
include_once __DIR__ . '/templates/footer.php';
$conn->close();
?>
