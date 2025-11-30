<?php
require_once 'includes/bootstrap.php';
requireLogin();

$error = '';
$success = '';
$warning = '';
$answer_conversion_applied = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $file = $_FILES['csv_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $error = "Only CSV files are allowed.";
        } else {
            try {
                // Check if user wants to force convert answers
                $forceConvert = isset($_POST['convert_zero_indexed']) && $_POST['convert_zero_indexed'] === '1';
                
                $questions = parseCSV($file['tmp_name'], $forceConvert);
                
                // Check if conversion was applied
                if ($forceConvert) {
                    $warning = "Answer fields have been automatically converted from 0-indexed to 1-indexed format.";
                    $answer_conversion_applied = true;
                } else {
                    // Show info if auto-detection happened
                    $answer_conversion_applied = detectZeroIndexedAnswers($questions);
                    if ($answer_conversion_applied) {
                        $warning = "Auto-detected 0-indexed answers and converted them to 1-indexed format.";
                    }
                }
                
                if (empty($questions)) {
                    $error = "No valid questions found in CSV.";
                } else {
                    $pdo->beginTransaction();

                    $file_id = uuidv4();
                    $stmt = $pdo->prepare("INSERT INTO files (id, original_filename, total_questions) VALUES (?, ?, ?)");
                    $stmt->execute([$file_id, $file['name'], count($questions)]);

                    $q_stmt = $pdo->prepare("INSERT INTO questions (id, file_id, question_text, option1, option2, option3, option4, option5, answer, explanation, type, section, order_index) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

                    foreach ($questions as $index => $q) {
                        $q_stmt->execute([
                            uuidv4(),
                            $file_id,
                            $q['question_text'],
                            $q['option1'],
                            $q['option2'],
                            $q['option3'],
                            $q['option4'],
                            $q['option5'],
                            $q['answer'],
                            $q['explanation'],
                            (int)$q['type'],
                            (int)$q['section'],
                            $index
                        ]);
                    }

                    $pdo->commit();
                    $success = "File uploaded successfully! " . count($questions) . " questions imported." . 
                               ($answer_conversion_applied ? " Answer field conversion was applied." : "");
                }
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Error processing file: " . $e->getMessage();
            }
        }
    } else {
        $error = "Upload failed with error code " . $file['error'];
    }
}
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<h2>Upload CSV Question Bank</h2>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo h($error); ?></div>
<?php endif; ?>
<?php if ($warning): ?>
    <div class="alert alert-warning"><?php echo h($warning); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo h($success); ?></div>
<?php endif; ?>

<div class="card">
    <div class="card-body">
        <form method="post" enctype="multipart/form-data">
            <?php echo csrfInput(); ?>
            <div class="mb-3">
                <label class="form-label">Select CSV File</label>
                <input type="file" name="csv_file" class="form-control" accept=".csv" required>
                <div class="form-text">
                    Required columns: questions, option1, option2, option3, option4, option5, answer, explanation, type, section
                </div>
            </div>
            
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" name="convert_zero_indexed" value="1" class="form-check-input" id="convertZeroIndexed">
                    <label class="form-check-label" for="convertZeroIndexed">
                        <strong>Convert 0-indexed answers to 1-indexed</strong>
                        <div class="form-text">
                            Enable this if your CSV answers start from 0 (0=Option 1, 1=Option 2, etc.) 
                            and need to be converted to 1-indexed format (1=Option 1, 2=Option 2, etc.)
                            <br>
                            System will auto-detect this, but you can manually override with this checkbox.
                        </div>
                    </label>
                </div>
            </div>
            
            <button type="submit" class="btn btn-primary">Upload & Process</button>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
