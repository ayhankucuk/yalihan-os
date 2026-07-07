# Sprint 4.9 — Production Readiness Review (PRR)
## SAAB v7 APPROVED | EC-2026-07-04-0002

**⚠️ Bu sprint sadece kanıt toplar. Kod yazmadan önce mevcut durum belgelenir.**

---

## Quality Gates — Mevcut Kanıt (Oturum 73)

### test — Kanıt Tarih: 2026-07-04

```
Status: 3 FAILED / 12 PASSED
Duration: 9.84s
```

> 📋 OwnerIlanCrudTest: 3 hata var. SQLite `yazlik_details.deleted_at` farkı — Sprint 4.2'den beri biliniyor. Board kararı: Sprint 4.9 kapsamı dışında.
> 📋 165 test dosyası mevcut
> 📋 342 controller dosyası
> 📋 ~1665 route kayıtlı

**Kanıt:** `php artisan test` çıktısı

### sab:integrity-scan — Kanıt Tarih: 2026-07-04

```
45 ihlal — 0 Sprint 4.8 dosyasından
Tüm ihlaller: Ilan.php Naming Authority + SilentCatch
```

> Board Rule 1: Kanıtsız PASS yasaktır. 45 FAIL mevcut. Düzeltme: Ilan.php `is_active` + `active` → `aktiflik_durumu`

### bekci:health — Kanıt Tarih: 2026-07-04

```
MCP Server: ⚠️ OFFLINE
Knowledge Base: ✅ 41 learning entries (100%)
Learning Activity: ✅ 40 learning actions (100%)
App Runtime: ✅ Tüm sistemler çalışıyor (100%)
Project Health: ⚠️ 59.25%
────────────────────────────────────────
TOPLAM: 68.89% — GOOD
```

### Route:list — Kanıt Tarih: 2026-07-04

```
Toplam route: 1665
Orphan controllers: 14 kritik
```

### Capability kanıt — Tarih: 2026-07-04

```
DriveAgent ✅ | DescriptionAgent ✅ | PhotoAgent ✅ | NotificationAgent ✅
PortfolioAgent ✅ | ProcessWorkspaceExecutionJob ✅ | ReplayService ✅
```

### Replay/Retry — Kanıt

```
ReplayService ✅ (app/Services/Workspace/ReplayService.php)
ProcessWorkspaceExecutionJob ✅ (app/Jobs/Workspace/ProcessWorkspaceExecutionJob.php)
WorkspaceExecution Model ✅ (app/Models/WorkspaceExecution.php)
```

### Webhook — Kanıt

```
POST /api/v1/webhook/drive ✅ (Sprint 4.8)
WhatsApp ✅ | Instagram ✅ | Facebook ✅
n8n webhook ✅ | Telegram ✅
```

---

## ⚠️ Bilinen Kanıtlanmış FAIL Durumlar

| Durum | Kanıt | Kaynak | Planlanan Eylem |
|-------|--------|--------|-----------------|
| 3 test FAIL | OwnerIlanCrudTest | SQLite softDeletes farkı | Sprint 4.2'den beri biliniyor |
| 45 SAB ihlal | sab:integrity-scan | Ilan.php Naming Authority | Sprint 4.9 kapsamı dışında |
| MCP offline | bekci:health | MCP server erişilemiyor | Operasyonel sorun değil |
| 14 orphan controller | Controller analizi | Kod yazıldı, route'sız | Sprint 4.9 + Sprint 4.8 Route Audit kapsamı |

> Rule 1: Hiçbir FAIL gizlenmeyecek. Hiçbir PASS kanıtsız verilmeyecek.

**Date:** 2026-07-04
**Mission:** YALIHAN OS'nin üretim ortamına güvenle çıkabilecek olgunlukta olduğunu kanıtlamak.

---

## Board Directive

> "Bu sprintin amacı: YALIHAN OS'nin gerçek üretim ortamına güvenle çıkabilecek olgunlukta olduğunu kanıtlamak."

**Değişen soru:**
- Eski: "Workspace çalışıyor mu?"
- Yeni: "Workspace kendi başına iş tamamlayabiliyor mu?"

