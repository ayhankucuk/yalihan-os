# Security and Authentication Architecture

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Security Model Overview

YALIHAN OS follows **Zero Trust** architecture — no implicit trust, every request is authenticated and authorized regardless of origin.

### 1.1 Security Layers

```
┌─────────────────────────────────────────────────────────────┐
│                    EXTERNAL LAYER                          │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐     │
│  │  OAuth   │ │ API Keys │ │   JWT    │ │  mTLS    │     │
│  │  2.0     │ │          │ │  Bearer  │ │          │     │
│  └────┬─────┘ └────┬─────┘ └────┬─────┘ └────┬─────┘     │
└───────┼────────────┼────────────┼────────────┼────────────┘
        │            │            │            │
        └────────────┴────────────┴────────────┘
                         │
┌────────────────────────▼───────────────────────────────────┐
│                    API GATEWAY                             │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  Rate Limiting │ Tenant Isolation │ Request Valid. │  │
│  └──────────────────────────────────────────────────────┘  │
└────────────────────────┬───────────────────────────────────┘
                         │
┌────────────────────────▼───────────────────────────────────┐
│                 INTERNAL SERVICES                           │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐     │
│  │ Service  │ │ Service  │ │ Service  │ │ Service  │     │
│  │   Cert   │ │    JWT   │ │  Scope   │ │    mTLS  │     │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘     │
└─────────────────────────────────────────────────────────────┘
```

---

## 2. Authentication Methods

### 2.1 End-User Authentication

| Method | Use Case | Implementation |
|--------|----------|----------------|
| **Email/Password** | User login | bcrypt hash, rate limited |
| **Magic Link** | Passwordless | JWT token via email |
| **Google OAuth** | SSO | OAuth 2.0 + Google |
| **TOTP** | 2FA | RFC 6238 compliant |

### 2.2 API Authentication

| Method | Use Case | Token Lifetime |
|--------|----------|----------------|
| **Bearer JWT** | User API access | 1 hour |
| **API Key** | Server-to-server | Until revoked |
| **Refresh Token** | Token refresh | 30 days |

### 2.3 External Service Authentication

| Service | Auth Method | Token Management |
|---------|-------------|-----------------|
| Google Workspace | OAuth 2.0 Service Account | Auto-refresh |
| Telegram | Bot Token | Static |
| WhatsApp | OAuth 2.0 | Auto-refresh |
| Airbnb | OAuth 2.0 Partner | Auto-refresh |
| Sahibinden | API Key + Signature | Static |
| Hepsiemlak | Bearer Token | Manual rotation |

---

## 3. Tenant Isolation Security

### 3.1 Data Isolation Model

**CRITICAL:** Every database query MUST include tenant scope.

```php
// INCORRECT - Security violation
$ilan = Ilan::find($id);

// CORRECT - Tenant-scoped
$ilan = Ilan::forTenant($this->tenantId)->find($id);
```

### 3.2 Middleware Enforcement

```php
// app/Http/Middleware/TenantIsolation.php
class TenantIsolation
{
    public function handle(Request $request, Closure $next)
    {
        $tenantId = $request->attributes->get('tenant_id');
        
        // Inject tenant scope into all Eloquent queries
        TenantScope::setCurrent($tenantId);
        
        // Validate tenant exists and is active
        $tenant = Tenant::findOrFail($tenantId);
        abort_if(!$tenant->aktifMi(), 403, 'Tenant suspended');
        
        return $next($request);
    }
}
```

### 3.3 Cross-Tenant Access Prevention

```php
// app/Models/Traits/TenantScoped.php
trait TenantScoped
{
    protected static function booted()
    {
        static::addGlobalScope('tenant', function (Builder $builder) {
            $tenantId = TenantScope::getCurrent();
            if ($tenantId) {
                $builder->where('tenant_id', $tenantId);
            }
        });
    }
}
```

---

## 4. API Security

### 4.1 Request Authentication

```php
// Laravel middleware for API auth
class ApiAuthMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        // Check Bearer token or API Key
        if ($token = $request->bearerToken()) {
            $this->validateJwt($token);
        } elseif ($apiKey = $request->header('X-API-Key')) {
            $this->validateApiKey($apiKey);
        } else {
            return response()->json(['error' => 'Unauthorized'], 401);
        }
        
        // Set tenant context
        $request->attributes->set('tenant_id', $this->tenantId);
        
        return $next($request);
    }
}
```

### 4.2 API Key Management

