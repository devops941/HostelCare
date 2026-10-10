<?php
session_start();
if (isset($_SESSION['user_id'])) {
    $role = $_SESSION['role'] ?? 'student';
    if ($role === 'admin')       header('Location: ' . BASE_URL . '/admin/dashboard.php');
    elseif ($role === 'staff')   header('Location: ' . BASE_URL . '/staff/dashboard.php');
    else                         header('Location: ' . BASE_URL . '/student/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HostelCare — Smart Hostel Complaint System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <style>
      body{margin:0;font-family:'Inter',system-ui,sans-serif}
      .lp-hero{min-height:100vh;background:linear-gradient(135deg,#0f172a 0%,#1e1b4b 60%,#312e81 100%);display:flex;align-items:center;justify-content:center;text-align:center;padding:40px 24px;position:relative;overflow:hidden}
      .lp-hero::before{content:'';position:absolute;width:700px;height:700px;border-radius:50%;background:radial-gradient(circle,rgba(79,70,229,.25),transparent 70%);top:-250px;right:-250px}
      .lp-hero::after{content:'';position:absolute;width:500px;height:500px;border-radius:50%;background:radial-gradient(circle,rgba(139,92,246,.2),transparent 70%);bottom:-200px;left:-150px}
      .hero-badge{display:inline-flex;align-items:center;gap:8px;background:rgba(255,255,255,.1);border:1px solid rgba(255,255,255,.2);color:rgba(255,255,255,.9);padding:6px 16px;border-radius:99px;font-size:13px;font-weight:500;margin-bottom:24px;backdrop-filter:blur(10px)}
      .hero-icon{width:72px;height:72px;background:linear-gradient(135deg,#4f46e5,#8b5cf6);border-radius:20px;display:flex;align-items:center;justify-content:center;font-size:32px;color:#fff;margin:0 auto 24px;box-shadow:0 12px 40px rgba(79,70,229,.5)}
      .hero-title{font-size:clamp(2.2rem,5vw,3.8rem);font-weight:800;color:#fff;letter-spacing:-1px;margin-bottom:16px;line-height:1.1}
      .hero-title span{background:linear-gradient(90deg,#818cf8,#c084fc);-webkit-background-clip:text;-webkit-text-fill-color:transparent;background-clip:text}
      .hero-sub{font-size:clamp(1rem,2vw,1.2rem);color:rgba(255,255,255,.7);max-width:520px;margin:0 auto 36px;line-height:1.6}
      .hero-btns{display:flex;gap:14px;justify-content:center;flex-wrap:wrap}
      .btn-hero-primary{background:#fff;color:#4f46e5;padding:12px 32px;border-radius:10px;font-size:15px;font-weight:700;text-decoration:none;transition:all .25s;border:none}
      .btn-hero-primary:hover{background:#f1f5f9;transform:translateY(-3px);box-shadow:0 12px 30px rgba(0,0,0,.25)}
      .btn-hero-outline{background:rgba(255,255,255,.1);color:#fff;padding:12px 32px;border-radius:10px;font-size:15px;font-weight:600;text-decoration:none;transition:all .25s;border:1.5px solid rgba(255,255,255,.3);backdrop-filter:blur(10px)}
      .btn-hero-outline:hover{background:rgba(255,255,255,.2);transform:translateY(-3px)}
      .lp-features{padding:80px 24px;background:#f8fafc}
      .lp-features h2{text-align:center;font-size:2rem;font-weight:800;color:#0f172a;margin-bottom:8px}
      .lp-features p.lead{text-align:center;color:#64748b;margin-bottom:48px;font-size:15px}
      .features-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(260px,1fr));gap:24px;max-width:1000px;margin:0 auto}
      .feat-card{background:#fff;border-radius:18px;padding:28px;border:1px solid #e2e8f0;box-shadow:0 4px 12px rgba(0,0,0,.06);transition:all .25s}
      .feat-card:hover{transform:translateY(-6px);box-shadow:0 16px 40px rgba(0,0,0,.1)}
      .feat-icon{width:48px;height:48px;border-radius:12px;display:flex;align-items:center;justify-content:center;font-size:22px;color:#fff;margin-bottom:16px}
      .feat-card h5{font-size:15px;font-weight:700;color:#0f172a;margin-bottom:8px}
      .feat-card p{font-size:13.5px;color:#64748b;line-height:1.6;margin:0}
      .lp-steps{padding:80px 24px;background:#fff}
      .lp-steps h2{text-align:center;font-size:2rem;font-weight:800;color:#0f172a;margin-bottom:48px}
      .steps-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));gap:24px;max-width:860px;margin:0 auto}
      .step-box{text-align:center;padding:28px 20px}
      .step-num{width:52px;height:52px;background:linear-gradient(135deg,#4f46e5,#8b5cf6);color:#fff;border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:800;margin:0 auto 16px;box-shadow:0 6px 20px rgba(79,70,229,.35)}
      .step-box h5{font-size:15px;font-weight:700;color:#0f172a;margin-bottom:8px}
      .step-box p{font-size:13.5px;color:#64748b;line-height:1.6;margin:0}
      .lp-footer{background:#0f172a;color:rgba(255,255,255,.6);text-align:center;padding:24px;font-size:13px}
    </style>
</head>
<body>

<section class="lp-hero">
  <div style="position:relative;z-index:1;max-width:700px">
    <div class="hero-badge"><i class="bi bi-shield-check-fill" style="color:#818cf8"></i> Trusted Hostel Management</div>
    <div class="hero-icon"><i class="bi bi-shield-check-fill"></i></div>
    <h1 class="hero-title">HostelCare<br><span>Smart Complaint System</span></h1>
    <p class="hero-sub">Submit, track, and resolve hostel complaints with real-time updates, auto-assignment, and complete transparency.</p>
    <div class="hero-btns">
      <a href="<?= BASE_URL ?>/login.php" class="btn-hero-primary"><i class="bi bi-box-arrow-in-right" style="margin-right:6px"></i>Login</a>
      <a href="<?= BASE_URL ?>/register.php" class="btn-hero-outline"><i class="bi bi-person-plus" style="margin-right:6px"></i>Register as Student</a>
    </div>
  </div>
</section>

<section class="lp-features">
  <h2>Why HostelCare?</h2>
  <p class="lead">Everything you need for efficient hostel management in one place.</p>
  <div class="features-grid">
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#4f46e5,#6366f1)"><i class="bi bi-lightning-charge-fill"></i></div>
      <h5>Instant Tracking</h5>
      <p>Submit complaints and track their status in real-time with complete audit history.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#10b981,#34d399)"><i class="bi bi-person-check-fill"></i></div>
      <h5>Auto-Assignment</h5>
      <p>Complaints are automatically routed to the right staff based on category.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#f59e0b,#fbbf24)"><i class="bi bi-exclamation-triangle-fill"></i></div>
      <h5>Escalation Alerts</h5>
      <p>Automatic escalation for unresolved complaints exceeding 48 hours.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#8b5cf6,#a78bfa)"><i class="bi bi-bar-chart-fill"></i></div>
      <h5>Rich Reports</h5>
      <p>Analytics dashboard with charts for complaint trends, resolution rates, and more.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#06b6d4,#22d3ee)"><i class="bi bi-star-fill"></i></div>
      <h5>Feedback System</h5>
      <p>Students can rate resolved complaints to ensure quality service delivery.</p>
    </div>
    <div class="feat-card">
      <div class="feat-icon" style="background:linear-gradient(135deg,#ef4444,#f87171)"><i class="bi bi-lock-fill"></i></div>
      <h5>Secure & Private</h5>
      <p>Role-based access control keeps data secure for admin, staff, and students.</p>
    </div>
  </div>
</section>

<section class="lp-steps">
  <h2>How It Works</h2>
  <div class="steps-grid">
    <div class="step-box">
      <div class="step-num">1</div>
      <h5>Register & Login</h5>
      <p>Create your student account with your room number and contact details.</p>
    </div>
    <div class="step-box">
      <div class="step-num">2</div>
      <h5>Submit Complaint</h5>
      <p>Describe your issue, select a category, set priority, and attach a photo.</p>
    </div>
    <div class="step-box">
      <div class="step-num">3</div>
      <h5>Track Progress</h5>
      <p>Watch real-time status updates as staff work on your complaint.</p>
    </div>
    <div class="step-box">
      <div class="step-num">4</div>
      <h5>Rate & Close</h5>
      <p>Give feedback once resolved to help us improve service quality.</p>
    </div>
  </div>
</section>

<footer class="lp-footer">
  &copy; 2026 HostelCare &mdash; Smart Hostel Complaint Tracking System
</footer>

</body>
</html>
