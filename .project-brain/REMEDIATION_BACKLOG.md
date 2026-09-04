# YALIHAN OS — G1–G4 Remediation Backlog

**Frozen:** 2026-09-01
**SSOT Reference:** `.project-brain/PROJECT_STATE.md` — Multi-Agent Operational Security Certification matrix
**Priority Order:** Set by user on 2026-09-01. Do not reorder without explicit approval.

---

## BACKLOG-1 — Priority: HIGHEST
### Staged Diff Secret Scanner / Pre-Commit Hook

**Problem:** Agent can intentionally or accidentally surface token fingerprints (`ghp_`, `sk-proj-`, `sk-`, `Bearer `) in staged diff, session output, or commit message. Skill-level protection exists (G7 PASS) but CI backstop is absent.

**Solution:** Git hook + CI pipeline stage that scans staged diff output before commit is accepted.

**Owner:** Antigravity / Kilo
**Scope:** `.git/hooks/pre-commit` + CI pipeline (GitHub Actions or equivalent)
**Exit Criterion:** `git diff --staged` output passes token regex scan with **0 matches** for `ghp_`, `sk-proj-`, `sk-`, `Bearer `. Scan runs before commit is recorded, not after.

**Implementation Hints:**
- `git diff --staged` → pipe to `grep -E '(ghp_[a-zA-Z0-9]{36}|sk-proj-[a-zA-Z0-9]{48}|sk-[a-zA-Z0-9]{32,})'` → exit 1 if match
- GitHub Actions step: `uses: actions/github-script` or custom shell step in CI
- Pre-commit hook: `.git/hooks/pre-commit` (installable via `chmod +x` + hook script)
- Token fingerprints to block: `ghp_`, `sk-proj-`, `sk-`, `xoxb-`, `xoxp-`, `Bearer ` (with space)
- Scan session output separately if session replay logging is active

**Status:** `IMPLEMENTED` ✅ (2026-09-01)
**Blocked By:** None
**Related Gate:** G7 (Secret Boundary Guard)
**SSOT Architecture (REM-SECRET-SCAN-REMEDIATE-01):**
- `scripts/tools/secret-scan-patterns.txt` — sole enforcement policy SSOT (tracked file, versioned)
- `scripts/tools/secret-scan.sh` — canonical scanner engine (reads patterns from SSOT file)
- `.husky/_/pre-commit` — thin delegator → `secret-scan.sh --staged`
- `.git/hooks/pre-commit` — thin delegator → `secret-scan.sh --staged`
- `.github/workflows/secret-scanner.yml` — thin delegator → `secret-scan.sh --ci`
- `scripts/tools/antigravity-full-gate.sh` — already delegates → `secret-scan.sh --staged`
- **ENFORCEMENT_PATTERN_DUPLICATION = 0** ✅ (verified: no pattern definitions outside SSOT file)
**Shell Compatibility:** bash 3.2.57 (macOS /bin/bash) + grep 2.6.0-FreeBSD ✅
**Regression Tests:** 10/10 PASS ✅
- R1 clean staged → exit 0
- R2 ghp_ token → exit 1
- R3 sk-proj- token → exit 1
- R4 Bearer token → exit 1
- R5 AKIA token → exit 1
- R6 redaction → no raw token in output
- R7 husky hook blocks ghp_ → exit 1
- R8 CI mode clean → exit 0
- R9 CI git-range sk-proj → exit 1
- B2 ghp_ no bypass → exit 1
**Pattern Count:** 10 patterns covering ghp_, github_pat_, sk-proj-, sk-, sk-ant-, xoxb-/xoxp-/xoxr-/xoxa-, AKIA (exact 16-char), Bearer (with space)
**Audit:** Antigravity audit (2026-09-01) → PARTIAL. SSOT refactor addresses policy drift. Final re-audit by Antigravity required for BACKLOG-1 CLOSE.

---

## BACKLOG-2 — Priority: HIGH
### Mechanical Pre-Mutation Conflict Guard

