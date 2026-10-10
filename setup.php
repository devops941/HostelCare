<?php
/**
 * HostelCare - One-time Database Setup Script
 * Browse to http://localhost/HostelCare/setup.php to initialize the database.
 * DELETE or protect this file after setup is complete.
 */

$DB_HOST = getenv('DB_HOST') ?: 'localhost';
$DB_USER = getenv('DB_USER') ?: 'root';
$DB_PASS = getenv('DB_PASS') ?: '';
$DB_NAME = getenv('DB_NAME') ?: 'hostelcare';

$messages = [];
$success  = true;

try {
    // Connect directly to the database
    $pdo = new PDO("mysql:host=$DB_HOST;dbname=$DB_NAME;charset=utf8mb4", $DB_USER, $DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $messages[] = ['ok', 'Connected to Database successfully.'];

    // Create tables
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        user_id       INT AUTO_INCREMENT PRIMARY KEY,
        name          VARCHAR(100) NOT NULL,
        email         VARCHAR(100) NOT NULL UNIQUE,
        password_hash VARCHAR(255) NOT NULL,
        role          ENUM('admin','staff','student') NOT NULL DEFAULT 'student',
        room_no       VARCHAR(10)  DEFAULT NULL,
        phone         VARCHAR(15)  DEFAULT NULL,
        created_at    DATETIME     DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB");
    $messages[] = ['ok', 'Table <b>users</b> ready.'];

    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        category_id       INT AUTO_INCREMENT PRIMARY KEY,
        name              VARCHAR(50) NOT NULL,
        assigned_staff_id INT DEFAULT NULL,
        FOREIGN KEY (assigned_staff_id) REFERENCES users(user_id) ON DELETE SET NULL
    ) ENGINE=InnoDB");
    $messages[] = ['ok', 'Table <b>categories</b> ready.'];

    $pdo->exec("CREATE TABLE IF NOT EXISTS complaints (
        complaint_id INT AUTO_INCREMENT PRIMARY KEY,
        student_id   INT NOT NULL,
        category_id  INT NOT NULL,
        title        VARCHAR(150) NOT NULL,
        description  TEXT NOT NULL,
        image_path   VARCHAR(255) DEFAULT NULL,
        priority     ENUM('low','medium','high') NOT NULL DEFAULT 'medium',
        status       ENUM('pending','assigned','in_progress','resolved','closed') NOT NULL DEFAULT 'pending',
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (student_id)  REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE RESTRICT
    ) ENGINE=InnoDB");
    $messages[] = ['ok', 'Table <b>complaints</b> ready.'];

    $pdo->exec("CREATE TABLE IF NOT EXISTS complaint_updates (
        update_id    INT AUTO_INCREMENT PRIMARY KEY,
        complaint_id INT NOT NULL,
        updated_by   INT NOT NULL,
        old_status   VARCHAR(20) DEFAULT NULL,
        new_status   VARCHAR(20) NOT NULL,
        remarks      TEXT DEFAULT NULL,
        updated_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE,
        FOREIGN KEY (updated_by)   REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $messages[] = ['ok', 'Table <b>complaint_updates</b> ready.'];

    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        feedback_id  INT AUTO_INCREMENT PRIMARY KEY,
        complaint_id INT NOT NULL UNIQUE,
        rating       TINYINT NOT NULL,
        comments     TEXT DEFAULT NULL,
        created_at   DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (complaint_id) REFERENCES complaints(complaint_id) ON DELETE CASCADE
    ) ENGINE=InnoDB");
    $messages[] = ['ok', 'Table <b>feedback</b> ready.'];

    // Seed users only if users table is empty
    $count = $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
    if ((int)$count === 0) {
        $admin_hash   = password_hash('admin123',   PASSWORD_BCRYPT);
        $staff_hash   = password_hash('staff123',   PASSWORD_BCRYPT);
        $student_hash = password_hash('student123', PASSWORD_BCRYPT);

        $pdo->prepare('INSERT INTO users (name,email,password_hash,role,phone) VALUES (?,?,?,?,?)')
            ->execute(['Chief Warden', 'admin@hostel.com', $admin_hash, 'admin', '9000000001']);

        $staff = [
            ['Ravi Kumar',   'electrical@hostel.com', '9000000002'],
            ['Suresh Patel', 'plumbing@hostel.com',   '9000000003'],
            ['Meena Devi',   'mess@hostel.com',        '9000000004'],
            ['Arjun Singh',  'wifi@hostel.com',        '9000000005'],
            ['Priya Nair',   'cleaning@hostel.com',    '9000000006'],
        ];
        $ins = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,phone) VALUES (?,?,?,\'staff\',?)');
        foreach ($staff as $s) $ins->execute([$s[0], $s[1], $staff_hash, $s[2]]);

        $students = [
            ['Amit Sharma', 'amit@student.com',  'A101', '9111111001'],
            ['Priya Verma', 'priya@student.com', 'B205', '9111111002'],
            ['Rahul Gupta', 'rahul@student.com', 'C310', '9111111003'],
        ];
        $ins2 = $pdo->prepare('INSERT INTO users (name,email,password_hash,role,room_no,phone) VALUES (?,?,?,\'student\',?,?)');
        foreach ($students as $st) $ins2->execute([$st[0], $st[1], $student_hash, $st[2], $st[3]]);

        $messages[] = ['ok', 'Seeded <b>9 users</b>: 1 admin, 5 staff, 3 students.'];
    } else {
        $messages[] = ['info', "Users table already has $count records — skipping user seed."];
    }

    // Seed categories if empty
    $catCount = $pdo->query('SELECT COUNT(*) FROM categories')->fetchColumn();
    if ((int)$catCount === 0) {
        $cats = ['Electrical'=>2,'Plumbing'=>3,'Mess / Food'=>4,'Wi-Fi / Network'=>5,'Cleaning'=>6,'Other'=>null];
        $insCat = $pdo->prepare('INSERT INTO categories (name,assigned_staff_id) VALUES (?,?)');
        foreach ($cats as $name => $sid) $insCat->execute([$name, $sid]);
        $messages[] = ['ok', 'Seeded <b>6 categories</b>.'];
    } else {
        $messages[] = ['info', 'Categories already seeded — skipping.'];
    }

    // Seed sample complaints if empty
    $cmpCount = $pdo->query('SELECT COUNT(*) FROM complaints')->fetchColumn();
    if ((int)$cmpCount === 0) {
        $sampleComplaints = [
            [7, 1, 'Fan not working in Room A101',    'Ceiling fan stopped working, makes clicking sound.', 'high',   'in_progress', '-3 days'],
            [8, 4, 'Wi-Fi slow in B-block',            'Internet extremely slow since Monday.',               'medium', 'assigned',    '-5 days'],
            [9, 2, 'Bathroom tap leaking',             'Tap leaks constantly wasting water.',                 'high',   'pending',     '-2 days'],
            [7, 3, 'Food quality poor',                'Mess food quality has degraded significantly.',       'low',    'resolved',    '-10 days'],
            [8, 5, 'Common area not cleaned',          '2nd floor corridor not cleaned for 3 days.',          'medium', 'pending',     '-55 hours'],
        ];
        $insCmp = $pdo->prepare('INSERT INTO complaints (student_id,category_id,title,description,priority,status,created_at,updated_at) VALUES (?,?,?,?,?,?,DATE_SUB(NOW(),INTERVAL ? SECOND),DATE_SUB(NOW(),INTERVAL ? SECOND))');
        foreach ($sampleComplaints as $cmp) {
            $secs = match($cmp[6]) {
                '-3 days'   => 3 * 86400,
                '-5 days'   => 5 * 86400,
                '-2 days'   => 2 * 86400,
                '-10 days'  => 10 * 86400,
                '-55 hours' => 55 * 3600,
                default     => 3600,
            };
            $insCmp->execute([$cmp[0], $cmp[1], $cmp[2], $cmp[3], $cmp[4], $cmp[5], $secs, $secs]);
        }
        $messages[] = ['ok', 'Seeded <b>5 sample complaints</b>.'];

        // Seed some complaint_updates
        $updData = [
            [1, 2, 'pending','assigned',   'Assigned to electrician.'],
            [1, 2, 'assigned','in_progress','Visited room, ordering part.'],
            [2, 5, 'pending','assigned',   'Logged with ISP, checking router.'],
            [4, 4, 'pending','assigned',   'Feedback noted.'],
            [4, 4, 'assigned','in_progress','Menu revision in progress.'],
            [4, 4, 'in_progress','resolved','Issue resolved. New menu implemented.'],
        ];
        $insUpd = $pdo->prepare('INSERT INTO complaint_updates (complaint_id,updated_by,old_status,new_status,remarks) VALUES (?,?,?,?,?)');
        foreach ($updData as $u) $insUpd->execute($u);

        // Seed feedback for complaint 4
        $pdo->prepare('INSERT INTO feedback (complaint_id,rating,comments) VALUES (?,?,?)')->execute([4, 4, 'Food quality improved. Thank you!']);
        $messages[] = ['ok', 'Seeded complaint updates and feedback.'];
    } else {
        $messages[] = ['info', 'Complaints already seeded — skipping.'];
    }

} catch (Exception $e) {
    $success    = false;
    $messages[] = ['error', 'Error: ' . htmlspecialchars($e->getMessage())];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HostelCare Setup</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:700px">
    <div class="text-center mb-4">
        <i class="bi bi-shield-check-fill text-primary" style="font-size:3rem"></i>
        <h2 class="fw-bold mt-2">HostelCare Setup</h2>
        <p class="text-muted">Database initialization and seeding</p>
    </div>

    <div class="card shadow-sm border-0">
        <div class="card-body p-4">
            <?php foreach ($messages as [$type, $msg]): ?>
            <div class="d-flex align-items-start gap-2 mb-2">
                <i class="bi bi-<?= $type==='ok'?'check-circle-fill text-success':($type==='info'?'info-circle-fill text-info':'x-circle-fill text-danger') ?> mt-1"></i>
                <span><?= $msg ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success mt-4 shadow-sm">
        <h5 class="fw-bold"><i class="bi bi-check-circle-fill me-2"></i>Setup Complete!</h5>
        <hr>
        <p class="mb-2"><strong>Login credentials:</strong></p>
        <table class="table table-sm table-bordered mb-3">
            <thead class="table-light"><tr><th>Role</th><th>Email</th><th>Password</th></tr></thead>
            <tbody>
                <tr><td><span class="badge bg-danger">Admin</span></td><td>admin@hostel.com</td><td><code>admin123</code></td></tr>
                <tr><td><span class="badge bg-info">Staff (Electrical)</span></td><td>electrical@hostel.com</td><td><code>staff123</code></td></tr>
                <tr><td><span class="badge bg-info">Staff (Plumbing)</span></td><td>plumbing@hostel.com</td><td><code>staff123</code></td></tr>
                <tr><td><span class="badge bg-info">Staff (Mess)</span></td><td>mess@hostel.com</td><td><code>staff123</code></td></tr>
                <tr><td><span class="badge bg-info">Staff (Wi-Fi)</span></td><td>wifi@hostel.com</td><td><code>staff123</code></td></tr>
                <tr><td><span class="badge bg-info">Staff (Cleaning)</span></td><td>cleaning@hostel.com</td><td><code>staff123</code></td></tr>
                <tr><td><span class="badge bg-success">Student</span></td><td>amit@student.com</td><td><code>student123</code></td></tr>
                <tr><td><span class="badge bg-success">Student</span></td><td>priya@student.com</td><td><code>student123</code></td></tr>
                <tr><td><span class="badge bg-success">Student</span></td><td>rahul@student.com</td><td><code>student123</code></td></tr>
            </tbody>
        </table>
        <div class="alert alert-warning py-2 mb-3">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <strong>Security:</strong> Delete or rename <code>setup.php</code> after setup to prevent unauthorized re-runs.
        </div>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-primary">
            <i class="bi bi-box-arrow-in-right me-2"></i>Go to Login
        </a>
    </div>
    <?php else: ?>
    <div class="alert alert-danger mt-4">
        <h5 class="fw-bold"><i class="bi bi-x-circle-fill me-2"></i>Setup Failed</h5>
        <p>Check your XAMPP MySQL is running and <code>config/db.php</code> credentials are correct.</p>
    </div>
    <?php endif; ?>
</div>
</body>
</html>
