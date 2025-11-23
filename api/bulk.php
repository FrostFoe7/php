<?php
/**
 * POST /api/bulk.php
 * Bulk operations on questions
 * 
 * Supported operations:
 * 1. Bulk delete: Delete multiple questions by uid
 * 2. Bulk update: Update same field across multiple questions
 * 3. Bulk create: Create multiple questions at once
 * 
 * Usage (JSON POST):
 *   // Bulk delete
 *   POST /api/bulk.php
 *   {
 *     "key": "frostfoe1337",
 *     "operation": "delete",
 *     "uids": [1, 2, 3]
 *   }
 *   
 *   // Bulk update
 *   POST /api/bulk.php
 *   {
 *     "key": "frostfoe1337",
 *     "operation": "update",
 *     "updates": [
 *       {"uid": 1, "field": "category", "value": "Mathematics"},
 *       {"uid": 2, "field": "category", "value": "Mathematics"}
 *     ]
 *   }
 *   
 *   // Bulk create
 *   POST /api/bulk.php
 *   {
 *     "key": "frostfoe1337",
 *     "operation": "create",
 *     "file_id": 1,
 *     "questions": [
 *       {"question": "Q1?", "option1": "A", "option2": "B", "correct": "A"},
 *       {"question": "Q2?", "option1": "C", "option2": "D", "correct": "D"}
 *     ]
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    APIResponse::error('Invalid request method. Only POST is accepted.', 405);
}

// Handle JSON payload
$rawInput = file_get_contents('php://input');
if (empty($rawInput)) {
    APIResponse::error('Empty request body.', 400);
}

$input = json_decode($rawInput, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    APIResponse::error('Invalid JSON payload.', 400);
}

// Validate API key
$apiKey = $input['key'] ?? '';
APIValidator::validateApiKey($apiKey);

try {
    $db = new QuestionDB();
    $operation = $input['operation'] ?? '';

    if ($operation === 'delete') {
        // Bulk delete
        $uids = $input['uids'] ?? [];
        
        if (!is_array($uids) || empty($uids)) {
            APIResponse::error('Uids must be a non-empty array.', 400);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        // Sort uids in descending order to avoid index shifting issues
        rsort($uids);
        
        foreach ($uids as $uid) {
            try {
                $db->deleteQuestion($uid);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = ['uid' => $uid, 'error' => $e->getMessage()];
            }
        }

        APIResponse::success($results, 'Bulk delete operation completed.');

    } elseif ($operation === 'update') {
        // Bulk update
        $updates = $input['updates'] ?? [];
        
        if (!is_array($updates) || empty($updates)) {
            APIResponse::error('Updates must be a non-empty array.', 400);
        }

        $results = ['success' => 0, 'failed' => 0, 'errors' => []];
        
        foreach ($updates as $update) {
            try {
                $uid = $update['uid'] ?? null;
                $field = $update['field'] ?? null;
                $value = $update['value'] ?? null;

                if (!$uid || !$field) {
                    throw new Exception('Missing uid or field in update');
                }

                $db->updateField($uid, $field, $value);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = ['update' => $update, 'error' => $e->getMessage()];
            }
        }

        APIResponse::success($results, 'Bulk update operation completed.');

    } elseif ($operation === 'create') {
        // Bulk create
        $fileId = $input['file_id'] ?? null;
        $questions = $input['questions'] ?? [];
        
        if (!$fileId) {
            APIResponse::error('Missing file_id.', 400);
        }

        if (!is_array($questions) || empty($questions)) {
            APIResponse::error('Questions must be a non-empty array.', 400);
        }

        $fileId = (int)$fileId;
        $results = ['success' => 0, 'failed' => 0, 'errors' => [], 'created_uids' => []];
        
        foreach ($questions as $index => $questionData) {
            try {
                if (empty($questionData['question']) && empty($questionData['questions'])) {
                    throw new Exception('Question text is required (field: question or questions)');
                }

                $db->addQuestion($fileId, $questionData);
                $results['success']++;
            } catch (Exception $e) {
                $results['failed']++;
                $results['errors'][] = ['index' => $index, 'error' => $e->getMessage()];
            }
        }

        // Get new uid range
        $mapData = $db->getGlobalUidMap();
        $results['total_questions'] = $mapData['total_questions'];

        APIResponse::success($results, 'Bulk create operation completed.', 201);

    } else {
        APIResponse::error('Unknown operation: ' . $operation . '. Supported: delete, update, create', 400);
    }

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
