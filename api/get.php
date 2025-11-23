<?php
/**
 * GET /api/get.php
 * Fetches specific file or single question by uid
 * 
 * Usage:
 *   GET /api/get.php?key=frostfoe1337&file_uuid=2411231830
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
        
        // Validate uid is non-negative
        if ($uid < 0) {
            APIResponse::error('Invalid uid: must be non-negative.', 400);
            exit;
        }

        $question = $db->getQuestionByUid($uid);

        if (!$question) {
            APIResponse::error('Question with uid ' . $uid . ' not found.', 404);
            exit;
        }

        // Add file_uuid to response
        $fileId = $question['file_id'];
        $stmt = $GLOBALS['conn']->prepare("SELECT file_uuid FROM csv_files WHERE id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $file = $result->fetch_assoc();
            $question['file_uuid'] = $file['file_uuid'];
        }
        $stmt->close();

        APIResponse::success(['question' => $question]);
        exit;

    } elseif (isset($_GET['file_uuid'])) {
        // Get single file by UUID
        $fileUuid = trim($_GET['file_uuid']);
        
        // Validate file_uuid format (10 digits)
        if ($fileUuid === '' || !is_numeric($fileUuid) || strlen($fileUuid) != 10) {
            APIResponse::error('Invalid file_uuid: must be 10 digits in YYMMDDHHMI format.', 400);
            exit;
        }
        
        // Get file ID from UUID
        $stmt = $GLOBALS['conn']->prepare("SELECT id, filename, description, row_count FROM csv_files WHERE file_uuid = ?");
        $stmt->bind_param("s", $fileUuid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            APIResponse::error('File not found.', 404);
            exit;
        }

        $file = $result->fetch_assoc();
        $fileId = $file['id'];
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
                    $question['file_uuid'] = $fileUuid;
                    $questions[] = $question;
                }
            }
        }

        $file['questions'] = $questions;
        $file['question_count'] = count($questions);
        $file['file_uuid'] = $fileUuid;

        APIResponse::success(['file' => $file]);
        exit;

    } else {
        APIResponse::error('Missing required parameter: uid or file_uuid', 400);
        exit;
    }

} catch (Exception $e) {
    // Log internal error details (don't expose to client)
    error_log('API Error in get.php: ' . $e->getMessage());
    APIResponse::error('Internal server error.', 500);
    exit;
}
?>
