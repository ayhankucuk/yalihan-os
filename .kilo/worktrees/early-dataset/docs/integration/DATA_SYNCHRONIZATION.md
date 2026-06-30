# Data Synchronization Architecture

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Overview

Data synchronization in YALIHAN OS spans three dimensions:
- **Internal Sync** — Services sharing state via Hermes events
- **External Sync** — Listings published to marketplace platforms
- **Backup Sync** — Data replicated for disaster recovery

All synchronization follows the **CAP theorem** decisions: **CP** for transactional data, **AP** for listing distribution.

---

## 2. Synchronization Patterns

### 2.1 Event-Driven Sync (Primary)

```
┌─────────────┐    Event    ┌─────────────┐
│   Source    │────────────▶│  Consumer   │
│   Service   │             │   Service   │
└──────┬──────┘             └──────┬──────┘
       │                           │
       │     ┌─────────────────────┘
       │     │
       ▼     ▼
┌─────────────────┐
│  Hermes (DLQ)   │
│  if fails       │
└─────────────────┘
```

### 2.2 Poll-Based Sync (Fallback)

For systems without webhook support:

```
┌─────────────┐    Scheduler    ┌─────────────┐
│    n8n      │────────────────▶│   Source    │
│  Workflow   │                 │    API      │
└──────┬──────┘                 └──────┬──────┘
       │                               │
       │         ┌─────────────────────┘
       │         │
       ▼         ▼
┌─────────────────┐
│   Transform     │
│   + Validate    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  YALIHAN OS     │
│  Database       │
└─────────────────┘
```

### 2.3 Delta Sync

Only changed records are synchronized:

```php
// Track last sync timestamp per entity
class SyncTracker
{
    public function getLastSync(string $tenantId, string $source): Carbon
    {
        return Cache::remember("sync:{$tenantId}:{$source}", 3600, function () {
            return DB::table('sync_checkpoints')
                ->where('tenant_id', $tenantId)
                ->where('source', $source)
                ->value('last_sync_at') ?? Carbon::now()->subYears(1);
        });
    }
    
    public function updateCheckpoint(string $tenantId, string $source, Carbon $timestamp): void
    {
        DB::table('sync_checkpoints')
            ->updateOrInsert(
                ['tenant_id' => $tenantId, 'source' => $source],
                ['last_sync_at' => $timestamp, 'updated_at' => now()]
            );
    }
}
```

---

## 3. Listing Synchronization

### 3.1 Listing Lifecycle Sync

```
┌──────────────┐
│ Ilan Created │
└──────┬───────┘
       │
       ▼
┌─────────────────────────────────────────────┐
│              HERMES (ilan.created)          │
└─────────────────────────────────────────────┘
       │
       ├──────────────────┬──────────────────┬──────────────────┐
       │                  │                  │                  │
       ▼                  ▼                  ▼                  ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│  Airbnb     │    │ Sahibinden  │    │ Hepsiemlak  │    │   Vector    │
│  Sync Job   │    │  Sync Job   │    │  Sync Job   │    │   Index     │
└──────┬──────┘    └──────┬──────┘    └──────┬──────┘    └──────┬──────┘
       │                  │                  │                  │
       │                  │                  │                  │
       ▼                  ▼                  ▼                  ▼
┌─────────────┐    ┌─────────────┐    ┌─────────────┐    ┌─────────────┐
│ Published   │    │ Published   │    │ Published   │    │  Searchable │
│ on Airbnb   │    │ on Sahibi.  │    │ on Hepsiem. │    │   (RAG)      │
└─────────────┘    └─────────────┘    └─────────────┘    └─────────────┘
```

### 3.2 Sync Configuration

```yaml
marketplace_sync:
  airbnb:
    enabled: true
    sync_direction: "outbound"
    delay_seconds: 5
    retry_policy:
      max_attempts: 3
      backoff: "exponential"
      
  sahibinden:
    enabled: true
    sync_direction: "outbound"
    delay_seconds: 10
    retry_policy:
      max_attempts: 5
      backoff: "linear"
      
  hepsiemlak:
    enabled: true
    sync_direction: "outbound"
    delay_seconds: 10
    retry_policy:
      max_attempts: 5
      backoff: "linear"
```

