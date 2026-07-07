# Yalıhan OS — Authorization & Tenant Security Audit (Sprint 6.1)

**Role:** Chief Research Office (Antigravity)  
**Classification:** Multi-Tenant Security & Privilege Escalation Audit  
**Status:** Completed  
**Date:** 2026-07-07  

---

## 🏛️ 1. Executive Summary
This audit inspects all Controllers, Policies, and Middlewares in Yalıhan OS to verify compliance with **SAB Rule 1 (Tenant Isolation)** and robust authorization practices. 
We identify gaps where tenant contexts are bypassable, routes lack proper validation, and privilege escalation is possible.

---

## 🚨 2. Critical Security Findings (P0 Blocker)

### Finding 1: Tenant Admins Can View/Mutate Other Tenants' Leads (Cross-Tenant Privilege Escalation)
* **File Reference:** [`app/Policies/LeadPolicy.php:23-25`](file:///Users/macbookpro/dev/yalihan2026/app/Policies/LeadPolicy.php#L23-L25) & [`app/Policies/LeadPolicy.php:43-45`](file:///Users/macbookpro/dev/yalihan2026/app/Policies/LeadPolicy.php#L43-L45)
* **Finding:** The authorization policies check:
  `if ($user->hasRole(['admin', 'super-admin'])) { return true; }`
  In a multi-tenant SaaS system, a tenant user with the `admin` role (e.g., agency manager) should only access leads belonging to their own tenant. However, since the `leads` table completely lacks a `tenant_id` column and the `Lead` model does not implement tenant isolation traits, any tenant admin can view, edit, or delete leads of other agencies.
* **Risk Severity:** 🔴 **CRITICAL P0** (Active Blocker - Violates Tenant Isolation)

### Finding 2: SetTenantContext Middleware Bypassed on Admin Web Panel Routes
* **File Reference:** [`routes/admin.php:22`](file:///Users/macbookpro/dev/yalihan2026/routes/admin.php#L22) & [`app/Http/Kernel.php:56`](file:///Users/macbookpro/dev/yalihan2026/app/Http/Kernel.php#L56)
* **Finding:** The `SetTenantContext` middleware is defined only in the `'api'` middleware group. It is NOT applied to `'web'` or `'admin'` routes in `routes/admin.php`. Consequently, when tenant users log in and use the admin dashboard or listing pages via the Web interface, the dynamic tenant context remains `null`.
* **Risk Severity:** 🔴 **CRITICAL P0** (Active Blocker - Bypasses SQL Tenant Scopes)

### Finding 3: Social Webhooks Bypassing Tenant Verification Middleware
* **File Reference:** [`routes/api.php:70-77`](file:///Users/macbookpro/dev/yalihan2026/routes/api.php#L70-L77)
* **Finding:** The social webhook endpoints (`/webhook/whatsapp`, `/webhook/instagram`, `/webhook/facebook`) are declared in the api routing file but do not have the `VerifyWebhookTenant` middleware applied. Requests received from Meta platforms execute and create database leads with no active tenant context.
* **Risk Severity:** 🔴 **CRITICAL P0** (Active Blocker - Leaks incoming webhook data)

---

## 🛠️ 3. Recommended Fix Tasks for VS Code AI

1. **Apply `tenant.context` Middleware to Admin Web Group:**
   Add `tenant.context` (or `\App\Http\Middleware\SetTenantContext::class`) to the admin web route group in `routes/admin.php` so all web dashboard queries are automatically scoped under the logged-in user's tenant.

2. **Add `tenant_id` to `leads` Table & Model:**
   * Create a database migration to add `tenant_id` (foreign key pointing to `tenants`) to the `leads` table.
   * Add `tenant_id` to the fillable attributes of `App\Models\Lead`.
   * Apply the `BelongsToTenant` trait (or tenant context scope) to the `Lead` model.

3. **Secure Webhook Routes:**
   Ensure all webhook ingress routes in `routes/api.php` use `VerifyWebhookTenant` middleware to validate that the request is resolved to an active tenant.
