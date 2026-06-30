/** @type {import('tailwindcss').Config} */
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './resources/**/*.css',
        './app/**/*.php',
    ],
    darkMode: 'class',
    // Suppress CSS conflict warnings for semantic color usage (blue/orange/gray)
    // These classes apply different color values, not identical CSS properties
    corePlugins: {
        // Keep all core plugins enabled
    },
    theme: {
        extend: {
            colors: {
                // Legacy scale (admin panels)
                primary: {
                    DEFAULT: 'var(--primary, #004ac6)',
                    500: '#f97316',
                    600: '#ea580c',
                },
                // ThemeService CSS variable tokens — frontend design system
                'primary-container': 'var(--primary-container, #2563eb)',
                'on-primary': 'var(--on-primary, #ffffff)',
                surface: 'var(--surface, #faf8ff)',
                'surface-container': {
                    DEFAULT: 'var(--surface-container, #ededf9)',
                    low:     'var(--surface-low, #f3f3fe)',
                    high:    'var(--surface-high, #e7e7f3)',
                    highest: 'var(--surface-highest, #e1e2ed)',
                },
                'surface-muted': 'var(--surface-muted, #F8FAFC)',
                'on-surface': {
                    DEFAULT: 'var(--on-surface, #191b23)',
                    variant: 'var(--on-surface-variant, #434655)',
                },
                secondary: 'var(--secondary, #565e74)',
                outline: 'var(--outline, #757780)',
                'outline-variant': 'var(--outline-variant, #C5C6D0)',
                // Status badges
                'status-sale': '#15803D',
                'status-rent': '#B45309',
                gray: {
                    50: '#f9fafb',
                    100: '#f3f4f6',
                    200: '#e5e7eb',
                    300: '#d1d5db',
                    500: '#6b7280',
                    600: '#4b5563',
                    700: '#374151',
                    800: '#1f2937',
                    900: '#111827',
                },
                green: {
                    500: '#22c55e',
                    600: '#16a34a',
                },
                red: {
                    500: '#ef4444',
                    600: '#dc2626',
                },
                yellow: {
                    500: '#f59e0b',
                    600: '#d97706',
                },
                blue: {
                    500: '#0ea5e9',
                    600: '#0284c7',
                },
            },
            spacing: {
                // Section spacing — py-section-gap
                'section-gap': '5rem',
                // Grid gutter — gap-grid-gutter
                'grid-gutter': '1.5rem',
            },
            fontSize: {
                // Typography scale — text-headline-lg, text-body-md etc.
                'headline-lg': ['2rem',    { lineHeight: '2.5rem',  fontWeight: '700' }],
                'headline-sm': ['1.25rem', { lineHeight: '1.75rem', fontWeight: '600' }],
                'body-md':     ['1rem',    { lineHeight: '1.6rem',  fontWeight: '400' }],
                'body-sm':     ['0.875rem',{ lineHeight: '1.4rem',  fontWeight: '400' }],
                'label-caps':  ['0.7rem',  { lineHeight: '1rem',    fontWeight: '700' }],
            },
            fontFamily: {
                sans: ['Inter', 'system-ui', 'sans-serif'],
            },
            animation: {
                shake: 'shake 0.3s ease-in-out',
                'pulse-slow': 'pulse 3s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
            keyframes: {
                shake: {
                    '0%, 100%': { transform: 'translateX(0)' },
                    '25%': { transform: 'translateX(-4px)' },
                    '75%': { transform: 'translateX(4px)' },
                },
            },
        },
    },
    // No custom component plugins - using Tailwind utilities only
    // Context7 compliance: Neo Design System and Bootstrap classes are forbidden
    plugins: [],
};
