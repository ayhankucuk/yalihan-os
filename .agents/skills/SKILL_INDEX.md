---
name: skill-index
description: Yalıhan OS agent skill taxonomy — dosya yolu bazlı otomatik skill seçimi rehberi.
---

# SKILL_INDEX — Automatic Backend Guard Selection

Agent, bir dosyayı açtığında veya değiştireceği zaman bu tabloya bakarak hangi skill'in gerekli olduğunu otomatik belirler.

## File → Skill Mapping Table

| File Pattern | Required Skill(s) | Rationale |
|---|---|---|
| `app/Http/Controllers/Api/V2/*` | `authorization-boundary-auditor` | Tenant/country scope ve 401/403/404 sınırları |
| `app/Http/Controllers/Api/V2/*Cortex*` | `cortex-orchestration-evaluator` + `authorization-boundary-auditor` | AI orkestrasyon + yetki kontrolü |
| `app/Http/Controllers/Api/V2/*Checkin*` | `hermes-event-sync` + `authorization-boundary-auditor` | n8n event entegrasyonu + yetki |
| `app/Http/Controllers/Owner/*` | `authorization-boundary-auditor` | Owner portal yetki sınırları |
| `app/Models/V2/*` | `schema-contract-guardian` | V2 model kontratı, $fillable drift |
| `app/Models/Lead.php` | `authorization-boundary-auditor` + `schema-contract-guardian` | Tenant boundary + schema |
| `database/migrations/*` | `schema-contract-guardian` | Kolon şeması, FK, index uyumu |
| `app/Services/IlanCrudService.php` | `authorization-boundary-auditor` + `schema-contract-guardian` | CRUD yetki + kontrat |
| `app/Services/CRM/*` | `authorization-boundary-auditor` | CRM yetki ve tenant izolasyonu |
| `app/Http/Middleware/*` | `authorization-boundary-auditor` | Middleware güvenlik sınırları |
| `tests/Feature/Security/*` | `authorization-boundary-auditor` | Güvenlik test coverage |
| `tests/Feature/Ilan/*` | `schema-contract-guardian` | İlan schema kontratı |
| `config/*.php` | `schema-contract-guardian` | Config şema uyumu |
| `routes/*.php` | `authorization-boundary-auditor` | Route yetki ve tenant scope |
| `app/Services/Cortex/*` | `cortex-orchestration-evaluator` | AI orkestrasyon, LLM çağrıları |
| `app/AI/*` | `cortex-orchestration-evaluator` | AI prompt, model routing |
| `app/Http/Controllers/Api/V2/*Location*` | `location-data-reconciliation` | Location hiyerarşi |
| `tests/Feature/*Location*` | `location-data-reconciliation` | Location veri doğrulama |
| `app/Services/YalihanCortex.php` | `cortex-orchestration-evaluator` | Cortex orchestration |
| `docs/ERA_V/*` | `saab` | ERA_V roadmap, mimari kararlar |
| `app/Events/*` | `hermes-event-sync` | Event sınıfı, payload kontratı |
| `app/Listeners/*` | `hermes-event-sync` | Event listener, n8n webhook |
| `app/Http/Controllers/Api/V2/*Webhook*` | `hermes-event-sync` + `authorization-boundary-auditor` | Webhook + event koordinasyonu |
| `tests/Feature/*Webhook*` | `hermes-event-sync` | Webhook idempotency testi |
| `tests/Unit/*Cost*` | `cortex-orchestration-evaluator` | AI maliyet hesaplama |
| `app/Services/Ilan/IlanPhotoService.php` | `schema-contract-guardian` | Fotoğraf schema, display_order |
| `app/Http/Controllers/Api/V2/*Photo*` | `schema-contract-guardian` + `authorization-boundary-auditor` | Photo upload + yetki |

---

## Skill Definitions

| Skill | Amaç | Anahtar Dosyalar |
|---|---|---|
| `authorization-boundary-auditor` | 401/403/404 sınırları, tenant/country scope, enumeration koruması | `OwnerAuthController`, V2 API controllers |
| `schema-contract-guardian` | Eloquent $fillable drift, DB kolon kontratı, migration uyumu | Modeller, migrations |
| `cortex-orchestration-evaluator` | AI orkestrasyon, DeepSeek/Ollama/OpenAI routing, token maliyet | `YalihanCortex.php`, AI service'leri |
| `hermes-event-sync` | n8n webhook, event idempotency, kuyruk akışları | Event/Listener sınıfları |
| `location-data-reconciliation` | Location hiyerarşi, orphan FK, migration planı | Location model ve migrations |
| `saab` | Mimari karar, ERA roadmap, ADRS | `docs/ERA_V/`, `.project-brain/SAAB*` |
| `laravel-enterprise-reviewer` | Thin controller, N+1, DDD sınırları, detektör | Tüm service/controller dosyaları |
| `api-contract-regression-guard` | JSON schema, pagination, V1/V2 geriye dönük uyumluluk | API controller return'ları |
| `security-secret-boundary-guard` | .env, PAT, API key sızıntısı, log maskeleme | Secret içeren dosyalar |
| `ponytail` | Minimal kod, stdlib/native tercih, YAGNI | Tüm kod dosyaları (genel rehber) |

---

## Kullanım

Agent bir dosyayı değiştirmeden ÖNCE:

1. Dosya yolunu yukarıdaki tabloda ara
2. Gerekli skill(leri) `skill()` tool ile yükle
3. Skill rehberine göre kodu gözden geçir
4. Değişikliği uygula

Örnek:
```
Agent: app/Http/Controllers/Api/V2/IlanController.php dosyasını açıyor
→ Tablo: authorization-boundary-auditor gerekli
→ skill('authorization-boundary-auditor') çağır
→ Skill rehberine göre tenant/country scope kontrol et
→ Değişikliği uygula
```

---

## Auto-Load Kuralı (AGENTS.md konvansiyonu)

Agent'ın `file_open` veya mutation başladığında otomatik olarak skill yüklemesi için:
`skill_index` alias'ı veya startup script'i bu tabloyu referans alır.
Manuel müdahale gerekmez — agent dosya yolunu okuyup skill seçimini yapar.
