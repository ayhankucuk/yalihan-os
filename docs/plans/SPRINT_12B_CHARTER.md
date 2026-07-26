# Sprint 12B — Workspace Tenant Isolation

**Charter ID:** BR-20260726-SPRINT12B
**Status:** ACTIVE
**Start Date:** 2026-07-26
**Sprint Goal:** Workspace Tenant Isolation'ı publish workflow'un ayrılmaz bir parçası haline getirmek ve tüm state transition'ların tenant sınırları içinde gerçekleştiğini kanıtlamak.

---

## Sprint 12B Authorization

**Board Question:** YALIHAN, bir workspace dışından yapılan publish/submit/unpublish işlemlerini güvenli şekilde engelleyebiliyor mu?

**SAAB Kalite Kapısı:** Tenant Isolation (SAAB v8)

---

## Phase 1 — Workspace Isolation ⭐ (P1)

### Implementation

- Publish/Submit/Unpublish işlemlerinde workspace ownership doğrulaması
- ListingCrudService ve ilgili Action katmanlarında tenant kontrolü
- Workspace dışı erişimin engellenmesi

### Evidence

| Senaryo | Beklenen Sonuç |
|---------|----------------|
| Aynı workspace | ✅ İzin |
| Farklı workspace | ❌ 403/404 |

---

## Phase 2 — Cross-Tenant Test Suite

### Test Senaryoları

| # | Test | Açıklama |
|---|------|----------|
| 1 | Tenant A kendi ilanını publish edebilir | ✅ İzin |
| 2 | Tenant A Tenant B ilanını publish edemez | ❌ Engelleme |
| 3 | Submit endpoint tenant kontrolü | Tenant boundary korunmalı |
| 4 | Publish endpoint tenant kontrolü | Tenant boundary korunmalı |
| 5 | Unpublish endpoint tenant kontrolü | Tenant boundary korunmalı |
| 6 | API Authorization | 403/404 dönmeli |

---

## Phase 3 — Replay Determinism

### Doğrulanacak

- Aynı transition log tekrar oynatıldığında aynı state oluşuyor
- Replay duplicate transition üretmiyor
- Replay audit trail'i bozmuyor

---

## Phase 4 — Persistence Hardening

### Kontroller

- FK Integrity
- Cascade davranışları
- Orphan record kontrolü
- Transaction rollback senaryoları

---

## Definition of Done

Sprint 12B aşağıdaki koşullar sağlandığında tamamlanmış kabul edilir:

| # | Koşul | Kanıt |
|---|-------|-------|
| 1 | Workspace isolation doğrulandı | Publish/Unpublish sadece aynı workspace'te çalışır |
| 2 | Cross-tenant testleri geçti | Tüm senaryolar yeşil |
| 3 | Replay deterministik | Deterministik sonuç, deterministic test |
| 4 | FK integrity doğrulandı | FK constraint testleri geçti |
| 5 | CI tamamen yeşil | Yeni testler dahil |
| 6 | No new violations | bekci:health + sab:integrity-scan temiz |

---

## Sprint Akışı

```
Sprint 12A ✅
        ↓
Sprint 12B ⏳ ACTIVE
Workspace Tenant Isolation
        ↓
Cross-Tenant Tests
        ↓
Replay Validation
        ↓
Persistence Hardening
        ↓
CERTIFICATION
        ↓
Sprint 12C ⏳
Legacy Migration
```

---

## Çıktılar

| Çıktı | Hedef |
|-------|-------|
| Workspace isolation implementation | Phase 1 |
| Cross-tenant test suite | Phase 2 |
| Replay deterministic proof | Phase 3 |
| FK integrity evidence | Phase 4 |
| Certification package | Sprint end |

---

**Board Decision:** Sprint 12B Workspace Tenant Isolation ile başlatılmıştır.
