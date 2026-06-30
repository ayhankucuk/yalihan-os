<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * SecureHeaders Middleware
 *
 * Phase 15: unsafe-eval kaldırıldı. Nonce-tabanlı strict-dynamic CSP.
 * SAB Core Constitution v2.6 — Anti-Bypass Guard aktif.
 *
 * Bağımlılık envanteri (unsafe-eval gerektiren):
 * - Alpine.js   → strict-dynamic ile zincir güveni sayesinde nonce'a gerek yok
 * - Chart.js    → CDN script'leri nonce ile mühürlendi
 * - SortableJS  → CDN script'leri nonce ile mühürlendi
 * - Leaflet.js  → CDN script'leri nonce ile mühürlendi
 * - Google Maps → CDN whitelist'e eklendi
 */
class SecureHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        // Her istek için benzersiz kriptografik nonce
        $nonce = base64_encode(random_bytes(16));

        // Blade şablonlarına global paylaşım
        View::share('cspNonce', $nonce);

        // Vite asset bootstrapping için nonce enjeksiyonu
        if (class_exists(Vite::class)) {
            Vite::useCspNonce($nonce);
        }

        $response = $next($request);

        $isLocal = app()->environment('local', 'development', 'testing');

        if ($isLocal) {
            // Geliştirme ortamı: Vite HMR için ws: ve localhost izinleri
            $csp = "default-src 'self'; "
                . "img-src 'self' data: https:; "
                . "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' "
                . "http://localhost:5173 http://localhost:5174 http://localhost:5175 "
                . "http://127.0.0.1:5173 http://127.0.0.1:5174 http://127.0.0.1:5175; "
                . "style-src 'self' 'unsafe-inline' "
                . "https://fonts.googleapis.com https://cdn.jsdelivr.net "
                . "http://localhost:5173 http://localhost:5174 http://localhost:5175; "
                . "connect-src 'self' "
                . "ws://localhost:5173 ws://localhost:5174 ws://localhost:5175 "
                . "ws://127.0.0.1:5173 ws://127.0.0.1:5174 ws://127.0.0.1:5175; "
                . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; "
                . "worker-src blob:; "
                . "frame-ancestors 'self'; "
                . "base-uri 'self'; "
                . "form-action 'self'";
        } else {
            // Production: unsafe-eval kaldırıldı — strict-dynamic ile Alpine.js uyumlu
            $csp = "default-src 'self'; "
                . "img-src 'self' data: https:; "
                . "script-src 'self' 'nonce-{$nonce}' 'strict-dynamic' "
                . "https://maps.googleapis.com https://maps.gstatic.com; "
                . "style-src 'self' 'unsafe-inline' "
                . "https://fonts.googleapis.com https://cdn.jsdelivr.net "
                . "https://unpkg.com; "
                . "connect-src 'self' https://maps.googleapis.com; "
                . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net data:; "
                . "worker-src blob:; "
                . "frame-ancestors 'self'; "
                . "object-src 'none'; "
                . "base-uri 'self'; "
                . "form-action 'self'";
        }

        $response->headers->set('Content-Security-Policy', $csp);
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'no-referrer-when-downgrade');
        $response->headers->set('Permissions-Policy', 'geolocation=(), camera=(), microphone=()');

        return $response;
    }
}
