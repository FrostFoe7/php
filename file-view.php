<?php
require_once 'includes/bootstrap.php';
requireLogin();

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM files WHERE id = ?");
$stmt->execute([$id]);
$file = $stmt->fetch();

if (!$file) {
    die("File not found");
}

$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE file_id = ? ORDER BY order_index ASC");
$q_stmt->execute([$id]);
$questions = $q_stmt->fetchAll();
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><?php echo h($file['original_filename']); ?></h2>
    <div>
        <a href="file-edit.php?id=<?php echo $file['id']; ?>" class="btn btn-warning">Edit All</a>
        <a href="index.php" class="btn btn-secondary">Back</a>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body">
        <strong>Total Questions:</strong> <?php echo count($questions); ?><br>
        <strong>Uploaded:</strong> <?php echo h($file['uploaded_at']); ?>
    </div>
</div>

<?php foreach ($questions as $q): ?>
<div class="card mb-2">
    <div class="card-body">
        <h5 class="card-title">Q<?php echo $q['order_index'] + 1; ?>: <?php echo $q['question_text']; // Allow HTML ?></h5>
        <div class="row">
            <div class="col-md-6">
                <ul>
                    <li>A: <?php echo $q['option1']; ?></li>
                    <li>B: <?php echo $q['option2']; ?></li>
                    <li>C: <?php echo $q['option3']; ?></li>
                    <li>D: <?php echo $q['option4']; ?></li>
                    <li>E: <?php echo $q['option5']; ?></li>
                </ul>
            </div>
            <div class="col-md-6">
                <p><strong>Answer:</strong> <?php echo h($q['answer']); ?></p>
                <p><strong>Type:</strong> <?php echo h($q['type']); ?> | <strong>Section:</strong> <?php echo h($q['section']); ?></p>
                <div class="alert alert-light">
                    <strong>Explanation:</strong><br>
                    <?php echo $q['explanation']; // Allow HTML ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<?php include 'templates/footer.php'; ?>
