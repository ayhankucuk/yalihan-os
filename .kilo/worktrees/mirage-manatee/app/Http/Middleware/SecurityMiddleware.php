<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Security Middleware - Context7 Standard
 *
 * 🎯 Hedefler:
 * - XSS Protection
 * - CSRF Protection
 * - Rate Limiting
 * - Security Headers
 * - Input Sanitization
 *
 * @version 1.0.0
 *
 * @author Context7 Team
 */
class SecurityMiddleware
{
    /**
     * Handle an incoming request
     */
    public function handle(Request $request, Closure $next)
    {
        // Apply security headers
        $response = $next($request);

        // Add security headers
        $this->addSecurityHeaders($response);

        // Log security events
        $this->logSecurityEvent($request, $response);

        return $response;
    }

    /**
     * Add security headers
     */
    private function addSecurityHeaders($response)
    {
        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'DENY',
            'X-XSS-Protection' => '1; mode=block',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'geolocation=(), microphone=(), camera=()',
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Content-Security-Policy' => $this->getCSPHeader(),
        ];

        foreach ($headers as $key => $value) {
            $response->headers->set($key, $value);
        }
    }

    /**
     * Get Content Security Policy header
     */
    private function getCSPHeader(): string
    {
        // Development ortamında Vite server'ı için esnek CSP
        $isDevelopment = app()->environment('local', 'development');
        $extraConnectSrc = $this->getDynamicConnectSrc();

        if ($isDevelopment) {
            return implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://code.jquery.com http://localhost:5173 http://localhost:5174 http://localhost:5175 http://127.0.0.1:5173 http://127.0.0.1:5174 http://127.0.0.1:5175 ws://localhost:5173 ws://localhost:5174 ws://localhost:5175 ws://127.0.0.1:5173 ws://127.0.0.1:5174 ws://127.0.0.1:5175 https://maps.googleapis.com",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com http://localhost:5173 http://localhost:5174 http://localhost:5175 http://127.0.0.1:5173 http://127.0.0.1:5174 http://127.0.0.1:5175",
                "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
                "img-src 'self' data: https: blob:",
                "connect-src 'self' https: wss: http://localhost:5173 http://localhost:5174 http://localhost:5175 http://127.0.0.1:5173 http://127.0.0.1:5174 http://127.0.0.1:5175 ws://localhost:5173 ws://localhost:5174 ws://localhost:5175 ws://127.0.0.1:5173 ws://127.0.0.1:5174 ws://127.0.0.1:5175 http://localhost:11434 http://localhost:51869".$extraConnectSrc,
                "media-src 'self'",
                "object-src 'none'",
                "child-src 'self'",
                "frame-ancestors 'none'",
                "form-action 'self'",
                "base-uri 'self'",
            ]);
        }

        // Production CSP: Alpine.js v3 çalışması için 'unsafe-inline' + 'unsafe-eval' zorunlu.
        // Alpine.js new Function() ile expression evaluate ettiğinden 'unsafe-eval' kaldırılamaz.
        // Uzun vadeli hedef: nonce tabanlı CSP (TODO: SAB teknik borç).
        // Localhost kaynaklarına erişim kapalıdır.
        return implode('; ', [
            "default-src 'self'",
            "script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdn.jsdelivr.net https://unpkg.com https://code.jquery.com https://maps.googleapis.com",
            "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://unpkg.com",
            "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
            "img-src 'self' data: https: blob:",
            "connect-src 'self' https: wss:".$extraConnectSrc,
            "media-src 'self'",
            "object-src 'none'",
            "child-src 'self'",
            "frame-ancestors 'none'",
            "form-action 'self'",
            "base-uri 'self'",
        ]);
    }

    /**
     * Dynamically include AnythingLLM host to connect-src from settings/config.
     */
    private function getDynamicConnectSrc(): string
    {
        try {
            $baseUrl = config('services.anythingllm.base_url', '');
            // Optional DB override
            if (class_exists(\App\Models\Setting::class)) {
                $override = \App\Models\Setting::query()
                    ->where('key', 'ai_anythingllm_url')
                    ->value('value');
                if (! empty($override)) {
                    $baseUrl = (string) $override;
                }
            }

            if (empty($baseUrl)) {
                return '';
            }

            $parts = parse_url($baseUrl);
            if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
                return '';
            }

            $scheme = $parts['scheme'];
            $host = $parts['host'];
            $port = isset($parts['port']) ? (':'.$parts['port']) : '';
            $origin = $scheme.'://'.$host.$port;

            // Prepend a space to join safely
            return ' '.$origin;
        } catch (\Throwable $e) {
            return '';
        }
    }

    /**
     * Log security events
     */
    private function logSecurityEvent(Request $request, $response)
    {
        // Log suspicious requests
        if ($this->isSuspiciousRequest($request)) {
            Log::channel('security')->warning('Suspicious request detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'headers' => $request->headers->all(),
                'input' => $request->all(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Log failed requests
        if ($response->getStatusCode() >= 400) {
            Log::channel('security')->info('HTTP Error', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Check if request is suspicious
     */
    private function isSuspiciousRequest(Request $request): bool
    {
        $suspiciousPatterns = [
            '/\.\.\//', // Directory traversal
            '/<script/i', // XSS attempts
            '/javascript:/i', // JavaScript injection
            '/vbscript:/i', // VBScript injection
            '/onload=/i', // Event handler injection
            '/onerror=/i', // Event handler injection
            '/eval\(/i', // Code execution
            '/base64_decode/i', // Base64 decoding
            '/exec\(/i', // Command execution
            '/system\(/i', // System command
            '/shell_exec/i', // Shell execution
            '/passthru/i', // Command passthrough
            '/file_get_contents/i', // File access
            '/fopen/i', // File operations
            '/fwrite/i', // File writing
            '/include/i', // File inclusion
            '/require/i', // File inclusion
            '/preg_replace.*\/e/i', // Code execution
            '/create_function/i', // Function creation
            '/assert\(/i', // Assertion
            '/union.*select/i', // SQL injection
            '/drop.*table/i', // SQL injection
            '/delete.*from/i', // SQL injection
            '/insert.*into/i', // SQL injection
            '/update.*set/i', // SQL injection
        ];

        $input = $request->all();
        $inputString = json_encode($input);

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $inputString)) {
                return true;
            }
        }

        return false;
    }
}
