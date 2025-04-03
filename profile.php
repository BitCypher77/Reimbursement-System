<?php
require 'config.php';
requireLogin();

// Initialize variables
$user = [];
$error = null;
$success = null;
$departments = [];

try {
    // Fetch user data
    $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("User not found.");
    }
    
    // For Admin user, ensure department_id and employeeID are set
    if ($_SESSION['role'] === 'Admin' && (empty($user['department_id']) || empty($user['employeeID']))) {
        $stmt = $pdo->prepare("UPDATE users SET department_id = 2, employeeID = 'EMP001' WHERE userID = ? AND role = 'Admin'");
        $stmt->execute([$_SESSION['user_id']]);
        
        // Reload user data
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Update session variables
        $_SESSION['department_id'] = 2;
        $_SESSION['is_profile_complete'] = true;
    }
    
    // Get all departments for selection
    $stmt = $pdo->query("SELECT * FROM departments ORDER BY department_name");
    $departments = $stmt->fetchAll();
    
    // Check if profile is complete
    $is_profile_complete = !empty($user['employeeID']) && !empty($user['department_id']);
    
    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        // Determine which form was submitted
        if (isset($_POST['update_profile'])) {
            // Profile update
            $fullName = trim($_POST['fullName']);
            $email = trim($_POST['email']);
            $contact_number = trim($_POST['contact_number'] ?? '');
            $employeeID = trim($_POST['employeeID'] ?? '');
            $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
            
            // Validate input
            if (empty($fullName)) {
                throw new Exception("Full name is required.");
            }
            
            if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception("A valid email address is required.");
            }
            
            if (empty($employeeID)) {
                throw new Exception("Employee ID is required.");
            }
            
            if (empty($department_id)) {
                throw new Exception("Please select your department.");
            }
            
            // Check if employeeID is already in use by another user
            $stmt = $pdo->prepare("SELECT userID FROM users WHERE employeeID = ? AND userID != ?");
            $stmt->execute([$employeeID, $_SESSION['user_id']]);
            if ($stmt->fetchColumn()) {
                throw new Exception("Employee ID is already in use by another employee.");
            }
            
            // Check if email is already in use by another user
            $stmt = $pdo->prepare("SELECT userID FROM users WHERE email = ? AND userID != ?");
            $stmt->execute([$email, $_SESSION['user_id']]);
            if ($stmt->fetchColumn()) {
                throw new Exception("Email address is already in use by another account.");
            }
            
            try {
                // Update the user record
                $stmt = $pdo->prepare("UPDATE users SET fullName = ?, email = ?, contact_number = ?, employeeID = ?, department_id = ?, updated_at = CURRENT_TIMESTAMP WHERE userID = ?");
                $stmt->execute([$fullName, $email, $contact_number, $employeeID, $department_id, $_SESSION['user_id']]);
                
                // Update session data
                $_SESSION['fullName'] = $fullName;
                $_SESSION['email'] = $email;
                $_SESSION['department_id'] = $department_id;
                
                // Check if profile is now complete
                $is_profile_complete = !empty($employeeID) && !empty($department_id);
                
                // Update session variable to reflect current status
                $_SESSION['is_profile_complete'] = $is_profile_complete;
                
                if ($is_profile_complete) {
                    $success = "Your profile has been updated successfully. You can now submit claims.";
                } else {
                    $success = "Your profile has been updated successfully.";
                }
            } catch (PDOException $e) {
                throw new Exception("Database error: " . $e->getMessage());
            }
            
        } elseif (isset($_POST['change_password'])) {
            // Password change
            $currentPassword = $_POST['current_password'];
            $newPassword = $_POST['new_password'];
            $confirmPassword = $_POST['confirm_password'];
            
            // Validate input
            if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
                throw new Exception("All password fields are required.");
            }
            
            if ($newPassword !== $confirmPassword) {
                throw new Exception("New passwords do not match.");
            }
            
            if (strlen($newPassword) < 8) {
                throw new Exception("New password must be at least 8 characters long.");
            }
            
            // Verify current password
            if (!password_verify($currentPassword, $user['password'])) {
                throw new Exception("Current password is incorrect.");
            }
            
            try {
                // Update password
                $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE userID = ?");
                $stmt->execute([$passwordHash, $_SESSION['user_id']]);
                
                $success = "Your password has been changed successfully.";
            } catch (PDOException $e) {
                throw new Exception("Database error: " . $e->getMessage());
            }
        }
        
        // Reload user data after update
        $stmt = $pdo->prepare("SELECT * FROM users WHERE userID = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        // Re-check if profile is complete
        $is_profile_complete = !empty($user['employeeID']) && !empty($user['department_id']);
    }
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Include header
include 'includes/header.php';
?>

