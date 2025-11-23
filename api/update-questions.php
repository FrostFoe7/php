<?php
/**
 * AJAX API Endpoint for Updating Questions
 * Bypasses 403 POST issues with direct API call
 * 
 * POST /api/update-questions.php
 * Parameters:
 *   file_uuid: 10-digit UUID
 *   questions: JSON array of questions
 */

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/config.php';

// Verify session
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get file_uuid
$file_uuid = trim($_POST['file_uuid'] ?? $_GET['file_uuid'] ?? '');

if ($file_uuid === '' || !is_numeric($file_uuid) || strlen($file_uuid) != 10) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid file UUID']);
    exit;
}

// Get questions data
$questions_json = $_POST['questions'] ?? $_GET['questions'] ?? '';

if (empty($questions_json)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No questions provided']);
    exit;
}

// Decode questions
$questions = json_decode($questions_json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid JSON: ' . json_last_error_msg()]);
    exit;
}

// Look up file_id from UUID
$stmt = $GLOBALS['conn']->prepare("SELECT id FROM csv_files WHERE file_uuid = ?");
$stmt->bind_param("s", $file_uuid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'File not found']);
    exit;
}

$file_data = $result->fetch_assoc();
$file_id = $file_data['id'];
$stmt->close();

// Process and validate questions
$new_data = [];
foreach ($questions as $index => $question) {
    if (isset($question['delete']) && $question['delete'] == '1') {
        continue; // Skip deleted questions
    }

    $new_data[] = [
        'questions' => $question['question'] ?? '',
        'option1' => $question['option1'] ?? '',
        'option2' => $question['option2'] ?? '',
        'option3' => $question['option3'] ?? '',
        'option4' => $question['option4'] ?? '',
        'option5' => $question['option5'] ?? '',
        'answer' => $question['answer'] ?? '',
        'explanation' => $question['explanation'] ?? '',
        'type' => $question['type'] ?? '',
        'section' => $question['section'] ?? ''
    ];
}

$row_count = count($new_data);
$json_text = json_encode($new_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'JSON encoding error']);
    exit;
}

// Update database
$stmt = $GLOBALS['conn']->prepare("UPDATE csv_files SET json_text = ?, row_count = ? WHERE id = ?");
$stmt->bind_param("sii", $json_text, $row_count, $file_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'File updated successfully',
        'file_uuid' => $file_uuid,
        'row_count' => $row_count
    ]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $stmt->error]);
}

$stmt->close();
?>
