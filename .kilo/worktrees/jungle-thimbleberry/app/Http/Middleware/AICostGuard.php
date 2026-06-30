<?php

namespace App\Http\Middleware;

use App\Services\AI\AICostService;
use App\Services\AI\FallbackAIService;
use App\Exceptions\AI\AIHardCapException;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * AICostGuard Middleware
 *
 * Context7-compliant middleware for enforcing AI cost limits.
 * Intercepts requests to AI endpoints and checks usage limits before proceeding.
 */
class AICostGuard
{
    protected AICostService $costService;
    protected FallbackAIService $fallbackService;

    public function __construct(AICostService $costService, FallbackAIService $fallbackService)
    {
        $this->costService = $costService;
        $this->fallbackService = $fallbackService;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Extract scope identifier (user ID or IP)
        $scope = $this->getScopeIdentifier($request);

        // Check if limits are exceeded
        if (!$this->costService->checkLimits($scope)) {
            $action = config('services.ai.hard_cap_aksiyon', 'fallback');


            if ($action === 'fallback') {
                // Return fallback template response
                $promptType = $this->extractPromptType($request);
                return response()->json(
                    $this->fallbackService->generateResponse($promptType),
                    200 // 200 OK with fallback indicator in meta
                );
            }

            // Return 429 Too Many Requests
            return response()->json([
                'success' => false,
                'message' => 'AI kullanım limiti aşıldı. Lütfen daha sonra tekrar deneyin.',
                'meta' => [
                    'neden' => 'HARD_CAP_LIMIT_ASIMI',
                    'yayin_durumu' => 'hard_cap_aktif'
                ]
            ], 429);
        }

        // Allow request to proceed
        return $next($request);
    }

    /**
     * Extract scope identifier from request (user_id or IP).
     */
    protected function getScopeIdentifier(Request $request): string
    {
        // Prefer authenticated user ID
        if ($request->user()) {
            return 'user_' . $request->user()->id;
        }

        // Fallback to IP address
        return 'ip_' . $request->ip();
    }

    /**
     * Extract prompt type from request for fallback context.
     */
    protected function extractPromptType(Request $request): string
    {
        // Check route name or request path
        $routeName = $request->route()?->getName() ?? '';

        if (str_contains($routeName, 'description')) {
            return 'ilan_aciklama';
        }

        if (str_contains($routeName, 'title')) {
            return 'ilan_baslik';
        }

        // Default generic prompt
        return 'genel';
    }
}
