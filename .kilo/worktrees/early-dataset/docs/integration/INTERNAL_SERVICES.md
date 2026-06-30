# Internal Services Integration Guide

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Overview

This document defines integration patterns between YALIHAN OS internal services. All services communicate via a standardized protocol stack: **Synchronous HTTP/REST** for request-reply, **Hermes Event Bus** for async, and **gRPC** for high-throughput internal calls.

---

## 2. Service Registry

### 2.1 Core Services

| Service | Port | Protocol | Purpose |
|---------|------|----------|---------|
| **YalihanCortex** | 8080 | HTTP/REST | AI orchestration |
| **IlanCrudService** | 8081 | HTTP/REST | Listing CRUD |
| **TenantService** | 8082 | HTTP/REST | Tenant management |
| **AuditService** | 8083 | HTTP/REST | Audit logging |
| **NotificationService** | 8084 | HTTP/REST | Multi-channel notifications |
| **VectorDBService** | 8085 | HTTP/gRPC | Vector embeddings |
| **Hermes** | 8086 | HTTP/WebSocket | Event bus |
| **MCPGateway** | 8087 | HTTP | AI tool gateway |

### 2.2 Service Discovery

```yaml
# DNS-based discovery
services:
  yalihan_cortex:
    fqdn: "yalihan-cortex.internal"
    port: 8080
    health_path: "/health"
    
  ilan_service:
    fqdn: "ilan-service.internal"
    port: 8081
    health_path: "/health"
```

---

## 3. Authentication Between Services

### 3.1 Service-to-Service Auth

All internal HTTP calls use **mutual TLS (mTLS)** with service certificates:

```php
// Each service has its own certificate
$certConfig = [
    'cert_file' => '/certs/ilan-service.crt',
    'key_file' => '/certs/ilan-service.key',
    'ca_file' => '/certs/internal-ca.crt',
];
```

### 3.2 Internal JWT

For service token validation:

```php
// Verify internal service token
class ServiceTokenValidator
{
    public function validate(HttpRequest $request): ServiceIdentity
    {
        $token = $request->bearerToken();
        $payload = $this->jwtVerifier->verify($token, config('services.internal_secret'));
        
        return new ServiceIdentity(
            $payload['service_name'],
            $payload['tenant_id'],
            $payload['scopes']
        );
    }
}
```

### 3.3 Required Scopes

| Caller | Callee | Required Scope |
|--------|--------|----------------|
| YalihanCortex | IlanCrudService | `ilan:read` |
| YalihanCortex | VectorDBService | `vector:write` |
| IlanCrudService | AuditService | `audit:write` |
| NotificationService | MCPGateway | `mcp:invoke` |
| Any | TenantService | `tenant:read` |

---

## 4. Core Service Integrations

### 4.1 IlanCrudService → YalihanCortex

**Pattern:** Request-Reply (sync)  
**Trigger:** Listing created/updated  
**Purpose:** Generate AI descriptions and embeddings

```php
// IlanCrudService publishes event
$this->hermes->publish(Event::make('ilan.created')
    ->withTenant($tenantId)
    ->withPayload([
        'ilan_id' => $ilan->id,
        'baslik' => $ilan->baslik,
        'aciklama' => $ilan->aciklama,
        'tip' => $ilan->tip,
    ]));

// YalihanCortex subscribes and processes
// (see Event Bus: ilan.created consumer)
```

### 4.2 YalihanCortex → VectorDBService

**Pattern:** gRPC (high throughput)  
**Purpose:** Store embeddings for semantic search

```protobuf
// vector_service.proto
service VectorService {
    rpc UpsertEmbedding(UpsertRequest) returns (UpsertResponse);
    rpc Search(SearchRequest) returns (SearchResponse);
    rpc Delete(DeleteRequest) returns (DeleteResponse);
}

message UpsertRequest {
    string tenant_id = 1;
    string collection = 2;
    string id = 3;
    repeated float vector = 4;
    map<string, string> metadata = 5;
}
```

### 4.3 IlanCrudService → AuditService

**Pattern:** Async (Hermes)  
**Purpose:** Record all listing changes

```php
// Every CRUD operation publishes audit event
Hermes::publish(Event::make('audit.log')
    ->withTenant($tenantId)
    ->withPayload([
        'action' => 'ilan.updated',
        'resource_type' => 'ilan',
        'resource_id' => $ilan->id,
        'actor_id' => $this->auth->userId(),
        'changes' => $ilan->getChanges(),
        'ip_address' => request()->ip(),
    ]));
```

### 4.4 TenantService → All Services

**Pattern:** Event-driven  
**Purpose:** Propagate tenant lifecycle events

