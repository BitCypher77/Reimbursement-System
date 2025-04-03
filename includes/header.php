<?php ?>
<!DOCTYPE html>
<html lang="en" class="<?= isset($_COOKIE['theme']) ? $_COOKIE['theme'] : 'light' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uzima Reimbursement</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <script>
        // Tailwind dark mode configuration
        tailwind.config = {
            darkMode: 'class'
        }
        
        // Check for dark mode preference
        if (localStorage.getItem('theme') === 'dark' || 
            (!localStorage.getItem('theme') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition-colors">
<div id="toast" class="hidden fixed top-4 right-4 p-4 rounded-lg shadow-lg bg-green-500 text-white"></div>

<nav class="bg-blue-600 dark:bg-gray-800 text-white shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16 items-center">
            <a href="<?php echo getRelativePathToRoot(); ?>dashboard.php" class="flex items-center gap-2">
                <i data-lucide="wallet" class="w-8 h-8"></i>
                <span class="text-xl font-bold">Uzima</span>
            </a>
            
            <div class="flex items-center gap-6">
                <!-- Show all navigation options regardless of role -->
                <?php if ($_SESSION['role'] !== 'Admin'): ?>
                <a href="<?php echo getRelativePathToRoot(); ?>submit_claim.php" class="hover:text-blue-200 flex items-center gap-1">
                    <i data-lucide="plus"></i> New Claim
                </a>
                <?php endif; ?>
                
                <a href="<?php echo getRelativePathToRoot(); ?>reports.php" class="hover:text-blue-200 flex items-center gap-1">
                    <i data-lucide="line-chart"></i> Reports
                </a>
                
                <a href="<?php echo getRelativePathToRoot(); ?>notifications.php" class="hover:text-blue-200 flex items-center gap-1">
                    <i data-lucide="bell"></i> Notifications
                </a>
                
                <a href="<?php echo getRelativePathToRoot(); ?>messages.php" class="hover:text-blue-200 flex items-center gap-1">
                    <i data-lucide="mail"></i> Messages
                </a>
                
                <a href="<?php echo getRelativePathToRoot(); ?>approvals.php" class="hover:text-blue-200 flex items-center gap-1">
                    <i data-lucide="check-circle"></i> Approvals
                </a>
                
                <!-- Dark Mode Toggle -->
                <button id="themeToggle" class="hover:text-blue-200 flex items-center gap-1" aria-label="Toggle dark mode">
                    <i data-lucide="moon" class="dark:hidden"></i>
                    <i data-lucide="sun" class="hidden dark:block"></i>
                </button>
                
                <div class="relative">
                    <button id="userDropdownButton" class="flex items-center gap-2 px-3 py-2 rounded-md border border-transparent hover:bg-blue-700 hover:text-white transition-colors focus:outline-none">
                        <i data-lucide="user"></i> <?= $_SESSION['fullName'] ?>
                        <i data-lucide="chevron-down" class="h-4 w-4 ml-1"></i>
                    </button>
                    <div id="userDropdownMenu" class="absolute hidden right-0 mt-2 w-56 bg-white dark:bg-gray-700 rounded-md shadow-lg z-10 border border-gray-200 dark:border-gray-600">
                        <div class="py-1">
                            <a href="<?php echo getRelativePathToRoot(); ?>profile.php" class="block px-4 py-3 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center">
                                <i data-lucide="user" class="h-5 w-5 mr-3 text-gray-500 dark:text-gray-400"></i> Profile
                            </a>
                            <?php if ($_SESSION['role'] === 'Admin'): ?>
                            <a href="<?php echo getRelativePathToRoot(); ?>admin/users.php" class="block px-4 py-3 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center">
                                <i data-lucide="users" class="h-5 w-5 mr-3 text-gray-500 dark:text-gray-400"></i> Manage Users
                            </a>
                            <a href="<?php echo getRelativePathToRoot(); ?>admin/settings.php" class="block px-4 py-3 text-gray-800 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center">
                                <i data-lucide="settings" class="h-5 w-5 mr-3 text-gray-500 dark:text-gray-400"></i> System Settings
                            </a>
                            <?php endif; ?>
                            <div class="border-t border-gray-200 dark:border-gray-600 my-1"></div>
                            <a href="<?php echo getRelativePathToRoot(); ?>logout.php" class="block px-4 py-3 text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600 flex items-center">
                                <i data-lucide="log-out" class="h-5 w-5 mr-3 text-red-500 dark:text-red-400"></i> Logout
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</nav>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

<!-- Flash Message Display -->
<?php if (isset($_SESSION['flash_message'])): ?>
    <div class="mb-6 rounded-md p-4 <?php 
        if ($_SESSION['flash_message']['type'] == 'warning') echo 'bg-yellow-50 dark:bg-yellow-900/30 border-l-4 border-yellow-500';
        elseif ($_SESSION['flash_message']['type'] == 'error') echo 'bg-red-50 dark:bg-red-900/30 border-l-4 border-red-500';
        elseif ($_SESSION['flash_message']['type'] == 'success') echo 'bg-green-50 dark:bg-green-900/30 border-l-4 border-green-500';
        else echo 'bg-blue-50 dark:bg-blue-900/30 border-l-4 border-blue-500';
    ?>">
        <div class="flex">
            <div class="flex-shrink-0">
                <i data-lucide="<?php 
                    if ($_SESSION['flash_message']['type'] == 'warning') echo 'alert-triangle'; 
                    elseif ($_SESSION['flash_message']['type'] == 'error') echo 'alert-circle';
                    elseif ($_SESSION['flash_message']['type'] == 'success') echo 'check-circle';
                    else echo 'info';
                ?>" class="h-5 w-5 <?php
                    if ($_SESSION['flash_message']['type'] == 'warning') echo 'text-yellow-400 dark:text-yellow-500';
                    elseif ($_SESSION['flash_message']['type'] == 'error') echo 'text-red-400 dark:text-red-500';
                    elseif ($_SESSION['flash_message']['type'] == 'success') echo 'text-green-400 dark:text-green-500';
                    else echo 'text-blue-400 dark:text-blue-500';
                ?>"></i>
            </div>
            <div class="ml-3">
                <h3 class="text-sm font-medium <?php
                    if ($_SESSION['flash_message']['type'] == 'warning') echo 'text-yellow-800 dark:text-yellow-200';
                    elseif ($_SESSION['flash_message']['type'] == 'error') echo 'text-red-800 dark:text-red-200';
                    elseif ($_SESSION['flash_message']['type'] == 'success') echo 'text-green-800 dark:text-green-200';
                    else echo 'text-blue-800 dark:text-blue-200';
                ?>"><?= htmlspecialchars($_SESSION['flash_message']['message']) ?></h3>
            </div>
        </div>
    </div>
    <?php unset($_SESSION['flash_message']); ?>
<?php endif; ?>

<script>
// User dropdown toggle functionality
document.addEventListener('DOMContentLoaded', function() {
    const userDropdownButton = document.getElementById('userDropdownButton');
    const userDropdownMenu = document.getElementById('userDropdownMenu');
    
    // Toggle dropdown on button click
    userDropdownButton.addEventListener('click', function(e) {
        e.stopPropagation();
        const isOpen = !userDropdownMenu.classList.contains('hidden');
        userDropdownMenu.classList.toggle('hidden');
        
        // Add/remove visual indicator when dropdown is open
        if (!isOpen) {
            userDropdownButton.classList.add('bg-blue-700');
            userDropdownButton.classList.add('text-white');
        } else {
            userDropdownButton.classList.remove('bg-blue-700');
            userDropdownButton.classList.remove('text-white');
        }
    });
    
    // Close dropdown when clicking elsewhere on the page
    document.addEventListener('click', function(e) {
        if (!userDropdownButton.contains(e.target) && !userDropdownMenu.contains(e.target)) {
            userDropdownMenu.classList.add('hidden');
            userDropdownButton.classList.remove('bg-blue-700');
            userDropdownButton.classList.remove('text-white');
        }
    });
    
    // Prevent dropdown from closing when clicking inside it
    userDropdownMenu.addEventListener('click', function(e) {
        e.stopPropagation();
    });
    
    // Initialize Lucide icons inside the dropdown
    lucide.createIcons();
});
</script>

<?php
// Helper function to determine relative path to root
function getRelativePathToRoot() {
    // Get the current script path relative to the document root
    $currentPath = $_SERVER['SCRIPT_NAME'];
    
    // Count directory levels to determine path to root
    $dirLevels = substr_count($currentPath, '/') - 1;
    
    if ($dirLevels <= 0) {
        return './';
    } else {
        // For subdirectories, create the appropriate relative path
        return str_repeat('../', $dirLevels);
    }
}
?>