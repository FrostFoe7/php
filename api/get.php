/* FILE: public_html/public/api/get.php */
<?php
// The config file contains the database connection and the API key.
require_once __DIR__ . '/../includes/config.php';

// Set the content type to JSON for the response.
header('Content-Type: application/json');

// --- API RESPONSE HELPER ---
function api_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    $response = ['success' => $success];
    if ($message) {
        $response['message'] = $message;
    }
    // Only include data if success is true
    if ($success) {
        // Merge the data into the top-level response
        $response = array_merge($response, $data);
    }
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// --- VALIDATE API KEY ---
$api_key = $_GET['key'] ?? '';
if (empty($api_key) || !defined('API_KEY') || $api_key !== API_KEY) {
    api_response(false, [], 'Unauthorized: Invalid or missing API key.', 401);
}

// --- VALIDATE FILE ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    api_response(false, [], 'Bad Request: Invalid or missing file ID.', 400);
}
$file_id = (int)$_GET['id'];

// --- FETCH DATA FROM DATABASE ---
$stmt = $conn->prepare("SELECT id, filename, row_count, json_text FROM csv_files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    api_response(false, [], 'Not Found: File with the given ID does not exist.', 404);
}

$file = $result->fetch_assoc();
$stmt->close();
$conn->close();

// --- PREPARE AND SEND RESPONSE ---
// Decode the JSON text to an array to be included in the 'data' field
$json_data = json_decode($file['json_text'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    api_response(false, [], 'Internal Server Error: Failed to parse stored JSON data.', 500);
}

$response_data = [
    'id' => $file['id'],
    'filename' => $file['filename'],
    'row_count' => $file['row_count'],
    'data' => $json_data
];

api_response(true, $response_data, 'Data retrieved successfully.');
