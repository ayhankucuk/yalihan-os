---
document_id: ADR-042
document_owner: architecture
decision_owner: saab
status: proposed
canonical: true
evidence_level: REPO_VERIFIED
as_of_commit: 587e7020
last_reviewed: 2026-09-07
review_after: 2026-10-07
supersedes: null
---

# ADR-042: Architecture Backbone Audit — 15 Mimari Karar

> [!IMPORTANT]
> **Sınır ve Kapsam:** Bu ADR belgesi production onay veya deployment yetkisi vermez. Tüm maddeler `ACTION_PROPOSED` ve `SAAB_REVIEW_PENDING` statüsündedir.

**Tarih:** 2026-09-07
**Durum:** PROPOSED — SAAB ONAYI BEKLENİYOR
**Sahip:** Strategic AI Architecture Board (SAAB)
**Kaynak:** docs/architecture/ARCHITECTURE_BACKBONE_AUDIT.md

---

## Bağlam (Context)

Yalıhan OS mimari omurgasının sistematik denetimi, normatif kural (authority.json, SAB.md, Constitution) ile kod mekanizması (model, scope, middleware, policy) ve gerçek uygulama (test, production) arasında 15 kritik uç tespit etmiştir.

Bu ADR, aşağıdaki 15 mimari kararın her biri için owner onayı bekleyen ACTION_PROPOSED önerilerini tek bir karar kaydında toplar.

### Tespit Edilen Kritik Uçlar

1. **SSOT hiyerarşisi belirsiz** — SAB.md ve Constitution ikisi de "anayasa" olarak konumlanıyor
2. **TenantScope fail-open** — tenant_id=null ise tüm veriler görünür
3. **40+ tabloda tenant_id eksik** — veri sızıntısı riski
4. **6 CQRS projection tablosunda tenant_id yok** — cross-tenant read model sızıntısı
5. **Queue job'larında tenant context standardizasyonu** — 14 kritik job'da TenantAwareJobInterface benimsendi, kalan kuyruk işlerinde yaygınlaştırılmalı
6. **Admin panel SetTenantContext yok** — admin tüm tenant verisini görür
7. **Hermes event log tenant_id leakage** — bazı event'lerde tenant_id yok
8. **REGISTRY.md güncel değil** — 5 ADR listeliyor, gerçekte 23

---

## Karar (Decision)

### 15 Mimari Karar

| # | Karar | Owner | Kanıt |
|---|-------|-------|-------|
| 1 | SSOT hiyerarşisi: authority.json > SAB.md > Constitution > ysos > ADR > REGISTRY > ONBOARDING | SAAB | REPO_VERIFIED |
| 2 | TenantScope fail-closed: tenant_id=null → bo sonuç | Security | REPO_VERIFIED |
| 3 | Domain ownership matrix: tüm 94 tablo sahiplik ile | Architecture | REPO_VERIFIED |
| 4 | CQRS projection lifecycle: tenant_id, tenant-aware rebuild/replay | Architecture | REPO_VERIFIED |
| 5 | Event naming (past tense), versioning, idempotency_key zorunlu | Architecture | DOCUMENTED |
| 6 | Queue tenant context: TenantAwareJobInterface yaygınlaştırma (14 job mevcut) | Backend | REPO_VERIFIED |
| 7 | Admin/Super-Admin: tenant-aware, AI ajan SuperAdmin yasağı | Security | REPO_VERIFIED |
| 8 | API versioning: v1/v2, OpenAPI, Idempotency-Key | Backend | DOCUMENTED |
| 9 | AI-Hermes separation: Hermes pure orchestrator, iş mantığı yok | AI | REPO_VERIFIED |
| 10 | Migration tenant-aware: backfill, rollback planı | Backend/DBA | DOCUMENTED |
| 11 | Production evidence: tenant test, drift, projection health | DevOps | DOCUMENTED |
| 12 | Documentation SSOT: REGISTRY güncel, stale arşiv | Architecture | REPO_VERIFIED |
| 13 | Bekçi scope: kod denetler, MD denetmez, runtime test gerekli | Architecture | REPO_VERIFIED |
| 14 | Global vs tenant table policy: global/tenant/conditional sınıflandırma | Architecture | REPO_VERIFIED |
| 15 | Model/Scope/Policy: BelongsToTenant, fail-closed, tenant-aware policy | Security | REPO_VERIFIED |

