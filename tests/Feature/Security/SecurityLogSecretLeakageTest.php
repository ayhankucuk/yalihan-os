<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\SecurityMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * BACKLOG-7: Security Log Secret Leakage Test
 *
 * Verifies that SecurityMiddleware::logSecurityEvent() does NOT leak
 * sensitive data (passwords, tokens, API keys, Authorization headers)
 * into the security log channel.
 *
 * @group security
 */
class SecurityLogSecretLeakageTest extends TestCase
{
    /**
     * Test: maskSensitiveHeaders redacts Authorization header
     */
    public function test_mask_sensitive_headers_redacts_authorization(): void
    {
        $middleware = new SecurityMiddleware();
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('maskSensitiveHeaders');
        $method->setAccessible(true);

        $headers = [
            'Authorization' => ['Bearer secret-jwt-token-12345'],
            'Content-Type' => ['application/json'],
            'Accept' => ['application/json'],
        ];

        $result = $method->invoke($middleware, $headers);

        $this->assertEquals('[REDACTED]', $result['Authorization']);
        $this->assertEquals(['application/json'], $result['Content-Type']);
        $this->assertEquals(['application/json'], $result['Accept']);
    }

    /**
     * Test: maskSensitiveHeaders redacts Cookie header
     */
    public function test_mask_sensitive_headers_redacts_cookie(): void
    {
        $middleware = new SecurityMiddleware();
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('maskSensitiveHeaders');
        $method->setAccessible(true);

        $headers = [
            'Cookie' => ['session=abc123; xsrf=def456'],
            'X-API-Key' => ['my-secret-api-key'],
            'X-Goog-Channel-Token' => ['goog-token-xyz'],
        ];

        $result = $method->invoke($middleware, $headers);

        $this->assertEquals('[REDACTED]', $result['Cookie']);
        $this->assertEquals('[REDACTED]', $result['X-API-Key']);
        $this->assertEquals('[REDACTED]', $result['X-Goog-Channel-Token']);
    }

    /**
     * Test: maskSensitiveInput redacts password fields
     */
    public function test_mask_sensitive_input_redacts_password(): void
    {
        $middleware = new SecurityMiddleware();
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('maskSensitiveInput');
        $method->setAccessible(true);

        $input = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'password' => 'super-secret-password',
            'password_confirmation' => 'super-secret-password',
        ];

        $result = $method->invoke($middleware, $input);

        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
        $this->assertEquals('[REDACTED]', $result['password']);
        $this->assertEquals('[REDACTED]', $result['password_confirmation']);
    }

    /**
     * Test: maskSensitiveInput redacts token and API key fields
     */
    public function test_mask_sensitive_input_redacts_tokens(): void
    {
        $middleware = new SecurityMiddleware();
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('maskSensitiveInput');
        $method->setAccessible(true);

        $input = [
            'api_key' => 'sk-1234567890abcdef',
            'access_token' => 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...',
            'refresh_token' => 'rt-abcdef123456',
            'client_secret' => 'secret-xyz',
            'normal_field' => 'not-sensitive',
        ];

        $result = $method->invoke($middleware, $input);

        $this->assertEquals('[REDACTED]', $result['api_key']);
        $this->assertEquals('[REDACTED]', $result['access_token']);
        $this->assertEquals('[REDACTED]', $result['refresh_token']);
        $this->assertEquals('[REDACTED]', $result['client_secret']);
        $this->assertEquals('not-sensitive', $result['normal_field']);
    }

    /**
     * Test: maskSensitiveInput handles nested arrays recursively
     */
    public function test_mask_sensitive_input_handles_nested_arrays(): void
    {
        $middleware = new SecurityMiddleware();
        $reflection = new \ReflectionClass($middleware);
        $method = $reflection->getMethod('maskSensitiveInput');
        $method->setAccessible(true);

        $input = [
            'user' => [
                'name' => 'Jane',
                'password' => 'nested-secret',
            ],
            'settings' => [
                'api_key' => 'nested-api-key',
                'theme' => 'dark',
            ],
            'normal' => 'visible',
        ];

        $result = $method->invoke($middleware, $input);

        $this->assertEquals('Jane', $result['user']['name']);
        $this->assertEquals('[REDACTED]', $result['user']['password']);
        $this->assertEquals('[REDACTED]', $result['settings']['api_key']);
        $this->assertEquals('dark', $result['settings']['theme']);
        $this->assertEquals('visible', $result['normal']);
    }
}