**Bu sprintin çıktısı:** Production Readiness Report
| Metric | Hedef |
|--------|-------|
| Route Coverage | %100 |
| Capability Coverage | %100 |
| Queue Health | PASS |
| Webhook Health | PASS |
| Replay | PASS |
| Backup | PASS |
| Restore | PASS |
| Monitoring | PASS |
| Deployment | PASS |
| **GO / NO-GO** | **GO** |

---

## P0 — Route Coverage

### Hedef
- Controller → Route eşleşmesi **%100**
- Orphan Controller = **0 kritik**
- Tüm public method'ların endpoint karşılığı var

### Controller Audit Grid

| Controller | Public Methods | Routes | Status |
|------------|---------------|---------|---------|
| Api/TelegramWebhookController | handleWebhook, test | 0 | → Register veya archive |
| Api/N8nWebhookController | 7 methods | 0 | → Archive |
| Api/AkilliCevreAnaliziController | 4 | 0 | → Register veya archive |
| Api/ImageAIController | 4 | 0 | → Register veya archive |
| Api/SeasonController | 5 | 0 | → Register veya archive |
| Api/ReferenceController | 6 | 0 | → Register veya archive |
| Api/KisiCRMController | 6 | 0 | → Register veya archive |
| Api/EventController | 5 | 0 | → Register veya archive |
| Api/ListingSearchController | 4 | 0 | → Register veya archive |
| Api/PropertyFeatureSuggestionController | 3 | 0 | → Register veya archive |
| Api/Context7Controller | 1 | 0 | → Register |
| Admin/IlanPhotoController | 3 | 0 | → Register |
| Admin/DescriptionDraftController | 5 | 0 | → Register |
| Api/DriveWebhookController | handle | ✅ | Register (Sprint 4.8) |

### Route Coverage Hedef
```
Şu an: ~65%
Hedef: %100
Gap: 14 kritik orphan controller
```

---

## P0 — Capability Audit

Her capability için 6 boyut doğrulanacak:

| Boyut | Ne | Nasıl |
|-------|----|-------|
| Route | Endpoint var mı? | `route:list` |
| Event | Hermes event üretiyor mu? | `HermesEventLog` sorgusu |
| Queue | Async job'a sahip mi? | `ProcessWorkspaceExecutionJob` trace |
| Webhook | Harici bildirim alıyor mu? | Webhook controller + channel registration |
| Permission | Yetkilendirme var mı? | Policy + middleware |
| Owner | Kimin sorumluluğunda? | Agent ataması |

### Capability Kartları (6 Boyut × Owner)

| Capability | Route | Event | Queue | Webhook | Owner | Sorumlu |
|-----------|-------|-------|-------|---------|--------|---------|
| Workspace Cockpit | ✅ | ✅ | ✅ | ✅ | DriveAgent | AdminController |
| Drive Sync | ✅ | ✅ | ✅ | ✅ | DriveAgent | DriveWorkspaceService |
| Replay Engine | ✅ | ✅ | ✅ | ❌ | ReplayService | WorkforceAgent |
| Telegram Bot | ❌ | ✅ | ❌ | ✅ | TelegramService | TakimYonetimiModule |
| AI Description | ✅ | ✅ | ✅ | ❌ | DescriptionAgent | AIWorkspace |
| Ilan CRUD | ✅ | ✅ | ✅ | ❌ | IlanCrudService | Admin |
| Kisi CRM | ✅ | ✅ | ❌ | ❌ | KisiService | Admin |
| AI Photo | ✅ | ✅ | ✅ | ❌ | PhotoAgent | AIWorkspace |
| OAuth Token | ❌ | ❌ | ❌ | ✅ | DriveWorkspaceService | Backend |
| n8n Integration | ✅ | ✅ | ✅ | ✅ | N8nAdapter | Backend |

> **Board Görüşü:** "Hiçbir üretim kapasitesi sahipsiz kalmamalı." — her capability'nin bir owner'ı ve sorumlusu olmalı.

---

## P0 — Replay Verification

### Test Suite

