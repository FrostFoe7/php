/* FILE: public_html/public/delete.php */
<?php
require_once __DIR__ . '/../includes/session_check.php';

// Check for ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid file ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

$file_id = (int)$_GET['id'];

// Fetch filename for the success message before deleting
$stmt_select = $conn->prepare("SELECT filename FROM csv_files WHERE id = ?");
$stmt_select->bind_param("i", $file_id);
$stmt_select->execute();
$result = $stmt_select->get_result();
$file = $result->fetch_assoc();
$filename = $file ? $file['filename'] : 'the file';
$stmt_select->close();

if (!$file) {
    $_SESSION['message'] = "File not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

// Prepare and execute the deletion
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

// Redirect back to the dashboard
header("Location: index.php");
exit;
