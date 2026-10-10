<?php
/**
 * Category Management Page - Admin Panel
 * Hostel Complaint Tracking System
 * Handles CRUD operations for complaint categories
 */

// Include configuration and auth
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin role
requireRole('admin');

$pageTitle = 'Category Management';
$pdo = getDB();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $name = trim($_POST['category_name'] ?? '');
                $assigned_staff_id = $_POST['assigned_staff_id'] ?? '';

                if (empty($name) || empty($assigned_staff_id)) {
                    $message = 'Please fill in all fields';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare('INSERT INTO categories (name, assigned_staff_id) VALUES (?, ?)');
                    $stmt->execute([$name, $assigned_staff_id]);
                    $message = 'Category added successfully';
                    $messageType = 'success';
                }
            } elseif ($_POST['action'] === 'edit') {
                $category_id = $_POST['category_id'] ?? '';
                $name = trim($_POST['category_name'] ?? '');
                $assigned_staff_id = $_POST['assigned_staff_id'] ?? '';

                if (empty($category_id) || empty($name) || empty($assigned_staff_id)) {
                    $message = 'Please fill in all fields';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare('UPDATE categories SET name = ?, assigned_staff_id = ? WHERE category_id = ?');
                    $stmt->execute([$name, $assigned_staff_id, $category_id]);
                    $message = 'Category updated successfully';
                    $messageType = 'success';
                }
            } elseif ($_POST['action'] === 'delete') {
                $category_id = $_POST['category_id'] ?? '';

                // Check if category has complaints
                $check = $pdo->prepare('SELECT COUNT(*) as count FROM complaints WHERE category_id = ?');
                $check->execute([$category_id]);
                $result = $check->fetch();

                if ($result['count'] > 0) {
                    $message = 'Cannot delete category with assigned complaints';
                    $messageType = 'danger';
                } else {
                    $stmt = $pdo->prepare('DELETE FROM categories WHERE category_id = ?');
                    $stmt->execute([$category_id]);
                    $message = 'Category deleted successfully';
                    $messageType = 'success';
                }
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Fetch all categories with staff details and complaint count
$categories = $pdo->query('
    SELECT
        c.category_id,
        c.name,
        c.assigned_staff_id,
        u.name as staff_name,
        COUNT(comp.complaint_id) as complaint_count
    FROM categories c
    LEFT JOIN users u ON c.assigned_staff_id = u.user_id
    LEFT JOIN complaints comp ON c.category_id = comp.category_id
    GROUP BY c.category_id, c.name, c.assigned_staff_id, u.name
    ORDER BY c.name
')->fetchAll();

// Fetch all staff members (users with role='staff')
$staff = $pdo->query('
    SELECT user_id, name, email FROM users WHERE role = "staff" ORDER BY name
')->fetchAll();

// Calculate workload: count active (non-closed) complaints per staff via their categories
$workload = $pdo->query('
    SELECT
        u.user_id,
        u.name,
        COUNT(comp.complaint_id) as complaint_count
    FROM users u
    LEFT JOIN categories cat ON cat.assigned_staff_id = u.user_id
    LEFT JOIN complaints comp ON comp.category_id = cat.category_id
                              AND comp.status NOT IN ("resolved","closed")
    WHERE u.role = "staff"
    GROUP BY u.user_id, u.name
    ORDER BY complaint_count DESC
')->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0"><i class="bi bi-list"></i> Complaint Categories</h5>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
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
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">No categories found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                        <td><?php echo htmlspecialchars($cat['staff_name'] ?? '— Unassigned —'); ?></td>
                                        <td>
                                            <span class="badge bg-info"><?php echo $cat['complaint_count']; ?></span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-warning" data-bs-toggle="modal" data-bs-target="#editModal"
                                                onclick="loadEditForm(<?php echo htmlspecialchars(json_encode($cat)); ?>)">
                                                <i class="bi bi-pencil"></i> Edit
                                            </button>
                                            <form method="POST" style="display:inline;">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="category_id" value="<?php echo $cat['category_id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-danger"
                                                    onclick="return confirm('Are you sure you want to delete this category?')">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <!-- Add Category Form -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-success text-white">
                <h5 class="mb-0"><i class="bi bi-plus-circle"></i> Add New Category</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="add">
                    <div class="mb-3">
                        <label for="category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="category_name" name="category_name"
                            placeholder="e.g., Electrical, Plumbing" required>
                    </div>
                    <div class="mb-3">
                        <label for="assigned_staff_id" class="form-label">Assign Staff</label>
                        <select class="form-select" id="assigned_staff_id" name="assigned_staff_id" required>
                            <option value="">-- Select Staff --</option>
                            <?php foreach ($staff as $member): ?>
                                <option value="<?php echo $member['user_id']; ?>">
                                    <?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['email']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-check-circle"></i> Add Category
                    </button>
                </form>
            </div>
        </div>

        <!-- Staff Workload -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart"></i> Staff Workload</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <?php if (empty($workload)): ?>
                        <div class="text-muted text-center py-3">No staff members assigned</div>
                    <?php else: ?>
                        <?php foreach ($workload as $staff_member): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span><?php echo htmlspecialchars($staff_member['name']); ?></span>
                                    <span class="badge bg-secondary"><?php echo $staff_member['complaint_count']; ?> complaints</span>
                                </div>
                                <div class="progress mt-2" style="height: 5px;">
                                    <?php
                                    $max_complaints = max(array_column($workload, 'complaint_count')) ?: 1;
                                    $percentage = ($staff_member['complaint_count'] / $max_complaints) * 100;
                                    ?>
                                    <div class="progress-bar" style="width: <?php echo $percentage; ?>%"></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Edit Category Modal -->
<div class="modal fade category-edit-modal" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="editModalLabel">Edit Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="category_id" id="edit_category_id">
                    <div class="mb-3">
                        <label for="edit_category_name" class="form-label">Category Name</label>
                        <input type="text" class="form-control" id="edit_category_name" name="category_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_assigned_staff_id" class="form-label">Assign Staff</label>
                        <select class="form-select" id="edit_assigned_staff_id" name="assigned_staff_id" required>
                            <option value="">-- Select Staff --</option>
                            <?php foreach ($staff as $member): ?>
                                <option value="<?php echo $member['user_id']; ?>">
                                    <?php echo htmlspecialchars($member['name']); ?> (<?php echo htmlspecialchars($member['email']); ?>)
                                </option>
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
    document.getElementById('edit_assigned_staff_id').value = category.assigned_staff_id;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
