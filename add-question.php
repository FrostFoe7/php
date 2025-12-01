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

    $uploadedImages = [];

    try {
        $pdo->beginTransaction();

        $question_image = uploadImageFromInput('question_image');
        if ($question_image) {
            $uploadedImages[] = $question_image;
        }

        $explanation_image = uploadImageFromInput('explanation_image');
        if ($explanation_image) {
            $uploadedImages[] = $explanation_image;
        }

        // Get current max order_index
        $max_stmt = $pdo->prepare("SELECT MAX(order_index) as max_index FROM questions WHERE file_id = ?");
        $max_stmt->execute([$id]);
        $max_result = $max_stmt->fetch();
        $next_order_index = ($max_result['max_index'] ?? -1) + 1;

        $q_stmt = $pdo->prepare("INSERT INTO questions 
            (id, file_id, question_text, option1, option2, option3, option4, option5, answer, explanation, question_image, explanation_image, type, section, order_index) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $q_stmt->execute([
            uuidv4(),
            $id,
            $_POST['question_text'] ?? '',
            $_POST['option1'] ?? '',
            $_POST['option2'] ?? '',
            $_POST['option3'] ?? '',
            $_POST['option4'] ?? '',
            $_POST['option5'] ?? '',
            $_POST['answer'] ?? '',
            $_POST['explanation'] ?? '',
            $question_image,
            $explanation_image,
            (int)($_POST['type'] ?? 0),
            $_POST['section'] ?? '0',
            $next_order_index
        ]);

        // Update total_questions count
        $count_stmt = $pdo->prepare("UPDATE files SET total_questions = (SELECT COUNT(*) FROM questions WHERE file_id = ?) WHERE id = ?");
        $count_stmt->execute([$id, $id]);

        $pdo->commit();
        $success = "Question added successfully!";

    } catch (Exception $e) {
        $pdo->rollBack();
        foreach ($uploadedImages as $filename) {
            deleteUploadedImage($filename);
        }
        $error = "Error adding question: " . $e->getMessage();
    }
}
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Add New Question to <?php echo h($file['display_name'] ?? $file['original_filename']); ?></h2>
    <div>
        <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-secondary">Back to File</a>
    </div>
</div>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo h($success); ?></div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo h($error); ?></div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?php echo csrfInput(); ?>
            
            <div class="mb-4">
                <label class="form-label"><strong>Question Text *</strong></label>
                <textarea name="question_text" class="form-control" rows="4" placeholder="Enter the question text (HTML allowed)" required></textarea>
            </div>
            
            <h5 class="mb-3">Options</h5>
            <div class="row">
                <?php for($i=1; $i<=5; $i++): ?>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Option <?php echo $i; ?></label>
                    <textarea name="option<?php echo $i; ?>" class="form-control" rows="2" placeholder="Option <?php echo $i; ?> text"></textarea>
                </div>
                <?php endfor; ?>
            </div>
            
            <hr>
            
            <h5 class="mb-3">Additional Details</h5>
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Answer *</label>
                    <input type="text" name="answer" class="form-control" placeholder="e.g., 1, 2, 3, 4, or 5" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Type</label>
                    <input type="number" name="type" class="form-control" value="0" placeholder="0">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Section</label>
                    <select name="section" class="form-select">
                        <option value="0">-- Select --</option>
                        <option value="p">Physics (P)</option>
                        <option value="c">Chemistry (C)</option>
                        <option value="m">Math (M)</option>
                        <option value="b">Biology (B)</option>
                        <option value="bm">Bio + Math (BM)</option>
                        <option value="bn">Bio + Non-Bio (BN)</option>
                        <option value="e">English (E)</option>
                        <option value="i">ICT (I)</option>
                        <option value="gk">General Knowledge (GK)</option>
                        <option value="iq">IQ Test (IQ)</option>
                    </select>
                </div>
            </div>
            
            <div class="mb-4">
                <label class="form-label">Explanation (Optional)</label>
                <textarea name="explanation" class="form-control" rows="3" placeholder="Explanation for this question (HTML allowed)"></textarea>
            </div>

            <div class="row">
                <div class="col-md-6 mb-3">
                    <label class="form-label">Question Image (JPG/PNG, &le;100KB)</label>
                    <input type="file" name="question_image" accept="image/jpeg,image/png" class="form-control">
                    <div class="form-text">If provided, the image will auto-fit to the screen when displayed.</div>
                </div>
                <div class="col-md-6 mb-3">
                    <label class="form-label">Explanation Image (Optional)</label>
                    <input type="file" name="explanation_image" accept="image/jpeg,image/png" class="form-control">
                    <div class="form-text">Upload an image to accompany the explanation (optional).</div>
                </div>
            </div>
            
            <div class="d-grid gap-2">
                <button type="submit" class="btn btn-success btn-lg">Add Question</button>
                <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
