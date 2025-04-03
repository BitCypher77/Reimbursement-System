<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Include configuration
require_once 'config.php';

$tests = [];
$allPassed = true;

// Function to add test result
function addTest($name, $passed, $message = '') {
    global $tests, $allPassed;
    $tests[] = [
        'name' => $name,
        'passed' => $passed,
        'message' => $message
    ];
    
    if (!$passed) {
        $allPassed = false;
    }
}

// 1. Check PHP Version
$phpVersion = phpversion();
$phpVersionPassed = version_compare($phpVersion, '7.4.0', '>=');
addTest('PHP Version', $phpVersionPassed, "Current PHP version: $phpVersion" . ($phpVersionPassed ? '' : ' (Recommended: 7.4.0 or higher)'));

// 2. Check Required Extensions
$requiredExtensions = ['pdo', 'pdo_sqlite', 'sqlite3', 'json', 'session', 'filter'];
foreach ($requiredExtensions as $extension) {
    $extensionLoaded = extension_loaded($extension);
    addTest("PHP Extension: $extension", $extensionLoaded, $extensionLoaded ? 'Loaded' : 'Not loaded');
}

// 3. Check Database Connection
try {
    $dbConnectionPassed = false;
    $dbMessage = '';
    
    if (isset($pdo) && $pdo instanceof PDO) {
        // Test a simple query
        $stmt = $pdo->query("SELECT 1");
        if ($stmt && $stmt->fetchColumn() == 1) {
            $dbConnectionPassed = true;
            $dbMessage = 'Successfully connected to SQLite database';
        } else {
            $dbMessage = 'Connected to database but query failed';
        }
    } else {
        $dbMessage = 'Database connection not established';
    }
} catch (Exception $e) {
    $dbMessage = "Database connection error: " . $e->getMessage();
}
addTest('Database Connection', $dbConnectionPassed, $dbMessage);

// 4. Check Required Tables
$requiredTables = [
    'users', 'departments', 'system_settings', 'user_tokens', 'login_attempts',
    'user_activity_logs', 'claims', 'claim_audit_logs', 'notifications', 'expense_categories'
];

try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT name FROM sqlite_master WHERE type='table'");
        $existingTables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        foreach ($requiredTables as $table) {
            $tableExists = in_array($table, $existingTables);
            addTest("Database Table: $table", $tableExists, $tableExists ? 'Exists' : 'Missing');
        }
    }
} catch (Exception $e) {
    addTest('Database Tables Check', false, "Error checking tables: " . $e->getMessage());
}

// 5. Check Directory Permissions
$requiredDirs = [
    'database' => 'Database storage',
    'assets/images' => 'Image uploads',
    'temp' => 'Temporary files'
];

foreach ($requiredDirs as $dir => $description) {
    $dirExists = is_dir($dir);
    if (!$dirExists) {
        // Try to create directory
        $dirExists = mkdir($dir, 0755, true);
    }
    
    $dirWritable = $dirExists && is_writable($dir);
    addTest("Directory: $dir ($description)", $dirWritable, $dirWritable ? 'Exists and is writable' : ($dirExists ? 'Exists but not writable' : 'Does not exist'));
}

// 6. Check Sample Data
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'Admin'");
        $adminCount = $stmt->fetchColumn();
        addTest('Admin User', $adminCount > 0, $adminCount > 0 ? "Found $adminCount admin users" : 'No admin users found');
    }
} catch (Exception $e) {
    addTest('Admin User Check', false, "Error checking admin users: " . $e->getMessage());
}

// 7. Check System Settings
try {
    if (isset($pdo) && $pdo instanceof PDO) {
        $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
        $settingsCount = $stmt->fetchColumn();
        addTest('System Settings', $settingsCount > 0, $settingsCount > 0 ? "Found $settingsCount system settings" : 'No system settings found');
    }
} catch (Exception $e) {
    addTest('System Settings Check', false, "Error checking system settings: " . $e->getMessage());
}

// 8. Check Configuration File
$configFileExists = file_exists('config.php');
addTest('Configuration File', $configFileExists, $configFileExists ? 'Exists' : 'Missing');

// 9. Check Session Configuration
$sessionActive = session_status() === PHP_SESSION_ACTIVE;
addTest('PHP Sessions', $sessionActive, $sessionActive ? 'Active' : 'Not active');

// 10. Generate Summary
$totalTests = count($tests);
$passedTests = 0;
foreach ($tests as $test) {
    if ($test['passed']) {
        $passedTests++;
    }
}
$percentagePassed = ($totalTests > 0) ? round(($passedTests / $totalTests) * 100) : 0;

