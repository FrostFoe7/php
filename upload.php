<?php
require_once __DIR__ . '/includes/session_check.php';

$upload_error = '';
$upload_success = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["csv_file"])) {
    $file = $_FILES["csv_file"];
    $description = trim($_POST['description']);

    if ($file["error"] !== UPLOAD_ERR_OK) {
        $upload_error = "File upload error. Code: " . $file["error"];
    } elseif ($file["size"] > 5 * 1024 * 1024) {
        $upload_error = "File is too large. Maximum size is 5MB.";
    } else {
            $safe_filename = preg_replace("/[^a-zA-Z0-9-._ ]/", "", basename($file["name"]));
            $file_path = $file["tmp_name"];
            $file_size_kb = round($file["size"] / 1024, 2);

            $csv_data = [];
            $headers = [];
            $is_first_row = true;

            setlocale(LC_ALL, 'en_US.UTF-8');

            if (($handle = fopen($file_path, "r")) !== FALSE) {
                while (($row = fgetcsv($handle, 0, ",")) !== FALSE) {
                    if (empty(array_filter($row))) {
                        continue;
                    }

                    if ($is_first_row) {
                        $headers = array_map(function($header) {
                            if (substr($header, 0, 3) === "\xEF\xBB\xBF") {
                                $header = substr($header, 3);
                            }
                            return trim($header);
                        }, $row);
                        $is_first_row = false;
                    } else {
                        $row = array_pad($row, count($headers), "");
                        $csv_data[] = array_combine($headers, $row);
                    }
                }
                fclose($handle);
            } else {
                $upload_error = "Could not open the uploaded file.";
            }

            if (empty($upload_error)) {
                $row_count = count($csv_data);
                $json_text = json_encode($csv_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    $upload_error = "Error encoding data to JSON: " . json_last_error_msg();
                } else {
                    $stmt = $conn->prepare("INSERT INTO csv_files (filename, description, json_text, row_count, size_kb) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssid", $safe_filename, $description, $json_text, $row_count, $file_size_kb);

                    if ($stmt->execute()) {
                        $_SESSION['message'] = "File '" . htmlspecialchars($safe_filename) . "' uploaded and processed successfully.";
                        $_SESSION['message_type'] = "success";
                        header("Location: index.php");
                        exit;
                    } else {
                        $upload_error = "Database error: " . $stmt->error;
                    }
                    $stmt->close();
                }
            }
        }
}

include_once __DIR__ . '/templates/header.php';
?>

<h2><i class="bi bi-cloud-upload"></i> Upload CSV File</h2>
<p>Upload a CSV file to convert it to JSON and store it in the database.</p>

<div class="card">
    <div class="card-body">
        <?php if ($upload_error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($upload_error); ?></div>
        <?php endif; ?>
        <form action="upload.php" method="post" enctype="multipart/form-data">
            <div class="mb-3">
                <label for="csv_file" class="form-label">CSV File</label>
                <input class="form-control" type="file" id="csv_file" name="csv_file" required>
                <div class="form-text">Max file size: 5MB. The first row should contain headers.</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Description (Optional)</label>
                <textarea class="form-control" id="description" name="description" rows="3" placeholder="A brief description of the file content."></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Upload and Process</button>
        </form>
    </div>
</div>

<?php
include_once __DIR__ . '/templates/footer.php';
$conn->close();
?>