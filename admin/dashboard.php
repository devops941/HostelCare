<?php
/**
 * Admin Dashboard for Hostel Complaint Tracking System
 * Shows statistics, escalated complaints, and recent complaints
 */

// Include authentication
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';

// Require admin role
requireRole('admin');

// Set page title for header
$pageTitle = 'Admin Dashboard';

// Include header
include __DIR__ . '/../includes/header.php';

// Get database connection
$db = getDB();

// Fetch statistics
try {
    // Total complaints
    $totalStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints");
    $totalStmt->execute();
    $totalComplaints = $totalStmt->fetch()['count'] ?? 0;

    // Pending complaints
    $pendingStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints WHERE status = 'pending'");
    $pendingStmt->execute();
    $pendingComplaints = $pendingStmt->fetch()['count'] ?? 0;

    // In Progress complaints
    $progressStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints WHERE status = 'in_progress'");
    $progressStmt->execute();
    $inProgressComplaints = $progressStmt->fetch()['count'] ?? 0;

    // Resolved complaints
    $resolvedStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints WHERE status = 'resolved'");
    $resolvedStmt->execute();
    $resolvedComplaints = $resolvedStmt->fetch()['count'] ?? 0;

    // Escalated complaints (48+ hours pending/assigned)
    $escalatedStmt = $db->prepare("
        SELECT COUNT(*) as count FROM complaints
        WHERE (status = 'pending' OR status = 'in_progress')
        AND created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
    ");
    $escalatedStmt->execute();
    $escalatedComplaints = $escalatedStmt->fetch()['count'] ?? 0;

    // Fetch escalated complaints for table
    $escalatedTableStmt = $db->prepare("
        SELECT
            c.complaint_id,
            u.name as student_name,
            cat.name as category,
            c.title,
            ROUND(HOUR(TIMEDIFF(NOW(), c.created_at))) as hours_pending,
            c.status
        FROM complaints c
        LEFT JOIN users u ON c.student_id = u.user_id
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        WHERE (c.status = 'pending' OR c.status = 'in_progress')
        AND c.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR)
        ORDER BY c.created_at ASC
    ");
    $escalatedTableStmt->execute();
    $escalatedList = $escalatedTableStmt->fetchAll() ?? [];

    // Fetch recent 10 complaints
    $recentStmt = $db->prepare("
        SELECT
            c.complaint_id,
            u.name as student_name,
            cat.name as category,
            c.title,
            c.priority,
            c.status,
            c.created_at
        FROM complaints c
        LEFT JOIN users u ON c.student_id = u.user_id
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        ORDER BY c.created_at DESC
    ");
    $recentStmt->execute();
    $recentComplaints = $recentStmt->fetchAll() ?? [];

} catch (PDOException $e) {
    $totalComplaints = 0;
    $pendingComplaints = 0;
    $inProgressComplaints = 0;
    $resolvedComplaints = 0;
    $escalatedComplaints = 0;
    $escalatedList = [];
    $recentComplaints = [];
}


/**
 * Helper function to format status badge
 */
function getStatusBadge($status) {
    $badgeClass = '';
    $statusText = ucfirst(str_replace('_', ' ', $status));

    switch ($status) {
        case 'pending':
            $badgeClass = 'bg-warning text-dark';
            break;
        case 'in_progress':
            $badgeClass = 'bg-info';
            break;
        case 'resolved':
            $badgeClass = 'bg-success';
            break;
        case 'closed':
            $badgeClass = 'bg-secondary';
            break;
        default:
            $badgeClass = 'bg-secondary';
    }

    return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
}
?>

<div class="page-header">
    <h4><i class="bi bi-speedometer2"></i>Admin Dashboard</h4>
    <p>System overview &mdash; <?= date('l, F j, Y') ?></p>
</div>

<!-- Stats row -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card primary">
            <div class="stat-icon primary"><i class="bi bi-chat-dots"></i></div>
            <div class="stat-value"><?= $totalComplaints ?></div>
            <div class="stat-label">Total</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="bi bi-clock"></i></div>
            <div class="stat-value"><?= $pendingComplaints ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card accent">
            <div class="stat-icon accent"><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-value"><?= $inProgressComplaints ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?= $resolvedComplaints ?></div>
            <div class="stat-label">Resolved</div>
        </div>
    </div>
    <div class="col-6 col-md-4 col-lg-2">
        <div class="stat-card danger">
            <div class="stat-icon danger"><i class="bi bi-exclamation-triangle"></i></div>
            <div class="stat-value"><?= $escalatedComplaints ?></div>
            <div class="stat-label">Escalated</div>
        </div>
    </div>
</div>

<!-- Escalated complaints -->
<div class="card mb-4">
    <div class="card-header" style="background:linear-gradient(135deg,#ef4444,#dc2626);color:#fff">
        <i class="bi bi-exclamation-circle"></i>
        Escalated Complaints (48+ Hours)
        <span class="badge bg-light text-danger ms-auto"><?= count($escalatedList) ?></span>
    </div>
    <div class="table-wrapper">
        <?php if (empty($escalatedList)): ?>
        <div class="empty-state"><i class="bi bi-emoji-smile"></i><h6>No escalated complaints. Great job!</h6></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>#ID</th><th>Student</th><th>Category</th><th>Title</th><th>Hours</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($escalatedList as $c): ?>
            <tr class="escalated-row">
                <td class="fw-bold text-primary">#<?= $c['complaint_id'] ?></td>
                <td><?= htmlspecialchars($c['student_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($c['category'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars(substr($c['title'], 0, 45)) ?></td>
                <td><span class="escalated-badge"><i class="bi bi-alarm"></i><?= $c['hours_pending'] ?>h</span></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- All complaints -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-list-ul"></i> All Complaints
        <span class="badge bg-secondary ms-2"><?= count($recentComplaints) ?></span>
        <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-sm btn-primary ms-auto">Manage <i class="bi bi-arrow-right"></i></a>
    </div>
    <div class="table-wrapper">
        <?php if (empty($recentComplaints)): ?>
        <div class="empty-state"><i class="bi bi-inbox"></i><h6>No complaints yet.</h6></div>
        <?php else: ?>
        <table class="table">
            <thead><tr><th>#ID</th><th>Student</th><th>Category</th><th>Title</th><th>Priority</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php foreach ($recentComplaints as $c):
                $isEsc = in_array($c['status'],['pending','assigned']) && (strtotime('now') - strtotime($c['created_at'])) > 48*3600;
            ?>
            <tr class="<?= $isEsc ? 'escalated-row' : '' ?>">
                <td class="fw-bold text-primary">#<?= $c['complaint_id'] ?></td>
                <td><?= htmlspecialchars($c['student_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($c['category'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars(substr($c['title'], 0, 45)) ?><?= $isEsc ? ' <span class="escalated-badge ms-1"><i class="bi bi-alarm"></i>Esc</span>' : '' ?></td>
                <td><span class="priority-badge priority-<?= $c['priority'] ?>"><?= ucfirst($c['priority']) ?></span></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<!-- Quick links -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="stat-icon primary mb-3" style="width:42px;height:42px;font-size:18px"><i class="bi bi-tags"></i></div>
                <h6 style="font-weight:700;margin-bottom:6px">Manage Categories</h6>
                <p class="text-muted small mb-3">Create, update and manage complaint categories</p>
                <a href="<?= BASE_URL ?>/admin/categories.php" class="btn btn-primary btn-sm"><i class="bi bi-arrow-right"></i> Go</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="stat-icon success mb-3" style="width:42px;height:42px;font-size:18px"><i class="bi bi-bar-chart"></i></div>
                <h6 style="font-weight:700;margin-bottom:6px">View Reports</h6>
                <p class="text-muted small mb-3">Analyse complaints and generate detailed reports</p>
                <a href="<?= BASE_URL ?>/admin/reports.php" class="btn btn-success btn-sm"><i class="bi bi-arrow-right"></i> Go</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="stat-icon accent mb-3" style="width:42px;height:42px;font-size:18px"><i class="bi bi-people"></i></div>
                <h6 style="font-weight:700;margin-bottom:6px">Manage Users</h6>
                <p class="text-muted small mb-3">Manage system users and their roles</p>
                <a href="<?= BASE_URL ?>/admin/users.php" class="btn btn-info btn-sm"><i class="bi bi-arrow-right"></i> Go</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
