# PR #1 vs RC2 Soy Ağacı — Lineage Map
**Tarih:** 2026-09-13  
**Kanıt:** `gh pr view`, `git log`, `git merge-base`, `git diff`  
**Seviye:** REPO_VERIFIED

---

## Lineage Ağacı (Kesin)

```
MERGE BASE (2026-07 öncesi)
6967cb25 ── docs: Sprint 16 Charter + M2 milestone + PROGRESS-TRACKER update
    │
    ├── PR#1: integration/era-v-phase2a-e01 ── 395 commit ── son: 41301042 (2026-09-03)
    │   └── RC2: release-candidate/RC2 ────── +177 commit ── son: 3638a978 (2026-09-12)
    │       └── RC2 dirty ──────────────────── 63 dosya unstaged (2026-09-13)
    │
    └── main (CANLI) ────────────────────── ~50 commit ilerledi ── son: d4ee6919 (2026-09-12)
        └── feat(legal): Meta App Publish compliance pages
        └── chore(ops): Cloudflare legal pages bypass script

RC2, PR#1'den 177 commit ILERDE
PR#1, main'den 509 commit GERİDE
RC2, main'den 509 commit İLERİDE (PR#1 + 177 + yeni)
```

---

## PR #1 Karakteristikleri

| Özellik | Değer |
|---------|-------|
| Açılış | 2026-07-30 |
| Son güncelleme | 2026-09-03 |
| Toplam commit | 395 |
| Değişen dosya (GitHub) | **1,035** |
| Eklenen satır | +186,949 |
| Silinen satır | -8,330 |
| Merge hedefi | `main` (eski) |
| Değişen dosya tipleri | 632 PHP, 169 MD, 127 YML, 31 PNG, 14 JSON, 12 JS, ... |
| Merge durumu | CONFLICTING + DIRTY |
| Review kararı | YOK |
| CI durumu | YOK |

---

## PR #1 Dosya Kategorileri (main'e göre)

| Kategori | Sayı | Örnek |
|----------|------|-------|
| PHP Backend | 632 | `app/Infrastructure/ChannelManager/Airbnb/*`, `app/Jobs/Reservation/*` |
| Test | ~150 | `tests/Feature/Ydl/*`, `tests/Feature/Reservation/*` |
| Migration | ~20 | `database/migrations/2026_*` |
| Doküman | 169 | `docs/SAB/*`, `docs/adr/*` |
| Config/Route | ~15 | `config/*.php`, `routes/*.php` |
| Agent/Skill | ~10 | `.agents/skills/*`, `.clinerules` |
| CI/CD | 127 | `.github/workflows/*`, `.playwright-mcp/*` |
| Asset/Medya | ~50 | PNG, JPG dosyaları |

---

## RC2 Karakteristikleri (release-candidate/RC2)

| Özellik | Değer |
|---------|-------|
| Dirty dosya | **63** (değil 74!) |
| PHP | 29 |
| Markdown | 21 |
| Diğer | 13 (JS, JSON, SH, dirs) |
| Migration (modified) | 5 |
| Seeder (modified + deleted) | 3 |
| Config/Route | 4 |
| Agent/Skill | 4 |
| Project-brain | 4 + 8 untracked |

### RC2 Dirty Dosya Detayı

**Modified — Migration:**
- `2026_08_04_230600_create_kategori_yayin_tipi_field_dependencies_table.php`
- `2026_08_23_000002_create_c51_settlement_domain_tables.php`
- `2026_08_23_000004_create_bank_accounts_table.php`
- `2026_08_24_000001_create_workforce_executions_table.php`
- `2026_09_04_173133_add_unique_composite_index_to_ilan_fotograflari.php`

**Modified — Seeder:**
- `database/seeders/DatabaseSeeder.php`

**Deleted — Seeder:**
- `database/seeders/OzellikKategoriSeeder.php`
- `database/seeders/PropertyHubOzelliklerSeeder.php`