| Test | Kapsam | Hedef |
|------|--------|-------|
| ReplayService_replay_createsNewExecution | `ReplayService` yeni kayıt oluşturuyor mu? | PASS |
| ReplayService_replayNeverMutatesOriginal | Orijinal kayıt değişmiyor mu? | PASS |
| RetryService_exponentialBackoff | Exponential backoff doğru mu? | PASS |
| RetryService_maxAttemptsExceeded | Max attempts aşılınca duruyor mu? | PASS |
| QueueRecovery_jobRetriesOnFailure | Job başarısız olunca retry ediyor mu? | PASS |
| QueueRecovery_dlqOnPermanentFailure | Kalıcı hatalarda DLQ'ya gidiyor mu? | PASS |
| FailureSimulation_replaySuccess | Simüle hata → replay → başarı | PASS |

### Simülasyon Senaryoları
1. `DriveAgent` başarısız → 3 retry → DLQ
2. DLQ'dan replay → yeni execution → başarı
3. `hermes:replay {logId}` → event log'ı okuyup tekrar çalıştırıyor

---

## P0 — Webhook Health

### Her webhook için

| Webhook | Endpoint | Channel Registration | Renewal Scheduler | Error Handling |
|---------|----------|--------------------|-----------------|---------------|
| Google Drive | `POST /api/webhook/drive` | ✅ `drive:renew-channels` | ✅ daily 06:00 | ✅ `try/catch + Log` |
| WhatsApp | `POST /api/webhook/whatsapp` | Manuel | Manuel | ✅ |
| Instagram | `POST /api/webhook/instagram` | Manuel | Manuel | ✅ |
| Facebook | `POST /api/webhook/facebook` | Manuel | Manuel | ✅ |
| Telegram | `POST /api/telegram/webhook` | Manuel | Manuel | ✅ |
| n8n | `POST /api/webhook/n8n/*` | Manuel | Manuel | ✅ |
| Airbnb | — | ❌ Planned P1 | ❌ | ❌ |
| Booking | — | ❌ Planned P1 | ❌ | ❌ |

### Health Doğrulama
```bash
php artisan drive:renew-channels --force
# Tüm channel'lar yenileniyor → PASS/FAIL

# Webhook endpoint manual test
curl -X POST https://yalihan.ai/api/webhook/drive \
  -H "Content-Type: application/json" \
  -d '{"message": {"data": "..."}}'
# → HermesEventLog yazılıyor → PASS
```

---

## P0 — Production Checklist

### Environment & Secrets
```bash
[ ] APP_ENV=production
[ ] APP_DEBUG=false
[ ] APP_KEY base64:32
[ ] DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD
[ ] REDIS_HOST, REDIS_PASSWORD
[ ] GOOGLE_DRIVE_WEBHOOK_SECRET=<random_32>
[ ] AI_STORAGE_* credentials
[ ] TELEGRAM_BOT_TOKEN
```

### Queue & Scheduler
```bash
[ ] php artisan queue:restart  # Cron'da çalışıyor mu?
[ ] Supervisor/PM2 process manager yapılandırıldı mı?
[ ] php artisan schedule:run  # Scheduler cron entry mevcut mu?
```

### Monitoring
```bash
[ ] bekci:health çalışıyor mu? (%60+ hedef)
[ ] bekci:health --detailed tüm bileşenler yeşil mi?
[ ] Disk space, RAM, CPU alert thresholds tanımlı mı?
```

### Backup & Restore
```bash
[ ] mysqldumpcron mevcut mu?
[ ] Backup → yeni sunucuda restore test edildi mi?
[ ] Migration idempotent mi? (up + down)
```

### Rollback Test
```bash
# Deploy sonrası — başarısız deployment senaryosu simüle ediliyor
git revert HEAD
php artisan migrate:rollback --step=1
php artisan cache:clear
php artisan route:clear
php artisan config:clear
php artisan queue:restart

# Tekrar deploy
git checkout main
php artisan migrate --force
php artisan cache:clear
php artisan queue:restart
curl -s http://localhost/health | jq '.status'
# → "ok" olmalı
```

### Secret Rotation
```bash
[ ] Google OAuth Client Secret — yenilenebilir mi?
[ ] Telegram Bot Token — rotate edilebiliyor mu?
[ ] n8n Webhook Secret — değiştirilebiliyor mu?
[ ] Cloudflare API Token — yenilenebilir mi?
[ ] GitHub Personal Access Token — expiry var mı?
[ ] Tüm secret'lar .env değil config() üzerinden yönetiliyor mu?
[ ] Production secret'lar env'de değil secrets manager'da mı?
```

