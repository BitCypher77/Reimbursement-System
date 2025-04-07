<?php
// Temporary error display for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include 'config.php'; 

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

// Handle session expiration message
$session_expired = isset($_GET['session_expired']) ? true : false;

// Get company name from settings
$company_name = getSystemSetting('company_name', 'Uzima Corporation');
$company_logo = getSystemSetting('company_logo', 'assets/images/uzima_logo.png');

// Check if logo file exists and adjust accordingly
$logo_html = '';
if (file_exists($company_logo)) {
    $logo_html = '<img class="mx-auto h-16 w-auto" src="' . htmlspecialchars($company_logo) . '" alt="' . htmlspecialchars($company_name) . '">';
} elseif (file_exists('assets/images/uzima_logo.svg')) {
    $logo_html = '<object class="mx-auto h-16 w-auto" type="image/svg+xml" data="assets/images/uzima_logo.svg"></object>';
} elseif (file_exists('assets/images/uzima_logo.png.html')) {
    $logo_html = '<iframe class="mx-auto border-0" src="assets/images/uzima_logo.png.html" style="border:none; width:200px; height:90px;"></iframe>';
} else {
    $logo_html = '<div class="mx-auto h-16 bg-blue-600 text-white font-bold text-xl flex items-center justify-center px-4 rounded">UZIMA</div>';
}
?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | <?= htmlspecialchars($company_name) ?> Expense Management</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="assets/images/favicon.png">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#f5f3ff',
                            100: '#ede9fe',
                            200: '#ddd6fe',
                            300: '#c4b5fd',
                            400: '#a78bfa',
                            500: '#8b5cf6',
                            600: '#7c3aed',
                            700: '#6d28d9',
                            800: '#5b21b6',
                            900: '#4c1d95',
                        },
                        secondary: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                    }
                }
            }
        }
    </script>
    
    <!-- Inter Font -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-image: url('https://images.unsplash.com/photo-1557682250-33bd709cbe85?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2829&q=80');
            background-size: cover;
            background-position: center;
            background-repeat: no-repeat;
            height: 100%;
        }
        
        .glass-effect {
            backdrop-filter: blur(15px);
            background-color: rgba(255, 255, 255, 0.7);
            box-shadow: 0 8px 32px 0 rgba(77, 28, 120, 0.37);
            border: 1px solid rgba(255, 255, 255, 0.18);
            transition: all 0.3s ease;
        }
        
        .glass-effect:hover {
            box-shadow: 0 8px 32px 0 rgba(77, 28, 120, 0.47);
            transform: translateY(-2px);
        }
        
        .dark .glass-effect {
            background-color: rgba(15, 23, 42, 0.8);
            border: 1px solid rgba(255, 255, 255, 0.08);
        }
        
        .login-animation {
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-15px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        
        input {
            transition: all 0.3s ease;
        }
        
        input:focus {
            transform: translateY(-2px);
        }
        
        button {
            transition: all 0.3s ease;
        }
        
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px -5px rgba(109, 40, 217, 0.5);
        }
        
        .welcome-text {
            background: linear-gradient(90deg, #7c3aed, #2dd4bf);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            animation: gradient 8s ease infinite;
            background-size: 200% auto;
        }
        
        .footer-wave {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 50px;
            background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 1200 120' preserveAspectRatio='none'%3E%3Cpath d='M0,0V46.29c47.79,22.2,103.59,32.17,158,28,70.36-5.37,136.33-33.31,206.8-37.5C438.64,32.43,512.34,53.67,583,72.05c69.27,18,138.3,24.88,209.4,13.08,36.15-6,69.85-17.84,104.45-29.34C989.49,25,1113-14.29,1200,52.47V0Z' opacity='.25' fill='%237c3aed'%3E%3C/path%3E%3Cpath d='M0,0V15.81C13,36.92,27.64,56.86,47.69,72.05,99.41,111.27,165,111,224.58,91.58c31.15-10.15,60.09-26.07,89.67-39.8,40.92-19,84.73-46,130.83-49.67,36.26-2.85,70.9,9.42,98.6,31.56,31.77,25.39,62.32,62,103.63,73,40.44,10.79,81.35-6.69,119.13-24.28s75.16-39,116.92-43.05c59.73-5.85,113.28,22.88,168.9,38.84,30.2,8.66,59,6.17,87.09-7.5,22.43-10.89,48-26.93,60.65-49.24V0Z' opacity='.5' fill='%230d9488'%3E%3C/path%3E%3Cpath d='M0,0V5.63C149.93,59,314.09,71.32,475.83,42.57c43-7.64,84.23-20.12,127.61-26.46,59-8.63,112.48,12.24,165.56,35.4C827.93,77.22,886,95.24,951.2,90c86.53-7,172.46-45.71,248.8-84.81V0Z' fill='%2314b8a6' opacity='.25'%3E%3C/path%3E%3C/svg%3E");
            background-size: cover;
        }
        
        @keyframes gradient {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .bottom-links a {
            transition: all 0.3s ease;
        }
        
        .bottom-links a:hover {
            transform: translateY(-3px);
            color: #7c3aed;
        }
        
        .dark .bottom-links a:hover {
            color: #2dd4bf;
        }
    </style>
</head>
<body class="h-full">
    <div class="footer-wave"></div>
    <div class="flex min-h-full flex-col justify-center py-6 sm:px-6 lg:px-8 relative">
        <div class="absolute inset-0 bg-gradient-to-r from-primary-600/40 to-secondary-600/40 backdrop-opacity-60 pointer-events-none"></div>
        
        

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <div class="text-center">
                <h2 class="mt-2 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white welcome-text">
                    Welcome Back
                </h2>
                <p class="mt-2 text-xl font-semibold text-primary-600 dark:text-primary-400">
                    Expense Management System
                </p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Sign in to manage your expenses with ease and efficiency
                </p>
            </div>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <div class="glass-effect py-8 px-4 shadow-2xl sm:rounded-xl sm:px-10 border border-gray-200/50 dark:border-gray-700/50">
                <?php if (isset($_GET['error'])): ?>
                    <div class="mb-4 rounded-md bg-red-50 dark:bg-red-900/30 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="alert-circle" class="h-5 w-5 text-red-400 dark:text-red-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800 dark:text-red-300">
                                    <?= htmlspecialchars($_GET['error']) ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if ($session_expired): ?>
                    <div class="mb-4 rounded-md bg-yellow-50 dark:bg-yellow-900/30 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="clock" class="h-5 w-5 text-yellow-400 dark:text-yellow-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-yellow-800 dark:text-yellow-300">
                                    Your session has expired. Please log in again.
                                </h3>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <form class="space-y-6" action="login.php" method="post">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div class="flex justify-center">
                        <div class="login-animation">
                            <i data-lucide="wallet" class="h-20 w-20 text-primary-600 dark:text-primary-400"></i>
                        </div>
                    </div>
                    
                    <div class="text-center mb-6">
                        <p class="text-sm text-primary-600 dark:text-primary-400 font-medium">Your financial journey begins here!</p>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email address
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" autocomplete="email" required 
                                   class="block w-full pl-10 py-3 placeholder-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="name@company.com">
                        </div>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Password
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="lock" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="password" id="password" name="password" autocomplete="current-password" required 
                                   class="block w-full pl-10 py-3 placeholder-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="••••••••">
                        </div>
                    </div>

                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <input id="remember-me" name="remember_me" type="checkbox" 
                                   class="h-4 w-4 text-primary-600 focus:ring-primary-500 border-gray-300 rounded dark:border-gray-600 dark:bg-gray-700">
                            <label for="remember-me" class="ml-2 block text-sm text-gray-700 dark:text-gray-300">
                                Remember me
                            </label>
                        </div>

                        <div class="text-sm">
                            <a href="forgot_password.php" class="font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                                Forgot your password?
                            </a>
                        </div>
                    </div>

                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i data-lucide="log-in" class="h-5 w-5 text-primary-500 group-hover:text-primary-400"></i>
                            </span>
                            Sign in
                        </button>
                    </div>
                </form>
                
                <div class="mt-6">
                    <div class="relative">
                        <div class="absolute inset-0 flex items-center">
                            <div class="w-full border-t border-gray-300 dark:border-gray-600"></div>
                        </div>
                        <div class="relative flex justify-center text-sm">
                            <span class="px-2 glass-effect text-gray-500 dark:text-gray-400">
                                New to the system?
                            </span>
                        </div>
                    </div>

                    <div class="mt-6">
                        <a href="register.php" 
                           class="w-full flex justify-center py-3 px-4 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm text-sm font-medium text-gray-700 dark:text-gray-300 bg-white/70 dark:bg-gray-700/70 hover:bg-gray-50 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            Create an account
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="mt-8 glass-effect rounded-lg p-6 text-center">
                <h3 class="text-lg font-bold text-primary-700 dark:text-primary-300 mb-2">About Uzima Reimbursement System</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    Uzima's expense management platform streamlines the reimbursement process, making it effortless to track, 
                    submit, and approve expenses. Our solution reduces processing time by up to 70% while ensuring 
                    compliance with company policies.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="flex flex-col items-center">
                        <i data-lucide="zap" class="h-8 w-8 text-primary-500 mb-2"></i>
                        <h4 class="text-sm font-semibold">Fast Processing</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Submit and get approved quickly</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <i data-lucide="shield" class="h-8 w-8 text-primary-500 mb-2"></i>
                        <h4 class="text-sm font-semibold">Secure System</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Your data is always protected</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <i data-lucide="bar-chart" class="h-8 w-8 text-primary-500 mb-2"></i>
                        <h4 class="text-sm font-semibold">Detailed Reports</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Track all your expenses easily</p>
                    </div>
                </div>
                <div class="flex justify-center space-x-4 bottom-links">
                    <a href="#" class="text-xs text-gray-600 dark:text-gray-300 flex items-center">
                        <i data-lucide="info" class="h-3 w-3 mr-1"></i> About Us
                    </a>
                    <a href="#" class="text-xs text-gray-600 dark:text-gray-300 flex items-center">
                        <i data-lucide="mail" class="h-3 w-3 mr-1"></i> Contact
                    </a>
                    <a href="#" class="text-xs text-gray-600 dark:text-gray-300 flex items-center">
                        <i data-lucide="help-circle" class="h-3 w-3 mr-1"></i> Help
                    </a>
                </div>
            </div>
            
            <div class="mt-4 text-center">
                <div class="text-xs text-gray-100 dark:text-gray-300">
                    <p>&copy; <?= date('Y') ?> <?= htmlspecialchars($company_name) ?>. All rights reserved.</p>
                    <p class="mt-1">Version 2.0.0</p>
                </div>
                <div class="mt-4 flex justify-center space-x-4">
                    <button id="themeToggle" class="text-gray-100 dark:text-gray-300 hover:text-white dark:hover:text-white">
                        <i data-lucide="moon" class="h-5 w-5 dark:hidden"></i>
                        <i data-lucide="sun" class="h-5 w-5 hidden dark:block"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Theme toggle script -->
    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
        
        // Theme Toggle
        document.getElementById('themeToggle').addEventListener('click', () => {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
            document.cookie = `theme=${isDark ? 'light' : 'dark'}; path=/; max-age=31536000`;
        });
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
    </script>
</body>
</html>