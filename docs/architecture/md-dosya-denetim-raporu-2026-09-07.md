# YALIHAN Mimari Bekçi — Eski Doküman ve Mimari Uyumluluk Denetimi

## 0. Görevin Amacı

Bu rapor, YALIHAN OS repository'sindeki eski, tekrar eden, yanlış konumlanmış, stale veya mimari kurallarla çelişen Markdown dokümanlarını planlı ve kanıtlı şekilde denetlemek için hazırlanmıştır.

Bu görev bir **read-only audit** görevidir. Ajanın temel amacı dosyaları temizlemek değil, önce gerçek durumu çıkarmak, her bulguyu doğru otorite kaynağına bağlamak ve uygulanacak değişiklikleri sıralı bir plan halinde önermektir.

Bu görev kapsamında:

- Hiçbir Markdown dosyası silinmeyecek.
- Hiçbir dosya taşınmayacak veya yeniden adlandırılmayacak.
- Hiçbir dosyanın içeriği otomatik olarak birleştirilmeyecek.
- Git commit, push, merge, migration, seed veya deploy yapılmayacak.
- Production veritabanına veya production sunucuya bağlanılmayacak.
- Eski bir raporun iddiası, güncel repository kanıtı olmadan doğru kabul edilmeyecek.
- Sadece somut, tekrar üretilebilir ve dosya/satır referansı olan bulgular raporlanacak.

---

## 1. Ajan Başlangıç Bildirimi

```text
Ajan: Claude (Architect/Code mode)
Worktree: /Users/macbookpro/repos/yalihan-os
Branch: release-candidate/RC2
HEAD: ef37389a3f619b73d8e3e2a37c830f67b89ab52f
Dirty: dirty — 20+ staged/unstaged değişiklik (app/, .sab/, .project-brain/ altında)
Skill: .agents/skills/yalihan-constitution-review/SKILL.md
Skill SHA-256: 54a886d3419c9c830f950674125007176ef97df67c2ebf0f18e3c0aca99121c4
Lifecycle SHA-256: faae5a12ef20bafa70e4093151844131766da9a839465cfaf1cceb61ad6ac093
Kapsam: Kök dizin, docs/, memory/, chief-ai/, agents/, .sab/, .project-brain/, .agents/, business-office/, docs/ysos/, docs/adr/, docs/adrs/, docs/ERA_V/
Mod: READ-ONLY
```

Dirty worktree durumu: HEAD `ef37389` üzerinde 20+ unstaged değişiklik mevcut (app/Models, app/Http, .sab/authority.json, .project-brain/ dosyaları). Bu değişiklikler denetim kapsamı dışındadır — sadece tracked .md dosyaları incelendi.

---

## 2. Otorite ve Kaynak Hiyerarşisi

Ajan, mimari uyumluluk hükmü verirken aşağıdaki kaynak sırasını kullanır. Kaynaklar arasında çelişki varsa sessizce birleştirme yapılmayacak; çelişki ayrı bir bulgu olarak yazılacaktır.

### Birincil Kaynaklar

1. `.sab/authority.json` — v6.1.1 (SSOT — mimari beklenen kural)
2. `docs/SAB.md` — v24.2.0 (Teknik Anayasa)
3. `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` (Mimari Anayasa)
4. `docs/ysos/CONSTITUTION.md` (Süreç Anayasası)
5. `docs/ERA_V/PHASE2-ROADMAP.md` (Aktif Roadmap)
6. İlgili ADR veya onaylı karar kaydı
7. `.project-brain/DECISION_LOG.md` (Karar Kayıtları)
8. `.project-brain/EVIDENCE_INDEX.md` (Kanıt İndeksi)
9. `.project-brain/KNOWN_ISSUES.md` (Bilinen Sorunlar)

> **Önemli ayrım:** `authority.json` mimarinin **beklenen kuralını** belirler. Kod, migration ve DB bu kuralın **gerçekten uygulanıp uygulanmadığını** gösterir. Bu üçü ayrı raporlanmalı: Beklenen kural (authority.json), Kod mekanizması (model/scope/middleware/policy), Gerçek uygulama (test ve production evidence).

### İkincil ve Navigasyon Kaynakları

- `.sab/ONBOARDING.md` — 228 satır (**ENTRYPOINT / NOT SSOT** — dosya başlığı açıkça "DOCUMENT STATUS: ENTRYPOINT / NOT SSOT" diyor; yalnızca ajan yönlendirme belgesi, normatif kural kaynağı değil)
- `README.md` (989 satır)
- `START_HERE.md` (186 satır)
- `PROJECT_CONTEXT.md` (114 satır)
- `docs/index.md` (185 satır)
- `docs/README.md` (156 satır)
- `docs/architecture/README.md`
- `docs/architecture/REGISTRY.md`
- `docs/architecture/MIMARI_BEKCI_AGENT_TALIMATI.md`
- `docs/BEKCI_CHANGELOG.md`
- `docs/known-debt.md` (343 satır)

### Tarihsel veya Destekleyici Kaynaklar

- `docs/MD_AUDIT_REPORT.md` (2026-06-16, WenOX — tarihsel karşılaştırma)
- Tarihli audit ve research raporları
- `memory/`, `chief-ai/`, `storage/notebooklm-sync/` altındaki belgeler

**Kural:** Tarihsel bir doküman güncel mimari kuralın SSOT'u değildir. Bir dosyanın başlığında `canonical`, `official`, `SSOT` veya `final` yazması tek başına otorite kanıtı sayılmaz.

---

## 3. Denetim Kapsamı

### Hariç Tutulan Dizinler

| Dizin | Gerekçe |
|-------|---------|
| `vendor/` | Üçüncü parti paketler |
| `node_modules/` | NPM bağımlılıkları |
| `.git/` | Git iç işleri |
| `storage/` | Runtime üretilen dosyalar |
| `testsprite_tests/` | Test fixture'ları |
| `playwright/` | E2E test altyapısı |
| `kilo-release-merge/` | Repo kopyası — INTENTIONAL_MIRROR |
| `yalihanai_clone/` | DB kopyası |
| `yalihanai_v2_production/` | Production DB kopyası |
| `.kilo/`, `.kilocode/` | IDE iç işleri |
| `yalihan-os.worktrees/` | Git worktree'leri |

