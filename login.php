<?php
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) { redirectByRole(); }

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (!$email || !$password) {
        $error = 'Email and password are required.';
    } else {
        $db   = getDB();
        $stmt = $db->prepare('SELECT user_id, name, email, password_hash, role, room_no, phone FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['email']   = $user['email'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['room_no'] = $user['room_no'];
            $_SESSION['phone']   = $user['phone'];
            redirectByRole();
        } else {
            $error = 'Invalid email or password.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | HostelCare</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body class="auth-page">

<div class="auth-card">
    <div class="auth-logo"><i class="bi bi-shield-check-fill"></i></div>
    <h2 style="font-size:22px;font-weight:800;text-align:center;margin-bottom:4px">Welcome Back</h2>
    <p style="text-align:center;color:#64748b;font-size:13px;margin-bottom:24px">Sign in to <strong>HostelCare</strong></p>

    <?php if ($error): ?>
    <div class="alert alert-danger" style="margin-bottom:16px">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($error) ?>
        <button class="btn-close" onclick="this.parentElement.remove()">×</button>
    </div>
    <?php endif; ?>

    <form method="POST" novalidate>
        <div style="margin-bottom:14px">
            <label class="form-label">Email Address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email" name="email" class="form-control"
                       placeholder="you@hostel.com" required autofocus
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
            </div>
        </div>
        <div style="margin-bottom:20px">
            <label class="form-label">Password</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password" name="password" class="form-control"
                       placeholder="Enter your password" required>
            </div>
        </div>
        <button type="submit" class="btn btn-primary w-100" style="padding:10px;font-size:14px">
            <i class="bi bi-box-arrow-in-right"></i> Sign In
        </button>
    </form>

    <hr style="border-color:#e2e8f0;margin:20px 0">

    <p style="text-align:center;font-size:12px;color:#64748b;margin-bottom:10px">Click any credential to auto-fill:</p>
    <div style="display:flex;gap:6px;flex-wrap:wrap;justify-content:center;margin-bottom:16px">
        <button type="button" onclick="fillCreds('admin@hostel.com','admin123')"
            style="background:#fee2e2;color:#991b1b;padding:5px 12px;border-radius:99px;font-size:11px;font-weight:600;border:1.5px solid #fecaca;cursor:pointer;transition:all .2s"
            onmouseover="this.style.background='#ef4444';this.style.color='#fff'" onmouseout="this.style.background='#fee2e2';this.style.color='#991b1b'">
            <i class="bi bi-shield-fill"></i> Admin
        </button>
        <button type="button" onclick="fillCreds('electrical@hostel.com','staff123')"
            style="background:#dbeafe;color:#1e40af;padding:5px 12px;border-radius:99px;font-size:11px;font-weight:600;border:1.5px solid #bfdbfe;cursor:pointer;transition:all .2s"
            onmouseover="this.style.background='#3b82f6';this.style.color='#fff'" onmouseout="this.style.background='#dbeafe';this.style.color='#1e40af'">
            <i class="bi bi-tools"></i> Staff
        </button>
        <button type="button" onclick="fillCreds('john.doe@student.com','student123')"
            style="background:#d1fae5;color:#065f46;padding:5px 12px;border-radius:99px;font-size:11px;font-weight:600;border:1.5px solid #a7f3d0;cursor:pointer;transition:all .2s"
            onmouseover="this.style.background='#10b981';this.style.color='#fff'" onmouseout="this.style.background='#d1fae5';this.style.color='#065f46'">
            <i class="bi bi-mortarboard-fill"></i> Student
        </button>
    </div>
    <p style="text-align:center;font-size:13px;color:#64748b;margin:0">
        New student? <a href="<?= BASE_URL ?>/register.php" style="color:#4f46e5;font-weight:600">Register here</a>
    </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function fillCreds(email, password) {
    document.querySelector('input[name="email"]').value = email;
    document.querySelector('input[name="password"]').value = password;
    document.querySelector('input[name="email"]').focus();
}
</script>
</body>
</html>
