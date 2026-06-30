# Laravel Agent Instructions

> Yalıhan Emlak — Laravel Framework spesifik kuralları.

## Framework Versiyon

- **Laravel**: 10.x
- **PHP**: 8.2+
- **PHP CI**: 8.2 (GitHub Actions ile uyumlu)

## Config Kullanımı

`env()` sadece `config/` dosyalarında ve `bootstrap/` içinde:

```php
// ✅ config/app.php içinde
'debug' => env('APP_DEBUG', false),

// ❌ app/Services/ içinde
env('APP_DEBUG')

// ✅ app/Services/ içinde
config('app.debug')
```

## Model Kuralları

- `BaseModel` extend et
- `$fillable` Context7 uyumlu (Türkçe alan adları)
- `$casts` lowercase (`yayin_durumu` → `string`)
- Lazy loading YASAK — `with()` kullan

## Service Katmanı

Tüm iş mantığı Service'de:

```php
// app/Services/Ilan/IlanCrudService.php
class IlanCrudService
{
    public function create(array $data): Ilan { ... }
    public function update(Ilan $ilan, array $data): Ilan { ... }
    public function delete(Ilan $ilan): void { ... }
}
```

## Event/Listener

ListingCreated, IlanYayinlandiEvent vb. domain event'leri kullan.
Event'ler projection tetikler (CQRS).

## Queue Jobs

- `$tries`: minimum 3 (external I/O)
- `$backoff`: exponential
- `$timeout`: belirt
- Exception yutma YASAK

## Migration Kuralları

- Migration'lar DB schema SSOT değil
- Canonical schema: `database/schema/mysql-schema.sql`
- Durum alanları: `tinyint` (ENUM YASAK)
- Kolon adları: snake_case, Türkçe

## Testing

- Test: `php artisan test`
- Factory + Seeder güncel tut
- Skip test YASAK
- Feature test > Unit test tercih et

## Cache

| Data Type | TTL |
|-----------|-----|
| Dynamic Lists | 60-120s |
| Financial/KPI | 600s |
| SEO Meta | 24h |

Model `saved`/`deleted` event'lerinde cache invalidation zorunlu.

## Telemetry

- AI istekleri: `ai_query_logs`, `ai_prompt_logs`
- Log channel daily rotate
- Exception: `Log::error()` + rethrow
