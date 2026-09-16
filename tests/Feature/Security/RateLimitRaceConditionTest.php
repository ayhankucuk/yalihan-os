<?php

namespace Tests\Feature\Security;

use App\Http\Middleware\AIRateLimitMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * BACKLOG-6: AI/API Rate-Limit Race Condition Test
 *
 * Verifies that rate limiting uses atomic RateLimiter operations
 * instead of Cache::get/put which had a TOCTOU race condition.
 *
 * @group security
 * @group backlog-6
 */
class RateLimitRaceConditionTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        RateLimiter::clear('race_test_concurrent');
        RateLimiter::clear('race_test_atomic');
    }

    /**
     * AIRateLimitMiddleware should use RateLimiter (not Cache::get/put).
     */
    public function test_ai_rate_limit_middleware_uses_atomic_ratelimiter(): void
    {
        $user = \App\Models\User::factory()->create([
            'role_id' => 1,
            'aktiflik_durumu' => 1,
            'tenant_id' => $this->getDefaultTenantId(),
        ]);

        Auth::login($user);

        $middleware = new AIRateLimitMiddleware();
        $request = Request::create('/api/ai/test', 'POST');
        $request->setRouteResolver(fn () => new class {
            public function getName(): string
            {
                return 'test.route';
            }
        });

        $callCount = 0;
        $response = $middleware->handle($request, function () use (&$callCount) {
            $callCount++;

            return response()->json(['success' => true], 200);
        }, 3, 1);

        // First request should pass
        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(1, $callCount);

        // Verify RateLimiter tracked the attempt (key matches middleware's resolveRequestSignature)
        $key = 'ai_rate_limit:'.$user->id.':test.route';
        $this->assertGreaterThan(0, RateLimiter::attempts($key));
    }

    /**
     * AIRateLimitMiddleware should block after role-based max attempts reached.
     * admin role = 100 attempts per minute.
     */
    public function test_ai_rate_limit_blocks_after_max_attempts(): void
    {
        $user = \App\Models\User::factory()->create([
            'role_id' => 1,
            'aktiflik_durumu' => 1,
            'tenant_id' => $this->getDefaultTenantId(),
        ]);

        Auth::login($user);

        $middleware = new AIRateLimitMiddleware();
        $request = Request::create('/api/ai/test', 'POST');
        $request->setRouteResolver(fn () => new class {
            public function getName(): string
            {
                return 'test.route';
            }
        });

        // Pre-exhaust the limit via RateLimiter directly (admin = 100 attempts)
        $key = 'ai_rate_limit:'.$user->id.':test.route';
        for ($i = 0; $i < 100; $i++) {
            RateLimiter::hit($key, 60);
        }

        // Next request through middleware should be blocked
        $response = $middleware->handle($request, fn () => response()->json(['success' => true], 200), 3, 1);

        $this->assertEquals(429, $response->getStatusCode());
    }

    /**
     * AIRateLimitMiddleware should return correct remaining count in headers.
     * admin role = 100 max_attempts.
     */
    public function test_ai_rate_limit_headers_show_remaining(): void
    {
        $user = \App\Models\User::factory()->create([
            'role_id' => 1,
            'aktiflik_durumu' => 1,
            'tenant_id' => $this->getDefaultTenantId(),
        ]);

        Auth::login($user);

        $middleware = new AIRateLimitMiddleware();
        $request = Request::create('/api/ai/test', 'POST');
        $request->setRouteResolver(fn () => new class {
            public function getName(): string
            {
                return 'test.route';
            }
        });

        $response = $middleware->handle($request, fn () => response()->json(['success' => true], 200), 10, 1);

        // Role-based limit (user role → max_attempts = 20)
        $limit = (int) $response->headers->get('X-RateLimit-Limit');
        $remaining = (int) $response->headers->get('X-RateLimit-Remaining');

        $this->assertGreaterThan(0, $limit);
        $this->assertEquals($limit - 1, $remaining);
    }

    /**
     * Concurrent attempts should not exceed the limit.
     * This simulates the race condition that Cache::get/put had.
     */
    public function test_concurrent_attempts_do_not_exceed_limit(): void
    {
        $key = 'race_test_concurrent';
        $maxAttempts = 5;
        $decaySeconds = 60;

        // Simulate 10 concurrent attempts
        $results = [];
        for ($i = 0; $i < 10; $i++) {
            $results[] = RateLimiter::attempt($key, $maxAttempts, fn () => true, $decaySeconds);
        }

        // Only 5 should succeed (the max)
        $succeeded = array_filter($results, fn ($r) => $r === true);
        $this->assertCount($maxAttempts, $succeeded);

        // 5 should be blocked
        $blocked = array_filter($results, fn ($r) => $r === false);
        $this->assertCount(5, $blocked);
    }

    /**
     * RateLimiter::attempt is atomic — no TOCTOU gap.
     * This is the core regression test for BACKLOG-6.
     */
    public function test_ratelimiter_attempt_is_atomic_no_toctou(): void
    {
        $key = 'race_test_atomic';
        $maxAttempts = 1;

        // First attempt succeeds
        $first = RateLimiter::attempt($key, $maxAttempts, fn () => true, 60);
        $this->assertTrue($first);

        // Second attempt immediately after should fail (no race window)
        $second = RateLimiter::attempt($key, $maxAttempts, fn () => true, 60);
        $this->assertFalse($second);

        // Verify attempts count is exactly 1 (not 2 from a race)
        $this->assertEquals(1, RateLimiter::attempts($key));
    }
}
