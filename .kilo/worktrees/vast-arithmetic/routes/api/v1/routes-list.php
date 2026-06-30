<?php

use Illuminate\Support\Facades\Route;

/**
 * Route Debug Endpoint
 * Lists all registered routes in the application.
 */
Route::get('/routes', function () {
    $routes = collect(Route::getRoutes())->map(function ($route) {
        return [
            'uri' => $route->uri(),
            'name' => $route->getName(),
            'methods' => $route->methods(),
            'action' => $route->getActionName(),
        ];
    })->values();

    return response()->json($routes);
});
