// Neo Admin minimal controller using Alpine.js
export default function neoAdmin() {
    return {
        dark: false,
        mobileSidebar: false,
        init() {
            // Restore theme
            const saved = localStorage.getItem('neo-theme');
            if (saved === 'dark') this.dark = true;
            this.$watch('dark', (val) => {
                localStorage.setItem('neo-theme', val ? 'dark' : 'light');
            });
        },
        toggleDark() {
            this.dark = !this.dark;
        },
    };
}

// Attach to window for inline x-data reference
window.neoAdmin = neoAdmin;
