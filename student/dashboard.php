<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pageTitle = 'My Dashboard';
include __DIR__ . '/../includes/header.php';

$db = getDB();
$student_id = $_SESSION['user_id'];
$student_name = $_SESSION['name'] ?? 'Student';
$room_no = $_SESSION['room_no'] ?? 'N/A';

try {
    $totalStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints WHERE student_id = ?");
    $totalStmt->execute([$student_id]);
    $totalComplaints = $totalStmt->fetch()['count'] ?? 0;

    $pendingStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'pending'");
    $pendingStmt->execute([$student_id]);
    $pendingComplaints = $pendingStmt->fetch()['count'] ?? 0;

    $progressStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND status = 'in_progress'");
    $progressStmt->execute([$student_id]);
    $inProgressComplaints = $progressStmt->fetch()['count'] ?? 0;

    $resolvedStmt = $db->prepare("SELECT COUNT(*) as count FROM complaints WHERE student_id = ? AND (status = 'resolved' OR status = 'closed')");
    $resolvedStmt->execute([$student_id]);
    $resolvedComplaints = $resolvedStmt->fetch()['count'] ?? 0;

    $complaintsStmt = $db->prepare("
        SELECT c.complaint_id, c.title, cat.name as category, c.priority, c.status, c.created_at,
               TIMESTAMPDIFF(HOUR, c.created_at, NOW()) as hours_ago,
               CASE WHEN c.status = 'pending' AND c.created_at < DATE_SUB(NOW(), INTERVAL 48 HOUR) THEN 1 ELSE 0 END as is_escalated
        FROM complaints c
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        WHERE c.student_id = ?
        ORDER BY c.created_at DESC
    ");
    $complaintsStmt->execute([$student_id]);
    $complaints = $complaintsStmt->fetchAll() ?? [];
} catch (PDOException $e) {
    $totalComplaints = $pendingComplaints = $inProgressComplaints = $resolvedComplaints = 0;
    $complaints = [];
}
?>

<!-- Welcome banner -->
<div style="background:linear-gradient(135deg,#4f46e5 0%,#8b5cf6 100%);border-radius:18px;padding:24px 28px;margin-bottom:24px;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:16px">
    <div>
        <h2 style="color:#fff;font-size:20px;font-weight:800;margin-bottom:4px">Welcome, <?= htmlspecialchars($student_name) ?>!</h2>
        <p style="color:rgba(255,255,255,.8);margin:0;font-size:13px"><i class="bi bi-door-closed" style="margin-right:6px"></i>Room <?= htmlspecialchars($room_no) ?> &nbsp;&middot;&nbsp; Track and manage your hostel complaints</p>
    </div>
    <a href="<?= BASE_URL ?>/student/new_complaint.php" style="background:#fff;color:#4f46e5;padding:10px 22px;border-radius:10px;font-size:13px;font-weight:700;text-decoration:none;display:inline-flex;align-items:center;gap:8px;white-space:nowrap">
        <i class="bi bi-plus-circle"></i> New Complaint
    </a>
</div>

<!-- Stats -->
<div class="row g-3 mb-4">
    <div class="col-6 col-lg-3">
        <div class="stat-card primary">
            <div class="stat-icon primary"><i class="bi bi-chat-dots"></i></div>
            <div class="stat-value"><?= $totalComplaints ?></div>
            <div class="stat-label">Total Submitted</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card warning">
            <div class="stat-icon warning"><i class="bi bi-clock"></i></div>
            <div class="stat-value"><?= $pendingComplaints ?></div>
            <div class="stat-label">Pending</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card purple">
            <div class="stat-icon purple"><i class="bi bi-arrow-repeat"></i></div>
            <div class="stat-value"><?= $inProgressComplaints ?></div>
            <div class="stat-label">In Progress</div>
        </div>
    </div>
    <div class="col-6 col-lg-3">
        <div class="stat-card success">
            <div class="stat-icon success"><i class="bi bi-check-circle"></i></div>
            <div class="stat-value"><?= $resolvedComplaints ?></div>
            <div class="stat-label">Resolved / Closed</div>
        </div>
    </div>
</div>

<!-- Recent complaints -->
<div class="card mb-4">
    <div class="card-header">
        <i class="bi bi-list-check"></i> My Complaints
        <a href="<?= BASE_URL ?>/student/my_complaints.php" class="btn btn-sm btn-primary ms-auto">View All</a>
    </div>
    <?php if (empty($complaints)): ?>
    <div class="card-body">
        <div class="empty-state">
            <i class="bi bi-inbox"></i>
            <h6>No complaints submitted yet.</h6>
            <a href="<?= BASE_URL ?>/student/new_complaint.php" class="btn btn-primary btn-sm mt-2">Submit your first complaint</a>
        </div>
    </div>
    <?php else: ?>
    <div class="table-wrapper">
        <table class="table">
            <thead><tr><th>#ID</th><th>Title</th><th>Category</th><th>Priority</th><th>Status</th><th>Submitted</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($complaints as $c): ?>
            <tr class="<?= $c['is_escalated'] ? 'escalated-row' : '' ?>">
                <td class="fw-bold text-primary">#<?= $c['complaint_id'] ?></td>
                <td>
                    <?= htmlspecialchars(substr($c['title'], 0, 50)) ?>
                    <?php if ($c['is_escalated']): ?><br><span class="escalated-badge"><i class="bi bi-alarm"></i>Escalated</span><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['category'] ?? 'N/A') ?></td>
                <td><span class="priority-badge priority-<?= $c['priority'] ?>"><?= ucfirst($c['priority']) ?></span></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($c['created_at'])) ?><br><?= $c['hours_ago'] ?>h ago</td>
                <td><a href="<?= BASE_URL ?>/student/view_complaint.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<!-- Quick actions -->
<div class="row g-3">
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="stat-icon primary mb-3" style="width:42px;height:42px;font-size:18px"><i class="bi bi-plus-circle"></i></div>
                <h6 style="font-weight:700;margin-bottom:6px">Submit Complaint</h6>
                <p class="text-muted small mb-3">Report a new issue in your hostel room</p>
                <a href="<?= BASE_URL ?>/student/new_complaint.php" class="btn btn-primary btn-sm">Submit</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="stat-icon accent mb-3" style="width:42px;height:42px;font-size:18px"><i class="bi bi-card-list"></i></div>
                <h6 style="font-weight:700;margin-bottom:6px">All Complaints</h6>
                <p class="text-muted small mb-3">View full list of all your submitted complaints</p>
                <a href="<?= BASE_URL ?>/student/my_complaints.php" class="btn btn-info btn-sm">View All</a>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card">
            <div class="card-body">
                <div class="stat-icon success mb-3" style="width:42px;height:42px;font-size:18px"><i class="bi bi-search"></i></div>
                <h6 style="font-weight:700;margin-bottom:6px">Track Status</h6>
                <p class="text-muted small mb-3">Monitor your complaint progress in real-time</p>
                <a href="<?= BASE_URL ?>/student/my_complaints.php" class="btn btn-success btn-sm">Track</a>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
