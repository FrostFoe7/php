<?php
require_once __DIR__ . '/includes/session_check.php';

$file_uuid = trim($_GET['uuid'] ?? '');

if ($file_uuid === '' || !is_numeric($file_uuid) || strlen($file_uuid) != 10) {
    $_SESSION['message'] = "Invalid file UUID format.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

// Look up internal file_id from UUID
$stmt = $GLOBALS['conn']->prepare("SELECT id FROM csv_files WHERE file_uuid = ?");
$stmt->bind_param("s", $file_uuid);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['message'] = "File not found.";
    $_SESSION['message_type'] = "danger";
    header("Location: index.php");
    exit;
}

$file_data = $result->fetch_assoc();
$file_id = $file_data['id'];
$stmt->close();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $questions = $_POST['questions'] ?? [];
    $new_data = [];

    foreach ($questions as $index => $question) {
        if (isset($question['delete']) && $question['delete'] == '1') {
            continue;
        }

        $new_data[] = [
            'questions' => $question['question'] ?? '',
            'option1' => $question['option1'] ?? '',
            'option2' => $question['option2'] ?? '',
            'option3' => $question['option3'] ?? '',
            'option4' => $question['option4'] ?? '',
            'option5' => $question['option5'] ?? '',
            'answer' => $question['answer'] ?? '',
            'explanation' => $question['explanation'] ?? '',
            'type' => $question['type'] ?? '',
            'section' => $question['section'] ?? ''
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
    header("Location: edit.php?uuid=" . $file_uuid);
    exit;
}

$stmt = $conn->prepare("SELECT filename, json_text, file_uuid FROM csv_files WHERE id = ?");
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

include_once __DIR__ . '/templates/header.php';
?>

<style>
    .question-counter {
        display: inline-block;
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 0.5rem 1rem;
        border-radius: 2rem;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }

    .form-controls {
        position: sticky;
        top: 0;
        background: white;
        padding: 1rem;
        border-bottom: 1px solid #dee2e6;
        display: flex;
        gap: 0.5rem;
        flex-wrap: wrap;
        z-index: 100;
        margin: -1rem -1rem 1rem -1rem;
    }

    @media (max-width: 576px) {
        .form-controls {
            flex-direction: column;
        }

        .form-controls .btn {
            width: 100%;
        }

        .question-counter {
            width: 100%;
            text-align: center;
        }
    }
</style>

<div class="mb-4">
    <h2 class="mb-2"><i class="bi bi-pencil-square"></i> Edit: <?php echo htmlspecialchars(substr($file['filename'], 0, 40)); ?></h2>
    <div>
        <span class="question-counter"><i class="bi bi-file-earmark-text"></i> <?php echo count($questions); ?> Questions</span>
        <span class="question-counter" style="margin-left: 0.5rem; background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);"><i class="bi bi-key"></i> UUID: <?php echo htmlspecialchars($file['file_uuid'] ?? 'N/A'); ?></span>
    </div>
</div>

<form id="editForm" action="edit.php?uuid=<?php echo $file_uuid; ?>" method="post">
    <div class="form-controls">
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back</a>
        <button type="button" class="btn btn-primary" onclick="addQuestion()"><i class="bi bi-plus-circle"></i> Add Question</button>
        <button type="submit" class="btn btn-success ms-auto"><i class="bi bi-save"></i> Save All Changes</button>
    </div>

    <div id="questions-container">
        <?php if (!empty($questions)): ?>
            <?php foreach ($questions as $index => $q): ?>
                <div class="card mb-3 card-question" id="question-card-<?php echo $index; ?>">
                    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span><i class="bi bi-question-circle"></i> Question #<?php echo $index + 1; ?></span>
                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion(<?php echo $index; ?>)"><i class="bi bi-trash"></i> Delete</button>
                    </div>
                    <div class="card-body">
                        <input type="hidden" name="questions[<?php echo $index; ?>][delete]" id="delete-flag-<?php echo $index; ?>" value="0">
                        
                        <div class="mb-3">
                            <label class="form-label"><strong>Question Text</strong></label>
                            <textarea class="form-control" name="questions[<?php echo $index; ?>][question]" rows="3"><?php echo htmlspecialchars($q['questions'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Option A</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][option1]" value="<?php echo htmlspecialchars($q['option1'] ?? ''); ?>" placeholder="First option">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Option B</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][option2]" value="<?php echo htmlspecialchars($q['option2'] ?? ''); ?>" placeholder="Second option">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Option C</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][option3]" value="<?php echo htmlspecialchars($q['option3'] ?? ''); ?>" placeholder="Third option">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Option D</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][option4]" value="<?php echo htmlspecialchars($q['option4'] ?? ''); ?>" placeholder="Fourth option">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Option E (Optional)</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][option5]" value="<?php echo htmlspecialchars($q['option5'] ?? ''); ?>" placeholder="Fifth option">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label"><strong>Correct Answer</strong></label>
                                <select class="form-select" name="questions[<?php echo $index; ?>][answer]">
                                    <option value="">Select answer</option>
                                    <option value="A" <?php echo (isset($q['answer']) && $q['answer'] == 'A') ? 'selected' : ''; ?>>Option A</option>
                                    <option value="B" <?php echo (isset($q['answer']) && $q['answer'] == 'B') ? 'selected' : ''; ?>>Option B</option>
                                    <option value="C" <?php echo (isset($q['answer']) && $q['answer'] == 'C') ? 'selected' : ''; ?>>Option C</option>
                                    <option value="D" <?php echo (isset($q['answer']) && $q['answer'] == 'D') ? 'selected' : ''; ?>>Option D</option>
                                    <option value="E" <?php echo (isset($q['answer']) && $q['answer'] == 'E') ? 'selected' : ''; ?>>Option E</option>
                                </select>
                            </div>
                        </div>

                        <hr>

                        <div class="mb-3">
                            <label class="form-label">Explanation / Hint</label>
                            <textarea class="form-control" name="questions[<?php echo $index; ?>][explanation]" rows="2" placeholder="Explain the answer..."><?php echo htmlspecialchars($q['explanation'] ?? ''); ?></textarea>
                        </div>

                        <div class="row">
                            <div class="col-12 col-md-6">
                                <label class="form-label">Type</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][type]" value="<?php echo htmlspecialchars($q['type'] ?? ''); ?>" placeholder="e.g., MCQ, Fill-in">
                            </div>
                            <div class="col-12 col-md-6">
                                <label class="form-label">Section / Chapter</label>
                                <input type="text" class="form-control" name="questions[<?php echo $index; ?>][section]" value="<?php echo htmlspecialchars($q['section'] ?? ''); ?>" placeholder="e.g., Chapter 1">
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-info">
                <i class="bi bi-info-circle"></i> No questions found in this file. Click "Add Question" to create one.
            </div>
        <?php endif; ?>
    </div>

    <div class="mt-4 d-flex gap-2 flex-wrap">
        <a href="index.php" class="btn btn-secondary"><i class="bi bi-arrow-left"></i> Back Without Saving</a>
        <button type="button" class="btn btn-primary" onclick="addQuestion()"><i class="bi bi-plus-circle"></i> Add Another Question</button>
        <button type="submit" class="btn btn-success ms-auto"><i class="bi bi-save"></i> Save All Changes</button>
    </div>
