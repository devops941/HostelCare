<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$pageTitle = 'All Complaints';
$db = getDB();

$status   = $_GET['status']   ?? '';
$priority = $_GET['priority'] ?? '';
$search   = trim($_GET['q']   ?? '');

$sql = "SELECT c.complaint_id, c.title, c.priority, c.status, c.created_at,
               u.name AS student_name, u.room_no,
               cat.name AS category,
               TIMESTAMPDIFF(HOUR, c.created_at, NOW()) AS hours_old
        FROM complaints c
        LEFT JOIN users u ON c.student_id = u.user_id
        LEFT JOIN categories cat ON c.category_id = cat.category_id
        WHERE 1=1";
$params = [];

if ($status && in_array($status, ['pending','in_progress','resolved','closed'])) {
    $sql .= ' AND c.status = ?'; $params[] = $status;
}
if ($priority && in_array($priority, ['low','medium','high'])) {
    $sql .= ' AND c.priority = ?'; $params[] = $priority;
}
if ($search) {
    $sql .= ' AND (c.title LIKE ? OR u.name LIKE ? OR cat.name LIKE ?)';
    $like = "%$search%";
    $params[] = $like; $params[] = $like; $params[] = $like;
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
            <h4><i class="bi bi-list-ul me-2"></i>All Complaints</h4>
            <p>Browse, filter, and manage every complaint in the system.</p>
        </div>
        <span class="badge bg-light text-dark fs-6 border"><?= count($complaints) ?> results</span>
    </div>
</div>

<!-- Filters -->
<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-2">
        <form method="GET" class="d-flex flex-wrap gap-2 align-items-center">
            <input type="text" name="q" class="form-control form-control-sm" style="width:200px"
                   placeholder="Search title / student / category" value="<?= htmlspecialchars($search) ?>">
            <select name="status" class="form-select form-select-sm" style="width:150px">
                <option value="">All Statuses</option>
                <?php foreach (['pending','in_progress','resolved','closed'] as $s): ?>
                <option value="<?= $s ?>" <?= $status===$s?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                <?php endforeach; ?>
            </select>
            <select name="priority" class="form-select form-select-sm" style="width:130px">
                <option value="">All Priorities</option>
                <?php foreach (['low','medium','high'] as $p): ?>
                <option value="<?= $p ?>" <?= $priority===$p?'selected':'' ?>><?= ucfirst($p) ?></option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="btn btn-sm btn-primary">Filter</button>
            <a href="<?= BASE_URL ?>/admin/complaints.php" class="btn btn-sm btn-outline-secondary">Reset</a>
        </form>
    </div>
</div>

<div class="card border-0 shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>#ID</th>
                    <th>Title</th>
                    <th>Student</th>
                    <th>Room</th>
                    <th>Category</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Submitted</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
            <?php if (empty($complaints)): ?>
            <tr><td colspan="9" class="text-center text-muted py-4">No complaints found.</td></tr>
            <?php else: ?>
            <?php foreach ($complaints as $c):
                $isEscalated = $c['status'] === 'pending' && $c['hours_old'] >= 48;
                $canUpdate   = !in_array($c['status'], ['closed']);
            ?>
            <tr class="<?= $isEscalated ? 'table-danger' : '' ?>">
                <td class="fw-bold text-primary">#<?= $c['complaint_id'] ?></td>
                <td>
                    <?= htmlspecialchars(mb_strimwidth($c['title'],0,50,'…')) ?>
                    <?php if ($isEscalated): ?>
                    <span class="badge bg-danger ms-1"><i class="bi bi-exclamation-triangle-fill"></i> Escalated</span>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['student_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($c['room_no'] ?? '—') ?></td>
                <td><?= htmlspecialchars($c['category'] ?? 'N/A') ?></td>
                <td><span class="priority-badge priority-<?= $c['priority'] ?>"><?= ucfirst($c['priority']) ?></span></td>
                <td><span class="status-badge status-<?= $c['status'] ?>"><?= ucfirst(str_replace('_',' ',$c['status'])) ?></span></td>
                <td class="text-muted small"><?= date('d M Y', strtotime($c['created_at'])) ?></td>
                <td>
                    <?php if ($canUpdate): ?>
                    <a href="<?= BASE_URL ?>/admin/update_status.php?id=<?= $c['complaint_id'] ?>" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-pencil-square"></i> Update
                    </a>
                    <?php else: ?>
                    <span class="text-muted small">Closed</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
