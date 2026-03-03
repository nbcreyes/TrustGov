function applySavedTheme() {
    const savedTheme = localStorage.getItem('trustgov_theme');
    if (savedTheme === 'dark') {
        document.documentElement.classList.add('dark');
    } else {
        document.documentElement.classList.remove('dark');
    }
    updateToggleIcon();
}

/**
 * Toggles between light and dark mode.
 * Saves the new preference to localStorage.
 */
function toggleTheme() {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('trustgov_theme', isDark ? 'dark' : 'light');
    updateToggleIcon();
}

/**
 * Updates the theme toggle button icon based on the current mode.
 * Shows moon icon for light mode, sun icon for dark mode.
 */
function updateToggleIcon() {
    const btn = document.getElementById('themeToggleBtn');
    if (!btn) return;

    const isDark = document.documentElement.classList.contains('dark');
    btn.innerHTML = isDark
        ? '<i class="fas fa-sun text-yellow-300 text-lg"></i>'
        : '<i class="fas fa-moon text-white text-lg"></i>';
}

// Apply the saved theme immediately when the script loads
applySavedTheme();