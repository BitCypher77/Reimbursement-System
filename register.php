<?php include 'config.php'; ?>
<!DOCTYPE html>
<html lang="en" class="h-full bg-gray-50 dark:bg-gray-900">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account | <?= htmlspecialchars(getSystemSetting('company_name', 'Uzima Corporation')) ?></title>
    
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
        
        .requirement {
            transition: color 0.3s ease, transform 0.3s ease;
        }
        
        .requirement-met {
            color: #10b981;
            transform: translateX(5px);
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

        <div class="flex justify-center items-center relative z-10 mb-4">
            <?php 
            $company_logo = getSystemSetting('company_logo', 'assets/images/uzima_logo.png');
            $company_name = getSystemSetting('company_name', 'Uzima Corporation');
            
            // Check for logo file
            if (file_exists($company_logo)) {
                echo '<img src="' . htmlspecialchars($company_logo) . '" alt="' . htmlspecialchars($company_name) . '" class="mx-auto h-16">';
            } elseif (file_exists('assets/images/uzima_logo.svg')) {
                echo '<object class="mx-auto h-16 w-auto" type="image/svg+xml" data="assets/images/uzima_logo.svg"></object>';
            } elseif (file_exists('assets/images/uzima_logo.png.html')) {
                echo '<iframe src="assets/images/uzima_logo.png.html" style="border:none; width:200px; height:90px;" class="mx-auto"></iframe>';
            } else {
                echo '<div class="mx-auto h-16 bg-primary-600 text-white font-bold text-xl flex items-center justify-center px-4 rounded">UZIMA</div>';
            }
            ?>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <div class="text-center">
                <h2 class="mt-2 text-4xl font-extrabold tracking-tight text-gray-900 dark:text-white welcome-text">
                    Join Our Platform
                </h2>
                <p class="mt-2 text-xl font-semibold text-primary-600 dark:text-primary-400">
                    Create Your Account
                </p>
                <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                    Sign up to start managing your expenses efficiently
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
                
                <?php if (isset($_GET['success'])): ?>
                    <div class="mb-4 rounded-md bg-green-50 dark:bg-green-900/30 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <i data-lucide="check-circle" class="h-5 w-5 text-green-400 dark:text-green-500"></i>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-green-800 dark:text-green-300">
                                    <?= htmlspecialchars($_GET['success']) ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="flex justify-center mb-6">
                    <div class="login-animation">
                        <i data-lucide="user-plus" class="h-20 w-20 text-primary-600 dark:text-primary-400"></i>
                    </div>
                </div>
                
                <div class="text-center mb-6">
                    <p class="text-sm text-primary-600 dark:text-primary-400 font-medium">Start your expense management journey today!</p>
                </div>
                
                <form action="register_process.php" method="post" id="registrationForm" class="space-y-6">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    
                    <div>
                        <label for="fullName" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Full Name
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="user" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="text" id="fullName" name="fullName" required
                                   class="block w-full pl-10 py-3 placeholder-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="John Doe">
                        </div>
                    </div>
                    
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Email Address
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="mail" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="email" id="email" name="email" required
                                   class="block w-full pl-10 py-3 placeholder-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="john.doe@example.com">
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
                            <input type="password" id="password" name="password" required
                                   class="block w-full pl-10 py-3 placeholder-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="••••••••">
                        </div>
                        <div class="mt-2 px-2 py-2 bg-gray-50 dark:bg-gray-800 rounded-lg">
                            <p class="text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Password must:</p>
                            <ul class="text-xs space-y-1 text-gray-500 dark:text-gray-400">
                                <li id="length-requirement" class="requirement flex items-center">
                                    <i data-lucide="circle" class="h-3 w-3 mr-1"></i>
                                    Be at least 8 characters long
                                </li>
                                <li id="uppercase-requirement" class="requirement flex items-center">
                                    <i data-lucide="circle" class="h-3 w-3 mr-1"></i>
                                    Contain at least one uppercase letter
                                </li>
                                <li id="lowercase-requirement" class="requirement flex items-center">
                                    <i data-lucide="circle" class="h-3 w-3 mr-1"></i>
                                    Contain at least one lowercase letter
                                </li>
                                <li id="number-requirement" class="requirement flex items-center">
                                    <i data-lucide="circle" class="h-3 w-3 mr-1"></i>
                                    Contain at least one number
                                </li>
                            </ul>
                        </div>
                    </div>
                    
                    <div>
                        <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Confirm Password
                        </label>
                        <div class="mt-1 relative rounded-md shadow-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i data-lucide="check-circle" class="h-5 w-5 text-gray-400"></i>
                            </div>
                            <input type="password" id="confirm_password" name="confirm_password" required
                                   class="block w-full pl-10 py-3 placeholder-gray-400 border border-gray-300 dark:border-gray-600 rounded-lg focus:outline-none focus:ring-2 focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                                   placeholder="••••••••">
                        </div>
                    </div>
                    
                    <div>
                        <button type="submit" 
                                class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-primary-600 hover:bg-primary-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary-500 transition-colors">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i data-lucide="user-plus" class="h-5 w-5 text-primary-500 group-hover:text-primary-400"></i>
                            </span>
                            Create Account
                        </button>
                    </div>
                </form>
                
                <div class="mt-6 text-center text-sm">
                    <span class="text-gray-700 dark:text-gray-300">Already have an account?</span>
                    <a href="index.php" class="ml-1 font-medium text-primary-600 hover:text-primary-500 dark:text-primary-400 dark:hover:text-primary-300">
                        Sign In
                    </a>
                </div>
            </div>
            
            <div class="mt-8 glass-effect rounded-lg p-6 text-center">
                <h3 class="text-lg font-bold text-primary-700 dark:text-primary-300 mb-2">Why Choose Uzima?</h3>
                <p class="text-sm text-gray-600 dark:text-gray-300 mb-4">
                    Join thousands of professionals who trust Uzima for their expense management needs. Our platform offers a 
                    user-friendly interface, powerful reporting tools, and seamless integration with your existing workflows.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                    <div class="flex flex-col items-center">
                        <i data-lucide="clock" class="h-8 w-8 text-primary-500 mb-2"></i>
                        <h4 class="text-sm font-semibold">Time-Saving</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Reduce expense processing time by 70%</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <i data-lucide="smartphone" class="h-8 w-8 text-primary-500 mb-2"></i>
                        <h4 class="text-sm font-semibold">Mobile Friendly</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Submit expenses on the go</p>
                    </div>
                    <div class="flex flex-col items-center">
                        <i data-lucide="trending-up" class="h-8 w-8 text-primary-500 mb-2"></i>
                        <h4 class="text-sm font-semibold">Analytics</h4>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Gain insights from spending patterns</p>
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
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();
        
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirm_password');
            const email = document.getElementById('email');
            
            const lengthRequirement = document.getElementById('length-requirement');
            const uppercaseRequirement = document.getElementById('uppercase-requirement');
            const lowercaseRequirement = document.getElementById('lowercase-requirement');
            const numberRequirement = document.getElementById('number-requirement');
            
            function validatePassword() {
                const value = password.value;
                
                // Check length
                if(value.length >= 8) {
                    lengthRequirement.classList.add('requirement-met');
                    lengthRequirement.querySelector('i').setAttribute('data-lucide', 'check-circle');
                } else {
                    lengthRequirement.classList.remove('requirement-met');
                    lengthRequirement.querySelector('i').setAttribute('data-lucide', 'circle');
                }
                
                // Check uppercase
                if(/[A-Z]/.test(value)) {
                    uppercaseRequirement.classList.add('requirement-met');
                    uppercaseRequirement.querySelector('i').setAttribute('data-lucide', 'check-circle');
                } else {
                    uppercaseRequirement.classList.remove('requirement-met');
                    uppercaseRequirement.querySelector('i').setAttribute('data-lucide', 'circle');
                }
                
                // Check lowercase
                if(/[a-z]/.test(value)) {
                    lowercaseRequirement.classList.add('requirement-met');
                    lowercaseRequirement.querySelector('i').setAttribute('data-lucide', 'check-circle');
                } else {
                    lowercaseRequirement.classList.remove('requirement-met');
                    lowercaseRequirement.querySelector('i').setAttribute('data-lucide', 'circle');
                }
                
                // Check number
                if(/[0-9]/.test(value)) {
                    numberRequirement.classList.add('requirement-met');
                    numberRequirement.querySelector('i').setAttribute('data-lucide', 'check-circle');
                } else {
                    numberRequirement.classList.remove('requirement-met');
                    numberRequirement.querySelector('i').setAttribute('data-lucide', 'circle');
                }
                
                // Update icons
                lucide.createIcons();
            }
            
            function validateConfirmPassword() {
                if(password.value === confirmPassword.value) {
                    confirmPassword.setCustomValidity('');
                } else {
                    confirmPassword.setCustomValidity('Passwords do not match');
                }
            }
            
            function validateEmail() {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (emailRegex.test(email.value)) {
                    email.setCustomValidity('');
                } else {
                    email.setCustomValidity('Please enter a valid email address');
                }
            }
            
            password.addEventListener('input', validatePassword);
            password.addEventListener('input', validateConfirmPassword);
            confirmPassword.addEventListener('input', validateConfirmPassword);
            email.addEventListener('input', validateEmail);
            
            form.addEventListener('submit', function(e) {
                validatePassword();
                validateConfirmPassword();
                validateEmail();
                
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
                
                const value = password.value;
                if(value.length < 8 || !/[A-Z]/.test(value) || !/[a-z]/.test(value) || !/[0-9]/.test(value)) {
                    alert('Please make sure your password meets all requirements');
                    e.preventDefault();
                }
            });
            
            // Initialize validation on page load
            validatePassword();
        });
        
        // Check for saved theme preference
        const savedTheme = localStorage.getItem('theme');
        if (savedTheme) {
            document.documentElement.classList.toggle('dark', savedTheme === 'dark');
        } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }
        
        // Add theme toggle button
        const themeToggle = document.createElement('button');
        themeToggle.id = 'themeToggle';
        themeToggle.className = 'text-gray-100 dark:text-gray-300 hover:text-white dark:hover:text-white mt-4 block mx-auto';
        themeToggle.innerHTML = '<i data-lucide="moon" class="h-5 w-5 dark:hidden"></i><i data-lucide="sun" class="h-5 w-5 hidden dark:block"></i>';
        
        document.querySelector('.mt-4.text-center').appendChild(themeToggle);
        
        themeToggle.addEventListener('click', () => {
            const html = document.documentElement;
            const isDark = html.classList.contains('dark');
            
            html.classList.toggle('dark');
            localStorage.setItem('theme', isDark ? 'light' : 'dark');
            document.cookie = `theme=${isDark ? 'light' : 'dark'}; path=/; max-age=31536000`;
            
            // Refresh icons
            lucide.createIcons();
        });
    </script>
</body>
</html>