### Çıktı Dosyaları

| # | Dosya | İçerik |
|---|-------|--------|
| 1 | `ARCHITECTURE_BACKBONE_AUDIT.md` | Ana audit (12 bölüm) |
| 2 | `DOMAIN_OWNERSHIP_MATRIX.md` | Tüm tabloların sahiplik matrixi |
| 3 | `TENANT_ISOLATION_CONTRACT.md` | Tenant izolasyon sözleşmesi |
| 4 | `EVENT_AND_QUEUE_CONTRACT.md` | Event ve queue sözleşmesi |
| 5 | `AI_HERMES_BOUNDARY.md` | AI/Hermes sınırı |
| 6 | `DOCUMENTATION_SSOT_MAP.md` | Dokümantasyon SSOT map |
| 7 | Bu ADR | Karar kaydı |

---

## Alternatifler (Alternatives)

### Alternatif 1: Sadece tenant isolation düzeltmesi

- Sadece Karar #2, #4, #6, #14, #15 uygulanır
- Diğer kararlar ertelenir
- **Reddedildi:** SSOT hiyerarşisi belirsizliği çözülmeden diğer düzeltmeler tutarsız olabilir

### Alternatif 2: Aşamalı onay

- Her karar ayrı ADR olarak onaylanır
- 15 ayrı ADR gerekir
- **Reddedildi:** Kararlar birbirine bağlı, tek ADR'de toplu onay daha verimli

### Alternatif 3: Hiçbir değişiklik yapma

- Mevcut durum korunur
- **Reddedildi:** Veri sızıntısı riski (fail-open scope, eksik tenant_id) kabul edilemez

---

## Sonuçlar (Consequences)

### Pozitif

- Tenant izolasyon güvenliği artar (fail-closed scope)
- SSOT hiyerarşisi netleşir (karar #1)
- CQRS projection tenant-aware olur (karar #4)
- Queue job'ları tenant context korur (karar #6)
- REGISTRY.md güncel ve tam (karar #12)
- Bekçi kapsamı net (karar #13)

### Negatif

- 20-25 günlük implementasyon süresi
- Migration riski (tenant_id ekleme)
- Test güncellemeleri gerekli
- Production deployment öncesi doğrulama gerekli

### Risk

- **Düşük:** SSOT hiyerarşisi ve dokümantasyon kararları (Karar #1, #12, #13)
- **Orta:** Event/queue sözleşmesi ve API versioning (Karar #5, #8)
- **Yüksek:** TenantScope fail-closed ve tenant_id ekleme (Karar #2, #4, #14, #15)

---

## Kabul Kriterleri

- [ ] SAAB 15 kararı onaylar
- [ ] Her karar için owner atanır
- [ ] Implementasyon planı (Phase 0-10) onaylanır
- [ ] Test planı onaylanır
- [ ] Production migration planı onaylanır

---

## Referanslar

- `docs/architecture/ARCHITECTURE_BACKBONE_AUDIT.md` — Ana audit raporu
- `docs/architecture/tenant-isolation-audit-2026-09-06.md` — Tenant izolasyon audit (1450 satır)
- `docs/architecture/cqrs-projection-research-report-2026-09-06.md` — CQRS projection research
- `SECURITY_EVIDENCE_DRIVE_TENANT_AUDIT.md` — Güvenlik evidence
- `.sab/authority.json` (v6.1.1) — Beklenen kural SSOT
- `docs/SAB.md` (v24.2.0) — Teknik anayasa
- `docs/architecture/YALIHAN_ARCHITECTURE_CONSTITUTION_v1.0.md` — Mimari anayasa
- `docs/architecture/REGISTRY.md` — Canlı catalog
- ADR-041 — Context Isolation Standard

---

*Bu ADR ARCHITECTURE_BACKBONE_AUDIT.md'nin 15 mimari kararını tek karar kaydında toplar. SAAB onayı sonrası implementasyon başlatılacaktır.*
