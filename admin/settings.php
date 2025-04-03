<?php
include '../config.php';
requireRole('Admin');

// Initialize variables
$success = null;
$error = null;
$settings = [];

// Get all system settings
try {
    $stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
    $settings = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching settings: " . $e->getMessage());
    $error = "Could not retrieve system settings. Please try again later.";
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        // Start transaction
        $pdo->beginTransaction();
        
        // Process each setting
        foreach ($_POST as $key => $value) {
            if (strpos($key, 'setting_') === 0) {
                $setting_key = substr($key, 8); // Remove 'setting_' prefix
                
                // Update the setting
                $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ?, updated_at = CURRENT_TIMESTAMP WHERE setting_key = ?");
                $stmt->execute([$value, $setting_key]);
            }
        }
        
        // Commit the transaction
        $pdo->commit();
        
        // Log the activity
        logUserActivity($_SESSION['user_id'], 'settings_update', "Updated system settings");
        
        $success = "System settings updated successfully.";
        
        // Refresh settings
        $stmt = $pdo->query("SELECT * FROM system_settings ORDER BY setting_key");
        $settings = $stmt->fetchAll();
        
    } catch (Exception $e) {
        // Rollback the transaction
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        error_log("Error updating settings: " . $e->getMessage());
        $error = "An error occurred while updating settings: " . $e->getMessage();
    }
}

// Include header
include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">System Settings</h1>
    </div>
    
    <?php if ($success): ?>
    <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i data-lucide="check-circle" class="h-5 w-5 text-green-400 dark:text-green-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-200"><?= htmlspecialchars($success) ?></h3>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if ($error): ?>
    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i data-lucide="alert-circle" class="h-5 w-5 text-red-400 dark:text-red-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-red-800 dark:text-red-200"><?= htmlspecialchars($error) ?></h3>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <form action="settings.php" method="post">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <?php foreach ($settings as $setting): ?>
                        <div>
                            <label for="setting_<?= $setting['setting_key'] ?>" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                                <?= formatSettingLabel($setting['setting_key']) ?>
                            </label>
                            
                            <?php if (in_array($setting['setting_key'], ['company_name', 'currency', 'fiscal_year_start', 'fiscal_year_end', 'max_claim_amount'])): ?>
                                <!-- Text input -->
                                <input type="text" 
                                    id="setting_<?= $setting['setting_key'] ?>" 
                                    name="setting_<?= $setting['setting_key'] ?>" 
                                    value="<?= htmlspecialchars($setting['setting_value']) ?>" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                            <?php elseif (in_array($setting['setting_key'], ['allow_registration', 'enable_2fa', 'enable_audit_logs'])): ?>
                                <!-- Boolean toggle -->
                                <select id="setting_<?= $setting['setting_key'] ?>" 
                                    name="setting_<?= $setting['setting_key'] ?>" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                                    <option value="1" <?= $setting['setting_value'] == '1' ? 'selected' : '' ?>>Enabled</option>
                                    <option value="0" <?= $setting['setting_value'] == '0' ? 'selected' : '' ?>>Disabled</option>
                                </select>
                            <?php else: ?>
                                <!-- Default text input -->
                                <input type="text" 
                                    id="setting_<?= $setting['setting_key'] ?>" 
                                    name="setting_<?= $setting['setting_key'] ?>" 
                                    value="<?= htmlspecialchars($setting['setting_value']) ?>" 
                                    class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                            <?php endif; ?>
                            
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                                <?= getSettingDescription($setting['setting_key']) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="mt-6">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                        <i data-lucide="save" class="h-4 w-4 mr-2"></i>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// Helper functions
function formatSettingLabel($key) {
    $label = str_replace('_', ' ', $key);
    return ucwords($label);
}

function getSettingDescription($key) {
    $descriptions = [
        'company_name' => 'The name of your organization displayed throughout the system',
        'company_logo' => 'Path to your company logo image',
        'currency' => 'Default currency used for expenses (e.g., KSH, EUR)',
        'fiscal_year_start' => 'Start date of your fiscal year (YYYY-MM-DD)',
        'fiscal_year_end' => 'End date of your fiscal year (YYYY-MM-DD)',
        'allow_registration' => 'Allow users to register their own accounts',
        'max_claim_amount' => 'Maximum amount allowed for a single expense claim',
        'enable_2fa' => 'Enable two-factor authentication for additional security',
        'enable_audit_logs' => 'Enable detailed audit logs for system actions',
        // Add more descriptions as needed
    ];
    
    return isset($descriptions[$key]) ? $descriptions[$key] : 'System configuration setting';
}

include '../includes/footer.php';
?> 