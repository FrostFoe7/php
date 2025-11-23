<?php
/**
 * GET /api/files.php
 * List all available CSV files
 * 
 * Usage:
 *   GET /api/files.php?key=frostfoe1337
 */

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/core.php';

// Validate API key
$apiKey = $_GET['key'] ?? '';
APIValidator::validateApiKey($apiKey);

try {
    $db = new QuestionDB();
    $files = $db->getFiles();
    
    APIResponse::success(['files' => $files, 'total' => count($files)]);

} catch (Exception $e) {
    APIResponse::error('Internal server error: ' . $e->getMessage(), 500);
}
?>
