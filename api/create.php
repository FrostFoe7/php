<?php
/**
 * POST /api/create.php
 * Add new question to a file
 * 
 * Usage (JSON POST):
 *   POST /api/create.php
 *   {
 *     "key": "frostfoe1337",
 *     "file_id": 1,
 *     "question": "What is 2+2?",
 *     "description": "Basic math question",
 *     "option1": "3",
 *     "option2": "4",
 *     "option3": "5",
 *     "option4": "6",
 *     "option5": "7",
 *     "correct": "B",
 *     "explanation": "2+2=4"
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
    APIValidator::validateRequired(['file_id' => $fileId], ['file_id']);
    $fileId = (int)$fileId;

    // Required fields for a question (accept either 'question' or 'questions')
    if (empty($input['question']) && empty($input['questions'])) {
        APIResponse::error('Question text is required (field: question or questions).', 400);
    }

    // Extract question data
    $questionData = [];
    $allowedFields = ['question', 'questions', 'description', 'option1', 'option2', 'option3', 'option4', 'option5', 'correct', 'answer', 'explanation', 'category', 'difficulty', 'tags', 'type', 'section'];
    
    foreach ($allowedFields as $field) {
        if (isset($input[$field])) {
            $questionData[$field] = $input[$field];
        }
    }

    // Normalize question field if needed
    // If 'question' is provided but not 'questions', and we want to support both, we keep what is provided.
    // But we should ensure at least one exists.
    
    $db->addQuestion($fileId, $questionData);

    // Get the new uid
    $mapData = $db->getGlobalUidMap();
    $newUid = $mapData['total_questions'];

    APIResponse::success(
        ['file_id' => $fileId, 'uid' => $newUid, 'question' => $questionData],
        'Question created successfully.',
        201
    );

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