<div class="max-w-4xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h1>
        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
            Manage your account information and settings
        </p>
    </div>

    <?php if (!isset($is_profile_complete) || !$is_profile_complete): ?>
    <div class="mb-6 rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4 border-l-4 border-yellow-500">
        <div class="flex">
            <div class="flex-shrink-0">
                <i data-lucide="alert-triangle" class="h-5 w-5 text-yellow-400 dark:text-yellow-500"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-200">Profile Incomplete</h3>
                <div class="mt-2 text-sm text-yellow-700 dark:text-yellow-300">
                    <p>Please complete your profile by adding your employee ID and selecting your department. You won't be able to submit claims until your profile is complete.</p>
                </div>
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
                    <h3 class="text-sm font-medium text-red-800 dark:text-red-200"><?= $error ?></h3>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <?php if ($success): ?>
        <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4">
            <div class="flex">
                <div class="flex-shrink-0">
                    <i data-lucide="check-circle" class="h-5 w-5 text-green-400 dark:text-green-500"></i>
                </div>
                <div class="ml-3">
                    <h3 class="text-sm font-medium text-green-800 dark:text-green-200"><?= $success ?></h3>
                </div>
            </div>
        </div>
    <?php endif; ?>
    
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden sm:rounded-lg">
        <div class="px-4 py-5 sm:px-6 border-b border-gray-200 dark:border-gray-700">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white">Account Information</h3>
            <p class="mt-1 max-w-2xl text-sm text-gray-500 dark:text-gray-400">Personal details and settings.</p>
        </div>
        
        <div class="px-4 py-5 sm:p-6 border-b border-gray-200 dark:border-gray-700">
            <form action="profile.php" method="post" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="update_profile" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="fullName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Full Name <span class="text-red-600">*</span>
                        </label>
                        <input type="text" name="fullName" id="fullName" value="<?= htmlspecialchars($user['fullName']) ?>" required
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email Address <span class="text-red-600">*</span>
                        </label>
                        <input type="email" name="email" id="email" value="<?= htmlspecialchars($user['email']) ?>" required
                               class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                    </div>
                    
                    <div>
                        <label for="employeeID" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Employee ID <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="id-card" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="text" name="employeeID" id="employeeID" value="<?= htmlspecialchars($user['employeeID'] ?? '') ?>" required
                                   placeholder="e.g. EMP001" 
                                   class="block w-full pl-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Your unique employee identification number
                        </p>
                    </div>
                    
                    <div>
                        <label for="department_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Department <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="building" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <select name="department_id" id="department_id" required
                                   class="block w-full pl-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                                <option value="">-- Select Department --</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?= $dept['department_id'] ?>" <?= ($user['department_id'] == $dept['department_id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept['department_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            The department you belong to
                        </p>
                    </div>
                    
                    <div>
                        <label for="contact_number" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Phone Number
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="phone" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="tel" name="contact_number" id="contact_number" value="<?= htmlspecialchars($user['contact_number'] ?? '') ?>"
                                   class="block w-full pl-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Role
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="user-check" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="text" id="role" value="<?= htmlspecialchars($user['role']) ?>" disabled
                                   class="block w-full pl-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-400 rounded-md shadow-sm bg-gray-50 dark:bg-gray-800">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        <i data-lucide="save" class="h-4 w-4 mr-2"></i>
                        Save Changes
                    </button>
                </div>
            </form>
        </div>
        
        <div class="px-4 py-5 sm:p-6">
            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Change Password</h3>
            
            <form action="profile.php" method="post" class="space-y-6">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="change_password" value="1">
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="md:col-span-2">
                        <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Current Password <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="key" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="password" name="current_password" id="current_password" required
                                   class="block w-full pl-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                        </div>
                    </div>
                    
                    <div>
                        <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            New Password <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="password" name="new_password" id="new_password" required
                                   class="block w-full pl-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            Password must be at least 8 characters long.
                        </p>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Confirm New Password <span class="text-red-600">*</span>
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="check-circle" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="password" name="confirm_password" id="confirm_password" required
                                   class="block w-full pl-10 border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                        </div>
                    </div>
                </div>
                
                <div class="flex justify-end">
                    <button type="submit" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none">
                        <i data-lucide="key" class="h-4 w-4 mr-2"></i>
                        Change Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize Lucide icons
    lucide.createIcons();
});
</script>

<?php 
// Store profile completion status in session for other pages to access
$_SESSION['is_profile_complete'] = $is_profile_complete ?? false;
include 'includes/footer.php'; 
?> 