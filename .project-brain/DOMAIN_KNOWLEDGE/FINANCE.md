# DOMAIN KNOWLEDGE: FINANCE

**LAST_VERIFIED_HEAD:** a7aa6532
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: Finans

**Tanım:**
- Finansal işlem takibi
- Ödeme/tahsilat yönetimi
- AI destekli finansal analiz

**SOURCE:** FinansalIslem model, FinansService, FinansalIslemDurumu enum

---

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| State enum | FinansalIslemDurumu (4 states) | app/Enums/FinansalIslemDurumu.php |
| Model | FinansalIslem | app/Modules/Finans/Models/FinansalIslem.php |
| Manager | FinansalIslemManager | FinansService |

**Table:** finansal_islemler

---

## State Lifecycle (FinansalIslemDurumu)

| State | Label | Meaning |
|-------|-------|---------|
| BEKLIYOR | Bekliyor | Ödeme/tahsilat bekliyor |
| ONAYLANDI | Onaylandı | Yönetici onayladı |
| REDDEDILDI | Reddedildi | Reddedildi |
| TAMAMLANDI | Tamamlandı | İşlem tamamlandı |

**EVIDENCE:** REPO_VERIFIED

---

## FinansalIslem Properties

| Property | Type | Evidence |
|---------|------|----------|
| Ilan | belongsTo | ilan_id FK |
| Kişi | belongsTo | kisi_id FK |
| Görev | belongsTo | gorev_id FK |
| Onaylayan | belongsTo | onaylayan_id (User) |

---

## Services

| Service | Purpose | Evidence |
|---------|---------|----------|
| FinansService | AI analiz, tahmin | app/Modules/Finans/Services/FinansService.php |
| FinansalIslemManager | CRUD işlemleri | app/Modules/Finans/Services/FinansalIslemManager.php |

---

## FinansService Functions

| Function | Purpose |
|----------|---------|
| analyzeFinancials() | AI destekli finansal analiz |
| predictFinancials() | Gelecek tahmin (ay/dönem bazlı) |
| suggestInvoice() | Fatura önerisi |
| analyzeRisk() | Risk analizi |
| generateSummaryReport() | Özet rapor |

---

## AI Integration

**Service:** AIService (external)

**Use Cases:**
- Finansal trend analizi
- Ödeme tahmini
- Risk değerlendirmesi

**EVIDENCE:** REPO_VERIFIED

---

## Tenant Isolation

| Check | Status | Evidence |
|-------|--------|----------|
| BelongsToTenant | ❌ **NOT FOUND** | Model lacks trait |
| Tenant scope | UNKNOWN | Requires verification |

⚠️ **POTENTIAL ISSUE:** FinansalIslem modelinde BelongsToTenant trait yok.

---

## Reservation Connection

| Connection | Status | Evidence |
|-----------|--------|----------|
| total_amount in reservation | YES | IlanReservation model |
| FinansalIslem link | PARTIAL | Model has ilan_id |

**NOTE:** Rezervasyon → Finans bağlantısı mevcut ama otomatik değil.

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Ilan | ilan_id FK |
| Kisi | kisi_id FK |
| Gorev | gorev_id FK |
| User | onaylayan_id (approval) |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Tenant isolation | UNKNOWN | ⚠️ NOT TESTED |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Tenant isolation? | Trait not found in model |
| Payment integration? | Not documented |
| Auto-create on reservation? | Not verified |
| Invoice generation? | suggestInvoice exists, full flow unknown |

---

## CANDIDATE_FINDING

```
OBSERVATION: FinansalIslem model lacks BelongsToTenant trait
DOMAIN: Finance
EVIDENCE: grep -l "BelongsToTenant" FinansalIslem.php returned nothing
POTENTIAL_IMPACT: Cross-tenant financial data exposure risk
REQUIRES_REVALIDATION: YES
```

---

## Business Value Chain

```
Rezervasyon (total_amount)
        ↓
FinansalIslem (manual or AI suggestion)
        ↓
Ödeme/Tahsilat
        ↓
Raporlama
```
