# YDL v1 — Phase 3 SPEC
## Agent Context Integration

**Version:** 1.0
**Date:** 2026-08-13
**Status:** SPEC — Implementation Not Started
**Owner:** Kilo Agent (YDL Orchestrator)
**Dependeny:** Phase 2 ✅ (write pipeline, gates, event log — complete)

---

## Problem Statement

Phase 2 tamamlandı: `ydl:apply` write pipeline + 4 certification gates. Ancak iki critical boşluk var:

**Boşluk 1 — Context:** YDL state dosyaları diskte duruyor ama hiçbir agent oturum başında bunları okumuyor. Bir agent yeni oturuma başladığında:
- Mevcut sprint ne?
- Hangi blocker'lar aktif?
- Sıradaki action ne?
- Agent ne yapmaya yetkili?

Bunların cevabı yok. Agent kör başlıyor.

**Boşluk 2 — Memory Update Loop:** Phase 2'de `ydl:apply --confirm` elle tetikleniyor. Ama oturum bittiğinde kimse bunu çalıştırmıyor. Memory dosyaları güncellenmez → bir sonraki agent eski context'le başlar → drift.

---

## Phase 3 Hedefi

> YDL state dosyalarını agent context'ine inject etmek ve session sonu memory update döngüsünü kapatmak.

**Temel ayrım:** Phase 2 = write pipeline (✅). Phase 3 = **read pipeline** + session lifecycle.

---

## Component 1: `YdlContextReader`

**Dosya:** `app/Services/Ydl/YdlContextReader.php`

**Sorumluluk:** `memory/ydl/state/current.json` ve `memory/ydl/blockers.json` okur → agent-readable context üretir.

**Çıktı formatı:**
```php
class YdlContextOutput
{
    public function __construct(
        public readonly string $sprint,
        public readonly string $sprintStatus,
        public readonly string $recommendationAction,
        public readonly string $recommendationTarget,
        public readonly string $recommendationRationale,
        public readonly string $confidence,
        public readonly array  $activeBlockers,   // [{id, gate, type, owner, development_action}]
        public readonly string $authorityLevel,    // 'FULL' | 'LIMITED_BY_BLOCKER' | 'STOP'
        public readonly string $gitBranch,
        public readonly string $gitCommit,
        public readonly string $sabStatus,         // 'CLEAN' | 'WARNINGS' | 'VIOLATIONS'
        public readonly string $lastUpdated,
    ) {}
}
```

**Authority Level Logic:**
```
if (sprintStatus === 'BLOCKED' && blocker.development_action === 'DO_NOT_CONTINUE_THIS_CODE') {
    authorityLevel = 'LIMITED_BY_BLOCKER'  // otonom çalışamaz, blocker dışı iş yapabilir
} elseif (hasSecurityBlocker) {
    authorityLevel = 'STOP'                 // hiçbir şey yapamaz
} else {
    authorityLevel = 'FULL'                // tam yetki
}
```

**Interface:**
```php
// Basit — bir satırda tam context
$context = (new YdlContextReader())->read();

// Agent prompt'larına enjekte edilebilir metin formatı
$markdown = (new YdlContextReader())->toMarkdown();
```

**toMarkdown() çıktısı (CLAUDE.md preamble injection için):**
```markdown
## YDL State — Oturum Başlangıcı

**Sprint:** Sprint 4.15 | **Durum:** AWAITING_BOOKING_COM_ONBOARDING
**Yetki:** LIMITED_BY_BLOCKER (BLK-001: Booking.com onboarding — dış bağımlılık)
**Sıradaki:** START — YDL Phase 3 | **Güven:** HIGH

**Aktif Blocker'lar:**
| ID | Gate | Tip | Sahip | Aksiyon |
|----|------|-----|-------|---------|
| BLK-001 | G35 | EXTERNAL_DEPENDENCY | BOOKING_COM | DO_NOT_CONTINUE_BOOKING_CODE |

**SAB:** CLEAN (0 new, 0 blocking)
**Git:** integration/era-v-phase2a-e01 @ 511eb634
```

---

