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

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    if (isset($_POST['questions']) && is_array($_POST['questions'])) {
        try {
            $pdo->beginTransaction();
            $update_stmt = $pdo->prepare("UPDATE questions SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, option5 = ?, answer = ?, explanation = ?, type = ?, section = ? WHERE id = ? AND file_id = ?");
            
            foreach ($_POST['questions'] as $q_id => $data) {
                $update_stmt->execute([
                    $data['question_text'],
                    $data['option1'],
                    $data['option2'],
                    $data['option3'],
                    $data['option4'],
                    $data['option5'],
                    $data['answer'],
                    $data['explanation'],
                    (int)$data['type'],
                    (int)$data['section'],
                    $q_id,
                    $id
                ]);
            }
            $pdo->commit();
            $success = "Saved Successfully";
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Error saving: " . $e->getMessage();
        }
    }
}

$q_stmt = $pdo->prepare("SELECT * FROM questions WHERE file_id = ? ORDER BY order_index ASC");
$q_stmt->execute([$id]);
$questions = $q_stmt->fetchAll();
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3 sticky-top bg-white py-3 border-bottom">
    <h2>Edit: <?php echo h($file['original_filename']); ?></h2>
    <div>
        <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" form="editForm" class="btn btn-primary">Save All Changes</button>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo h($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo h($error); ?></div>
<?php endif; ?>

<form id="editForm" method="post">
    <?php echo csrfInput(); ?>
    
    <?php foreach ($questions as $q): ?>
    <div class="card mb-4 shadow-sm">
        <div class="card-header bg-light">
            <strong>Question #<?php echo $q['order_index'] + 1; ?></strong> (ID: <?php echo $q['id']; ?>)
        </div>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Question Text (HTML Allowed)</label>
                <textarea name="questions[<?php echo $q['id']; ?>][question_text]" class="form-control" rows="3"><?php echo h($q['question_text']); ?></textarea>
            </div>
            
            <div class="row">
                <?php for($i=1; $i<=5; $i++): ?>
                <div class="col-md-6 mb-2">
                    <label class="form-label">Option <?php echo $i; ?></label>
                    <textarea name="questions[<?php echo $q['id']; ?>][option<?php echo $i; ?>]" class="form-control" rows="1"><?php echo h($q['option'.$i]); ?></textarea>
                </div>
                <?php endfor; ?>
            </div>

            <div class="row mt-2">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Answer</label>
                    <input type="text" name="questions[<?php echo $q['id']; ?>][answer]" class="form-control" value="<?php echo h($q['answer']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Type (Int)</label>
                    <input type="number" name="questions[<?php echo $q['id']; ?>][type]" class="form-control" value="<?php echo h($q['type']); ?>">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Section (Int)</label>
                    <input type="number" name="questions[<?php echo $q['id']; ?>][section]" class="form-control" value="<?php echo h($q['section']); ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Explanation (HTML Allowed)</label>
                <textarea name="questions[<?php echo $q['id']; ?>][explanation]" class="form-control" rows="2"><?php echo h($q['explanation']); ?></textarea>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
    
    <div class="d-grid gap-2 mb-5">
        <button type="submit" class="btn btn-primary btn-lg">Save All Changes</button>
    </div>
</form>

<?php include 'templates/footer.php'; ?>
