<?php
/**
 * POST /api/delete.php
 * Delete a question by uid
 * 
 * Usage (Form-urlencoded POST):
 *   POST /api/delete.php
 *   key=frostfoe1337&uid=1
 *   
 * Usage (JSON POST):
 *   POST /api/delete.php
 *   {
 *     "key": "frostfoe1337",
 *     "uid": 1
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

    $uid = $input['uid'] ?? null;
    APIValidator::validateRequired(['uid' => $uid], ['uid']);
    $uid = (int)$uid;

    $db->deleteQuestion($uid);
    APIResponse::success(['uid' => $uid, 'deleted' => true], 'Question deleted successfully.');

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
