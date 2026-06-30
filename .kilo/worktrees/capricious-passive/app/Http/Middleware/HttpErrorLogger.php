<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HttpErrorLogger
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);
        $httpStatusCode = $response->getStatusCode();
        if ($httpStatusCode >= 400) {
            Log::channel('security')->warning('http-error', [
                'http_status_code' => $httpStatusCode,
                'method' => $request->getMethod(),
                'path' => $request->getPathInfo(),
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
            ]);
        }

        return $response;
    }
}
