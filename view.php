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

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-file-earmark-text"></i> View File: <?php echo htmlspecialchars($file['filename']); ?></h2>
    <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back to Dashboard</a>
</div>

<div class="card mb-4">
    <div class="card-header">
        File Details
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>ID:</strong> <?php echo $file_id; ?></p>
                <p><strong>Filename:</strong> <?php echo htmlspecialchars($file['filename']); ?></p>
                <p><strong>Description:</strong> <?php echo nl2br(htmlspecialchars($file['description'])); ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Row Count:</strong> <?php echo htmlspecialchars($file['row_count']); ?></p>
                <p><strong>Size:</strong> <?php echo htmlspecialchars($file['size_kb']); ?> KB</p>
                <p><strong>Uploaded At:</strong> <?php echo date("Y-m-d H:i:s", strtotime($file['created_at'])); ?></p>
            </div>
        </div>
        <a href="edit.php?id=<?php echo $file_id; ?>" class="btn btn-warning"><i class="bi bi-pencil-square"></i> Edit this file</a>
    </div>
</div>

<div class="card">
    <div class="card-header">
        JSON Data Preview
    </div>
    <div class="card-body">
        <pre class="json-preview"><code><?php echo htmlspecialchars($file['json_text']); ?></code></pre>
    </div>
</div>

<?php
include_once __DIR__ . '/templates/footer.php';
$conn->close();
?>
