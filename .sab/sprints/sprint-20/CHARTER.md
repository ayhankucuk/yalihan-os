# Sprint 20 Charter — Commercial Offering Aggregate

**Status:** 🟢 APPROVED (REVISION 1)
**Date:** 2026-07-26
**Author:** Chief AI + Agent (Kilo)
**Board Resolution:** BR-20260726-SPRINT20
**Reviewed by:** SAAB Board

---

## Strategic Context

### Property Intelligence OS Roadmap

```
Sprint 11 ✅ Property Aggregate Root
Sprint 12 ✅ Property → Listing Bridge
Sprint 13 ✅ Replay & Recovery
Sprint 14 ✅ BAI Metrics
Sprint 15 ✅ M2 Runtime
Sprint 16 ✅ Property Command Center + CommercialOffering Model
─────────────────────────────────────────
Sprint 20 → CommercialOffering Aggregate Root
```

### Architectural Gap

```
Property (Aggregate Root ✅)
    │
    └── CommercialOffering (BaseModel + Service ⚠️)
            ├── No Domain Invariants
            ├── No State Machine
            ├── No Aggregate Root Pattern
            └── No Tenant Isolation Tests
```

### Sprint 20 Soru

> **"YALIHAN, bir emlak danışmanının aynı mülk için farklı fiyatlandırma senaryolarını (satılık / kiralık / sezonluk) tek bir aggregate içinde yönetmesini sağlayacak mı?"**

---

## Sprint 20 Mission

> **"CommercialOffering'ı Domain Aggregate Root olarak tamamlayarak, tek mülk → çoklu sunum mimarisini güvenilir şekilde uygulamak."**

---

## Deliverables

### Program A — CommercialOffering Aggregate Root (P0)

**Soru:** CommercialOffering Domain Driven Design aggregate root olarak doğru tasarlanmış mı?

**Deliverables:**
- [ ] CommercialOfferingValueObjects: Money, DateRange, Commission
- [ ] Domain Invariants enforced (e.g., bitis_tarihi > baslangic_tarihi)
- [ ] Aggregate Root class (extends DomainAggregate)
- [ ] Repository Interface + Eloquent Implementation
- [ ] State Machine: DRAFT → ACTIVE → ARCHIVED

**Kalite Kapıları:**
- [ ] Domain invariant violation test: bitis < baslangic → DomainException
- [ ] State transition test: DRAFT → ACTIVE → ARCHIVED
- [ ] Aggregate replay-safe
- [ ] Aggregate invariants korunuyor (tek write path üzerinden)
- [ ] Idempotency: aynı operasyon tekrar çalıştırıldığında tutarlı durum

**Test Strategy:**
- Unit: Domain Invariants, Value Objects, State Machine
- Feature: CRUD + Tenant Isolation
- Integration: Property ↔ Offering ↔ Events

### Program B — Property ↔ CommercialOffering Integration (P0)

**Soru:** Property'den CommercialOffering'a doğru referans zinciri var mı?

**Deliverables:**
- [ ] Property aggregate'a addOffering() method
- [ ] Property aggregate'a getOfferings() method
- [ ] Property → CommercialOffering 1:N relation (Eloquent)
- [ ] Business rule: Tek mülk = Birden fazla offering (SATILIK, KIRALIK, SEZONLUK)

**Kalite Kapıları:**
- [ ] Test: Property'ye offering ekleme
- [ ] Test: Property'den offerings listesi alma
- [ ] Test: Offering silindiğinde Property'den çıkıyor
- [ ] Test: Property state → Offering state bağımlılığı (varsa)
- [ ] Replay-safe: Offering ekleme event replay edilebiliyor

### Program C — Tenant Isolation (P0)

**Soru:** CommercialOffering tenant-isolated mu?

**Deliverables:**
- [ ] TenantContextService entegrasyonu
- [ ] TenantScope global scope (varsa)
- [ ] Tenant isolation feature tests

**Kalite Kapıları:**
- [ ] Test: Tenant A offering'ine Tenant B erişemez
- [ ] Test: Offering create/update tenant context gerektirir

### Program D — API Surface (P1)

**Soru:** CommercialOffering HTTP API üzerinden erişilebilir mi?

**Deliverables:**
- [ ] CommercialOfferingController (index, show, store, update, destroy)
- [ ] Form Request validation classes
- [ ] API routes

