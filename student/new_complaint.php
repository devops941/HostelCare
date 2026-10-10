<?php
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
requireRole('student');

$pageTitle  = 'Submit New Complaint';
$db         = getDB();
$errors     = [];
$success    = false;

// Load categories for dropdown
$cats = $db->query('SELECT category_id, name FROM categories ORDER BY name')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $category_id = (int)($_POST['category_id'] ?? 0);
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $priority    = $_POST['priority'] ?? 'medium';

    if (!$category_id)                  $errors[] = 'Please select a category.';
    if (!$title)                         $errors[] = 'Title is required.';
    if (strlen($title) > 150)            $errors[] = 'Title must be under 150 characters.';
    if (!$description)                   $errors[] = 'Description is required.';
    if (!in_array($priority, ['low','medium','high'])) $priority = 'medium';

    $image_path = null;
    if (isset($_FILES['photo']) && $_FILES['photo']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error.';
        } else {
            $tmp  = $_FILES['photo']['tmp_name'];
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime  = finfo_file($finfo, $tmp);
            finfo_close($finfo);
            if (!in_array($mime, ['image/jpeg','image/png','image/jpg'])) {
                $errors[] = 'Only JPG/PNG images are allowed.';
            } elseif ($_FILES['photo']['size'] > 2 * 1024 * 1024) {
                $errors[] = 'Image must be under 2 MB.';
            } else {
                $ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
                $filename = uniqid('img_', true) . '.' . $ext;
                $uploadDir = __DIR__ . '/../uploads/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                if (move_uploaded_file($tmp, $uploadDir . $filename)) {
                    $image_path = $filename;
                } else {
                    $errors[] = 'Failed to save image.';
                }
            }
        }
    }

    if (empty($errors)) {
        // Determine status and auto-assign
        $catStmt = $db->prepare('SELECT assigned_staff_id FROM categories WHERE category_id = ?');
        $catStmt->execute([$category_id]);
        $cat = $catStmt->fetch();

        $status = $cat && $cat['assigned_staff_id'] ? 'assigned' : 'pending';

        $ins = $db->prepare('INSERT INTO complaints (student_id,category_id,title,description,image_path,priority,status) VALUES (?,?,?,?,?,?,?)');
        $ins->execute([$_SESSION['user_id'], $category_id, $title, $description, $image_path, $priority, $status]);
        $new_id = (int)$db->lastInsertId();

        // Record auto-assignment in audit trail
        if ($status === 'assigned') {
            $upd = $db->prepare('INSERT INTO complaint_updates (complaint_id,updated_by,old_status,new_status,remarks) VALUES (?,?,?,?,?)');
            $upd->execute([$new_id, $_SESSION['user_id'], 'pending', 'assigned', 'Auto-assigned to staff on submission.']);
        }

        $_SESSION['flash_success'] = 'Complaint #' . $new_id . ' submitted successfully!';
        header('Location: ' . BASE_URL . '/student/my_complaints.php');
        exit;
    }
}

include __DIR__ . '/../includes/header.php';
?>

<div class="page-header mb-4">
    <h4><i class="bi bi-plus-circle me-2"></i>Submit a New Complaint</h4>
    <p>Fill in the form below. Your complaint will be assigned automatically.</p>
</div>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <?php if ($errors): ?>
                <div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <ul class="mb-0">
                        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <form method="POST" enctype="multipart/form-data" novalidate>
                    <!-- Category -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                        <select name="category_id" class="form-select" required>
                            <option value="">-- Select a category --</option>
                            <?php foreach ($cats as $c): ?>
                            <option value="<?= $c['category_id'] ?>" <?= (($_POST['category_id'] ?? '') == $c['category_id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Title -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" maxlength="150"
                               value="<?= htmlspecialchars($_POST['title'] ?? '') ?>"
                               placeholder="Brief description of the issue" required>
                        <div class="form-text">Max 150 characters.</div>
                    </div>

                    <!-- Description -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Description <span class="text-danger">*</span></label>
                        <textarea name="description" class="form-control" rows="5" required
                                  data-char-limit="1000" placeholder="Describe the issue in detail..."><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                    </div>

                    <!-- Priority -->
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Priority</label>
                        <div class="d-flex gap-3">
                            <?php foreach (['low'=>'Low','medium'=>'Medium','high'=>'High'] as $val => $lbl): ?>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="priority" id="p_<?= $val ?>"
                                       value="<?= $val ?>" <?= (($_POST['priority'] ?? 'medium') === $val) ? 'checked' : '' ?>>
                                <label class="form-check-label priority-badge priority-<?= $val ?>" for="p_<?= $val ?>">
                                    <?= $lbl ?>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Photo upload -->
                    <div class="mb-4">
                        <label class="form-label fw-semibold">Attach Photo <span class="text-muted small">(optional, JPG/PNG max 2MB)</span></label>
                        <input type="file" name="photo" id="photo" class="form-control" accept="image/jpeg,image/png">
                        <div id="imagePreview" class="mt-2"></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-send me-2"></i>Submit Complaint
                        </button>
                        <a href="<?= BASE_URL ?>/student/dashboard.php" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('photo').addEventListener('change', function(e) {
    const file = e.target.files[0];
    const preview = document.getElementById('imagePreview');
    if (!file) { preview.innerHTML = ''; return; }
    const reader = new FileReader();
    reader.onload = ev => {
        preview.innerHTML = `<img src="${ev.target.result}" class="img-thumbnail mt-1" style="max-height:180px">
                             <small class="d-block text-muted">${file.name} (${(file.size/1024).toFixed(1)} KB)</small>`;
    };
    reader.readAsDataURL(file);
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
