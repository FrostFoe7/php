<?php
/**
 * GET /api/get.php
 * Fetches specific file or single question by uid
 * 
 * Usage:
 *   GET /api/get.php?key=frostfoe1337&file_id=1
 *   GET /api/get.php?key=frostfoe1337&uid=5
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core.php';

// Validate API key
$apiKey = $_GET['key'] ?? '';
APIValidator::validateApiKey($apiKey);

try {
    $db = new QuestionDB();

    if (isset($_GET['uid'])) {
        // Get single question by uid
        $uid = (int)$_GET['uid'];
        $question = $db->getQuestionByUid($uid);

        if (!$question) {
            APIResponse::error('Question with uid ' . $uid . ' not found.', 404);
        }

        APIResponse::success(['question' => $question]);

    } elseif (isset($_GET['file_id'])) {
        // Get single file by ID
        $fileId = (int)$_GET['file_id'];
        
        $stmt = $GLOBALS['conn']->prepare("SELECT id, filename, description, row_count FROM csv_files WHERE id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            APIResponse::error('File not found.', 404);
        }

        $file = $result->fetch_assoc();
        $stmt->close();

        // Get file's questions
        $mapData = $db->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

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

        $file['questions'] = $questions;
        $file['question_count'] = count($questions);

        APIResponse::success(['file' => $file]);

    } else {
        APIResponse::error('Missing required parameter: uid or file_id', 400);
    }

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