| Tenant Event | Services Notified |
|--------------|-------------------|
| `tenant.created` | All services initialize tenant context |
| `tenant.plan_changed` | Rate limits updated |
| `tenant.suspended` | All access revoked |
| `tenant.deleted` | All data purged |

```php
// TenantService publishes on activation
Hermes::publish(Event::make('tenant.activated')
    ->withTenant($tenantId)
    ->withPayload([
        'plan' => $tenant->plan,
        'limits' => $tenant->getLimits(),
    ]));
```

---

## 5. AI Workforce Integration

### 5.1 AI Workforce Orchestrator

The orchestrator coordinates multi-step AI tasks:

```
User Request
      │
      ▼
┌──────────────────────────────────────┐
│     AI Workforce Orchestrator        │
│  ┌────────────────────────────────┐ │
│  │  Task Queue (Bull + Redis)     │ │
│  └────────────────────────────────┘ │
└──────────────────────────────────────┘
      │
      ▼
┌──────────────────────────────────────┐
│           Task Pipeline              │
│                                      │
│  ┌──────┐    ┌──────┐    ┌──────┐  │
│  │ Step1 │───▶│ Step2 │───▶│ Step3 │  │
│  └──────┘    └──────┘    └──────┘  │
│      │                           │  │
│      ▼                           ▼  │
│  YalihanCortex              Hermes   │
│  (OpenAI/Ollama/           (result)  │
│   DeepSeek)                         │
└──────────────────────────────────────┘
```

### 5.2 Task Definition

```php
// app/AI/TaskDefinition.php
class PropertyDescriptionTask implements TaskDefinition
{
    public function name(): string => 'property_description';
    
    public function steps(): array => [
        new TaskStep('extract_features', $this->extractFeatures(...)),
        new TaskStep('generate_turkish', $this->generateTurkish(...)),
        new TaskStep('generate_english', $this->generateEnglish(...)),
        new TaskStep('generate_embedding', $this->generateEmbedding(...)),
    ];
    
    public function onComplete(array $results): void
    {
        // Update ilan with generated content
        $this->ilanCrudService->updateAiContent(
            $results['ilan_id'],
            $results['turkish'],
            $results['english'],
            $results['embedding']
        );
    }
}
```

### 5.3 OpenClaw Integration

OpenClaw provides external AI agent workforce:

```php
// app/AI/OpenClawBridge.php
class OpenClawBridge
{
    public function dispatchExternalTask(string $type, array $params): string
    {
        $response = Http::post(config('openclaw.api_url') . '/tasks', [
            'task_type' => $type,
            'input' => $params,
            'callback_webhook' => route('api.v1.openclaw.webhook'),
            'priority' => $params['priority'] ?? 'normal',
        ]);
        
        Hermes::publish(Event::make('ai.task.dispatched')
            ->withPayload([
                'task_id' => $response['task_id'],
                'type' => $type,
                'dispatched_at' => now(),
            ]));
            
        return $response['task_id'];
    }
}
```

---

## 6. Vector Database Integration

### 6.1 Collection Schema

| Collection | Purpose | Dimensions | Index |
|------------|---------|------------|-------|
| `ilan_embeddings` | Property semantic search | 1536 | HNSW |
| `chat_context` | Conversation history | 1536 | HNSW |
| `document_chunks` | Document search | 1536 | HNSW |

### 6.2 Implementation

```php
// app/Services/Vector/VectorDBService.php
class VectorDBService
{
    public function indexListing(Ilan $ilan): void
    {
        // Generate embedding via YalihanCortex
        $embedding = $this->cortex->embed($ilan->baslik . ' ' . $ilan->aciklama);
        
        $this->vectorClient->upsert('ilan_embeddings', [
            'id' => "ilan:{$ilan->id}:{$ilan->tenant_id}",
            'vector' => $embedding,
            'metadata' => [
                'tenant_id' => $ilan->tenant_id,
                'ilan_id' => $ilan->id,
                'tip' => $ilan->tip,
                'il' => $ilan->il,
                'fiyat' => $ilan->fiyat,
            ]
        ]);
    }
    
    public function semanticSearch(string $query, int $tenantId): array
    {
        $embedding = $this->cortex->embed($query);
        
        return $this->vectorClient->search('ilan_embeddings', [
            'vector' => $embedding,
            'filter' => ['tenant_id' => $tenantId],
            'top_k' => 20,
        ]);
    }
}
```

---

## 7. Notification Service Integration

### 7.1 Channel Abstraction

