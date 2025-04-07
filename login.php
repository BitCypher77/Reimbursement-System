<?php
require 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Check CSRF token
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        header("Location: index.php?error=Invalid request. Please try again.");
        exit();
    }
    
    // Validate input
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if (!$email) {
        header("Location: index.php?error=Invalid email format");
        exit();
    }
    
    $password = $_POST['password'];
    if (empty($password)) {
        header("Location: index.php?error=Password is required");
        exit();
    }
    
    // Remember me option
    $remember_me = isset($_POST['remember_me']) ? true : false;
    
    try {
        // Get user from database
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Valid login
            
            // Update last login time
            $updateStmt = $pdo->prepare("UPDATE users SET last_login = datetime('now') WHERE userID = ?");
            $updateStmt->execute([$user['userID']]);
            
            // Set session variables
            $_SESSION['user_id'] = $user['userID'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['fullName'] = $user['fullName'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['department_id'] = $user['department_id'];
            $_SESSION['profile_image'] = $user['profile_image'];
            
            // If remember me is checked, set a cookie to persist the session
            if ($remember_me) {
                $selector = bin2hex(random_bytes(8));
                $validator = bin2hex(random_bytes(32));
                
                // Store the token in the database (you'd need a remember_tokens table)
                // This is just a basic implementation that could be expanded
                $token_hash = hash('sha256', $validator);
                $expires = date('Y-m-d H:i:s', time() + 30 * 24 * 60 * 60); // 30 days
                
                try {
                    $stmt = $pdo->prepare("INSERT INTO user_tokens (user_id, selector, token, expires) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$user['userID'], $selector, $token_hash, $expires]);
                } catch (PDOException $e) {
                    error_log("Remember me token error: " . $e->getMessage());
                    // Continue even if this fails - it's not critical
                }
                
                // Set cookie with selector and validator
                setcookie(
                    'remember',
                    $selector . ':' . $validator,
                    time() + 30 * 24 * 60 * 60, // 30 days
                    '/',
                    '',
                    true, // Only send over HTTPS
                    true  // HttpOnly
                );
            }
            
            // Log user activity
            logUserActivity($user['userID'], 'login', 'User logged in');
            
            // Check if it's an AJAX request
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                // Send JSON response for AJAX requests
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => true,
                    'redirect' => 'dashboard.php'
                ]);
                exit();
            }
            
            // Regular form submission
            header("Location: dashboard.php");
            exit();
        } else {
            // Invalid credentials
            
            // Log failed login attempt (for security monitoring)
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            
            try {
                // First check if the table exists
                $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='login_attempts'");
                if (!$tableCheck->fetchColumn()) {
                    // Table doesn't exist - create it
                    $pdo->exec("CREATE TABLE login_attempts (
                        id INTEGER PRIMARY KEY AUTOINCREMENT,
                        email TEXT NOT NULL,
                        ip_address TEXT NOT NULL,
                        user_agent TEXT,
                        attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                    )");
                }
                
                $stmt = $pdo->prepare("INSERT INTO login_attempts (email, ip_address, user_agent, attempted_at) VALUES (?, ?, ?, datetime('now'))");
                $stmt->execute([$email, $ip, $user_agent]);
            } catch (PDOException $e) {
                error_log("Failed to log login attempt: " . $e->getMessage());
                // Continue even if this fails
            }
            
            // Check if we should delay response (to prevent brute force attacks)
            // Check number of failed attempts in the last hour
            try {
                // Ensure table exists again (just to be safe)
                $tableCheck = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name='login_attempts'");
                if ($tableCheck->fetchColumn()) {
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM login_attempts WHERE email = ? AND attempted_at > datetime('now', '-1 hour')");
                    $stmt->execute([$email]);
                    $attempts = $stmt->fetchColumn();
                    
                    if ($attempts > 5) {
                        // Too many failed attempts, delay response
                        sleep(2); // Add a delay to slow down brute force attacks
                    }
                }
            } catch (PDOException $e) {
                error_log("Failed to check login attempts: " . $e->getMessage());
                // Continue even if this fails
            }
            
            // AJAX response
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid credentials. Please check your email and password.'
                ]);
                exit();
            }
            
            // Regular response
            header("Location: index.php?error=Invalid credentials. Please check your email and password.");
            exit();
        }
    } catch (PDOException $e) {
        error_log("Login error: " . $e->getMessage());
        
        // AJAX response
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'System error. Please try again later.'
            ]);
            exit();
        }
        
        header("Location: index.php?error=System error. Please try again later.");
        exit();
    }
} else {
    // Not a POST request
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Uzima Expense System</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Additional login-specific styles */
        .login-container {
            max-width: 450px;
            margin: 50px auto;
            padding: 30px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .login-logo {
            text-align: center;
            margin-bottom: 25px;
        }
        .login-logo img {
            max-width: 150px;
            height: auto;
        }
        .alert {
            border-radius: 5px;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container">
        <div class="login-container">
            <div class="login-logo">
                <?php
                // Check if PNG logo exists
                if (file_exists('assets/images/uzima_logo.png')) {
                    echo '<img src="assets/images/uzima_logo.png" alt="Uzima Logo" class="img-fluid">';
                } 
                // Check if HTML logo exists
                elseif (file_exists('assets/images/uzima_logo.png.html')) {
                    echo '<iframe src="assets/images/uzima_logo.png.html" style="border:none; width:150px; height:60px;"></iframe>';
                } 
                // Fallback to text
                else {
                    echo '<h1 style="color:#0d6efd;">UZIMA</h1>';
                }
                ?>
            </div>
            <!-- Rest of the file remains unchanged -->
        </div>
    </div>
</body>
</html>