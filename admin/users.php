<?php
/**
 * User Management Page - Admin Panel
 * Hostel Complaint Tracking System
 * Handles CRUD operations for users and role management
 */

// Include configuration and auth
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin role
requireRole('admin');

$pageTitle = 'User Management';
$pdo = getDB();
$message = '';
$messageType = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        try {
            if ($_POST['action'] === 'add') {
                $name = trim($_POST['user_name'] ?? '');
                $email = trim($_POST['user_email'] ?? '');
                $phone = trim($_POST['user_phone'] ?? '');
                $role = $_POST['user_role'] ?? 'student';
                $temp_password = $_POST['temp_password'] ?? 'TempPass123!';

                if (empty($name) || empty($email)) {
                    $message = 'Name and email are required';
                    $messageType = 'danger';
                } else {
                    // Check if email already exists
                    $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
                    $check->execute([$email]);

                    if ($check->rowCount() > 0) {
                        $message = 'Email already exists';
                        $messageType = 'danger';
                    } else {
                        $password_hash = password_hash($temp_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO users (name, email, phone, role, password_hash) VALUES (?, ?, ?, ?, ?)');
                        $stmt->execute([$name, $email, $phone, $role, $password_hash]);
                        $message = "User added successfully. Temporary password: <strong>$temp_password</strong>";
                        $messageType = 'success';
                    }
                }
            } elseif ($_POST['action'] === 'delete') {
                $user_id = $_POST['user_id'] ?? '';

                // Prevent deletion of admin user
                $check = $pdo->prepare('SELECT role FROM users WHERE user_id = ?');
                $check->execute([$user_id]);
                $result = $check->fetch();

                if ($result && $result['role'] === 'admin') {
                    $message = 'Cannot delete admin users';
                    $messageType = 'danger';
                } else {
                    // Check if user has submitted complaints
                    $check_complaints = $pdo->prepare('SELECT COUNT(*) as count FROM complaints WHERE student_id = ?');
                    $check_complaints->execute([$user_id]);
                    $complaint_result = $check_complaints->fetch();

                    if ($complaint_result['count'] > 0) {
                        $message = 'Cannot delete user with assigned complaints. Archive instead.';
                        $messageType = 'warning';
                    } else {
                        $stmt = $pdo->prepare('DELETE FROM users WHERE user_id = ?');
                        $stmt->execute([$user_id]);
                        $message = 'User deleted successfully';
                        $messageType = 'success';
                    }
                }
            }
        } catch (Exception $e) {
            $message = 'Error: ' . $e->getMessage();
            $messageType = 'danger';
        }
    }
}

// Get filter parameter
$role_filter = $_GET['role'] ?? '';

// Fetch all users with optional role filter
$query = 'SELECT user_id, name, email, phone, role, created_at FROM users';
$params = [];

if (!empty($role_filter) && in_array($role_filter, ['admin', 'staff', 'student'])) {
    $query .= ' WHERE role = ?';
    $params[] = $role_filter;
}

$query .= ' ORDER BY role, name';
$users_stmt = $pdo->prepare($query);
$users_stmt->execute($params);
$users = $users_stmt->fetchAll();

