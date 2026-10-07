# FORENSIC REPORT — TELEGRAM_OPERATIONAL_CONFIGURATION_CONVERGENCE_TRIAGE_05

**Task ID:** `TELEGRAM_OPERATIONAL_CONFIGURATION_CONVERGENCE_TRIAGE_05`
**Mode:** FORENSIC RESEARCHER (STRICT READ-ONLY)
**Intent:** Operational configuration drift investigation post credential authority remediation
**Date:** 2026-09-23
**Baseline HEAD:** `7d320a44fa720fd600cadb46aa47605696d301c9`

---

## CONTEXT VERIFICATION

| Check | Result |
|-------|--------|
| Current HEAD | `7d320a44` ✅ |
| Bot token canonical | CANONICAL_CLEAN ✅ — `config('services.telegram.bot_token')` |
| Credential test | COMMITTED in `7d320a44` ✅ |
| Worktree | Dirty (48 files) — NOT modified |
| Bot token contract | CLOSED |

**Bot token remediation: CLOSED. Not reopened.**

---

## A. TEAM CHANNEL ID AUTHORITY

### Storage (Write)
- `TelegramBotController::updateSettings()` lines 157-165
- Key: `team:{team_id}:telegram_channel_id` → Settings DB
- Write via `Setting::set()` ✅

### Runtime Read — Triple source

| Source | Location | Key | Purpose |
|--------|----------|-----|---------|
| 1 (primary) | `TelegramBotService::sendTestMessage():1104-1106` | `Setting::get('team:1:telegram_channel_id')` | Admin test messages |
| 2 (fallback) | `TelegramBotService::sendTestMessage():1106` | `config('services.telegram.team_channel_id')` | LEGACY env fallback |
| 3 (notification) | `NotificationAuthorityService::resolveRecipients():153` | `config('services.telegram.team_channel_id')` | ALL notification events |

**Team identity:** Hardcoded `teamId = 1` (controller line 27). No multi-team dynamic resolution.

### Classification

```
WRITE_AUTHORITY:       Settings DB ✅
RUNTIME_READ_AUTHORITY:
  sendTestMessage:     Setting DB → config fallback
  NotificationRouting: config ONLY
FALLBACK_CHAIN:       Setting DB → config
CLASSIFICATION:        SPLIT_BRAIN ⚠️
ROOT_CAUSE:            Test and production notification routes diverge
                        for team-configured channels
```

---

## B. ADMIN CHAT ID AUTHORITY

### Runtime Resolution — 4-way cascade

```
1. config('services.telegram.admin_chat_id')  ← DEPLOYMENT AUTHORITY
        ↓ (if empty AND NOT unit/console)
2. Setting::get('telegram_admin_chat_id')    ← DB OVERRIDE ⚠️ STILL PRESENT
        ↓ (if empty)
3. User::where(role_id=1)                    ← FALLBACK: super_admin user
       .whereNotNull('telegram_chat_id')
       .first()
```

**NEW FINDING:** `TelegramService.php:45-48` DB override STILL PRESENT despite comment (line 40-43) saying it should be removed. Code says "override etme" but the code still does it.

**Chat ID semantics:** Routing/configuration identifier, NOT a credential. Controls where critical Cortex alerts are delivered.

### Classification

```
SEMANTIC_PURPOSE:     Routing address for admin critical alerts
WRITE_AUTHORITY:      4-way: config → DB override → User fallback → individual API
STORAGE:              4 sources
CLASSIFICATION:        SPLIT_BRAIN ⚠️
ROOT_CAUSE:            Intentional runtime override feature + accumulated fallbacks
```

---

## C. updateSettings() FALSE-SUCCESS

### Trace

**Caller:** `TelegramBotController::updateSettings()` line 168
- Controller persists `team:team_id:telegram_channel_id` BEFORE calling service
- Then calls `$this->telegramService->updateSettings($validated)` → NO-OP

**Service (lines 1149-1158):**
```php
public function updateSettings(array $settings): array
{
    Cache::forget('telegram_settings');  // real side-effect ✅
    return ['success' => true];           // misleading
}
```

### Classification

```
CALLERS:              1 confirmed
ACTUAL_SIDE_EFFECT:   Cache invalidation only
PERSISTENCE:          Controller handles it
CLASSIFICATION:        MISLEADING_NO_OP + PARTIAL_IMPLEMENTATION
```

---

## D. ADMIN SURFACE CONTRACT

### `/admin/telegram` — PROVEN_ORPHAN

- Route: `routes/admin.php:649-651` → returns `admin.telegram.index` blade
- Blade: standalone file, no `@extends` admin layout, no assets
- Navigation: NOT reachable (sidebar links to `admin.telegram-bot.index`)
- Sidebar menu regex `admin.telegram.*` matches but the actual link is `telegram-bot.index`

### `/admin/telegram-bot` — CANONICAL

- 8 routes all wired to `TelegramBotController`
- Blade: full `@extends('admin.layouts.admin')`
- Navigation: `navigation.blade.php:153`, `sidebar-content.blade.php:596`

### Visible Control Matrix

