# Yalıhan Emlak — Sistem Yol Haritası

**Versiyon:** 3.0.0
**Son güncelleme:** 2026-07-04 (Sprint 4.8 CERTIFIED — SC-2026-07-04-0048)
**YSOS:** v1.0 ACTIVE | **SAAB:** v7.0 | **Durum:** YSOS ENGINEERING STANDARD

---

## YSOS Era Başlangıcı (2026-07-03)

**YSOS — Yalıhan Sprint Operating System** resmi olarak devreye girdi.

YSOS = Platform Engineering + Context Engineering + Agent Engineering birleşimi.
Bu proje artık sadece kod değil — **metodoloji** üretiyor.

### YSOS Bileşenleri

| Bileşen | Durum |
|---------|--------|
| YSOS Framework | ✅ ACTIVE |
| SAAB v7 (Governance) | ✅ ACTIVE |
| Sprint Lifecycle | ✅ ACTIVE |
| Quality Gates | ✅ ACTIVE |
| Context Engineering | ✅ ACTIVE |
| Evidence Standard | ✅ ACTIVE |
| Certification Standard | ✅ ACTIVE |
| Handoff Standard | ✅ ACTIVE |
| Sprint Template | ✅ ACTIVE |
| Artisan Commands (design) | 📋 Designed |

---

## Mevcut Durum

Sprint 4.2 tamamlandı. Owner Portal CRUD Lifecycle fonksiyonel.
Sprint 4.3 (AI Workforce Zinciri) planlanıyor.
**Yeni mühendislik standardı:** YSOS v1.0

---

## SPRINT 1 — ✅ TAMAMLANDI (2026-05-10/12)

- [x] FIX-01: Dead `IlanController` import silindi
- [x] FIX-02: Dead commented routes temizlendi
- [x] Mail entegrasyonu: `Mail::to()` aktif
- [x] N8N Webhook: `.env`'de mevcut
- [x] Owner Report migration'ları: `rows` + `metrics` + `exports` tabloları
- [x] GovernanceDecision hash migration
- [x] APP_DEBUG=false + APP_ENV=production
- [x] FieldMCP auth:sanctum+tenant
- [x] SetTenantContext middleware
- [x] CI PHP 8.2 → production match

---

## SPRINT 2 — ✅ TAMAMEN KAPANDI (2026-06-15)

| Görev | Commit | Durum |
|-------|--------|-------|
| #19 YalihanCortex God Object dekompoze | `5004346` | ✅ |
| #28 app/Domains/ → app/Domain/ birleştirme | `6909772` | ✅ |
| #58 DriftDetectionService kanonik seçim | `a8cf352` | ✅ |
| #60 ModuleServiceProvider isim çakışması | `6125ca3` | ✅ |
| #61 yalihan-bekci/ MCP dizin denetimi | `b68a7c9` | ✅ |
| B-006 Deprecated ghost model temizliği | `a947d80c` | ✅ |

---

## SPRINT 3 — ✅ KAPANDI (2026-06-15/16)

### Tamamlanan
- [x] Kisi.php Context7 email→eposta — `6923cf73`
- [x] Ilan.php + Kisi.php pivot aktiflik_durumu fix
- [x] IlanCrudService Split-Brain fix (handleVerticalDetails) — Seçenek A
- [x] MD Audit: 432 → 195 dosya, docs/ kökü 12 SSOT'a indirildi
- [x] PROGRESS-TRACKER kırık referanslar temizlendi
- [x] known-debt.md 35 maddeye güncellendi

---

## SPRINT 4 — 🔄 FAZ 2 ÜRÜN AŞAMASI + ERA III (2026-06-25 → )

Risk: HIGH. ADR + tam test coverage şart.

| # | Görev | Durum |
|---|-------|-------|
| Sprint 4.0 | Reliability Hardening | ✅ KAPANDI |
| Sprint 4.1 | Alpine.js UI Stabilization | ✅ KAPANDI |
| Sprint 4.2 | Real CRUD Certification | ✅ KAPANDI |
| Sprint 4.3 | AI Workforce Zinciri | ⏳ Planlanıyor |
| **Sprint 4.6** | **Property Digital Twin Cockpit** | ✅ KAPANDI |
| **Sprint 4.7** | **Workspace Execution Engine** | ✅ KAPANDI |
| Sprint 4.8 | Workspace Integrations (Drive Sync) | ✅ CERTIFIED (SC-2026-07-04-0048) |
| Sprint 4.9 | Capability Activation Platform | ⏳ Planlanıyor |

