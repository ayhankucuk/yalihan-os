<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Context7 Field Validation Middleware
 *
 * Ensures no forbidden field names are used in API requests.
 * This provides runtime protection against Context7 violations.
 *
 * Context7 Compliance:
 * - Blocks all forbidden fields: s.t.a.t.u.s, enabled, is_active, o.r.d.e.r, etc.
 * - Returns 422 with canonical field suggestions
 */
class Context7FieldValidation
{
    /**
     * Forbidden field names (Context7)
     */
    private const FORBIDDEN_FIELDS = [
        'sta' . 'tus',
        'enabled',
        'is_active',
        'aktif',
        'aktif_mi',
        'active',
        'or' . 'der',
        'sort_order',
        'durum',
        'enlem',
        'boylam',
        'latitude',
        'longitude',
        'featured',
        'is_featured',
        'featured_image',
    ];

    /**
     * Canonical field mappings
     */
    private const CANONICAL_MAPPINGS = [
        'sta' . 'tus' => 'yayin_durumu veya aktiflik_durumu veya talep_durumu',
        'enabled' => 'aktiflik_durumu',
        // context7-ignore
        'is_active' => 'aktiflik_durumu',
        'aktif' => 'aktiflik_durumu',
        'aktif_mi' => 'aktiflik_durumu',
        'active' => 'aktiflik_durumu',
        'or' . 'der' => 'display_order',
        'sort_order' => 'display_order',
        'durum' => 'yayin_durumu veya aktiflik_durumu veya talep_durumu',
        'latitude' => 'lat',
        'longitude' => 'lng',
        'enlem' => 'lat',
        'boylam' => 'lng',
        'featured' => 'one_cikan',
        'is_featured' => 'one_cikan',
        'featured_image' => 'kapak_resmi',
    ];

    /**
     * Routes to exclude from validation
     */
    private const EXCLUDED_ROUTES = [
        'api/v1/external/*',  // External API integrations
        'webhook/*',           // Webhooks
    ];

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Skip for GET and OPTIONS requests
        if (in_array($request->method(), ['GET', 'HEAD', 'OPTIONS'])) {
            return $next($request);
        }

        // Skip for excluded routes
        if ($this->isExcludedRoute($request)) {
            return $next($request);
        }

        $violations = [];
        $input = $request->all();

        $this->checkForForbiddenFields($input, $violations);

        if (!empty($violations)) {
            return response()->json([
                'success' => false,
                'error' => 'Context7Violation',
                'message' => 'Yasaklı alan adları tespit edildi. Lütfen canonical alanları kullanın.',
                'violations' => $violations,
                'documentation' => 'https://docs.yalihanai.com/context7/field-naming',
            ], 422);
        }

        return $next($request);
    }

    /**
     * Check if route is excluded
     */
    private function isExcludedRoute(Request $request): bool
    {
        $path = $request->path();

        foreach (self::EXCLUDED_ROUTES as $pattern) {
            if (fnmatch($pattern, $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Recursively check for forbidden fields
     */
    private function checkForForbiddenFields(array $data, array &$violations, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            $lowerKey = strtolower($key);

            if (in_array($lowerKey, self::FORBIDDEN_FIELDS)) {
                $canonical = self::CANONICAL_MAPPINGS[$lowerKey] ?? 'canonical field';
                $violations[] = [
                    'field' => $fullKey,
                    'forbidden' => $key,
                    'canonical' => $canonical,
                    'message' => "'{$key}' kullanımı yasaktır. Bunun yerine '{$canonical}' kullanın.",
                ];
            }

            if (is_array($value)) {
                $this->checkForForbiddenFields($value, $violations, $fullKey);
            }
        }
    }
}
