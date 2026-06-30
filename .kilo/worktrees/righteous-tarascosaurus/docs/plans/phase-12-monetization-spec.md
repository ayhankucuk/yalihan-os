# Phase 12: SaaS Monetization & Global Expansion Specification

## 🛡️ Executive Summary
Phase 12 represents the transition of Yalıhan AI OS from a technical success to a commercial authority. This phase establishes a **Multi-Tenant Monetization Core** protected by the **Cognitive Guardian (Bekçi v2.1)**. The architecture ensures mathematical determinism in financial flows and absolute isolation between tenants.

## 🏗️ Architectural Directives

### 1. Tenant-Aware Billing Ledger (SAB §12.1)
The financial backbone of the system will be hardened with strict tenant isolation.

#### [MODIFY] `app/Models/SaaS/BillingLedgerEntry.php`
- **Rename:** `amount` -> `islem_tutari`
- **Rename:** `tur` -> `islem_turu`
- **Constraint:** Append-only logic. Updates or deletes are blocked by `FinancialIntegrityGuard`.

#### [MODIFY] `app/Models/LedgerAccount.php`
- **Add:** `tenant_id` column.
- **Rule:** Every ledger account must belong to a specific tenant or the system (system_tenant_id = 0).

#### [MODIFY] `app/Services/FinancialLedgerService.php`
- **Evolution:** All methods must now accept or resolve a `tenant_id`.
- **Integrity:** `recordDoubleEntry` will verify that both accounts belong to the same tenant or involve a system-level fee account.

### 2. Credit-Based AI Guard (SAB §12.2)
AI usage is no longer "free"; it is a metered resource.

#### [NEW] `app/Services/AI/Monetization/AiBudgetGuard.php`
- **Purpose:** Acts as a **Circuit Breaker** for Cortex operations.
- **Contract:** Before any AI execution, `AiBudgetGuard::canExecute(Tenant $tenant, string $feature)` is called.
- **Logic:**
    1. Resolve `AiFeaturePrice` for the tenant's current plan.
    2. Check tenant's credit balance in `BillingLedger`.
    3. Return `bool` and estimated cost.
    4. If credits < cost, throw `InsufficientCreditsException`.

#### [MODIFY] `app/Services/AI/Cortex/CortexOrchestrator.php`
- **Integration:** Inject `AiBudgetGuard`.
- **Flow:** `checkBudget() -> execute() -> deductCredits()`.

### 3. Subscription & Feature Entitlements
Access to premium features is controlled via subscription status.

#### [NEW] `app/Http/Middleware/Subscription/FeatureEntitlementMiddleware.php`
- **Logic:** Verifies if the tenant's plan contains the required feature slug in the `features` array.
- **Anomaly Detection:** Tracks usage velocity. If a tenant consumes > 50% of monthly credits in < 1 hour, trigger a `Bilişsel Uyarı` (Cognitive Warning).

## 🛡️ Security & Integrity Guards

### Webhook Cognitive Verification
Payment webhooks (Stripe/Iyzico) must be verified beyond simple HMAC.
- **Cognitive Rule:** `WebhookSignatureGuard` will verify the IP origin, signature expiry, and idempotency of the payload.
- **Bekçi v2.1 Integration:** Any attempt to bypass webhook verification will trigger an immediate `SECURITY_LOCKDOWN`.

### Authority Leakage Prevention
- **AST Scanner Rule:** Any query to financial tables (`ledger_entries`, `billing_ledger_entries`) that lacks a `where('tenant_id', ...)` clause will fail the `quality:gate`.

## 📈 Monetization Tiers (Initial Proposal)

| Plan | Credits/Mo | Features |
|---|---|---|
| **Free** | 100 | Basic Search, Public AI |
| **Pro** | 5,000 | Cortex Match, Portfolio Doctor |
| **Enterprise** | Unlimited* | Custom AI Training, Market Intelligence |

---

> **"Financial Fortress: Zero Trust, Zero Drift, Zero Loss."**
> — SAB v24.2 (Cognitive Seal)
