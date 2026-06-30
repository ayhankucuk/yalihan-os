<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Services\Logging\LogService;
use Illuminate\Support\Str;

class RequestIdMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $rid = $request->headers->get('X-Request-Id') ?: Str::uuid()->toString();
        $request->headers->set('X-Request-Id', $rid);
        LogService::withContext(['request_id' => $rid]);
        $response = $next($request);
        $response->headers->set('X-Request-Id', $rid);

        return $response;
    }
}
