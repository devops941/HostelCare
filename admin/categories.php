<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'Category Management';
$pdo = getDB();
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $name             = trim($_POST['category_name'] ?? '');
                $assigned_staff_id = $_POST['assigned_staff_id'] ?? '' ?: null;

                if (empty($name)) {
                    $message = 'Category name is required.';
                    $messageType = 'danger';
                } elseif (empty($assigned_staff_id)) {
                    $message = 'Please assign a staff member to this category.';
                    $messageType = 'danger';
                } else {
                    $pdo->prepare('INSERT INTO categories (name, assigned_staff_id) VALUES (?, ?)')
                        ->execute([$name, $assigned_staff_id]);
                    $message = 'Category added successfully.';
                    $messageType = 'success';
                }
            } elseif ($_POST['action'] === 'edit') {
                $category_id      = $_POST['category_id'] ?? '';
                $name             = trim($_POST['category_name'] ?? '');
                $assigned_staff_id = $_POST['assigned_staff_id'] ?? '' ?: null;

                if (empty($category_id) || empty($name)) {
                    $message = 'Category name is required.';
                    $messageType = 'danger';
                } else {
                    $pdo->prepare('UPDATE categories SET name = ?, assigned_staff_id = ? WHERE category_id = ?')
                        ->execute([$name, $assigned_staff_id, $category_id]);
                    $message = 'Category updated successfully.';
                    $messageType = 'success';
                }
            } elseif ($_POST['action'] === 'delete') {
                $category_id = $_POST['category_id'] ?? '';
                $check = $pdo->prepare('SELECT COUNT(*) as count FROM complaints WHERE category_id = ?');
                $check->execute([$category_id]);
                if ($check->fetch()['count'] > 0) {
                    $message = 'Cannot delete a category that has complaints.';
                    $messageType = 'danger';
                } else {
                    $pdo->prepare('DELETE FROM categories WHERE category_id = ?')->execute([$category_id]);
                    $message = 'Category deleted successfully.';
                    $messageType = 'success';
                }
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

$categories = $pdo->query('
    SELECT c.category_id, c.name, c.assigned_staff_id,
           u.name AS staff_name,
           COUNT(comp.complaint_id) AS complaint_count
    FROM categories c
    LEFT JOIN users u ON c.assigned_staff_id = u.user_id
    LEFT JOIN complaints comp ON c.category_id = comp.category_id
    GROUP BY c.category_id, c.name, c.assigned_staff_id, u.name
    ORDER BY c.name
')->fetchAll();

$staff = $pdo->query('SELECT user_id, name FROM users WHERE role = "staff" ORDER BY name')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header mb-4">
    <h4><i class="bi bi-tags me-2"></i>Category Management</h4>
    <p>Add, edit, or remove complaint categories and assign staff.</p>
</div>

<div class="row">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold bg-light">
                <i class="bi bi-list me-2"></i>Complaint Categories
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                <div class="alert alert-<?= $messageType ?> alert-dismissible fade show">
                    <?= htmlspecialchars($message) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Category Name</th>
                                <th>Assigned Staff</th>
                                <th>Complaints</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                            <tr><td colspan="4" class="text-center text-muted py-4">No categories found.</td></tr>
                            <?php else: ?>
                            <?php foreach ($categories as $cat): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($cat['name']) ?></strong></td>
                                <td>
                                    <?php if ($cat['staff_name']): ?>
                                    <span class="badge bg-success"><i class="bi bi-person me-1"></i><?= htmlspecialchars($cat['staff_name']) ?></span>
                                    <?php else: ?>
                                    <span class="text-muted small">— Unassigned —</span>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-info"><?= $cat['complaint_count'] ?></span></td>
                                <td>
                                    <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal"
                                        onclick="loadEditForm(<?= htmlspecialchars(json_encode($cat)) ?>)">
                                        <i class="bi bi-pencil"></i> Edit
                                    </button>
                                    <form method="POST" style="display:inline">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="category_id" value="<?= $cat['category_id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger"
                                            onclick="return confirm('Delete this category?')">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold bg-light">
                <i class="bi bi-plus-circle me-2"></i>Add New Category
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="category_name" name="category_name"
                            placeholder="e.g., Electrical, Plumbing" required>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_staff_id" class="form-label">Assign Staff <span class="text-danger">*</span></label>
                        <select class="form-select" id="assigned_staff_id" name="assigned_staff_id" required>
                            <option value="">-- Select Staff --</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-circle me-1"></i>Add Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_assigned_staff_id" class="form-label">Assign Staff <span class="text-danger">*</span></label>
                        <select class="form-select" id="edit_assigned_staff_id" name="assigned_staff_id">
                            <option value="">Select Staff</option>
                            <?php foreach ($staff as $s): ?>
                            <option value="<?= $s['user_id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-warning">Update Category</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.body.appendChild(document.getElementById('editModal'));
function loadEditForm(category) {
    document.getElementById('edit_category_id').value = category.category_id;
    document.getElementById('edit_category_name').value = category.name;
    document.getElementById('edit_assigned_staff_id').value = category.assigned_staff_id ?? '';
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