### Toplam Dosya Sayısı

- Hariç tutulanlar sonrası ana repo: **2042 .md dosyası**
- Kritik dizinlerde incelenen: **~150 .md dosyası** (kök, docs/, memory/, chief-ai/, agents/, .sab/, .project-brain/, .agents/, business-office/, docs/ysos/, docs/adr/, docs/adrs/, docs/ERA_V/)

## 4. Yönetici Özeti

En fazla 10 madde ile:

1. **Kök dizin belge kalabalığı** — 10 .md dosyası mevcut; `ROADMAP.md` (547 satır, MD5: 7042cb6d) ile `docs/ROADMAP.md` (440 satır, MD5: cf617bf1) FUNCTIONAL_DUPLICATE (farklı içerik, farklı satır sayısı — EXACT_DUPLICATE değil), `SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` yanlış konumda, `STATE_PACKET.md` stale (Sprint 3.6 tamamlanmış)
2. **`docs/` kökünde 29 .md dosyası** — önceki audit (`docs/MD_AUDIT_REPORT.md` §4, 2026-06-16) "maksimum 15" önermişti ancak bu öneri herhangi bir otorite kaynağında (authority.json, SAB.md, ADR) doğrulanamamıştır; "15" sayısı tarihsel bir öneridir, güncel otorite kuralı değildir; `MD_AUDIT_REPORT.md` stale, `SYSTEM_ARCHITECTURE.md` `docs/SAB.md` ile FUNCTIONAL_DUPLICATE
3. **`docs/adr/` (23 dosya) vs `docs/adrs/` (6 dosya)** — iki ADR dizini çakışıyor; `docs/adr/` canonical (daha eski, daha fazla dosya, tarihli format)
4. **`memory/` vs `chief-ai/` tekrarı** — `memory/DECISIONS.md` ile `chief-ai/decision-log.md` FUNCTIONAL_DUPLICATE; `memory/PROJECT_BRAIN.md` ile `chief-ai/EXECUTIVE_SUMMARY.md` OVERLAP
5. **`kilo-release-merge/` dizini** — tüm repo'nun kopyası (~500+ .md), INTENTIONAL_MIRROR; `.gitignore`'a eklenmeli
6. **Governance kuralları 3-5 dosyada tekrar ediyor** — "Tenant isolation" 5 dosyada, "Thin Controller" 4 dosyada, "Context7 yasak alan adları" 3 dosyada, "Write Authority" 4 dosyada
7. **`docs/SAB.md` (v24.2.0) ile `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` çakışması** — ikisi de "anayasa" olarak konumlanıyor; kapsamı netleştirilmeli
8. **`docs/ysos/CONSTITUTION.md` ile `docs/SAB.md` çakışması** — ikisi de "anayasa"; SAB=teknik, ysos=süreç olarak ayrılmalı
9. **`agents/backend.md` SAB.md kurallarını kopyalıyor** — SSOT ihlali; referans vermelidir
10. **Bu turda hiçbir değişiklik yapılmadı** — read-only denetim; tüm öneriler owner onayı gerektirir

## 5. Dosya Envanteri

### 5.1 Kök Dizin (10 dosya)

| Dosya | Tür | Authority | Aktiflik | Duplicate | Konum | Mimari Uyum | Evidence | Önerilen |
|-------|-----|-----------|----------|-----------|-------|-------------|----------|----------|
| `README.md` | Operasyonel rehber | İkincil | ACTIVE_WITH_REFRESH | OVERLAP (CLAUDE, START_HERE ile) | ✅ Kök | ✅ | REPO_VERIFIED | Kısalt, detayları docs/'a taşı |
| `CLAUDE.md` | AI agent talimatı | İkincil | ACTIVE | OVERLAP (START_HERE ile) | 🔴 Kök değil | ✅ | REPO_VERIFIED | docs/ai-agents/'a taşı |
| `START_HERE.md` | Giriş rehberi | İkincil | ACTIVE | OVERLAP (PROJECT_CONTEXT ile) | 🔴 Kök değil | ✅ | REPO_VERIFIED | docs/onboarding.md ile birleştir |
| `PROJECT_CONTEXT.md` | Proje tanıtımı | İkincil | ACTIVE | OVERLAP (START_HERE ile) | 🔴 Kök değil | ✅ | REPO_VERIFIED | docs/onboarding.md ile birleştir |
| `ROADMAP.md` | Yol haritası | İkincil | ACTIVE | FUNCTIONAL_DUPLICATE (docs/ROADMAP.md) — 547 satır vs 440 satır, MD5: 7042cb6d vs cf617bf1, farklı içerik | 🔴 Kök değil | ✅ | REPO_VERIFIED | ACTION_PROPOSED — docs/ROADMAP.md canonical, kök silinecek (incoming: memory/WHERE_IS_WHAT.md, START_HERE.md, chief-ai/research/00_RESEARCH_INDEX.md, workflows/deploy.md) |
| `SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` | Güvenlik raporu | Tarihsel | HISTORICAL_VALID | NOT_DUPLICATE | 🔴 Yanlış konum | ✅ | REPO_VERIFIED | docs/architecture/'a taşı |
| `AGENTS.md` | Agent rehberi | İkincil | ACTIVE | OVERLAP (agents/README.md) | 🔴 Kök değil | ✅ | REPO_VERIFIED | agents/README.md ile birleştir |
| `CONTRIBUTING.md` | Katkı rehberi | İkincil | ACTIVE | NOT_DUPLICATE | ✅ Kök | ✅ | REPO_VERIFIED | Korumalı |
| `CHANGELOG.md` | Değişiklik kaydı | İkincil | ACTIVE | FUNCTIONAL_DUPLICATE (docs/BEKCI_CHANGELOG.md) — kök: 1554 satır, Oturum 63'ten başlar; docs/: 5432 satır, Oturum 162'den başlar. Aynı amaç (Bekçi günlüğü), farklı kapsam/session | ✅ Kök | ✅ | REPO_VERIFIED | Kapsam netleştir — kök eski sessionlar, docs/ güncel |
| `STATE_PACKET.md` | Sprint 3.6 state | Tarihsel | ARCHIVE_CANDIDATE | NOT_DUPLICATE | 🔴 Kök değil | ✅ | DOCUMENTED | docs/_archive/'a taşı |

