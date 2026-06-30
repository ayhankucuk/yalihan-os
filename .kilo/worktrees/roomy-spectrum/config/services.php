<?php

use App\Enums\AI\DeepSeekModel;

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Services (AI Only - Maps removed)
    |--------------------------------------------------------------------------
    */

    'google' => [
        'api_key' => env('GOOGLE_API_KEY', ''),
        'model' => env('GOOGLE_MODEL', 'gemini-2.5-flash'),
    ],

    // Google Maps Geocoding Service
    'google_maps' => [
        'api_key' => env('GOOGLE_MAPS_API_KEY', ''),
        'timeout' => env('GOOGLE_MAPS_TIMEOUT', 10),
        'region' => env('GOOGLE_MAPS_REGION', 'tr'),
        'language' => env('GOOGLE_MAPS_LANGUAGE', 'tr'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Providers & Cost Guardrails
    | Phase 13: Epic 1 - Cost Control
    |--------------------------------------------------------------------------
    */

    'ai' => [
        'daily_limit_usd' => env('AI_DAILY_LIMIT_USD', 10.0),
        'hourly_limit_usd' => env('AI_HOURLY_LIMIT_USD', 2.0),
        'soft_cap_percentage' => env('AI_SOFT_CAP_PERCENTAGE', 80),
        'soft_cap_aktif' => env('AI_SOFT_CAP_ACTIVE', true),
        'hard_cap_aksiyon' => env('AI_HARD_CAP_ACTION', 'fallback'), // fallback | 429
        'hard_cap_aktif' => env('AI_HARD_CAP_ACTIVE', true),
    ],

    'openai' => [
        'api_key' => env('OPENAI_API_KEY', ''),
        'audio_model' => env('OPENAI_AUDIO_MODEL', 'whisper-1'),
        'chat_model' => env('OPENAI_CHAT_MODEL', 'gpt-4o'),
    ],

    'anthropic' => [
        'api_key' => env('ANTHROPIC_API_KEY', ''),
    ],

    'deepseek' => [
        'enabled' => env('DEEPSEEK_ENABLED', false),
        'api_key' => env('DEEPSEEK_API_KEY', ''),
        'base_url' => env('DEEPSEEK_BASE_URL', 'https://api.deepseek.com'),
        'anthropic_base_url' => env('DEEPSEEK_ANTHROPIC_BASE_URL', 'https://api.deepseek.com/anthropic'),
        'model' => env('DEEPSEEK_MODEL', DeepSeekModel::CHAT->value),
        'timeout' => (int) env('DEEPSEEK_TIMEOUT', 30),
        'max_tokens' => (int) env('DEEPSEEK_MAX_TOKENS', 2048),
    ],

    'gemini' => [
        'api_key' => env('GOOGLE_API_KEY', ''),
        'model' => env('GOOGLE_MODEL', 'gemini-2.5-flash'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Google Speech-to-Text
    |--------------------------------------------------------------------------
    */

    'google_speech' => [
        'api_key' => env('GOOGLE_SPEECH_API_KEY', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Azure Cognitive Services
    |--------------------------------------------------------------------------
    */

    'azure_speech' => [
        'api_key' => env('AZURE_SPEECH_API_KEY', ''),
        'region' => env('AZURE_SPEECH_REGION', 'westeurope'),
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Services (NetGSM/İletimerkezi)
    |--------------------------------------------------------------------------
    */

    'sms' => [
        'provider' => env('SMS_PROVIDER', 'netgsm'),
        'netgsm' => [
            'username' => env('NETGSM_USERNAME', ''),
            'password' => env('NETGSM_PASSWORD', ''),
        ],
        'iletimerkezi' => [
            'api_key' => env('ILETIMERKEZI_API_KEY', ''),
            'api_hash' => env('ILETIMERKEZI_API_HASH', ''),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | TKGM (Tapu Kadastro) Servisi
    | Context7 Kural #70: TKGM Entegrasyonu
    |--------------------------------------------------------------------------
    */

    'tkgm' => [
        'base_url' => env('TKGM_BASE_URL', 'https://parselsorgu.tkgm.gov.tr'),
        'api_key' => env('TKGM_API_KEY', ''),
        'timeout' => env('TKGM_TIMEOUT', 10), // seconds
        'cache_durumu' => env('TKGM_CACHE_ENABLED', true),
        'cache_ttl' => env('TKGM_CACHE_TTL', 3600), // 1 hour
    ],

    /*
    |--------------------------------------------------------------------------
    | Wikimapia API
    | Geo-location and Places Data Integration
    | Documentation: https://wikimapia.org/api/
    |--------------------------------------------------------------------------
    */

    'wikimapia' => [
        'base_url' => env('WIKIMAPIA_BASE_URL', 'http://api.wikimapia.org'),
        'api_key' => env('WIKIMAPIA_API_KEY', ''),
        'timeout' => env('WIKIMAPIA_TIMEOUT', 10), // seconds
        'cache_durumu' => env('WIKIMAPIA_CACHE_ENABLED', true),
        'cache_ttl' => env('WIKIMAPIA_CACHE_TTL', 3600), // 1 hour
        'language' => env('WIKIMAPIA_LANGUAGE', 'tr'), // ISO 639-1 format
        'format' => env('WIKIMAPIA_FORMAT', 'json'), // xml, json, jsonp, kml
    ],

    /*
    |--------------------------------------------------------------------------
    | OpenStreetMap Nominatim Service (FREE Alternative)
    |--------------------------------------------------------------------------
    |
    | FREE geocoding and place search service
    | Rate limit: 1 request/second
    | Coverage: Worldwide
    |
    */

    'nominatim' => [
        'base_url' => env('NOMINATIM_BASE_URL', 'https://nominatim.openstreetmap.org'),
        'email' => env('NOMINATIM_EMAIL', env('MAIL_FROM_ADDRESS', 'admin@yalihanemlak.com.tr')),
        'timeout' => env('NOMINATIM_TIMEOUT', 10),
        'cache_durumu' => env('NOMINATIM_CACHE_ENABLED', true),
        'cache_ttl' => env('NOMINATIM_CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | ElevenLabs TTS Service
    |--------------------------------------------------------------------------
    */

    'elevenlabs' => [
        'base_url' => env('ELEVENLABS_BASE_URL', 'https://api.elevenlabs.io'),
        'api_key' => env('ELEVENLABS_API_KEY', ''),
        'model' => env('ELEVENLABS_MODEL', 'eleven_multilingual_v2'),
        'timeout' => env('ELEVENLABS_TIMEOUT', 20),
    ],

    /*
    |--------------------------------------------------------------------------
    | n8n Integration Service
    | Context7: n8n webhook entegrasyonu için yapılandırma
    |--------------------------------------------------------------------------
    */

    'n8n' => [
        // Ana baz URL — N8nIntegrationService ve N8nService tarafından kullanılır
        // 'url' ve 'webhook_url' aynı ENV değerine bağlıdır (tutarsızlık giderildi)
        'url'         => env('N8N_WEBHOOK_URL', 'http://localhost:5678'),
        'webhook_url' => env('N8N_WEBHOOK_URL', 'http://localhost:5678'),
        'webhook_base_url' => env('N8N_WEBHOOK_URL', 'http://localhost:5678'),

        'webhook_secret' => env('N8N_WEBHOOK_SECRET', ''),
        'webhook_token'  => env('N8N_WEBHOOK_TOKEN', ''),
        'timeout'        => env('N8N_TIMEOUT', 30), // seconds

        // N8nWebhookService tarafından kullanılan yüksek düzey webhook'lar
        // ✅ Fix #37: Hardcoded production URL'ler null'a alındı (2026-05-15)
        // Dev ortamı artık prod N8N'i yanlışlıkla tetikleyemez.
        // .env.production'da bu değerlerin tamamı tanımlı olmalı.
        'webhooks' => [
            'high_match'       => env('N8N_WEBHOOK_HIGH_MATCH',      null),
            'new_listing'      => env('N8N_WEBHOOK_NEW_LISTING',      null),
            'demand_fulfilled' => env('N8N_WEBHOOK_DEMAND_FULFILLED', null),
            'critical_update'  => env('N8N_WEBHOOK_CRITICAL_UPDATE',  null),
            'rapor'            => env('N8N_WEBHOOK_RAPOR_BILDIRIMI',  null),
        ],

        // Olay bazlı spesifik webhook'lar (NotifyN8nAbout* Job'ları)
        // ✅ Fix #37: Hardcoded production URL fallback'leri null'a alındı (2026-05-15)
        'new_ilan_webhook_url'                 => env('N8N_NEW_ILAN_WEBHOOK',                 null),
        'ilan_price_changed_webhook_url'       => env('N8N_ILAN_PRICE_CHANGED_WEBHOOK',       null),
        'gorev_created_webhook_url'            => env('N8N_GOREV_CREATED_WEBHOOK',            null),
        'gorev_durum_changed_webhook_url'      => env('N8N_GOREV_DURUM_CHANGED_WEBHOOK',      null),
        'gorev_deadline_yaklasiyor_webhook_url'=> env('N8N_GOREV_DEADLINE_YAKLASIYOR_WEBHOOK', null),
        'gorev_gecikti_webhook_url'            => env('N8N_GOREV_GECIKTI_WEBHOOK',            null),
    ],

    /*
    |--------------------------------------------------------------------------
    | Telegram Bot Service
    | Context7: YalihanCortex_Bot - AI özellikleri ve CRM entegrasyonu
    |--------------------------------------------------------------------------
    */

    'telegram' => [
        'bot_token' => env('TELEGRAM_BOT_TOKEN', ''),
        'bot_username' => env('TELEGRAM_BOT_USERNAME', 'YalihanCortex_Bot'),
        'webhook_url' => env('TELEGRAM_WEBHOOK_URL', 'https://panel.yalihanemlak.com.tr/api/telegram/webhook'),
        'webhook_secret' => env('TELEGRAM_WEBHOOK_SECRET', ''),
        'team_channel_id' => env('TELEGRAM_TEAM_CHANNEL_ID', ''),
        'admin_chat_id' => env('TELEGRAM_ADMIN_CHAT_ID', ''),
    ],

    /*
    |--------------------------------------------------------------------------
    | Facebook Messenger API
    |--------------------------------------------------------------------------
    | Facebook Messenger webhook entegrasyonu için credentials
    | Meta Business Suite üzerinden oluşturulur
    */

    'facebook' => [
        'page_id' => env('FACEBOOK_PAGE_ID', ''),
        'page_access_token' => env('FACEBOOK_PAGE_ACCESS_TOKEN', ''),
        'webhook_verify_token' => env('FACEBOOK_WEBHOOK_VERIFY_TOKEN', ''),
        'app_secret' => env('META_APP_SECRET', ''),
        'api_version' => env('FACEBOOK_API_VERSION', 'v18.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | WhatsApp Business API
    |--------------------------------------------------------------------------
    | WhatsApp Business API entegrasyonu için credentials
    | Meta Business Suite üzerinden oluşturulur
    */

    'whatsapp' => [
        'access_token' => env('WHATSAPP_ACCESS_TOKEN', ''),
        'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID', ''),
        'business_account_id' => env('WHATSAPP_BUSINESS_ACCOUNT_ID', ''),
        'webhook_verify_token' => env('WHATSAPP_WEBHOOK_VERIFY_TOKEN', ''),
        'app_secret' => env('META_APP_SECRET', ''),
        'api_version' => env('WHATSAPP_API_VERSION', 'v18.0'),
        'test_number' => env('WHATSAPP_TEST_NUMBER'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Instagram Graph API
    |--------------------------------------------------------------------------
    | Instagram Direct mesajlaşma için Graph API
    | Meta Business Suite üzerinden oluşturulur
    */

    'instagram' => [
        'access_token' => env('INSTAGRAM_ACCESS_TOKEN', ''),
        'account_id' => env('INSTAGRAM_ACCOUNT_ID', ''),
        'business_account_id' => env('INSTAGRAM_BUSINESS_ACCOUNT_ID', ''),
        'webhook_verify_token' => env('INSTAGRAM_WEBHOOK_VERIFY_TOKEN', ''),
        'app_secret' => env('META_APP_SECRET', ''),
        'api_version' => env('INSTAGRAM_API_VERSION', 'v18.0'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend API Configuration
    | Context7: Vitrin (Mağaza) ile Panel (Depo) arasındaki internal API
    |--------------------------------------------------------------------------
    */

    'frontend_api' => [
        'internal_key' => env('FRONTEND_API_KEY', ''),
        'allowed_ips' => env('FRONTEND_API_ALLOWED_IPS', '172.17.0.0/16,10.0.0.0/8') ? explode(',', env('FRONTEND_API_ALLOWED_IPS', '172.17.0.0/16,10.0.0.0/8')) : [],
        'log_requests' => env('FRONTEND_API_LOG_REQUESTS', false),
        'rate_limit' => env('FRONTEND_API_RATE_LIMIT', 60), // istek/dakika
    ],

];
