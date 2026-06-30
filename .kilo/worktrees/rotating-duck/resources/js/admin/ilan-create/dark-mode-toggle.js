// Yalıhan Bekçi - Dark Mode Toggle System
// Advanced dark mode with system preference tracking

class DarkModeToggle {
    constructor() {
        this.storageKey = 'darkMode';
        this.systemPreference = null;
        this.currentMode = null;
        this.observers = [];
        this.init();
    }

    init() {
        this.detectSystemPreference();
        this.loadUserPreference();
        this.setupEventListeners();
        this.setupCSSVariables();
        this.applyDarkMode();
    }

    // 🎨 Dark mode toggle (Sistem tercihi takibi)
    detectSystemPreference() {
        if (window.matchMedia) {
            const mediaQuery = window.matchMedia('(prefers-color-scheme: dark)');
            this.systemPreference = mediaQuery.matches ? 'dark' : 'light';

            // Listen for system preference changes
            mediaQuery.addEventListener('change', (e) => {
                this.systemPreference = e.matches ? 'dark' : 'light';
                this.handleSystemPreferenceChange();
            });
        } else {
            this.systemPreference = 'light'; // Default fallback
        }
    }

    loadUserPreference() {
        const stored = localStorage.getItem(this.storageKey);
        if (stored) {
            this.currentMode = stored;
        } else {
            // Use system preference if no user preference
            this.currentMode = this.systemPreference;
        }
    }

    setupEventListeners() {
        // Listen for clicks on dark mode toggle buttons
        document.addEventListener('click', (e) => {
            if (
                e.target.matches('[data-dark-mode-toggle]') ||
                e.target.closest('[data-dark-mode-toggle]')
            ) {
                e.preventDefault();
                this.toggle();
            }
        });

        // Keyboard shortcut (Ctrl/Cmd + Shift + D)
        document.addEventListener('keydown', (e) => {
            if ((e.ctrlKey || e.metaKey) && e.shiftKey && e.key === 'D') {
                e.preventDefault();
                this.toggle();
            }
        });
    }

    setupCSSVariables() {
        const cssVariables = `
            :root {
                --color-bg-primary: #ffffff;
                --color-bg-secondary: #f8fafc;
                --color-bg-tertiary: #f1f5f9;
                --color-text-primary: #1e293b;
                --color-text-secondary: #64748b;
                --color-text-tertiary: #94a3b8;
                --color-border-primary: #e2e8f0;
                --color-border-secondary: #cbd5e1;
                --color-accent-primary: #3b82f6;
                --color-accent-secondary: #1d4ed8;
                --color-success: #10b981;
                --color-warning: #f59e0b;
                --color-error: #ef4444;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
            }

            .dark {
                --color-bg-primary: #0f172a;
                --color-bg-secondary: #1e293b;
                --color-bg-tertiary: #334155;
                --color-text-primary: #f8fafc;
                --color-text-secondary: #cbd5e1;
                --color-text-tertiary: #94a3b8;
                --color-border-primary: #334155;
                --color-border-secondary: #475569;
                --color-accent-primary: #60a5fa;
                --color-accent-secondary: #3b82f6;
                --color-success: #34d399;
                --color-warning: #fbbf24;
                --color-error: #f87171;
                --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.3);
                --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.4);
                --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.5);
            }

            /* Smooth transitions */
            * {
                transition: background-color 0.3s ease,
                           border-color 0.3s ease,
                           color 0.3s ease,
                           box-shadow 0.3s ease;
            }

            /* Context7: Neo classes removed - Tailwind CSS dark mode kullanılıyor */
            /* Dark mode styles artık Tailwind'in dark: prefix'i ile yönetiliyor */
            /* Table styles: Tailwind dark:bg-gray-800 dark:text-white dark:border-gray-700 kullanılmalı */

            /* AI Widget dark mode */
            .dark .ai-widget {
                background: var(--color-bg-secondary);
                border-color: var(--color-border-primary);
            }

            .dark .ai-widget-header {
                background: var(--color-bg-tertiary);
                border-bottom-color: var(--color-border-primary);
            }

            /* Skeleton loader dark mode */
            .dark .skeleton-loader {
                background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
                background-size: 200% 100%;
            }

            /* Toast notifications dark mode */
            .dark .toast {
                background: var(--color-bg-secondary);
                border-color: var(--color-border-primary);
                color: var(--color-text-primary);
            }

            /* Progress indicators dark mode */
            .dark .progress-bar {
                background: var(--color-bg-tertiary);
            }

            .dark .progress-fill {
                background: linear-gradient(90deg, var(--color-accent-primary), var(--color-accent-secondary));
            }

            /* Form validation dark mode */
            .dark .form-error {
                color: var(--color-error);
                background: rgba(239, 68, 68, 0.1);
                border-color: var(--color-error);
            }

            .dark .form-success {
                color: var(--color-success);
                background: rgba(16, 185, 129, 0.1);
                border-color: var(--color-success);
            }
        `;

        const style = document.createElement('style');
        style.textContent = cssVariables;
        document.head.appendChild(style);
    }

