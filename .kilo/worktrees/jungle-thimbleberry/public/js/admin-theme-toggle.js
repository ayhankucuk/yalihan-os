/**
 * Admin Theme Toggle - Dark/Light Mode Switcher
 * Version: 1.0.0
 */

class AdminThemeToggle {
    constructor() {
        this.theme = localStorage.getItem('admin-theme') || 'light';
        this.init();
    }

    init() {
        this.applyTheme();
        this.createToggleButton();
        this.bindEvents();

        // Auto detect user preference if no saved theme
        if (!localStorage.getItem('admin-theme')) {
            this.detectSystemTheme();
        }
    }

    applyTheme() {
        document.documentElement.setAttribute('data-theme', this.theme);
        document.body.classList.toggle('dark-mode', this.theme === 'dark');
    }

    detectSystemTheme() {
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            this.theme = 'dark';
            this.applyTheme();
            localStorage.setItem('admin-theme', this.theme);
        }
    }

    createToggleButton() {
        // Remove existing toggle if present
        const existingToggle = document.querySelector('.theme-toggle');
        if (existingToggle) {
            existingToggle.remove();
        }

        // Create toggle button
        const toggle = document.createElement('button');
        toggle.className = 'theme-toggle';
        toggle.setAttribute('aria-label', 'Toggle dark mode');
        toggle.setAttribute('title', `Switch to ${this.theme === 'light' ? 'dark' : 'light'} mode`);

        // Add icon based on current theme
        toggle.innerHTML =
            this.theme === 'light' ? '<i class="fas fa-moon"></i>' : '<i class="fas fa-sun"></i>';

        // Add to body
        document.body.appendChild(toggle);
    }

    bindEvents() {
        // Toggle button click
        document.addEventListener('click', (e) => {
            if (e.target.closest('.theme-toggle')) {
                this.toggleTheme();
            }
        });

        // Keyboard shortcut (Ctrl/Cmd + Shift + D)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggleTheme();
            }
        });

        // Listen for system theme changes
        if (window.matchMedia) {
            window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
                if (!localStorage.getItem('admin-theme-manual')) {
                    this.theme = e.matches ? 'dark' : 'light';
                    this.applyTheme();
                    this.updateToggleButton();
                    localStorage.setItem('admin-theme', this.theme);
                }
            });
        }
    }

    toggleTheme() {
        this.theme = this.theme === 'light' ? 'dark' : 'light';
        this.applyTheme();
        this.updateToggleButton();
        localStorage.setItem('admin-theme', this.theme);
        localStorage.setItem('admin-theme-manual', 'true'); // User manually changed theme

        // Trigger animation
        this.animateToggle();

        // Dispatch custom event
        window.dispatchEvent(
            new CustomEvent('themeChanged', {
                detail: { theme: this.theme },
            })
        );
    }

    updateToggleButton() {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            toggle.innerHTML =
                this.theme === 'light'
                    ? '<i class="fas fa-moon"></i>'
                    : '<i class="fas fa-sun"></i>';
            toggle.setAttribute(
                'title',
                `Switch to ${this.theme === 'light' ? 'dark' : 'light'} mode`
            );
        }
    }

    animateToggle() {
        const toggle = document.querySelector('.theme-toggle');
        if (toggle) {
            toggle.style.transform = 'rotate(360deg) scale(1.2)';
            setTimeout(() => {
                toggle.style.transform = 'rotate(0deg) scale(1)';
            }, 300);
        }

        // Add page transition effect
        document.body.style.transition = 'background-color 0.3s ease-in-out';
        setTimeout(() => {
            document.body.style.transition = '';
        }, 300);
    }

    // Public method to get current theme
    getCurrentTheme() {
        return this.theme;
    }

    // Public method to set theme programmatically
    setTheme(newTheme) {
        if (newTheme === 'light' || newTheme === 'dark') {
            this.theme = newTheme;
            this.applyTheme();
            this.updateToggleButton();
            localStorage.setItem('admin-theme', this.theme);
        }
    }
}

// Auto-initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.adminThemeToggle = new AdminThemeToggle();
});

// Also initialize immediately if DOM is already loaded
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        if (!window.adminThemeToggle) {
            window.adminThemeToggle = new AdminThemeToggle();
        }
    });
} else {
    if (!window.adminThemeToggle) {
        window.adminThemeToggle = new AdminThemeToggle();
    }
}

// Export for use in modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdminThemeToggle;
}
