// Dark Mode Theme Manager
(function() {
    // Check for saved theme preference or default to light mode
    const currentTheme = localStorage.getItem('theme') || 'light';
    
    // Apply theme immediately to prevent flash
    if (currentTheme === 'dark') {
        document.documentElement.classList.add('dark');
    }
    
    // Function to toggle theme
    window.toggleTheme = function() {
        const html = document.documentElement;
        const isDark = html.classList.contains('dark');
        
        if (isDark) {
            html.classList.remove('dark');
            localStorage.setItem('theme', 'light');
        } else {
            html.classList.add('dark');
            localStorage.setItem('theme', 'dark');
        }
    };
    
    // Function to get current theme
    window.getCurrentTheme = function() {
        return document.documentElement.classList.contains('dark') ? 'dark' : 'light';
    };
})();
