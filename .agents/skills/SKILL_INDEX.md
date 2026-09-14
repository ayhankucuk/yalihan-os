---
name: skill-index
description: Yalıhan OS agent skill taxonomy — dosya yolu bazlı otomatik skill seçimi rehberi.
---

# SKILL_INDEX — Automatic Skill Selection

Agent bir dosyayı açtığında veya değiştireceği zaman bu tabloya bakarak hangi skill'in gerekli olduğunu otomatik belirler.

---

## 🔷 Core Skills (Her Görev Öncesi — Zorunlu)

| File Pattern | Required Skill | Rationale |
|---|---|---|
| Tüm dosyalar | `core-engineering-guard` | Mimari kurallar, kanıt standardı, Laravel kod kalitesi, thin controller |
| `*.blade.php` (Alpine/JS) | `blade-alpine-runtime-guardian` | Script kapanışı, Alpine scope, window bağlama |
| `app/Http/Controllers/Api/*Ilan*` | `api-contract-envelope-guardian` | JSON envelope drift, `status`/`yayin_durumu` alan eşleşmesi |
| Git worktree, `git worktree` | `multi-agent-worktree-sandbox` | Worktree izolasyonu, ajan başına branch, handoff |
| Git dirty tree, cleanup | `git-worktree-hygiene` | Dirty tree sınıflandırma, güvenli paketleme, envanter yönetimi |
| Commit öncesi / hot-spot | `conflict-guard-preflight` | Pre-commit hot-spot taraması, kilit edinme/bırakma |
| Envanter analizi | `dirty-inventory-generator` | Dirty dosya analizi, risk matrisi ve envanter raporu üretimi |
| `docs/ERA_V/*` | `saab` | ERA roadmap, mimari kararlar |
| `docs/architecture/*` | `saab` + `core-engineering-guard` | Mimari anayasa uyumu |

---

## 🔒 Güvenlik & Yetki

| File Pattern | Required Skill | Kontrol Ettiği Şey |
|---|---|---|
| `Api/V2/*`, `Owner/*` | `core-engineering-guard` | Tenant scope, 401/403/404 sınırları |
| `app/Http/Middleware/*` | `core-engineering-guard` | Middleware güvenlik sınırları |
| `app/Services/CRM/*` | `core-engineering-guard` | CRM yetki ve tenant izolasyonu |
| `routes/*.php` | `core-engineering-guard` | Route yetki ve tenant scope |

---

## 🗄️ Schema & Veri

| File Pattern | Required Skill | Kontrol Ettiği Şey |
|---|---|---|
| `database/migrations/*` | `core-engineering-guard` | Kolon şeması, FK, index uyumu |
| `app/Models/*` | `core-engineering-guard` | $fillable drift, canonical alan adları |
| `config/*.php` | `core-engineering-guard` | Config şema uyumu, `env()` yasağı |
| `app/Services/IlanCrudService.php` | `core-engineering-guard` | CRUD yetki + kontrat |
| `Location*`, `tests/*Location*` | `core-engineering-guard` | Location hiyerarşi, orphan FK (location-data-reconciliation skill'ine yönlendir) |

---

## 🤖 AI & Cortex

| File Pattern | Required Skill | Kontrol Ettiği Şey |
|---|---|---|
| `app/Services/Cortex/*` | `core-engineering-guard` | AI routing, token maliyet, LLM çağrıları |
| `app/AI/*` | `core-engineering-guard` | AI prompt, model routing |
| `app/Services/YalihanCortex.php` | `core-engineering-guard` | Cortex orchestration |

---

## 🔌 Entegrasyon & Events

| File Pattern | Required Skill | Kontrol Ettiği Şey |
|---|---|---|
| `app/Events/*` | `core-engineering-guard` | Event sınıfı, payload kontratı |
| `app/Listeners/*` | `core-engineering-guard` | Event listener, n8n webhook |
| `app/Http/Controllers/Api/V2/*Webhook*` | `core-engineering-guard` | Webhook + event koordinasyonu |

---

## 🧪 Test & Quality

| File Pattern | Required Skill | Kontrol Ettiği Şey |
|---|---|---|
| `tests/Feature/Security/*` | `core-engineering-guard` | Güvenlik test coverage |
| `tests/Feature/Ilan/*` | `core-engineering-guard` | İlan schema kontratı |
| `tests/Feature/Wizard/*` | `core-engineering-guard` | Wizard akış kontratı |

---

## 📦 Storage & Media

| File Pattern | Required Skill | Kontrol Ettiği Şey |
|---|---|---|
| `storage/app/public/ilan-fotograflari/` | `media-storage-lifecycle-guardian` | DB-fiziksel dosya çapraz denetimi |
| `app/Services/Ilan/IlanPhotoService.php` | `core-engineering-guard` | Fotoğraf schema, display_order |
| `app/Http/Controllers/Api/V2/*Photo*` | `core-engineering-guard` | Photo upload + yetki |

---

## 🛠️ Kullanım

Agent bir dosyayı değiştirmeden **ÖNCE**:

1. Dosya yolunu yukarıdaki tabloda ara
2. Gerekli skill(leri) `skill()` tool ile yükle
3. Skill rehberine göre kodu gözden geçir
4. Değişikliği uygula

**Örnek:**
```
Agent: app/Http/Controllers/Api/V1/IlanController.php dosyasını açıyor
→ Tablo: core-engineering-guard + api-contract-envelope-guardian gerekli
→ skill('core-engineering-guard') çağır
→ skill('api-contract-envelope-guardian') çağır
→ Kanıt standardı + thin controller + JSON envelope kontrol et
→ Değişikliği uygula
```

---

## Skill Öncelik Sırası

1. **Zorunlu** (`core-engineering-guard`) — tüm PHP/Blade dosyaları
2. **Bağlamsal** (`blade-alpine-runtime-guardian`, `api-contract-envelope-guardian`) — ilgili dosya tiplerinde
3. **Koordinasyon** (`multi-agent-worktree-sandbox`) — Git/worktree operasyonlarında
4. **Stratejik** (`saab`) — mimari karar ve ERA_V dokümanlarında

---

## Skill Özet Kartları

### `core-engineering-guard`
Mimari değişmezler + Laravel kod kalitesi + kanıt standardı + release sınırları

### `blade-alpine-runtime-guardian`
Blade/JS bütünlüğü + Alpine scope + DOM bağlı kütüphane idempotency

### `api-contract-envelope-guardian`
Frontend-backend JSON kontratı + HTTP durum kodu + tenant scope

### `multi-agent-worktree-sandbox`
Git worktree izolasyonu + test DB ayrımı + handoff protokolü

### `saab`
Mimari karar + ERA roadmap + ADRS + stratejik öncelik zinciri
