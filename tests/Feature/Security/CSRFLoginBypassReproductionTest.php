<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Http\Middleware\VerifyCsrfToken;

/**
 * LOCAL_AUTH_LOGIN_CSRF_REMEDIATION_01
 * Regression: POST /login CSRF protection enforcement
 * 
 * Fix applied:
 * - Removed 'login' from VerifyCsrfToken $except array
 * - Form already has @csrf directive (no change needed)
 * 
 * Verification: Uses reflection to directly verify middleware configuration
 */
class CSRFLoginBypassReproductionTest extends TestCase
{
    /**
     * KEY TEST: Verify 'login' is NOT in CSRF except array
     * This is the root cause fix - if this passes, CSRF protection is restored
     */
    public function test_csrf_middleware_except_array_does_not_contain_login(): void
    {
        $middleware = app(VerifyCsrfToken::class);
        
        // Use reflection to access protected $except property
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        
        // KEY ASSERTION: 'login' should NOT be in except
        $this->assertNotContains('login', $except, 
            "FAIL: 'login' found in CSRF except array - CSRF bypass is ACTIVE");
        
        // Verify legitimate exclusions remain
        $this->assertContains('telegram/webhook', $except);
        $this->assertContains('api/telegram/webhook', $except);
    }
    
    /**
     * Verify: Login form has @csrf directive (hidden input present)
     * The form includes CSRF token - only the middleware exception was the problem
     */
    public function test_login_form_has_csrf_hidden_input(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);

        $content = $response->getContent();
        $loginFormSection = $this->extractLoginFormSection($content);
        
        $hasCsrfInput = preg_match(
            '/<input[^>]*type=["\']*hidden["\'][^>]*name=["\']_token["\'][^>]*>/i',
            $loginFormSection
        ) || preg_match(
            '/<input[^>]*name=["\']_token["\'][^>]*type=["\']*hidden["\'][^>]*>/i',
            $loginFormSection
        );
        
        $this->assertTrue((bool)$hasCsrfInput, 
            'Login form should have hidden CSRF input (@csrf directive)');
    }
    
    /**
     * Verify: Login form has CSRF meta tag for JavaScript
     */
    public function test_login_form_renders_csrf_in_meta_for_js(): void
    {
        $response = $this->get(route('login'));
        $response->assertStatus(200);
        $content = $response->getContent();

        $this->assertStringContainsString('csrf-token', $content);
        $this->assertStringContainsString(csrf_token(), $content);
    }
    
    /**
     * Baseline: With CSRF disabled, auth failure returns 302
     */
    public function test_baseline_without_csrf_returns_302(): void
    {
        $this->withoutMiddleware(VerifyCsrfToken::class);
        
        $response = $this->post(route('login'), [
            'email' => 'invalid@example.com',
            'password' => 'wrong-password',
        ]);
        
        // Without CSRF middleware, auth fails → redirect
        $this->assertEquals(302, $response->getStatusCode());
    }

    private function extractLoginFormSection(string $content): string
    {
        if (preg_match('/<form[^>]*action=["\'].*?login.*?["\'][^>]*>.*?<\/form>/is', $content, $matches)) {
            return $matches[0];
        }
        return '';
    }
}