### 3.3 Bidirectional Sync (Future)

For CRM and Finance integrations:

```
┌──────────────────┐         ┌──────────────────┐
│    YALIHAN OS    │◀═══════▶│      CRM         │
│                  │  Sync   │   (Future)       │
└──────────────────┘         └──────────────────┘
        │                              │
        │    ┌─────────────────────────┘
        │    │
        ▼    ▼
┌───────────────────────────────────────┐
│         Conflict Resolution           │
│  ┌─────────────────────────────────┐ │
│  │  Last-Write-Wins for listings   │ │
│  │  Manual merge for contacts      │ │
│  └─────────────────────────────────┘ │
└───────────────────────────────────────┘
```

---

## 4. Sync Job Implementation

### 4.1 Base Sync Job

```php
// app/Jobs/MarketplaceSyncJob.php
abstract class MarketplaceSyncJob implements ShouldQueue
{
    use Dispatchable, Queueable;
    
    public int $tries = 5;
    public array $backoff = [10, 30, 60, 300, 900]; // seconds
    
    public function __construct(
        public int $ilanId,
        public int $tenantId,
        public string $marketplace
    ) {}
    
    public function handle(): void
    {
        $ilan = Ilan::forTenant($this->tenantId)->findOrFail($this->ilanId);
        
        // Check if sync needed
        if (!$this->shouldSync($ilan)) {
            return;
        }
        
        $this->sync($ilan);
        $this->recordSyncSuccess($ilan);
    }
    
    abstract protected function shouldSync(Ilan $ilan): bool;
    abstract protected function sync(Ilan $ilan): void;
    
    protected function failed(\Throwable $e): void
    {
        Hermes::publish(Event::make('marketplace.failed')
            ->withTenant($this->tenantId)
            ->withPayload([
                'marketplace' => $this->marketplace,
                'ilan_id' => $this->ilanId,
                'error' => $e->getMessage(),
                'attempts' => $this->attempts(),
            ]));
    }
}
```

### 4.2 Airbnb Sync Implementation

```php
class AirbnbSyncJob extends MarketplaceSyncJob
{
    protected function shouldSync(Ilan $ilan): bool
    {
        $lastSync = $ilan->syncMeta('airbnb')->last_sync_at ?? null;
        
        return $ilan->yayin_durumu === 'aktif'
            && (!$lastSync || $ilan->updated_at->gt($lastSync));
    }
    
    protected function sync(Ilan $ilan): void
    {
        $airbnbId = $ilan->marketplace_ids['airbnb'] ?? null;
        
        if ($airbnbId) {
            $this->airbnbService->updateListing($airbnbId, $this->transform($ilan));
        } else {
            $result = $this->airbnbService->createListing($this->transform($ilan));
            $ilan->marketplace_ids = array_merge($ilan->marketplace_ids ?? [], [
                'airbnb' => $result['id']
            ]);
            $ilan->save();
        }
        
        // Sync photos separately (Airbnb API requirement)
        $this->syncPhotos($ilan);
        
        // Update sync timestamp
        $ilan->syncMeta('airbnb')->update(['last_sync_at' => now()]);
    }
    
    protected function transform(Ilan $ilan): array
    {
        return [
            'listing_name' => $ilan->baslik,
            'description' => $ilan->ai_description ?? $ilan->aciklama,
            'listing_type' => $this->mapListingType($ilan->tip),
            'bedrooms' => $ilan->oda_sayisi ?? 1,
            'bathrooms' => $ilan->banyo_sayisi ?? 1,
            'max_guests' => $ilan->kapasite ?? 2,
            'location' => [
                'lat' => $ilan->lat,
                'lng' => $ilan->lng,
                'address' => $ilan->adres,
            ],
            'pricing' => [
                'nightly_price' => $ilan->fiyat / 100, // Convert to cents
                'currency' => 'TRY',
            ],
            'photos' => $ilan->fotolar->take(20)->pluck('url')->toArray(),
            'amenities' => $this->extractAmenities($ilan),
        ];
    }
}
```

