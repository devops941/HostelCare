<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('staff');

$pageTitle = 'Update Complaint Status';
$db        = getDB();
$staff_id  = $_SESSION['user_id'];
$id        = (int)($_GET['id'] ?? 0);

if (!$id) {
    header('Location: ' . BASE_URL . '/staff/dashboard.php');
    exit;
}

// Load complaint — must belong to this staff's categories
$stmt = $db->prepare("
    SELECT c.*, cat.name AS category, u.name AS student_name, u.room_no, u.phone
    FROM complaints c
    JOIN categories cat ON c.category_id = cat.category_id
    JOIN users u ON c.student_id = u.user_id
    WHERE c.complaint_id = ? AND cat.assigned_staff_id = ?
");
$stmt->execute([$id, $staff_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    $_SESSION['flash_error'] = 'Complaint not found or access denied.';
    header('Location: ' . BASE_URL . '/staff/dashboard.php');
    exit;
}

// Allowed transitions
$transitions = [
    'pending'     => ['assigned'],
    'assigned'    => ['in_progress'],
    'in_progress' => ['resolved'],
    'resolved'    => [],
    'closed'      => [],
];
$allowed = $transitions[$complaint['status']] ?? [];

// Handle POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = $_POST['new_status'] ?? '';
    $remarks    = trim($_POST['remarks'] ?? '');

    if (!in_array($new_status, $allowed)) {
        $_SESSION['flash_error'] = 'Invalid status transition.';
    } elseif (!$remarks) {
        $_SESSION['flash_error'] = 'Please add remarks before updating status.';
    } else {
        $old_status = $complaint['status'];
        $db->prepare('UPDATE complaints SET status=?, updated_at=NOW() WHERE complaint_id=?')
           ->execute([$new_status, $id]);
        $db->prepare('INSERT INTO complaint_updates (complaint_id,updated_by,old_status,new_status,remarks) VALUES (?,?,?,?,?)')
           ->execute([$id, $staff_id, $old_status, $new_status, $remarks]);
        $_SESSION['flash_success'] = "Status updated to \"" . ucfirst(str_replace('_',' ',$new_status)) . '" successfully.';
        header('Location: ' . BASE_URL . '/staff/update_status.php?id=' . $id);
        exit;
    }
}

// Fetch status history
$histStmt = $db->prepare("
    SELECT cu.*, u.name AS changed_by
    FROM complaint_updates cu
    LEFT JOIN users u ON cu.updated_by = u.user_id
    WHERE cu.complaint_id = ? ORDER BY cu.updated_at ASC
");
$histStmt->execute([$id]);
$history = $histStmt->fetchAll();

include __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-arrow-repeat me-2"></i>Update Status — Complaint #<?= $id ?></h4>
        <a href="<?= BASE_URL ?>/staff/dashboard.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Back to Dashboard</a>
    </div>
    <span class="status-badge status-<?= $complaint['status'] ?> fs-6">
        <?= ucfirst(str_replace('_',' ', $complaint['status'])) ?>
    </span>
</div>

<div class="row g-4">
    <!-- Left: Complaint details + Update form -->
    <div class="col-lg-8">
        <!-- Complaint info card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light fw-semibold">Complaint Details</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="text-muted small">Title</div>
                        <div class="fw-semibold"><?= htmlspecialchars($complaint['title']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Category</div>
                        <div class="fw-semibold"><?= htmlspecialchars($complaint['category']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Student</div>
                        <div><?= htmlspecialchars($complaint['student_name']) ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Room</div>
                        <div><?= htmlspecialchars($complaint['room_no'] ?? 'N/A') ?></div>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Priority</div>
                        <span class="priority-badge priority-<?= $complaint['priority'] ?>"><?= ucfirst($complaint['priority']) ?></span>
                    </div>
                    <div class="col-sm-6">
                        <div class="text-muted small">Submitted</div>
                        <div><?= date('d M Y, H:i', strtotime($complaint['created_at'])) ?></div>
                    </div>
                    <div class="col-12">
                        <div class="text-muted small">Description</div>
                        <p class="mt-1 mb-0" style="white-space:pre-wrap"><?= htmlspecialchars($complaint['description']) ?></p>
                    </div>
                    <?php if ($complaint['image_path']): ?>
                    <div class="col-12">
                        <div class="text-muted small mb-1">Attached Photo</div>
                        <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($complaint['image_path']) ?>"
                             class="img-fluid rounded shadow-sm" style="max-height:250px" alt="Complaint photo">
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Update form -->
        <?php if (!empty($allowed)): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-primary text-white fw-semibold"><i class="bi bi-pencil-square me-2"></i>Update Status</div>
            <div class="card-body">
                <form method="POST" data-confirm-status-change="Are you sure you want to update the complaint status?">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">New Status</label>
                        <select name="new_status" class="form-select" required>
                            <option value="">-- Select new status --</option>
                            <?php foreach ($allowed as $s): ?>
                            <option value="<?= $s ?>"><?= ucfirst(str_replace('_',' ',$s)) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Remarks <span class="text-danger">*</span></label>
                        <textarea name="remarks" class="form-control" rows="4" required
                                  placeholder="Describe the action taken or reason for status change..."
                                  data-char-limit="500"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-check-circle me-2"></i>Update Status
                    </button>
                </form>
            </div>
        </div>
        <?php else: ?>
        <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            This complaint is <strong><?= ucfirst(str_replace('_',' ',$complaint['status'])) ?></strong> — no further status updates available.
        </div>
        <?php endif; ?>
    </div>

    <!-- Right: History timeline -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light fw-semibold"><i class="bi bi-clock-history me-2"></i>Status History</div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                <p class="text-muted small">No history yet.</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $h): ?>
                    <div class="timeline-item <?= $h['new_status'] ?>">
                        <div class="fw-semibold small">
                            <span class="status-badge status-<?= $h['new_status'] ?> me-1">
                                <?= ucfirst(str_replace('_',' ',$h['new_status'])) ?>
                            </span>
                        </div>
                        <?php if ($h['remarks']): ?>
                        <p class="text-muted small mb-1 mt-1"><?= htmlspecialchars($h['remarks']) ?></p>
                        <?php endif; ?>
                        <div class="text-muted" style="font-size:.75rem">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($h['changed_by'] ?? 'System') ?>
                            &nbsp;·&nbsp;<?= date('d M, H:i', strtotime($h['updated_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
