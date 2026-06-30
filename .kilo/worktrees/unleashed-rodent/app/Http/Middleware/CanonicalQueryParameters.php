<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CanonicalQueryParameters
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('testing')) {
            return $next($request);
        }
        if (strtoupper($request->getMethod()) === 'GET') {
            $path = $request->path();
            $original = $request->query();

            $allowed = ['page', 'per_page', 'q', 'search', 'kategori', 'sort', 'filter'];
            if (str_starts_with($path, 'admin/adres-yonetimi')) {
                $allowed = [];
            } elseif (str_starts_with($path, 'ilanlar/international')) {
                $allowed = [
                    'country', 'city', 'citizenship', 'min_price', 'max_price', 'property_type', 'delivery', 'min_area', 'max_area', 'type', 'sort',
                ];
            } elseif (str_starts_with($path, 'ai/explore')) {
                $allowed = [
                    'budget_min', 'budget_max', 'city', 'scenario', 'risk', 'timeline',
                ];
            }

            $canonical = [];
            foreach ($original as $key => $value) {
                if ($key === '_token' || $key === 'parent_id') {
                    continue;
                }
                if (! in_array($key, $allowed, true)) {
                    continue;
                }
                if (is_array($value)) {
                    $value = end($value) ?: null;
                }
                if ($value === null || $value === '') {
                    continue;
                }
                $canonical[$key] = $value;
            }

            $target = $request->url();
            if (! empty($canonical)) {
                $target .= '?'.http_build_query($canonical);
            }

            if ($target !== $request->fullUrl()) {
                return redirect()->to($target, 302);
            }
        }

        return $next($request);
    }
}
