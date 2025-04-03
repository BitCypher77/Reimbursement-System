<?php
require 'config.php';
requireLogin();

// Only the current logged-in user can run this script
if (isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Get current user ID
        $current_user_id = $_SESSION['user_id'];
        
        // Update current user to Admin if not already
        $stmt = $pdo->prepare("UPDATE users SET role = 'Admin' WHERE userID = ?");
        $stmt->execute([$current_user_id]);
        
        // Get current user info
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
        $stmt->execute([$current_user_id]);
        $current_user = $stmt->fetch();
        
        // Update session to reflect new role
        $_SESSION['role'] = 'Admin';
        
        // Deactivate all other users
        $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE userID != ?");
        $stmt->execute([$current_user_id]);
        
        // Reassign all claims to current user
        $stmt = $pdo->prepare("UPDATE claims SET userID = ? WHERE userID != ?");
        $stmt->execute([$current_user_id, $current_user_id]);
        
        // Clean up approvals by setting the current user as the approver
        $stmt = $pdo->prepare("UPDATE claims SET approverID = ? WHERE approverID IS NOT NULL AND approverID != ?");
        $stmt->execute([$current_user_id, $current_user_id]);
        
        // Update reviewer_id in claims
        $stmt = $pdo->prepare("UPDATE claims SET reviewer_id = ? WHERE reviewer_id IS NOT NULL AND reviewer_id != ?");
        $stmt->execute([$current_user_id, $current_user_id]);
        
        // Set department manager to current user
        $stmt = $pdo->prepare("UPDATE departments SET manager_id = ?");
        $stmt->execute([$current_user_id]);
        
        // Commit the transaction
        $pdo->commit();
        
        // Log this significant change
        logUserActivity($current_user_id, 'system_change', 'Converted all users to Admin role and consolidated system to single admin user');
        
        // Success message
        $success = "System successfully updated. You now have full admin privileges and are the only active user.";
    } catch (PDOException $e) {
        // Rollback the transaction on error
        $pdo->rollBack();
        
        error_log("Error updating to admin: " . $e->getMessage());
        $error = "Failed to update system: " . $e->getMessage();
    }
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-6">Consolidate System to Single Admin</h1>
    
    <?php if (isset($success)): ?>
    <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4">
        <div class="flex">
            <div class="flex-shrink-0">
                <i data-lucide="check-circle" class="h-5 w-5 text-green-400 dark:text-green-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-green-800 dark:text-green-200"><?= htmlspecialchars($success) ?></h3>
                <div class="mt-2">
                    <p class="text-sm text-green-700 dark:text-green-300">
                        You can now <a href="dashboard.php" class="font-medium underline">return to the dashboard</a> and access all features.
                    </p>
                </div>
            </div>
        </div>
    </div>
    <?php elseif (isset($error)): ?>
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
    <?php else: ?>
    <div class="bg-white dark:bg-gray-800 shadow sm:rounded-lg">
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">
                Consolidate to Single Admin User
            </h3>
            <div class="mt-2 max-w-xl text-sm text-gray-500 dark:text-gray-400">
                <p>
                    This action will convert your account to an Admin user with full privileges and deactivate all other user accounts.
                    All existing claims will be reassigned to your account.
                </p>
                <p class="mt-2 font-bold text-red-600 dark:text-red-400">
                    WARNING: This action cannot be undone. Make sure you have backed up your data if needed.
                </p>
            </div>
            <div class="mt-5">
                <a href="update_to_admin.php?confirm=yes" class="inline-flex items-center justify-center px-4 py-2 border border-transparent font-medium rounded-md shadow-sm text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 sm:text-sm">
                    Convert to Single Admin User
                </a>
                <a href="dashboard.php" class="ml-3 inline-flex items-center justify-center px-4 py-2 border border-gray-300 dark:border-gray-600 shadow-sm font-medium rounded-md text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:text-sm">
                    Cancel
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?> 