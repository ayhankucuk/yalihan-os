#!/usr/bin/env node
/**
 * DAP Protocol — Dark Mode Bulk Fixer (Context7 Compliant)
 *
 * Idempotent script that:
 * - Scans Blade, JS, TS, JSX files for class attributes
 * - Injects missing dark mode variants based on SSOT map
 * - Normalizes redundant dark variants
 * - Generates detailed fix report
 *
 * Usage:
 *   node scripts/fix-dark-mode.cjs --check   # Check only (exit 1 if violations found)
 *   node scripts/fix-dark-mode.cjs --fix     # Apply fixes and generate report
 *   node scripts/fix-dark-mode.cjs --report  # Generate report without changes
 *
 * @context7 Dark Mode Hardening Protocol
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

// ========================================
// Configuration
// ========================================
const SSOT_MAP = require('../.context7/dark-mode-map.json');
const SCAN_DIRS = ['resources/views', 'resources/js'];
const FILE_EXTENSIONS = ['.blade.php', '.js', '.jsx', '.ts', '.tsx'];
const REPORT_PATH = path.join(__dirname, '../../docs/_reports/DARK_MODE_REPORT.md');
const JSON_REPORT_PATH = path.join(__dirname, '../../.precheck/dark-mode.latest.json');

// Command line arguments
const args = process.argv.slice(2);
const MODE = args.includes('--apply')
    ? 'apply'
    : args.includes('--dry')
      ? 'dry'
      : args.includes('--check')
        ? 'check'
        : 'dry';
const CHANGED_ONLY = args.includes('--changed-only');

// ========================================
// Statistics
// ========================================
const stats = {
    filesScanned: 0,
    filesModified: 0,
    injectionsCount: 0,
    normalizationsCount: 0,
    errorFiles: [],
    modifiedFiles: [],
};

// ========================================
// Core Functions
// ========================================

/**
 * Extract all class attributes from file content
 * Handles: class="...", class='...', :class="...", x-bind:class="..."
 */
function extractClassAttributes(content) {
    const classRegex = /(?:x-bind:)?(?::)?class=["']([^"']+)["']/g;
    const matches = [];
    let match;

    while ((match = classRegex.exec(content)) !== null) {
        matches.push({
            fullMatch: match[0],
            classes: match[1],
            index: match.index,
        });
    }

    return matches;
}

/**
 * Parse individual classes from a class string
 * Handles: "class1 class2 class3" or "class1  class2   class3"
 */
function parseClasses(classString) {
    return classString.split(/\s+/).filter((c) => c.trim().length > 0);
}

/**
 * Check if a class is a dark mode variant
 */
function isDarkVariant(className) {
    return className.startsWith('dark:');
}

/**
 * Get the property from a utility class
 * e.g., "bg-white" -> "bg", "text-slate-900" -> "text"
 */
function getClassProperty(className) {
    // Handle special cases first
    if (className.startsWith('placeholder:')) {
        return 'placeholder';
    }
    if (className.includes('hover:')) {
        return className.split(':')[1].split('-')[0];
    }
    if (className.includes('focus:')) {
        return className.split(':')[1].split('-')[0];
    }

    // Standard cases
    const parts = className.split('-');
    if (parts.length >= 2) {
        return parts[0]; // bg, text, border, ring, etc.
    }

    return null;
}

/**
 * Process a single class attribute and inject missing dark variants
 */
function processClassAttribute(classString) {
    const classes = parseClasses(classString);
    const lightClasses = classes.filter((c) => !isDarkVariant(c));
    const darkClasses = classes.filter((c) => isDarkVariant(c));

    const injected = [];
    const normalized = [];

    // Build a map of existing dark properties
    const existingDarkProps = new Set();
    darkClasses.forEach((darkClass) => {
        const withoutDark = darkClass.substring(5); // Remove "dark:"
        const prop = getClassProperty(withoutDark);
        if (prop) {
            existingDarkProps.add(prop);
        }
    });

    // Check each light class for missing dark variant
    lightClasses.forEach((lightClass) => {
        if (SSOT_MAP[lightClass]) {
            const prop = getClassProperty(lightClass);

            // If no dark variant exists for this property, inject it
            if (prop && !existingDarkProps.has(prop)) {
                injected.push(SSOT_MAP[lightClass]);
                existingDarkProps.add(prop);
            }
        }
    });

    // Normalize existing dark classes against SSOT
    const normalizedDark = [];
    lightClasses.forEach((lightClass) => {
        if (SSOT_MAP[lightClass]) {
            const expectedDark = SSOT_MAP[lightClass];

            // Check if this dark variant exists
            if (darkClasses.includes(expectedDark)) {
                normalizedDark.push(expectedDark);
            } else {
                // Check for alternative dark variants for same property
                const prop = getClassProperty(lightClass);
                const existingVariants = darkClasses.filter((dc) => {
                    const withoutDark = dc.substring(5);
                    return getClassProperty(withoutDark) === prop;
                });

                if (existingVariants.length > 0) {
                    // Use SSOT variant, mark others as normalized
                    normalizedDark.push(expectedDark);
                    existingVariants.forEach((variant) => {
                        if (variant !== expectedDark) {
                            normalized.push({ from: variant, to: expectedDark });
                        }
                    });
                }
            }
        }
    });

    // Rebuild class string
    const finalClasses = [...lightClasses, ...normalizedDark, ...injected];

    // Remove duplicates while preserving order
    const uniqueClasses = [...new Set(finalClasses)];

    return {
        modified: injected.length > 0 || normalized.length > 0,
        newClassString: uniqueClasses.join(' '),
        injected,
        normalized,
    };
}