    toggle() {
        this.currentMode = this.currentMode === 'dark' ? 'light' : 'dark';
        this.saveUserPreference();
        this.applyDarkMode();
        this.notifyObservers();
    }

    setMode(mode) {
        if (['light', 'dark', 'auto'].includes(mode)) {
            this.currentMode = mode === 'auto' ? this.systemPreference : mode;
            this.saveUserPreference();
            this.applyDarkMode();
            this.notifyObservers();
        }
    }

    applyDarkMode() {
        const isDark = this.currentMode === 'dark';

        // Apply to document
        if (isDark) {
            document.documentElement.classList.add('dark');
        } else {
            document.documentElement.classList.remove('dark');
        }

        // Update meta theme-color
        const metaThemeColor = document.querySelector('meta[name="theme-color"]');
        if (metaThemeColor) {
            metaThemeColor.content = isDark ? '#0f172a' : '#ffffff';
        }

        // Update toggle button states
        this.updateToggleButtons();

        // Update system preference indicator
        this.updateSystemPreferenceIndicator();
    }

    updateToggleButtons() {
        const isDark = this.currentMode === 'dark';

        document.querySelectorAll('[data-dark-mode-toggle]').forEach((button) => {
            const icon = button.querySelector('i');
            const text = button.querySelector('span');

            if (icon) {
                icon.className = isDark ? 'fas fa-sun' : 'fas fa-moon';
            }

            if (text) {
                text.textContent = isDark ? 'Açık Mod' : 'Koyu Mod';
            }

            button.setAttribute('aria-label', isDark ? 'Açık moda geç' : 'Koyu moda geç');
            button.title = isDark ? 'Açık moda geç (Ctrl+Shift+D)' : 'Koyu moda geç (Ctrl+Shift+D)';
        });
    }

    updateSystemPreferenceIndicator() {
        const indicators = document.querySelectorAll('[data-system-preference]');
        indicators.forEach((indicator) => {
            if (this.currentMode === this.systemPreference) {
                indicator.classList.add('system-preference-active');
                indicator.title = 'Sistem tercihi ile eşleşiyor';
            } else {
                indicator.classList.remove('system-preference-active');
                indicator.title =
                    'Sistem tercihi:' + (this.systemPreference === 'dark' ? 'Koyu' : 'Açık');
            }
        });
    }

    handleSystemPreferenceChange() {
        // If user is using 'auto' mode, update to new system preference
        if (this.currentMode === this.systemPreference) {
            this.applyDarkMode();
            this.notifyObservers();
        }
    }

    saveUserPreference() {
        localStorage.setItem(this.storageKey, this.currentMode);
    }

    // Observer pattern for dark mode changes
    addObserver(callback) {
        this.observers.push(callback);
    }

    removeObserver(callback) {
        const index = this.observers.indexOf(callback);
        if (index > -1) {
            this.observers.splice(index, 1);
        }
    }