---

## ✅ SPRINT 4.6 — PROPERTY DIGITAL TWIN COCKPIT (2026-07-04) ✅

SAAB Board Resolution: Property Digital Twin Cockpit APPROVED.
SC-2026-07-04-0046-REV2: PRODUCTION CERTIFIED.

**Mission:** Build the first production-grade Property Digital Twin Cockpit.

### In Scope
- Workspace Dashboard (`/admin/workspace/{id}`)
- Timeline Component (Hermes event history)
- Health Score Component
- Workspace Metrics/Summary/Events API endpoints
- Dashboard Tests
- Execution Monitor panel
- Finance + Reservations + Documents panels

### Out of Scope
- Telegram, Async Queue, Drive Sync, New AI Agents

---

## ✅ SPRINT 4.7 — WORKSPACE EXECUTION ENGINE (2026-07-04) ✅

SAAB Board Resolution: SC-2026-07-04-0047 — CERTIFIED.

**Mission:** Transform Workspace from Operational Cockpit into Operational Execution Engine.

### In Scope
- WorkspaceExecution model (8-state)
- Execution Queue + Background Job
- ReplayService (idempotent)
- RetryService (exponential backoff)
- Execution Monitor in cockpit
- Execution REST API (7 endpoints)
- Tenant isolation

### Architecture
```
Workspace → Execution Record → Queue → Worker → Agent → Timeline → Cockpit
```

---

## ✅ SPRINT 4.8 — WORKSPACE INTEGRATIONS (2026-07-04) ✅

SAAB Board: SC-2026-07-04-0048 — CERTIFIED.

### In Scope
- Google Drive Workspace Integration
- Drive file change detection
- Drive events → Workspace Timeline
- Document lifecycle management
- DriveWebhookService (register/renew/stop/validate/process)
- Webhook Route: POST /api/webhook/drive
- Channel metadata persistence (drive_webhook_channel_json)
- File metadata persistence (metadata_json)
- Integration Health panel in cockpit
- DriveAgent auto-register on workspace provisioning
- Scheduler command: drive:renew-channels (daily 06:00)

### Out of Scope
- Telegram, New AI Agents

### Board Operational Prerequisites (Production deployment)
```bash
# 1. Migration — channel + metadata kolonları
php artisan migrate

# 2. Scheduler renewal — otomatik channel yenileme
# Kernel.php'ye eklendi: drive:renew-channels --force (daily 06:00)

# 3. Google Cloud Pub/Sub — webhook push URL
# Subscription push: https://yalihan.ai/api/webhook/drive

# 4. .env webhook secret
GOOGLE_DRIVE_WEBHOOK_SECRET=<random_32_char>
```

---

## ⏳ SPRINT 4.9 — CAPABILITY ACTIVATION PLATFORM (Planlanıyor)

**SAAB Board Tavsiyesi (SC-2026-07-04-0048):**
> "Bu noktadan sonra Sprint 4.9'un odağı teknik entegrasyondan ziyade Capability Activation ve sistemde mevcut yeteneklerin eksiksiz şekilde kullanılabilir hale getirilmesi olmalıdır."

### Focus Areas
- Orphan controller route registration (14 kritik controller)
- Mevcut API endpoint'lerin eksik endpoint'lerle tamamlanması
- Pre-existing technical debt (45 SAB violations — Ilan.php naming)
- Sistem yeteneklerinin eksiksiz aktivasyonu

### Out of Scope
- Yeni AI ajanlar
- Yeni entegrasyonlar (Drive/Telegram dışında)

---

## ⏳ ERA IV — AUTONOMOUS OPERATIONS

**ERA IV Directive (SC-2026-07-04-ERA-IV):**
> "Yeni soru: Workspace kendi başına iş tamamlayabiliyor mu?"

**ERA III tamamlandı:** Observation + Execution + Integration katmanları ayrıldı.
**ERA IV hedefi:** Workspace'in kendi başına tam iş çalıştırabilmesi.

### ERA III — COMPLETED (2026-07-04) ✅

**EC-2026-07-04-0001 — ERA III CERTIFIED**

| Sprint | Katman | Status |
|--------|--------|--------|
| Sprint 4.6 | Observation Layer | ✅ Certified |
| Sprint 4.7 | Execution Layer | ✅ Certified |
| Sprint 4.8 | Integration Layer | ✅ Certified |

**Mimari:**
```
Workspace
  ├─ Observation → Cockpit, Health, Timeline
  ├─ Execution → Queue, Replay, Retry
  └─ Integration → Drive Webhook, Metadata, Files
```

