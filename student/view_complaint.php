<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pageTitle  = 'View Complaint';
$db         = getDB();
$student_id = $_SESSION['user_id'];
$id         = (int)($_GET['id'] ?? 0);

if (!$id) {
    $_SESSION['flash_error'] = 'Invalid complaint ID.';
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
}

// Fetch complaint (verify ownership)
$stmt = $db->prepare("
    SELECT c.*, cat.name AS category, u.name AS student_name
    FROM complaints c
    LEFT JOIN categories cat ON c.category_id = cat.category_id
    LEFT JOIN users u ON c.student_id = u.user_id
    WHERE c.complaint_id = ? AND c.student_id = ?
");
$stmt->execute([$id, $student_id]);
$complaint = $stmt->fetch();

if (!$complaint) {
    $_SESSION['flash_error'] = 'Complaint not found or access denied.';
    header('Location: ' . BASE_URL . '/student/my_complaints.php');
    exit;
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

// Fetch existing feedback
$fbStmt = $db->prepare('SELECT * FROM feedback WHERE complaint_id = ?');
$fbStmt->execute([$id]);
$feedback = $fbStmt->fetch();

// Handle feedback submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    $rating   = (int)($_POST['rating']   ?? 0);
    $comments = trim($_POST['comments']  ?? '');
    if ($rating < 1 || $rating > 5) {
        $_SESSION['flash_error'] = 'Rating must be between 1 and 5.';
    } elseif ($feedback) {
        $_SESSION['flash_error'] = 'You have already submitted feedback for this complaint.';
    } else {
        $ins = $db->prepare('INSERT INTO feedback (complaint_id, rating, comments) VALUES (?,?,?)');
        $ins->execute([$id, $rating, $comments]);
        // Move to closed
        $upd = $db->prepare("UPDATE complaints SET status='closed', updated_at=NOW() WHERE complaint_id=?");
        $upd->execute([$id]);
        $close = $db->prepare('INSERT INTO complaint_updates (complaint_id,updated_by,old_status,new_status,remarks) VALUES (?,?,?,?,?)');
        $close->execute([$id, $student_id, 'resolved', 'closed', 'Student submitted feedback and closed the complaint.']);
        $_SESSION['flash_success'] = 'Thank you for your feedback! Complaint closed.';
        header('Location: ' . BASE_URL . '/student/view_complaint.php?id=' . $id);
        exit;
    }
}

include __DIR__ . '/../includes/header.php';

$statusColors = ['pending'=>'warning','assigned'=>'primary','in_progress'=>'purple','resolved'=>'success','closed'=>'secondary'];
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-1"><i class="bi bi-file-text me-2"></i>Complaint #<?= $complaint['complaint_id'] ?></h4>
        <a href="<?= BASE_URL ?>/student/my_complaints.php" class="text-muted small"><i class="bi bi-arrow-left me-1"></i>Back to My Complaints</a>
    </div>
    <span class="status-badge status-<?= $complaint['status'] ?> fs-6">
        <?= ucfirst(str_replace('_',' ', $complaint['status'])) ?>
    </span>
</div>

<div class="row g-4">
    <!-- Complaint details -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light fw-semibold">Complaint Details</div>
            <div class="card-body">
                <h5 class="fw-bold"><?= htmlspecialchars($complaint['title']) ?></h5>
                <div class="d-flex gap-2 mb-3 flex-wrap">
                    <span class="badge bg-light text-dark border"><i class="bi bi-tag me-1"></i><?= htmlspecialchars($complaint['category']) ?></span>
                    <span class="priority-badge priority-<?= $complaint['priority'] ?>"><?= ucfirst($complaint['priority']) ?> Priority</span>
                </div>
                <p class="text-muted" style="white-space:pre-wrap"><?= htmlspecialchars($complaint['description']) ?></p>
                <?php if ($complaint['image_path']): ?>
                <div class="mt-3">
                    <p class="fw-semibold mb-1">Attached Photo:</p>
                    <img src="<?= BASE_URL ?>/uploads/<?= htmlspecialchars($complaint['image_path']) ?>"
                         class="img-fluid rounded shadow-sm" style="max-height:300px" alt="Complaint photo">
                </div>
                <?php endif; ?>
            </div>
            <div class="card-footer bg-light text-muted small">
                Submitted: <?= date('d M Y, H:i', strtotime($complaint['created_at'])) ?> &nbsp;&middot;&nbsp;
                Last updated: <?= date('d M Y, H:i', strtotime($complaint['updated_at'])) ?>
            </div>
        </div>

        <!-- Feedback section -->
        <?php if ($complaint['status'] === 'resolved' && !$feedback): ?>
        <div class="card border-success border-0 shadow-sm">
            <div class="card-header bg-success text-white fw-semibold"><i class="bi bi-star me-2"></i>Rate this Resolution</div>
            <div class="card-body">
                <p class="text-muted mb-3">Your complaint is resolved. Please rate the service and close it.</p>
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Rating (1–5 stars)</label>
                        <div class="d-flex gap-1 fs-2" id="starRating">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="bi bi-star star-icon" data-value="<?= $i ?>" style="cursor:pointer;color:#d1d5db"></i>
                            <?php endfor; ?>
                        </div>
                        <input type="hidden" name="rating" id="ratingInput" value="">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Comments (optional)</label>
                        <textarea name="comments" class="form-control" rows="3" placeholder="Any additional comments..."></textarea>
                    </div>
                    <button type="submit" name="submit_feedback" class="btn btn-success">
                        <i class="bi bi-check-circle me-2"></i>Submit Feedback & Close
                    </button>
                </form>
            </div>
        </div>
        <?php elseif ($feedback): ?>
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light fw-semibold"><i class="bi bi-star-fill text-warning me-2"></i>Your Feedback</div>
            <div class="card-body">
                <div class="d-flex gap-1 fs-3 mb-2">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <i class="bi bi-star<?= $i <= $feedback['rating'] ? '-fill' : '' ?>" style="color:<?= $i <= $feedback['rating'] ? '#fbbf24' : '#d1d5db' ?>"></i>
                    <?php endfor; ?>
                    <span class="fs-5 ms-2 fw-bold"><?= $feedback['rating'] ?>/5</span>
                </div>
                <?php if ($feedback['comments']): ?>
                <p class="text-muted mb-0"><?= htmlspecialchars($feedback['comments']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Status timeline -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-light fw-semibold"><i class="bi bi-clock-history me-2"></i>Status Timeline</div>
            <div class="card-body">
                <?php if (empty($history)): ?>
                <p class="text-muted small">No status changes yet.</p>
                <?php else: ?>
                <div class="timeline">
                    <?php foreach ($history as $h): ?>
                    <div class="timeline-item <?= $h['new_status'] ?>">
                        <div class="fw-semibold small">
                            <span class="status-badge status-<?= $h['new_status'] ?> me-1"><?= ucfirst(str_replace('_',' ',$h['new_status'])) ?></span>
                        </div>
                        <?php if ($h['remarks']): ?>
                        <p class="text-muted small mb-1 mt-1"><?= htmlspecialchars($h['remarks']) ?></p>
                        <?php endif; ?>
                        <div class="text-muted" style="font-size:.75rem">
                            <i class="bi bi-person me-1"></i><?= htmlspecialchars($h['changed_by'] ?? 'System') ?>
                            &nbsp;·&nbsp; <?= date('d M, H:i', strtotime($h['updated_at'])) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelectorAll('.star-icon').forEach(star => {
    star.addEventListener('click', function() {
        const val = parseInt(this.dataset.value);
        document.getElementById('ratingInput').value = val;
        document.querySelectorAll('.star-icon').forEach((s, i) => {
            s.classList.toggle('bi-star-fill', i < val);
            s.classList.toggle('bi-star', i >= val);
            s.style.color = i < val ? '#fbbf24' : '#d1d5db';
        });
    });
    star.addEventListener('mouseover', function() {
        const val = parseInt(this.dataset.value);
        document.querySelectorAll('.star-icon').forEach((s, i) => {
            s.style.color = i < val ? '#fbbf24' : '#d1d5db';
        });
    });
});
document.getElementById('starRating')?.addEventListener('mouseleave', function() {
    const val = parseInt(document.getElementById('ratingInput').value) || 0;
    document.querySelectorAll('.star-icon').forEach((s, i) => {
        s.style.color = i < val ? '#fbbf24' : '#d1d5db';
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
