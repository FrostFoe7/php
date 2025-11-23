<?php
/**
 * GET /api/list.php
 * Fetches all questions with uid mapping
 * Optional filter by file UUID (10-digit YYMMDDHHMI format)
 * 
 * Usage:
 *   GET /api/list.php?key=frostfoe1337
 *   GET /api/list.php?key=frostfoe1337&file_uuid=2411231830
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core.php';

// Validate API key
$apiKey = $_GET['key'] ?? '';
APIValidator::validateApiKey($apiKey);

try {
    $db = new QuestionDB();

    // Validate and parse file_uuid parameter
    $fileUuid = null;
    if (isset($_GET['file_uuid'])) {
        $fileUuid = trim($_GET['file_uuid']);
        
        // Validate file_uuid format (10 digits)
        if ($fileUuid !== '' && (!is_numeric($fileUuid) || strlen($fileUuid) != 10)) {
            APIResponse::error('Invalid file_uuid: must be 10 digits in YYMMDDHHMI format.', 400);
            exit;
        }
    }

    // Use !== null to handle file_uuid correctly
    if ($fileUuid !== null) {
        // Get specific file's questions by UUID
        $mapData = $db->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];
        
        // Get file ID from UUID
        $stmt = $GLOBALS['conn']->prepare("SELECT id FROM csv_files WHERE file_uuid = ?");
        $stmt->bind_param("s", $fileUuid);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            $stmt->close();
            APIResponse::error('File not found.', 404);
            exit;
        }
        
        $file = $result->fetch_assoc();
        $stmt->close();
        $fileId = $file['id'];

        // Verify file exists in data
        if (!isset($allFilesData[$fileId])) {
            APIResponse::error('File data not found.', 404);
            exit;
        }

        // Add uid and file_uuid to each question
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

        APIResponse::success(['questions' => $questions, 'total' => count($questions), 'file_uuid' => $fileUuid]);
        exit;
    } else {
        // Get all questions with file_uuid
        $allQuestions = $db->getAllQuestions();
        
        // Add file_uuid to each question
        $questionsWithUuid = [];
        foreach ($allQuestions as $question) {
            $fileId = $question['file_id'];
            
            // Get file_uuid
            $stmt = $GLOBALS['conn']->prepare("SELECT file_uuid FROM csv_files WHERE id = ?");
            $stmt->bind_param("i", $fileId);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $file = $result->fetch_assoc();
                $question['file_uuid'] = $file['file_uuid'];
            }
            $stmt->close();
            
            $questionsWithUuid[] = $question;
        }
        
        APIResponse::success(['questions' => $questionsWithUuid, 'total' => count($questionsWithUuid)]);
        exit;
    }

} catch (Exception $e) {
    // Log internal error details (don't expose to client)
    error_log('API Error in list.php: ' . $e->getMessage());
    APIResponse::error('Internal server error.', 500);
    exit;
}
?>
