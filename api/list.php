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

    // Validate and parse file_id parameter
    $fileId = null;
    if (isset($_GET['file_id'])) {
        $fileId = (int)$_GET['file_id'];
        // Validate file_id is non-negative
        if ($fileId < 0) {
            APIResponse::error('Invalid file_id: must be non-negative.', 400);
            exit;
        }
    }

    // Use !== null to handle file_id = 0 correctly
    if ($fileId !== null) {
        // Get specific file's questions
        $mapData = $db->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        // Verify file exists
        if (!isset($allFilesData[$fileId])) {
            APIResponse::error('File not found.', 404);
            exit;
        }

        // Add uid and file_id to each question
        $questions = [];
        foreach ($globalUidMap as $uid => $info) {
            if ($info['file_id'] === $fileId) {
                $indexInFile = $info['index_in_file'];
                if (isset($allFilesData[$fileId][$indexInFile])) {
                    $question = $allFilesData[$fileId][$indexInFile];
                    $question['uid'] = $uid;
                    $question['file_id'] = $fileId;
                    $questions[] = $question;
                }
            }
        }

        APIResponse::success(['questions' => $questions, 'total' => count($questions), 'file_id' => $fileId]);
        exit;
    } else {
        // Get all questions
        $allQuestions = $db->getAllQuestions();
        APIResponse::success(['questions' => $allQuestions, 'total' => count($allQuestions)]);
        exit;
    }

} catch (Exception $e) {
    // Log internal error details (don't expose to client)
    error_log('API Error in list.php: ' . $e->getMessage());
    APIResponse::error('Internal server error.', 500);
    exit;
}
?>
