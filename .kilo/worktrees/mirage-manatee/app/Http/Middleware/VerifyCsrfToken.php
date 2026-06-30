<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'telegram/test-message-test',
        'telegram/webhook',
        'api/telegram/webhook',  // Telegram webhook endpoint (CSRF koruması yok)
        'telegram/set-webhook',
        'login',  // Geçici olarak CSRF korumasını devre dışı bırak
        'admin/ozellikler/context7/*',  // Context7 AI endpoints için CSRF devre dışı
        'test-tkgm-direct',  // TKGM test endpoint
        'test-tkgm-investment',  // TKGM yatırım analizi endpoint
        'test-tkgm-ai-plan',  // TKGM AI plan notları endpoint
        'api/admin/market-intelligence/sync',  // n8n bot sync endpoint (n8n.secret middleware ile korumalı)
        'admin/ilan-kategorileri/api/*',  // Property Type Manager API endpoints
    ];
}
