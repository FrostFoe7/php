/* FILE: public_html/public/edit.php */
<?php
require_once __DIR__ . '/../includes/session_check.php';

// --- VALIDATE FILE ID ---
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['message'] = "Invalid file ID.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}
$file_id = (int)$_GET['id'];

// --- HANDLE FORM SUBMISSION (POST REQUEST) ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $questions = $_POST['questions'] ?? [];
    $new_data = [];

    foreach ($questions as $index => $question) {
        // If a question is marked for deletion, just skip it
        if (isset($question['delete']) && $question['delete'] == '1') {
            continue;
        }

        // Rebuild the associative array for each question
        $new_data[] = [
            'Question' => $question['question'] ?? '',
            'Option 1' => $question['option1'] ?? '',
            'Option 2' => $question['option2'] ?? '',
            'Option 3' => $question['option3'] ?? '',
            'Option 4' => $question['option4'] ?? '',
            'Option 5' => $question['option5'] ?? '',
            'Answer' => $question['answer'] ?? '',
            'Explanation' => $question['explanation'] ?? '',
            'Type' => $question['type'] ?? '',
            'Section' => $question['section'] ?? ''
        ];
    }

    $row_count = count($new_data);
    $json_text = json_encode($new_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);

    if (json_last_error() !== JSON_ERROR_NONE) {
        $_SESSION['message'] = "Error encoding data to JSON: " . json_last_error_msg();
        $_SESSION['message_type'] = "danger";
    } else {
        $stmt = $conn->prepare("UPDATE csv_files SET json_text = ?, row_count = ? WHERE id = ?");
        $stmt->bind_param("sii", $json_text, $row_count, $file_id);

        if ($stmt->execute()) {
            $_SESSION['message'] = "File updated successfully.";
            $_SESSION['message_type'] = "success";
        } else {
            $_SESSION['message'] = "Database error on update: " . $stmt->error;
            $_SESSION['message_type'] = "danger";
        }
        $stmt->close();
    }
    // Redirect back to the edit page to show changes
    header("Location: edit.php?id=" . $file_id);
    exit;
}


