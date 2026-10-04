<?php

namespace Tests\Feature\Security;

use Tests\TestCase;
use App\Http\Middleware\VerifyCsrfToken;

/**
 * Direct middleware test - verifies CSRF middleware configuration
 */
class CSRFLoginDirectTest extends TestCase
{
    public function test_csrf_middleware_should_not_exclude_login(): void
    {
        // Get the middleware instance from the container
        $middleware = app(VerifyCsrfToken::class);
        
        // Use reflection to access the protected $except property
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $except = $property->getValue($middleware);
        
        // 'login' should NOT be in the except list anymore
        $this->assertNotContains('login', $except, 
            "'login' should NOT be in CSRF except list after fix");
    }
    
    public function test_login_route_is_not_excluded_from_csrf(): void
    {
        $middleware = app(VerifyCsrfToken::class);
        
        // Use reflection to access the protected $except property
        $reflection = new \ReflectionClass($middleware);
        $property = $reflection->getProperty('except');
        $property->setAccessible(true);
        $excludedRoutes = $property->getValue($middleware);
        
        // Verify login is NOT excluded
        $this->assertNotContains('login', $excludedRoutes, 
            "Login route should be protected by CSRF after fix");
        
        // Verify legitimate exclusions still exist
        $this->assertContains('telegram/webhook', $excludedRoutes, 
            "Telegram webhook should still be excluded");
        $this->assertContains('api/telegram/webhook', $excludedRoutes, 
            "API Telegram webhook should still be excluded");
    }
}
