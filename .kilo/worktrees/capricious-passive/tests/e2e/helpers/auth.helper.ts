import { Page } from '@playwright/test';

/**
 * Context7 Compliant Authentication Helper
 *
 * Provides methods for logging in as different user types
 * with proper Turkish naming conventions
 */
export class AuthHelper {
    constructor(private page: Page) {}

    private async isForbiddenPage(): Promise<boolean> {
        const title = await this.page.title();
        if (/forbidden|403/i.test(title)) {
            return true;
        }

        const forbiddenText = this.page.getByText(/Forbidden|403|Bu işlem için yetkiniz yok/i).first();
        return (await forbiddenText.count()) > 0 && (await forbiddenText.isVisible());
    }

    /**
     * Admin olarak giriş yap (Idempotent - zaten admin'deyse skip)
     * Credentials: proje.md'den (ayhankucuk@gmail.com / admin123)
     */
    async loginAsAdmin() {
        const adminEmail = process.env.ADMIN_EMAIL || 'ayhankucuk@gmail.com';
        const adminPassword = process.env.ADMIN_PASSWORD || 'admin123';

        // Existing storage state may contain stale/unauthorized session.
        await this.page.goto('/admin/dashboard/index', { waitUntil: 'domcontentloaded' });
        const onAdminRoute = this.page.url().includes('/admin');
        if (onAdminRoute && !(await this.isForbiddenPage())) {
            return;
        }

        await this.page.goto('/login', { waitUntil: 'domcontentloaded' });

        const emailField = this.page.getByLabel(/E-posta Adresi|Email/i);
        if ((await emailField.count()) > 0) {
            await emailField.fill(adminEmail);
        } else {
            await this.page.fill('#email', adminEmail);
        }

        const passwordField = this.page.getByLabel(/Şifre|Password/i);
        if ((await passwordField.count()) > 0) {
            await passwordField.fill(adminPassword);
        } else {
            await this.page.fill('#password', adminPassword);
        }

        const loginButton = this.page.getByRole('button', { name: /Giriş Yap|Login|Sign In/i });
        if ((await loginButton.count()) > 0) {
            await loginButton.first().click();
        } else {
            await this.page.click('button[type="submit"]');
        }

        // Dashboard'a yönlendirilmeyi bekle (flexible URL matching)
        await this.page.waitForURL(/\/(admin|dashboard)/, { timeout: 15000 });

        if (await this.isForbiddenPage()) {
            throw new Error(
                'AUTH_GUARD_FIXTURE: Logged-in session reached forbidden page. Check role/permission fixture for admin user.'
            );
        }
    }

    /**
     * Test user olarak giriş yap
     */
    async loginAsUser(email: string, password: string) {
        await this.page.goto('/login');
        await this.page.fill('#email', email);
        await this.page.fill('#password', password);
        await this.page.click('button[type="submit"]');

        await this.page.waitForURL(/\/(admin|dashboard)/, { timeout: 10000 });
    }

    /**
     * Çıkış yap
     */
    async logout() {
        // Logout button'u bul ve tıkla
        await this.page.click('[data-testid="logout-button"], button:has-text("Çıkış")');
        await this.page.waitForURL('/login');
    }

    /**
     * Giriş yapmış mı kontrol et
     */
    async isLoggedIn(): Promise<boolean> {
        const currentURL = this.page.url();
        return !currentURL.includes('/login');
    }
}