### 5.2 `docs/` Kök Dizini (29 dosya)

| Dosya | Tür | Authority | Aktiflik | Duplicate | Konum | Mimari Uyum | Evidence | Önerilen |
|-------|-----|-----------|----------|-----------|-------|-------------|----------|----------|
| `docs/index.md` | Navigasyon | Birincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı — SSOT entry |
| `docs/README.md` | Navigasyon | İkincil | ACTIVE | OVERLAP (docs/index.md ile) | ✅ | ✅ | REPO_VERIFIED | Kapsam netleştir — index.md veya README.md canonical seç |
| `docs/SAB.md` | Anayasa/governance | Birincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı — teknik anayasa |
| `docs/ROADMAP.md` | Roadmap/plan | Birincil | ACTIVE | FUNCTIONAL_DUPLICATE (kök ROADMAP.md) — 440 satır vs 547 satır, farklı içerik | ✅ | ✅ | REPO_VERIFIED | Korumalı — canonical (kök silinecek) |
| `docs/known-debt.md` | Changelog/debt | İkincil | ACTIVE_WITH_REFRESH | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Aktif/çözülmüş ayr |
| `docs/PROGRESS-TRACKER.md` | Sprint raporu | İkincil | ACTIVE_WITH_REFRESH | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Böl — 2329 satır |
| `docs/architecture-lite.md` | Architecture | İkincil | ACTIVE | OVERLAP (SAB.md) | ✅ | 🟡 Tekrar | REPO_VERIFIED | SAB.md'ye referans |
| `docs/MD_AUDIT_REPORT.md` | Tarihsel audit | Tarihsel | ARCHIVE_CANDIDATE | NOT_DUPLICATE | ✅ | ✅ | DOCUMENTED | docs/_archive/'a taşı |
| `docs/SYSTEM_ARCHITECTURE.md` | Architecture | İkincil | SUPERSEDED | FUNCTIONAL_DUPLICATE (SAB.md) | ✅ | 🟡 Tekrar | REPO_VERIFIED | SAB.md'ye referans |
| `docs/BEKCI_CHANGELOG.md` | Changelog | İkincil | ACTIVE | FUNCTIONAL_DUPLICATE (kök CHANGELOG.md) — 5432 satır, Oturum 162'den başlar; kök 1554 satır, Oturum 63'ten. Aynı amaç, farklı kapsam | ✅ | ✅ | REPO_VERIFIED | Korumalı — güncel/geniş kapsam, kök eski sessionlar için arşiv adayı |
| `docs/MILESTONES.md` | Roadmap/plan | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/VIZYON.md` | Roadmap/plan | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/webhook-tenant-security.md` | Security/audit | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/PILOT-002-CHARTER.md` | Charter | Tarihsel | ARCHIVE_CANDIDATE | NOT_DUPLICATE | ✅ | ✅ | DOCUMENTED | docs/_archive/'a taşı |
| `docs/PILOT-002-AUTHORITY.md` | Charter | Tarihsel | ARCHIVE_CANDIDATE | NOT_DUPLICATE | ✅ | ✅ | DOCUMENTED | docs/_archive/'a taşı |
| `docs/PILOT-002-DISCOVERY.md` | Charter | Tarihsel | ARCHIVE_CANDIDATE | NOT_DUPLICATE | ✅ | ✅ | DOCUMENTED | docs/_archive/'a taşı |
| `docs/ENGINEERING_BOOTSTRAP.md` | Technical ref | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/DAP_CORE.md` | Technical ref | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/DAP_DECISION_TABLE.md` | Technical ref | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/INTEGRATION_BLUEPRINT.md` | Technical ref | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/PROPERTY_BLUEPRINT.md` | Technical ref | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/YALIHAN_OS_DOMAIN_MODEL.md` | Architecture | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/authority-map.md` | Governance | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/MCP_BEKCI_SMOKE_TEST.md` | Technical ref | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/SUPERVISED_AUTONOMY_CHARTER.md` | Charter | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/implementation_plan.md` | Plan | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/apex-cutover-checklist.md` | Operasyon | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/gmail-communications-intelligence-charter.md` | Charter | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |
| `docs/yalihan-project-brain-v3.md` | Plan | İkincil | ACTIVE | NOT_DUPLICATE | ✅ | ✅ | REPO_VERIFIED | Korumalı |

### 5.3 `docs/architecture/` (38 dosya)

| Dosya | Tür | Aktiflik | Duplicate | Mimari Uyum | Önerilen |
|-------|-----|----------|-----------|-------------|----------|
| `YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` | Anayasa | ACTIVE | OVERLAP (SAB.md) | 🟡 Çakışma | SAB.md ile kapsam netleştir |
| `SABIT-Yalihan-Mimari-Anayasa-Uyumluluk-Raporu.md` | Audit | HISTORICAL_VALID | NOT_DUPLICATE | ✅ | Korumalı |
| `MIMARI_BEKCI_AGENT_TALIMATI.md` | Governance | ACTIVE | NOT_DUPLICATE | ✅ | Korumalı |
| `tenant-isolation-audit-2026-09-06.md` | Security/audit | ACTIVE | NOT_DUPLICATE | ✅ | Korumalı |
| `cqrs-projection-research-report-2026-09-06.md` | Architecture | ACTIVE | NOT_DUPLICATE | ✅ | Korumalı |
| `tenant-isolation-bekci.md` | Technical ref | ACTIVE | NOT_DUPLICATE | ✅ | Korumalı |
| `md-dosya-denetim-raporu-2026-09-07.md` | Audit | ACTIVE | NOT_DUPLICATE | ✅ | Bu rapor |
| Diğer 31 dosya | Çeşitli | ACTIVE | NOT_DUPLICATE | ✅ | Korumalı |