```yaml
# API Key structure
api_keys:
  - id: "key_abc123"
    tenant_id: "tenant_xyz"
    name: "Production Integration"
    scopes: ["ilan:read", "ilan:write", "ai:invoke"]
    created_at: "2026-01-15T10:00:00Z"
    last_used: "2026-06-28T09:45:00Z"
    expires_at: "2027-01-15T10:00:00Z"
    revoked: false
```

### 4.3 Rate Limiting

```php
// Per-tenant rate limiting
class TenantRateLimiter
{
    public const DEFAULT_LIMITS = [
        'free' => ['requests' => 100, 'window' => 'minute'],
        'pro' => ['requests' => 1000, 'window' => 'minute'],
        'enterprise' => ['requests' => 10000, 'window' => 'minute'],
    ];
    
    public function handle(Request $request): void
    {
        $key = 'rate_limit:' . $this->tenantId . ':' . $this->getWindow();
        
        $current = Cache::increment($key);
        if ($current === 1) {
            Cache::expire($key, 60); // 1 minute window
        }
        
        if ($current > $this->getLimit()) {
            throw new TooManyRequestsException($this->getLimit());
        }
    }
}
```

---

## 5. External Integration Security

### 5.1 OAuth Token Storage

**NEVER store tokens in plain text or code.**

```php
// Encrypted token storage
class ExternalTokenStore
{
    public function store(string $tenantId, string $service, array $tokens): void
    {
        // Encrypt before storage
        $encrypted = encrypt([
            'access_token' => $tokens['access_token'],
            'refresh_token' => $tokens['refresh_token'] ?? null,
            'expires_at' => $tokens['expires_at'],
        ]);
        
        // Store in secure vault
        $this->vault->put(
            "tokens:{$tenantId}:{$service}",
            $encrypted
        );
    }
    
    public function retrieve(string $tenantId, string $service): ?array
    {
        $encrypted = $this->vault->get("tokens:{$tenantId}:{$service}");
        return $encrypted ? decrypt($encrypted) : null;
    }
}
```

### 5.2 Signature Verification

Sahibinden and similar services require request signing:

```php
// app/Integrations/Sahibinden/SignatureService.php
class SignatureService
{
    public function createSignature(array $payload, string $timestamp): string
    {
        $data = json_encode($payload) . $timestamp . config('sahibinden.api_secret');
        return hash_hmac('sha256', $data, config('sahibinden.api_secret'));
    }
    
    public function verifyWebhook(Request $request): bool
    {
        $signature = $request->header('X-Sahibinden-Signature');
        $timestamp = $request->header('X-Sahibinden-Timestamp');
        
        // Check timestamp freshness (5 min window)
        if (abs(time() - $timestamp) > 300) {
            return false;
        }
        
        $expected = $this->createSignature(
            $request->all(),
            $timestamp
        );
        
        return hash_equals($expected, $signature);
    }
}
```

### 5.3 Webhook Security

```php
// Verify webhook authenticity
class WebhookVerifier
{
    public function verifyTelegram(Request $request): bool
    {
        $secret = config('telegram.webhook_secret');
        $data = $request->all();
        
        $hash = $data['hash'];
        unset($data['hash']);
        
        $checkString = collect($data)
            ->sortKeys()
            ->map(fn($v, $k) => "{$k}={$v}")
            ->join("\n");
        
        $expectedHash = bin2hex(hash_hmac('sha256', $checkString, $secret, true));
        
        return hash_equals($expectedHash, $hash);
    }
    
    public function verifyAirbnb(Request $request): bool
    {
        $signature = $request->header('X-Airbnb-Signature');
        $payload = $request->getContent();
        
        return $this->hmacVerify($payload, $signature, config('airbnb.webhook_secret'));
    }
}
```

---

## 6. Encryption Standards

### 6.1 Data at Rest

| Data Type | Encryption | Key Management |
|-----------|------------|-----------------|
| Database | AES-256-GCM | AWS KMS / HashiCorp Vault |
| File Storage | AES-256-GCM | AWS KMS |
| Backups | AES-256-GCM | AWS KMS |
| Token Storage | AES-256-GCM | Dedicated Vault |

### 6.2 Data in Transit

| Connection | Protocol | Min Version |
|------------|----------|-------------|
| External APIs | TLS | 1.2 (prefer 1.3) |
| Internal Services | mTLS | 1.2 |
| Webhooks Out | TLS | 1.2 |
| Browser | TLS | 1.2 |

### 6.3 Key Rotation

- **OAuth Tokens:** Auto-refresh before expiry
- **API Keys:** 90-day rotation policy
- **Encryption Keys:** Annual rotation
- **Service Certificates:** 1-year validity, auto-renewal

---

## 7. Audit Logging

### 7.1 Required Audit Events

