# Event Bus Architecture (Hermes)

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Overview

Hermes is YALIHAN OS's internal event bus — the **backbone of asynchronous communication** between all services. It enables:

- **Decoupled Services** — Publishers don't know subscribers
- **Scalability** — Add consumers without modifying producers
- **Reliability** — Events persist until delivered
- **Traceability** — Full event lineage tracking

---

## 2. Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                         PUBLISHERS                              │
│  IlanCrudService  │  TenantService  │  AI Services  │  etc.   │
└────────────────────────────┬────────────────────────────────────┘
                             │ Publish Event
                             ▼
┌─────────────────────────────────────────────────────────────────┐
│                      HERMES CORE                                │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐ │
│  │   Topic     │  │  Consumer   │  │    Dead Letter Queue    │ │
│  │  Registry   │  │   Groups    │  │         (DLQ)           │ │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘ │
│                                                                 │
│  ┌─────────────────────────────────────────────────────────────┐ │
│  │                   PERSISTENCE LAYER                        │ │
│  │            MySQL + Redis (hot) + S3 (archive)             │ │
│  └─────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                             │
              ┌──────────────┼──────────────┐
              ▼              ▼              ▼
        ┌──────────┐   ┌──────────┐   ┌──────────┐
        │Consumer A│   │Consumer B│   │Consumer C│
        └──────────┘   └──────────┘   └──────────┘
```

---

## 3. Event Schema

All events follow CloudEvents specification:

```json
{
  "specversion": "1.0",
  "type": "com.yalihan.ilan.created",
  "source": "/ilan/crud-service",
  "id": "evt_abc123def456",
  "time": "2026-06-28T10:30:00Z",
  "datacontenttype": "application/json",
  "tenant_id": "tenant_xyz",
  "data": {
    "ilan_id": 12345,
    "baslik": "Luxury Villa in Bodrum",
    "fiyat": 15000000,
    "yayin_durumu": "aktif"
  }
}
```

---

## 4. Core Events

### 4.1 Listing Events

| Event | Trigger | Consumers |
|-------|---------|-----------|
| `ilan.created` | New listing created | Marketplace sync, AI indexing, Email |
| `ilan.updated` | Listing modified | Marketplace sync, Cache invalidation |
| `ilan.deleted` | Listing removed | Marketplace sync, Search index |
| `ilan.photo_added` | Photo uploaded | AI analysis, Thumbnail generation |
| `ilan.published` | Listing goes live | Notification, Analytics |

### 4.2 Tenant Events

| Event | Trigger | Consumers |
|-------|---------|-----------|
| `tenant.created` | New tenant onboarded | Initialize defaults, Welcome email |
| `tenant.activated` | Tenant activated | Enable services, Provision resources |
| `tenant.suspended` | Tenant suspended | Disable access, Notify users |
| `tenant.plan_changed` | Subscription change | Update limits, Notify |

### 4.3 AI Events

| Event | Trigger | Consumers |
|-------|---------|-----------|
| `ai.description_generated` | AI description ready | Store, Update search index |
| `ai.embedding_completed` | Vector embedding done | Update vector DB |
| `ai.analysis_completed` | Property analysis done | Store results |

### 4.4 Integration Events

| Event | Trigger | Consumers |
|-------|---------|-----------|
| `marketplace.synced` | External sync complete | Audit log |
| `marketplace.failed` | Sync failed | Alerting, Retry |
| `webhook.delivered` | Webhook sent | Audit |
| `webhook.failed` | Webhook failed | Retry, Alert |

---

## 5. Consumer Groups

Each consumer belongs to a **consumer group** — events are distributed across group members:

```yaml
consumer_groups:
  marketplace_sync:
    description: "Sync listings to external platforms"
    consumers:
      - airbnb_sync
      - sahibinden_sync
      - hepsiemlak_sync
    concurrency: 3
    retry_policy:
      max_attempts: 5
      backoff: "exponential"
      initial: "1s"
      max: "5m"
      
  ai_indexing:
    description: "Index content for AI"
    consumers:
      - vector_indexer
      - embedding_generator
    concurrency: 5
    retry_policy:
      max_attempts: 3
```

---

## 6. Topic Configuration

```yaml
topics:
  ilan.created:
    retention: 7d
    compaction: true
    replication_factor: 3
    
  ai.description_generated:
    retention: 30d
    compaction: false
    
  marketplace.failed:
    retention: 90d
    dlq: "marketplace_failures"
```

---

## 7. Dead Letter Queue (DLQ)

Events that fail after max retries go to DLQ:

| DLQ Name | Source | Alert Threshold |
|----------|--------|------------------|
| `marketplace_failures` | marketplace.* | 10 in 5min |
| `ai_processing_failures` | ai.* | 5 in 5min |
| `notification_failures` | tenant.* | 20 in 5min |

**DLQ Processing:**
- Manual review dashboard
- Re-queue with delay
- Discard with audit
- Alert on threshold

---

## 8. Implementation

### 8.1 Event Publisher

```php
// In IlanCrudService
use App\Services\Hermes\EventBus;

class IlanCrudService
{
    public function __construct(
        private IlanRepository $ilanRepo,
        private EventBus $hermes
    ) {}
    
    public function create(array $data): Ilan
    {
        $ilan = $this->ilanRepo->create($data);
        
        $this->hermes->publish(
            Event::make('ilan.created')
                ->withTenant($this->tenantId)
                ->withPayload(['ilan_id' => $ilan->id])
        );
        
        return $ilan;
    }
}
```

### 8.2 Event Consumer

```php
// app/Consumers/MarketplaceSyncConsumer.php
class MarketplaceSyncConsumer implements EventConsumer
{
    public function handle(CloudEvent $event): void
    {
        match ($event->type) {
            'ilan.created' => $this->syncToAirbnb($event),
            'ilan.updated' => $this->updateAirbnb($event),
            'ilan.deleted' => $this->removeFromAirbnb($event),
        };
    }
    
    public function topic(): string => 'ilan.*';
    public function group(): string => 'marketplace_sync';
}
```

---

## 9. Monitoring

### Key Metrics
- `hermes_events_published_total{topic, status}`
- `hermes_events_consumed_total{topic, consumer, status}`
- `hermes_event_processing_duration_seconds{topic, consumer}`
- `hermes_dlq_size{queue}`
- `hermes_consumer_lag{topic, consumer_group}`

### Alerting Rules
| Condition | Severity | Action |
|-----------|----------|--------|
| Consumer lag > 1000 | Warning | Scale consumers |
| DLQ size > 100 | Critical | Immediate review |
| Publishing failure > 1% | Warning | Investigate |
| Processing latency p95 > 5s | Warning | Optimize |

---

## 10. Guarantees

| Guarantee | Level |
|-----------|-------|
| **At-Least-Once** | Events delivered at least once (with idempotency) |
| **Ordering** | Per-partition ordering maintained |
| **Durability** | Events persisted before acknowledgment |
| **Replay** | Events replayable from offset |

---

*Document approved by SAAB v6 Integration Office*
