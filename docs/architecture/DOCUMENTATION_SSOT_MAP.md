# YALIHAN OS — DOCUMENTATION SSOT MAP

**Tarih:** 2026-09-07
**Durum:** DOCUMENTED / VALIDATION_PENDING
**Kaynak:** ARCHITECTURE_BACKBONE_AUDIT.md §12, md-dosya-denetim-raporu-2026-09-07.md

---

## 1. Amaç

Bu belge, Yalıhan OS'deki tüm dokümantasyon için:
- SSOT hiyerarşisini
- Her belgenin rolünü (SSOT / Anayasa / Catalog / Navigation / Historical)
- Güncellik durumunu
- Arşiv gereksinimlerini
- Bekçi kapsamı dışında kalan dokümantasyon denetim sürecini

tanımlar.

---

## 2. SSOT Hiyerarşisi

```
1. .sab/authority.json (v6.1.1)     — Beklenen kural SSOT
2. docs/SAB.md (v24.2.0)            — Teknik anayasa
3. docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md — Mimari anayasa
4. docs/ysos/CONSTITUTION.md        — Süreç anayasası
5. docs/ERA_V/PHASE2-ROADMAP.md     — Aktif roadmap
6. docs/adr/ (23 ADR)               — Mimari karar kayıtları
7. docs/architecture/REGISTRY.md    — Canlı catalog
8. .sab/ONBOARDING.md               — Giriş/yönlendirme (NOT SSOT)
```

### Kapsam Ayrımı

| Belge | Kapsamı | Cevapladığı Soru |
|-------|---------|-----------------|
| authority.json | Context7, CI, governance, context isolation | "Beklenen kural nedir?" |
| SAB.md | Mutation, CQRS, testing, drift, financial | "Nasıl kod yazılır?" |
| Constitution | Domain boundaries, contracts, security, AI | "Sistem ne yapar?" |
| ysos/CONSTITUTION.md | Operasyon, agent yönetimi, oturum | "Nasıl çalışılır?" |
| REGISTRY.md | Domain, table, event, contract, agent, ADR catalog | "Nerede ve kim sahip?" |
| ONBOARDING.md | Giriş, yönlendirme, hızlı başlangıç | "Nereden başlamalı?" |

---

## 3. Belge Envanteri

### 3.1 Birincil Kaynaklar (SSOT)

| Belge | Satır | Versiyon | Rol | Güncel | Kanıt |
|-------|-------|----------|-----|--------|-------|
| `.sab/authority.json` | 332 | v6.1.1 | Beklenen kural SSOT | ✅ | REPO_VERIFIED |
| `docs/SAB.md` | 195 | v24.2.0 | Teknik anayasa | ✅ | REPO_VERIFIED |
| `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` | 561 | v1.0.0 | Mimari anayasa | ✅ | REPO_VERIFIED |
| `docs/ysos/CONSTITUTION.md` | — | — | Süreç anayasası | ✅ | REPO_VERIFIED |
| `docs/ERA_V/PHASE2-ROADMAP.md` | 386 | — | Aktif roadmap | ✅ | REPO_VERIFIED |
| `docs/architecture/REGISTRY.md` | 91 | — | Canlı catalog | 🟡 ADR index eksik | REPO_VERIFIED |

### 3.2 Navigasyon Kaynakları (NOT SSOT)

| Belge | Satır | Rol | Güncel | Kanıt |
|-------|-------|-----|--------|-------|
| `.sab/ONBOARDING.md` | 228 | Giriş/yönlendirme | ✅ NOT SSOT | REPO_VERIFIED |
| `README.md` | 989 | Operasyonel rehber | 🟡 Uzun, kısaltılmalı | REPO_VERIFIED |
| `START_HERE.md` | 186 | Hızlı başlangıç | ✅ | REPO_VERIFIED |
| `PROJECT_CONTEXT.md` | 114 | Proje tanıtımı | ✅ | REPO_VERIFIED |
| `docs/index.md` | 185 | Dokümantasyon navigasyon | ✅ | REPO_VERIFIED |
| `docs/README.md` | 156 | docs/ dizin rehberi | ✅ | REPO_VERIFIED |

### 3.3 Karar ve Borç Kaynakları

| Belge | Satır | Rol | Güncel | Kanıt |
|-------|-------|-----|--------|-------|
| `.project-brain/DECISION_LOG.md` | 69 | Karar kayıtları | ✅ | REPO_VERIFIED |
| `.project-brain/KNOWN_ISSUES.md` | 44 | Bilinen sorunlar | ✅ | REPO_VERIFIED |
| `docs/known-debt.md` | 343 | Teknik borç envanteri | ✅ | REPO_VERIFIED |
| `docs/PROGRESS-TRACKER.md` | 2329 | Sprint ilerleme | 🟡 Çok uzun, bölünmeli | REPO_VERIFIED |
| `docs/BEKCI_CHANGELOG.md` | 5432 | Bekçi günlüğü (güncel) | ✅ | REPO_VERIFIED |
| `CHANGELOG.md` | 1554 | Bekçi günlüğü (eski) | 🟡 Arşiv adayı | REPO_VERIFIED |

### 3.4 Tarihsel / Arşiv Adayları

| Belge | Satır | Rol | Önerilen | Kanıt |
|-------|-------|-----|----------|-------|
| `docs/MD_AUDIT_REPORT.md` | 294 | Eski MD audit (2026-06-16) | `docs/_archive/` | REPO_VERIFIED |
| `STATE_PACKET.md` | — | Sprint 3.6 state | `docs/_archive/` | REPO_VERIFIED |
| `docs/PILOT-002-CHARTER.md` | — | Pilot charter | `docs/_archive/` | REPO_VERIFIED |
| `docs/PILOT-002-AUTHORITY.md` | — | Pilot authority | `docs/_archive/` | REPO_VERIFIED |
| `docs/PILOT-002-DISCOVERY.md` | — | Pilot discovery | `docs/_archive/` | REPO_VERIFIED |

