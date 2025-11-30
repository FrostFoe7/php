<?php
require_once 'includes/bootstrap.php';
requireLogin();

$error = '';
$success = '';

// Handle create category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    verifyCsrfToken($_POST['csrf_token'] ?? '');
    
    if ($_POST['action'] === 'create_category') {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $color = trim($_POST['color'] ?? '#007bff');
        
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO categories (id, name, description, color) VALUES (?, ?, ?, ?)");
                $stmt->execute([uuidv4(), $name, $description, $color]);
                $success = "Category created successfully!";
            } catch (Exception $e) {
                if (strpos($e->getMessage(), 'Duplicate') !== false) {
                    $error = "A category with this name already exists.";
                } else {
                    $error = "Error creating category: " . $e->getMessage();
                }
            }
        } else {
            $error = "Category name is required.";
        }
    } elseif ($_POST['action'] === 'delete_category') {
        $cat_id = $_POST['category_id'] ?? '';
        try {
            $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$cat_id]);
            $success = "Category deleted successfully!";
        } catch (Exception $e) {
            $error = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Get all categories
$stmt = $pdo->query("SELECT c.*, COUNT(f.id) as file_count FROM categories c 
                     LEFT JOIN files f ON f.category_id = c.id 
                     GROUP BY c.id 
                     ORDER BY c.name ASC");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<?php include 'templates/header.php'; ?>
<?php include 'templates/nav.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h2>Manage Categories</h2>
    <a href="index.php" class="btn btn-secondary">Back to Question Banks</a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger"><?php echo h($error); ?></div>
<?php endif; ?>
<?php if ($success): ?>
    <div class="alert alert-success"><?php echo h($success); ?></div>
<?php endif; ?>

<!-- Create New Category Form -->
<div class="card mb-4">
    <div class="card-header">
        <h5>Create New Category</h5>
    </div>
    <div class="card-body">
        <form method="post">
            <?php echo csrfInput(); ?>
            <input type="hidden" name="action" value="create_category">
            
            <div class="row">
                <div class="col-md-4 mb-3">
                    <label class="form-label">Category Name *</label>
                    <input type="text" name="name" class="form-control" placeholder="e.g., Math, Science" required>
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">Color</label>
                    <input type="color" name="color" class="form-control" value="#007bff">
                </div>
                <div class="col-md-4 mb-3">
                    <label class="form-label">&nbsp;</label>
                    <button type="submit" class="btn btn-success form-control">Create Category</button>
                </div>
            </div>
            
            <div class="mb-3">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-control" rows="2" placeholder="Optional description"></textarea>
            </div>
        </form>
    </div>
</div>

<!-- Categories List -->
<div class="row">
    <?php foreach ($categories as $cat): ?>
    <div class="col-md-6 mb-3">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h5 class="card-title">
                            <span class="badge" style="background-color: <?php echo h($cat['color']); ?>;">
                                <?php echo h($cat['name']); ?>
                            </span>
                        </h5>
                        <p class="card-text text-muted">
                            <?php echo h($cat['description'] ?? 'No description'); ?>
                        </p>
                        <small class="text-muted">
                            <?php echo (int)$cat['file_count']; ?> question bank<?php echo (int)$cat['file_count'] !== 1 ? 's' : ''; ?>
                        </small>
                    </div>
                    <form method="post" onsubmit="return confirm('Delete this category? Files will not be deleted.');">
                        <?php echo csrfInput(); ?>
                        <input type="hidden" name="action" value="delete_category">
                        <input type="hidden" name="category_id" value="<?php echo $cat['id']; ?>">
                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($categories)): ?>
    <div class="alert alert-info">No categories created yet. Create one to get started!</div>
<?php endif; ?>

<?php include 'templates/footer.php'; ?>