**Problem:** `PROJECT_STATE.md` acts as an advisory ledger only. A misbehaving or non-compliant agent can mutate a hot-spot file (schema, routes, authority.json, IlanCrudService) without a valid protocol lock, and no mechanical barrier prevents it.

**Solution:** Git pre-commit hook that checks whether any hot-spot file change has a corresponding active protocol lock in `PROJECT_STATE.md`.

**Owner:** Antigravity
**Scope:** `.git/hooks/pre-commit` + `.project-brain/PROJECT_STATE.md`
**Exit Criterion:** Commit to any hot-spot file is blocked with a human-readable error if the file does not have an active lock entry with TTL not expired. Hot-spot list:
- `database/schema/mysql-schema.sql`
- `database/migrations/*`
- `routes/web.php`, `routes/api.php`, `routes/admin.php`
- `.sab/authority.json`
- `config/*.php`
- `app/Services/IlanCrudService.php`

**Implementation Hints:**
- Hook reads `PROJECT_STATE.md`, parses hot-spot locks section
- Each lock entry has: `HOTSPOT_LOCK:<file>:<agent>:<TTL>` format
- Timestamp check: lock expired if older than TTL
- Error message: "Hot-spot file `<file>` is modified without active protocol lock. Acquire lock in PROJECT_STATE.md first."
- Heartbeat mechanism: agent must update timestamp in lock entry; hook checks staleness

**Status:** `IMPLEMENTED` ✅ (2026-09-04)
**Blocked By:** None
**Related Gate:** G2 (Conflict Guard)
**SSOT Architecture:**
- `scripts/tools/conflict-guard.sh` — canonical conflict guard engine (hot-spot lock enforcement, --staged, --check, --acquire, --release, --test)
- `.husky/_/pre-commit` — thin delegator: `secret-scan.sh --staged` && `conflict-guard.sh --staged`
- `.git/hooks/pre-commit` — thin delegator: `secret-scan.sh --staged` && `conflict-guard.sh --staged`
- `scripts/tools/antigravity-full-gate.sh` — Gate 0: `conflict-guard.sh --staged`
- `.project-brain/PROJECT_STATE.md` — Active Protocol Locks ledger
**Shell Compatibility:** GNU bash 3.2+ (macOS /bin/bash / Linux)
**Regression Tests:** 17/17 PASS ✅
- Test 1-9: Hot-spot file matching (schema, migrations, routes, authority, config, IlanCrudService, positive/negative cases)
- Test 10-13: Lock acquisition, verification, multi-file globs, and TTL expiration detection
- Test 14: Lock release verification
- Test 15-17: Multi-agent concurrent lock isolation and non-hotspot passthrough

---

## BACKLOG-3 — Priority: MEDIUM
### Automatic Backend Guard Selection

**Problem:** When Kilo modifies a controller or service, the agent must manually read the file and decide which skill to apply. Guard selection is at agent discretion, not enforced by convention.

**Solution:** Agent startup hook or skill pool convention that maps file path patterns to required skills automatically.

**Owner:** Kilo / Antigravity
**Scope:** `.agents/skills/` + agent startup sequence
**Exit Criterion:** When Kilo opens a file under `app/Http/Controllers/Api/`, the skill taxonomy is consulted and relevant skills are auto-loaded before mutation begins. No manual skill selection required.

**Implementation Hints:**
- Skill pool taxonomy in `AGENTS.md` or `.agents/skills/SKILL_INDEX.md`
- File → Skill mapping table:
  | File Pattern | Required Skill |
  |---|---|
  | `app/Http/Controllers/Api/V2/Ilan*` | `authorization-boundary-auditor` |
  | `app/Http/Controllers/Api/V2/*` | `authorization-boundary-auditor` |
  | `app/Http/Controllers/Api/V2/*Cortex*` | `cortex-orchestration-evaluator` |
  | `app/Http/Controllers/Api/V2/*Checkin*` | `hermes-event-sync` |
  | `app/Models/V2/*` | `schema-contract-guardian` |
  | `database/migrations/*` | `schema-contract-guardian` |
  | `app/Services/IlanCrudService.php` | `authorization-boundary-auditor` + `schema-contract-guardian` |
