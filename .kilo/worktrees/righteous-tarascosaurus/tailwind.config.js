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
                primary: {
                    500: '#f97316',
                    600: '#ea580c',
                },
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
