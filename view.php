<?php
require_once __DIR__ . '/includes/session_check.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid file ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

$file_id = (int)$_GET['id'];

$stmt = $conn->prepare("SELECT filename, description, json_text, row_count, size_kb, created_at FROM csv_files WHERE id = ?");
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
    .detail-card {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        border-radius: 0.5rem;
        padding: 1.5rem;
        margin-bottom: 1.5rem;
    }

    .detail-card-item {
        margin-bottom: 1rem;
    }

    .detail-card-item:last-child {
        margin-bottom: 0;
    }

    .detail-label {
        font-size: 0.85rem;
        opacity: 0.9;
    }

    .detail-value {
        font-size: 1.5rem;
        font-weight: 700;
    }

    .json-view-controls {
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        margin-bottom: 1rem;
    }

    .json-preview {
        max-height: 600px;
        border-radius: 0.375rem;
    }

    @media (max-width: 576px) {
        .detail-card {
            padding: 1rem;
        }

        .detail-value {
            font-size: 1.25rem;
        }

        .json-preview {
            max-height: 400px;
            font-size: 0.75rem;
        }

        .json-view-controls {
            flex-direction: column;
        }

        .json-view-controls .btn {
            width: 100%;
        }
    }
</style>

<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
            <div>
                <h2 class="mb-0"><i class="bi bi-file-earmark-text"></i> <?php echo htmlspecialchars(substr($file['filename'], 0, 30)); ?></h2>
                <p class="text-muted mb-0"><small>File ID: <?php echo $file_id; ?></small></p>
            </div>
            <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="detail-card">
            <div class="detail-card-item">
                <div class="detail-label">Total Rows</div>
                <div class="detail-value"><?php echo htmlspecialchars($file['row_count']); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="detail-card">
            <div class="detail-card-item">
                <div class="detail-label">File Size</div>
                <div class="detail-value"><?php echo htmlspecialchars($file['size_kb']); ?> <small>KB</small></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="detail-card">
            <div class="detail-card-item">
                <div class="detail-label">Uploaded</div>
                <div class="detail-value"><?php echo date("M d", strtotime($file['created_at'])); ?></div>
            </div>
        </div>
    </div>
    <div class="col-12 col-sm-6 col-lg-3">
        <div class="detail-card">
            <div class="detail-card-item">
                <div class="detail-label">Time</div>
                <div class="detail-value"><?php echo date("H:i", strtotime($file['created_at'])); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <i class="bi bi-info-circle"></i> File Information
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-12 col-md-6">
                        <p class="mb-3">
                            <strong><i class="bi bi-file-earmark"></i> Filename:</strong><br>
                            <span class="text-muted"><?php echo htmlspecialchars($file['filename']); ?></span>
                        </p>
                    </div>
                    <div class="col-12 col-md-6">
                        <p class="mb-0">
                            <strong><i class="bi bi-calendar-event"></i> Created At:</strong><br>
                            <span class="text-muted"><?php echo date("Y-m-d H:i:s", strtotime($file['created_at'])); ?></span>
                        </p>
                    </div>
                </div>
                <?php if (!empty($file['description'])): ?>
                    <hr>
                    <p class="mb-0">
                        <strong><i class="bi bi-chat-dots"></i> Description:</strong><br>
                        <span class="text-muted"><?php echo nl2br(htmlspecialchars(substr($file['description'], 0, 200))); ?></span>
                    </p>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="row mb-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-code-square"></i> JSON Data Preview (<?php echo htmlspecialchars($file['row_count']); ?> records)</span>
                <div class="json-view-controls">
                    <button class="btn btn-sm btn-info" onclick="copyToClipboard()"><i class="bi bi-clipboard"></i> Copy</button>
                    <a href="edit.php?id=<?php echo $file_id; ?>" class="btn btn-sm btn-warning"><i class="bi bi-pencil-square"></i> Edit</a>
                    <a href="delete.php?id=<?php echo $file_id; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');"><i class="bi bi-trash"></i> Delete</a>
                </div>
            </div>
            <div class="card-body p-0">
                <pre class="json-preview" id="jsonPreview"><code><?php echo htmlspecialchars($file['json_text']); ?></code></pre>
            </div>
        </div>
    </div>
</div>

<script>
    function copyToClipboard() {
        const jsonText = document.getElementById('jsonPreview').innerText;
        navigator.clipboard.writeText(jsonText).then(() => {
            alert('JSON copied to clipboard!');
        }).catch(() => {
            alert('Failed to copy to clipboard');
        });
    }

    // Mobile-friendly JSON display
    if (window.innerWidth < 768) {
        const jsonPreview = document.getElementById('jsonPreview');
        jsonPreview.style.fontSize = '0.75rem';
    }
</script>

<?php
include_once __DIR__ . '/templates/footer.php';
$conn->close();
?>
