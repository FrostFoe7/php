<?php
require_once 'includes/bootstrap.php';
requireLogin();

$id = $_GET['id'] ?? '';
$stmt = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmt->execute([$id]);
$q = $stmt->fetch();

if (!$q) {
    die("Question not found");
}

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    try {
        $update_stmt = $pdo->prepare("UPDATE questions SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, option5 = ?, answer = ?, explanation = ?, type = ?, section = ? WHERE id = ?");
        $update_stmt->execute([
            $_POST['question_text'],
            $_POST['option1'],
            $_POST['option2'],
            $_POST['option3'],
            $_POST['option4'],
            $_POST['option5'],
            $_POST['answer'],
            $_POST['explanation'],
            (int)$_POST['type'],
            (int)$_POST['section'],
            $id
        ]);
        
        // Refresh data
        $stmt->execute([$id]);
        $q = $stmt->fetch();
        
        $success = "Question updated successfully";
    } catch (Exception $e) {
        $error = "Error saving: " . $e->getMessage();
    }
}
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Edit Question</h2>
    <a href="file-edit.php?id=<?php echo $q['file_id']; ?>" class="btn btn-secondary">Back to File</a>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo h($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo h($error); ?></div>
<?php endif; ?>

<form method="post">
    <?php echo csrfInput(); ?>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Question Text (HTML Allowed)</label>
                <textarea name="question_text" class="form-control" rows="3"><?php echo h($q['question_text']); ?></textarea>
            </div>
            
            <div class="row">
                <?php for($i=1; $i<=5; $i++): ?>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Option <?php echo $i; ?></label>
                    <textarea name="option<?php echo $i; ?>" class="form-control" rows="1"><?php echo h($q['option'.$i]); ?></textarea>
                </div>
                <?php endfor; ?>
            </div>

            <div class="row mt-2">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Answer</label>
                    <input type="text" name="answer" class="form-control" value="<?php echo h($q['answer']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Type (Int)</label>
                    <input type="number" name="type" class="form-control" value="<?php echo h($q['type']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Section (Int)</label>
                    <input type="number" name="section" class="form-control" value="<?php echo h($q['section']); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Explanation (HTML Allowed)</label>
                <textarea name="explanation" class="form-control" rows="2"><?php echo h($q['explanation']); ?></textarea>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</form>

<?php include 'templates/footer.php'; ?>
