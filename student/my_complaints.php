<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pageTitle  = 'My Complaints';
$db         = getDB();
$student_id = $_SESSION['user_id'];
$filter     = $_GET['status'] ?? '';

$sql = "SELECT c.complaint_id, c.title, cat.name AS category, c.priority, c.status,
               c.created_at, c.updated_at,
               TIMESTAMPDIFF(HOUR, c.created_at, NOW()) AS hours_old
        FROM complaints c
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        WHERE c.student_id = ?";
$params = [$student_id];

if ($filter && in_array($filter, ['pending','assigned','in_progress','resolved','closed'])) {
    $sql    .= ' AND c.status = ?';
    $params[] = $filter;
}
$sql .= ' ORDER BY c.created_at DESC';

$stmt = $db->prepare($sql);
$stmt->execute($params);
$complaints = $stmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header mb-4">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h4><i class="bi bi-list-ul me-2"></i>My Complaints</h4>
            <p>Track the status of all your submitted complaints.</p>
        </div>
        <a href="<?= BASE_URL ?>/student/new_complaint.php" class="btn btn-light fw-semibold">
            <i class="bi bi-plus-circle me-1"></i>New Complaint
        </a>
    </div>
</div>

<!-- Filter bar -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <div class="d-flex gap-2 flex-wrap align-items-center">
            <span class="text-muted me-2 small">Filter by status:</span>
            <?php foreach ([''=>'All', 'pending'=>'Pending', 'assigned'=>'Assigned', 'in_progress'=>'In Progress', 'resolved'=>'Resolved', 'closed'=>'Closed'] as $val => $lbl): ?>
            <a href="?status=<?= $val ?>" class="badge text-decoration-none fs-6
                <?= $filter === $val ? 'bg-primary' : 'bg-light text-dark border' ?>">
                <?= $lbl ?>
            </a>
            <?php endforeach; ?>
            <div class="ms-auto">
                <input type="text" id="searchBox" class="form-control form-control-sm" placeholder="Search complaints..." data-table-search="complaintsTable" style="width:200px">
            </div>
        </div>
    </div>
</div>

<?php if (empty($complaints)): ?>
<div class="text-center py-5">
    <i class="bi bi-inbox text-muted" style="font-size:3rem"></i>
    <h5 class="mt-3 text-muted">No complaints found.</h5>
    <a href="<?= BASE_URL ?>/student/new_complaint.php" class="btn btn-primary mt-2">Submit your first complaint</a>
</div>
<?php else: ?>
<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0" id="complaintsTable">
            <thead class="table-light">
                <tr>
                    <th>#ID</th>
                    <th>Title</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Last Update</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($complaints as $c):
                $isEscalated = in_array($c['status'], ['pending','assigned']) && $c['hours_old'] >= 48;
            ?>
            <tr class="<?= $isEscalated ? 'table-danger' : '' ?>">
                <td class="fw-bold text-primary">#<?= $c['complaint_id'] ?></td>
                <td>
                    <?= htmlspecialchars(mb_strimwidth($c['title'], 0, 50, '…')) ?>
                    <?php if ($isEscalated): ?>
                        <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle-fill"></i> Escalated</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['category'] ?? 'N/A') ?></td>
                <td><span class="priority-badge priority-<?= $c['priority'] ?>"><?= ucfirst($c['priority']) ?></span></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td class="text-muted small"><?= date('d M Y, H:i', strtotime($c['updated_at'])) ?></td>
                <td>
                    <a href="<?= BASE_URL ?>/student/view_complaint.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye me-1"></i>View
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>