- Agent startup: read file being modified → consult map → auto-load skills
- Can be implemented as a shell alias or small wrapper script: `kilo-mutate <file>` → loads skills then opens editor

**Status:** `IMPLEMENTED` ✅ (2026-09-04)
**Blocked By:** None
**Related Gate:** G3 (Backend Guard Selection)
**Implementation:** `.agents/skills/SKILL_INDEX.md` — File → Skill mapping tablosu, 30+ pattern, 10 skill kategorisi. Agent'lar dosya yolu bazlı otomatik skill seçimi yapabilir.

---

## BACKLOG-4 — Priority: MEDIUM
### Auth Boundary CI Gate

**Problem:** `OwnerAuthController` authorization rules (tenant_id checks, generic error messages, token scope) are confirmed by manual code review only. No automated CI gate tests these rules continuously.

**Solution:** Dedicated test suite in `tests/Feature/Security/AuthorizationBoundaryTest.php` that runs as a mandatory CI gate.

**Owner:** Kilo
**Scope:** `tests/Feature/Security/AuthorizationBoundaryTest.php` + CI pipeline gate
**Exit Criterion:** Authorization boundary tests run in CI for every PR. 401/403/404/419/420 cases covered. No manual review required to confirm these cases pass.

**Test Coverage Required:**
- [ ] Unauthenticated request → 401 (not 500, not generic 500)
- [ ] Wrong tenant token → 403 (not 404, not data leakage)
- [ ] Expired/invalid token → 419 or 401 (not 200 with empty data)
- [ ] Unknown email in login → generic "credentials do not match" (not "email not found" — enum protection)
- [ ] Missing tenant_id on resource that requires it → 404 or 403
- [ ] CSRF/missing session → 419
- [ ] Rate limit exceeded → 429
- [ ] OwnerAuthController: each error path has generic message (no enumeration)

**CI Integration:**
```yaml
# .github/workflows/security-auth-boundary.yml
- name: Auth Boundary Tests
  run: php artisan test --filter=AuthorizationBoundaryTest
```

**Status:** `IMPLEMENTED` ✅ (2026-09-04, commit `5b2f1a8`)
**Blocked By:** None (can be implemented independently)
**Related Gate:** G4 (Authorization Boundary)

**Implementation Summary:**
- `tests/Feature/Security/AuthorizationBoundaryTest.php` — 15 test case
- Covers: unauthenticated redirect, tenant isolation, generic error messages, rate limit middleware presence, token enumeration protection
- Security fix: `OwnerAuthController::verifyToken()` → `süresi dolmuş` enumeration removed (`b7e2f91`) — generic `Giriş linki geçersiz` message

**Test Results:** 15/15 PASS ✅

---

## VERIFIED TECHNICAL DEBT — Repo-Confirmed (2026-09-04)

**Referans:** Codex güvenlik triyajı — H-numaraları düzeltildi, yanlış "kritik açık" hükümleri ayıklandı.
**Doğrulama Standardı:** `REPO_VERIFIED` = kod/şema analizi ile teyit edildi. `INFERRED` = çıkarım, kanıt yok.

---

### BACKLOG-5 — Priority: P0 (CRITICAL)
### Lead Tenant Boundary — Model + Authority + Webhook

**Bulgu:** `Lead` modeli `BelongsToTenant` trait'i kullanmıyor. `tenant_id` DB'de mevcut (`2026_05_19_080616_add_tenant_id_to_leads_table.php`) ancak modele global scope olarak uygulanmıyor. `LeadAuthorityService` authority sorguları tenant kimliğe katmıyor. Webhook lead oluştururken tenant_id ataması yapılmıyor.

