<?php
include 'config.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: register.php?error=Invalid security token. Please try again.");
        exit();
    }
    
    // Validate inputs
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    // Check for empty fields
    if (empty($fullName) || empty($email) || empty($password) || empty($confirmPassword)) {
        header("Location: register.php?error=All fields are required");
        exit();
    }

    // Check password match
    if ($password !== $confirmPassword) {
        header("Location: register.php?error=Passwords do not match");
        exit();
    }
    
    // Validate password complexity
    if (strlen($password) < 8) {
        header("Location: register.php?error=Password must be at least 8 characters long");
        exit();
    }
    
    // Check for at least one uppercase letter, one lowercase letter, and one number
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password)) {
        header("Location: register.php?error=Password must contain at least one uppercase letter, one lowercase letter, and one number");
        exit();
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        header("Location: register.php?error=Invalid email format");
        exit();
    }

    // Generate a unique employee ID
    $employeeID = 'EMP' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
    
    try {
        // Check if employee ID already exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE employeeID = ?");
        $stmt->execute([$employeeID]);
        while ($stmt->fetchColumn() > 0) {
            // Generate a new one if it exists
            $employeeID = 'EMP' . str_pad(mt_rand(1, 9999), 4, '0', STR_PAD_LEFT);
            $stmt->execute([$employeeID]);
        }
    } catch (PDOException $e) {
        error_log("Error checking employee ID: " . $e->getMessage());
        // Continue anyway
    }

    // Check if email exists
    try {
        $stmt = $pdo->prepare("SELECT email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->rowCount() > 0) {
            header("Location: register.php?error=This email is already registered. Please use a different email or login to your existing account.");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Error checking email: " . $e->getMessage());
        header("Location: register.php?error=Error validating email. Please try again.");
        exit();
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Insert new user
    try {
        $stmt = $pdo->prepare("INSERT INTO users (employeeID, fullName, email, password, role, created_at, updated_at) VALUES (?, ?, ?, ?, 'Employee', datetime('now'), datetime('now'))");
        $stmt->execute([$employeeID, $fullName, $email, $hashedPassword]);
        
        // Log the registration
        try {
            // Make sure user activity logs table exists
            $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='user_activity_logs'");
            if (!$tableCheck->fetchColumn()) {
                // Table doesn't exist - create it
                $pdo->exec("CREATE TABLE user_activity_logs (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    user_id INTEGER,
                    activity_type TEXT NOT NULL,
                    activity_description TEXT NOT NULL,
                    ip_address TEXT,
                    user_agent TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
            }
            
            logUserActivity(null, 'registration', "New user registered: $email");
        } catch (Exception $e) {
            error_log("Failed to log registration: " . $e->getMessage());
            // Continue anyway
        }
        
        header("Location: index.php?success=Registration successful. Please login with your new account.");
        exit();
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        
        // More detailed error checking for constraint violations
        if (strpos($e->getMessage(), 'UNIQUE constraint failed: users.email') !== false) {
            header("Location: register.php?error=This email is already registered. Please use a different email or login to your existing account.");
        } else if (strpos($e->getMessage(), 'UNIQUE constraint failed: users.employeeID') !== false) {
            // This shouldn't happen as we check for duplicate employee IDs, but just in case
            header("Location: register.php?error=System error generating unique ID. Please try again.");
        } else {
            header("Location: register.php?error=Registration failed: Database error. Please try again later.");
        }
        exit();
    }
} else {
    // If not POST request, redirect to registration page
    header("Location: register.php?error=Please use the registration form");
    exit();
}
?>