<?php
if (!function_exists('isLoggedIn')) {
    require_once __DIR__ . '/auth.php';
}
if (!isset($pageTitle)) $pageTitle = 'HostelCare';
$user = currentUser();
$role = $user['role'] ?? '';
$uri  = $_SERVER['REQUEST_URI'] ?? '';

function isActive(string $segment): string {
    global $uri;
    return strpos($uri, $segment) !== false ? 'active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> | HostelCare</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
</head>
<body>

<div class="app-shell">

  <!-- ── Sidebar ─────────────────────────────────────────── -->
  <aside class="sidebar" id="sidebar">

    <!-- Brand -->
    <a href="<?= BASE_URL ?>/" class="sidebar-brand">
      <div class="sidebar-brand-icon"><i class="bi bi-shield-check-fill"></i></div>
      <div class="sidebar-brand-name">HostelCare<span>Complaint Tracking</span></div>
    </a>

    <!-- Navigation -->
    <nav class="sidebar-nav">
      <?php if ($role === 'admin'): ?>
        <div class="nav-section-label">Main</div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('admin/dashboard') ?>" href="<?= BASE_URL ?>/admin/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('admin/complaints') ?>" href="<?= BASE_URL ?>/admin/complaints.php">
            <i class="bi bi-chat-square-dots"></i> Complaints
          </a>
        </div>
        <div class="nav-section-label" style="margin-top:8px">Manage</div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('admin/categories') ?>" href="<?= BASE_URL ?>/admin/categories.php">
            <i class="bi bi-tags"></i> Categories
          </a>
        </div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('admin/users') ?>" href="<?= BASE_URL ?>/admin/users.php">
            <i class="bi bi-people"></i> Users
          </a>
        </div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('admin/reports') ?>" href="<?= BASE_URL ?>/admin/reports.php">
            <i class="bi bi-bar-chart-line"></i> Reports
          </a>
        </div>

      <?php elseif ($role === 'staff'): ?>
        <div class="nav-section-label">Workspace</div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('staff/dashboard') ?>" href="<?= BASE_URL ?>/staff/dashboard.php">
            <i class="bi bi-speedometer2"></i> Dashboard
          </a>
        </div>

      <?php elseif ($role === 'student'): ?>
        <div class="nav-section-label">My Portal</div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('student/dashboard') ?>" href="<?= BASE_URL ?>/student/dashboard.php">
            <i class="bi bi-house-heart"></i> Dashboard
          </a>
        </div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('new_complaint') ?>" href="<?= BASE_URL ?>/student/new_complaint.php">
            <i class="bi bi-plus-circle"></i> New Complaint
          </a>
        </div>
        <div class="nav-item">
          <a class="nav-link <?= isActive('my_complaints') ?>" href="<?= BASE_URL ?>/student/my_complaints.php">
            <i class="bi bi-card-list"></i> My Complaints
          </a>
        </div>
      <?php endif; ?>
    </nav>

    <!-- User footer -->
    <?php if ($user): ?>
    <div class="sidebar-footer">
      <div class="sidebar-user">
        <div class="sidebar-user-avatar avatar-<?= $role ?>">
          <?= strtoupper(substr($user['name'], 0, 1)) ?>
        </div>
        <div class="sidebar-user-info">
          <div class="sidebar-user-name"><?= htmlspecialchars($user['name']) ?></div>
          <div class="sidebar-user-role"><?= ucfirst($role) ?></div>
        </div>
      </div>
    </div>
    <?php endif; ?>

  </aside><!-- /sidebar -->

  <!-- Mobile overlay -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <!-- ── Main content ─────────────────────────────────────── -->
  <div class="main-content">

    <!-- Topbar -->
    <header class="topbar">
      <button class="topbar-toggle" id="sidebarToggle" aria-label="Toggle menu">
        <i class="bi bi-list"></i>
      </button>
      <div>
        <div class="topbar-title"><?= htmlspecialchars($pageTitle) ?></div>
        <?php if ($role): ?>
        <div class="topbar-subtitle">
          <?= htmlspecialchars($user['name'] ?? '') ?>
          <?php if ($role === 'student' && !empty($user['room_no'])): ?>
          &nbsp;· Room <?= htmlspecialchars($user['room_no']) ?>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
      <div class="topbar-actions">
        <a href="<?= BASE_URL ?>/logout.php" class="topbar-logout">
          <i class="bi bi-box-arrow-right"></i> Logout
        </a>
      </div>
    </header>

    <!-- Flash messages -->
    <?php if (isset($_SESSION['flash_success']) || isset($_SESSION['flash_error'])): ?>
    <div class="flash-zone">
      <?php if (isset($_SESSION['flash_success'])): ?>
      <div class="alert alert-success">
        <i class="bi bi-check-circle-fill"></i>
        <?= htmlspecialchars($_SESSION['flash_success']) ?>
        <button class="btn-close" onclick="this.parentElement.remove()">×</button>
      </div>
      <?php unset($_SESSION['flash_success']); endif; ?>
      <?php if (isset($_SESSION['flash_error'])): ?>
      <div class="alert alert-danger">
        <i class="bi bi-exclamation-triangle-fill"></i>
        <?= htmlspecialchars($_SESSION['flash_error']) ?>
        <button class="btn-close" onclick="this.parentElement.remove()">×</button>
      </div>
      <?php unset($_SESSION['flash_error']); endif; ?>
    </div>
    <?php endif; ?>

    <!-- Page content start -->
    <div class="page-content fade-in">
