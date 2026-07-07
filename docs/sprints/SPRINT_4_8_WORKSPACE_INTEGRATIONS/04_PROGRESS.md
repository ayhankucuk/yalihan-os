# Sprint 4.8 — Workspace Integration Platform
## Progress Report — 2026-07-04 (Sprint 4.8-R1)

---

## SAAB v7 Board Decision

**Sprint ID:** S4.8-R1
**Status:** ✅ BOARD APPROVED (Resume)
**Mission:** Workspace'i Google Workspace ile yaşayan Property Digital Twin haline getirmek.

**Board Finding (Pre-Resume):**
- Drive Folder Provisioning → PASS
- Drive Sync → PASS
- Template Engine → PASS
- DriveWebhook Controller → PASS
- Workspace Integration → PARTIAL
- Timeline Integration → FAIL
- Webhook Registration → FAIL
- Metadata Synchronization → PARTIAL

**Tamamlanma:** ~40%

---

## P0 Deliverables — Tamamlanan İşler

### P0.1 ✅ DriveWebhookService
**Dosya:** `app/Services/Drive/DriveWebhookService.php`

API:
```php
registerChannel(PortfolioDriveWorkspace $ws, string $callbackUrl): array
renewChannel(PortfolioDriveWorkspace $ws): array
stopChannel(PortfolioDriveWorkspace $ws): array
validateNotification(array $payload, ?string $token, ?string $channelId): array
processChanges(PortfolioDriveWorkspace $ws): array
workspacesNeedingRenewal(): array
```

Flow:
```
registerChannel()
  → POST /files/{folderId}/watch (Drive API)
  → persistChannel() → workspace.drive_webhook_channel_json
  → return channel metadata

renewChannel()
  → PATCH /files/{folderId}/watch (or re-register on failure)
  → update stored expiration

validateNotification()
  → resolveWorkspaceByChannelId()
  → verify HMAC token
  → auto-renew if expired

processChanges()
  → DriveSyncService::getChanges()
  → shouldProcessChange() (filter folders, hidden)
  → emitDriveEvent() → HermesEventLog
  → persistFileMetadata() → metadata_json
```

---

### P0.2 ✅ Route Registration
**Dosya:** `routes/api.php`

```php
POST /api/webhook/drive  →  DriveWebhookController::handle()
```

**DriveWebhookController** (`app/Http/Controllers/Api/DriveWebhookController.php`):
- `DriveWebhookService` + `DriveSyncService` injected
- Token validation via `validateNotification()`
- Workspace resolution → tenant context set
- `processChanges()` → Hermes events
- Returns: `{ ok: true, processed: N }`

---

### P0.3 ✅ Timeline Integration
**Dosya:** `app/Services/Workspace/WorkspaceTimelineService.php`

`EVENT_LABELS` dizisine eklenen Drive event'leri:
```php
'drive.folder.created'           → 'Drive Klasörü Oluşturuldu'
'drive.sync.outbound'            → 'Drive Dosya Yüklendi'
'drive.sync.inbound'             → 'Drive Dosya Değişti'
'drive.webhook.received'        → 'Drive Bildirimi Alındı'
'drive.file.created'             → 'Drive Dosyası Oluşturuldu'
'drive.file.updated'             → 'Drive Dosyası Güncellendi'
'drive.file.deleted'             → 'Drive Dosyası Silindi'
'drive.file.changed'             → 'Drive Değişikliği'
'drive.file.sheet_updated'       → 'Google Sheet Güncellendi'
'drive.file.doc_updated'        → 'Google Doc Güncellendi'
'drive.file.slide_updated'      → 'Google Slides Güncellendi'
'drive.channel.registered'       → 'Drive Webhook Bağlandı'
'drive.channel.renewed'          → 'Drive Webhook Yenilendi'
'drive.channel.stopped'          → 'Drive Webhook Kaldırıldı'
```

**DriveAgent** (`app/Services/Hermes/Handlers/Workforce/DriveAgent.php`):
- `DriveWebhookService` inject edildi
- Step 6: `registerChannel()` → workspace provisioning sonrası webhook otomatik kayıt
- `output_payload` → `webhook_channel_registered` + `channel_id`

---

### P0.4 ✅ Metadata Persistence

**Migration:** `database/migrations/2026_07_04_000001_add_drive_webhook_and_metadata_to_portfolio_drive_workspaces.php`
```php
$table->json('drive_webhook_channel_json')->nullable();
$table->json('metadata_json')->nullable();
```