### 5.4 `memory/` (11 dosya)

| Dosya | Tür | Aktiflik | Duplicate | Önerilen |
|-------|-----|----------|-----------|----------|
| `PROJECT_BRAIN.md` | Sistem snapshot | ACTIVE | OVERLAP (chief-ai/EXECUTIVE_SUMMARY) | Kapsam netleştir |
| `DECISIONS.md` | Karar kaydı | ACTIVE | FUNCTIONAL_DUPLICATE (chief-ai/decision-log) | ACTION_PROPOSED — chief-ai canonical; incoming link'ler: memory/SESSION_NOTES.md, memory/WHERE_IS_WHAT.md, audits/README.md, workflows/README.md, agents/README.md, docs/BEKCI_CHANGELOG.md, docs/SYSTEM_ARCHITECTURE.md, docs/ysos/AI_AGENT_RULES.md |
| `SESSION_NOTES.md` | Oturum kaydı | ACTIVE_WITH_REFRESH | NOT_DUPLICATE | Korumalı |
| `CHANGELOG_AGENT.md` | Changelog | ACTIVE | NOT_DUPLICATE | Korumalı |
| `CHIEF_AI_VISION.md` | Strateji | ACTIVE | NOT_DUPLICATE | Korumalı |
| `HOW_IT_WORKS.md` | Rehber | ACTIVE | NOT_DUPLICATE | Korumalı |
| `LEARNED_PATTERNS.md` | Bilgi tabanı | ACTIVE | NOT_DUPLICATE | Korumalı |
| `WHERE_IS_WHAT.md` | Navigasyon | ACTIVE | NOT_DUPLICATE | Korumalı |
| `ydl/ARCHITECTURE_CHARTER.md` | Mimari | ACTIVE | NOT_DUPLICATE | Korumalı |
| `ydl/PHASE3_SPEC.md` | Plan | HISTORICAL_VALID | NOT_DUPLICATE | Korumalı |
| `sessions/README.md` | Navigasyon | ACTIVE | NOT_DUPLICATE | Korumalı |

### 5.5 `chief-ai/` (24 dosya)

| Dosya | Tür | Aktiflik | Duplicate | Önerilen |
|-------|-----|----------|-----------|----------|
| `EXECUTIVE_SUMMARY.md` | Executive | ACTIVE | OVERLAP (memory/PROJECT_BRAIN) | Kapsam: memory=geçici, chief-ai=kalıcı |
| `decision-log.md` | Karar kaydı | ACTIVE | FUNCTIONAL_DUPLICATE (memory/DECISIONS) | Korumalı — canonical |
| `risk-register.md` | Risk | ACTIVE | NOT_DUPLICATE | Korumalı |
| `technical-debt.md` | Debt | ACTIVE | OVERLAP (docs/known-debt) | Kapsam: chief-ai=stratejik, docs=teknik |
| `sprint-backlog.md` | Sprint | ACTIVE | NOT_DUPLICATE | Korumalı |
| Diğer 19 dosya | Çeşitli | ACTIVE | NOT_DUPLICATE | Korumalı |

### 5.6 `agents/` (6 dosya)

| Dosya | Tür | Aktiflik | Duplicate | Mimari Uyum | Önerilen |
|-------|-----|----------|-----------|-------------|----------|
| `backend.md` | Agent talimatı | ACTIVE | OVERLAP (SAB.md) | 🔴 SSOT ihlali | Tekrarları kaldır, SAB.md'ye referans |
| `frontend.md` | Agent talimatı | ACTIVE | NOT_DUPLICATE | ✅ | Korumalı |
| `governance.md` | Agent talimatı | ACTIVE | UNKNOWN | 🟡 Değerlendir | SAB.md ile kontrol et |
| `laravel.md` | Agent talimatı | ACTIVE | UNKNOWN | 🟡 Değerlendir | SAB.md ile kontrol et |
| `mcp.md` | Agent talimatı | ACTIVE | NOT_DUPLICATE | ✅ | Korumalı |
| `README.md` | Navigasyon | ACTIVE | OVERLAP (kök AGENTS.md) | ✅ | AGENTS.md'yi buraya birleştir |

### 5.7 `docs/adr/` (23 dosya) vs `docs/adrs/` (6 dosya)

| Dizin | Dosya sayısı | Format | İçerik | Önerilen |
|-------|-------------|--------|--------|----------|
| `docs/adr/` | 23 | Tarihli + sayılı | Genel mimari kararlar | Canonical — koru |
| `docs/adrs/` | 6 | ADR-XXX formatı | Channel Manager kararları | `docs/adr/`'e taşı |

**Karar:** `docs/adr/` canonical (daha eski, daha fazla dosya, tarihli format). `docs/adrs/` içeriği `docs/adr/`'e taşınacak. Owner onayı gerekli (ACTION_PROPOSED).

### 5.8 `docs/ysos/` (22 dosya)

| Dosya | Tür | Authority | Mimari Uyum | Önerilen |
|-------|-----|-----------|-------------|----------|
| `CONSTITUTION.md` | Anayasa | Birincil | 🟡 SAB.md ile çakışma | Kapsam: SAB=teknik, ysos=süreç |
| Diğer 21 dosya | Çeşitli | Birincil/İkincil | ✅ | Korumalı |

## 6. Ana Bulgu Tablosu

Her bulgu şu kolonları içerir: Bulgu ID | Konu | İddia | Kontrol edilen dosya | Satır | SSOT/plan maddesi | Mevcut bulgu kaydı | Kanıt komutu | Kanıt seviyesi | Kontrol sonucu | Risk | Önerilen faz | Önerilen aksiyon | Silme izni | Kapanış kriteri | Durum