/**
 * Process a single file
 */
function processFile(filePath) {
    try {
        const content = fs.readFileSync(filePath, 'utf8');
        const classMatches = extractClassAttributes(content);

        if (classMatches.length === 0) {
            return { modified: false };
        }

        let newContent = content;
        let fileInjections = 0;
        let fileNormalizations = 0;

        // Process in reverse to maintain correct indices
        for (let i = classMatches.length - 1; i >= 0; i--) {
            const match = classMatches[i];
            const result = processClassAttribute(match.classes);

            if (filePath.includes('login.blade.php')) {
                console.log('Login Blade Match:', match.classes);
                console.log('Result:', result);
            }

            if (result.modified) {
                // Replace the class attribute
                const quote = match.fullMatch.includes('"') ? '"' : "'";
                const prefix = match.fullMatch.split(quote)[0] + quote;
                const suffix = quote;
                const newAttribute = prefix + result.newClassString + suffix;

                newContent =
                    newContent.substring(0, match.index) +
                    newAttribute +
                    newContent.substring(match.index + match.fullMatch.length);

                fileInjections += result.injected.length;
                fileNormalizations += result.normalized.length;
            }
        }

        const wasModified = newContent !== content;

        if (wasModified && (MODE === 'apply' || MODE === 'fix')) {
            fs.writeFileSync(filePath, newContent, 'utf8');
        }

        return {
            modified: wasModified,
            injections: fileInjections,
            normalizations: fileNormalizations,
        };
    } catch (error) {
        stats.errorFiles.push({ path: filePath, error: error.message });
        return { modified: false, error: true };
    }
}

/**
 * Recursively scan directory for target files
 */
function scanDirectory(dirPath, files = []) {
    if (!fs.existsSync(dirPath)) {
        return files;
    }

    const entries = fs.readdirSync(dirPath, { withFileTypes: true });

    entries.forEach((entry) => {
        const fullPath = path.join(dirPath, entry.name);

        if (entry.isDirectory()) {
            // Skip node_modules, vendor, etc.
            if (!['node_modules', 'vendor', '.git'].includes(entry.name)) {
                scanDirectory(fullPath, files);
            }
        } else if (entry.isFile()) {
            const ext = path.extname(entry.name);
            if (FILE_EXTENSIONS.includes(ext)) {
                files.push(fullPath);
            }
        }
    });

    return files;
}

/**
 * Get list of changed files from git (for --changed-only mode)
 */
function getChangedFiles() {
    try {
        // Get staged and unstaged changes
        const output = execSync('git diff --name-only HEAD', {
            cwd: path.join(__dirname, '..'),
            encoding: 'utf-8',
        });

        const changedFiles = output
            .split('\n')
            .filter((f) => f.trim().length > 0)
            .map((f) => path.join(__dirname, '..', f))
            .filter((f) => {
                const ext = path.extname(f);
                return FILE_EXTENSIONS.includes(ext);
            });

        return changedFiles;
    } catch (error) {
        console.warn('⚠️ Could not get changed files from git, scanning all files instead');
        return null;
    }
}

/**
 * Generate fix report
 */
function generateReport() {
    const timestamp = new Date().toISOString();
    const projectRoot = path.join(__dirname, '..');

    // Sort modified files by injection count
    const sortedFiles = stats.modifiedFiles
        .sort((a, b) => b.injections - a.injections)
        .slice(0, 20);

    let report = `# Dark Mode Fix Report\n\n`;
    report += `**Generated:** ${timestamp}\n`;
    report += `**Mode:** ${MODE}\n\n`;

    report += `## Summary\n\n`;
    report += `- **Files Scanned:** ${stats.filesScanned}\n`;
    report += `- **Files Modified:** ${stats.filesModified}\n`;
    report += `- **Dark Variants Injected:** ${stats.injectionsCount}\n`;
    report += `- **Redundant Variants Normalized:** ${stats.normalizationsCount}\n`;

    if (stats.errorFiles.length > 0) {
        report += `- **Files with Errors:** ${stats.errorFiles.length}\n`;
    }

    report += `\n## Top 20 Changed Files\n\n`;

    if (sortedFiles.length === 0) {
        report += `*No files were modified.*\n`;
    } else {
        sortedFiles.forEach((file, index) => {
            const relativePath = path.relative(projectRoot, file.path);
            report += `${index + 1}. \`${relativePath}\` — ${file.injections} injections, ${file.normalizations} normalizations\n`;
        });
    }

    if (stats.errorFiles.length > 0) {
        report += `\n## Errors\n\n`;
        stats.errorFiles.forEach(({ path: filePath, error }) => {
            const relativePath = path.relative(projectRoot, filePath);
            report += `- \`${relativePath}\`: ${error}\n`;
        });
    }

    report += `\n## Details\n\n`;

    if (stats.modifiedFiles.length === 0) {
        report += `*No modifications needed. Codebase is Context7 dark mode compliant.*\n`;
    } else {
        stats.modifiedFiles.forEach((file) => {
            const relativePath = path.relative(projectRoot, file.path);
            report += `### ${relativePath}\n\n`;
            report += `- Injections: ${file.injections}\n`;
            report += `- Normalizations: ${file.normalizations}\n\n`;
        });
    }

    // Ensure report directory exists
    const reportDir = path.dirname(REPORT_PATH);
    if (!fs.existsSync(reportDir)) {
        fs.mkdirSync(reportDir, { recursive: true });
    }

    fs.writeFileSync(REPORT_PATH, report, 'utf8');

    return report;
}

