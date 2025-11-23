<?php
require_once 'includes/bootstrap.php';
requireLogin();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['csv_file'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');

    $file = $_FILES['csv_file'];
    if ($file['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if ($ext !== 'csv') {
            $error = "Only CSV files are allowed.";
        } else {
            try {
                $questions = parseCSV($file['tmp_name']);
                
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
                    $success = "File uploaded successfully! " . count($questions) . " questions imported.";
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
            <button type="submit" class="btn btn-primary">Upload & Process</button>
        </form>
    </div>
</div>

<?php include 'templates/footer.php'; ?>
