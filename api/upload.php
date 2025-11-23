<?php
/**
 * POST /api/upload.php
 * 
 * Upload CSV file from Next.js web app
 * 
 * Form Data:
 * - key: API key (required)
 * - file: CSV file (required)
 * - description: File description (optional)
 * 
 * Response:
 * {
 *   "success": true,
 *   "message": "File uploaded successfully",
 *   "data": {
 *     "file_id": 1,
 *     "filename": "questions.csv",
 *     "description": "Exam questions",
 *     "row_count": 150,
 *     "size_kb": 125.50
 *   }
 * }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    APIResponse::error('Invalid request method. Only POST is accepted.', 405);
}

// Validate API key
$apiKey = $_POST['key'] ?? '';
APIValidator::validateApiKey($apiKey);

try {
    // Check if file was uploaded
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        $error = $_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE;
        APIResponse::error('File upload error. Code: ' . $error, 400);
    }

    $file = $_FILES['file'];
    $description = $_POST['description'] ?? '';
    $description = trim($description);

    // Validate file
    $filename = basename($file['name']);
    if (!preg_match('/\.csv$/i', $filename)) {
        APIResponse::error('File must be a CSV file.', 400);
    }

    // Validate file size (5MB max)
    $maxSize = 5 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        APIResponse::error('File size exceeds 5MB limit.', 400);
    }

    // Safe filename
    $safeFilename = preg_replace("/[^a-zA-Z0-9-._ ]/", "", $filename);
    $filePath = $file['tmp_name'];
    $fileSizeKb = round($file['size'] / 1024, 2);

    // Parse CSV file
    $csvData = [];
    $headers = [];
    $isFirstRow = true;

    setlocale(LC_ALL, 'en_US.UTF-8');

    if (($handle = fopen($filePath, 'r')) !== FALSE) {
        while (($row = fgetcsv($handle, 0, ',')) !== FALSE) {
            // Skip empty rows
            if (empty(array_filter($row))) {
                continue;
            }

            if ($isFirstRow) {
                // Parse headers, removing BOM if present
                $headers = array_map(function ($header) {
                    if (substr($header, 0, 3) === "\xEF\xBB\xBF") {
                        $header = substr($header, 3);
                    }
                    return trim($header);
                }, $row);
                $isFirstRow = false;
            } else {
                // Parse data row
                $row = array_pad($row, count($headers), '');
                $csvData[] = array_combine($headers, $row);
            }
        }
        fclose($handle);
    } else {
        APIResponse::error('Could not open the uploaded file.', 500);
    }

    if (empty($csvData)) {
        APIResponse::error('CSV file contains no data rows.', 400);
    }

    // Encode to JSON
    $rowCount = count($csvData);
    $jsonText = json_encode($csvData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    if (json_last_error() !== JSON_ERROR_NONE) {
        APIResponse::error('Error encoding data to JSON: ' . json_last_error_msg(), 500);
    }

    // Insert into database
    $stmt = $GLOBALS['conn']->prepare(
        "INSERT INTO csv_files (filename, description, json_text, row_count, size_kb) VALUES (?, ?, ?, ?, ?)"
    );

    if (!$stmt) {
        APIResponse::error('Database prepare failed: ' . $GLOBALS['conn']->error, 500);
    }

    $stmt->bind_param('sssid', $safeFilename, $description, $jsonText, $rowCount, $fileSizeKb);

    if ($stmt->execute()) {
        $fileId = $stmt->insert_id;
        $stmt->close();

        APIResponse::success(
            [
                'file_id' => $fileId,
                'filename' => $safeFilename,
                'description' => $description,
                'row_count' => $rowCount,
                'size_kb' => $fileSizeKb,
                'headers' => $headers,
            ],
            'File uploaded successfully.',
            201
        );
    } else {
        $stmt->close();
        APIResponse::error('Database error: ' . $GLOBALS['conn']->error, 500);
    }

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