## Component 2: `ydl:context` Artisan Command

**Dosya:** `app/Console/Commands/YdlContextCommand.php`

**Sorumluluk:** CLI'den `YdlContextReader` çıktısını gösterir. Agent'lar bu komutu çağırabilir.

```bash
php artisan ydl:context
# → YdlContextOutput Markdown formatında

php artisan ydl:context --json
# → JSON formatında (agent parsing için)

php artisan ydl:context --authority
# → Sadece authority level + blockers
```

**Kullanım:** Agent oturum başında `php artisan ydl:context` çalıştırır → context bilgisini okur → yetki seviyesine göre karar verir.

---

## Component 3: Memory Update Loop — Session Event Pipeline

**Sorumluluk:** Her oturum sonunda YdlEvent üretmek ve `ydl:apply` pipeline'ını tetiklemek.

**Oturum Lifecycle:**

```
Agent Oturum Başlangıcı
    │
    ├── php artisan ydl:context          ← Component 2
    │       ↓
    │   YdlContextReader okur state     ← Component 1
    │       ↓
    │   Agent karar verir (authority'ye göre)
    │
    ├── [Agent çalışır]
    │
    └── Oturum Sonu
        │
        ├── YdlSessionSummary üretilir   ← Component 3A
        │       (sprint, action, commit, blocker changes)
        │
        ├── ydl:apply --dry-run          ← mevcut Phase 2
        │       (event + patch planı üretilir)
        │
        └── git commit → ydl:apply --confirm
                (G3c COMMIT_DRIFT geçer → write yapar)
```

**YdlSessionSummary DTO:**
```php
final class YdlSessionSummary
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $sprint,
        public readonly string $action,          // 'CONTINUE' | 'FIX' | 'START' | 'CERTIFIED'
        public readonly string $target,
        public readonly string $commit,
        public readonly string $branch,
        public readonly array  $blockerChanges,   // [{op: 'add'|'resolve'|'update', ...}]
        public readonly string $occurredAt,
    ) {}
}
```