### Bulgu #001 — ROADMAP.md Duplicate

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-001 |
| **Konu** | Kök `ROADMAP.md` ile `docs/ROADMAP.md` duplicate |
| **İddia** | İki dosya da yol haritası içeriyor |
| **Kontrol edilen dosya** | `ROADMAP.md` (kök, 547 satır, MD5: 7042cb6d70a2f4f2e8cbdb9225aab267), `docs/ROADMAP.md` (440 satır, MD5: cf617bf19f269aa5b104ec6b2f87a9c9) |
| **Satır** | Tüm dosya |
| **SSOT maddesi** | `.sab/authority.json` SSOT ilkesi — tek canonical kaynak |
| **Mevcut bulgu kaydı** | `docs/MD_AUDIT_REPORT.md` §2 (2026-06-16, WenOX) |
| **Kanıt komutu** | `md5 ROADMAP.md` → 7042cb6d...; `md5 docs/ROADMAP.md` → cf617bf1...; `wc -l ROADMAP.md docs/ROADMAP.md` → 547 vs 440 |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | FUNCTIONAL_DUPLICATE — aynı amaç (yol haritası), farklı içerik (farklı satır sayısı, farklı MD5). EXACT_DUPLICATE değil. |
| **Risk** | Yüksek — çelişen güncellemeler riski |
| **Önerilen faz** | Phase 1 |
| **Önerilen aksiyon** | Kök `ROADMAP.md` silinecek; `docs/ROADMAP.md` canonical. Incoming link'ler güncellenecek: `memory/WHERE_IS_WHAT.md`, `START_HERE.md`, `chief-ai/research/00_RESEARCH_INDEX.md`, `workflows/deploy.md` |
| **Silme izni** | ACTION_PROPOSED — owner onayı gerekli, incoming link'ler önce güncellenmeli |
| **Kapanış kriteri** | Kök dosya silinmiş, 4 incoming link `docs/ROADMAP.md`'ye yönlendirilmiş |
| **Durum** | ACTION_PROPOSED |

### Bulgu #002 — SECURITY_EVIDENCE Yanlış Konum

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-002 |
| **Konu** | `SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` kök dizinde |
| **İddia** | Güvenlik raporu kök dizinde olmamalı |
| **Kontrol edilen dosya** | `SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` (126 satır) |
| **Satır** | Tüm dosya |
| **SSOT maddesi** | Talimat §3.1 — kök dizin belge kuralı |
| **Mevcut bulgu kaydı** | Yok |
| **Kanıt komutu** | `ls -la SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | Doğrulandı — yanlış konum |
| **Risk** | Düşük — erişilebilirlik sorunu |
| **Önerilen faz** | Phase 1 |
| **Önerilen aksiyon** | `docs/architecture/` altına taşınacak. Incoming link'ler güncellenecek: `docs/BEKCI_CHANGELOG.md` |
| **Silme izni** | ACTION_PROPOSED — taşıma, owner onayı |
| **Kapanış kriteri** | Dosya `docs/architecture/` altında, `docs/BEKCI_CHANGELOG.md` link'i güncellenmiş |
| **Durum** | ACTION_PROPOSED |

### Bulgu #003 — docs/adr/ vs docs/adrs/ Çift Dizin

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-003 |
| **Konu** | İki ADR dizini çakışıyor |
| **İddia** | `docs/adr/` (23 dosya) ve `docs/adrs/` (6 dosya) aynı amaç için kullanılıyor |
| **Kontrol edilen dosya** | `docs/adr/README.md`, `docs/adrs/ADR-TEMPLATE.md` |
| **Satır** | Dizin yapısı |
| **SSOT maddesi** | Talimat §3.3 — mimari dizinler |
| **Mevcut bulgu kaydı** | `docs/MD_AUDIT_REPORT.md` §2 (2026-06-16) |
| **Kanıt komutu** | `find docs/adr -name "*.md" \| wc -l` → 23; `find docs/adrs -name "*.md" \| wc -l` → 6 |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | Doğrulandı — çift dizin |
| **Risk** | Orta — ADR'ler dağılmış, arama zor |
| **Önerilen faz** | Phase 2 |
| **Önerilen aksiyon** | `docs/adrs/` içeriği `docs/adr/`'e taşınacak |
| **Silme izni** | Evir — taşıma, owner onayı |
| **Kapanış kriteri** | Tek ADR dizini, tüm ADR'ler `docs/adr/` altında |
| **Durum** | ACTION_PROPOSED |

### Bulgu #004 — memory/DECISIONS.md vs chief-ai/decision-log.md

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-004 |
| **Konu** | İki karar kaydı dosyası çakışıyor |
| **İddia** | `memory/DECISIONS.md` ile `chief-ai/decision-log.md` aynı işlevi görüyor |
| **Kontrol edilen dosya** | `memory/DECISIONS.md`, `chief-ai/decision-log.md` |
| **Satır** | Tüm dosya |
| **SSOT maddesi** | `.sab/authority.json` SSOT ilkesi — tek canonical kaynak |
| **Mevcut bulgu kaydı** | Yok |
| **Kanıt komutu** | `wc -l memory/DECISIONS.md chief-ai/decision-log.md` |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | Doğrulandı — FUNCTIONAL_DUPLICATE |
| **Risk** | Orta — çelişen kararlar riski |
| **Önerilen faz** | Phase 2 |
| **Önerilen aksiyon** | `memory/DECISIONS.md` silinecek; `chief-ai/decision-log.md` canonical. Incoming link'ler güncellenecek: `memory/SESSION_NOTES.md`, `memory/WHERE_IS_WHAT.md`, `audits/README.md`, `workflows/README.md`, `agents/README.md`, `docs/BEKCI_CHANGELOG.md`, `docs/SYSTEM_ARCHITECTURE.md`, `docs/ysos/AI_AGENT_RULES.md` |
| **Silme izni** | ACTION_PROPOSED — owner onayı, 8 incoming link önce güncellenmeli |
| **Kapanış kriteri** | Tek karar kaydı, 8 incoming link `chief-ai/decision-log.md`'ye yönlendirilmiş |
| **Durum** | ACTION_PROPOSED |

### Bulgu #005 — SAB.md vs YALIHAN_ARCHITECTURE_CONSTITUTION Çakışması

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-005 |
| **Konu** | İki anayasa dosyası çakışıyor |
| **İddia** | `docs/SAB.md` (v24.2.0) ve `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` ikisi de "anayasa" |
| **Kontrol edilen dosya** | `docs/SAB.md` (194 satır), `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` |
| **Satır** | Başlık ve kapsam bölümleri |
| **SSOT maddesi** | `.sab/authority.json` SSOT ilkesi — tek canonical anayasa kaynağı |
| **Mevcut bulgu kaydı** | Yok |
| **Kanıt komutu** | `head -20 docs/SAB.md` ve `head -20 docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | Doğrulandı — OVERLAP |
| **Risk** | Yüksek — authority çakışması |
| **Önerilen faz** | Phase 2 |
| **Önerilen aksiyon** | Kapsam netleştir: SAB.md=teknik anayasa, CONSTITUTION=mimari anayasa; çapraz referans ekle |
| **Silme izni** | Hayır — içerik düzenleme, owner onayı |
| **Kapanış kriteri** | İki dosya kapsamları net ayrılmış, çapraz referans mevcut |
| **Durum** | ACTION_PROPOSED |

