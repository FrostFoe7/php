<?php
// Lightweight AJAX endpoint to avoid LiteSpeed/ModSecurity 403 on large POST bodies to file-edit.php
require_once __DIR__ . '/includes/bootstrap.php';
requireLogin();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Expect JSON body
$raw = file_get_contents('php://input');
$data = json_decode($raw, true);
if (!is_array($data)) {
    // Fallback to form encoding in case JSON fails
    $data = $_POST;
}

$csrf = $data['csrf_token'] ?? '';
verifyCsrfToken($csrf);

$file_id = $data['file_id'] ?? '';
$new_filename = isset($data['original_filename']) ? trim($data['original_filename']) : null;
$questionsPayload = $data['questions'] ?? null;

if (!$file_id) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing file_id']);
    exit;
}

// Validate file exists
$stmt = $pdo->prepare("SELECT id, original_filename FROM files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch();

if (!$file) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

$response = [
    'success' => false,
    'updated_questions' => 0,
    'file_renamed' => false,
    'error' => null
];

try {
    $pdo->beginTransaction();

    // 1. Update file name if a new one is provided and it's different
    if ($new_filename !== null && $new_filename !== '' && $new_filename !== $file['original_filename']) {
        $updateFileStmt = $pdo->prepare("UPDATE files SET original_filename = ? WHERE id = ?");
        $updateFileStmt->execute([$new_filename, $file_id]);
        $response['file_renamed'] = $updateFileStmt->rowCount() > 0;
    }

    // 2. Update questions if payload is present
    if (is_array($questionsPayload)) {
        $updateQStmt = $pdo->prepare(
            "UPDATE questions SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, option5 = ?, answer = ?, explanation = ?, type = ?, section = ? WHERE id = ? AND file_id = ?"
        );

        $updated_count = 0;
        foreach ($questionsPayload as $qid => $q) {
            if (!is_array($q)) continue;
            
            $params = [
                $q['question_text'] ?? '',
                $q['option1'] ?? '',
                $q['option2'] ?? '',
                $q['option3'] ?? '',
                $q['option4'] ?? '',
                $q['option5'] ?? '',
                $q['answer'] ?? '',
                $q['explanation'] ?? '',
                // Ensure type and section are correctly cast
                (isset($q['type']) && $q['type'] !== '') ? (int)$q['type'] : 0,
                $q['section'] ?? '0',
                $qid,
                $file_id
            ];
            $updateQStmt->execute($params);
            $updated_count++;
        }
        $response['updated_questions'] = $updated_count;
    }

    $pdo->commit();
    $response['success'] = true;
    echo json_encode($response);

} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    // Provide a more detailed error in the response
    echo json_encode([
        'success' => false,
        'error' => 'Save operation failed',
        'details' => $e->getMessage()
    ]);
}
