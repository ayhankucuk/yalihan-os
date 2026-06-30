<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class StaticCacheHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $path = $request->path();
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        $cacheable = in_array($ext, ['css', 'js', 'jpg', 'jpeg', 'png', 'gif', 'svg', 'webp', 'woff', 'woff2']);

        if ($cacheable) {
            $response->headers->set('Cache-Control', 'public, max-age=604800');
        }

        return $response;
    }
}
