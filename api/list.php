<?php
/**
 * GET /api/list.php
 * Fetches all questions with uid mapping
 * Optional filter by file ID
 * 
 * Usage:
 *   GET /api/list.php?key=frostfoe1337
 *   GET /api/list.php?key=frostfoe1337&file_id=1
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core.php';

// Validate API key
$apiKey = $_GET['key'] ?? '';
APIValidator::validateApiKey($apiKey);

try {
    $db = new QuestionDB();

    $fileId = isset($_GET['file_id']) ? (int)$_GET['file_id'] : null;

    if ($fileId) {
        // Get specific file's questions
        $stmt = $GLOBALS['conn']->prepare("SELECT json_text FROM csv_files WHERE id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            APIResponse::error('File not found.', 404);
        }

        $file = $result->fetch_assoc();
        $stmt->close();

        $jsonData = json_decode($file['json_text'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            APIResponse::error('Failed to parse file JSON.', 500);
        }

        // Add uid and file_id to each question
        $questions = [];
        $mapData = $db->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];

        foreach ($globalUidMap as $uid => $info) {
            if ($info['file_id'] === $fileId) {
                $indexInFile = $info['index_in_file'];
                if (isset($jsonData[$indexInFile])) {
                    $question = $jsonData[$indexInFile];
                    $question['uid'] = $uid;
                    $question['file_id'] = $fileId;
                    $questions[] = $question;
                }
            }
        }

        APIResponse::success(['questions' => $questions, 'total' => count($questions), 'file_id' => $fileId]);
    } else {
        // Get all questions
        $allQuestions = $db->getAllQuestions();
        APIResponse::success(['questions' => $allQuestions, 'total' => count($allQuestions)]);
    }

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
