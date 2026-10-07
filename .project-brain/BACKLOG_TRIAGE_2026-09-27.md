# YALIHAN OS — Backlog Triyaj Raporu
**Tarih:** 2026-09-27
**Karar Sahibi:** Ayhan (oturum)
**Baseline:** `release-candidate/RC2` (16e1a31a)

---

## Triyaj Kararı: Kapatılan İşler (Yeniden Açılmayacak)

Aşağıdaki işler mevcut evidence'e göre kapanmış veya stale. Backlog'a tekrar sokulmayacaklar.

| # | Konu | Kapanış Nedeni |
|---|---|---|
| 1 | Canonical Integrity Gate _14B | CLOSED ✅ — 16e1a31a |
| 2 | Bekçi command shadowing _14A | CLOSED ✅ |
| 3 | Sentinel <70 health authority | CLOSED ✅ |
| 4 | PropertyReservation modify/cancel tenant isolation | CLOSED ✅ |
| 5 | Bulk import NULL coordinate semantics | CLOSED ✅ |
| 6 | Public resource lat/lng/null-coordinate contract | CLOSED ✅ |
| 7 | Public Ilan test tenant-context correction | CLOSED ✅ |
| 8 | Canonical local bootstrap | CLOSED ✅ |
| 9 | Admin role authority | CLOSED ✅ |
| 10 | Feature seeder duplicate authority | CLOSED ✅ |
| 11 | ADR #006 Emlak Proje / Team Proje ayrımı | CLOSED ✅ — intentional bounded-context ayrımı |
| 12 | User.is_active eski finding | STALE_FINDING |
| 13 | TenantContextService lifecycle | POTENTIAL_NOT_REPRODUCED |
| 14 | IlanService session/default tenant fallback | SAFE_BY_LIFECYCLE |
| 15 | Hermes Dashboard untracked çalışma | STALE / INCOMPLETE |

**Özel not:** ADR #006 "projeler ve emlak_projeleri birlikte var" şeklinde yeniden açılmayacak. Bu intentional bounded-context ayrımıdır.

---

## Aktif Remediation Kuyruğu (2026-09-27 İtibarıyla)

İlk dört işle sınırlı. Hygiene/security/runtime grubu sonra.

| # | Konu | Mevcut Durum | Sonraki Hareket |
|---|---|---|---|
| **C3** | Financial Snapshot | 🔴 Reproduce edilmiş eski failure; current HEAD teyidi bekliyor | Read-only current-state + reproduction |
| **F01** | CRM/Kisi tenant isolation | 🔴 HIGH_VALUE_FINDING_CANDIDATE | Runtime tenant bypass reproduce et |
| **85** | SAB Guard ihlali | 🟠 Gerçek blocking output var ama 85 ayrı defect olduğu kanıt değil | Read-only clustering/root-cause triage |
| **R1** | Reservation duplicate authority | 🟠 DUPLICATE_AUTHORITY_CANDIDATE | IlanReservation ↔ PropertyReservation runtime authority doğrula |

### Ardından (Hygiene/Security/Runtime Grubu)

| # | Konu | Mevcut Durum | Sonraki Hareket |
|---|---|---|---|
| 5 | Repository hygiene | 🟡 Gerçek temizlik ihtiyacı var | KEEP/IGNORE/ARCHIVE/DELETE_CANDIDATE manifest |
| 6 | ENV Git-history exposure | 🟠 POTENTIAL_HISTORY_EXPOSURE | Remote exposure + credential liveness read-only araştır |
| 7 | Legacy migrations retirement | 🟡 Historical lineage duruyor | Ayrı retirement audit; toplu silme yok |
| 8 | Production canonical DB/schema audit | ⚠️ UNKNOWN | Deploy öncesi read-only production audit |
| 9 | Scheduler dead-command adayları | 🟠 Eski evidence var | Current HEAD runtime/signature reproduction |
| 10 | Public Ilan resource field drift | 🟠 Güçlü candidate | HTTP/resource reproduction |
| 11 | Cortex/Report lat/lng drift | 🟠 Güçlü candidate | Current runtime reproduction |
| 12 | YalihanCortex generateIlanTitle() | 🟠 Önceki forensic finding | Current HEAD call-path doğrulaması |
| 13 | FeatureAssignmentMigrationTest legacy drift | 🟡 Test debt | Daha yüksek impact işleri bittikten sonra |
| 14 | AI telemetry / tenant parity | 🟡 Eski blocked çalışma | Current-state yeniden doğrulama gerekli |
| 15 | ActionCenter idempotency race | 🟠 Çözülmemiş mimari konu | Ayrı bounded architecture decision |
| 16 | Hermes correlation chain | 🟡 Schema/runtime audit gerekli | Read-only current-state |
| 17 | Production APP_DEBUG effective authority | 🟠 Önceki production observation var | Production read-only runtime authority audit |
| 18 | Danışman/User production schema F003/F004 | ⚠️ BLOCKED_INSUFFICIENT_EVIDENCE | Production DB evidence olmadan işlem yok |

---

## Temizlik Sınıflandırması (Repository Hygiene Wave 1)

### Repository Artifact Temizliği
`.playwright-mcp/`, test/log/dump çıktıları, root/audit screenshot kümeleri, eski worktree artifactları.
→ Önce referans/reproducibility açısından sınıflandırılmalı.
→ `*.png` **global ignore yapılmamalı** — screenshot evidence olabilir.

### Secret Hygiene
- Mevcut `.env.backup`, `.env.prod.*` dosyaları — ignored/untracked ama credential kategorileri içeriyor
- Eski iki env backup Git history'de bulunuyor
- Remote exposure + credential liveness: **UNKNOWN**
→ Normal "dosya temizliği" değildir.

### Legacy/Code Hygiene
Deprecated stub'lar, duplicate model adayları, eski compatibility katmanları, orphan adayları.
→ `grep bulamadı → sil` yaklaşımı **YASAK**.

---

## Daha Büyük Resim: Production Doğrulama Açığı

Local/repository tarafı: Schema baseline → controlled migration lineage → canonical seeders → bootstrap → model/contracts → Sentinel FAST → Doktor DEEP → Bekçi telemetry

**Production aynı seviyede doğrulanmış değil.**

Gelecekte "YALIHAN OS canonical dönüşüm tamamlandı" denebilmesi için:
1. Production Read-Only Audit
2. Compatibility check
3. Ayhan Human Gate
4. Gerekli deploy/migration
5. Independent production verification

**Mevcut durum:** Birçok yerde production evidence hâlâ **UNKNOWN**.

---

## Çalışma Ritmi Önerisi (Ayhan-agreed)

1. ~~Yeni forensic alan açmayalım~~
2. C3'ü sonuçlandır → fix gerekiyorsa bounded fix + regression + verifier + commit
3. F01'i reproduce et
4. 85 SAB çıktısını kümelendir
5. Reservation authority doğrula
6. Ardından Repository Hygiene Cleanup Wave 1

**Amaç:** Daha fazla finding üretmek değil, kuyruğu küçültmek.

---

## Dikkat: Eski Evidence Uyarısı

Daha eski kayıtlarda bulunan bazı ifadeler ("schema baseline migrationların gerisinde", "ADR #006 production migration bekliyor") bugünkü repository gerçeği olarak kullanılmamalı. Bunlar daha eski evidence dönemine ait. Güncel canonical schema checkpoint ve migration-boundary çalışmaları sonradan geldi. Production tarafı: **UNKNOWN**.
