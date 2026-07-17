# 05-BUSINESS-VALUE-MAP.md

## Business Value Map — Sprint 12D

### How to Read This Document
For every proposed entity, table, service, and event, each question must be answered:
1. Which real property operation does it automate?
2. Which spreadsheet, WhatsApp message, paper record or human memory does it replace?
3. Who uses it?
4. What mistake does it prevent?
5. Which BAI metric does it improve?
6. Is it required now or can it be deferred?

---

## 1. PropertyOwnership (Core — REQUIRED)

**Entity:** `property_ownerships` table + `PropertyOwnership` model + `PropertyOwnershipService`

| Question | Answer |
|----------|--------|
| Real property operation | Assigning legal owner(s) to a Property; recording percentage shares; transferring ownership when property is sold |
| Replaces | WhatsApp messages: "Bu mülkün sahibi kim?" — no verifiable answer. Spreadsheet: "Mülk sahipleri.xlsx" — not connected to system. Paper: title deed copies in advisor's drawer. |
| Who uses | Advisor (assigns), Owner (views their properties), Chief AI (queries ownership for decisions), Accounting (for future commission calculations) |
| Mistake prevented | Wrong owner charged for commission. Property assigned to previous owner after sale. Two advisors claim the same property. |
| BAI metric | **Automation Rate** — eliminates manual owner verification. **Decision Accuracy** — correct owner for every commission |
| Required now? | **YES — NOW** — Without this, no property can be assigned an owner in the canonical system. All other ownership features depend on this. |

---

## 2. PropertyRepresentative (Core — REQUIRED)

**Entity:** `property_representatives` table + `PropertyRepresentative` model + `PropertyRepresentativeService`

| Question | Answer |
|----------|--------|
| Real property operation | Recording who can legally represent the owner — sign documents, give instructions, receive payments |
| Replaces | WhatsApp: "Mülk sahibi nerede, kim temsil ediyor?" — advisor guesses. Paper: authorization letters in drawer. |
| Who uses | Advisor (records), Legal team (verifies), Future accounting (determines who signs invoices) |
| Mistake prevented | Giving instructions to the wrong person. Unauthorized person signing contracts. |
| BAI metric | **Decision Accuracy** — correct representative for every property |
| Required now? | **YES — NOW** — Required for legal compliance and future accounting. Multiple owners need clearly defined representatives. |

---

## 3. PropertyAccessAsset (Operations — REQUIRED)

**Entity:** `property_access_assets` table + `PropertyAccessAsset` model + `PropertyAccessAssetService`

| Question | Answer |
|----------|--------|
| Real property operation | Registering that a property has physical keys/cards/remotes; tracking who currently holds them |
| Replaces | WhatsApp: "Anahtar kimde?" — advisor checks his WhatsApp chat history. Paper: "Anahtar takip formu.xlsx" — often lost. |
| Who uses | Advisor (registers, transfers), Owner (requests key return), Property manager (coordinates access) |
| Mistake prevented | Showing a property without a key. Losing a key and not knowing who had it last. Giving access to wrong person. |
| BAI metric | **Operational Efficiency** — field operations without key-hunting delays. **Error Rate** — wrong key incidents |
| Required now? | **YES — NOW** — Advisors waste significant time finding out who has keys. Field operations are blocked by missing key information. |

---

## 4. PropertyKeyCustody (Operations — REQUIRED)

**Entity:** `property_key_custodies` table + `PropertyKeyCustody` model + `PropertyKeyCustodyService`

| Question | Answer |
|----------|--------|
| Real property operation | Recording every key custody transfer — who got the key, when, why |
| Replaces | WhatsApp: "Anahtarı verdim, not aldım mı?" — no record. Memory: advisor remembers but can't prove it. |
| Who uses | Advisor (records transfer), Owner (wants to know who has keys), Legal team (in case of dispute) |
| Mistake prevented | Disputed key handoffs. Lost keys with no accountability. Unauthorized person accessing property. |
| BAI metric | **Operational Efficiency** — no time wasted on key disputes. **Audit Readiness** |
| Required now? | **YES — NOW** — Essential for operational security. Required to answer "who had this key in March 2025?" |

---

## 5. PropertyDocument (Compliance — REQUIRED)

**Entity:** `property_documents` table + `PropertyDocument` model

| Question | Answer |
|----------|--------|
| Real property operation | Recording what documents exist for a property (title deed, management agreement, insurance), their expiry dates, and their storage location |
| Replaces | Physical folder: "Mülk dosyası" — often incomplete, can be lost. Spreadsheet: "Belge takip.xlsx" — not linked to system. |
| Who uses | Advisor (registers, checks expiry), Legal team (finds document quickly), Owner (requests copies) |
| Mistake prevented | Signing a deal without verifying title deed. Missing insurance policy. Expired documents not renewed. |
| BAI metric | **Compliance Score** — all required documents present and valid. **Decision Accuracy** |
| Required now? | **YES — NOW** — Expired title deeds and missing documents cause legal risk. |

