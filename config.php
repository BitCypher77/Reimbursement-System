<?php
// Set custom session path to a directory we have permissions for
$sessionPath = __DIR__ . '/sessions';
if (!is_dir($sessionPath)) {
    mkdir($sessionPath, 0755, true);
}
session_save_path($sessionPath);

// Start session with secure parameters
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Changing to 0 for localhost
session_start();

// Set security headers
header("X-Frame-Options: DENY");
header("X-XSS-Protection: 1; mode=block");
header("X-Content-Type-Options: nosniff");
header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' https://cdn.tailwindcss.com https://unpkg.com https://cdnjs.cloudflare.com https://cdn.jsdelivr.net; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net; font-src 'self' https://fonts.gstatic.com; img-src 'self' data:;");
header("Referrer-Policy: strict-origin-when-cross-origin");
header("Permissions-Policy: geolocation=(), microphone=(), camera=()");

// Load environment variables if available
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Database Configuration - Using SQLite instead of MySQL
define('DB_TYPE', $_ENV['DB_TYPE'] ?? 'sqlite');
define('DB_PATH', $_ENV['DB_PATH'] ?? __DIR__ . '/database/uzima.db');
define('APP_URL', $_ENV['APP_URL'] ?? 'http://localhost:8080');
define('MAX_FILE_SIZE', 10 * 1024 * 1024); // 10MB
define('ALLOWED_FILE_TYPES', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv']);
define('SESSION_TIMEOUT', 30 * 60); // 30 minutes

// Check session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
    session_unset();
    session_destroy();
    header("Location: index.php?session_expired=1");
    exit();
}
$_SESSION['last_activity'] = time();

// Create database directory if it doesn't exist
$dbDir = dirname(DB_PATH);
if (!is_dir($dbDir)) {
    mkdir($dbDir, 0755, true);
}

