<?php
require_once __DIR__ . '/includes/session_check.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid file ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

$file_id = (int)$_GET['id'];

// Check if confirmation is given
if (isset($_POST['confirm']) && $_POST['confirm'] === 'yes') {
    $stmt_select = $conn->prepare("SELECT filename FROM csv_files WHERE id = ?");
    $stmt_select->bind_param("i", $file_id);
    $stmt_select->execute();
    $result = $stmt_select->get_result();
    $file = $result->fetch_assoc();
    $filename = $file ? $file['filename'] : 'the file';
    $stmt_select->close();

    $stmt_delete = $conn->prepare("DELETE FROM csv_files WHERE id = ?");
    $stmt_delete->bind_param("i", $file_id);

    if ($stmt_delete->execute()) {
        $_SESSION['message'] = "File '" . htmlspecialchars($filename) . "' has been deleted successfully.";
        $_SESSION['message_type'] = "success";
    } else {
        $_SESSION['message'] = "Error deleting file: " . $stmt_delete->error;
        $_SESSION['message_type'] = "danger";
    }

    $stmt_delete->close();
    $conn->close();

    header("Location: index.php");
    exit;
}

// Show confirmation page
$stmt = $conn->prepare("SELECT filename, row_count, size_kb, file_uuid, created_at FROM csv_files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "File not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

$file = $result->fetch_assoc();
$stmt->close();

include_once __DIR__ . '/templates/header.php';
?>

<style>
    .delete-warning {
        background: linear-gradient(135deg, #ff6b6b 0%, #ee5a6f 100%);
        color: white;
        padding: 2rem;
        border-radius: 0.5rem;
        text-align: center;
        margin-bottom: 2rem;
    }

    .delete-warning h2 {
        font-size: 1.75rem;
        margin-bottom: 1rem;
    }

    .file-info-delete {
        background-color: white;
        padding: 1.5rem;
        border-radius: 0.5rem;
        margin-bottom: 2rem;
        border-left: 4px solid #dc3545;
    }

    .file-info-delete p {
        margin-bottom: 0.5rem;
    }

    .confirmation-buttons {
        display: flex;
        gap: 1rem;
        justify-content: center;
        flex-wrap: wrap;
    }

    @media (max-width: 576px) {
        .delete-warning {
            padding: 1.5rem;
        }

        .delete-warning h2 {
            font-size: 1.25rem;
        }

        .confirmation-buttons {
            flex-direction: column;
        }

        .confirmation-buttons .btn {
            width: 100%;
        }
    }
</style>

<div class="row justify-content-center">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="delete-warning">
            <i class="bi bi-exclamation-triangle" style="font-size: 2.5rem; display: block; margin-bottom: 1rem;"></i>
            <h2>Delete File?</h2>
            <p>This action cannot be undone. The file and all its data will be permanently deleted.</p>
        </div>

        <div class="file-info-delete">
            <h5><i class="bi bi-file-earmark"></i> File Details</h5>
            <hr>
            <p><strong>Filename:</strong> <code><?php echo htmlspecialchars($file['filename']); ?></code></p>
            <p><strong>File UUID:</strong> <code><?php echo htmlspecialchars($file['file_uuid'] ?? 'N/A'); ?></code></p>
            <p><strong>Questions:</strong> <?php echo htmlspecialchars($file['row_count']); ?></p>
            <p><strong>File Size:</strong> <?php echo htmlspecialchars($file['size_kb']); ?> KB</p>
            <p><strong>Uploaded:</strong> <small><?php echo date("Y-m-d H:i:s", strtotime($file['created_at'])); ?></small></p>
        </div>

        <form method="post" id="deleteForm">
            <div class="confirmation-buttons">
                <a href="index.php" class="btn btn-secondary btn-lg"><i class="bi bi-x-circle"></i> Cancel</a>
                <button type="submit" name="confirm" value="yes" class="btn btn-danger btn-lg"><i class="bi bi-trash"></i> Yes, Delete Permanently</button>
            </div>
        </form>
    </div>
</div>

<?php
include_once __DIR__ . '/templates/footer.php';
$conn->close();
?>