| Event Type | Logged Fields |
|------------|---------------|
| `auth.login` | user_id, tenant_id, ip, user_agent, success |
| `auth.logout` | user_id, tenant_id |
| `auth.token_refresh` | user_id, tenant_id |
| `auth.api_key_used` | key_id, tenant_id, endpoint, ip |
| `data.read` | user_id, tenant_id, resource, resource_id |
| `data.write` | user_id, tenant_id, resource, resource_id, changes |
| `integration.webhook` | service, tenant_id, payload_hash, result |
| `integration.api_call` | service, tenant_id, endpoint, status_code |

### 7.2 Audit Log Schema

```json
{
  "id": "aud_abc123",
  "timestamp": "2026-06-28T10:30:00.000Z",
  "event_type": "data.write",
  "actor": {
    "type": "user",
    "id": "user_xyz",
    "tenant_id": "tenant_abc"
  },
  "resource": {
    "type": "ilan",
    "id": 12345
  },
  "action": "ilan.updated",
  "changes": {
    "fiyat": {"old": 5000000, "new": 4500000}
  },
  "context": {
    "ip": "192.168.1.100",
    "user_agent": "Mozilla/5.0...",
    "request_id": "req_abc123"
  }
}
```

### 7.3 Implementation

```php
// app/Services/Audit/AuditService.php
class AuditService
{
    public function log(AuditEvent $event): void
    {
        // Always async to not block operations
        Hermes::publish(Event::make('audit.log')
            ->withTenant($event->tenantId ?? TenantScope::getCurrent())
            ->withPayload($event->toArray()));
    }
}

// Usage in any service
$this->audit->log(AuditEvent::dataWrite(
    userId: $this->auth->userId(),
    tenantId: $this->tenantId,
    resource: 'ilan',
    resourceId: $ilan->id,
    action: 'ilan.updated',
    changes: $ilan->getChanges()
));
```

---

## 8. Secret Management

### 8.1 Secret Types

| Type | Examples | Storage |
|------|----------|---------|
| **API Keys** | Sahibinden, Hepsiemlak | Vault |
| **OAuth Secrets** | Google, Airbnb | Vault |
| **Database Credentials** | MySQL, Redis | Vault |
| **Service Certificates** | Internal mTLS | Kubernetes Secrets |
| **Webhook Secrets** | Telegram, Airbnb | Vault + Env fallback |

### 8.2 Vault Integration

```php
// app/Services/Secret/VaultService.php
class VaultService
{
    public function get(string $key): ?string
    {
        // Try Vault first
        try {
            return $this->vault->read("secret/data/{$key}")['data'];
        } catch (VaultException $e) {
            // Fallback to environment variable
            $envKey = strtoupper(str_replace('.', '_', $key));
            return env($envKey);
        }
    }
    
    public function rotate(string $key): void
    {
        // Generate new secret
        $newValue = Str::random(64);
        
        // Update Vault
        $this->vault->write("secret/data/{$key}", $newValue);
        
        // Trigger application reload
        $this->notifyConfigReload();
    }
}
```

---

## 9. Compliance

### 9.1 GDPR Compliance

| Requirement | Implementation |
|-------------|----------------|
| **Data Minimization** | Only collect necessary data |
| **Consent** | Explicit consent for marketing |
| **Right to Delete** | Cascade delete with audit |
| **Data Portability** | Export API endpoint |
| **Breach Notification** | 72-hour notification SLA |

### 9.2 Data Retention

| Data Type | Retention | Disposal |
|-----------|-----------|----------|
| Audit Logs | 7 years | Secure delete |
| API Logs | 1 year | Secure delete |
| User Sessions | 30 days | Automatic purge |
| Temp Files | 24 hours | Automatic purge |

---

## 10. Security Monitoring

### 10.1 Alert Rules

| Alert | Severity | Condition |
|-------|----------|-----------|
| **Failed Login Storm** | Critical | >50 failures in 5 min |
| **Auth Anomaly** | High | Login from new country |
| **API Key Abuse** | High | >10x normal usage |
| **Cross-Tenant Attempt** | Critical | Any detected attempt |
| **Token Theft** | Critical | Token used from new IP |
| **Webhook Manipulation** | High | Invalid signature |

### 10.2 Monitoring Metrics

```yaml
security_metrics:
  - name: "auth_failures_total"
    labels: ["tenant_id", "reason"]
    
  - name: "api_key_usage_total"
    labels: ["key_id", "endpoint"]
    
  - name: "cross_tenant_attempts_total"
    labels: ["attempted_tenant", "source_service"]
    
  - name: "token_refresh_failures_total"
    labels: ["service"]
```

---

*Document approved by SAAB v6 Integration Office*