---

## 5. Real-Time Sync (Webhooks)

### 5.1 Inbound Webhook Handling

```php
// app/Http/Controllers/Api/WebhookController.php
class WebhookController
{
    public function handleAirbnb(Request $request): Response
    {
        // Verify webhook signature
        if (!$this->verifier->verifyAirbnb($request)) {
            return response()->json(['error' => 'Invalid signature'], 401);
        }
        
        $event = $request->input('event_type');
        $payload = $request->input('data');
        
        // Queue for processing
        AirbnbWebhookJob::dispatch($event, $payload)
            ->onQueue('webhooks.airbnb');
        
        return response()->json(['status' => 'accepted'], 202);
    }
}

// app/Jobs/AirbnbWebhookJob.php
class AirbnbWebhookJob implements ShouldQueue
{
    public function handle(): void
    {
        match ($this->event) {
            'reservation.created' => $this->handleReservation($this->payload),
            'reservation.updated' => $this->updateReservation($this->payload),
            'reservation.cancelled' => $this->cancelReservation($this->payload),
            'calendar_updated' => $this->syncCalendar($this->payload),
            default => Log::warning("Unhandled Airbnb event: {$this->event}"),
        };
    }
}
```

### 5.2 Outbound Webhook Delivery

```php
// app/Services/Webhook/WebhookDeliveryService.php
class WebhookDeliveryService
{
    public function deliver(string $tenantId, string $event, array $payload): DeliveryResult
    {
        $webhooks = Webhook::forTenant($tenantId)
            ->where('event', $event)
            ->where('active', true)
            ->get();
        
        $results = [];
        foreach ($webhooks as $webhook) {
            $results[] = $this->deliverToUrl($webhook, $event, $payload);
        }
        
        return new DeliveryResult($results);
    }
    
    protected function deliverToUrl(Webhook $webhook, string $event, array $payload): Result
    {
        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-Webhook-Signature' => $this->sign($payload, $webhook->secret),
                    'X-Webhook-Event' => $event,
                    'X-Webhook-Delivery' => Str::uuid(),
                ])
                ->post($webhook->url, $payload);
            
            return Result::success($response->status());
        } catch (\Throwable $e) {
            return Result::failure($e->getMessage());
        }
    }
}
```

---

## 6. Data Consistency

### 6.1 Consistency Levels

| Data Type | Consistency | Mechanism |
|-----------|-------------|-----------|
| Listings | Eventual | Hermes + Read-your-writes |
| User Sessions | Strong | Redis + DB |
| Payments | Strong | 2PC / Saga |
| Inventory | Eventual | Source of truth |
| AI Embeddings | Eventual | Background index |

### 6.2 Conflict Resolution

```php
// app/Services/Sync/ConflictResolver.php
class ConflictResolver
{
    public function resolve(
        string $resourceType,
        array $local,
        array $remote
    ): array {
        return match ($resourceType) {
            'ilan' => $this->resolveListing($local, $remote),
            'contact' => $this->resolveContact($local, $remote),
            'calendar' => $this->resolveLatest($local, $remote),
            default => throw new UnknownResourceException($resourceType),
        };
    }
    
    protected function resolveListing(array $local, array $remote): array
    {
        // Local wins for own edits
        // Remote wins for external bookings
        $resolved = array_merge($remote, [
            'baslik' => $local['baslik'],
            'aciklama' => $local['aciklama'],
            'fiyat' => $local['fiyat'],
            'yayin_durumu' => $local['yayin_durumu'],
        ]);
        
        // Last-write-wins for timestamps
        $resolved['updated_at'] = max(
            strtotime($local['updated_at']),
            strtotime($remote['updated_at'])
        );
        
        return $resolved;
    }
}
```