| Field | Write Target | Runtime Reader | Effective | Classification |
|-------|-------------|----------------|-----------|----------------|
| `bot_token` | BLOCKED (422) | config only | Locked ✅ | EFFECTIVE_CONTROL |
| `team_id` | param | hardcoded 1 | Fixed | PRESENTATION_ONLY |
| `telegram_channel_id` | Settings DB | Setting + config | Persists ✅ | EFFECTIVE_CONTROL |
| `webhook_url` | NOT editable | config only | Read-only | PRESENTATION_ONLY |
| `telegram_notifications` | Settings DB | isChannelEnabled() | Toggle ✅ | EFFECTIVE_CONTROL |

---

## E. NOTIFICATION BOUNDARY

`/admin/ayarlar → Bildirimler → telegram_notifications` ✅

OPERATIONAL ENABLE/DISABLE ONLY. Controls `telegram_notifications` toggle via `NotificationAuthorityService::isChannelEnabled()`. No credential or routing authority. **NOTIFICATION_BOUNDARY_STATUS: CANONICAL ✅**

---

## TESTS RUN

| Suite | Result |
|-------|--------|
| `TelegramCredentialAuthorityContractTest` (6 tests, 13 assertions) | ✅ PASS |

---

## REAL FINDINGS (5 issues)

| # | Severity | Type | Issue | Location |
|---|----------|------|-------|----------|
| F1 | **HIGH** | SPLIT_BRAIN | `sendTestMessage` reads DB → falls back to config; `NotificationAuthority` reads config ONLY → routes diverge for team-configured channels | `TelegramBotService.php:1104-1106`, `NotificationAuthorityService.php:153` |
| F2 | **MEDIUM** | LEGACY_OVERRIDE | `telegram_admin_chat_id` DB override STILL PRESENT in TelegramService despite comment saying remove | `TelegramService.php:45-48` |
| F3 | **MEDIUM** | SPLIT_BRAIN | 4-way cascade for admin_chat_id — competing authorities | `TelegramService.php:33-66` |
| F4 | **LOW** | MISLEADING_NO_OP | `updateSettings()` returns success:true for ANY input | `TelegramBotService.php:1149-1158` |
| F5 | **LOW** | PROVEN_ORPHAN | `/admin/telegram` route → Blade with no layout | `routes/admin.php:649-651` |

---

## STALE FINDINGS

| Item | Reason |
|------|--------|
| "test file untracked" | Now committed in `7d320a44` |
| "ready for commit" | Already committed |
| "STALE_TASK_CONTEXT blocked" | Context resolved |

---

## UNKNOWN ITEMS

| Item | Why Unknown |
|------|------------|
| Whether `Setting::get('telegram_admin_chat_id')` used in production | No production DB evidence |
| Whether teams beyond team_id=1 exist | Hardcoded `teamId=1` everywhere |
| Whether NotificationAuthority telegram recipients ever resolve to real ID | Falls back to empty without config |

---

## RECOMMENDED BOUNDED FIX

### Scope: TELEGRAM_OPERATIONAL_CONFIGURATION_FIX_06

**Files:**
1. `app/Services/Notification/NotificationAuthorityService.php` — Fix F1
2. `app/Modules/TakimYonetimi/Services/TelegramBotService.php` — Fix F1 + F4
3. `app/Services/TelegramService.php` — Fix F2 + F3 (architectural decision needed)
4. `routes/admin.php` — Fix F5

**Fix F1 (HIGH):**
- `NotificationAuthorityService::resolveRecipients()` line 153: Add Setting DB lookup before config
- Pattern: `Setting::get("team:1:telegram_channel_id") ?: config('services.telegram.team_channel_id', '')`

**Fix F4 (LOW):** Rename to `clearTelegramSettingsCache()` with accurate return semantics

**Fix F2+F3 (MEDIUM — decision needed):**
- Option A: Keep cascade (document precedence clearly)
- Option B: Remove DB/User fallbacks → config only (breaking change)
- **Ayhan must choose**

**Fix F5 (LOW):** Redirect `/admin/telegram` → `/admin/telegram-bot` (Strangler Fig)

**NOT in scope:** Credential authority (closed), Notification domain (canonical), telegram_notifications toggle (canonical)

---

## REGRESSION TEST CONTRACT

| Test | Expected |
|------|----------|
| `TelegramCredentialAuthorityContractTest` (6 tests) | ALL PASS |
| `NotificationSettingsContractTest` (8 tests) | ALL PASS |
| `TelegramNotificationHandlerTest` (6 tests) | ALL PASS |
| Manual: Configure team channel → test msg + notification → same channel | Both routes same |

---

## EVIDENCE_LEVEL: REPO_VERIFIED
## EVIDENCE_TYPE: STATIC_ANALYSIS + ROUTE_INSPECTION + TEST_EXECUTION
## PRODUCTION_STATUS: UNKNOWN
## DOMAIN_STATE: FUNCTIONALLY_FIXED_CLEANUP_REMAINS

---

## FINAL RESULT: TRIAGE_COMPLETE ✅

Five real findings. One HIGH (F1 — team channel split-brain). F2/F3 requires architectural decision. ONE bounded task feasible for F1+F4+F5.
