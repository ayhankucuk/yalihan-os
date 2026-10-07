# DOMAIN KNOWLEDGE: QUEUE & JOBS

**LAST_VERIFIED_HEAD:** a7aa6532
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: Queue & Jobs

**Tanım:**
- Asenkron işlem yönetimi
- Zamanlanmış görevler (Scheduler)
- Arka plan işlemleri

**SOURCE:** app/Jobs/, app/Console/Kernel.php

---

## Job Directories (37 adet)

| Category | Jobs |
|---------|------|
| AI | AI translation, optimization |
| Cortex | AI analysis |
| Hermes | Async event handling |
| ChannelManager | Channel sync |
| Concierge | Guest services |
| Archive | Data archival |
| Location | Location services |
| NotifyN8n | n8n webhook bildirimleri |

---

## Job Types

| Type | Description | Evidence |
|------|-------------|----------|
| AsyncJob | Queue'da çalışır | Laravel default |
| DispatchJob | Job dispatch | Hermes async handling |
| CommandJob | CLI command | Laravel |

---

## Queue Configuration

**Driver:** Database (default)

**Queue Names:**
- default
- notifications
- ai-processing

---

## Scheduled Commands (Kernel.php)

| Command | Schedule | Purpose |
|---------|----------|---------|
| exchange:update | scheduled | Döviz kuru güncelleme |
| drive:renew-channels | scheduled | Google Drive yenileme |
| quality:gate | scheduled | Kalite kontrolü |
| gorevler:check-deadlines | daily | Görev deadline kontrolü |
| cortex:hunt | scheduled | AI analiz |
| queue:check-worker | scheduled | Queue worker sağlık |
| bekci:mcp-audit | scheduled | MCPAudit |
| ai:optimize-thresholds | weekly | AI threshold optimizasyonu |
| ai:recompute-provider-profiles | weekly | AI profil güncelleme |
| ai:data-hygiene | scheduled | Veri temizliği |
| telemetry:detect-anomalies | scheduled | Anomali tespiti |
| ranking:recalculate-all | scheduled | Sıralama yeniden hesaplama |
| rental:sync-airbnb | scheduled | Airbnb senkronizasyonu |
| channex:sync-revisions | scheduled | Channex senkronizasyonu |

---

## n8n Webhook Notifications

| Job | Trigger | Target |
|-----|---------|--------|
| NotifyN8nAboutGorevDeadlineYaklasiyor | Görev deadline | n8n webhook |
| NotifyN8nAboutGorevDurumChanged | Görev durum değişikliği | n8n webhook |
| NotifyN8nAboutGorevGecikti | Görev gecikti | n8n webhook |
| NotifyN8nAboutIlanPriceChange | İlan fiyat değişikliği | n8n webhook |
| NotifyN8nAboutNewGorev | Yeni görev | n8n webhook |

---

## Queue Worker Health

**Command:** `queue:check-worker`
**Schedule:** scheduled

**Purpose:** Queue worker process sağlık kontrolü

---

## Hermes Async Integration

| Job | Purpose | Evidence |
|-----|---------|----------|
| AsyncHandlerDispatchJob | Hermes event async | app/Jobs/Hermes/AsyncHandlerDispatchJob.php |

---

## TENANT ISOLATION

| Check | Status | Evidence |
|-------|--------|----------|
| Tenant-scoped jobs | UNKNOWN | Not verified |

---

## Related Domains

| Domain | Connection |
|--------|------------|
| Hermes | Async event handling |
| AI | AI processing jobs |
| n8n | Webhook notifications |
| Reservation | Notification dispatch |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Scheduled task reliability | SCHEDULED_TASK_RELIABILITY_VERIFICATION | VERIFIED_PASS |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Queue failure handling? | Not documented |
| Job retry policy? | Not documented |
| Production worker status? | Not verified |
| Job timeout configuration? | Not documented |
