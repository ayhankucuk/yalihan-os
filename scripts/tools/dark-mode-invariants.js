/**
 * DAP Protocol — Dark Mode Invariant Policy (SSOT)
 *
 * Single Source of Truth for dark mode class mappings and normalization rules.
 * This file defines the canonical light→dark utility class conversions following
 * Context7 compliance standards.
 *
 * @context7 Dark Mode Hardening Protocol
 * @version 2.0.0
 */

module.exports = {
    /**
     * Standard Palette Mappings
     * Light utility → Dark utility (canonical)
     */
    mappings: {
        // ========================================
        // Background Colors
        // ========================================
        'bg-white': 'dark:bg-slate-900',
        'bg-slate-50': 'dark:bg-slate-900',
        'bg-gray-50': 'dark:bg-slate-900',
        'bg-gray-100': 'dark:bg-slate-800',
        'bg-slate-100': 'dark:bg-slate-800',
        'bg-gray-200': 'dark:bg-slate-700',
        'bg-slate-200': 'dark:bg-slate-700',

        // Opacity-aware backgrounds
        'bg-white/10': 'dark:bg-slate-900/10',
        'bg-white/20': 'dark:bg-slate-900/20',
        'bg-white/30': 'dark:bg-slate-900/30',
        'bg-white/40': 'dark:bg-slate-900/40',
        'bg-white/50': 'dark:bg-slate-900/50',
        'bg-white/60': 'dark:bg-slate-900/60',
        'bg-white/70': 'dark:bg-slate-900/70',
        'bg-white/80': 'dark:bg-slate-900/80',
        'bg-white/90': 'dark:bg-slate-900/90',

        'bg-gray-50/50': 'dark:bg-slate-900/50',
        'bg-slate-50/50': 'dark:bg-slate-900/50',

        // ========================================
        // Text Colors
        // ========================================
        'text-slate-900': 'dark:text-slate-100',
        'text-gray-900': 'dark:text-slate-100',
        'text-slate-800': 'dark:text-slate-200',
        'text-gray-800': 'dark:text-slate-200',
        'text-slate-700': 'dark:text-slate-300',
        'text-gray-700': 'dark:text-slate-300',
        'text-slate-600': 'dark:text-slate-400',
        'text-gray-600': 'dark:text-slate-400',
        'text-slate-500': 'dark:text-slate-500',
        'text-gray-500': 'dark:text-slate-500',
        'text-slate-400': 'dark:text-slate-600',
        'text-gray-400': 'dark:text-slate-600',

        // ========================================
        // Border Colors
        // ========================================
        'border-slate-200': 'dark:border-slate-800',
        'border-gray-200': 'dark:border-slate-800',
        'border-slate-300': 'dark:border-slate-700',
        'border-gray-300': 'dark:border-slate-700',
        'border-slate-400': 'dark:border-slate-600',
        'border-gray-400': 'dark:border-slate-600',

        // ========================================
        // Ring Colors
        // ========================================
        'ring-slate-200': 'dark:ring-slate-700',
        'ring-gray-200': 'dark:ring-slate-700',
        'ring-slate-300': 'dark:ring-slate-600',
        'ring-gray-300': 'dark:ring-slate-600',

        // ========================================
        // Placeholder Colors
        // ========================================
        'placeholder:text-gray-400': 'dark:placeholder:text-slate-500',
        'placeholder:text-slate-400': 'dark:placeholder:text-slate-500',
        'placeholder:text-gray-500': 'dark:placeholder:text-slate-400',
        'placeholder:text-slate-500': 'dark:placeholder:text-slate-400',

        // ========================================
        // Divide Colors
        // ========================================
        'divide-slate-200': 'dark:divide-slate-800',
        'divide-gray-200': 'dark:divide-slate-800',
        'divide-slate-300': 'dark:divide-slate-700',
        'divide-gray-300': 'dark:divide-slate-700',

        // ========================================
        // Shadow (Disable in dark mode)
        // ========================================
        shadow: 'dark:shadow-none',
        'shadow-sm': 'dark:shadow-none',
        'shadow-md': 'dark:shadow-none',
        'shadow-slate-200': 'dark:shadow-slate-900',
        'shadow-gray-200': 'dark:shadow-slate-900',

        // ========================================
        // Hover States
        // ========================================
        'hover:bg-gray-50': 'dark:hover:bg-slate-800',
        'hover:bg-slate-50': 'dark:hover:bg-slate-800',
        'hover:bg-gray-100': 'dark:hover:bg-slate-700',
        'hover:bg-slate-100': 'dark:hover:bg-slate-700',

        'hover:text-gray-900': 'dark:hover:text-slate-100',
        'hover:text-slate-900': 'dark:hover:text-slate-100',
        'hover:text-gray-700': 'dark:hover:text-slate-300',
        'hover:text-slate-700': 'dark:hover:text-slate-300',

        // ========================================
        // Focus States
        // ========================================
        'focus:bg-gray-50': 'dark:focus:bg-slate-800',
        'focus:bg-slate-50': 'dark:focus:bg-slate-800',

        // ========================================
        // Gradients
        // ========================================
        'from-white': 'dark:from-slate-900',
        'from-gray-50': 'dark:from-slate-900',
        'from-slate-50': 'dark:from-slate-900',

        'via-white': 'dark:via-slate-900',
        'via-gray-50': 'dark:via-slate-900',
        'via-slate-50': 'dark:via-slate-900',

        'to-white': 'dark:to-slate-900',
        'to-gray-50': 'dark:to-slate-900',
        'to-slate-50': 'dark:to-slate-900',
    },

    /**
     * Normalization Rules
     * When multiple dark variants exist for same property, use this priority
     */
    normalization: {
        // If both exist, keep only the canonical one
        priority: 'mappings', // Always prefer mappings table
    },

    /**
     * Ignore Patterns
     * Files/lines that should be skipped
     */
    ignore: {
        patterns: [
            // Comment-based ignores
            '// context7-ignore-darkmode',
            '// @darkmode-ignore',

            // File patterns (glob)
            'vendor/**',
            'node_modules/**',
            '*.min.js',
            '*.bundle.js',
        ],
    },

    /**
     * Validation Rules
     */
    validation: {
        // Light utility must have corresponding dark utility
        requireDarkVariant: true,

        // No duplicate dark utilities for same property
        noDuplicateDark: true,

        // Preserve class attribute integrity
        preserveQuotes: true,
        preserveSpacing: true,
    },
};