// --- FETCH DATA FOR DISPLAY (GET REQUEST) ---
$stmt = $conn->prepare("SELECT filename, json_text FROM csv_files WHERE id = ?");
$stmt->bind_param("i", $file_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "File not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

$file = $result->fetch_assoc();
$stmt->close();

$questions = json_decode($file['json_text'], true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("Error decoding JSON: " . json_last_error_msg());
}

include_once __DIR__ . '/../templates/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h2><i class="bi bi-pencil-square"></i> Edit File: <?php echo htmlspecialchars($file['filename']); ?></h2>
    <div>
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="submit" form="editForm" class="btn btn-success"><i class="bi bi-save"></i> Save All Changes</button>
    </div>
</div>

<form id="editForm" action="edit.php?id=<?php echo $file_id; ?>" method="post">
    <div id="questions-container">
        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $index => $q): ?>
                <div class="card mb-3 card-question" id="question-card-<?php echo $index; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>Question #<?php echo $index + 1; ?></span>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion(<?php echo $index; ?>)"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="questions[<?php echo $index; ?>][delete]" id="delete-flag-<?php echo $index; ?>" value="0">
                        <div class="mb-3">
                            <label class="form-label">Question</label>
                            <textarea class="form-control" name="questions[<?php echo $index; ?>][question]"><?php echo htmlspecialchars($q['Question'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label">Option 1</label><input type="text" class="form-control" name="questions[<?php echo $index; ?>][option1]" value="<?php echo htmlspecialchars($q['Option 1'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Option 2</label><input type="text" class="form-control" name="questions[<?php echo $index; ?>][option2]" value="<?php echo htmlspecialchars($q['Option 2'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Option 3</label><input type="text" class="form-control" name="questions[<?php echo $index; ?>][option3]" value="<?php echo htmlspecialchars($q['Option 3'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Option 4</label><input type="text" class="form-control" name="questions[<?php echo $index; ?>][option4]" value="<?php echo htmlspecialchars($q['Option 4'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Option 5</label><input type="text" class="form-control" name="questions[<?php echo $index; ?>][option5]" value="<?php echo htmlspecialchars($q['Option 5'] ?? ''); ?>"></div>
                            <div class="col-md-6">
                                <label class="form-label">Correct Answer</label>
                                <select class="form-select" name="questions[<?php echo $index; ?>][answer]">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <option value="<?php echo $i; ?>" <?php echo (isset($q['Answer']) && $q['Answer'] == $i) ? 'selected' : ''; ?>>Option <?php echo $i; ?></option>
                                    <?php endfor; ?>
                                </select>
                            </div>
                        </div>
                        <div class="mb-3 mt-3">
                            <label class="form-label">Explanation (HTML allowed)</label>
                            <textarea class="form-control" name="questions[<?php echo $index; ?>][explanation]" rows="3"><?php echo htmlspecialchars($q['Explanation'] ?? ''); ?></textarea>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><label class="form-label">Type</label><input type="text" class="form-control" name="questions[<?php echo $index; ?>][type]" value="<?php echo htmlspecialchars($q['Type'] ?? ''); ?>"></div>
                            <div class="col-md-6"><label class="form-label">Section</label><input type="text" class="form-control" name="questions[<?php echo $index; ?>][section]" value="<?php echo htmlspecialchars($q['Section'] ?? ''); ?>"></div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">No questions found in this file. You can add one below.</div>
        <?php endif; ?>
    </div>
    <button type="button" class="btn btn-primary mt-3" onclick="addQuestion()"><i class="bi bi-plus-circle"></i> Add New Question</button>
    <button type="submit" form="editForm" class="btn btn-success mt-3 float-end"><i class="bi bi-save"></i> Save All Changes</button>
</form>

<script>
let questionIndex = <?php echo count($questions); ?>;

function deleteQuestion(index) {
    if (confirm('Are you sure you want to delete this question? This cannot be undone until you save.')) {
        const card = document.getElementById('question-card-' + index);
        const deleteFlag = document.getElementById('delete-flag-' + index);
        if (card) {
            card.style.display = 'none';
            // Mark for deletion on the server
            if(deleteFlag) {
                deleteFlag.value = '1';
            }
        }
    }
}

function addQuestion() {
    const container = document.getElementById('questions-container');
    const newCard = document.createElement('div');
    newCard.className = 'card mb-3 card-question';
    newCard.id = 'question-card-' + questionIndex;
    newCard.innerHTML = `
        <div class="card-header d-flex justify-content-between align-items-center">
            <span>New Question #${questionIndex + 1}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion(${questionIndex})"><i class="bi bi-trash"></i> Delete</button>
        </div>
        <div class="card-body">
            <input type="hidden" name="questions[${questionIndex}][delete]" id="delete-flag-${questionIndex}" value="0">
            <div class="mb-3">
                <label class="form-label">Question</label>
                <textarea class="form-control" name="questions[${questionIndex}][question]"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6"><label class="form-label">Option 1</label><input type="text" class="form-control" name="questions[${questionIndex}][option1]" value=""></div>
                <div class="col-md-6"><label class="form-label">Option 2</label><input type="text" class="form-control" name="questions[${questionIndex}][option2]" value=""></div>
                <div class="col-md-6"><label class="form-label">Option 3</label><input type="text" class="form-control" name="questions[${questionIndex}][option3]" value=""></div>
                <div class="col-md-6"><label class="form-label">Option 4</label><input type="text" class="form-control" name="questions[${questionIndex}][option4]" value=""></div>
                <div class="col-md-6"><label class="form-label">Option 5</label><input type="text" class="form-control" name="questions[${questionIndex}][option5]" value=""></div>
                <div class="col-md-6">
                    <label class="form-label">Correct Answer</label>
                    <select class="form-select" name="questions[${questionIndex}][answer]">
                        <option value="1" selected>Option 1</option>
                        <option value="2">Option 2</option>
                        <option value="3">Option 3</option>
                        <option value="4">Option 4</option>
                        <option value="5">Option 5</option>
                    </select>
                </div>
            </div>
            <div class="mb-3 mt-3">
                <label class="form-label">Explanation (HTML allowed)</label>
                <textarea class="form-control" name="questions[${questionIndex}][explanation]" rows="3"></textarea>
            </div>
            <div class="row">
                <div class="col-md-6"><label class="form-label">Type</label><input type="text" class="form-control" name="questions[${questionIndex}][type]" value=""></div>
                <div class="col-md-6"><label class="form-label">Section</label><input type="text" class="form-control" name="questions[${questionIndex}][section]" value=""></div>
            </div>
        </div>
    `;
    container.appendChild(newCard);
    questionIndex++;
}
</script>

<?php
include_once __DIR__ . '/../templates/footer.php';
$conn->close();
?>
