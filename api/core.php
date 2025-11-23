<?php
/**
 * API Core Functions - Centralized API utilities
 */

require_once __DIR__ . '/../includes/config.php';

class APIResponse {
    public static function send($success, $data = [], $message = '', $statusCode = 200) {
        http_response_code($statusCode);
        
        $response = [
            'success' => $success,
        ];
        
        if ($message) {
            $response['message'] = $message;
        }
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    public static function error($message = 'Unknown error', $statusCode = 400, $data = []) {
        self::send(false, $data, $message, $statusCode);
    }

    public static function success($data = [], $message = '', $statusCode = 200) {
        self::send(true, $data, $message, $statusCode);
    }
}

class APIValidator {
    private static $errors = [];

    public static function validateApiKey($key) {
        if (empty($key) || $key !== API_KEY) {
            APIResponse::error('Unauthorized: Invalid or missing API key.', 401);
        }
        return true;
    }

    public static function validateRequired($params, $requiredFields) {
        self::$errors = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($params[$field]) || (is_string($params[$field]) && trim($params[$field]) === '')) {
                self::$errors[] = "Missing required field: $field";
            }
        }

        if (!empty(self::$errors)) {
            APIResponse::error(implode('; ', self::$errors), 400);
        }

        return true;
    }

    public static function validateNumeric($value, $fieldName) {
        if (!is_numeric($value)) {
            APIResponse::error("Field '$fieldName' must be numeric.", 400);
        }
        return true;
    }

    public static function validateUidExists($uid, $globalUidMap) {
        if (!isset($globalUidMap[$uid])) {
            APIResponse::error('Record with specified uid not found.', 404);
        }
        return true;
    }
}

class QuestionDB {
    private $conn;

    public function __construct() {
        global $conn;
        $this->conn = $conn;
    }

    /**
     * Get global UID map (uid => [file_id, index_in_file])
     */
    public function getGlobalUidMap() {
        $stmt = $this->conn->prepare("SELECT id, json_text FROM csv_files ORDER BY id");
        $stmt->execute();
        $result = $stmt->get_result();

        $globalUidMap = [];
        $allFilesData = [];
        $currentGlobalUid = 1;

        while ($file = $result->fetch_assoc()) {
            $fileId = $file['id'];
            $jsonData = json_decode($file['json_text'], true);

            if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
                foreach ($jsonData as $indexInFile => $row) {
                    $globalUidMap[$currentGlobalUid] = [
                        'file_id' => $fileId,
                        'index_in_file' => $indexInFile
                    ];
                    $currentGlobalUid++;
                }
                $allFilesData[$fileId] = $jsonData;
            }
        }

        $stmt->close();

