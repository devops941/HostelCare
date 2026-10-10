<?php
/**
 * Session Authentication Helper for Hostel Complaint System
 * Supports roles: admin, staff, student
 */

// Start session
session_start();

/**
 * Check if user is logged in
 * @return bool
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Require user to be logged in
 * Redirects to login page if not authenticated
 */
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}
  

/**
 * Require specific role
 * @param string|array $role Single role or array of allowed roles
 */
function requireRole($role) {
    // First check if logged in
    requireLogin();
    
    // Convert single role to array
    $allowedRoles = is_array($role) ? $role : [$role];
    
    // Check if user's role is allowed
    if (!in_array($_SESSION['role'], $allowedRoles, true)) {
        header('Location: ' . BASE_URL . '/login.php');
        exit();
    }
}

/**
 * Get current logged-in user data
 * @return array|null User session array or null if not logged in
 */
function currentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return $_SESSION;
}

/**
 * Redirect user to appropriate dashboard based on their role
 */
function redirectByRole() {
    requireLogin();
    
    $role = $_SESSION['role'];
    
    switch ($role) {
        case 'admin':
            header('Location: ' . BASE_URL . '/admin/dashboard.php');
            break;
        case 'staff':
            header('Location: ' . BASE_URL . '/staff/dashboard.php');
            break;
        case 'student':
            header('Location: ' . BASE_URL . '/student/dashboard.php');
            break;
        default:
            header('Location: ' . BASE_URL . '/login.php');
            break;
    }
    
    exit();
}

/**
 * Logout user
 */
function logout() {
    session_destroy();
    header('Location: ' . BASE_URL . '/login.php');
    exit();
}

?>
