<?php
require_once __DIR__ . '/includes/session_check.php';

$result = $conn->query("SELECT id, filename, description, row_count, size_kb, file_uuid, created_at FROM csv_files ORDER BY created_at DESC");

include_once __DIR__ . '/templates/header.php';
?>

<style>
    .stats-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
        box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    }

    .stats-card h3 {
        font-size: 2rem;
        font-weight: 700;
        margin: 0;
    }

    .stats-card p {
        font-size: 0.9rem;
        margin: 0;
        opacity: 0.9;
    }

    .action-buttons {
        display: flex;
        gap: 0.25rem;
        flex-wrap: wrap;
    }

    @media (max-width: 576px) {
        .action-buttons {
            flex-direction: column;
        }

        .action-buttons .btn {
            width: 100%;
            text-align: left;
        }

        .table-responsive {
            font-size: 0.85rem;
        }

        .table th, .table td {
            padding: 0.5rem 0.25rem;
        }
    }
</style>

<div class="mb-4">
    <div class="row">
        <div class="col-12 col-md-6">
            <h2 class="mb-0"><i class="bi bi-speedometer2"></i> Dashboard</h2>
        </div>
        <div class="col-12 col-md-6 text-end">
            <a href="upload.php" class="btn btn-primary"><i class="bi bi-cloud-upload"></i> Upload New CSV</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="stats-card">
            <h3><?php echo $result && $result->num_rows > 0 ? $result->num_rows : 0; ?></h3>
            <p>Total Files</p>
        </div>
    </div>
</div>

<?php $result->data_seek(0); ?>

<div class="card">
    <div class="card-header">
        <i class="bi bi-file-earmark-csv"></i> Uploaded Files
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="min-width: 60px;">ID</th>
                        <th style="min-width: 120px;">Filename</th>
                        <th style="min-width: 100px;">Description</th>
                        <th style="min-width: 60px;">Rows</th>
                        <th style="min-width: 80px;">Size (KB)</th>
                        <th style="min-width: 130px;">Uploaded At</th>
                        <th style="min-width: 140px;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($result && $result->num_rows > 0): ?>
                        <?php while($row = $result->fetch_assoc()): ?>
                            <tr>
                                <td><span class="badge bg-primary"><?php echo htmlspecialchars($row['id']); ?></span></td>
                                <td>
                                    <strong><?php echo htmlspecialchars($row['filename']); ?></strong><br>
                                    <small class="text-muted"><?php echo htmlspecialchars(strlen($row['description']) > 50 ? substr($row['description'], 0, 50) . '...' : $row['description']); ?></small>
                                </td>
                                <td><?php echo htmlspecialchars($row['description']); ?></td>
                                <td><span class="badge bg-info"><?php echo htmlspecialchars($row['row_count']); ?></span></td>
                                <td><?php echo htmlspecialchars($row['size_kb']); ?></td>
                                <td><small><?php echo date("M d, Y H:i", strtotime($row['created_at'])); ?></small></td>
                                <td>
                                    <div class="action-buttons">
                                        <a href="view.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-info" title="View"><i class="bi bi-eye"></i> View</a>
                                        <a href="edit.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-warning" title="Edit"><i class="bi bi-pencil-square"></i> Edit</a>
                                        <a href="delete.php?id=<?php echo $row['id']; ?>" class="btn btn-sm btn-danger" title="Delete" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i> Delete</a>
                                    </div>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <p class="text-muted mb-2"><i class="bi bi-inbox" style="font-size: 2rem;"></i></p>
                                <p class="text-muted">No files uploaded yet.</p>
                                <a href="upload.php" class="btn btn-sm btn-primary">Upload Your First File</a>
                            </td>
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
