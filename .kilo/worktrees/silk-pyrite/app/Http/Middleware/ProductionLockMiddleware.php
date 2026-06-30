<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ProductionLockMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        if (app()->environment('testing')) {
            return $next($request);
        }

        if (config('governance.production_lock') !== 'OPEN') {
            abort(423, 'Production Lock Active');
        }

        return $next($request);
    }
}