**Modified — Config/Route:**
- `config/feature-flags.php`
- `config/location.php`
- `routes/admin.php`
- `routes/admin/talepler.php`

**Modified — Agents/Brain:**
- `.agents/skills/SKILL_INDEX.md`
- `.agents/skills/api-contract-envelope-guardian/SKILL.md`
- `.agents/skills/blade-alpine-runtime-guardian/SKILL.md`
- `.agents/skills/multi-agent-worktree-sandbox/SKILL.md`
- `.project-brain/DECISION_LOG.md`
- `.project-brain/EVIDENCE_INDEX.md`
- `.project-brain/KNOWN_ISSUES.md`
- `.project-brain/PROJECT_STATE.md`
- `.sab/authority.json`

**New — Domains/Application:**
- `app/Application/Ilan/`
- `app/Domain/Ilan/Policies/`
- `app/Domain/Ilan/ValueObjects/`
- `app/Domain/Location/`
- `app/Listeners/Wizard/`

---

## Conflict Analizi

### Neden PR #1 CONFLICTING + DIRTY?

PR#1 `main`'e göre 509 commit geride. Main bu sürede:
1. Cloudflare legal bypass script ekledi (`d4ee6919`)
2. Meta App compliance sayfaları ekledi (`384850f8`)
3. Sprint 16 Charter dokümantasyonu (`6967cb25`)

Bu 509 commit'lik fark, PR#1'in değiştirdiği 632 PHP + 127 YML dosyasının bir kısmıyla çakışıyor.

### Gerçek Conflict Miktarı

GitHub'a göre `1,035` dosya değişmiş. Bu dosyaların bir kısmı:
- Artık main'de farklı versiyonda
- Bazı dosyalar main'de tamamen silinmiş olabilir
- YML workflow dosyaları main'de güncellenmiş olabilir

---

## Karar Matrisi: PR #1 Ne Yapmalı?

| Seçenek | Avantaj | Dezavantaj | Risk |
|---------|---------|-----------|------|
| **A) PR#1'i kapat + RC2'den yeni PR** | Temiz, kontrollü, 509 commit'i parçalara böl | Yeniden PR açılmalı | Düşük — zaten PR#1 çalışmıyor |
| **B) PR#1'e RC2'yi rebase et** | Commit geçmişi korunur | 1035 dosya rebase = saatler | Çok yüksek — rebase conflict felaket |
| **C) PR#1'i main'e squash-merge** | Tek commit, temiz | 186K satır + 509 commit kaybı | Orta — history gider |
| **D) PR#1'i beklet, RC2'yi main'e ayrı PR** | PR#1'ten değerli parçalar alınabilir | İki ayrı PR = koordinasyon | Orta |

---

## Önerilen Yol

```
ADIM 1: PR #1 kapat (Close, not merge)
ADIM 2: RC2 dirty dosyaları analiz et → sahiplik belirle
ADIM 3: RC2'yi temiz commit'le (staged + commit disiplini)
ADIM 4: RC2 → main için yeni PR (theme/theme bazlı paketler)
ADIM 5: Paralel olarak: PR#1 içinden değerli SKILL dosyalarını al (opsiyonel)
```

---

## RC2 Dirty Sahiplik Tahmini

| Sahip/Kategori | Dosya sayısı | Not |
|----------------|-------------|-----|
| Bu oturum (Cline) | 4 skill + 1 SKILL_INDEX | `.agents/skills/*` |
| Bilinmiyor | 8 project-brain | `.project-brain/*` |
| Bilinmiyor | 5 migration + 1 seeder | `database/` |
| Bilinmiyor | 4 config + 2 route | `config/`, `routes/` |
| Bilinmiyor | Domain dirs + ADR | `app/`, `docs/adr/` |
| Bilinmiyor | Tests dirs | `tests/Feature/Location/` |

**Öncelik:** Migration + Seeder dosyaları İNSAN ONAYI gerektirir. Config/Route dosyaları incelenmeli.
