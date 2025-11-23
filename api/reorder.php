<?php
/**
 * POST /api/reorder.php
 * Reorder questions
 * 
 * Usage (JSON POST):
 *   POST /api/reorder.php
 *   {
 *     "key": "frostfoe1337",
 *     "file_id": 1,
 *     "order": [5, 3, 1, 2, 4]  // New order by uid within the file
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    APIResponse::error('Invalid request method. Only POST is accepted.', 405);
}

// Handle both form-urlencoded and JSON payloads
$input = $_POST;

if (empty($input)) {
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $input = json_decode($rawInput, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            APIResponse::error('Invalid JSON payload.', 400);
        }
    }
}

// Validate API key
$apiKey = $input['key'] ?? '';
APIValidator::validateApiKey($apiKey);

try {
    $db = new QuestionDB();

    $fileId = $input['file_id'] ?? null;
    $order = $input['order'] ?? null;

    APIValidator::validateRequired(['file_id' => $fileId, 'order' => $order], ['file_id', 'order']);

    if (!is_array($order) || empty($order)) {
        APIResponse::error('Order must be a non-empty array of question uids.', 400);
    }

    $fileId = (int)$fileId;

    // Get current questions in file
    $mapData = $db->getGlobalUidMap();
    $globalUidMap = $mapData['uid_map'];
    $allFilesData = $mapData['files_data'];

    // Get all questions from this file
    $fileQuestions = [];
    foreach ($globalUidMap as $uid => $info) {
        if ($info['file_id'] === $fileId) {
            $indexInFile = $info['index_in_file'];
            $fileQuestions[$uid] = $allFilesData[$fileId][$indexInFile];
        }
    }

    // Validate all uids exist in file
    foreach ($order as $uid) {
        if (!isset($fileQuestions[$uid])) {
            APIResponse::error('Question with uid ' . $uid . ' not found in file ' . $fileId . '.', 404);
        }
    }

    // Ensure all questions in the file are included in the order
    if (count($order) !== count($fileQuestions)) {
        APIResponse::error('Order array must contain all questions in the file. Expected ' . count($fileQuestions) . ' uids, got ' . count($order) . '.', 400);
    }

    // Reorder questions
    $reorderedQuestions = [];
    foreach ($order as $uid) {
        $reorderedQuestions[] = $fileQuestions[$uid];
    }

    // Save reordered questions
    $globalConn = $GLOBALS['conn'];
    $jsonText = json_encode($reorderedQuestions, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    $stmt = $globalConn->prepare("UPDATE csv_files SET json_text = ? WHERE id = ?");
    $stmt->bind_param("si", $jsonText, $fileId);

    if ($stmt->execute()) {
        $stmt->close();
        APIResponse::success(
            ['file_id' => $fileId, 'reordered_count' => count($order)],
            'Questions reordered successfully.'
        );
    } else {
        $stmt->close();
        APIResponse::error('Failed to save reordered questions.', 500);
    }

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