---

## ⏳ PRR Sprint 4.9 — Production Readiness Review (Önkoşul)

**Board Acknowledgement:** EC-2026-07-04-0002 — ERA III OFFICIALLY CLOSED
**Next Phase:** ERA IV — Autonomous Operations
**Sprint 4.9 Authorized:** Production Readiness Review

### PRR Kapsamı

| Alan | Kontrol |
|------|---------|
| Route Coverage | Tüm controller method'larının route karşılığı var mı? |
| Orphan Controllers | 14 kritik + 6 duplicate çözüldü mü? |
| Queue Health | Retry/Replay mekanizması doğru çalışıyor mu? |
| Webhook Health | Channel renewal, error handling, retry |
| Replay Tests | Başarısız execution'lar yeniden oynatılabiliyor mu? |
| Security Review | Tenant isolation, webhook auth, rate limiting |
| Tenant Isolation | Tüm query'ler tenant scope içeriyor mu? |
| Backup / Restore | Migration ve seed'ler idempotent mi? |
| Monitoring | Health metrics, alert thresholds |
| Deployment Checklist | Hetzner'a deploy için kontrol listesi |

### PRR Çıktısı
- `docs/sprints/SPRINT_PRR_4_9/` — PRR Sprint dokümantasyonu
- Technical debt quantified (SAB violations, orphan controllers)
- Deployment checklist
- Go/no-go for Sprint 5.0

---

## ⏳ Sprint 4.9 — Capability Activation Platform

**Board Directive (SC-2026-07-04-0048):**
> "Sprint 4.9'un odağı teknik entegrasyondan ziyade mevcut yeteneklerin eksiksiz şekilde kullanılabilir hale getirilmesidir."

### Yetenek Aktivasyon Öncelikleri

| # | Yetenek | Durum | Eylem |
|---|---------|-------|-------|
| 1 | Route Coverage | ~65% | Orphan controller route'larını tamamla |
| 2 | Orphan Controllers | 14 kritik | Register veya arşivle |
| 3 | Duplicate Controllers | 6 çift | Birleştir veya eskiyi sil |
| 4 | SAB Violations | 45 pre-existing | Naming Authority düzelt |
| 5 | Replay Tests | Eksik | ReplayService test yaz |
| 6 | Webhook Health | Board önkoşulu | `drive:renew-channels` test et |
| 7 | API Contract Registry | Yok | Tüm endpoint kontratlarını belgele |
| 8 | Capability Registry | Yok | Workspace cockpit yeteneklerini kataloga gir |

### ERA IV Hedef Durum (Sprint 4.9 Sonu)
```
Workspace
  ├─ Cockpit → 16/16 panel çalışıyor
  ├─ Queue → Tüm agent'lar idempotent replay destekliyor
  ├─ Drive → Webhook channel renew + file sync çalışıyor
  ├─ API → Tüm endpoint route'lı, test edilmiş
  └─ Güvenlik → Tenant isolation + webhook auth doğrulanmış
```

### Out of Scope
- Yeni AI ajanlar
- Yeni entegrasyonlar (Drive dışında)

---

## SPRINT 4.2 — ✅ KAPANDI (2026-07-03)

**YSOS Sprint Standardı ilk uygulama.**

| Metric | Pre-Sprint | Post-Sprint |
|--------|-------------|-------------|
| OwnerIlanCrudTest | 9/20 pass | **12/15 pass** |
| Regression | — | **0 new failures** |
| Controller methods missing | 4 | **0** |
| Blade enum TypeError | 3 files | **0** |

**Değişiklikler:**
- `ucfirst()` → `->label()` (3 blade dosyası)
- `edit()`, `update()`, `destroy()`, `readiness()` eklendi
- Route model binding `{ilan}` aktif
- `IlanPolicy::update()` ownership fix

---

## Sprint Roadmap — ERA III & Beyond

```
Sprint 4.6 → Property Digital Twin Cockpit     [✅ CERTIFIED]
Sprint 4.7 → Async Queue + Event Replay      [✅ CERTIFIED]
Sprint 4.8 → Google Drive Integration        [✅ CERTIFIED — ERA III COMPLETE]
Sprint 4.9 → Capability Activation Platform  [⏳ Planlanıyor]
Sprint 5.0 → İlk Canlı Müşteri Pilotu     [⏳ Planlanıyor]
```

---

## Sprint 4.7 — Async Queue + Event Replay (Planlanıyor)