// Output as HTML
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Health Check - Uzima Expense Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            padding: 20px;
            background-color: #f5f7fb;
        }
        .health-card {
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            margin-bottom: 20px;
        }
        .test-result {
            border-bottom: 1px solid #eee;
            padding: 10px 0;
        }
        .test-result:last-child {
            border-bottom: none;
        }
        .progress {
            height: 10px;
            border-radius: 5px;
        }
        h1, h2 {
            color: #333;
        }
        .summary-box {
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            color: white;
        }
        .badge-result {
            font-size: 85%;
            padding: 5px 10px;
            border-radius: 20px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="text-center mb-4">
                    <h1>Uzima Expense Management</h1>
                    <h2>System Health Check</h2>
                    <p class="text-muted">Check if your system meets all requirements</p>
                </div>
                
                <!-- Overall Health Summary -->
                <div class="summary-box text-center <?= $allPassed ? 'bg-success' : ($percentagePassed >= 80 ? 'bg-warning' : 'bg-danger') ?>">
                    <h3>System Health: <?= $percentagePassed ?>% Operational</h3>
                    <div class="progress mt-2">
                        <div class="progress-bar <?= $allPassed ? 'bg-success' : ($percentagePassed >= 80 ? 'bg-warning' : 'bg-danger') ?>" 
                             role="progressbar" style="width: <?= $percentagePassed ?>%" 
                             aria-valuenow="<?= $percentagePassed ?>" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                    <p class="mt-2">
                        <?= $passedTests ?> of <?= $totalTests ?> tests passed
                    </p>
                </div>
                
                <!-- Test Results -->
                <div class="card health-card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Detailed Test Results</h4>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php foreach ($tests as $test): ?>
                                <div class="list-group-item test-result">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-1"><?= htmlspecialchars($test['name']) ?></h5>
                                        <span class="badge-result <?= $test['passed'] ? 'bg-success' : 'bg-danger' ?>">
                                            <?= $test['passed'] ? 'PASSED' : 'FAILED' ?>
                                        </span>
                                    </div>
                                    <?php if (!empty($test['message'])): ?>
                                        <p class="mb-0 text-muted"><?= htmlspecialchars($test['message']) ?></p>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Recommendations -->
                <?php if (!$allPassed): ?>
                <div class="card health-card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Recommendations</h4>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <?php foreach ($tests as $test): ?>
                                <?php if (!$test['passed']): ?>
                                    <li class="list-group-item">
                                        <strong><?= htmlspecialchars($test['name']) ?>:</strong>
                                        <?php 
                                        // Add specific recommendations based on test name
                                        if (strpos($test['name'], 'PHP Version') !== false) {
                                            echo "Update your PHP version to at least 7.4.0.";
                                        } elseif (strpos($test['name'], 'PHP Extension') !== false) {
                                            $extension = str_replace('PHP Extension: ', '', $test['name']);
                                            echo "Install the missing $extension PHP extension.";
                                        } elseif (strpos($test['name'], 'Database Connection') !== false) {
                                            echo "Check your database configuration in config.php. Ensure the SQLite database file exists and is writable.";
                                        } elseif (strpos($test['name'], 'Database Table') !== false) {
                                            echo "The database schema is incomplete. Re-run the initialization process or manually create the missing table.";
                                        } elseif (strpos($test['name'], 'Directory') !== false) {
                                            echo "Create the directory or fix its permissions using chmod 755.";
                                        } elseif (strpos($test['name'], 'Admin User') !== false) {
                                            echo "Create at least one admin user through the registration process.";
                                        } elseif (strpos($test['name'], 'System Settings') !== false) {
                                            echo "Initialize the system settings in the database.";
                                        } elseif (strpos($test['name'], 'Configuration File') !== false) {
                                            echo "Restore the missing configuration file.";
                                        } elseif (strpos($test['name'], 'PHP Sessions') !== false) {
                                            echo "Check your PHP configuration to ensure sessions are properly enabled.";
                                        } else {
                                            echo "Fix the reported issue before proceeding.";
                                        }
                                        ?>
                                    </li>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Quick Links -->
                <div class="card health-card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">Quick Links</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <a href="index.php" class="btn btn-primary w-100">Login Page</a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="register.php" class="btn btn-outline-primary w-100">Register</a>
                            </div>
                            <div class="col-md-4 mb-2">
                                <a href="<?= $_SERVER['PHP_SELF'] ?>" class="btn btn-outline-secondary w-100">Rerun Tests</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- System Info -->
                <div class="card health-card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0">System Information</h4>
                    </div>
                    <div class="card-body">
                        <table class="table table-striped">
                            <tbody>
                                <tr>
                                    <th>PHP Version</th>
                                    <td><?= phpversion() ?></td>
                                </tr>
                                <tr>
                                    <th>Server Software</th>
                                    <td><?= $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown' ?></td>
                                </tr>
                                <tr>
                                    <th>Operating System</th>
                                    <td><?= PHP_OS ?></td>
                                </tr>
                                <tr>
                                    <th>Database Type</th>
                                    <td>SQLite</td>
                                </tr>
                                <tr>
                                    <th>Server Time</th>
                                    <td><?= date('Y-m-d H:i:s') ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 