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

    $uploadedImages = [];
    $question_image = $q['question_image'];
    $explanation_image = $q['explanation_image'];
    $removeQuestionImage = !empty($_POST['remove_question_image']);
    $removeExplanationImage = !empty($_POST['remove_explanation_image']);

    try {
        $pdo->beginTransaction();

        $questionFileError = $_FILES['question_image']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($questionFileError !== UPLOAD_ERR_NO_FILE) {
            $newQuestionImage = uploadImageFromInput('question_image');
            $uploadedImages[] = $newQuestionImage;
            if ($question_image) {
                deleteUploadedImage($question_image);
            }
            $question_image = $newQuestionImage;
        } elseif ($removeQuestionImage && $question_image) {
            deleteUploadedImage($question_image);
            $question_image = null;
        }

        $explanationFileError = $_FILES['explanation_image']['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($explanationFileError !== UPLOAD_ERR_NO_FILE) {
            $newExplanationImage = uploadImageFromInput('explanation_image');
            $uploadedImages[] = $newExplanationImage;
            if ($explanation_image) {
                deleteUploadedImage($explanation_image);
            }
            $explanation_image = $newExplanationImage;
        } elseif ($removeExplanationImage && $explanation_image) {
            deleteUploadedImage($explanation_image);
            $explanation_image = null;
        }

        $update_stmt = $pdo->prepare("UPDATE questions SET question_text = ?, option1 = ?, option2 = ?, option3 = ?, option4 = ?, option5 = ?, answer = ?, explanation = ?, question_image = ?, explanation_image = ?, type = ?, section = ? WHERE id = ?");
        $update_stmt->execute([
            $_POST['question_text'],
            $_POST['option1'],
            $_POST['option2'],
            $_POST['option3'],
            $_POST['option4'],
            $_POST['option5'],
            $_POST['answer'],
            $_POST['explanation'],
            $question_image,
            $explanation_image,
            (int)$_POST['type'],
            $_POST['section'],
            $id
        ]);

        $pdo->commit();

        // Refresh data
        $stmt->execute([$id]);
        $q = $stmt->fetch();

        $success = "Question updated successfully";
    } catch (Exception $e) {
        $pdo->rollBack();
        foreach ($uploadedImages as $filename) {
            deleteUploadedImage($filename);
        }
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

<form method="post" enctype="multipart/form-data">
    <?php echo csrfInput(); ?>
    
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Question Text (HTML Allowed)</label>
                <textarea name="question_text" class="form-control" rows="3"><?php echo h($q['question_text']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Question Image (Optional)</label>
                <?php if ($q['question_image']): ?>
                    <div class="image-preview mb-2">
                        <img src="<?php echo h(getUploadedImageUrl($q['question_image'])); ?>" alt="Question image">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="remove_question_image" id="removeQuestionImage" value="1">
                        <label class="form-check-label" for="removeQuestionImage">Remove current image</label>
                    </div>
                <?php endif; ?>
                <input type="file" name="question_image" accept="image/jpeg,image/png" class="form-control">
                <div class="form-text">Upload a new image to replace the current one (max 100KB).</div>
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
                    <label class="form-label">Section</label>
                    <select name="section" class="form-select">
                        <option value="0" <?php echo ($q['section'] ?? '0') === '0' ? 'selected' : ''; ?>>-- Select --</option>
                        <option value="p" <?php echo ($q['section'] ?? '') === 'p' ? 'selected' : ''; ?>>Physics (P)</option>
                        <option value="c" <?php echo ($q['section'] ?? '') === 'c' ? 'selected' : ''; ?>>Chemistry (C)</option>
                        <option value="m" <?php echo ($q['section'] ?? '') === 'm' ? 'selected' : ''; ?>>Math (M)</option>
                        <option value="b" <?php echo ($q['section'] ?? '') === 'b' ? 'selected' : ''; ?>>Biology (B)</option>
                        <option value="bm" <?php echo ($q['section'] ?? '') === 'bm' ? 'selected' : ''; ?>>Bio + Math (BM)</option>
                        <option value="bn" <?php echo ($q['section'] ?? '') === 'bn' ? 'selected' : ''; ?>>Bio + Non-Bio (BN)</option>
                        <option value="e" <?php echo ($q['section'] ?? '') === 'e' ? 'selected' : ''; ?>>English (E)</option>
                        <option value="i" <?php echo ($q['section'] ?? '') === 'i' ? 'selected' : ''; ?>>ICT (I)</option>
                        <option value="gk" <?php echo ($q['section'] ?? '') === 'gk' ? 'selected' : ''; ?>>General Knowledge (GK)</option>
                        <option value="iq" <?php echo ($q['section'] ?? '') === 'iq' ? 'selected' : ''; ?>>IQ Test (IQ)</option>
                    </select>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">Explanation (HTML Allowed)</label>
                <textarea name="explanation" class="form-control" rows="2"><?php echo h($q['explanation']); ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Explanation Image (Optional)</label>
                <?php if ($q['explanation_image']): ?>
                    <div class="image-preview mb-2">
                        <img src="<?php echo h(getUploadedImageUrl($q['explanation_image'])); ?>" alt="Explanation image">
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" name="remove_explanation_image" id="removeExplanationImage" value="1">
                        <label class="form-check-label" for="removeExplanationImage">Remove current explanation image</label>
                    </div>
                <?php endif; ?>
                <input type="file" name="explanation_image" accept="image/jpeg,image/png" class="form-control">
                <div class="form-text">Attach an image that explains the question (max 100KB).</div>
            </div>
            
            <button type="submit" class="btn btn-primary">Save Changes</button>
        </div>
    </div>
</form>

<?php include 'templates/footer.php'; ?>
