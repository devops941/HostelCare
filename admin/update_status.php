<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('admin');

$db = getDB();
$id = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

$stmt = $db->prepare('
    SELECT c.*, u.name AS student_name, u.room_no, u.phone, cat.name AS category
    FROM complaints c
    LEFT JOIN users u ON c.student_id = u.user_id
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    WHERE c.complaint_id = ?
');
$stmt->execute([$id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

if ($complaint['status'] === 'closed') {
    $_SESSION['flash_error'] = 'This complaint is already closed.';
    header('Location: ' . BASE_URL . '/admin/complaints.php');
    exit;
}

$allowed = [
    'pending'     => ['in_progress'],
    'in_progress' => ['resolved'],
    'resolved'    => [],
];
$nextStatuses = $allowed[$complaint['status']] ?? [];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['new_status'] ?? '';
    $remarks    = trim($_POST['remarks'] ?? '');

    if (!in_array($new_status, $nextStatuses)) {
        $errors[] = 'Invalid status transition.';
    }
    if (!$remarks) {
        $errors[] = 'Remarks are required.';
    }

    if (empty($errors)) {
        $old_status = $complaint['status'];
        $db->prepare('UPDATE complaints SET status = ? WHERE complaint_id = ?')
           ->execute([$new_status, $id]);
        $db->prepare('INSERT INTO complaint_updates (complaint_id, updated_by, old_status, new_status, remarks) VALUES (?,?,?,?,?)')
           ->execute([$id, $_SESSION['user_id'], $old_status, $new_status, $remarks]);

        $_SESSION['flash_success'] = 'Complaint #' . $id . ' updated to ' . ucfirst(str_replace('_', ' ', $new_status)) . '.';
        header('Location: ' . BASE_URL . '/admin/update_status.php?id=' . $id);
        exit;
    }
}

// Timeline
$updates = $db->prepare('
    SELECT cu.*, u.name AS updated_by_name
    FROM complaint_updates cu
    LEFT JOIN users u ON cu.updated_by = u.user_id
    WHERE cu.complaint_id = ?
    ORDER BY cu.updated_at ASC
');
$updates->execute([$id]);
$timeline = $updates->fetchAll();

$pageTitle = 'Update Complaint #' . $id;
include __DIR__ . '/../includes/header.php';
?>

<div class="page-header mb-4">
    <a href="<?= BASE_URL ?>/admin/complaints.php" class="text-muted small">
        <i class="bi bi-arrow-left me-1"></i>Back to Complaints
    </a>
    <h4 class="mt-1"><i class="bi bi-pencil-square me-2"></i>Update Complaint #<?= $id ?></h4>
</div>

<div class="row">
    <div class="col-lg-7">
        <!-- Complaint detail -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <h5 class="fw-bold"><?= htmlspecialchars($complaint['title']) ?></h5>
                <div class="d-flex flex-wrap gap-2 mb-3">
                    <span class="badge bg-secondary"><?= htmlspecialchars($complaint['category']) ?></span>
                    <span class="priority-badge priority-<?= $complaint['priority'] ?>"><?= ucfirst($complaint['priority']) ?></span>
                    <span class="status-badge status-<?= $complaint['status'] ?>"><?= ucfirst(str_replace('_',' ',$complaint['status'])) ?></span>
                </div>
                <p class="text-muted"><?= nl2br(htmlspecialchars($complaint['description'])) ?></p>
                <?php if ($complaint['image_path']): ?>
                <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($complaint['image_path']) ?>" class="img-fluid rounded" style="max-height:200px">
                <?php endif; ?>
                <hr>
                <small class="text-muted">
                    <i class="bi bi-person me-1"></i><?= htmlspecialchars($complaint['student_name']) ?>
                    <?php if ($complaint['room_no']): ?> &bull; Room <?= htmlspecialchars($complaint['room_no']) ?><?php endif; ?>
                    <?php if ($complaint['phone']): ?> &bull; <?= htmlspecialchars($complaint['phone']) ?><?php endif; ?>
                </small>
            </div>
        </div>

        <!-- Update form -->
        <?php if (!empty($nextStatuses)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold bg-light">
                <i class="bi bi-arrow-right-circle me-2"></i>Change Status
            </div>
            <div class="card-body">
                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <ul class="mb-0"><?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?></ul>
                </div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Status</label>
                        <select name="new_status" class="form-select" required>
                            <option value="">-- Select --</option>
                            <?php foreach ($nextStatuses as $s): ?>
                            <option value="<?= $s ?>" <?= (($_POST['new_status'] ?? '') === $s) ? 'selected' : '' ?>>
                                <?= ucfirst(str_replace('_', ' ', $s)) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="3" required
                                  placeholder="Describe the action taken..."><?= htmlspecialchars($_POST['remarks'] ?? '') ?></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-check-circle me-2"></i>Update Status
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            This complaint is <strong><?= ucfirst(str_replace('_',' ',$complaint['status'])) ?></strong> — awaiting student feedback to close.
        </div>
        <?php endif; ?>
    </div>

    <div class="col-lg-5">
        <!-- Timeline -->
        <div class="card border-0 shadow-sm">
            <div class="card-header fw-semibold bg-light">
                <i class="bi bi-clock-history me-2"></i>Update History
            </div>
            <div class="card-body p-0">
                <?php if (empty($timeline)): ?>
                <p class="text-muted p-3 mb-0">No updates yet.</p>
                <?php else: ?>
                <ul class="timeline-list list-unstyled p-3 mb-0">
                    <?php foreach ($timeline as $t): ?>
                    <li class="mb-3">
                        <div class="d-flex justify-content-between">
                            <span class="fw-semibold small">
                                <span class="status-badge status-<?= $t['old_status'] ?>"><?= ucfirst(str_replace('_',' ',$t['old_status'])) ?></span>
                                <i class="bi bi-arrow-right mx-1"></i>
                                <span class="status-badge status-<?= $t['new_status'] ?>"><?= ucfirst(str_replace('_',' ',$t['new_status'])) ?></span>
                            </span>
                            <small class="text-muted"><?= date('d M, H:i', strtotime($t['updated_at'])) ?></small>
                        </div>
                        <?php if ($t['remarks']): ?>
                        <p class="text-muted small mb-0 mt-1"><?= htmlspecialchars($t['remarks']) ?></p>
                        <?php endif; ?>
                        <small class="text-muted">by <?= htmlspecialchars($t['updated_by_name'] ?? 'System') ?></small>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
