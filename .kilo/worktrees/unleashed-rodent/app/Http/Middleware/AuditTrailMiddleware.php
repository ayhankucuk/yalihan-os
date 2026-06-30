<?php

namespace App\Http\Middleware;

use App\Modules\Admin\Models\AuditLog;
use Closure;
use Illuminate\Http\Request;

class AuditTrailMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if (! in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $response;
        }

        if (str_starts_with($request->path(), 'storage/') || str_starts_with($request->path(), 'assets/')) {
            return $response;
        }

        $route = $request->route();
        $routeName = $route ? $route->getName() : null;
        $action = $route ? $route->getActionName() : null;

        $controller = null;
        $method = null;
        if ($action) {
            $parts = explode('@', $action);
            $controller = $parts[0] ?? null;
            $method = $parts[1] ?? null;
        }

        $module = $controller && str_contains($controller, 'Modules') ? 'module' : 'app';

        AuditLog::query()->create([
            'user_id' => optional($request->user())->id,
            'method' => $request->method(),
            'route_name' => $routeName,
            'url' => $request->fullUrl(),
            'ip' => sha1((string) $request->ip()),
            'status_code' => $response->getStatusCode(),
            'module' => $module,
            'controller' => $controller,
            'action' => $method,
            'meta' => [
                'headers' => ['accept' => $request->header('accept'), 'user-agent' => $request->userAgent()],
            ],
        ]);

        return $response;
    }
}