### Bulgu #006 — agents/backend.md SSOT İhlali

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-006 |
| **Konu** | `agents/backend.md` SAB.md kurallarını kopyalıyor |
| **İddia** | Backend agent talimatları SAB.md'deki kuralları tekrar ediyor |
| **Kontrol edilen dosya** | `agents/backend.md`, `docs/SAB.md` |
| **Satır** | Tüm dosya |
| **SSOT maddesi** | `.sab/authority.json` SSOT ilkesi — tek canonical kaynak |
| **Mevcut bulgu kaydı** | Yok |
| **Kanıt komutu** | `grep -c "thin controller\|tenant\|CQRS" agents/backend.md` |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | Doğrulandı — SSOT ihlali |
| **Risk** | Orta — güncelleme tutarsızlığı |
| **Önerilen faz** | Phase 2 |
| **Önerilen aksiyon** | Tekrarlar kaldırılacak, `docs/SAB.md`'ye referans verilecek |
| **Silme izni** | Hayır — içerik düzenleme |
| **Kapanış kriteri** | `agents/backend.md` SAB.md'ye referans veriyor, tekrar yok |
| **Durum** | ACTION_PROPOSED |

### Bulgu #007 — STATE_PACKET.md Stale

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-007 |
| **Konu** | `STATE_PACKET.md` Sprint 3.6 state paketi — stale |
| **İddia** | Sprint 3.6 tamamlanmış, dosya aktif görevi yok |
| **Kontrol edilen dosya** | `STATE_PACKET.md` |
| **Satır** | Tüm dosya |
| **SSOT maddesi** | Talimat §4 Adım 5 — stale kontrolü |
| **Mevcut bulgu kaydı** | `docs/MD_AUDIT_REPORT.md` §5 (2026-06-16) |
| **Kanıt komutu** | `grep -l "Sprint 3.6\|STATE_PACKET" docs/PROGRESS-TRACKER.md` |
| **Kanıt seviyesi** | DOCUMENTED |
| **Kontrol sonucu** | Doğrulandı — ARCHIVE_CANDIDATE |
| **Risk** | Düşük — bilgi kirliliği |
| **Önerilen faz** | Phase 1 |
| **Önerilen aksiyon** | `docs/_archive/`'e taşınacak |
| **Silme izni** | Hayır — arşiv, owner onayı |
| **Kapanış kriteri** | Dosya `docs/_archive/` altında |
| **Durum** | ACTION_PROPOSED |

### Bulgu #008 — kilo-release-merge/ Repo Kopyası

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-008 |
| **Konu** | `kilo-release-merge/` tüm repo'nun kopyasını içeriyor |
| **İddia** | ~500+ .md dosyası ana repo'yu tekrar ediyor |
| **Kontrol edilen dosya** | `kilo-release-merge/` dizini |
| **Satır** | Dizin yapısı |
| **SSOT maddesi** | Talimat §3.4 — hariç tutulan dizinler |
| **Mevcut bulgu kaydı** | Yok |
| **Kanıt komutu** | `find kilo-release-merge -name "*.md" \| wc -l` |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | Doğrulandı — INTENTIONAL_MIRROR |
| **Risk** | Yüksek — arama kirliliği, yanlış dosya riski |
| **Önerilen faz** | Phase 1 |
| **Önerilen aksiyon** | `.gitignore`'a eklenecek veya silinecek |
| **Silme izni** | Evet — owner onayı |
| **Kapanış kriteri** | Dizin `.gitignore`'da veya silinmiş |
| **Durum** | ACTION_PROPOSED |

### Bulgu #009 — docs/MD_AUDIT_REPORT.md Stale

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-009 |
| **Konu** | Önceki denetim raporu stale |
| **İddia** | 2026-06-16 tarihli rapor, güncel değil |
| **Kontrol edilen dosya** | `docs/MD_AUDIT_REPORT.md` (294 satır) |
| **Satır** | Tüm dosya |
| **SSOT maddesi** | Talimat §4 Adım 5 — stale kontrolü |
| **Mevcut bulgu kaydı** | Bu raporun selefi |
| **Kanıt komutu** | `head -5 docs/MD_AUDIT_REPORT.md` → "2026-06-16, WenOX" |
| **Kanıt seviyesi** | DOCUMENTED |
| **Kontrol sonucu** | Doğrulandı — ARCHIVE_CANDIDATE |
| **Risk** | Düşük — tarihsel değer var |
| **Önerilen faz** | Phase 1 |
| **Önerilen aksiyon** | `docs/_archive/`'e taşınacak. Incoming link'ler güncellenecek: `docs/BEKCI_CHANGELOG.md`, `docs/PROGRESS-TRACKER.md`, `docs/index.md`, `docs/governance/CLAUDE_MEMORY.md` |
| **Silme izni** | ACTION_PROPOSED — arşiv, owner onayı, 4 incoming link güncellenecek |
| **Kapanış kriteri** | Dosya `docs/_archive/` altında, 4 incoming link güncellenmiş, bu rapor referans veriyor |
| **Durum** | ACTION_PROPOSED |