### Smoke Tests
```bash
# Deploy sonrası otomatik smoke test
curl -f https://yalihan.ai/admin/workspace/1     → 200
curl -f https://yalihan.ai/api/webhook/drive -X POST -d '{}' → 200
curl -f https://yalihan.ai/api/ilanlar            → 200
php artisan bekci:health --json | jq '.health'   → >60%
```

---

## P0 — Hetzner Deployment Review

### Sunucu Durumu

| Bileşen | Durum |
|---------|-------|
| Nginx | ✅ |
| PHP 8.5 | ✅ |
| MySQL 8.4 | ✅ |
| Redis | ✅ |
| Composer | ✅ |

### Deployment Adımları

```bash
# 1. Kod deploy
git pull origin main

# 2. Bağımlılıklar
composer install --optimize-autoloader --no-dev

# 3. Migration
php artisan migrate --force

# 4. Config cache
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Queue worker restart
php artisan queue:restart

# 6. Scheduler cron
* * * * * cd /var/www/yalihan && php artisan schedule:run >> /dev/null 2>&1

# 7. Health doğrulama
php artisan bekci:health
php artisan sab:integrity-scan

# 8. Smoke test
curl https://yalihan.ai/admin/workspace/1
curl https://yalihan.ai/api/webhook/drive -X POST -d '{}'
```

### Cloudflare + SSL
```bash
[ ] DNS A record → 157.180.116.63
[ ] SSL sertifikası aktif (Let's Encrypt veya manuel)
[ ] Cloudflare proxy aktif
[ ] www → non-www redirect
```

### Monitoring Agent
```bash
[ ] Prometheus/Netdata agent kurulu mu?
[ ] CPU/RAM/Disk alert mevcut mu?
[ ] Log rotation (logrotate) yapılandırıldı mı?
```

---

## Sprint Çıktısı: Production Readiness Report

### Rapor Şablonu

```
══════════════════════════════════════════════════════════════
PRODUCTION READINESS REPORT
Tarih: YYYY-MM-DD
Sistem: YALIHAN OS
══════════════════════════════════════════════════════

ROUTE COVERAGE     ████████████ 100%  ✅
CAPABILITY AUDIT   ████████████ 100%  ✅
REPLAY TESTS       ████████████ 100%  ✅
QUEUE HEALTH       ████████████ PASS  ✅
WEBHOOK HEALTH     ████████████ PASS  ✅
BACKUP             ████████████ PASS  ✅
RESTORE            ████████████ PASS  ✅
MONITORING         ████████████ PASS  ✅
DEPLOYMENT         ████████████ PASS  ✅
SECRET ROTATION    ████████████ PASS  ✅  ← Board EK
ROLLBACK TEST      ████████████ PASS  ✅  ← Board EK
SMOKE TESTS        ████████████ PASS  ✅  ← Board EK

══════════════════════════════════════════════════════
BOARD DECISION
GO / NO-GO           ✅ GO
══════════════════════════════════════════════════════
```

---

## DoD

Sprint 4.9 sadece aşağıdakiler tamamlandığında kapanacaktır:

| # | Item | Status |
|---|------|--------|
| 1 | Route Coverage %100, Orphan = 0 | ⏳ |
| 2 | Capability Audit %100 + Owner Matrix | ⏳ |
| 3 | Replay Test Suite PASS | ⏳ |
| 4 | Queue Recovery PASS | ⏳ |
| 5 | Webhook Health PASS | ⏳ |
| 6 | Production Checklist PASS | ⏳ |
| 7 | Hetzner Deployment PASS | ⏳ |
| 8 | **Secret Rotation Verification** | ⏳ |  ← Board EK |
| 9 | **Rollback Test PASS** | ⏳ |  ← Board EK |
| 10 | **Smoke Tests PASS** | ⏳ |  ← Board EK |
| 11 | Production Readiness Report → GO | ⏳ |

---

## Out of Scope
- Yeni AI ajanlar
- Yeni entegrasyonlar (Drive/Telegram dışında)
- Yeni modeller

## Board Sign-Off

| Role | Status |
|------|--------|
| Architecture | ⏳ |
| Development | ⏳ |
| QA | ⏳ |
| Operations | ⏳ |
| Product | ⏳ |
| Security | ⏳ |

**GO for Sprint 5.0:** ⏳ Pending Board Review
