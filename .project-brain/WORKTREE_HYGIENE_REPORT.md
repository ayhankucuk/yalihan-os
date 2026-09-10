# Worktree Hijyen Raporu — 2026-09-10

**Üreten:** Cline (cline/worktree-audit-hygiene)  
**Tarih:** 2026-09-10  
**Kaynak Commit:** 928a1459 (`release-candidate/RC2`)  
**Tarama:** Tüm 38 worktree, git status + merge-base analizi

---

## Özet

| Metrik | Değer |
|---|---|
| Toplam worktree | 38 |
| RC2'ye merge edilmiş | 1 (`confirmed-nigella`) |
| RC2'ye merge EDİLMEMİŞ | 37 |
| Kirli (uncommitted değişiklik) | 13 worktree + 1 ana çalışma ağacı |
| Aktif RC dalı (korunmalı) | 2 (`release-candidate/RC2`, `release/era-v-phase2a-rc1`) |

---

## Kategoriler

### 🟢 BUDANABİLİR (Safe to Prune)

**Kriter:** RC2'ye merge edilmiş VEYA >30 gün aktif olmayan VEYA tamamen terk edilmiş.

| Worktree | Branch | Son Commit | Durum |
|---|---|---|---|
| `.kilo/worktrees/confirmed-nigella` | `confirmed-nigella` | 2026-07-23 (49 gün) | **RC2'ye merge edilmiş** — bayat |
| `yalihan-os-9yphh` | `worktree/roo-9yphh` | 2026-08-04 (37 gün) | RC2'ye merge edilmemiş — terk edilmiş görünüyor |

**Neden budanabilir:** `confirmed-nigella` RC2'ye entegre edilmiş ve 49 gündür aktif değil. `yalihan-os-9yphh` 37 gündür commit almıyor ve RC2'ye hiç entegre edilmemiş.

---

### 🟡 BEKLEYEN DEĞİŞİKLİK (Dirty / Uncommitted)

**Kriter:** `git status --short` çıktısında uncommitted değişiklik var. Silinmemeli — önce içerik değerlendirilmeli.

| Worktree | Kirli Dosyalar | Önem |
|---|---|---|
| **Ana çalışma ağacı** (`yalihan-os`) | `app/Models/Ilan.php` | ⚠️ **Klitonian değişiklik — dikkat!** |
| `yalihan-os.option-a-merge` | `app/Models/Ilan.php` + 4 yeni migration | ⚠️ Schema değişikliği — içerik incelenmeli |
| `yalihan-os.codex-schema-integration` | Governance manifest + 5 ilan fotoğrafı | Fotoğraf + governance artifact |
| `yalihan-os.kilo-v2-tenant-isolation` | Governance manifest + 4 ilan fotoğrafı | Fotoğraf + governance artifact |
| `yalihan-os.kronik1-schema-sync` | Governance manifest + 5 ilan fotoğrafı | Fotoğraf + governance artifact |
| `client-lead-tenant-boundary` | Governance manifest + 4 ilan fotoğrafı | Fotoğraf + governance artifact |
| `yalihan-os.phase2-slug-mapping` | `Observer.php`, `Ups/`, migration, seeder, test | ⚠️ Aktif kod değişikliği |
| `codex-ilceler-foreign-key` | Migration + test + IMPACT_ANALYSIS | ⚠️ FK migration dosyası |
| `codex-constitution-review-workflow` | `.agents/`, `.clinerules`, brain dosyaları | Agent dokümantasyonu |
| `codex-antigravity-browser-runtime-certification` | `tests/e2e/...spec.ts` | E2E test değişikliği |
| `kilo-release-merge` | `mcp-servers/yalihan-bekci-mcp.js` | MCP sunucu değişikliği |

**Neden dikkat gerektirir:** Bu worktree'lerdeki değişiklikler ya yeni kod, ya da schema migration içerebilir. Silme öncesi içerik review zorunlu.

---

### 🔴 KORUNMALI (Release / RC Dalı)

**Kriter:** Aktif RC/release dalı veya ana geliştirme ağacı. Kesinlikle silinmez.