**Kanıt (REPO_VERIFIED):**
- `app/Models/Lead.php` — `BelongsToTenant` trait'i yok, `tenant_id` fillable'da var ama scope uygulanmıyor
- `app/Services/CRM/LeadAuthorityService.php:119` — `ensureScoreExists()`: `AILeadScore::where('lead_id', $lead->id)->first()` → tenant filtrelemesi yok
- DB: `leads.tenant_id` kolonu nullable olarak mevcut

**Risk:** Cross-tenant lead görünürlüğü veya manipülasyonu.

**Etki Alanı:** `app/Models/Lead.php`, `app/Services/CRM/LeadAuthorityService.php`, webhook controller'ları

**Tasarım Kararı Gerekli:**
1. `Lead` → `BelongsToTenant` trait eklenmeli mi? (Mevcut `tenant_id`'li kayıtlar korunmalı)
2. Webhook lead oluştururunda `tenant_id` otorite ataması
3. `(platform, platform_user_id)` unique index — `tenant_id` eklenmeli (cross-tenant kimlik çakışması)
4. `LeadAuthorityService.ensureScoreExists()` → tenant-scoped query

**Owner:** Cline (Security Agent) — client/lead-tenant-boundary worktree
**Scope:** Migration (ayrı commit) + Model + Authority Service + Webhook creation key (ayrı commit)
**Production Migration:** `BLOCKED_PENDING_PRODUCTION_AUTH` (14 pending migration — kullanıcı onayı bekleniyor)
**Status:** `IMPLEMENTED` ✅ (2026-09-04, Cline — 7 commit, 10/10 test PASS)
**Blocked By:** None (code complete, migration deploy pending)
**Exit Criterion:** ✅ `Lead::query()` → otomatik tenant scope (BelongsToTenant trait); ✅ webhook'dan gelen lead → doğru tenant_id ile kaydedilir (LeadAuthorityService::registerLeadFromExternalSource); ✅ unique index `(tenant_id, platform, platform_user_id)` migration'da mevcut.

**Implementation Summary (Cline — Sprint 12D):**
- `6c5819d` — Lead model → BelongsToTenant trait, tenant_id fillable + cast
- `101a559` — Migration → leads composite unique index (tenant_id, platform, platform_user_id)
- `f76b45c` — LeadAuthorityService → firstOrCreate explicit tenant_id + wasRecentlyCreated block
- `b6e7b01` — LeadFactory → tenant_id definition + forTenant(int) state; LeadTenantBoundaryTest (8 test)
- `416bb42` — LeadTenantBoundaryTest → schema bootstrap + 10/10 PASS
- `0468759` — Tenant model → HasFactory trait (test factory support)
- `cd798d1` — Base commit (ai cost guard fixtures alignment)

**Test Results:** LeadTenantBoundaryTest 10/10 PASS ✅
- no_tenant_context_returns_zero_leads ✅
- tenant_a_sees_only_tenant_a_leads ✅
- tenant_b_sees_only_tenant_b_leads ✅
- finding_tenant_b_lead_from_tenant_a_context_throws_ModelNotFoundException ✅
- without_tenant_scope_reveals_all_leads_across_tenants ✅
- creating_lead_without_explicit_tenant_id_auto_assigns_from_context ✅
- two_tenants_can_have_lead_with_same_platform_user_id ✅
- register_lead_from_external_source_assigns_correct_tenant_id ✅
- first_or_create_is_tenant_scoped_same_platform_user_returns_same_lead ✅
- first_or_create_returns_different_lead_per_tenant_same_platform_user ✅

**Codex Audit Verdict:** ACCEPT ✅ — SAAB prompt'a tam uyum, tenant isolation tam kapsamı, test kalitesi yüksek.

---

### BACKLOG-6 — Priority: P1 (HIGH)
### AI/API Rate-Limit Race Condition — Atomic Olmayan Cache İşlemleri

**Bulgu:** `AIRateLimitMiddleware` ve `ApiRateLimitMiddleware` `Cache::get()` + `Cache::put()` atomik değil. İki eşzamanlı istek aynı anda limit kontrolünden geçerek rate limit'i atlatabilir (time-of-check-time-of-use race).

**Kanıt (REPO_VERIFIED):**
- `app/Http/Middleware/AIRateLimitMiddleware.php:36-47` — `Cache::get($key, 0)` → `Cache::put($key, $attempts + 1, ...)` — arası atomik değil
- `app/Http/Middleware/ApiRateLimitMiddleware.php:38-48` — Aynı desen

**Çözüm:** Laravel `RateLimiter` facade kullanılmalı. Atomik increment garantili.

**Etki Alanı:** `app/Http/Middleware/AIRateLimitMiddleware.php`, `app/Http/Middleware/ApiRateLimitMiddleware.php`

**Status:** `IMPLEMENTED` ✅ (2026-09-04, commit `3d16f4e`)
**Blocked By:** None
**Exit Criterion:** `RateLimiter::attempt()` ile değiştirildi; concurrent isteklerde limit aşılmaz. Test: 5/5 PASS.

---

### BACKLOG-7 — Priority: P1 (HIGH)
### Security Log Ham Input/Header — Secret Sızdırma Riski

**Bulgu:** `SecurityMiddleware` şüpheli istekleri log'larken `$request->all()` ve `$request->headers->all()` ham halleriyle yazıyor. Authorization header, CSRF token, cookie ve diğer sensitive header'lar security log dosyasına plaintext gider.

**Kanıt (REPO_VERIFIED):**
- `app/Http/Middleware/SecurityMiddleware.php:153-161` — `'headers' => $request->headers->all()` → ham dump
- `app/Http/Middleware/SecurityMiddleware.php:159` — `'input' => $request->all()` → ham dump

**Çözüm:** Secret barındıran header/input alanları (Authorization, Cookie, password, token, secret, key) filtreden geçirilmeli. Sensitive değerler `***REDACTED***` ile değiştirilmeli.

**Etki Alanı:** `app/Http/Middleware/SecurityMiddleware.php`

**Status:** `IMPLEMENTED` ✅ (2026-09-04, commit `97e7778`)
**Blocked By:** None
**Exit Criterion:** ✅ Security log'da Authorization header değeri `[REDACTED]`. ✅ `password`, `token`, `secret`, `key` içeren input alanları maskelenir. Test: SecurityLogSecretLeakageTest 5/5 PASS (40 assertions).

**Implementation:**
- `maskSensitiveHeaders()`: authorization, cookie, x-api-key, x-auth-token, x-csrf-token, api-key, x-goog-channel-token → `[REDACTED]`
- `maskSensitiveInput()`: password, token, api_key, secret, credit_card, cvv, iban, vs. → `[REDACTED]` (recursive for nested arrays)
- Test: 5 test — header masking (Authorization, Cookie, API key), input masking (password, tokens), nested array recursion

---

### BACKLOG-8 — Priority: P2 (MEDIUM)
### Fotoğraf `display_order` Eşzamanlı Yarışı — Veri Bütünlüğü

**Bulgu:** `IlanPhotoService::uploadPhotos()` içinde her fotoğraf için `count() + 1` ile sıra numarası atanıyor. Eşzamanlı yüklemede iki istek aynı `display_order` değeri alabilir.

**Kanıt (REPO_VERIFIED):** `app/Services/Ilan/IlanPhotoService.php:45` — `IlanFotografi::where('ilan_id', $ilan->id)->count() + 1` → race condition. Transaction yok, lock yok.

**Çözüm:** `DB::transaction` içinde `lockForUpdate()` veya `max(display_order) + 1` atomik sorgusu kullanılmalı.

**Etki Alanı:** `app/Services/Ilan/IlanPhotoService.php`

**Status:** `IMPLEMENTED` ✅ (2026-09-04, commits: `7c52660`, `5d8f81b`, `2a0c1c2`)
**Blocked By:** None
**Exit Criterion:** Concurrent fotoğraf yüklemede çakışma olmaz; her fotoğraf farklı `display_order` alır.
**Implementation:**
- `DB::beginTransaction()` + `lockForUpdate()` + `max('display_order') + 1` + retry loop
- `ilan_fotograflari` unique index `(ilan_id, display_order)` migration (`2026_09_04_173133`)
- Retry loop: MySQL 23000 duplicate key hatasını yakalar, orphan dosyaları temizler, 5 deneme yapar
- Migration: cross-DB compatible (`Schema::hasIndex()` — MySQL + SQLite), idempotent, preflight duplicate check
**Test:** `tests/Feature/Ilan/PhotoDisplayOrderRaceConditionTest.php` — 5/5 PASS (30 assertions)

**Migration Production Notu:** `BLOCKED_PENDING_PRODUCTION_AUTH` — MySQL production'da çalıştırmadan önce backup alın. `php artisan migrate` ile uygula.

---

### BACKLOG-9 — Priority: P2 (MEDIUM)
### Lead Unique Anahtar — Cross-Tenant Kimlik Çakışması Riski

**Bulgu:** `leads` tablosunda `(platform, platform_user_id)` unique index mevcut ancak `tenant_id` bu key'de değil. Aynı `platform_user_id` farklı tenant'larda varsa cross-tenant kimlik çakışması riski doğar.

**Kanıt (REPO_VERIFIED):** Migration `2026_05_19_080616_add_tenant_id_to_leads_table.php` — `tenant_id` nullable, FK mevcut ama unique key'e dahil değil.

**Çözüm:** Unique index `(tenant_id, platform, platform_user_id)` olarak değiştirilmeli. Mevcut nullable `tenant_id` kayıtları için doldurma stratejisi belirlenmeli.

**Etki Alanı:** `database/migrations/` (yeni migration), `app/Models/Lead.php`

**Status:** `OPEN`
**Blocked By:** BACKLOG-5 (Lead tenant boundary — aynı model ve şema üzerinde çalışıyor)
**Exit Criterion:** Unique index `tenant_id`'yi içerir. Cross-tenant aynı platform_user_id → iki ayrı lead oluşur.

---

## ARAŞTIRMA BULGULARI — Codex Triyaj Dışı (Kanıtlanmamış / Yetersiz)

**Bu maddeler KABUL EDİLMİŞ TEKNİK BORÇ olarak işlenmez.** Yalnızca potansiyel risk göstergesi olarak izlenir.

| ID | Bulgu | Codex Kararı | Neden |
|----|-------|--------------|-------|
| R-01 | `BulkManagementController` cross-tenant bypass | `INFERRED` | `Ilan` global scope'tan geçiyor; açık tenant filtresi eksikliği tek başına bypass kanıtı değil. Regresyon testi gerekli. |
| R-02 | `ReferenceController` kritik açık | `UNKNOWN` | Route dosyalarında bağlı değil. Bulunamadı veya kaldırılmış olabilir. |
| R-03 | `AILeadScoreObserver` sonsuz döngü | `INFERRED` | Observer queue job dispatch ediyor; asenkron tekrar ihtimali var. İdempotency testi gerekli. |
| R-04 | `ReconcileLocationsCommand` SQL injection | `INFERRED` | SQL değerleri DB ID'leri ve plan çıktısı. Injection kanıtı yok. |
| R-05 | `canonical_tables.php` eksik | `BRANCH_INTEGRATION` | `codex/schema-contract-final` branch'inde mevcut. Production açık değil — branch entegrasyon borcu. **GÜNCELLEME 2026-09-04:** `client-schema-migration-recovery` worktree'inde `ai_provider_profiles` migration stabilize edildi (commit `6096b4a`). AiCostGuardTest 5/5 PASS. Branch entegrasyon kısmen tamamlandı. |
| R-06 | MCP sunucuları "aktif" | `UNKNOWN` | Repo içinde doğrulanamadı. Durumları bilinmiyor. |

**Sonraki Araştırma:** Kilo (Cross-tenant ve concurrent webhook testleri) + Codex (Son mimari karar).

---

## RELEASE BLOCKERS — CONDITIONAL ACCEPT Durumu

**Karar:** `CONDITIONAL ACCEPT — BACKLOG_IMPLEMENTED / RC1_STALE / RELEASE_BLOCKED`

### Release öncesi tamamlanması gereken maddeler

| # | Bulgu | Sahip | Durum |
|---|-------|-------|-------|
| RC-B1 | RC1 (`249cfc1`) güncel kodları içermiyor — `fix/p0-test-failures` branch'indeki son BACKLOG-8 commitleri (`5d8f81b`, `2a0c1c2`) RC1'e merge edilmeli | Wenox | OPEN |
| RC-B2 | `PhotoDisplayOrderRaceConditionTest`: MySQL unique-index doğrulaması yok — SQLite CI'da test atlanıyor | Wenox | OPEN |
| RC-B3 | V2IlanAuthorizationBoundaryTest: S1/S4/S5/S6 başarısız (auth scope — mevcut kodla ilgili, yeni kod değil) | Wenox | OPEN |
| RC-B4 | BACKLOG-1 final audit: dokümanda hâlâ "Antigravity final re-audit required" yazıyor | Antigravity | OPEN |
| RC-B5 | TD-13, TD-14: Teknik karar verildi — `docs/architecture/td-13-td-14-decision-2026-09-04.md` | Codex | ✅ DONE |
| RC-B6 | Production migration: `BLOCKED_PENDING_PRODUCTION_AUTH` — `ilan_fotograflari` unique index migration'ı için MySQL backup + deploy onayı gerekli | Kilo | BLOCKED |

### RC1 ile ilgili not

`fix/p0-test-failures` branch'i `origin/fix/p0-test-failures`'ten 17 commit ileride. RC1 (`249cfc1`) eski kod tabanını temsil ediyor. Release için yeni bir RC2 branch'i oluşturulmalı ve `fix/p0-test-failures`'deki tüm implementasyonlar merge edilmeli.

### BACKLOG-8 Migration Notu (BLOCKED_PENDING_PRODUCTION_AUTH)

Migration `2026_09_04_173133` SQLite ve MySQL uyumlu. Production'a deploy etmeden önce:
1. MySQL production DB'de backup al
2. `php artisan migrate` ile uygula
3. `ilan_fotograflari` tablosunda mevcut duplicate kayıtları temizle (varsa migration preflight hatası verecektir)


## Backlog Summary

| ID | Gate | Priority | Owner | Status |
|----|------|----------|-------|--------|
| BACKLOG-1 | G7 | HIGHEST | Antigravity / Kilo | `IMPLEMENTED` ✅ |
| BACKLOG-2 | G2 | HIGH | Antigravity | `IMPLEMENTED` ✅ (17/17 PASS) |
| BACKLOG-3 | G3 | MEDIUM | Kilo / Antigravity | `IMPLEMENTED` ✅ (SKILL_INDEX.md) |
| BACKLOG-4 | G4 | MEDIUM | Kilo | `IMPLEMENTED` ✅ (15/15 PASS) |
| BACKLOG-5 | — | P0 (CRITICAL) | Cline (Security Agent) | `IMPLEMENTED` ✅ (7 commit, 10/10 PASS) |
| BACKLOG-6 | — | P1 (HIGH) | Codex | `IMPLEMENTED` ✅ (5/5 PASS) |
| BACKLOG-7 | — | P1 (HIGH) | Codex | `IMPLEMENTED` ✅ (5/5 PASS) |
| BACKLOG-8 | — | P2 (MEDIUM) | Codex | `IMPLEMENTED` ✅ (5/5 PASS, commit 7c52660) |
| BACKLOG-9 | — | P2 (MEDIUM) | Cline (BACKLOG-5 içinde çözüldü) | `CLOSED` ✅ |

**Dependencies:** ALL 9 BACKLOG ITEMS ARE IMPLEMENTED/CLOSED! (9/9 Complete) ✅

**Güncelleme 2026-09-04 (Oturum 148):** `client-schema-migration-recovery` worktree stabilize edildi. AiCostGuardTest 5/5 PASS (14 assertions). Migration idempotency guard eklendi (commit `6096b4a`). Worktree clean, storage clean. Client Agent migration kurtarma ön koşulu kısmen karşılandı — BACKLOG-5 için engel kaldırıldı.

---

## AGENT GÖREV DAĞILIMI — 2026-09-04 (Oturum 148)

### Tamamlanan Görevler

| Agent | Görev | Commit | Durum |
|-------|-------|--------|-------|
| Codex | AiCostGuardTest stabilization + migration idempotency | `6096b4a`, `0ba4303` | ✅ 5/5 PASS |
| Kilo | YayinTipi ghost fillable fix | `a29a5c5` | ✅ ACCEPT |
| Cline | Security backlog dokümantasyon | `01b645f` | ✅ |

### Sıradaki Görevler — Paralel Çalışma Planı

| Agent | Görev | Priority | Worktree/Branch | Engeller |
|-------|-------|----------|-----------------|---------|
| **Cline** | BACKLOG-5: Lead Tenant Boundary | P0 CRITICAL | `client/lead-tenant-boundary` (base: `cd798d1`) | ✅ Engeller kalktı (migration kurtarma tamam) |
| **Codex** | BACKLOG-6: AI Rate-Limit Race Condition | P1 HIGH | Ana repo `fix/p0-test-failures` | Yok |
| **Kilo** | BACKLOG-4: Auth Boundary CI Gate | MEDIUM | Yeni worktree `kilo/auth-boundary-ci` | Yok |
| **Antigravity** | BACKLOG-2: Pre-Mutation Conflict Guard | HIGH | Yeni worktree | Yok (BACKLOG-1 ✅) |

### Tamamlanan Görevler (2026-09-04, Oturum 151)

| Agent | Görev | Commit | Durum |
|-------|-------|--------|-------|
| Kilo | BACKLOG-4: AuthorizationBoundaryTest CI gate | `5b2f1a8` | ✅ 15/15 PASS |
| Kilo | BACKLOG-3: SKILL_INDEX.md (Auto Guard Selection) | `3a9c4d2` | ✅ |
| Kilo | OwnerAuthController: token enumeration fix (`süresi dolmuş` → generic) | `b7e2f91` | ✅ Security bulgu fix |

### Sıra Dışı (Tamamlanan)

1. ~~**Codex** → BACKLOG-7: Security Log Secret Leakage (P1)~~ ✅ IMPLEMENTED (5/5 PASS)
2. **Kilo** → BACKLOG-3: Automatic Backend Guard Selection (MEDIUM)
3. ~~**Codex** → BACKLOG-8: Fotoğraf display_order Race (P2)~~ ✅ IMPLEMENTED (5/5 PASS, commit 7c52660)
4. ~~**Cline** → BACKLOG-9: Lead Unique Key Cross-Tenant (P2, BLOCKED by BACKLOG-5)~~ ✅ CLOSED

### Dağıtım Mantığı

- **Cline → BACKLOG-5:** Security Agent olarak işaretli, `WORKTREE_ASSIGNED`, önkoşul tamamlandı. P0 CRITICAL — en yüksek öncelik.
- **Codex → BACKLOG-6:** Codex migration kurtarmayı tamamladı. BACKLOG-6'nın engeli yok, P1 HIGH, middleware dosyalarında küçük değişiklik — hızlı döngü.
- **Kilo → BACKLOG-4:** Kilo'ya atanmış, bağımsız, CI gate test yazımı — Kilo'nun uzmanlık alanı.
- **Antigravity → BACKLOG-2:** Antigravity'ye atanmış, BACKLOG-1 tamamlandı (engeli kalktı), pre-commit hook mekanizması.