// ========================================
// Main Execution
// ========================================

function main() {
    console.log(
        `🚀 DAP Protocol — Dark Mode Fixer (Mode: ${MODE.toUpperCase()}${CHANGED_ONLY ? ', Changed Only' : ''})\n`
    );

    const projectRoot = path.join(__dirname, '..');
    let allFiles = [];

    if (CHANGED_ONLY) {
        const changedFiles = getChangedFiles();
        if (changedFiles) {
            allFiles = changedFiles;
            console.log(`📁 Scanning ${allFiles.length} changed files...\n`);
        } else {
            // Fallback to full scan
            SCAN_DIRS.forEach((dir) => {
                const fullPath = path.join(projectRoot, dir);
                scanDirectory(fullPath, allFiles);
            });
            console.log(`📁 Scanning ${allFiles.length} files (git unavailable, full scan)...\n`);
        }
    } else {
        // Scan all configured directories
        SCAN_DIRS.forEach((dir) => {
            const fullPath = path.join(projectRoot, dir);
            scanDirectory(fullPath, allFiles);
        });
        console.log(`📁 Scanning ${allFiles.length} files...\n`);
    }

    stats.filesScanned = allFiles.length;

    // Process each file
    allFiles.forEach((filePath) => {
        if (filePath.includes('login.blade.php')) {
            console.log('Found login.blade.php in scan list');
        }
        const result = processFile(filePath);

        if (result.modified) {
            stats.filesModified++;
            stats.injectionsCount += result.injections || 0;
            stats.normalizationsCount += result.normalizations || 0;

            stats.modifiedFiles.push({
                path: filePath,
                injections: result.injections || 0,
                normalizations: result.normalizations || 0,
            });
        }
    });

    // Generate reports
    const report = generateReport();

    // Generate machine-readable JSON
    const jsonReportDir = path.dirname(JSON_REPORT_PATH);
    if (!fs.existsSync(jsonReportDir)) {
        fs.mkdirSync(jsonReportDir, { recursive: true });
    }

    const jsonReport = {
        timestamp: new Date().toISOString(),
        mode: MODE,
        changedOnly: CHANGED_ONLY,
        stats: {
            filesScanned: stats.filesScanned,
            filesModified: stats.filesModified,
            injectionsCount: stats.injectionsCount,
            normalizationsCount: stats.normalizationsCount,
            errorCount: stats.errorFiles.length,
        },
        modifiedFiles: stats.modifiedFiles.map((f) => ({
            path: path.relative(projectRoot, f.path),
            injections: f.injections,
            normalizations: f.normalizations,
        })),
        errors: stats.errorFiles.map((e) => ({
            path: path.relative(projectRoot, e.path),
            error: e.error,
        })),
    };

    fs.writeFileSync(JSON_REPORT_PATH, JSON.stringify(jsonReport, null, 2), 'utf8');

    // Print summary
    console.log(`\n✅ Processing Complete\n`);
    console.log(`📊 Summary:`);
    console.log(`   Files Scanned: ${stats.filesScanned}`);
    console.log(`   Files Modified: ${stats.filesModified}`);
    console.log(`   Injections: ${stats.injectionsCount}`);
    console.log(`   Normalizations: ${stats.normalizationsCount}`);

    if (stats.errorFiles.length > 0) {
        console.log(`   Errors: ${stats.errorFiles.length}`);
    }

    console.log(`\n📄 Report: ${REPORT_PATH}`);
    console.log(`📄 JSON: ${JSON_REPORT_PATH}\n`);

    // Exit with appropriate code
    if ((MODE === 'check' || MODE === 'dry') && stats.filesModified > 0) {
        console.log(`❌ Dark mode violations detected!`);
        console.log(`   Run: npm run fix:darkmode\n`);
        process.exit(1);
    } else if (MODE === 'check' || MODE === 'dry') {
        console.log(`✅ No dark mode violations detected.\n`);
        process.exit(0);
    } else {
        console.log(`✅ Fixes applied successfully.\n`);
        process.exit(0);
    }
}

// Run main function
main();
