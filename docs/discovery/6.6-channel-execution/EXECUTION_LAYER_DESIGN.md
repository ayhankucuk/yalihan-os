# Sprint 6.6 Discovery — Execution Layer Architecture

> **Tarih:** 2026-07-09
> **Sprint:** 6.6 — Channel Execution
> **Tip:** Discovery — Execution Katmanı Tasarımı
> **Referans:** Sprint 6.5 PublishingPackage output

---

## 1. Execution Lifecycle

```
PublishingPackage üretildi
         ↓
Manual Export (Sprint 6.6)
         ↓
ChannelExecutionLog kaydı (execution_id, status = pending)
         ↓
Danışman kabul etti
         ↓
ChannelExecutionLog.status = approved
         ↓
[REPLAY] ← Sadece failed/pending kanallar
         ↓
Manual Export üretildi
         ↓
ChannelExecutionLog.status = completed / failed
         ↓
Dashboard'da görünür
```

### Execution State Machine

```
PENDING → APPROVED → EXPORTED
  ↓          ↓
REJECTED   FAILED
  ↓          ↓
  ←─── REPLAY ───┘
```

---

## 2. ChannelExecutionLog Modeli

### Tablo: `channel_execution_logs`

| Alan | Tip | Açıklama |
|------|-----|-----------|
| id | bigint | PK |
| ilan_id | bigint | Ilan FK |
| tenant_id | bigint | Tenant koruma |
| execution_id | uuid | Batch ID (birden fazla kanal tek seferde) |
| channel | enum | airbnb, sahibinden, hepsiemlak |
| payload_hash | string | Idempotency key |
| status | enum | pending, approved, exported, failed, skipped |
| export_payload | json | Manuel export içeriği |
| exported_at | datetime | Manuel export zamanı |
| started_at | datetime | Başlangıç |
| completed_at | datetime | Bitiş |
| error_message | text | Hata detayı |
| retry_count | int | Tekrar sayısı |
| triggered_by | bigint | User ID |
| metadata | json | Ekstra bilgi |

### Status Enum

```php
const STATUS_PENDING   = 'pending';    // Bekliyor
const STATUS_APPROVED  = 'approved';   // Danışman onayladı
const STATUS_EXPORTED  = 'exported';  // Manuel export üretildi
const STATUS_FAILED    = 'failed';    // Hata
const STATUS_SKIPPED   = 'skipped';   // Atlandı
```

---

## 3. Execution Plan (Batch)

Birden fazla kanal tek seferde planlanır:

```php
ExecutionPlan {
    ilan_id
    tenant_id
    execution_id: uuid
    channels: ['airbnb', 'sahibinden', 'hepsiemlak']
    workspace_id: int
    publish_package_trace_id: string
    payload_hash: string (idempotency)
    status: pending | partial_success | all_success | failed
    created_by: user_id
    created_at
}
```

---

## 4. Replay Senaryosu

### Senaryo 1: Kısmi başarısızlık

```
Airbnb      → EXPORTED ✅
Sahibinden  → FAILED ❌
Hepsiemlak → PENDING ⏳
```

**Replay komutu:** `airbnb_skip, hepsiemlak_skip`

**Sonuç:** Sadece failed/pending kanallar yeniden çalışır — exported olanlar dokunulmaz.

### Senaryo 2: Idempotent koruması

```
Aynı payload_hash ile tekrar çalıştırma →  SKIPPED
```
Aynı içerik → aynı hash → skip koruması.

---

## 5. Dashboard Entegrasyonu

### Workspace Cockpit Extension

```
┌─────────────────────────────────────────────┐
│  📡 Publishing Intelligence                 │
├─────────────────────────────────────────────┤
│  Airbnb      │ ✅ Exported │ 14:32 │ Export │
│  Sahibinden │ ⏳ Pending  │   —   │ Export │
│  Hepsiemlak │ ❌ Failed   │ 14:30 │ Retry  │
└─────────────────────────────────────────────┘
```

---

## 6. Hermes Entegrasyonu (Gelecek Sprint)

```
Hermes: "workspace.publish" eventi tetikler
         ↓
PublishingIntelligenceOrchestrator
         ↓
PreparePublishingJob (hazır)
         ↓
PublishingPackageReady event
         ↓
[HERMES DISPATCHER — Sprint 6.7+]
         ↓
ChannelExecutionOrchestrator
         ↓
Manual Export üretir
```

**Sprint 6.6:** Hermes publish emri almaz — sadece Workspace lifecycle tetikler.