---

## 7. Sync Monitoring

### 7.1 Sync Health Metrics

| Metric | Description | Alert |
|--------|-------------|-------|
| `sync_lag_seconds` | Time since last successful sync | > 5 min |
| `sync_failures_total` | Cumulative sync failures | > 0 |
| `sync_duration_seconds` | Time to complete sync | > 30s |
| `sync_backlog` | Pending sync items | > 100 |
| `dlq_size` | Dead letter queue depth | > 10 |

### 7.2 Dashboard Panels

```
┌─────────────────────────────────────────────────────────────────┐
│                    SYNC HEALTH DASHBOARD                         │
├─────────────────────────────────────────────────────────────────┤
│                                                                  │
│  Airbnb        ████████████░░░░  90%  │  Last: 2m ago   ✅     │
│  Sahibinden    ████████████████  100% │  Last: 1m ago   ✅     │
│  Hepsiemlak    ████████████░░░░  85%  │  Last: 5m ago   ⚠️     │
│                                                                  │
├─────────────────────────────────────────────────────────────────┤
│  DLQ Queue                                                     │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │ Marketplace Failures: 3 items  │  [Review] [Retry] [Purge]│ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

---

## 8. Recovery Procedures

### 8.1 Full Resync

When sync breaks completely:

```bash
# Trigger full resync for a tenant
php artisan sync:full-resync \
    --tenant=tenant_xyz \
    --marketplace=airbnb \
    --force
```

```php
// app/Console/Commands/FullResyncCommand.php
class FullResyncCommand extends Command
{
    public function handle(): int
    {
        $tenantId = $this->option('tenant');
        $marketplace = $this->option('marketplace');
        
        $ilanlar = Ilan::forTenant($tenantId)
            ->where('yayin_durumu', 'aktif')
            ->orderBy('id')
            ->cursor();
        
        $bar = $this->output->createProgressBar($ilanlar->count());
        
        foreach ($ilanlar as $ilan) {
            MarketplaceSyncJob::dispatch($ilan->id, $tenantId, $marketplace);
            $bar->advance();
        }
        
        return Command::SUCCESS;
    }
}
```

### 8.2 Selective Sync

```bash
# Sync specific listing
php artisan sync:single \
    --ilan=12345 \
    --marketplace=airbnb
```

---

## 9. Performance Optimization

### 9.1 Batch Processing

```php
// Process listings in batches to respect API limits
class BatchSyncScheduler
{
    public function scheduleBatchSync(string $marketplace, int $batchSize = 50): void
    {
        $pending = Ilan::forTenant($this->tenantId)
            ->where('sync_status', 'pending')
            ->where('yayin_durumu', 'aktif')
            ->orderBy('priority', 'desc')
            ->orderBy('updated_at')
            ->limit($batchSize)
            ->get();
        
        foreach ($pending->chunk(10) as $chunk) {
            BatchSyncJob::dispatch($chunk->pluck('id'), $this->tenantId, $marketplace);
        }
    }
}
```

### 9.2 Rate Limit Respect

```php
class RateLimitedClient
{
    protected array $limits = [
        'airbnb' => ['requests' => 1000, 'window' => 3600], // per hour
        'sahibinden' => ['requests' => 100, 'window' => 3600],
        'hepsiemlak' => ['requests' => 500, 'window' => 86400], // per day
    ];
    
    public function throttle(string $service): void
    {
        $limit = $this->limits[$service];
        $key = "rate_limit:{$service}:" . floor(time() / $limit['window']);
        
        $current = (int) Cache::get($key, 0);
        
        if ($current >= $limit['requests']) {
            $sleepSeconds = $limit['window'] - (time() % $limit['window']);
            sleep($sleepSeconds);
            Cache::forget($key);
        }
        
        Cache::increment($key);
        Cache::expire($key, $limit['window']);
    }
}
```

---

*Document approved by SAAB v6 Integration Office*
