<?php
require_once __DIR__ . '/../includes/config.php';
header('Content-Type: application/json');

function api_response($success, $data = [], $message = '', $statusCode = 200) {
    http_response_code($statusCode);
    if ($success && isset($data['data'])) {
        // Return only the data array for successful responses
        echo json_encode($data['data'], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    } else {
        // Return error response with success and message
        $response = ['success' => $success];
        if ($message) {
            $response['message'] = $message;
        }
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    exit;
}

$api_key = $_GET['key'] ?? '';
if (empty($api_key) || !defined('API_KEY') || $api_key !== API_KEY) {
    api_response(false, [], 'Unauthorized: Invalid or missing API key.', 401);
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    api_response(false, [], 'Bad Request: Invalid or missing file ID.', 400);
}
$file_id = (int)$_GET['id'];

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

$json_data = json_decode($file['json_text'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    api_response(false, [], 'Internal Server Error: Failed to parse stored JSON data.', 500);
}

api_response(true, ['data' => $json_data], '');