| Worktree | Branch | Son Commit | Durum |
|---|---|---|---|
| `yalihan-os` (ana) | `release-candidate/RC2` | 2026-09-10 | **ANA ÇALIŞMA AĞACI** |
| `worktrees/release-candidate` | `release/era-v-phase2a-rc1` | 2026-09-04 | RC dalı — KRONIK-1 içeriyor |

---

### ⚪ AKTİF DALLAR (RC2'ye Entegre Edilmemiş — Değerlendirme Gerekli)

**Kriter:** RC2'ye merge edilmemiş, ancak commit almış. Bunların RC2'ye entegre edilip edilmeyeceği kararı bekleniyor.

| Worktree | Branch | Son Commit | Not |
|---|---|---|---|
| `kilo-doctor-hardening` | `kilo/doctor-hardening-v1` | 2026-09-09 | Doctor v1.1'in kaynağı — RC2'ye PUSH edilmiş mi? |
| `codex-feature-failure-triage` | `codex/feature-failure-triage` | 2026-09-09 | Feature test izolasyonu |
| `codex-antigravity-browser-runtime-certification` | `codex/antigravity-browser-runtime-certification` | 2026-09-09 | Browser E2E kanıt |
| `antigravity-pkg2-bekci-tenant-audit` | `antigravity/pkg2-bekci-tenant-audit` | 2026-09-08 | Bekçi audit |
| `antigravity-pkg3-engineering-skills` | `antigravity/pkg3-engineering-skills` | 2026-09-08 | Agent skill kaydı |
| `kilo-tenant-backfill` | `kilo/tenant-backfill` | 2026-09-08 | Tenant isolation |
| `ilan-bugs-fix` | `cline/ilan-bugs-fix` | 2026-09-08 | İlan hata düzeltmesi |
| `cline-security-backlog-docs` | `cline/security-backlog-docs` | 2026-09-04 | Güvenlik doküman |
| `kilo-v2-tenant-isolation` | `kilo/v2-tenant-isolation` | 2026-09-08 | Tenant v2 |
| `antigravity-governance-pilot` | `antigravity/pkg1-doc-lifecycle-pilot` | 2026-09-08 | GOV-DOC-001 |
| `kronik1-schema-sync` | `codex/kronik1-schema-sync` | 2026-09-03 | **KRONIK-1 aktif worktree** |
| `property-hub-repair-safety` | `codex/property-hub-repair-safety` | 2026-09-02 | Provenance düzeltmesi |
| `antigravity-adr042-revision` | `antigravity/adr042-revision-and-doc-fixes` | 2026-09-08 | ADR mimari doküman |
| `client-schema-migration-recovery` | `client/schema-migration-recovery` | 2026-09-04 | Migration recovery |
| `kilo-release-merge` | `kilo/578f204-merge` | 2026-09-02 | Merge dalı |
| `kilo-stale-ref-cleanup` | `kilo/stale-ref-cleanup` | 2026-09-03 | Test düzeltmesi |
| `kilo-yayintipi-fillable-fix` | `kilo/yayintipi-fillable-fix` | 2026-09-03 | YayinTipi model düzeltmesi |
| `phase2-release` | `codex/phase2-release` | 2026-09-02 | Phase 2 release |
| `phase2-integration-candidate` | `codex/phase2-integration-release` | 2026-08-29 | Phase 2 entegrasyon |
| `phase1-resolver` | `feature/phase1-resolver-inheritance` | 2026-09-01 | Phase 1 resolver |
| `phase2-slug-mapping` | `feature/phase2-slug-mapping-seeder` | 2026-08-25 | Slug mapping seeder |
| `tenant-isolation-bekci` | `codex/tenant-isolation-bekci` | 2026-09-07 | Tenant izolasyon bekçi |
| `codex-ilceler-foreign-key` | `codex/ilceler-foreign-key` | 2026-09-06 | İlçe FK |
| `codex-schema-contract` | `codex/canonical-schema-contract-fix` | 2026-09-03 | Schema kontrat |
| `codex-schema-contract-final` | `codex/schema-contract-final` | 2026-09-04 | Schema kontrat final |
| `codex-schema-baseline` | `codex/schema-drift-baseline` | 2026-09-03 | Schema baseline |
| `codex-release-validation` | `codex/release-validation` | 2026-09-03 | Release validation |
| `codex-project-plan` | `codex/project-completion-plan` | 2026-09-03 | Project plan |
| `codex-worktree-standard` | `codex/worktree-isolation-standard` | 2026-09-03 | Worktree standart |
| `client-lead-tenant-boundary` | `client/lead-tenant-boundary` | 2026-09-04 | Lead tenant boundary |
| `antigravity-pkg2-bekci-tenant-audit` | `antigravity/pkg2-bekci-tenant-audit` | 2026-09-08 | Tenant audit (dup — aynı WT) |
| `codex-constitution-review-workflow` | `codex/constitution-review-workflow` | 2026-09-09 | AGENTS.md review |

