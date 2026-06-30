import { chromium, expect, type FullConfig } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { execSync } from 'node:child_process';

/**
 * Resolve and validate baseURL for e2e tests — SSOT for browser test URL authority
 *
 * Resolution precedence (deterministic):
 * 1. PLAYWRIGHT_BASE_URL (PRIMARY — explicit e2e authority)
 * 2. APP_URL (LEGACY FALLBACK — backward compatibility only)
 * 3. http://127.0.0.1:8000 (LOCAL FALLBACK — zero-config dev)
 *
 * @param configBaseURL - baseURL from playwright.config.ts (already resolved via env chain)
 * @throws {Error} Invalid URL, placeholder, or missing config
 * @returns {string} Validated baseURL with source logging
 */
function resolveAndValidateBaseURL(configBaseURL: string | undefined): string {
    // Determine source for logging/debugging
    let source: string;
    let url: string | undefined;

    if (process.env.PLAYWRIGHT_BASE_URL) {
        source = 'PLAYWRIGHT_BASE_URL (PRIMARY)';
        url = process.env.PLAYWRIGHT_BASE_URL;
    } else if (process.env.APP_URL) {
        source = 'APP_URL (LEGACY FALLBACK)';
        url = process.env.APP_URL;
        console.warn(
            '⚠️  Using APP_URL as fallback. Set PLAYWRIGHT_BASE_URL for explicit e2e authority.'
        );
    } else if (configBaseURL) {
        source = 'LOCAL FALLBACK (hardcoded)';
        url = configBaseURL;
        console.warn(
            '⚠️  Using hardcoded fallback. Set PLAYWRIGHT_BASE_URL for explicit e2e authority.'
        );
    } else {
        source = 'NONE';
        url = undefined;
    }

    console.log(`🔍 baseURL Source: ${source}`);
    if (url) {
        console.log(`🌐 baseURL Value: ${url}`);
    }

    // Guard 1: URL existence
    if (!url || url.trim() === '') {
        throw new Error(
            '❌ E2E_CONFIG_ERROR: baseURL tanımlı değil\n' +
                '\n' +
                'Çözüm:\n' +
                '  export PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000\n' +
                '\n' +
                'Veya .env dosyasında:\n' +
                '  PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000\n'
        );
    }

    // Guard 2: Placeholder detection
    const forbiddenPatterns = [
        'REAL_DOMAIN',
        'example.com',
        'example.org',
        'localhost.test',
        'test.local',
    ];

    for (const pattern of forbiddenPatterns) {
        if (url.includes(pattern)) {
            throw new Error(
                `❌ E2E_CONFIG_ERROR: baseURL placeholder içeriyor\n` +
                    `\n` +
                    `Tespit edilen: ${pattern}\n` +
                    `Mevcut URL: ${url}\n` +
                    `Source: ${source}\n` +
                    `\n` +
                    `Çözüm:\n` +
                    `  export PLAYWRIGHT_BASE_URL=http://127.0.0.1:8000\n`
            );
        }
    }

    // Guard 3: URL format validation
    let parsed: URL;
    try {
        parsed = new URL(url);
    } catch (e) {
        throw new Error(
            `❌ E2E_CONFIG_ERROR: Geçersiz URL formatı\n` +
                `\n` +
                `Mevcut: ${url}\n` +
                `Source: ${source}\n` +
                `Hata: ${(e as Error).message}\n` +
                `\n` +
                `Geçerli format: http://127.0.0.1:8000\n`
        );
    }

    // Guard 4: localhost port requirement
    if (parsed.hostname === 'localhost' && !parsed.port) {
        throw new Error(
            `❌ E2E_CONFIG_ERROR: localhost için port gerekli\n` +
                `\n` +
                `Mevcut: ${url}\n` +
                `Source: ${source}\n` +
                `\n` +
                `Çözüm:\n` +
                `  export PLAYWRIGHT_BASE_URL=http://localhost:8000\n`
        );
    }

    // Guard 5: Protocol validation
    if (!['http:', 'https:'].includes(parsed.protocol)) {
        throw new Error(
            `❌ E2E_CONFIG_ERROR: Geçersiz protocol\n` +
                `\n` +
                `Mevcut: ${parsed.protocol}\n` +
                `Source: ${source}\n` +
                `Beklenen: http: veya https:\n`
        );
    }

    return url;
}

async function globalSetup(config: FullConfig) {
    const { baseURL: rawBaseURL, storageState } = config.projects[0].use;

    // ✅ SSOT: Resolve + validate baseURL (fail-fast with source visibility)
    const baseURL = resolveAndValidateBaseURL(rawBaseURL);

    console.log(`✅ Validated Base URL: ${baseURL}`);
    console.log(`🔒 Preflight checks: PASSED`);

    const adminEmail = process.env.ADMIN_EMAIL || 'ayhankucuk@gmail.com';
    const adminPassword = process.env.ADMIN_PASSWORD || 'admin123';
    const storagePath =
        typeof storageState === 'string' ? storageState : 'playwright/.auth/admin.json';

    // E2E fixture bootstrap: ensure admin user has broad permissions in local/CI test envs.
    try {
        execSync(
            `php artisan tinker --execute='$u=\\App\\Models\\User::updateOrCreate(["email"=>"${adminEmail}"],["name"=>"E2E Admin","password"=>"${adminPassword}","role_id"=>1,"aktiflik_durumu"=>1]);$r=\\Spatie\\Permission\\Models\\Role::firstOrCreate(["name"=>"super-admin","guard_name"=>"web"]);$r->syncPermissions(\\Spatie\\Permission\\Models\\Permission::pluck("name")->all());$u->syncRoles([$r->name]);echo "fixture_ok";'`,
            { stdio: 'pipe' }
        );
    } catch (e) {
        console.warn('⚠️ Admin fixture bootstrap failed (continuing):', e);
    }

    // Ensure directory exists
    const authDir = path.dirname(storagePath);
    if (!fs.existsSync(authDir)) {
        fs.mkdirSync(authDir, { recursive: true });
    }

    console.log(`🚀 Global Setup: Logging in as ${adminEmail}...`);
    console.log(`🌐 Base URL: ${baseURL}`);

    const browser = await chromium.launch();
    const page = await browser.newPage({ baseURL });

    try {
        // Navigate to login page
        await page.goto('/login');

        // Turkish/English supported regex for login fields
        // Email field
        const emailField = page.getByLabel(/E-posta Adresi|Email/i);
        await expect(emailField).toBeVisible({ timeout: 10000 });
        await emailField.fill(adminEmail);

        // Password field
        const passwordField = page.getByLabel(/Şifre|Password/i);
        await passwordField.fill(adminPassword);

        // Login button
        const loginButton = page.getByRole('button', { name: /Giriş Yap|Login|Sign In/i });
        await loginButton.click();

        // Verify successful login (URL starts with /admin or similar)
        await page.waitForURL(/\/admin/);
        const title = await page.title();
        if (/forbidden|403/i.test(title)) {
            console.warn(
                '⚠️ AUTH_GUARD_FIXTURE: global setup reached forbidden page after login; continuing for per-test classification.'
            );
        }

        console.log('✅ Login successful, saving session state...');

        // Save storage state
        await page.context().storageState({ path: storagePath });
    } catch (error) {
        console.error('❌ Global Setup Failed:', error);
        throw error;
    } finally {
        await browser.close();
    }
}

export default globalSetup;