**Oturum Sonu Trigger Mekanizması (bu oturumda implementasyon yok — Phase 3'te de yok):
Manuel: Her oturum sonunda agent `ydl session-summary` çalıştırır.
Gelecek (Phase 4): Otomatik — agent shutdown hook veya CI pipeline.**

**`ydl session-summary` Artisan Command:**
```bash
# Mevcut çalışma için summary üret
php artisan ydl:session-summary --action CONTINUE --target "YDL Phase 3" --commit $(git rev-parse HEAD)

# Blocker resolve et
php artisan ydl:session-summary --action CONTINUE --target "YDL Phase 3" --resolve-blocker BLK-001 --commit $(git rev-parse HEAD)
```

Bu komut bir `YdlSessionSummary` üretir, bunu bir `YdlEvent`'e dönüştürür, ve `ydl:apply --dry-run` çalıştırır. Agent sonucu görür → commit yapar → `ydl:apply --confirm` çalışır (G3c geçer).

---

## Component 4: CLAUDE.md Preamble Injection

**Sorumluluk:** `CLAUDE.md`'nin başına otomatik YDL state section eklemek.

**Injection Mekanizması:**
```bash
# CLAUDE.md başına YDL state ekle (oturum başı)
php artisan ydl:context --inject-claude

# mevcut YDL section'ı kaldır + yeniden ekle ( idempotent)
```

**Not:** Bu otomatik injection değil — agent manuel olarak `ydl:context --inject-claude` çalıştırır. Kilo Agent oturum başı protocol'üne `php artisan ydl:context` eklenir (CLAUDE.md güncellenir).

**Kilo Protocol güncellemesi (CLAUDE.md):**
```markdown
## ⚡ OTURUM BAŞI (YDL Phase 3)

```bash
# 1. Sistem sağlığını kontrol et
php artisan bekci:health --detailed

# 2. Mimari ihlal var mı bak
php artisan sab:integrity-scan

# 3. YDL state oku — Agent context injection
php artisan ydl:context

# 4. Değişiklik yapacaksan önce gate çalıştır
./scripts/tools/antigravity-full-gate.sh --quick
```
```

---

## Component 5: Phase 3 Test Suite

**Dosya:** `tests/Feature/Ydl/YdlPhase3ContextTest.php`

**Test senaryoları:**

| # | Senaryo | Beklenen |
|---|---------|----------|
| T1 | `YdlContextReader` mevcut state okuyabiliyor mu? | Tüm alanlar doğru |
| T2 | Authority FULL: blocker yok | `authorityLevel = 'FULL'` |
| T3 | Authority LIMITED: BLK-001 aktif, DO_NOT_CONTINUE_THIS_CODE | `authorityLevel = 'LIMITED_BY_BLOCKER'` |
| T4 | Authority STOP: security blocker | `authorityLevel = 'STOP'` |
| T5 | `ydl:context --json` valid JSON üretiyor mu? | JSON parse edilebilir |
| T6 | `ydl:context --authority` sadece authority gösteriyor mu? | Authority + blockers only |
| T7 | `ydl session-summary --action CONTINUE` YdlEvent üretiyor mu? | Event valid |
| T8 | `ydl session-summary --resolve-blocker` blocker değişikliği üretiyor mu? | blockerChanges ≠ [] |

---

## Phase 3 Olmayanlar (Phase 4'e Ertelenen)

- **Otomatik injection:** Agent oturum başında CLAUDE.md'yi otomatik güncellemez. Manuel: `ydl:context --inject-claude`
- **Shutdown hook:** Oturum bitiminde otomatik `ydl session-summary` tetiklenmez
- **CI/CD entegrasyonu:** Test sonrası otomatik YDL pipeline yok
- **Agent otomatik write:** Agent'lar hâlâ `ydl:apply` CLI'yi manuel çağırır

---

## Phase 3 DoD (Definition of Done)

> Her satır yeşil olmalı.

| # | Kriter | Kanıt |
|---|--------|-------|
| 1 | `YdlContextReader` tüm state alanlarını okuyabiliyor | `YdlContextReaderTest` 5/5 PASS |
| 2 | Authority level logic doğru | T2, T3, T4 PASS |
| 3 | `ydl:context --json` valid | T5 PASS |
| 4 | `ydl session-summary` YdlEvent üretiyor | T7, T8 PASS |
| 5 | Phase 3 test suite: 8/8 PASS | `YdlPhase3ContextTest` |
| 6 | Architecture Charter Phase 3 checkbox ✅ | `memory/ydl/ARCHITECTURE_CHARTER.md` |
| 7 | CLI help metni doğru | `php artisan ydl:context --help` |
| 8 | PHASE 3 CERTIFIED yazısı yok — Phase 3'e kadar bekler | Phase 3 test suite green |

---

## Phase 3 Pipeline

```
Oturum Başı                          Oturum Sonu
    │                                     │
    ▼                                     ▼
ydl:context ──► YdlContextReader ──► ydl:session-summary
                │ (state oku)              │ (event üret)
                │                            ▼
                │                       ydl:apply --dry-run
                │                            │ (plan görülür)
                │                            ▼
                │                       git commit
                │                            │
                └──────────────────────────► ydl:apply --confirm
                                                 │ (G3c geçer)
                                                 ▼
                                           Memory Güncellenir
                                                 │
                                                 ▼
                                           Event Log'a eklenir
```

---

## Dependency Chain

```
Phase 2 (✅ COMPLETE)
    │
    ├── YdlStatePatcher         — patch üretir
    ├── YdlWriteGuard           — 4 gate
    ├── YdlControlledWriter     — writes
    └── YdlEventLog             — append-only store
            │
            ▼
Phase 3 (🔲 IN PROGRESS)
    │
    ├── YdlContextReader        ← state okur
    ├── ydl:context CLI         ← agent erişir
    ├── ydl session-summary     ← event üretir
    └── CLAUDE.md injection     ← context enjekte
            │
            ▼
Phase 4 (🔲 FUTURE)
    │
    ├── Otomatik shutdown hook
    ├── CI/CD pipeline trigger
    └── Agent otomatik write
```