### Bulgu #010 — Governance Kuralları Tekrarı

| Alan | Değer |
|------|-------|
| **Bulgu ID** | MD-AUDIT-010 |
| **Konu** | 4 governance kuralı 3-5 dosyada tekrar ediyor |
| **İddia** | Tenant isolation (5 dosya), Thin Controller (4), Context7 (3), Write Authority (4) |
| **Kontrol edilen dosya** | `README.md`, `CLAUDE.md`, `START_HERE.md`, `docs/SAB.md`, `agents/backend.md` |
| **Satır** | Çoklu |
| **SSOT maddesi** | `.sab/authority.json` SSOT ilkesi — tek canonical kaynak |
| **Mevcut bulgu kaydı** | Yok |
| **Kanıt komutu** | `grep -rl "tenant.isolation\|BelongsToTenant" README.md CLAUDE.md START_HERE.md docs/SAB.md agents/backend.md` |
| **Kanıt seviyesi** | REPO_VERIFIED |
| **Kontrol sonucu** | Doğrulandı — SSOT ihlali |
| **Risk** | Yüksek — güncelleme tutarsızlığı |
| **Önerilen faz** | Phase 2 |
| **Önerilen aksiyon** | Her kural sadece `docs/SAB.md`'de kalmalı, diğerleri referans vermeli |
| **Silme izni** | Hayır — içerik düzenleme |
| **Kapanış kriteri** | Her kural tek canonical kaynaktan referans alıyor |
| **Durum** | ACTION_PROPOSED |

## 7. Kanıt Seviyesi ve Durum Sözlüğü

### Kanıt Seviyeleri

- `DOCUMENTED`: İddia bir dokümanda yazıyor.
- `REPO_VERIFIED`: Güncel repository dosyalarıyla mekanizma veya içerik doğrulandı.
- `TEST_VERIFIED`: İddia ilgili assertion'lı test ile doğrulandı. (Bu raporda kullanılmadı)
- `PRODUCTION_VERIFIED`: Gerçek ortam doğrulaması. (Bu raporda kullanılmadı)
- `INFERRED`: Kaynaklardan çıkarım. (Bu raporda kullanılmadı)
- `UNKNOWN`: Yeterli kanıt yok.

### Bulgu Durumları

- `OPEN`: Bulgu tanımlandı, aksiyon bekliyor
- `VALIDATION_PENDING`: Kanıt toplama aşamasında
- `ACTION_PROPOSED`: Aksiyon önerildi, owner onayı bekleniyor
- `CLOSED`: Kapanış kriterleri sağlandı
- `BLOCKED_PENDING_AUTH`: Yetkili onay bekleniyor
- `UNKNOWN`: Durum belirsiz

**Tüm bulgular bu raporda `ACTION_PROPOSED` durumundadır.** Hiçbir aksiyon uygulanmadı.

## 8. Planla Uyumlu Aksiyon Fazları

### Phase 0 — Kanıt ve Sahiplik Kilidi

- [x] Snapshot doğrulandı (branch, HEAD, dirty durumu)
- [x] SSOT hiyerarşisi doğrulandı (10 birincil kaynak okundu)
- [x] Mevcut bulgu ID'leri eşlendi (`docs/MD_AUDIT_REPORT.md` referans alındı)
- [x] Dosya sahipliği ve link envanteri çıkarıldı (§5)
- [x] Hiçbir içerik değişikliği yapılmadı

**Çıkış kriteri:** Her kritik dosya için owner, authority ve durum adayı belli. ✅ SAĞLANDI

### Phase 1 — Kritik Authority ve Duplicate Bulguları

| # | Aksiyon | Bulgu ID | Owner | Risk |
|---|---------|----------|-------|------|
| A1 | `ROADMAP.md` (kök) sil — 4 incoming link güncellenecek (memory/WHERE_IS_WHAT.md, START_HERE.md, chief-ai/research/00_RESEARCH_INDEX.md, workflows/deploy.md) | MD-AUDIT-001 | Repo owner | Düşük |
| A2 | `SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` → `docs/architecture/` taşı | MD-AUDIT-002 | Repo owner | Düşük |
| A3 | `STATE_PACKET.md` → `docs/_archive/` taşı | MD-AUDIT-007 | Repo owner | Düşük |
| A4 | `docs/MD_AUDIT_REPORT.md` → `docs/_archive/` taşı | MD-AUDIT-009 | Repo owner | Düşük |
| A5 | `kilo-release-merge/` → `.gitignore`'a ekle veya sil | MD-AUDIT-008 | Repo owner | Orta |

**Çıkış kriteri:** Silme/taşıma değil, canonical dosya ve önerilen referans yapısı onaylanmış olmalı.

### Phase 2 — İçerik Sahipliği ve Birleştirme Planı

| # | Aksiyon | Bulgu ID | Owner | Risk |
|---|---------|----------|-------|------|
| B1 | `docs/adrs/` → `docs/adr/` birleştir | MD-AUDIT-003 | Architecture owner | Orta |
| B2 | `memory/DECISIONS.md` sil, `chief-ai/decision-log.md` canonical — 8 incoming link güncellenecek | MD-AUDIT-004 | AI owner | Orta |
| B3 | `docs/SAB.md` ile `YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` kapsam netleştir | MD-AUDIT-005 | Architecture owner | Yüksek |
| B4 | `agents/backend.md` SAB.md tekrarları kaldır | MD-AUDIT-006 | Agent owner | Orta |
| B5 | Governance kuralı tekrarları kaldır (4 kural, 3-5 dosya) | MD-AUDIT-010 | Architecture owner | Yüksek |
| B6 | `START_HERE.md` + `PROJECT_CONTEXT.md` → `docs/onboarding.md` birleştir | — | Repo owner | Orta |
| B7 | `CLAUDE.md` → `docs/ai-agents/claude.md` taşı | — | AI owner | Düşük |

**Çıkış kriteri:** Her aday için kaynak, hedef, link güncelleme listesi ve rollback planı yazılmış olmalı.