// Get role statistics
$role_stats = $pdo->query('
    SELECT role, COUNT(*) as count FROM users GROUP BY role
')->fetchAll(PDO::FETCH_KEY_PAIR);

include __DIR__ . '/../includes/header.php';
?>

<div class="row">
    <div class="col-lg-3">
        <!-- Statistics Cards -->
        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <h3 class="text-primary"><?php echo array_sum($role_stats); ?></h3>
                <p class="text-muted mb-0">Total Users</p>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <h3 class="text-success"><?php echo $role_stats['staff'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Staff Members</p>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <h3 class="text-info"><?php echo $role_stats['student'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Students</p>
            </div>
        </div>

        <div class="card shadow-sm mb-3">
            <div class="card-body text-center">
                <h3 class="text-warning"><?php echo $role_stats['admin'] ?? 0; ?></h3>
                <p class="text-muted mb-0">Admins</p>
            </div>
        </div>

        <!-- Role Filter -->
        <div class="card shadow-sm">
            <div class="card-header bg-secondary text-white">
                <h5 class="mb-0"><i class="bi bi-funnel"></i> Filter by Role</h5>
            </div>
            <div class="card-body">
                <div class="list-group">
                    <a href="users.php" class="list-group-item list-group-item-action <?php echo empty($role_filter) ? 'active' : ''; ?>">
                        <i class="bi bi-check-circle"></i> All Roles
                    </a>
                    <a href="users.php?role=admin" class="list-group-item list-group-item-action <?php echo $role_filter === 'admin' ? 'active' : ''; ?>">
                        <span class="badge bg-warning">Admin</span> Administrators
                    </a>
                    <a href="users.php?role=staff" class="list-group-item list-group-item-action <?php echo $role_filter === 'staff' ? 'active' : ''; ?>">
                        <span class="badge bg-success">Staff</span> Staff Members
                    </a>
                    <a href="users.php?role=student" class="list-group-item list-group-item-action <?php echo $role_filter === 'student' ? 'active' : ''; ?>">
                        <span class="badge bg-info">Student</span> Students
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-people"></i> User Management</h5>
                <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-plus-circle"></i> Add User
                </button>
            </div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Role</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">No users found</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($user['name']); ?></strong>
                                        </td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td><?php echo htmlspecialchars($user['phone'] ?? '-'); ?></td>
                                        <td>
                                            <?php
                                            $role_badge_class = match($user['role']) {
                                                'admin'   => 'warning',
                                                'staff'   => 'success',
                                                'student' => 'info',
                                                default   => 'secondary'
                                            };
                                            $role_icon = match($user['role']) {
                                                'admin'   => 'shield-fill',
                                                'staff'   => 'tools',
                                                'student' => 'mortarboard',
                                                default   => 'person'
                                            };
                                            ?>
                                            <span class="badge bg-<?php echo $role_badge_class; ?>">
                                                <i class="bi bi-<?php echo $role_icon; ?>"></i> <?php echo ucfirst($user['role']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                            </small>
                                        </td>
                                        <td>
                                            <?php if ($user['user_id'] !== ($_SESSION['user_id'] ?? null)): ?>
                                                <form method="POST" style="display:inline;">
                                                    <input type="hidden" name="action" value="delete">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['user_id']; ?>">
                                                    <button type="submit" class="btn btn-sm btn-danger"
                                                        onclick="return confirm('Are you sure you want to delete this user?')">
                                                        <i class="bi bi-trash"></i> Delete
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-light text-dark">Current User</span>
                                            <?php endif; ?>
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
</div>

<!-- Add User Modal -->
<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="addUserModalLabel">Add New User</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="action" value="add">

                    <div class="mb-3">
                        <label for="user_name" class="form-label">Full Name</label>
                        <input type="text" class="form-control" id="user_name" name="user_name" required>
                    </div>

                    <div class="mb-3">
                        <label for="user_email" class="form-label">Email Address</label>
                        <input type="email" class="form-control" id="user_email" name="user_email" required>
                    </div>

                    <div class="mb-3">
                        <label for="user_phone" class="form-label">Phone Number</label>
                        <input type="tel" class="form-control" id="user_phone" name="user_phone" placeholder="Optional">
                    </div>

                    <div class="mb-3">
                        <label for="user_role" class="form-label">Role</label>
                        <select class="form-select" id="user_role" name="user_role" required>
                            <option value="student">Student</option>
                            <option value="staff">Staff Member</option>
                            <option value="admin">Administrator</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="temp_password" class="form-label">Temporary Password</label>
                        <div class="input-group">
                            <input type="text" class="form-control" id="temp_password" name="temp_password" value="TempPass123!" readonly>
                            <button type="button" class="btn btn-outline-secondary" onclick="generatePassword()">
                                <i class="bi bi-arrow-repeat"></i> Generate
                            </button>
                        </div>
                        <small class="text-muted">User should change this on first login</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary"
                        onclick="bootstrap.Modal.getInstance(document.getElementById('addUserModal')).hide()">Cancel</button>
                    <button type="submit" class="btn btn-success">
                        <i class="bi bi-check-circle"></i> Add User
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Move modal to body to avoid flex/overflow container issues
const addUserModal = document.getElementById('addUserModal');
document.body.appendChild(addUserModal);

// Reset form fields when modal is closed
addUserModal.addEventListener('hidden.bs.modal', () => {
    addUserModal.querySelector('form').reset();
    document.getElementById('temp_password').value = 'TempPass123!';
});

function generatePassword() {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%';
    let password = '';
    for (let i = 0; i < 12; i++) {
        password += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    document.getElementById('temp_password').value = password;
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
