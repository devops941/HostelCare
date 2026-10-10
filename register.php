<?php
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { redirectByRole(); }

require_once __DIR__ . '/config/db.php';

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name']             ?? '');
    $email    = trim($_POST['email']            ?? '');
    $password =      $_POST['password']         ?? '';
    $confirm  =      $_POST['confirm_password'] ?? '';
    $room_no  = trim($_POST['room_no']          ?? '');
    $phone    = trim($_POST['phone']            ?? '');

    if (!$name)                                        $errors['name']     = 'Full name is required.';
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'Valid email is required.';
    if (strlen($password) < 6)                         $errors['password'] = 'Password must be at least 6 characters.';
    if ($password !== $confirm)                        $errors['confirm']  = 'Passwords do not match.';
    if (!$room_no)                                     $errors['room_no']  = 'Room number is required.';
    if (!preg_match('/^[0-9]{10}$/', $phone))          $errors['phone']    = 'Phone must be exactly 10 digits.';

    if (empty($errors['email'])) {
        $db   = getDB();
        $chk  = $db->prepare('SELECT user_id FROM users WHERE email = ?');
        $chk->execute([$email]);
        if ($chk->fetch()) $errors['email'] = 'This email is already registered.';
    }

    if (empty($errors)) {
        $db   = getDB();
        $hash = password_hash($password, PASSWORD_BCRYPT);
        $ins  = $db->prepare('INSERT INTO users (name, email, password_hash, role, room_no, phone) VALUES (?,?,?,\'student\',?,?)');
        $ins->execute([$name, $email, $hash, $room_no, $phone]);
        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | HostelCare</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card" style="max-width:460px">
    <div class="auth-logo"><i class="bi bi-person-plus-fill"></i></div>
    <h2 style="font-size:22px;font-weight:800;text-align:center;margin-bottom:4px">Student Registration</h2>
    <p style="text-align:center;color:#64748b;font-size:13px;margin-bottom:24px">Create your HostelCare account</p>

    <?php if ($success): ?>
    <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        Registration successful! <a href="<?= BASE_URL ?>/login.php" style="font-weight:600">Login now</a>
    </div>
    <?php else: ?>

    <form method="POST" novalidate>
        <div style="margin-bottom:14px">
            <label class="form-label">Full Name</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-person"></i></span>
                <input type="text" name="name" class="form-control <?= isset($errors['name'])?'is-invalid':'' ?>"
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" placeholder="Your full name" required>
            </div>
            <?php if (isset($errors['name'])): ?><div class="invalid-feedback" style="display:block"><?= $errors['name'] ?></div><?php endif; ?>
        </div>
        <div style="margin-bottom:14px">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control <?= isset($errors['email'])?'is-invalid':'' ?>"
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" placeholder="you@example.com" required>
            </div>
            <?php if (isset($errors['email'])): ?><div class="invalid-feedback" style="display:block"><?= $errors['email'] ?></div><?php endif; ?>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:14px">
            <div>
                <label class="form-label">Password</label>
                <input type="password" name="password" class="form-control <?= isset($errors['password'])?'is-invalid':'' ?>"
                       placeholder="Min 6 chars" required>
                <?php if (isset($errors['password'])): ?><div class="invalid-feedback" style="display:block"><?= $errors['password'] ?></div><?php endif; ?>
            </div>
            <div>
                <label class="form-label">Confirm Password</label>
                <input type="password" name="confirm_password" class="form-control <?= isset($errors['confirm'])?'is-invalid':'' ?>"
                       placeholder="Repeat password" required>
                <?php if (isset($errors['confirm'])): ?><div class="invalid-feedback" style="display:block"><?= $errors['confirm'] ?></div><?php endif; ?>
            </div>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:20px">
            <div>
                <label class="form-label">Room Number</label>
                <input type="text" name="room_no" class="form-control <?= isset($errors['room_no'])?'is-invalid':'' ?>"
                       value="<?= htmlspecialchars($_POST['room_no'] ?? '') ?>" placeholder="e.g. A101" required>
                <?php if (isset($errors['room_no'])): ?><div class="invalid-feedback" style="display:block"><?= $errors['room_no'] ?></div><?php endif; ?>
            </div>
            <div>
                <label class="form-label">Phone (10 digits)</label>
                <input type="text" name="phone" class="form-control <?= isset($errors['phone'])?'is-invalid':'' ?>"
                       value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" placeholder="9876543210" maxlength="10">
                <?php if (isset($errors['phone'])): ?><div class="invalid-feedback" style="display:block"><?= $errors['phone'] ?></div><?php endif; ?>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding:10px;font-size:14px">
            <i class="bi bi-person-check"></i> Register Account
        </button>
    </form>

    <?php endif; ?>

    <p style="text-align:center;font-size:13px;color:#64748b;margin-top:16px;margin-bottom:0">
        Already have an account? <a href="<?= BASE_URL ?>/login.php" style="color:#4f46e5;font-weight:600">Login here</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