---

## Önemli Bulgular

### 🔴 Kritik: 36/38 worktree RC2'ye Entegre Edilmemiş

Sistemin en büyük sorunu: neredeyse tüm worktree dalları (`confirmed-nigella` hariç) hiçbir zaman `release-candidate/RC2`'ye merge edilmemiş. Bu şu anlama geliyor:

1. **Toplam 36 branch** ayrı geliştirme yapıyor, hiçbiri RC2'ye entegre olmamış.
2. `kilo-doctor-hardening` (b191e3cf) — RC2'nin `yalihan-doctor.sh` v1.1'inin kaynağı olabilir; ancak RC2'ye PR/merge yapılmamış.
3. `kilo-yayintipi-fillable-fix` (a29a5c5a) — `YayinTipi.php` ghost field düzeltmesi zaten yapılmış; ancak RC2'ye entegre edilmemiş.
4. `kilo-release-merge` (578f204 merge) — `phase2-release`'i RC2'ye entegre etmeye çalışıyor gibi görünüyor; ancak tamamlanmamış.

### 🟡 Kirli Worktree'lerdeki Değişiklikler

Toplam **13 worktree + 1 ana çalışma ağacı** kirli. Kirli worktree'lerin önemli bir kısmı sadece `storage/app/public/ilan-fotograflari/` ve `storage/app/governance/` dosyalarında değişiklik gösteriyor — bunlar büyük ihtimalle fotoğraf yüklemesi veya governance artifact.

### ⚠️ Ana Çalışma Ağacı Kirli

`app/Models/Ilan.php` — muhtemelen Klio'nun kanıt-tabanlı incelemesinden kalan bir değişiklik. **Acil inceleme gerekmez** (RC2 temiz, pushlanmış durumda), ancak kaydedilmemiş.

---

## Önerilen Eylem Planı

### Acil (Onay Gerekmez — Hijyen)
- [ ] Ana çalışma ağacındaki `app/Models/Ilan.php` değişikliğini ya commit ya da `git checkout app/Models/Ilan.php` ile temizle

### Onay Sonrası Uygula
1. [ ] `confirmed-nigella` worktree'yi kaldır — 49 gündür bayat, RC2'ye entegre edilmiş
2. [ ] `yalihan-os-9yphh` worktree'yi kaldır — 37 gündür aktif değil, hiç entegre edilmemiş

### Değerlendirme Gerekli (Karar Bekler)
1. [ ] `kilo-doctor-hardening` → `release-candidate/RC2` ile ilişkisi nedir? Doctor v1.1'in kaynağı mı?
2. [ ] `kilo-yayintipi-fillable-fix` → `YayinTipi.php` düzeltmesi RC2'dé zaten var mı? Varsa bu WT gereksiz.
3. [ ] `kilo-release-merge` → 578f204 merge işlemi tamamlanmış mı? Değilse tamamlanabilir mi?
4. [ ] 27 diğer aktif dal → hangileri birleştirilmeli, hangileri terk edilmeli?

---

## Katı Kısıtlamalar

> ⚠️ **SİLME YOK.** Bu rapor salt okunurdur. Yukarıdaki "Acil" ve "Onay Sonrası" maddeleri kullanıcı onayı olmadan **UYGULANMAZ**.
