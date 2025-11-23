<?php
$file_id = $_GET['file_id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM questions WHERE file_id = ? ORDER BY order_index ASC");
$stmt->execute([$file_id]);
$questions = $stmt->fetchAll();

echo json_encode($questions);
?>
