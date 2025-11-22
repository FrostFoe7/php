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

$stmt = $conn->prepare("SELECT id, json_text FROM csv_files");
$stmt->execute();
$result = $stmt->get_result();

$all_data = [];
while ($file = $result->fetch_assoc()) {
    $json_data = json_decode($file['json_text'], true);
    if (json_last_error() === JSON_ERROR_NONE) {
        foreach ($json_data as &$row) {
            $row['file_id'] = $file['id'];
            $all_data[] = $row;
        }
    }
}
$stmt->close();
$conn->close();

$serialized_data = [];
$serial_number = 1;
foreach ($all_data as $row) {
    $row_with_sr_no = ['sr_no' => $serial_number] + $row;
    $serialized_data[] = $row_with_sr_no;
    $serial_number++;
}

api_response(true, ['data' => $serialized_data], '');
