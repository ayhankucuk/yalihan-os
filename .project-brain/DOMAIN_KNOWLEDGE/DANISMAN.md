# DOMAIN KNOWLEDGE: DANISMAN (ADVISOR)

**LAST_VERIFIED_HEAD:** 32236d54
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: Danışman (Advisor)

**Tanım:**
- Yalıhan Emlak satış danışmanları
- Müşteri (Kişi) yönetimi
- İlan ataması ve takibi

**SOURCE:** User model, DanismanController, AdvisorCommandCenterService

---

## Canonical Authority

| Concept | Authority | Evidence |
|---------|-----------|----------|
| Role enum | UserRole (3 roles) | app/Enums/UserRole.php |
| User-Tenant | belongsTo(Tenant::class) | User model |
| Ilan assignment | danisman_id FK | User hasMany(Ilan::class) |

---

## User Roles

| Role | Value | Label | Permissions |
|------|-------|-------|-------------|
| SUPERADMIN | super-admin | Süper Admin | Full access |
| DANISMAN | danisman | Danışman | CRM + Ilan management |
| EDITOR | editor | Editör | Limited editing |

**EVIDENCE:** UserRole enum

---

## Danışman Responsibilities

| Responsibility | Model Relation | Evidence |
|--------------|----------------|----------|
| Ilan atama | hasMany(Ilan::class) | User model |
| Kişi takibi | hasMany(Kisi::class) | User model |
| Talep takibi | hasMany(Talep::class) | User model |
| Yorumlar | hasMany(DanismanYorum::class) | User model |

---

## Command Center

**Service:** AdvisorCommandCenterService
**Purpose:** Dashboard data aggregation for advisors

**Functions:**
- getCommandCenterData() - Ana dashboard
- buildKpiSummary() - KPI özeti
- buildBuyerMatchPanel() - Alıcı eşleştirme paneli
- buildPriorityActions() - Öncelikli aksiyonlar

**EVIDENCE:** REPO_VERIFIED

---

## Tenant Context

| Check | Required | Evidence |
|-------|----------|----------|
| User belongs to Tenant | YES | User belongsTo(Tenant::class) |
| Scoped queries | YES | Model scopes |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| User role isolation | RBAC tests | PASS |

**EVIDENCE:** REPO_VERIFIED

---

## Related Domains

| Domain | Relationship |
|--------|--------------|
| Kisi | Danışman müşteri takip eder |
| Talep | Danışman talep yönetir |
| Ilan | Danışmana atanır |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Role-based permission matrix? | Partial |
| AI recommendations for danışman? | DanismanAIController exists |
| Performance metrics? | AdvisorAnalyticsService exists |