        return [
            'uid_map' => $globalUidMap,
            'files_data' => $allFilesData,
            'total_questions' => $currentGlobalUid - 1
        ];
    }

    /**
     * Get all questions with uid mapping
     */
    public function getAllQuestions() {
        $mapData = $this->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        $questions = [];
        foreach ($globalUidMap as $uid => $info) {
            $fileId = $info['file_id'];
            $indexInFile = $info['index_in_file'];

            if (isset($allFilesData[$fileId][$indexInFile])) {
                $question = $allFilesData[$fileId][$indexInFile];
                $question['uid'] = $uid;
                $question['file_id'] = $fileId;
                $questions[] = $question;
            }
        }

        return $questions;
    }

    /**
     * Get single question by uid
     */
    public function getQuestionByUid($uid) {
        $mapData = $this->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        if (!isset($globalUidMap[$uid])) {
            return null;
        }

        $info = $globalUidMap[$uid];
        $fileId = $info['file_id'];
        $indexInFile = $info['index_in_file'];

        if (isset($allFilesData[$fileId][$indexInFile])) {
            $question = $allFilesData[$fileId][$indexInFile];
            $question['uid'] = $uid;
            $question['file_id'] = $fileId;
            return $question;
        }

        return null;
    }

    /**
     * Update single field
     */
    public function updateField($uid, $field, $value) {
        $mapData = $this->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        APIValidator::validateUidExists($uid, $globalUidMap);

        $info = $globalUidMap[$uid];
        $fileId = $info['file_id'];
        $indexInFile = $info['index_in_file'];

        $allFilesData[$fileId][$indexInFile][$field] = $value;

        return $this->saveFileData($fileId, $allFilesData[$fileId]);
    }

    /**
     * Update multiple fields at once
     */
    public function updateMultipleFields($uid, $updates) {
        $mapData = $this->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        APIValidator::validateUidExists($uid, $globalUidMap);

        $info = $globalUidMap[$uid];
        $fileId = $info['file_id'];
        $indexInFile = $info['index_in_file'];

        foreach ($updates as $field => $value) {
            $allFilesData[$fileId][$indexInFile][$field] = $value;
        }

        return $this->saveFileData($fileId, $allFilesData[$fileId]);
    }

    /**
     * Update options array
     */
    public function updateOptions($uid, $optionsData) {
        $mapData = $this->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        APIValidator::validateUidExists($uid, $globalUidMap);

        $info = $globalUidMap[$uid];
        $fileId = $info['file_id'];
        $indexInFile = $info['index_in_file'];

        // Update individual option fields or full options object
        if (isset($optionsData['options']) && is_array($optionsData['options'])) {
            // Full options replacement
            for ($i = 0; $i < count($optionsData['options']); $i++) {
                $allFilesData[$fileId][$indexInFile]["option" . ($i + 1)] = $optionsData['options'][$i];
            }
        } else {
            // Individual option update
            foreach ($optionsData as $optionField => $value) {
                $allFilesData[$fileId][$indexInFile][$optionField] = $value;
            }
        }

        return $this->saveFileData($fileId, $allFilesData[$fileId]);
    }

    /**
     * Delete a question by uid
     */
    public function deleteQuestion($uid) {
        $mapData = $this->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        APIValidator::validateUidExists($uid, $globalUidMap);

        $info = $globalUidMap[$uid];
        $fileId = $info['file_id'];
        $indexInFile = $info['index_in_file'];

        // Remove the question from the file
        unset($allFilesData[$fileId][$indexInFile]);

        // Reindex array to maintain proper JSON structure
        $allFilesData[$fileId] = array_values($allFilesData[$fileId]);

        return $this->saveFileData($fileId, $allFilesData[$fileId]);
    }

    /**
     * Reorder questions within or across files
     */
    public function reorderQuestions($reorderMap) {
        // $reorderMap: ['uid' => new_position_uid, ...]
        // This requires careful handling across multiple files

        $mapData = $this->getGlobalUidMap();
        $globalUidMap = $mapData['uid_map'];
        $allFilesData = $mapData['files_data'];

        // Validate all uids exist
        foreach (array_keys($reorderMap) as $uid) {
            APIValidator::validateUidExists($uid, $globalUidMap);
        }

        // For now, implement single-file reordering
        // Extract all questions and reconstruct
        $questions = [];
        foreach ($globalUidMap as $uid => $info) {
            $fileId = $info['file_id'];
            $indexInFile = $info['index_in_file'];
            $questions[$uid] = $allFilesData[$fileId][$indexInFile];
        }

        // Apply reordering (simplified: assumes all in same file)
        // This is complex for cross-file moves; for now handle single file
        $firstUid = array_key_first($reorderMap);
        $targetFileId = $globalUidMap[$firstUid]['file_id'];

        // Get all questions from target file
        $fileQuestions = [];
        foreach ($globalUidMap as $uid => $info) {
            if ($info['file_id'] === $targetFileId) {
                $indexInFile = $info['index_in_file'];
                $fileQuestions[$uid] = $allFilesData[$targetFileId][$indexInFile];
            }
        }

        // Reorder based on reorderMap
        $newOrder = [];
        foreach ($reorderMap as $uid => $newPos) {
            if (isset($fileQuestions[$uid])) {
                $newOrder[$newPos] = $fileQuestions[$uid];
            }
        }
        ksort($newOrder);

        // Save reordered questions
        $reorderedData = array_values($newOrder);
        return $this->saveFileData($targetFileId, $reorderedData);
    }

    /**
     * Add new question to file
     */
    public function addQuestion($fileId, $questionData) {
        $stmt = $this->conn->prepare("SELECT json_text FROM csv_files WHERE id = ?");
        $stmt->bind_param("i", $fileId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            APIResponse::error("File with ID $fileId not found.", 404);
        }

        $file = $result->fetch_assoc();
        $stmt->close();

        $jsonData = json_decode($file['json_text'], true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            APIResponse::error('Failed to parse file JSON data.', 500);
        }

        $jsonData[] = $questionData;

        return $this->saveFileData($fileId, $jsonData);
    }

    /**
     * Save file data to database
     */
    private function saveFileData($fileId, $data) {
        $jsonText = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

        $stmt = $this->conn->prepare("UPDATE csv_files SET json_text = ? WHERE id = ?");
        $stmt->bind_param("si", $jsonText, $fileId);

        if ($stmt->execute()) {
            $stmt->close();
            return true;
        } else {
            $stmt->close();
            APIResponse::error('Failed to save data to database.', 500);
        }
    }

    /**
     * Get available files
     */
    public function getFiles() {
        $stmt = $this->conn->prepare("SELECT id, filename, description, row_count FROM csv_files");
        $stmt->execute();
        $result = $stmt->get_result();

        $files = [];
        while ($file = $result->fetch_assoc()) {
            $files[] = $file;
        }

        $stmt->close();
        return $files;
    }
}
?>
