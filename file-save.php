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
    // Fallback to form encoding
    $data = $_POST;
}

$csrf = $data['csrf_token'] ?? '';
verifyCsrfToken($csrf);

$file_id = $data['file_id'] ?? '';
$questionsPayload = $data['questions'] ?? null;

if (!$file_id || !is_array($questionsPayload)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing file_id or questions payload']);
    exit;
}

// Validate file exists
$stmt = $pdo->prepare("SELECT id FROM files WHERE id = ?");
$stmt->execute([$file_id]);
$file = $stmt->fetch();
if (!$file) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

try {
    $pdo->beginTransaction();
    $updateStmt = $pdo->prepare(
        "UPDATE questions SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, option5 = ?, answer = ?, explanation = ?, type = ?, section = ? WHERE id = ? AND file_id = ?"
    );

    $count = 0;
    foreach ($questionsPayload as $qid => $q) {
        // Skip if not an array
        if (!is_array($q)) continue;
        $updateStmt->execute([
            $q['question_text'] ?? '',
            $q['option1'] ?? '',
            $q['option2'] ?? '',
            $q['option3'] ?? '',
            $q['option4'] ?? '',
            $q['option5'] ?? '',
            $q['answer'] ?? '',
            $q['explanation'] ?? '',
            (int)($q['type'] ?? 0),
            (int)($q['section'] ?? 0),
            $qid,
            $file_id
        ]);
        $count++;
    }

    $pdo->commit();
    echo json_encode(['success' => true, 'updated' => $count]);
} catch (Exception $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Save failed', 'details' => $e->getMessage()]);
}