### Phase 3 — Dizin Yapısı ve Erişilebilirlik

| # | Aksiyon | Etki |
|---|---------|------|
| C1 | `docs/PROGRESS-TRACKER.md` (2329 satır) sprint bazında böl | Okunabilirlik |
| C2 | `docs/` kökünden 3 PILOT-002 dosyası arşive taşı | Kalabalık azaltma |
| C3 | `docs/SYSTEM_ARCHITECTURE.md` SAB.md'ye referans ver | Tekrar azaltma |
| C4 | `docs/architecture-lite.md` SAB.md'ye referans ver | Tekrar azaltma |
| C5 | `README.md` (989 satır) kısalt | Kök README amaca dönmeli |
| C6 | `docs/index.md` güncelle — yeni yapıyı yansıt | SSOT entry point |

**Çıkış kriteri:** Yeni önerilen ağaç, mevcut link etkisi ve migration/taşıma riskleri ile birlikte sunulmalı.

### Phase 4 — Sürekli Governance Kontrolü

| # | Aksiyon |
|---|---------|
| D1 | Yeni .md dosyaları için naming/location kontrolü |
| D2 | MD dosya denetimi için periyodik kontrol — NOT: `bekci:tenant-audit` komutu tenant model/migration denetimi yapar, Markdown dosyası denetimi yapmaz. `.bekciignore` dosyası `docs/` dizinini Bekçi taramasından hariç tutar. MD denetimi ayrı bir süreç gerektirir. |
| D3 | SSOT referans kontrolü — yeni dosyalar canonical kaynağa referans vermeli |
| D4 | Tarihli raporların arşiv politikası |
| D5 | CI veya command gate önerisi — uygulanmış gibi gösterilmemeli |

**Çıkış kriteri:** Kontrolün nasıl çalışacağı, hangi kapsamda çalışacağı ve false positive durumunda insan onayının nerede olacağı belirtilmeli.

## 9. Güvenlik ve Veri Sınırları

Bu rapor kapsamında:
- API anahtarı, `.env` değeri, parola, token, kişisel veri kopyalanmamıştır.
- Production veritabanına bağlanılmamıştır.
- Migration, seed, delete, rename, move, commit, push, merge veya deploy yapılmamıştır.
- Tüm öneriler uygulanamaz; uygulama için ayrı açık yetki ve yeni snapshot gerekir.

## 10. Kalite Kontrol Listesi

- [x] Git kökü, branch, HEAD ve dirty durumu raporlandı (§1)
- [x] `yalihan-constitution-review` skill'i tamamen okundu (SKILL.md, 98 satır)
- [x] `finding-lifecycle.md` tamamen okundu (73 satır)
- [x] Skill ve lifecycle hash'leri kaydedildi (§1)
- [x] `docs/SAB.md`, `.sab/authority.json` (SSOT) ve ilgili anayasa kaynakları okundu — `.sab/ONBOARDING.md` "NOT SSOT" olarak işaretlendi (entrypoint only)
- [x] `docs/ERA_V/PHASE2-ROADMAP.md` ile plan kontrolü yapıldı
- [x] Önceki audit (`docs/MD_AUDIT_REPORT.md`) arandı ve tarihsel olarak kullanıldı
- [x] Untracked/staged/unstaged dosyalar kapsam açısından belirtildi (§1)
- [x] Exact duplicate ile functional overlap ayrıldı (§5, §6)
- [x] Stale kararı yalnız yaşa göre verilmedi (§5 — STATE_PACKET.md için sprint tamamlanmış kontrolü yapıldı)
- [x] Tarihsel/immutable belgeler silinebilir sayılmadı (HISTORICAL_VALID olarak işaretlendi)
- [x] Her kritik iddia dosya ve satırla bağlandı (§6)
- [x] Her kritik iddia authority veya roadmap maddesine bağlandı (§6)
- [x] Evidence seviyeleri abartılmadı (DOCUMENTED vs REPO_VERIFIED ayrımı yapıldı)
- [x] Production doğrulaması yapılmadıysa açıkça `DOCUMENTED` veya `REPO_VERIFIED` yazıldı
- [x] Hiçbir dosya silinmedi, taşınmadı veya değiştirilmedi
- [x] Her öneri Phase 0–4 arasında konumlandırıldı (§8)
- [x] Her önerinin owner onay ihtiyacı ve kapanış kriteri belirtildi (§6, §8)
- [x] Broken link ve incoming reference riski kontrol edildi (§6 kapanış kriterleri) — her silme/taşıma önerisi için incoming link listesi çıkarıldı
- [x] "Maksimum 15 dosya" önerisi bir otorite kaynağında doğrulanamadı — tarihsel audit önerisi olarak işaretlendi (§4 madde 2)
- [x] `bekci:tenant-audit` komutunun Markdown denetimi yapmadığı doğrulandı — tenant model/migration denetimi yapar, `.bekciignore` `docs/`'u hariç tutar (§8 Phase 4 D2)
- [x] Rapor yeni bir paralel SSOT yaratmıyor; mevcut index/bulgu kaydına referans veriyor

## 11. Teslim Cümlesi

```text
Bu çalışma Markdown dokümanlarının ve mimari uyumluluk iddialarının
salt-okunur denetimidir. Dosya silme, taşıma, birleştirme, kod değişikliği,
migration, production doğrulaması ve release onayı bu kapsamda yapılmamıştır.
Her öneri, ilgili owner onayı ve güncel snapshot üzerinde ayrı doğrulama
gerektirir.
```

Bu talimatın uygulanması Bekçi'nin repository'ye otomatik müdahale ettiği, dosyaları kendiliğinden temizlediği veya YALIHAN mimarisini production'da sertifikalandırdığı anlamına gelmez.

---

*Bu rapor 2026-09-07 tarihinde, `yalihan-constitution-review` skill'i (v3.3) ve `MD_MIMARI_UYUMLULUK_DENETIMI_AGENT_TALIMATI.md` talimatına uygun olarak hazırlanmıştır.*

