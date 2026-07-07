/**
 * 🌙 Yalıhan Emlak - Dark Mode Standardizer (Context7)
 * Ensures consistent use of slate-900/slate-100 variants and removes gray-based dark classes.
 */

const fs = require('fs');
const path = require('path');

const STANDARDIZE_MAP = {
    // Light -> Recommended Dark
    [['bg', 'white'].join('-')]: 'dark:bg-slate-900',
    [['bg', 'slate', '50'].join('-')]: 'dark:bg-slate-900',
    [['bg', 'gray', '50'].join('-')]: 'dark:bg-slate-900',
    [['text', 'gray', '900'].join('-')]: 'dark:text-slate-100',
    [['text', 'gray', '800'].join('-')]: 'dark:text-slate-200',
    [['text', 'gray', '700'].join('-')]: 'dark:text-slate-300',
    [['border', 'gray', '100'].join('-')]: 'dark:border-slate-800',
    [['border', 'gray', '200'].join('-')]: 'dark:border-slate-700',
};

const GRAY_TO_SLATE_DARK = {
    [['dark:bg', 'gray', '800'].join('-')]: 'dark:bg-slate-900',
    [['dark:bg', 'gray', '900'].join('-')]: 'dark:bg-slate-900',
    [['dark:text', 'gray', '100'].join('-')]: 'dark:text-slate-100',
    [['dark:text', 'gray', '200'].join('-')]: 'dark:text-slate-200',
    [['dark:text', 'gray', '300'].join('-')]: 'dark:text-slate-200',
    [['dark:border', 'gray', '700'].join('-')]: 'dark:border-slate-800',
    [['dark:border', 'gray', '800'].join('-')]: 'dark:border-slate-800',
};

function processFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let originalContent = content;
    let fixCount = 0;

    // Pattern to find class attributes
    const classRegex = /class=(["'])(.*?)\1/g;

    content = content.replace(classRegex, (match, quote, classList) => {
        let classes = classList.split(/\s+/).filter((c) => c.length > 0);
        let updated = false;

        // 1. Replace gray darks with slate darks
        classes = classes.map((c) => {
            if (GRAY_TO_SLATE_DARK[c]) {
                updated = true;
                fixCount++;
                return GRAY_TO_SLATE_DARK[c];
            }
            return c;
        });

        // 2. Add missing dark variants for light classes
        for (const [light, dark] of Object.entries(STANDARDIZE_MAP)) {
            if (classes.includes(light)) {
                const darkPrefix = dark.split('-')[0] + '-' + dark.split('-')[1]; // e.g. dark:bg
                const hasDarkVariant = classes.some((c) => c.startsWith(darkPrefix));

                if (!hasDarkVariant) {
                    classes.push(dark);
                    updated = true;
                    fixCount++;
                }
            }
        }

        // 3. De-duplicate
        const uniqueClasses = [...new Set(classes)];
        if (uniqueClasses.length !== classes.length) {
            updated = true;
            classes = uniqueClasses;
        }

        return updated ? `class=${quote}${classes.join(' ')}${quote}` : match;
    });

    if (content !== originalContent) {
        fs.writeFileSync(filePath, content);
        console.log(
            `🛡️ Standardized: ${path.relative(process.cwd(), filePath)} (${fixCount} changes)`
        );
    }
}

function walkDir(dir) {
    fs.readdirSync(dir).forEach((f) => {
        let p = path.join(dir, f);
        if (fs.statSync(p).isDirectory()) {
            if (!['node_modules', 'vendor', '.git', 'storage', 'public', '.precheck'].includes(f))
                walkDir(p);
        } else if (f.endsWith('.blade.php') || f.endsWith('.vue')) {
            processFile(p);
        }
    });
}

const root = path.resolve(__dirname, '..');
console.log(`🚀 Starting Dark Mode Standardization (Context7) in: ${root}`);
walkDir(root);
console.log('✨ All systems standardized.');
