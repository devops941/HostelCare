<?php
// Staff Dashboard for Hostel Complaint Tracking System
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Require staff role
requireRole('staff');

$pageTitle = 'Staff Dashboard';
$db = getDB();
$staffId = $_SESSION['user_id'];
$staffName = $_SESSION['name'] ?? 'Staff Member';

// Get staff's assigned categories
$categoryQuery = $db->prepare("
    SELECT category_id FROM categories WHERE assigned_staff_id = ?
");
$categoryQuery->execute([$staffId]);
$categories = $categoryQuery->fetchAll();
$categoryIds = array_map(fn($cat) => $cat['category_id'], $categories);

// If no categories assigned, handle gracefully
if (empty($categoryIds)) {
    $categoryIds = [0]; // Will return 0 results safely
}

$placeholders = str_repeat('?,', count($categoryIds) - 1) . '?';

// Get complaints assigned to this staff member
$complaintsQuery = $db->prepare("
    SELECT
        c.complaint_id,
        c.title,
        c.description,
        c.priority,
        c.status,
        c.created_at,
        c.updated_at,
        cat.name AS category,
        s.name AS student_name,
        s.room_no,
        (TIMESTAMPDIFF(HOUR, c.created_at, NOW()) >= 48) AS is_escalated
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.category_id
    JOIN users s ON c.student_id = s.user_id
    WHERE c.category_id IN ($placeholders)
    ORDER BY c.created_at DESC
");
$complaintsQuery->execute($categoryIds);
$complaints = $complaintsQuery->fetchAll();

// Calculate statistics
$totalComplaints = count($complaints);

$pending = count(array_filter($complaints, fn($c) => $c['status'] === 'pending'));
$assigned = count(array_filter($complaints, fn($c) => $c['status'] === 'assigned'));
$pendingAssigned = $pending + $assigned;

$inProgress = count(array_filter($complaints, fn($c) => $c['status'] === 'in_progress'));

// Count resolved today
$resolvedToday = count(array_filter($complaints, fn($c) =>
    $c['status'] === 'resolved' &&
    date('Y-m-d', strtotime($c['updated_at'])) === date('Y-m-d')
));

// Get escalated complaints count
$escalated = count(array_filter($complaints, fn($c) => $c['is_escalated']));

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header">
    <h4><i class="bi bi-speedometer2"></i>Staff Dashboard</h4>
    <p>Welcome back, <strong><?= htmlspecialchars($staffName) ?></strong>. Manage your assigned complaints.</p>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card primary">
            <div class="stat-icon primary"><i class="bi bi-inbox"></i></div>
            <div class="stat-value"><?= $totalComplaints ?></div>
            <div class="stat-label">Total Assigned</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="bi bi-clock"></i></div>
            <div class="stat-value"><?= $pendingAssigned ?></div>
            <div class="stat-label">Pending / Assigned</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card purple">
            <div class="stat-icon purple"><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-value"><?= $inProgress ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?= $resolvedToday ?></div>
            <div class="stat-label">Resolved Today</div>
        </div>
    </div>
</div>

<?php if ($escalated > 0): ?>
<div class="alert alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill"></i>
    <strong><?= $escalated ?></strong> complaint(s) escalated — over 48 hours without resolution.
    <button class="btn-close" onclick="this.parentElement.remove()">×</button>
</div>
<?php endif; ?>

<!-- Complaints table -->
<div class="card">
    <div class="card-header"><i class="bi bi-list-check"></i> My Complaints</div>
    <?php if (empty($complaints)): ?>
    <div class="card-body">
        <div class="empty-state"><i class="bi bi-inbox"></i><h6>No complaints assigned to your category yet.</h6></div>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr><th>#ID</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Student</th><th>Room</th><th>Date</th><th></th></tr>
            </thead>
            <tbody>
            <?php foreach ($complaints as $c): ?>
            <tr class="<?= $c['is_escalated'] ? 'escalated-row' : '' ?>">
                <td class="fw-bold text-primary">#<?= $c['complaint_id'] ?></td>
                <td>
                    <?= htmlspecialchars(substr($c['title'], 0, 40)) ?>
                    <?php if ($c['is_escalated']): ?><br><span class="escalated-badge"><i class="bi bi-alarm"></i>Escalated</span><?php endif; ?>
                </td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($c['category']) ?></span></td>
                <td><span class="priority-badge priority-<?= $c['priority'] ?>"><?= ucfirst($c['priority']) ?></span></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
                <td><?= htmlspecialchars($c['student_name']) ?></td>
                <td><strong><?= htmlspecialchars($c['room_no'] ?? 'N/A') ?></strong></td>
                <td class="text-muted small"><?= date('d M, H:i', strtotime($c['created_at'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/staff/update_status.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm btn-primary">
                        <i class="bi bi-pencil-square"></i> Update
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
