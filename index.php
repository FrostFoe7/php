<?php
require_once 'includes/bootstrap.php';
requireLogin();

$error = '';
$success = '';

// Get categories
$cat_stmt = $pdo->query("SELECT * FROM categories ORDER BY name ASC");
$categories = $cat_stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle file name update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'rename_file') {
        verifyCsrfToken($_POST['csrf_token'] ?? '');
        $file_id = $_POST['file_id'] ?? '';
        $display_name = trim($_POST['display_name'] ?? '');
        $category_id = $_POST['category_id'] ?? null;
        
        if (!empty($display_name)) {
            try {
                $stmt = $pdo->prepare("UPDATE files SET display_name = ?, category_id = ? WHERE id = ?");
                $stmt->execute([$display_name, $category_id ?: null, $file_id]);
                $success = "File name and category updated successfully!";
            } catch (Exception $e) {
                $error = "Error updating file: " . $e->getMessage();
            }
        } else {
            $error = "Display name cannot be empty.";
        }
    }
}

// Get sorting parameter
$sort_by = $_GET['sort'] ?? 'uploaded';
$sort_order = $_GET['order'] ?? 'desc';

$query = "SELECT f.*, c.name as category_name, c.color as category_color 
          FROM files f 
          LEFT JOIN categories c ON f.category_id = c.id";