**Model Helpers** (`PortfolioDriveWorkspace.php`):
```php
getWebhookChannel(): ?array
hasActiveChannel(): bool
getChannelExpiration(): ?string
getLastSyncAt(): ?string
getLastSyncError(): ?string
getWebhookUrl(): ?string
getTrackedFiles(): array
getFileById(string $driveFileId): ?array
getGoogleDocFiles(): array
getGoogleSheetFiles(): array
getTrackedFileCount(): int
```

**Persisted per file** (`metadata_json.drive_files[]`):
```php
[
  'id'              => $fileId,
  'name'            => $fileName,
  'mime_type'       => $mimeType,
  'web_view_link'   => $webViewLink,
  'modified_time'   => $modifiedTime,
  'last_synced_at'   => now()->toIso8601String(),
]
```

---

### P0.5 ✅ Integration Health Panel

**Cockpit Blade** (`resources/views/admin/workspace/cockpit.blade.php` — Panel 6):

**Webhook durumu:**
- Yeşil/kırmızı pulse dot → Aktif/Kapalı
- Kanal TTL (saat) → 24h altında amber warning
- Son sync zamanı → `diffForHumans()`
- Sync hatası → kırmızı alert box

**Dosya listesi:**
- Toplam · Docs · Sheets count
- Son 5 dosya linkli (Google Doc/Sheet/Slide)

**Klasör linkleri:** 12 subfolder chip grid (mevcut yapı korundu)

**`WorkspaceSummaryService::driveInfo()`** — eklenen alanlar:
```php
'webhook' => [
  'connected', 'channel_id', 'webhook_url',
  'expiration', 'expiration_ts',
  'last_sync_at', 'last_error', 'last_count',
  'needs_renewal'
],
'files' => [
  'total', 'docs', 'sheets', 'list' (last 10)
]
```

---

## DoD Checklist

| Item | Status |
|------|--------|
| Drive Folder | ✅ PASS (Sprint 4.4/4.6) |
| Template Engine | ✅ PASS (Sprint 4.4/4.6) |
| Drive Sync | ✅ PASS (`DriveSyncService::getChanges()`) |
| Webhook Route | ✅ PASS (`POST /api/webhook/drive`) |
| Webhook Service | ✅ PASS (`DriveWebhookService`) |
| Timeline Integration | ✅ PASS (Drive event labels) |
| Metadata Persistence | ✅ PASS (migration + helpers + DriveWebhookService) |
| Integration Health | ✅ PASS (cockpit panel) |

---

## Mimari Özet

```
Google Drive Push Notification
    ↓
POST /api/webhook/drive (DriveWebhookController)
    ↓
DriveWebhookService::validateNotification()
    → HMAC token verification
    → workspace resolve
    → auto-renew if expired
    ↓
DriveSyncService::processWebhookPayload()
    → base64 decode Pub/Sub envelope
    → extract fileId + changeType + name
    ↓
DriveWebhookService::processChanges()
    → DriveSyncService::getChanges()
    → shouldProcessChange() (filter folders/hidden)
    → persistFileMetadata() → metadata_json
    → emitDriveEvent() → HermesEventLog (drive.*)
    → updateLastSync() → channel.last_sync_at
    ↓
WorkspaceTimelineService
    → HermesEventLog → Unified Timeline (cockpit)
    ↓
DriveAgent (workspace provisioning)
    → DriveWebhookService::registerChannel()
    → webhook otomatik kayıt

Scheduler (daily)
    → workspacesNeedingRenewal()
    → renewChannel() before TTL expiry
```

---

## Routing

```
POST /api/webhook/drive
  → DriveWebhookController::handle()
  → DriveWebhookService::validateNotification()
  → DriveWebhookService::processChanges()
  → DriveSyncService::processWebhookPayload()
```

---

## Test Strategy

```bash
# Channel lifecycle
php artisan tinker \
  --execute="app(\App\Services\Drive\DriveWebhookService::class)->workspacesNeedingRenewal()"

# Manual channel registration for existing workspace
php artisan tinker \
  --execute="\$ws = App\Models\PortfolioDriveWorkspace::first();
             app(\App\Services\Drive\DriveWebhookService::class)->registerChannel(\$ws, url('/api/webhook/drive'))"

# Health panel data
php artisan tinker \
  --execute="\$ws = App\Models\PortfolioDriveWorkspace::first();
             dd(\$ws->driveInfo())"
```
