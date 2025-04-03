</main>
<footer class="theme-blue dark:bg-gray-800 text-white py-6 mt-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
        <p class="text-sm">&copy; <?= date('Y') ?> Uzima Reimbursement System. All rights reserved.</p>
    </div>
</footer>

<script>
    // Theme Toggle
    document.addEventListener('DOMContentLoaded', function() {
        // Initialize Lucide Icons
        lucide.createIcons();
        
        // Theme toggle functionality
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            themeToggle.addEventListener('click', () => {
                const html = document.documentElement;
                const isDark = html.classList.contains('dark');
                
                if (isDark) {
                    html.classList.remove('dark');
                    localStorage.setItem('theme', 'light');
                } else {
                    html.classList.add('dark');
                    localStorage.setItem('theme', 'dark');
                }
                
                // Set cookie for server-side rendering
                document.cookie = `theme=${isDark ? 'light' : 'dark'}; path=/; max-age=31536000`;
            });
        }
    });
</script>
</body>
</html>