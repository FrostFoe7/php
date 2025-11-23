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
                    // Generate file UUID in YYMMDDHHMI format
                    $file_uuid = generateFileUUID($conn);
                    
                    $stmt = $conn->prepare("INSERT INTO csv_files (filename, description, json_text, row_count, size_kb, file_uuid) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->bind_param("sssiis", $safe_filename, $description, $json_text, $row_count, $file_size_kb, $file_uuid);

                    if ($stmt->execute()) {
                        $_SESSION['message'] = "File '" . htmlspecialchars($safe_filename) . "' uploaded successfully. UUID: " . $file_uuid;
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

<style>
    .upload-zone {
        border: 2px dashed #0d6efd;
        border-radius: 0.5rem;
        padding: 2rem;
        text-align: center;
        background-color: #f0f6ff;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .upload-zone:hover {
        border-color: #0b5ed7;
        background-color: #e7f1ff;
    }

    .upload-zone.dragging {
        border-color: #198754;
        background-color: #f1fbf3;
    }

    .upload-zone i {
        font-size: 3rem;
        color: #0d6efd;
        margin-bottom: 1rem;
    }

    .upload-zone.dragging i {
        color: #198754;
    }

    @media (max-width: 576px) {
        .upload-zone {
            padding: 1.5rem;
        }

        .upload-zone i {
            font-size: 2rem;
        }
    }
</style>

<div class="row">
    <div class="col-12">
        <h2 class="mb-1"><i class="bi bi-cloud-upload"></i> Upload CSV File</h2>
        <p class="text-muted mb-4">Upload a CSV file to convert it to JSON and store it in the database.</p>
    </div>
</div>

<div class="row">
    <div class="col-12 col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body">
                <?php if ($upload_error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-circle"></i> <?php echo htmlspecialchars($upload_error); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <form action="upload.php" method="post" enctype="multipart/form-data" id="uploadForm">
                    <!-- Drag & Drop Zone -->
                    <div class="upload-zone" id="uploadZone">
                        <div>
                            <i class="bi bi-cloud-upload"></i>
                            <p class="mb-1"><strong>Drag & drop your CSV file here</strong></p>
                            <p class="text-muted mb-2">or click to select a file</p>
                            <small class="text-muted">Max file size: 5MB</small>
                        </div>
                        <input class="form-control" type="file" id="csv_file" name="csv_file" accept=".csv" required style="display: none;">
                    </div>

                    <!-- File preview -->
                    <div class="mt-3" id="filePreview" style="display: none;">
                        <div class="alert alert-info">
                            <i class="bi bi-check-circle"></i> 
                            <strong id="fileName"></strong> selected<br>
                            <small id="fileSize"></small>
                        </div>
                    </div>

                    <div class="mb-3 mt-4">
                        <label for="description" class="form-label">Description <small class="text-muted">(Optional)</small></label>
                        <textarea class="form-control" id="description" name="description" rows="3" placeholder="A brief description of the file content..."></textarea>
                        <small class="form-text text-muted">Character count: <span id="charCount">0</span>/500</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg" id="submitBtn">
                            <i class="bi bi-cloud-check"></i> Upload and Process
                        </button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Help Section -->
    <div class="col-12 col-md-4 mt-4 mt-md-0">
        <div class="card bg-light">
            <div class="card-header">
                <i class="bi bi-question-circle"></i> CSV Format Tips
            </div>
            <div class="card-body">
                <ul class="small mb-0">
                    <li>First row should contain headers</li>
                    <li>Columns: question, option_a, option_b, option_c, option_d, answer</li>
                    <li>Answer should be a letter (A, B, C, D)</li>
                    <li>Use UTF-8 encoding for Bengali text</li>
                    <li>Max file size: 5MB</li>
                    <li>Supported format: .csv</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
    // Drag and Drop functionality
    const uploadZone = document.getElementById('uploadZone');
    const fileInput = document.getElementById('csv_file');
    const filePreview = document.getElementById('filePreview');
    const charCountSpan = document.getElementById('charCount');
    const descriptionInput = document.getElementById('description');

    uploadZone.addEventListener('click', () => fileInput.click());

    uploadZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        uploadZone.classList.add('dragging');
    });

    uploadZone.addEventListener('dragleave', () => {
        uploadZone.classList.remove('dragging');
    });

    uploadZone.addEventListener('drop', (e) => {
        e.preventDefault();
        uploadZone.classList.remove('dragging');
        if (e.dataTransfer.files.length > 0) {
            fileInput.files = e.dataTransfer.files;
            updateFilePreview();
        }
    });

    fileInput.addEventListener('change', updateFilePreview);

    function updateFilePreview() {
        if (fileInput.files.length > 0) {
            const file = fileInput.files[0];
            document.getElementById('fileName').textContent = file.name;
            document.getElementById('fileSize').textContent = 'Size: ' + (file.size / 1024).toFixed(2) + ' KB';
            filePreview.style.display = 'block';
        } else {
            filePreview.style.display = 'none';
        }
    }

    // Character count for description
    descriptionInput.addEventListener('input', () => {
        charCountSpan.textContent = descriptionInput.value.length;
        if (descriptionInput.value.length > 500) {
            descriptionInput.value = descriptionInput.value.substring(0, 500);
            charCountSpan.textContent = '500';
        }
    });

    // Form validation
    document.getElementById('uploadForm').addEventListener('submit', (e) => {
        if (!fileInput.files.length) {
            e.preventDefault();
            alert('Please select a file to upload.');
        }
    });
</script>

<?php
include_once __DIR__ . '/templates/footer.php';
$conn->close();
?>