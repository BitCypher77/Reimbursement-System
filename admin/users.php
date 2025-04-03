<?php
include '../config.php';
requireRole('Admin');

// Initialize variables
$success = null;
$error = null;

// User Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    try {
        // Validate CSRF token
        if (!isset($_POST['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
            throw new Exception("Invalid request. Please try again.");
        }
        
        $action = $_POST['action'];
        
        switch ($action) {
            case 'create':
                // User creation logic would go here
                // For now, we'll just show a placeholder success message
                $success = "New user created successfully.";
                break;
                
            case 'update':
                // Update user details would go here
                // For now, we'll just show a placeholder success message
                $success = "User details updated successfully.";
                break;
                
            case 'activate':
                // Activate user
                $stmt = $pdo->prepare("UPDATE users SET is_active = 1 WHERE userID = ?");
                $stmt->execute([$_POST['user_id']]);
                $success = "User activated successfully.";
                break;
                
            case 'deactivate':
                // Deactivate user
                $stmt = $pdo->prepare("UPDATE users SET is_active = 0 WHERE userID = ?");
                $stmt->execute([$_POST['user_id']]);
                $success = "User deactivated successfully.";
                break;
        }
        
        // Log the activity
        logUserActivity($_SESSION['user_id'], 'user_management', "Performed action: $action on user ID: " . ($_POST['user_id'] ?? 'new user'));
        
    } catch (Exception $e) {
        error_log("User management error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Fetch all users
try {
    $stmt = $pdo->query("SELECT * FROM users ORDER BY fullName");
    $users = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("Error fetching users: " . $e->getMessage());
    $error = "Could not retrieve users. Please try again later.";
    $users = [];
}

include '../includes/header.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="mb-6 flex justify-between items-center">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">User Management</h1>
        <button id="newUserBtn" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md shadow-sm text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
            <i data-lucide="user-plus" class="h-4 w-4 mr-2"></i>
            Add New User
        </button>
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
    
    <!-- User list -->
    <div class="bg-white dark:bg-gray-800 shadow overflow-hidden rounded-lg">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    <?php if (empty($users)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-center text-gray-500 dark:text-gray-400">
                                No users found
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($user['fullName']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?= htmlspecialchars($user['email']) ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?= getRoleBadgeClass($user['role']) ?>">
                                        <?= $user['role'] ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <?php if ($user['is_active']): ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100">
                                            Active
                                        </span>
                                    <?php else: ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 dark:bg-red-800 dark:text-red-100">
                                            Inactive
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-300">
                                    <button 
                                        class="editUserBtn text-blue-600 dark:text-blue-400 hover:underline mr-3"
                                        data-user-id="<?= $user['userID'] ?>"
                                        data-user-name="<?= htmlspecialchars($user['fullName']) ?>"
                                        data-user-email="<?= htmlspecialchars($user['email']) ?>"
                                        data-user-role="<?= $user['role'] ?>"
                                    >
                                        Edit
                                    </button>
                                    
                                    <?php if ($user['userID'] != $_SESSION['user_id']): ?>
                                        <?php if ($user['is_active']): ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="action" value="deactivate">
                                                <input type="hidden" name="user_id" value="<?= $user['userID'] ?>">
                                                <button type="submit" class="text-red-600 dark:text-red-400 hover:underline">
                                                    Deactivate
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                                <input type="hidden" name="action" value="activate">
                                                <input type="hidden" name="user_id" value="<?= $user['userID'] ?>">
                                                <button type="submit" class="text-green-600 dark:text-green-400 hover:underline">
                                                    Activate
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="text-gray-400 dark:text-gray-500">Current User</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- New User Modal -->
    <div id="newUserModal" class="fixed inset-0 overflow-y-auto hidden" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Add New User</h3>
                    <form id="newUserForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="create">
                        
                        <div class="mb-4">
                            <label for="fullName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                            <input type="text" name="fullName" id="fullName" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" id="email" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Password</label>
                            <input type="password" name="password" id="password" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <select name="role" id="role" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm" required>
                                <option value="Admin">Admin</option>
                                <option value="FinanceOfficer">Finance Officer</option>
                                <option value="Manager">Manager</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="newUserForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Create User
                    </button>
                    <button type="button" id="cancelNewUser" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="fixed inset-0 overflow-y-auto hidden" aria-modal="true">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 dark:bg-gray-900 dark:bg-opacity-75 transition-opacity"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">&#8203;</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-white mb-4">Edit User</h3>
                    <form id="editUserForm" method="POST">
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                        <input type="hidden" name="action" value="update">
                        <input type="hidden" name="user_id" id="edit_user_id">
                        
                        <div class="mb-4">
                            <label for="edit_fullName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Full Name</label>
                            <input type="text" name="fullName" id="edit_fullName" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="edit_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" name="email" id="edit_email" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="edit_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">New Password (leave blank to keep current)</label>
                            <input type="password" name="password" id="edit_password" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm">
                        </div>
                        
                        <div class="mb-4">
                            <label for="edit_role" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Role</label>
                            <select name="role" id="edit_role" class="mt-1 block w-full border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:ring-blue-500 focus:border-blue-500 rounded-md shadow-sm" required>
                                <option value="Admin">Admin</option>
                                <option value="FinanceOfficer">Finance Officer</option>
                                <option value="Manager">Manager</option>
                                <option value="Employee">Employee</option>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="submit" form="editUserForm" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Update User
                    </button>
                    <button type="button" id="cancelEditUser" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // New User Modal
    const newUserBtn = document.getElementById('newUserBtn');
    const newUserModal = document.getElementById('newUserModal');
    const cancelNewUser = document.getElementById('cancelNewUser');
    
    newUserBtn.addEventListener('click', function() {
        newUserModal.classList.remove('hidden');
    });
    
    cancelNewUser.addEventListener('click', function() {
        newUserModal.classList.add('hidden');
    });
    
    // Edit User Modal
    const editUserBtns = document.querySelectorAll('.editUserBtn');
    const editUserModal = document.getElementById('editUserModal');
    const cancelEditUser = document.getElementById('cancelEditUser');
    
    editUserBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const userId = this.getAttribute('data-user-id');
            const userName = this.getAttribute('data-user-name');
            const userEmail = this.getAttribute('data-user-email');
            const userRole = this.getAttribute('data-user-role');
            
            document.getElementById('edit_user_id').value = userId;
            document.getElementById('edit_fullName').value = userName;
            document.getElementById('edit_email').value = userEmail;
            document.getElementById('edit_role').value = userRole;
            document.getElementById('edit_password').value = '';
            
            editUserModal.classList.remove('hidden');
        });
    });
    
    cancelEditUser.addEventListener('click', function() {
        editUserModal.classList.add('hidden');
    });
    
    // Close modals when clicking outside
    window.addEventListener('click', function(e) {
        if (e.target === newUserModal) {
            newUserModal.classList.add('hidden');
        }
        if (e.target === editUserModal) {
            editUserModal.classList.add('hidden');
        }
    });
});
</script>

<?php
// Helper function to get role badge class
function getRoleBadgeClass($role) {
    switch ($role) {
        case 'Admin':
            return 'bg-purple-100 text-purple-800 dark:bg-purple-800 dark:text-purple-100';
        case 'FinanceOfficer':
            return 'bg-green-100 text-green-800 dark:bg-green-800 dark:text-green-100';
        case 'Manager':
            return 'bg-blue-100 text-blue-800 dark:bg-blue-800 dark:text-blue-100';
        case 'Employee':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300';
    }
}

include '../includes/footer.php';
?>