**Mission:** Event Replay Engine + Queue reliability

### In Scope
- Async event processing reliability
- Dead Letter Queue (DLQ) replay
- Event idempotency verification
- Queue monitoring

### Out of Scope
- Telegram
- Drive integration

---

## Sprint 4.8 — Google Drive & Docs Integration (Planlanıyor)

**Mission:** Real document sync with Google Drive

### In Scope
- Drive OAuth integration
- Auto-upload property documents
- Title deed → Drive
- Energy certificate → Drive

### Out of Scope
- Telegram
- Event Replay

---

## Sprint 4.9 — Telegram Production (Planlanıyor)

**Mission:** Full Telegram bot in production

### In Scope
- Telegram bot webhook
- Notification pipeline
- Agent status alerts
- Real-time updates

### Out of Scope
- Drive integration
- Event Replay

---

## Sprint 5.0 — İlk Canlı Müşteri Pilotu

**Mission:** First real user in production

### In Scope
- Production deployment
- Real user onboarding
- Feedback collection
- Sprint 5.1 backlog planning

---

## TEKNİK BORÇ

| # | Görev | Risk | Öncelik |
|---|-------|------|---------|
| #20-25 | Hetzner deploy (SSH bloker çözümü zorunlu) | 🔴 | P0 |
| T-UPS-V2-FULL | JSONB tam göçü (`ekstra_ozellikler` migration + 3 servis) | 🔴 | P1 |
| T-FAV-01 | `ilan_favorileri.user_id` vs pivot `kisi_id` FK uyumsuzluğu | 🟠 | P2 |
| FIX-06 | `AIController` CRM methods → `AICrmGatewayService` | 🟠 | P2 |
| FIX-07 | `PropertyHubController` AI methods → `PropertyAIService` | 🟠 | P2 |
| FIX-11 | `PropertyHubController` (28 method) → 4 controller | 🟠 | P3 |
| FIX-12 | `DecisionEngineController` (27 method) → 4 controller | 🟠 | P3 |
| #16 | FinanceProcessor OpenAI bağımlılığı kaldır | 🟡 | P3 |
| #17 | PortfolioProcessor whereBetween → Haversine | 🟡 | P3 |
| #18 | `yayin_durumu` 6 farklı string standardizasyonu | 🟡 | P3 |
| #26 | `bekci:pattern:sync` komutu | 🟡 | P4 |

---

## SPRINT 5+ — Mimari Olgunluk

Risk: VERY HIGH. Her iş ayrı ADR + tam test coverage şart.

### Dual Sistem Konsolidasyonu
- [ ] CRM V1 (`Musteri`) + V2 (`CRM\*`) → tek model (ayrı migration sprint)
- [ ] Finance modül çakışması: `FinansalIslem` vs `Finance\*`
- [ ] Yanlış namespace servisler: 4 Advisor + 2 CRM → doğru konuma taşı

### Template SSOT + Namespace Migration
- [ ] FIX-17: 11 controller → tek template hiyerarşisi (ADR gerekli)
- [ ] FIX-18: 14 controller Api/Admin namespace migration

---

## Kritik Kurallar (Asla Dokunma)

| Sınıf | Kural |
|-------|-------|
| [`IlanCrudService::store()`](../app/Services/Ilan/IlanCrudService.php) | DOKUNMA — tek write authority |
| [`StoreIlanRequest`](../app/Http/Requests/Ilan/StoreIlanRequest.php) | YÜKSEK RİSK |
| [`ListingStateMachine`](../app/StateMachines/ListingStateMachine.php) | BYPASS YASAK |
| `FeatureTemplateResolver` (Ups\) | SSOT koru |
| `.sab/authority.json` | Agent değiştiremez |

---

## Deploy Checklist (#20-25)

```bash
# Sunucu: Hetzner CX33 — 157.180.116.63
ssh ubuntu@157.180.116.63
# #20: PHP 8.2 + Nginx + MySQL + Redis + Supervisor
# #21: rsync ile Laravel gönder
# #22: composer install --no-dev && php artisan migrate --force && php artisan config:cache
# #23: Nginx config + Cloudflare Tunnel (panel subdomain)
# #24: supervisor + php artisan horizon:start
# #25: php artisan telegram:set-webhook
```

**N8N:** https://n8n.yalihanemlak.com.tr ✅ aktif
**Panel:** https://panel.yalihanemlak.com.tr (deploy bekliyor)

---

*Son güncelleme: 2026-06-16 | Bekçi herzaman uyanık.*