---

## 6. Immutable Timeline Events (Audit — REQUIRED)

**Entity:** Domain events + `PropertyTimelineService`

| Question | Answer |
|----------|--------|
| Real property operation | Answering: "Bu mülkün sahibi ne zaman değişti?" — with evidence |
| Replaces | Human memory: "Sanırım 2024'te satıldı." — unreliable. Spreadsheet: not linked. |
| Who uses | Advisor (answers owner questions), Legal team (due diligence), Audit (compliance), Chief AI (property history for decisions) |
| Mistake prevented | Incorrect ownership history used in legal proceedings. Wrong person attributed to past transactions. |
| BAI metric | **Audit Readiness** — immutable evidence for every property decision. **Decision Accuracy** |
| Required now? | **YES — NOW** — Without immutable history, the system cannot answer the fundamental question: "who owned this property?" |

---

## 7. PropertyIdentity (Structural — REQUIRED)

**Entity:** `property_identity_json` column (structured) + Value Objects

| Question | Answer |
|----------|--------|
| Real property operation | Recording the full structural identity of a property: site name, block, building, entrance, floor, apartment number |
| Replaces | WhatsApp: "Bodrum'daki Residence'ın A blok 5. kat 12 numara." — ambiguous. Ilan address: free text, inconsistent. |
| Who uses | Advisor (registers, searches), Owner (identifies their unit), Field team (finds correct unit) |
| Mistake prevented | Showing wrong apartment in a complex. Confusion between similar units. |
| BAI metric | **Operational Efficiency** — correct property identification in field |
| Required now? | **YES — NOW** — Required to distinguish between multiple units in the same building. |

---

## 8. Legal Entity Support (Party Extension — REQUIRED)

**Entity:** Extension of `Kisi` model with company fields

| Question | Answer |
|----------|--------|
| Real property operation | Property owned by a company (LLC, corporation) rather than an individual |
| Replaces | Workaround: company listed as individual's name — wrong legal representation |
| Who uses | Advisor (assigns company as owner), Legal team (verifies company documents), Future accounting (company invoicing) |
| Mistake prevented | Individual signing for a company. Wrong tax identification. Commission paid to wrong entity. |
| BAI metric | **Decision Accuracy** — correct legal entity for contracts |
| Required now? | **YES — NOW** — Company-owned properties exist in portfolio. Cannot be represented without this. |

---

## 9. Ownership Share Validation (Business Rule — REQUIRED)

**Entity:** `PropertyOwnershipShareValidator` service + invariant

| Question | Answer |
|----------|--------|
| Real property operation | Ensuring that ownership shares sum to 100% across all active owners |
| Replaces | Manual calculation: "paylar doğru mu?" — human error. Spreadsheet validation — not enforced in system. |
| Who uses | Advisor (when assigning shares), System (automatically enforces) |
| Mistake prevented | Shares summing to 120% or 80%. Owner with 0% share listed as owner. |
| BAI metric | **Error Rate** — invalid ownership configurations |
| Required now? | **YES — NOW** — Without this, the system can record mathematically impossible ownership splits. |

---

## 10. OBS-1: PortfolioDriveWorkspace — NO CHANGE

**Entity:** `PortfolioDriveWorkspace` model (existing, no change)

| Question | Answer |
|----------|--------|
| This is | Drive metadata, not a competing workspace aggregate |
| Replaces | Nothing new — this is existing infrastructure |
| Who uses | DriveAgent, WorkspaceDashboardController, AI completion tracking |
| Mistake prevented | N/A — existing model |
| BAI metric | N/A |
| Required now? | N/A — OUT OF SCOPE for Sprint 12D changes |

---

## Rejected Proposals (No Concrete Operational Value)

| Proposed Entity | Rejection Reason |
|-----------------|------------------|
| `PropertyValuation` | External valuation — OUT OF SCOPE |
| `CommissionRate` | Accounting — OUT OF SCOPE |
| `OwnerStatement` | Accounting — OUT OF SCOPE |
| `ListingIntelligence` | External listing detection — OUT OF SCOPE |
| `ReservationAccounting` | Accounting — OUT OF SCOPE |

---

## BAI Metric Mapping

| BAI Metric | Sprint 12D Impact |
|------------|------------------|
| Automation Rate | ✅ High — owner assignment, key tracking, document expiry alerts |
| Decision Accuracy | ✅ High — correct owner, representative, legal entity |
| Operational Efficiency | ✅ High — key location, unit identification, document retrieval |
| Error Rate | ✅ High — share validation, tenant isolation, history mutation protection |
| Compliance Score | ✅ High — document expiry, title deed verification, authorized representatives |
| Audit Readiness | ✅ High — immutable ownership history, key custody log |

---

*Business value reviewed: 2026-07-17*
