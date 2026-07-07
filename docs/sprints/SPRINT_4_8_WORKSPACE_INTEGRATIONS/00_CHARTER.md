# Sprint 4.8 — Workspace Integration Platform

**SAAB v7 APPROVED**
**Date:** 2026-07-04
**Mission:** Transform Workspace into the living center of all digital assets.

---

## Why This Sprint Exists

Sprint 4.6 built the **Observation Layer** (Property Digital Twin Cockpit).
Sprint 4.7 built the **Execution Layer** (Workspace Execution Engine).
Sprint 4.8 builds the **Integration Layer** (Workspace Integration Platform).

Today:
- Workspace Drive folders are created manually
- No Google Docs templates
- No bidirectional Drive ↔ Workspace sync
- No webhook for Drive change detection
- No Drive events in Workspace Timeline

After Sprint 4.8:
- Workspace provisioning → automatic Drive folder + 12 subfolders
- Workspace provisioning → automatic Google Docs templates
- Drive changes → webhook → Hermes → Timeline
- Drive ↔ Workspace sync is bidirectional
- All Drive events are tracked

---

## P0 Deliverables

### 1. Drive Workspace Provisioning (Extension)

`DriveWorkspaceService` already creates folders. This sprint extends it with:
- Template creation in each folder on provisioning
- Template Engine: Google Docs from Drive API

### 2. Google Docs Template Engine — `DriveTemplateService`

Templates created on workspace provisioning:

| Template | Folder | Purpose |
|----------|--------|---------|
| Portföy Kartı | 01_Fotograflar | Property overview doc |
| AI Summary | 11_AI | AI analysis results |
| Yetki Belgesi | 03_Tapu | Authorization document |
| Ekspertiz Notları | 05_Ekspertiz | Appraisal notes |
| CRM Kartı | 09_CRM | Customer relationship card |

**Architecture:**
```
DriveWorkspaceService → creates folders
    ↓
DriveTemplateService → copies templates from source folder
    ↓
Stores doc IDs + URLs in workspace metadata
    ↓
WorkspaceTimelineService → "Template Created" events
```

### 3. Workspace ↔ Drive Sync — `DriveSyncService`

**CMS → Drive** (outbound):
```
Workspace updated (photo added, field changed)
    ↓
DriveSyncService
    ↓
Drive API: upload/copy file to folder
    ↓
WorkspaceExecution record
```

**Drive → Workspace** (inbound via webhook):
```
Google Drive webhook (POST /drive/webhook)
    ↓
DriveWebhookController
    ↓
DriveSyncService::processChange()
    ↓
Workspace updated (DB)
    ↓
Hermes event → Timeline
```

### 4. Drive Webhook — `DriveWebhookController`

Endpoint: `POST /api/drive/webhook`

1. Receive Google Drive push notification
2. Validate webhook signature (HMAC)
3. Call `DriveService::getChanges()` (changes.list)
4. Route changes to appropriate handlers
5. Emit Hermes events

**Registration:**
- `DriveWebhookService::registerChannel()` — creates a Drive push channel
- Channel: `files/{workspaceId}` — one per workspace
- Expiry: 7 days, auto-renewed

### 5. Workspace Timeline Integration

Drive events added to Hermes Timeline:

| Event | Trigger |
|-------|---------|
| `drive.folder.created` | Root folder created |
| `drive.document.created` | Template document created |
| `drive.sync.outbound` | File uploaded to Drive |
| `drive.sync.inbound` | File added to Drive |
| `drive.webhook.received` | Webhook received |

---

## Architecture

```
Google Drive API
    │
    ├── DriveWorkspaceService (existing — folders)
    │         ↓
    ├── DriveTemplateService (NEW — docs from templates)
    │         ↓
    ├── DriveSyncService (NEW — bidirectional sync)
    │         ↓
    └── DriveWebhookController (NEW — webhook endpoint)
                    │
                    ↓
              Hermes Event Bus
                    │
                    ↓
              WorkspaceTimelineService
                    │
                    ↓
              Cockpit Timeline
```

---

## Not In Scope

- Google Sheets integration (P1)
- Gmail integration (P1)
- Drive permission management (P1)
- Folder quota/health checks (P1)
- Telegram (Sprint 4.9)
- New AI agents

---

## Success Scenario

```
Advisor creates new Portfolio
    ↓
WorkspaceProvisioningJob (queued)
    ↓
DriveWorkspaceService → Drive folders created (12 subfolders)
    ↓
DriveTemplateService → 5 Google Docs created from templates
    ↓
Workspace updated (drive_folder_id, subfolders_json with doc IDs)
    ↓
WorkspaceExecution record: succeeded
    ↓
Hermes Timeline: "Drive Folder Created" + "Template Created" events
    ↓
Cockpit updates immediately (via polling/websocket in Sprint 4.9)
```

---

## KPI

| Metric | Target |
|--------|--------|
| Template creation success rate | ≥ 95% |
| Webhook delivery rate | ≥ 99% |
| Sync latency | < 30 seconds |
| Timeline event accuracy | 100% |