</form>

<script>
let questionIndex = <?php echo count($questions); ?>;

function deleteQuestion(index) {
    if (confirm('Are you sure you want to delete this question?')) {
        const card = document.getElementById('question-card-' + index);
        const deleteFlag = document.getElementById('delete-flag-' + index);
        if (card) {
            card.style.opacity = '0.5';
            card.style.textDecoration = 'line-through';
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
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <span><i class="bi bi-question-circle"></i> New Question #${questionIndex + 1}</span>
            <button type="button" class="btn btn-sm btn-danger" onclick="deleteQuestion(${questionIndex})"><i class="bi bi-trash"></i> Delete</button>
        </div>
        <div class="card-body">
            <input type="hidden" name="questions[${questionIndex}][delete]" id="delete-flag-${questionIndex}" value="0">
            
            <div class="mb-3">
                <label class="form-label"><strong>Question Text</strong></label>
                <textarea class="form-control" name="questions[${questionIndex}][question]" rows="3"></textarea>
            </div>

            <div class="row">
                <div class="col-12 col-md-6">
                    <label class="form-label">Option A</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][option1]" placeholder="First option">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Option B</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][option2]" placeholder="Second option">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Option C</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][option3]" placeholder="Third option">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Option D</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][option4]" placeholder="Fourth option">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Option E (Optional)</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][option5]" placeholder="Fifth option">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label"><strong>Correct Answer</strong></label>
                    <select class="form-select" name="questions[${questionIndex}][answer]">
                        <option value="">Select answer</option>
                        <option value="A">Option A</option>
                        <option value="B">Option B</option>
                        <option value="C">Option C</option>
                        <option value="D">Option D</option>
                        <option value="E">Option E</option>
                    </select>
                </div>
            </div>

            <hr>

            <div class="mb-3">
                <label class="form-label">Explanation / Hint</label>
                <textarea class="form-control" name="questions[${questionIndex}][explanation]" rows="2" placeholder="Explain the answer..."></textarea>
            </div>

            <div class="row">
                <div class="col-12 col-md-6">
                    <label class="form-label">Type</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][type]" placeholder="e.g., MCQ, Fill-in">
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label">Section / Chapter</label>
                    <input type="text" class="form-control" name="questions[${questionIndex}][section]" placeholder="e.g., Chapter 1">
                </div>
            </div>
        </div>
    `;
    container.appendChild(newCard);
    questionIndex++;
    // Scroll to new question
    newCard.scrollIntoView({ behavior: 'smooth' });
}
</script>

<?php
include_once __DIR__ . '/templates/footer.php';
$conn->close();
?>