### 3.5 Audit ve Research Raporları (Tarihli)

| Belge | Tarih | Rol | Kanıt |
|-------|-------|-----|-------|
| `docs/architecture/tenant-isolation-audit-2026-09-06.md` | 2026-09-06 | Tenant izolasyon audit | REPO_VERIFIED |
| `docs/architecture/cqrs-projection-research-report-2026-09-06.md` | 2026-09-06 | CQRS projection research | REPO_VERIFIED |
| `docs/architecture/md-dosya-denetim-raporu-2026-09-07.md` | 2026-09-07 | MD dosya denetim | REPO_VERIFIED |
| `docs/architecture/ARCHITECTURE_BACKBONE_AUDIT.md` | 2026-09-07 | Mimari omurga audit | REPO_VERIFIED |
| `SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` | — | Güvenlik evidence | REPO_VERIFIED |

---

## 4. Bekçi Kapsamı

### 4.1 Bekçi Denetim Kapsamı

| Mekanizma | Kapsam | Hariç | Kanıt |
|-----------|-------|------|-------|
| `sab:integrity-scan` | app/ PHP dosyaları | docs/ (.bekciignore) | REPO_VERIFIED |
| `bekci:tenant-audit` | Model + migration dosyaları | docs/, runtime scope | REPO_VERIFIED |
| `guard:cqrs` | CQRS boundary | docs/ | REPO_VERIFIED |
| `sab:guard` | Tüm SAB kuralları | docs/ | REPO_VERIFIED |
| `sab:preflight` | Release profile | docs/ | REPO_VERIFIED |

### 4.2 .bekciignore İçeriği

```
.context7/
docs/
yalihan-bekci/
storage/
vendor/
node_modules/
.cursor/
.cursor/rules/
*.mdc
scripts/*.php
config/validation-rules.php
config/**/validation*.php
database/seeders/**
```

### 4.3 Dokümantasyon Denetim Süreci (Bekçi Dışı)

```
Bekçi kodu denetler, Markdown'u denetlemez.
MD denetimi ayrı süreç gerektirir:

1. MD_MIMARI_UYUMLULUK_DENETIMI_AGENT_TALIMATI.md talimatı
2. yalihan-constitution-review skill'i (v3.3)
3. Read-only audit — değişiklik yapmaz
4. Periyodik olarak çalıştırılmalı
5. Sonuçlar docs/architecture/ altında tarihli rapor olarak
```

---

## 5. Çelişkiler ve Çözümler

| # | Çelişki | Çözüm | Durum |
|---|---------|-------|-------|
| 1 | SAB.md "teknik anayasa" vs Constitution "mimari anayasa" | Kapsam ayrımı: SAB=nasıl kod, Constitution=sistem ne yapar | ACTION_PROPOSED |
| 2 | REGISTRY.md 5 ADR, docs/adr/ 23 ADR | REGISTRY.md ADR index güncellenecek | ACTION_PROPOSED |
| 3 | ROADMAP.md (kök, 547 satır) vs docs/ROADMAP.md (440 satır) | Kök silinecek, docs/ canonical | ACTION_PROPOSED |
| 4 | CHANGELOG.md (kök, 1554 satır) vs docs/BEKCI_CHANGELOG.md (5432 satır) | Kök arşiv adayı, docs/ canonical | ACTION_PROPOSED |
| 5 | docs/adr/ (23) vs docs/adrs/ (6) | docs/adr/ canonical, docs/adrs/ taşınacak | ACTION_PROPOSED |
| 6 | memory/DECISIONS.md vs chief-ai/decision-log.md | chief-ai canonical, memory/ silinecek | ACTION_PROPOSED |

---

## 6. Arşiv Politikası

### 6.1 Arşiv Kriterleri

Bir belge şu durumlarda `docs/_archive/`'a taşınmalıdır:
- Üzerinden 6 ay geçmiş ve aktif sprint'te referans verilmeyen
- Yerini yeni bir belge almış (örn: MD_AUDIT_REPORT → md-dosya-denetim-raporu)
- Sprint tamamlanmış ve state paketi artık gerekli değil
- Pilot/charter belgesi kapanmış

### 6.2 Arşiv Öncesi Kontrol

- [ ] Incoming link'ler tespit edildi ve güncellendi
- [ ] Belge tarihi ve arşiv nedeni belgelendi
- [ ] Yerini alan belge referans verildi

---

## 7. Kabul Kriterleri

- [ ] SSOT hiyerarşisi dokümante (Karar #1)
- [ ] REGISTRY.md ADR index güncel (23 ADR)
- [ ] REGISTRY.md tablo matrix DOMAIN_OWNERSHIP_MATRIX.md ile senkron
- [ ] Stale belgeler docs/_archive/'a taşınmış
- [ ] Kök dizin belge kalabalığı azaltılmış
- [ ] MD denetim süreci periyodik çalışıyor
- [ ] Bekçi kapsamı (kod vs MD) dokümante

---

*Bu belge ARCHITECTURE_BACKBONE_AUDIT.md §12 (Karar #12, #13) gereği üretilmiştir. Veri kaynakları: md-dosya-denetim-raporu-2026-09-07.md, .bekciignore, authority.json, SAB.md, Constitution, REGISTRY.md.*
