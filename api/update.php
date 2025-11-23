<?php
/**
 * PUT /api/update.php
 * Update single or multiple fields in a question
 * Update options for a question
 * 
 * Usage (Form-urlencoded POST):
 *   POST /api/update.php
 *   key=frostfoe1337&uid=1&field=question&value=New Question Text
 *   key=frostfoe1337&uid=1&field=option1&value=New Option
 *   
 * Usage (JSON POST):
 *   POST /api/update.php
 *   {
 *     "key": "frostfoe1337",
 *     "uid": 1,
 *     "updates": {
 *       "question": "New Question",
 *       "description": "New Description",
 *       "option1": "Option 1",
 *       "correct": "A"
 *     }
 *   }
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    APIResponse::error('Invalid request method. Only POST is accepted.', 405);
}

// Handle both form-urlencoded and JSON payloads
$input = $_POST;

// If empty POST, try to parse JSON from request body
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

    // Single field update (form-urlencoded)
    if (isset($input['field']) && isset($input['value'])) {
        $field = $input['field'];
        $value = $input['value'];

        $db->updateField($uid, $field, $value);
        APIResponse::success(['uid' => $uid, 'field' => $field, 'value' => $value], 'Question field updated successfully.');

    // Multiple fields update (JSON payload)
    } elseif (isset($input['updates']) && is_array($input['updates'])) {
        $updates = $input['updates'];

        if (empty($updates)) {
            APIResponse::error('No updates provided.', 400);
        }

        $db->updateMultipleFields($uid, $updates);
        APIResponse::success(['uid' => $uid, 'updates' => $updates], 'Question updated successfully.');

    // Update options specifically
    } elseif (isset($input['options']) || isset($input['option1']) || isset($input['option2']) || isset($input['option3']) || isset($input['option4']) || isset($input['option5'])) {
        $optionsData = [];

        if (isset($input['options']) && is_array($input['options'])) {
            $optionsData['options'] = $input['options'];
        } else {
            for ($i = 1; $i <= 5; $i++) {
                if (isset($input['option' . $i])) {
                    $optionsData['option' . $i] = $input['option' . $i];
                }
            }
        }

        $db->updateOptions($uid, $optionsData);
        APIResponse::success(['uid' => $uid, 'options_updated' => array_keys($optionsData)], 'Question options updated successfully.');

    } else {
        APIResponse::error('Missing update parameters. Provide either (field + value), updates object, or options.', 400);
    }

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