    notifyObservers() {
        this.observers.forEach((callback) => {
            try {
                callback(this.currentMode, this.systemPreference);
            } catch (error) {
                console.error('Dark mode observer error:', error);
            }
        });
    }

    // Public API
    isDark() {
        return this.currentMode === 'dark';
    }

    isLight() {
        return this.currentMode === 'light';
    }

    isAuto() {
        return this.currentMode === this.systemPreference;
    }

    getCurrentMode() {
        return this.currentMode;
    }

    getSystemPreference() {
        return this.systemPreference;
    }

    // Alpine.js integration
    setupAlpineIntegration() {
        document.addEventListener('alpine:init', () => {
            // Dark mode store
            Alpine.store('darkMode', {
                currentMode: this.currentMode,
                systemPreference: this.systemPreference,
                isDark: this.isDark(),
                isLight: this.isLight(),
                isAuto: this.isAuto(),

                toggle() {
                    window.darkModeToggle.toggle();
                    this.updateState();
                },

                setMode(mode) {
                    window.darkModeToggle.setMode(mode);
                    this.updateState();
                },

                updateState() {
                    this.currentMode = window.darkModeToggle.getCurrentMode();
                    this.systemPreference = window.darkModeToggle.getSystemPreference();
                    this.isDark = window.darkModeToggle.isDark();
                    this.isLight = window.darkModeToggle.isLight();
                    this.isAuto = window.darkModeToggle.isAuto();
                },
            });

            // Dark mode directive
            Alpine.directive('dark-mode', (el, { expression }, { evaluateLater, effect }) => {
                const evaluate = evaluateLater(expression);
                let options = {};

                effect(() => {
                    evaluate((value) => {
                        options = value || {};
                        this.applyDarkModeToElement(el, options);
                    });
                });

                // Initial application
                this.applyDarkModeToElement(el, options);
            });
        });

        // Listen for dark mode changes and update Alpine store
        this.addObserver(() => {
            if (window.Alpine && window.Alpine.store('darkMode')) {
                window.Alpine.store('darkMode').updateState();
            }
        });
    }

    applyDarkModeToElement(element, options) {
        const isDark = this.isDark();

        if (options.hideInDark && isDark) {
            element.style.display = 'none';
        } else if (options.hideInLight && !isDark) {
            element.style.display = 'none';
        } else if (options.showInDark && isDark) {
            element.style.display = options.display || 'block';
        } else if (options.showInLight && !isDark) {
            element.style.display = options.display || 'block';
        } else {
            element.style.display = '';
        }
    }

    // Utility methods
    getContrastColor(backgroundColor) {
        // Simple contrast calculation
        const rgb = this.hexToRgb(backgroundColor);
        if (!rgb) return '#000000';

        const luminance = (0.299 * rgb.r + 0.587 * rgb.g + 0.114 * rgb.b) / 255;
        return luminance > 0.5 ? '#000000' : '#ffffff';
    }

    hexToRgb(hex) {
        const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
        return result
            ? {
                  r: parseInt(result[1], 16),
                  g: parseInt(result[2], 16),
                  b: parseInt(result[3], 16),
              }
            : null;
    }

    // Export current theme for external use
    exportTheme() {
        return {
            mode: this.currentMode,
            systemPreference: this.systemPreference,
            cssVariables: this.getCSSVariables(),
            timestamp: Date.now(),
        };
    }

    getCSSVariables() {
        const computedStyle = getComputedStyle(document.documentElement);
        const variables = {};

        for (let i = 0; i < computedStyle.length; i++) {
            const property = computedStyle[i];
            if (property.startsWith('--')) {
                variables[property] = computedStyle.getPropertyValue(property);
            }
        }

        return variables;
    }
}

// Global instance
window.darkModeToggle = new DarkModeToggle();

// Auto-setup Alpine integration
window.darkModeToggle.setupAlpineIntegration();

// Export for module usage
export default DarkModeToggle;
