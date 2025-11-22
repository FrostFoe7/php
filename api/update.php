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

$uid = $_POST['uid'] ?? null;
$field = $_POST['field'] ?? null;
$value = $_POST['value'] ?? null;

if (empty($uid) || empty($field)) {
    api_response(false, [], 'Missing required parameters: uid, field.', 400);
}

$all_files_data = [];
$stmt = $conn->prepare("SELECT id, json_text FROM csv_files ORDER BY id");
$stmt->execute();
$all_files_result = $stmt->get_result();

$global_uid_map = [];
$current_global_uid = 1;

while ($file = $all_files_result->fetch_assoc()) {
    $file_id = $file['id'];
    $json_data_for_file = json_decode($file['json_text'], true);

    if (json_last_error() === JSON_ERROR_NONE) {
        foreach ($json_data_for_file as $index_in_file => $row) {
            $global_uid_map[$current_global_uid] = [
                'file_id' => $file_id,
                'index_in_file_json' => $index_in_file
            ];
            $current_global_uid++;
        }
    }
    $all_files_data[$file_id] = $json_data_for_file; // Store original parsed data per file_id
}
$stmt->close();

if (!isset($global_uid_map[$uid])) {
    api_response(false, [], 'Record with specified uid not found.', 404);
}

$target_info = $global_uid_map[$uid];
$target_file_id = $target_info['file_id'];
$target_index_in_file_json = $target_info['index_in_file_json'];

// Retrieve the specific file's JSON data from the $all_files_data (already fetched)
// This avoids another DB query
$target_file_json_data = $all_files_data[$target_file_id];

if (!isset($target_file_json_data[$target_index_in_file_json])) {
    api_response(false, [], 'Internal error: Target record not found in file JSON.', 500);
}

// Update the specific field
$target_file_json_data[$target_index_in_file_json][$field] = $value;

// Encode the modified data back to JSON
$updated_json_text_for_file = json_encode($target_file_json_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

// Update the database for the specific file_id
$stmt = $conn->prepare("UPDATE csv_files SET json_text = ? WHERE id = ?");
$stmt->bind_param("si", $updated_json_text_for_file, $target_file_id);
if ($stmt->execute()) {
    api_response(true, [], 'Record updated successfully.');
} else {
    api_response(false, [], 'Failed to update record in the database.', 500);
}

$stmt->close();
$conn->close();
