<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

function api_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    $response = ['success' => $success];
    if ($message) {
        $response['message'] = $message;
    }
    if ($data) {
        $response['data'] = $data;
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    api_response(false, [], 'Invalid request method. Only POST is accepted.', 405);
}

$api_key = $_POST['key'] ?? '';
if (empty($api_key) || !defined('API_KEY') || $api_key !== API_KEY) {
    api_response(false, [], 'Unauthorized: Invalid or missing API key.', 401);
}

$file_id = $_POST['file_id'] ?? null;
$sr_no = $_POST['sr_no'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? null;

if (empty($file_id) || empty($sr_no) || empty($field)) {
    api_response(false, [], 'Missing required parameters: file_id, sr_no, field.', 400);
}

// Fetch the specific JSON file from the database
$stmt = $conn->prepare("SELECT json_text FROM csv_files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();
$file = $result->fetch_assoc();
$stmt->close();

if (!$file) {
    api_response(false, [], 'File not found.', 404);
}

$json_data = json_decode($file['json_text'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    api_response(false, [], 'Error decoding JSON data.', 500);
}

// Find the record to update. Since sr_no is 1-based index, we convert to 0-based.
$index_to_update = -1;
$current_sr_no = 1;

// We need to find the overall index of the sr_no in the context of ALL files
// This is not efficient, but it's the only way with the current structure.
$all_data = [];
$stmt = $conn->prepare("SELECT id, json_text FROM csv_files ORDER BY id");
$stmt->execute();
$all_files_result = $stmt->get_result();
while ($current_file = $all_files_result->fetch_assoc()) {
    $current_json_data = json_decode($current_file['json_text'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        foreach ($current_json_data as $row) {
            if ($current_file['id'] == $file_id) {
                 $all_data[] = $row;
            }
        }
    }
}
$stmt->close();


$found = false;
foreach ($all_data as $index => &$row) {
    if (($index + 1) == $sr_no) {
        $row[$field] = $value;
        $found = true;
        break;
    }
}

if (!$found) {
    api_response(false, [], 'Record with specified sr_no not found in the given file_id.', 404);
}

// Encode the modified data back to JSON
$updated_json_text = json_encode($all_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Update the database
$stmt = $conn->prepare("UPDATE csv_files SET json_text = ? WHERE id = ?");
$stmt->bind_param("si", $updated_json_text, $file_id);
if ($stmt->execute()) {
    api_response(true, [], 'Record updated successfully.');
} else {
    api_response(false, [], 'Failed to update record in the database.', 500);
}

$stmt->close();
$conn->close();
