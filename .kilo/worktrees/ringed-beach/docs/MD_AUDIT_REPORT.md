# MD Dosyaları Audit Raporu
> Oluşturma: 2026-06-16 | WenOX tarafından üretildi
> Kapsam: Tüm `*.md` dosyaları (`/vendor`, `/node_modules`, `/.git` hariç)

---

## 📊 Genel İstatistik

| Metrik | Değer |
|--------|-------|
| **Toplam MD dosyası** | 432 |
| **Aktif (docs/, archive hariç)** | 149 |
| **Arşiv (docs/archive/)** | 218 |
| **Proje kökü** | 5 (README, CHANGELOG, CLAUDE, CONTRIBUTING, CLAUDE.md) |
| **app/Modules/ içi** | 8 |
| **resources/prompts/** | 12 |
| **testsprite_tests/** | 6 |
| **mcp-servers/** | 5 |
| **storage/notebooklm-sync/** | 7 (mirror kopyalar) |

---

## 🔴 Kritik Sorunlar

### 1. Geçersiz Dosya Adı
```
app/Modules/Auth/Controllers/# Code Citations.md
```
**Sorun:** Hash (`#`) ve boşluk içeren geçersiz dosya adı. Shell'de sorun çıkarır, git'te kaçış gerektirir.
**Aksiyon:** Yeniden adlandır → `app/Modules/Auth/Controllers/CODE_CITATIONS.md`

### 2. Duplicate Dosyalar

| Dosya | Konum 1 | Konum 2 |
|-------|---------|---------|
| `API_CONTRACT.md` | `docs/API_CONTRACT.md` | `docs/technical/api/API_CONTRACT.md` |
| `SAB.md` (kopyalar) | `docs/SAB.md` (SSOT) | `storage/notebooklm-sync/SAB.md` (mirror) |
| `CLAUDE_MEMORY.md` | `docs/governance/CLAUDE_MEMORY.md` | `storage/notebooklm-sync/CLAUDE_MEMORY.md` |
| `BEKCI_CHANGELOG.md` | `docs/BEKCI_CHANGELOG.md` | `storage/notebooklm-sync/BEKCI_CHANGELOG.md` |
| `yalihan-project-brain-v2.md` | `docs/_archived/` | `.sab/proposals/` |

**Not:** `storage/notebooklm-sync/` mirror kopyaları intentional (NotebookLM sync için), `@sab-intentional` olarak işaretle.

### 3. Yanlış Konumdaki Dosyalar

| Dosya | Mevcut Konum | Doğru Konum |
|-------|-------------|------------|
| `docs/API_CONTRACT.md` | `docs/` kökü | `docs/technical/api/API_CONTRACT.md` (zaten var) |
| `docs/CHANGELOG_SPRINT1.md` | `docs/` | `docs/archive/` veya `CHANGELOG.md`'ye birleştir |
| `docs/config_cleanup_report.md` | `docs/` kökü | `docs/_reports/` |
| `docs/FRONTEND_AUDIT.md` | `docs/` kökü | `docs/_reports/` |
| `docs/FRONTEND_CLEANUP_LOG.md` | `docs/` kökü | `docs/_reports/` |
| `docs/HASACTIVESCOPE_REFACTORING.md` | `docs/` kökü | `docs/_reports/` veya `docs/technical/` |
| `docs/OTURUM_40_FINAL_REPORT.md` | `docs/` kökü | `docs/archive/` |
| `docs/OTURUM_41_FINAL_REPORT.md` | `docs/` kökü | `docs/archive/` |
| `docs/P1.1-Atomik-Tamamlama-Raporu.md` | `docs/` kökü | `docs/archive/` |
| `docs/SAB_PREFLIGHT_AUDIT_REPORT.md` | `docs/` kökü | `docs/_reports/` |
| `docs/TEST_SUITE_ANALYSIS_SESSION_24.md` | `docs/` kökü | `docs/archive/` veya `docs/_reports/` |
| `docs/TODO_SPRINT1.md` | `docs/` kökü | `docs/archive/` |
| `docs/GEMINI_ENGINEER_PLAN.md` | `docs/` kökü | `docs/archive/` (eski plan) |

---

## 🟠 Orta Öncelikli Sorunlar

### 4. docs/ Kökünde Fazla Kalabalık
`docs/` doğrudan altında 40+ MD dosyası var. Tavsiye edilen maksimum: 15.

Kök seviyesinde **kalması gereken** dosyalar (SSOT):
- `index.md` ✅
- `SAB.md` ✅
- `PROGRESS-TRACKER.md` ✅
- `known-debt.md` ✅
- `BEKCI_CHANGELOG.md` ✅
- `ROADMAP.md` ✅
- `yalihan-project-brain-v3.md` ✅
- `DAP_CORE.md` ✅
- `authority-map.md` ✅

### 5. Stale Plan Dosyaları (docs/ kökünde)

| Dosya | Durum |
|-------|-------|
| `docs/WHATSAPP_PLAN.md` | Plan fazı geçti → archive |
| `docs/GEMINI_ENGINEER_PLAN.md` | Eski plan → archive |
| `docs/implementation_plan_p1.2.md` | Sprint 1 bitti → archive |
| `docs/implementation_plan_s2_b1_config_cleanup.md` | Sprint 1 bitti → archive |
| `docs/TODO_SPRINT1.md` | Sprint 1 bitti → archive |

### 6. Reports Dizini Karmaşası

`docs/reports/` ve `docs/_reports/` olmak üzere **iki ayrı reports dizini** var:
- `docs/reports/` → 3 dosya (hygiene audit)
- `docs/_reports/` → 9 dosya

**Aksiyon:** `docs/reports/` içindeki 3 dosyayı `docs/_reports/`'a taşı, `docs/reports/` sil.

### 7. NotebookLM Mirror Netlik Sorunu

`storage/notebooklm-sync/` altında 7 dosya var — bunlar `docs/` asıllarının kopyaları.
Sync script mekanizması var ama hangi dosyaların sync edildiği belirsiz.

---

## 🟡 Düşük Öncelik

### 8. .sab/proposals/ Arşiv Adayları

| Dosya | Durum |
|-------|-------|
| `.sab/proposals/yalihan-project-brain-v2.md` | v3 var, bu arşiv |
| `.sab/proposals/yalihan-project-brain.md` | v1, arşiv |
| `.sab/proposals/yalihan-runtime-truth.md` | Değerlendirmeli |

### 9. testsprite_tests/ MD Dosyaları

6 MD dosyası testsprite raporları. Bunlar `docs/_reports/testsprite/` altında toplanabilir veya olduğu yerde bırakılabilir.

### 10. resources/prompts/ — Naming Tutarlılığı

12 prompt dosyası var. Tümü `snake_case` + Türkçe isim kullanıyor. Tutarlı ✅.
`talep-analizi-legacy.prompt.md` — "legacy" ibaresi: arşivlenebilir veya güncel versiyona taşınabilir.

---

## 📁 Dizin Yapısı Değerlendirmesi

### Mevcut Yapı

```
docs/
├── 🔴 40+ dosya kök seviyede (çok kalabalık)
├── _archived/          ← sadece 1 dosya, archive/ ile birleştirilmeli
├── _reports/           ← 9 dosya ✅
├── adr/                ← 20 ADR ✅ düzenli
├── ai-engines/         ← 11 AI engine spec ✅
├── architecture/       ← 15 mimari belge ✅
├── archive/            ← 218 arşiv dosyası ✅
│   ├── 2026-day-1/
│   ├── 2026_05/
│   ├── changelogs/
│   ├── ci-reports/
│   ├── governance-history/
│   └── phase-reports/
├── features/           ← 1 dosya (büyütülmeli)
├── governance/         ← 4 dosya ✅
├── owner-portal/       ← 2 dosya ✅
├── plans/              ← 6 plan dosyası ✅
├── production/         ← 1 dosya (az)
├── registry/           ← 6 kayıt dosyası ✅
├── reports/            ← 3 dosya 🔴 _reports/ ile birleştirilmeli
├── runbooks/           ← 7 runbook ✅
└── technical/          ← 9 teknik belge ✅
    ├── api/
    ├── frontend/
    └── system/
```

### Önerilen Yapı

```
docs/
├── index.md            (SSOT giriş noktası)
├── SAB.md              (Teknik Anayasa)
├── PROGRESS-TRACKER.md (Phase takibi)
├── BEKCI_CHANGELOG.md  (Governance log)
├── ROADMAP.md          (Yol haritası)
├── known-debt.md       (Teknik borç)
├── DAP_CORE.md         (DAP otopilot)
├── authority-map.md    (Otorite haritası)
├── yalihan-project-brain-v3.md (Proje beyin belgesi)
│
├── _reports/           (Tüm raporlar — reports/ buraya birleştirilmeli)
├── adr/                (ADR — değişmez)
├── ai-engines/         (AI motoru spec'leri)
├── architecture/       (Mimari belgeler)
├── archive/            (Tarihsel — dokunma)
│   └── _archived/      (eski _archived/ buraya taşı)
├── features/           (Feature spec'leri — büyütülmeli)
├── governance/         (CLAUDE_MEMORY, AI_FINDINGS vs.)
├── owner-portal/       (Owner portal belgeleri)
├── plans/              (Aktif planlar)
├── production/         (Deployment, runbook destekleri)
├── registry/           (Kayıt defteri)
├── runbooks/           (Operasyon runbook'ları)
└── technical/          (Teknik referanslar)
    ├── api/
    ├── frontend/
    └── system/
```

---

## ✅ İyi Durumda Olan Dosyalar

| Dizin | Durum | Not |
|-------|-------|-----|
| `docs/adr/` | ✅ Düzenli | 20 ADR, tarih-sıralı |
| `docs/architecture/` | ✅ Düzenli | 15 belge, konuya göre |
| `docs/runbooks/` | ✅ Düzenli | 7 operasyonel runbook |
| `docs/registry/` | ✅ İyi | 6 kayıt belgesi |
| `docs/governance/` | ✅ İyi | 4 kritik governance belgesi |
| `docs/ai-engines/` | ✅ İyi | 11 AI engine spec |
| `docs/archive/` | ✅ Düzenli | Tarih bazlı klasörleme |
| `resources/prompts/` | ✅ Tutarlı | 12 prompt, snake_case |

---

## 🎯 Aksiyon Planı (Öncelik Sırası)

### Öncelik 1 — Hemen Yap (Kritik)
- [ ] `app/Modules/Auth/Controllers/# Code Citations.md` → `CODE_CITATIONS.md` olarak yeniden adlandır
- [ ] `docs/API_CONTRACT.md` → `docs/technical/api/API_CONTRACT.md` ile karşılaştır, biri silinmeli
- [ ] `docs/reports/` → `docs/_reports/`'a birleştir, boş dizini sil

### Öncelik 2 — Bu Sprint (Düzen)
- [ ] docs/ kökündeki stale plan dosyalarını archive'e taşı (5 dosya — bkz. §5)
- [ ] docs/ kökündeki eski raporları `docs/_reports/` veya `docs/archive/`'e taşı (8 dosya — bkz. §3)
- [ ] `docs/_archived/` içeriğini `docs/archive/`'e taşı, klasörü sil

### Öncelik 3 — Gelecek Sprint
- [ ] `.sab/proposals/` v1 ve v2 brain dosyalarını archive'e taşı
- [ ] `storage/notebooklm-sync/` mirror'larına `# NotebookLM Mirror` başlığı ekle
- [ ] `testsprite_tests/` MD dosyalarını `docs/_reports/testsprite/`'a taşımayı değerlendir

---

## 📋 Tam Dosya Listesi (Aktif — Archive Hariç)

### docs/ Kök (Düzenlenmeli)
| Dosya | Durum | Önerilen Aksiyon |
|-------|-------|-----------------|
| `index.md` | ✅ SSOT | Güncelle |
| `SAB.md` | ✅ SSOT | Yerinde kal |
| `PROGRESS-TRACKER.md` | ✅ Aktif | Yerinde kal |
| `BEKCI_CHANGELOG.md` | ✅ Aktif | Yerinde kal |
| `ROADMAP.md` | ✅ Aktif | Yerinde kal |
| `known-debt.md` | ✅ Aktif | Yerinde kal |
| `DAP_CORE.md` | ✅ SSOT | Yerinde kal |
| `yalihan-project-brain-v3.md` | ✅ SSOT | Yerinde kal |
| `authority-map.md` | ✅ Aktif | Yerinde kal |
| `API_CONTRACT.md` | 🔴 Duplicate | `technical/api/` ile birleştir |
| `ARCHITECTURE_DEEP_DIVE.md` | 🟡 Taşı | `docs/architecture/` altına |
| `architecture-lite.md` | ✅ Quick ref | Yerinde kal (kısayol erişim) |
| `AI_COLLABORATION_DESIGN.md` | 🟡 Taşı | `docs/architecture/` altına |
| `AI_COPILOT_ARCHITECTURE_VALIDATION.md` | 🟡 Taşı | `docs/architecture/` altına |
| `ai_learning_loop.md` | 🟡 Taşı | `docs/ai-engines/` altına |
| `BEKCI_CHANGELOG.md` | ✅ | Yerinde kal |
| `CHANGELOG_SPRINT1.md` | 🟠 Archive | `docs/archive/` |
| `config_cleanup_report.md` | 🟠 Taşı | `docs/_reports/` |
| `DAP_DECISION_TABLE.md` | ✅ | `DAP_CORE.md` ile bağlantılı, yerinde kal |
| `FRONTEND_AUDIT.md` | 🟠 Taşı | `docs/_reports/` |
| `FRONTEND_CLEANUP_LOG.md` | 🟠 Taşı | `docs/_reports/` |
| `FRONTEND_DESIGN_VISION.md` | 🟡 Taşı | `docs/architecture/` |
| `FRONTEND_FILTER_SYSTEM.md` | 🟡 Taşı | `docs/technical/frontend/` |
| `GEMINI_ENGINEER_PLAN.md` | 🟠 Archive | `docs/archive/` |
| `HASACTIVESCOPE_REFACTORING.md` | 🟠 Taşı | `docs/_reports/` |
| `IDE_ONBOARDING_GUIDE.md` | ✅ | `docs/technical/` altına taşı |
| `implementation_plan_p1.2.md` | 🟠 Archive | Bitti, archive'e |
| `implementation_plan_s2_b1_config_cleanup.md` | 🟠 Archive | Bitti, archive'e |
| `N1_TRIAGE_STRATEGY.md` | 🟡 | `docs/technical/` altına |
| `NOTEBOOKLM_INTEGRATION.md` | ✅ | Yerinde veya `docs/technical/` |
| `NOTEBOOKLM_SOURCES.md` | ✅ | Yerinde veya `docs/technical/` |
| `NOTEBOOKLM_SYNC.md` | ✅ | Yerinde veya `docs/technical/` |
| `OTURUM_40_FINAL_REPORT.md` | 🟠 Archive | Oturum raporu |
| `OTURUM_41_FINAL_REPORT.md` | 🟠 Archive | Oturum raporu |
| `P1.1-Atomik-Tamamlama-Raporu.md` | 🟠 Archive | Bitti |
| `PERFORMANCE_PROFILER.md` | 🟡 | `docs/technical/` altına |
| `PHASE_12_*.md` (5 dosya) | 🟠 Archive | Phase 12 bitti |
| `REFACTORING_LOG.md` | 🟡 | `docs/registry/` altına |
| `ROO_CAPABILITIES.md` | 🟡 | `docs/technical/` altına |
| `SAB_PREFLIGHT_AUDIT_REPORT.md` | 🟠 Taşı | `docs/_reports/` |
| `SEARCH_AND_FILTER_SPEC.md` | 🟡 | `docs/features/` altına |
| `TEST_SUITE_ANALYSIS_SESSION_24.md` | 🟠 Archive | Oturum raporu |
| `TODO_SPRINT1.md` | 🟠 Archive | Bitti |
| `walkthrough_queue_hardening.md` | 🟡 | `docs/technical/` |
| `webhook-tenant-security.md` | 🟡 | `docs/technical/` veya `docs/architecture/` |
| `WHATSAPP_PLAN.md` | 🟠 Archive | Plan fazı geçti |
| `YALIHAN_CORTEX_ARCHITECTURE.md` | 🟡 Taşı | `docs/architecture/` |

---

## 🗒️ Notlar

1. `storage/notebooklm-sync/` dosyaları **bilinçli mirror** — dokunma, `@intentional-mirror` etiketi ver.
2. `docs/archive/` içindeki **218 dosyaya dokunma** — tarihsel kayıt, SAB Rule koruması altında.
3. `docs/adr/` dosyalarına **dokunma** — ADR'ler immutable, tarih-sıralı.
4. `app/Modules/*/README.md` dosyaları modül belgeleri — yerinde kalmalı.
5. `resources/prompts/*.prompt.md` — AI prompt SSOT, yerinde kalmalı.

---

*Bu rapor: `docs/MD_AUDIT_REPORT.md` | WenOX Oturum 2026-06-16*