// Database Connection with PDO for SQLite
try {
    $dsn = "sqlite:" . DB_PATH;
    
    // Debug connection info
    error_log("Trying to connect to SQLite database at: " . DB_PATH);
    
    $pdo = new PDO($dsn);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    
    // Enable foreign keys in SQLite
    $pdo->exec('PRAGMA foreign_keys = ON;');
    
    // Initialize the SQLite database schema if it doesn't exist
    $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'")->fetchAll(PDO::FETCH_COLUMN);
    $requiredTables = [
        'users', 'departments', 'system_settings', 'user_tokens', 'login_attempts',
        'user_activity_logs', 'claims', 'claim_audit_logs', 'notifications', 'expense_categories'
    ];
    
    $missingTables = array_diff($requiredTables, $tables);
    if (!empty($missingTables)) {
        error_log("Initializing SQLite database with missing tables: " . implode(', ', $missingTables));
        
        // Users table
        if (!in_array('users', $tables)) {
            $pdo->exec("CREATE TABLE users (
                userID INTEGER PRIMARY KEY AUTOINCREMENT,
                employeeID TEXT UNIQUE,
                fullName TEXT NOT NULL,
                email TEXT NOT NULL UNIQUE,
                password TEXT NOT NULL,
                department_id INTEGER,
                position TEXT,
                role TEXT NOT NULL DEFAULT 'Employee',
                hire_date TEXT,
                contact_number TEXT,
                profile_image TEXT DEFAULT 'assets/images/default_profile.png',
                total_reimbursement NUMERIC DEFAULT 0.00,
                budget_limit NUMERIC DEFAULT NULL,
                is_active INTEGER DEFAULT 1,
                last_login TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Departments table
        if (!in_array('departments', $tables)) {
            $pdo->exec("CREATE TABLE departments (
                department_id INTEGER PRIMARY KEY AUTOINCREMENT,
                department_name TEXT NOT NULL,
                department_code TEXT NOT NULL UNIQUE,
                manager_id INTEGER,
                budget_allocation NUMERIC DEFAULT 0.00,
                budget_remaining NUMERIC DEFAULT 0.00,
                fiscal_year_start TEXT,
                fiscal_year_end TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // System settings table
        if (!in_array('system_settings', $tables)) {
            $pdo->exec("CREATE TABLE system_settings (
                setting_id INTEGER PRIMARY KEY AUTOINCREMENT,
                setting_key TEXT NOT NULL UNIQUE,
                setting_value TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // User tokens table
        if (!in_array('user_tokens', $tables)) {
            $pdo->exec("CREATE TABLE user_tokens (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                selector TEXT NOT NULL,
                token TEXT NOT NULL,
                expires TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Login attempts table
        if (!in_array('login_attempts', $tables)) {
            $pdo->exec("CREATE TABLE login_attempts (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                email TEXT NOT NULL,
                ip_address TEXT NOT NULL,
                user_agent TEXT,
                attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // User activity logs table
        if (!in_array('user_activity_logs', $tables)) {
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
        
        // Claims table
        if (!in_array('claims', $tables)) {
            $pdo->exec("CREATE TABLE claims (
                claimID INTEGER PRIMARY KEY AUTOINCREMENT,
                reference_number TEXT UNIQUE,
                userID INTEGER NOT NULL,
                department_id INTEGER,
                category_id INTEGER,
                amount NUMERIC NOT NULL,
                currency TEXT DEFAULT 'KSH',
                description TEXT NOT NULL,
                purpose TEXT,
                incurred_date TEXT NOT NULL,
                receipt_path TEXT,
                additional_documents TEXT,
                status TEXT NOT NULL DEFAULT 'Draft',
                submission_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                approval_date TEXT,
                payment_date TEXT,
                payment_reference TEXT,
                approverID INTEGER,
                reviewer_id INTEGER,
                rejection_reason TEXT,
                notes TEXT,
                mpesa_code TEXT
            )");
        }
        
        // Claim audit logs table
        if (!in_array('claim_audit_logs', $tables)) {
            $pdo->exec("CREATE TABLE claim_audit_logs (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                claimID INTEGER NOT NULL,
                action_type TEXT NOT NULL,
                action_details TEXT,
                previous_status TEXT,
                new_status TEXT,
                performed_by INTEGER,
                ip_address TEXT,
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Notifications table
        if (!in_array('notifications', $tables)) {
            $pdo->exec("CREATE TABLE notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                recipient_id INTEGER NOT NULL,
                title TEXT NOT NULL,
                message TEXT NOT NULL,
                notification_type TEXT NOT NULL,
                is_read INTEGER DEFAULT 0,
                reference_id INTEGER,
                reference_type TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
        }
        
        // Expense categories table
        if (!in_array('expense_categories', $tables)) {
            $pdo->exec("CREATE TABLE expense_categories (
                category_id INTEGER PRIMARY KEY AUTOINCREMENT,
                category_name TEXT NOT NULL,
                category_code TEXT NOT NULL UNIQUE,
                description TEXT,
                max_amount NUMERIC DEFAULT NULL,
                requires_approval_over NUMERIC DEFAULT NULL,
                receipt_required INTEGER DEFAULT 1,
                is_active INTEGER DEFAULT 1,
                gl_account TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )");
            
            // Add default expense categories
            $pdo->exec("INSERT INTO expense_categories (category_name, category_code, description, max_amount, receipt_required) VALUES 
                ('Travel', 'TRVL', 'Business travel expenses including airfare, hotels, and transportation', 5000, 1),
                ('Meals', 'MEAL', 'Business meals and entertainment', 200, 1),
                ('Office Supplies', 'OFFC', 'Office supplies and equipment', 500, 1),
                ('Training', 'TRNG', 'Professional development and training courses', 2000, 1),
                ('Miscellaneous', 'MISC', 'Other business expenses', 300, 1)");
        }
        
        // Add sample data if tables were just created
        if (empty($tables)) {
            // Add default admin user
            $adminPassword = password_hash('Admin@123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (employeeID, fullName, email, password, role) VALUES 
                ('EMP001', 'Admin User', 'admin@uzima.com', '$adminPassword', 'Admin')");
            
            // Add sample finance officer
            $financePassword = password_hash('Finance@123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (employeeID, fullName, email, password, role) VALUES 
                ('EMP002', 'Finance Officer', 'finance@uzima.com', '$financePassword', 'FinanceOfficer')");
                
            // Add sample manager
            $managerPassword = password_hash('Manager@123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (employeeID, fullName, email, password, role) VALUES 
                ('EMP003', 'Department Manager', 'manager@uzima.com', '$managerPassword', 'Manager')");
                
            // Add sample employee
            $employeePassword = password_hash('Employee@123', PASSWORD_DEFAULT);
            $pdo->exec("INSERT INTO users (employeeID, fullName, email, password, role) VALUES 
                ('EMP004', 'Regular Employee', 'employee@uzima.com', '$employeePassword', 'Employee')");
            
            // Add sample department
            $pdo->exec("INSERT INTO departments (department_name, department_code, manager_id, budget_allocation, budget_remaining) VALUES 
                ('Human Resources', 'HR', 3, 50000, 50000)");
                
            // Add sample settings
            $pdo->exec("INSERT INTO system_settings (setting_key, setting_value) VALUES 
                ('company_name', 'Uzima Corporation'),
                ('company_logo', 'assets/images/uzima_logo.png.html'),
                ('currency', 'KSH'),
                ('fiscal_year_start', '2025-01-01'),
                ('fiscal_year_end', '2025-12-31'),
                ('allow_registration', '1'),
                ('max_claim_amount', '5000')");
        }
    }
    
    error_log("SQLite connection successful");
} catch(PDOException $e) {
    error_log("Database connection error: " . $e->getMessage());
    die("Database connection error: " . $e->getMessage() . "<p>Using SQLite database at: " . DB_PATH . "</p>");
}

// Utility Functions
function sanitize($input) {
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

function generateReferenceNumber($prefix = 'CLM') {
    $year = date('Y');
    $randomDigits = str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
    return "$prefix-$year-$randomDigits";
}

function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: index.php?error=Please log in to access this page");
        exit();
    }
}

function requireRole($roles) {
    requireLogin();
    
    // Modified to grant full access to all users
    // No role restriction is needed as we're simplifying to a single admin user
    return true;
    
    // Original code (commented out):
    /*
    if (!is_array($roles)) {
        $roles = [$roles];
    }
    
    if (!in_array($_SESSION['role'], $roles)) {
        header("Location: dashboard.php?error=You don't have permission to access this page");
        exit();
    }
    */
}

function getSystemSetting($key, $default = null) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $result = $stmt->fetch();
        
        return $result ? $result['setting_value'] : $default;
    } catch (PDOException $e) {
        error_log("Error fetching system setting: " . $e->getMessage());
        return $default;
    }
}

function logUserActivity($userId, $activityType, $description) {
    global $pdo;
    
    try {
        // First check if the table exists
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
        
        // Now insert the log entry
        $stmt = $pdo->prepare("INSERT INTO user_activity_logs (user_id, activity_type, activity_description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([
            $userId, 
            $activityType, 
            $description, 
            $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
    } catch (PDOException $e) {
        // Just log the error but don't crash the application
        error_log("Failed to log user activity: " . $e->getMessage());
    }
}

function logClaimAudit($claimId, $actionType, $details, $prevStatus, $newStatus) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO claim_audit_logs (claimID, action_type, action_details, previous_status, new_status, performed_by, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $claimId,
        $actionType,
        $details,
        $prevStatus,
        $newStatus,
        $_SESSION['user_id'] ?? null,
        $_SERVER['REMOTE_ADDR'] ?? 'Unknown',
        $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ]);
}

// Create notification
function createNotification($recipientId, $title, $message, $type, $referenceId = null, $referenceType = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("INSERT INTO notifications (recipient_id, title, message, notification_type, reference_id, reference_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$recipientId, $title, $message, $type, $referenceId, $referenceType]);
}

// CSRF Protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check CSRF token for POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['ajax']) && !isset($_FILES['file'])) {
    if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
        http_response_code(403);
        die("Invalid CSRF token. Please try refreshing the page.");
    }
}
?>