```php
// app/Services/Notification/NotificationService.php
interface NotificationChannel
{
    public function send(Notification $notification): SendResult;
}

class NotificationService
{
    public function send(Notification $notification): array
    {
        $channels = $this->resolveChannels($notification->channels());
        
        return array_map(
            fn(NotificationChannel $channel) => $channel->send($notification),
            $channels
        );
    }
}
```

### 7.2 Channel Implementations

| Channel | Implementation | Use Case |
|---------|---------------|----------|
| **Email** | Via Google Gmail API | Formal communications |
| **Telegram** | Via Telegram Bot API | Real-time alerts |
| **WhatsApp** | Via WhatsApp Business API | Client notifications |
| **In-App** | WebSocket + DB | Dashboard notifications |

### 7.3 Notification Types

```php
enum NotificationType: string
{
    case VIEWING_CONFIRMED = 'viewing_confirmed';
    case VIEWING_REMINDER = 'viewing_reminder';
    case INQUIRY_RECEIVED = 'inquiry_received';
    case LISTING_PUBLISHED = 'listing_published';
    case PRICE_CHANGE = 'price_change';
    case AI_COMPLETED = 'ai_completed';
}
```

---

## 8. MCP Gateway Integration

### 8.1 Gateway as Internal Service

MCPGateway provides standardized AI tool access:

```php
// Any internal service can invoke MCP tools
$result = $this->mcpGateway->invoke(
    'google_workspace',
    'gmail_send',
    [
        'to' => $clientEmail,
        'subject' => $subject,
        'body' => $body,
    ],
    [
        'tenant_id' => $this->tenantId,
        'user_id' => $this->userId,
    ]
);
```

### 8.2 Tool Routing

```php
// MCPGateway routes based on tool prefix
class ToolRouter
{
    public function route(string $server, string $tool): McpServer
    {
        return match ($server) {
            'google_workspace' => $this->googleWorkspaceServer,
            'telegram' => $this->telegramServer,
            'whatsapp' => $this->whatsappServer,
            'marketplace' => $this->marketplaceServer,
            default => throw new UnknownServerException($server),
        };
    }
}
```

---

## 9. Error Handling

### 9.1 Inter-Service Errors

| Error Type | Handling Strategy |
|------------|------------------|
| **Timeout** | Retry 3x with exponential backoff |
| **Connection Refused** | Circuit breaker, fallback |
| **Auth Failure** | Re-authenticate, retry |
| **Rate Limited** | Queue and retry later |
| **5xx Server Error** | Retry with backoff |

### 9.2 Circuit Breaker Configuration

```yaml
circuit_breakers:
  yalihan_cortex:
    failure_threshold: 5
    timeout: 30s
    half_open_requests: 3
    
  vector_db:
    failure_threshold: 3
    timeout: 10s
    half_open_requests: 1
```

---

## 10. Health & Monitoring

### 10.1 Health Endpoints

Each service exposes `/health` endpoint:

```json
{
  "status": "healthy",
  "service": "ilan-crud-service",
  "version": "2.1.0",
  "checks": {
    "database": "ok",
    "cache": "ok",
    "hermes": "ok"
  },
  "timestamp": "2026-06-28T10:00:00Z"
}
```

### 10.2 Service Metrics

| Metric | Description |
|--------|-------------|
| `internal_http_requests_total` | Total requests by service, endpoint, status |
| `internal_http_request_duration_seconds` | Latency histogram |
| `hermes_publish_total` | Events published by service, topic |
| `hermes_consume_total` | Events consumed by service, topic |
| `circuit_breaker_state` | Current state by service |

---

## 11. Service Dependencies

```
                    ┌─────────────────┐
                    │  TenantService  │
                    └────────┬────────┘
                             │
            ┌────────────────┼────────────────┐
            │                │                │
            ▼                ▼                ▼
    ┌───────────┐    ┌───────────┐    ┌───────────┐
    │IlanCrud   │    │ Audit     │    │Notify     │
    │Service    │    │ Service   │    │Service    │
    └─────┬─────┘    └─────┬─────┘    └─────┬─────┘
          │                │                │
          │    ┌───────────┴───────────┐    │
          │    │                       │    │
          ▼    ▼                       ▼    ▼
    ┌─────────────────────────────────────────┐
    │           Hermes (Event Bus)            │
    └─────────────────────────────────────────┘
          │         │         │         │
          ▼         ▼         ▼         ▼
    ┌─────────┐ ┌─────────┐ ┌─────────┐ ┌─────────┐
    │Yalihan  │ │VectorDB │ │  MCP    │ │OpenClaw │
    │Cortex   │ │Service  │ │Gateway  │ │ (Ext)   │
    └─────────┘ └─────────┘ └─────────┘ └─────────┘
```

---

*Document approved by SAAB v6 Integration Office*
