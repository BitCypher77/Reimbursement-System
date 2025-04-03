<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout activity if user is logged in
if (isset($_SESSION['user_id'])) {
    require_once 'config.php';
    try {
        // Log user activity
        logUserActivity($_SESSION['user_id'], 'user_logout', 'User logged out');
    } catch (Exception $e) {
        // Continue with logout even if logging fails
        error_log("Failed to log logout activity: " . $e->getMessage());
    }
}

// Unset all session variables
$_SESSION = array();

// Destroy the session cookie
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 