**Kalite Kapıları:**
- [ ] Feature test: CRUD operations
- [ ] Tenant isolation at controller level

---

## Sprint 20 DoD

Sprint sonunda şu sorulara "evet" denebilmeli:

- [ ] CommercialOffering Aggregate Root çalışıyor mu?
- [ ] Domain invariants testlerle korunuyor mu?
- [ ] Property ↔ Offering entegrasyonu çalışıyor mu?
- [ ] Tenant isolation enforced mu?
- [ ] Replay-safe tasarım doğrulanmış mı?

---

## Board Questions

### Soru 1
> "Bu sprintte YALIHAN hangi gerçek emlak operasyonunu tamamladı?"

### Soru 2
> "Bu operasyon için danışmanın kaç dakika manuel çalışması ortadan kalktı?"

### Soru 3
> "Tek mülk → çoklu sunum senaryosu (SATILIK + KIRALIK + SEZONLUK) artık YALIHAN'da çalışıyor mu?"

### Soru 4
> "Bir Property aynı anda birden fazla aktif CommercialOffering taşıyabilir mi ve bu kurallar nasıl yönetilecek?"

### Soru 5
> "CommercialOffering yaşam döngüsü Property yaşam döngüsünden hangi noktalarda bağımsız, hangi noktalarda bağımlı olacak?"

---

## Definition of Done

```
Aggregate Root
        ↓
Domain Invariants
        ↓
Property Integration
        ↓
Tenant Isolation Tests
        ↓
API Surface
        ↓
Sprint Review
```

---

## Key Files to Create

| Dosya | Açıklama |
|-------|-----------|
| `app/Domain/CommercialOffering/` | Domain folder |
| `app/Domain/CommercialOffering/CommercialOfferingAggregate.php` | Aggregate Root |
| `app/Domain/CommercialOffering/ValueObjects/` | VOs (Money, DateRange, Commission) |
| `app/Domain/CommercialOffering/CommercialOfferingStateMachine.php` | State transitions |
| `app/Repositories/CommercialOfferingRepositoryInterface.php` | Contract |
| `app/Repositories/EloquentCommercialOfferingRepository.php` | Implementation |
| `app/Http/Controllers/Admin/CommercialOfferingController.php` | CRUD |
| `app/Http/Requests/CommercialOffering/` | Form requests |
| `tests/Unit/Domain/CommercialOffering/` | Domain tests |
| `tests/Feature/CommercialOffering/` | Feature tests |

---

## Risk Assessment

| Risk | Olasılık | Etki | Çözüm |
|------|----------|------|-------|
| Property aggregate değişikliği Breaking Change | Orta | Yüksek | Feature flag + backward compat |
| Migration gerekirse | Düşük | Orta | Property ID nullable, FK sonra ekle |
| State machine complex olursa | Orta | Orta | Basit string → enum → full state machine |

---

## Estimated Duration

**2-3 oturum** (Domain modeling + implementation + tests)

---

## Priority Sequence (SAAB Board)

1. CommercialOffering Aggregate Root
2. Value Objects (Money, DateRange, Commission)
3. State Machine (DRAFT → ACTIVE → ARCHIVED)
4. Property ↔ CommercialOffering entegrasyonu
5. Tenant Isolation
6. API Surface
7. Sertifikasyon ve kanıt üretimi

---

## Aggregate Boundary Questions (Sprint 20 Answers)

### Soru 4 Cevabı
> "Bir Property aynı anda birden fazla aktif CommercialOffering taşıyabilir mi?"

**Karar:** EVET — Farklı offering_type'lar için (SATILIK, KIRALIK, SEZONLUK) birden fazla ACTIVE offering olabilir.
Aynı offering_type için sadece bir ACTIVE offering olabilir.
Business rule: `UNIQUE(offering_type) WHERE yayin_durumu = 'ACTIVE'`

### Soru 5 Cevabı
> "CommercialOffering bağımsız mı, bağımlı mı?"

**Karar:**
- Bağımsız: Offering create/update/archive
- Bağımlı: Property ARŞİVLENİRSE → Offering'ler de arşive gider
- Bağımsız: Property silinemez (soft delete), offering'ler kalır

---

*CommercialOffering Aggregate Root — Sprint 20 — APPROVED*
*Revision 1 — 2026-07-26*