switch ($sort_by) {
    case 'category':
        $query .= " ORDER BY c.name " . ($sort_order === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'name':
        $query .= " ORDER BY COALESCE(f.display_name, f.original_filename) " . ($sort_order === 'asc' ? 'ASC' : 'DESC');
        break;
    case 'questions':
        $query .= " ORDER BY f.total_questions " . ($sort_order === 'asc' ? 'ASC' : 'DESC');
        break;
    default: // uploaded
        $query .= " ORDER BY f.uploaded_at " . ($sort_order === 'asc' ? 'ASC' : 'DESC');
}

$stmt = $pdo->query($query);
$files = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group files by category if sorting by category
$grouped_files = [];
if ($sort_by === 'category') {
    foreach ($files as $file) {
        $cat_name = $file['category_name'] ?? 'Uncategorized';
        if (!isset($grouped_files[$cat_name])) {
            $grouped_files[$cat_name] = [];
        }
        $grouped_files[$cat_name][] = $file;
    }
}
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Question Banks</h2>
    <div>
        <a href="file-upload.php" class="btn btn-success">Upload New CSV</a>
        <a href="categories.php" class="btn btn-info">Manage Categories</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo h($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo h($success); ?></div>
<?php endif; ?>

<!-- Sort Controls -->
<div class="card mb-3">
    <div class="card-body">
        <label class="form-label mb-0">Sort By:</label>
        <div class="btn-group" role="group">
            <a href="?sort=uploaded&order=desc" class="btn btn-sm <?php echo $sort_by === 'uploaded' && $sort_order === 'desc' ? 'btn-primary' : 'btn-outline-primary'; ?>">Recently Uploaded</a>
            <a href="?sort=name&order=asc" class="btn btn-sm <?php echo $sort_by === 'name' && $sort_order === 'asc' ? 'btn-primary' : 'btn-outline-primary'; ?>">Name A-Z</a>
            <a href="?sort=category&order=asc" class="btn btn-sm <?php echo $sort_by === 'category' ? 'btn-primary' : 'btn-outline-primary'; ?>">Category</a>
            <a href="?sort=questions&order=desc" class="btn btn-sm <?php echo $sort_by === 'questions' ? 'btn-primary' : 'btn-outline-primary'; ?>">Most Questions</a>
        </div>
    </div>
</div>

<?php if ($sort_by === 'category' && !empty($grouped_files)): ?>
    <!-- Grouped by Category View -->
    <?php foreach ($grouped_files as $category => $cat_files): ?>
        <div class="mb-4">
            <h4 class="border-bottom pb-2 mb-3">
                <?php if ($category !== 'Uncategorized'): ?>
                    <span class="badge" style="background-color: <?php echo h($cat_files[0]['category_color'] ?? '#007bff'); ?>;">
                        <?php echo h($category); ?>
                    </span>
                <?php else: ?>
                    <span class="text-muted"><?php echo h($category); ?></span>
                <?php endif; ?>
            </h4>
            
            <table class="table table-striped table-sm">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Questions</th>
                        <th>Uploaded</th>
                        <th>External ID</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cat_files as $file): ?>
                    <tr>
                        <td>
                            <strong><?php echo h($file['display_name'] ?? $file['original_filename']); ?></strong>
                            <br>
                            <small class="text-muted"><?php echo h($file['original_filename']); ?></small>
                        </td>
                        <td><?php echo h($file['total_questions']); ?></td>
                        <td><?php echo h($file['uploaded_at']); ?></td>
                        <td><?php echo h($file['external_id'] ?? '-'); ?></td>
                        <td>
                            <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-info">View</a>
                            <a href="file-edit.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                            <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal" onclick="fillEditModal('<?php echo $file['id']; ?>', <?php echo json_encode($categories); ?>)">Rename</button>
                            <a href="file-delete.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endforeach; ?>

<?php else: ?>
    <!-- Regular List View -->
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Name</th>
                <th>Category</th>
                <th>Questions</th>
                <th>Uploaded</th>
                <th>External ID</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($files as $file): ?>
            <tr>
                <td>
                    <strong><?php echo h($file['display_name'] ?? $file['original_filename']); ?></strong>
                    <br>
                    <small class="text-muted"><?php echo h($file['original_filename']); ?></small>
                </td>
                <td>
                    <?php if (!empty($file['category_name'])): ?>
                        <span class="badge" style="background-color: <?php echo h($file['category_color'] ?? '#007bff'); ?>;">
                            <?php echo h($file['category_name']); ?>
                        </span>
                    <?php else: ?>
                        <span class="text-muted">-</span>
                    <?php endif; ?>
                </td>
                <td><?php echo h($file['total_questions']); ?></td>
                <td><?php echo h($file['uploaded_at']); ?></td>
                <td><?php echo h($file['external_id'] ?? '-'); ?></td>
                <td>
                    <a href="file-view.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-info">View</a>
                    <a href="file-edit.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-warning">Edit</a>
                    <button class="btn btn-sm btn-secondary" data-bs-toggle="modal" data-bs-target="#editModal" onclick="fillEditModal('<?php echo $file['id']; ?>', <?php echo json_encode($categories); ?>)">Rename</button>
                    <a href="file-delete.php?id=<?php echo $file['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<!-- Rename/Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit File</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="post">
                <?php echo csrfInput(); ?>
                <input type="hidden" name="action" value="rename_file">
                <input type="hidden" name="file_id" id="modal_file_id" value="">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Display Name</label>
                        <input type="text" name="display_name" id="modal_display_name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select name="category_id" id="modal_category_id" class="form-select">
                            <option value="">-- Select Category --</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php include 'templates/footer.php'; ?>

<script>
let categoriesData = <?php echo json_encode($categories); ?>;

function fillEditModal(fileId, categories) {
    document.getElementById('modal_file_id').value = fileId;
    document.getElementById('modal_display_name').value = '';
    
    // Fetch current file data
    fetch('api/get-file.php?id=' + fileId)
        .then(r => r.json())
        .then(data => {
            document.getElementById('modal_display_name').value = data.display_name || data.original_filename;
            
            // Fill category select
            const select = document.getElementById('modal_category_id');
            select.innerHTML = '<option value="">-- Select Category --</option>';
            categories.forEach(cat => {
                const option = document.createElement('option');
                option.value = cat.id;
                option.textContent = cat.name;
                if (cat.id === data.category_id) {
                    option.selected = true;
                }
                select.appendChild(option);
            });
        })
        .catch(err => console.error('Error:', err));
}
</script>

<?php include 'templates/footer.php'; ?>
