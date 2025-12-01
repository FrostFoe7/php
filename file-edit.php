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
    <div class="w-100">
        <label for="filename" class="form-label">File Name</label>
        <input type="text" id="filename" name="filename" class="form-control form-control-lg" value="<?php echo h($file['original_filename']); ?>">
    </div>
    <div class="ms-3 d-flex flex-shrink-0" style="gap: 0.5rem;">
        <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-secondary">Cancel</a>
        <button type="submit" form="editForm" class="btn btn-primary">Save All</button>
    </div>
</div>

<div class="row mb-3">
    <div class="col">
        <div class="btn-group w-100" role="group">
            <a href="add-question.php?id=<?php echo $file['id']; ?>" class="btn btn-outline-success">Add Question</a>
            <a href="file-upload.php?merge=<?php echo $file['id']; ?>" class="btn btn-outline-info">Merge CSV</a>
            <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-outline-secondary">Preview File</a>
        </div>
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
                    <label class="form-label">Section</label>
                    <select name="questions[<?php echo $q['id']; ?>][section]" class="form-select">
                        <option value="0" <?php echo $q['section'] === '0' ? 'selected' : ''; ?>>-- Select --</option>
                        <option value="p" <?php echo $q['section'] === 'p' ? 'selected' : ''; ?>>Physics (P)</option>
                        <option value="c" <?php echo $q['section'] === 'c' ? 'selected' : ''; ?>>Chemistry (C)</option>
                        <option value="m" <?php echo $q['section'] === 'm' ? 'selected' : ''; ?>>Math (M)</option>
                        <option value="b" <?php echo $q['section'] === 'b' ? 'selected' : ''; ?>>Biology (B)</option>
                        <option value="bm" <?php echo $q['section'] === 'bm' ? 'selected' : ''; ?>>Bio + Math (BM)</option>
                        <option value="bn" <?php echo $q['section'] === 'bn' ? 'selected' : ''; ?>>Bio + Non-Bio (BN)</option>
                        <option value="e" <?php echo $q['section'] === 'e' ? 'selected' : ''; ?>>English (E)</option>
                        <option value="i" <?php echo $q['section'] === 'i' ? 'selected' : ''; ?>>ICT (I)</option>
                        <option value="gk" <?php echo $q['section'] === 'gk' ? 'selected' : ''; ?>>General Knowledge (GK)</option>
                        <option value="iq" <?php echo $q['section'] === 'iq' ? 'selected' : ''; ?>>IQ Test (IQ)</option>
                    </select>
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

<script>
// AJAX fallback to bypass LiteSpeed/ModSecurity 403 on large POST bodies
// Intercepts the normal form submission and sends JSON to file-save.php
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('editForm');
    if (!form) return;

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.textContent = 'Saving...';
        }

        const csrf = form.querySelector('input[name="csrf_token"]').value;
        const filenameInput = document.getElementById('filename');
        const questionCards = form.querySelectorAll('.card');
        
        const data = { 
            file_id: '<?php echo $file['id']; ?>', 
            csrf_token: csrf, 
            original_filename: filenameInput ? filenameInput.value : '<?php echo h($file['original_filename']); ?>',
            questions: {} 
        };

        questionCards.forEach(card => {
            const header = card.querySelector('.card-header');
            if (!header) return;
            
            // Extract QID from a more robust source if possible
            const idHolder = header.textContent.match(/ID:\s*([0-9a-fA-F-]+)/);
            if (!idHolder) return;
            const qid = idHolder[1];

            const scope = card;
            const getVal = sel => {
                const el = scope.querySelector(sel);
                return el ? el.value : '';
            };

            data.questions[qid] = {
                question_text: getVal(`textarea[name="questions[${qid}][question_text]"]`),
                option1: getVal(`textarea[name="questions[${qid}][option1]"]`),
                option2: getVal(`textarea[name="questions[${qid}][option2]"]`),
                option3: getVal(`textarea[name="questions[${qid}][option3]"]`),
                option4: getVal(`textarea[name="questions[${qid}][option4]"]`),
                option5: getVal(`textarea[name="questions[${qid}][option5]"]`),
                answer: getVal(`input[name="questions[${qid}][answer]"]`),
                explanation: getVal(`textarea[name="questions[${qid}][explanation]"]`),
                type: getVal(`input[name="questions[${qid}][type]"]`),
                section: getVal(`select[name="questions[${qid}][section]"]`)
            };
        });

        fetch('file-save.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(data)
        })
        .then(r => r.text())
        .then(txt => {
            let json;
            try { json = JSON.parse(txt); } catch { json = { error: 'Invalid JSON response', raw: txt }; }
            
            if (json.success) {
                showNotice('Saved Successfully (' + (json.updated_questions || 0) + ' questions, file name ' + (json.file_renamed ? 'updated' : 'unchanged') + ')', 'success');
                if (json.file_renamed && filenameInput) {
                    // Update the title and input to reflect the new name
                    document.querySelector('h2').textContent = 'Edit: ' + filenameInput.value;
                }
            } else {
                showNotice('Save failed: ' + (json.error || 'Unknown error'), 'danger');
            }
        })
        .catch(err => {
            showNotice('Network error: ' + err.message, 'danger');
        })
        .finally(() => {
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Save All Changes';
            }
        });
    });

    function showNotice(message, type) {
        const div = document.createElement('div');
        div.className = 'alert alert-' + type + ' position-fixed top-0 end-0 m-3 shadow';
        div.style.zIndex = 2000;
        div.textContent = message;
        document.body.appendChild(div);
        setTimeout(() => div.remove(), 4000);
    }
});
</script>
