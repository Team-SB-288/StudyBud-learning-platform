// Theme Switch Functionality
document.addEventListener('DOMContentLoaded', function() {
    const themeSwitch = document.getElementById('themeSwitch');
    
    if (!themeSwitch) {
        console.error('Theme switch element not found');
        return;
    }

    // Set initial state
    document.documentElement.setAttribute('data-theme', 'light');
    themeSwitch.checked = true;

    // Load saved theme if exists
    const savedTheme = localStorage.getItem('theme');
    if (savedTheme) {
        document.documentElement.setAttribute('data-theme', savedTheme);
        themeSwitch.checked = savedTheme === 'light';
    }

    // Handle theme switch click
    themeSwitch.addEventListener('change', () => {
        const newTheme = themeSwitch.checked ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', newTheme);
        localStorage.setItem('theme', newTheme);
    });
});