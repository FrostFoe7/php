<?php
require_once '../includes/bootstrap.php';
requireLogin();

header('Content-Type: application/json');

$id = $_GET['id'] ?? '';

if (empty($id)) {
    http_response_code(400);
    echo json_encode(['error' => 'ID required']);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$file) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
}

echo json_encode($file);
