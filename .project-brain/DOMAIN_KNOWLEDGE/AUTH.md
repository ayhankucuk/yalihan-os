# DOMAIN KNOWLEDGE: AUTH & AUTHORIZATION

**LAST_VERIFIED_HEAD:** a7aa6532
**Evidence Level:** REPO_VERIFIED

---

## Business Concept: Auth & Authorization

**Tanım:**
- Kullanıcı kimlik doğrulama
- Rol tabanlı yetkilendirme
- Policy/Gate tabanlı erişim kontrolü

**SOURCE:** AuthServiceProvider, Policies, Guards

---

## Authentication

| Method | Evidence |
|--------|----------|
| Session | Laravel default |
| API Token | Laravel Sanctum |
| Google OAuth | GoogleOAuthController |

---

## Roles (UserRole Enum)

| Role | Value | Label | Permissions |
|------|-------|-------|-------------|
| SUPERADMIN | super-admin | Süper Admin | Full access |
| DANISMAN | danisman | Danışman | CRM + Ilan management |
| EDITOR | editor | Editör | Limited editing |

---

## Policies (14 adet)

| Policy | Domain | Evidence |
|--------|--------|----------|
| IlanPolicy | Ilan | Authorization |
| TalepPolicy | Talep | Authorization |
| KisiPolicy | Kisi | Authorization |
| PropertyReservationPolicy | Reservation | Authorization |
| DanismanPolicy | Danışman | Authorization |
| FeaturePolicy | Features | Authorization |
| LeadPolicy | CRM/Lead | Authorization |
| FavoriPolicy | Favoriler | Authorization |
| CommunicationSeverityPolicy | Communication | Authorization |
| IlanKategoriPolicy | Kategori | Authorization |
| OzellikKategoriPolicy | Özellik | Authorization |
| OwnerReportPolicy | Reports | Authorization |
| PortfolioDriveWorkspacePolicy | Workspace | Authorization |
| Api\* | API | Authorization |

---

## Policy Structure

```
Policy::class
        ↓
before() hook (optional)
        ↓
Gate::define() or Policy method
        ↓
Authorized or Denied
```

---

## Authorization Flow

```
Request
        ↓
Middleware (auth:web, auth:api)
        ↓
Policy Check (authorize())
        ↓
Gate Check (Gate::allows())
        ↓
Allow/Deny Response
```

---

## Gate Definitions

**Location:** AuthServiceProvider

**Example:**
```php
Gate::define('action', function (User $user, $model) {
    return $user->role === 'admin';
});
```

---

## Route Model Binding

| Binding | Policy | Evidence |
|---------|--------|----------|
| Ilan | IlanPolicy | REPO_VERIFIED |
| Talep | TalepPolicy | REPO_VERIFIED |
| Kisi | KisiPolicy | REPO_VERIFIED |
| Reservation | PropertyReservationPolicy | REPO_VERIFIED |

---

## Tenant Isolation

| Check | Method | Evidence |
|-------|--------|----------|
| Auth | Session/API guard | Laravel |
| Authorization | Policy | Per-domain |
| Tenant scope | BelongsToTenant | Model trait |

**Note:** Tenant isolation AUTH değil, domain layer'da çözülür.

---

## Related Domains

| Domain | Policy |
|--------|--------|
| Ilan | IlanPolicy |
| Talep | TalepPolicy |
| Kisi | KisiPolicy |
| Reservation | PropertyReservationPolicy |

---

## TESTED INVARIANTS

| Invariant | Test | Result |
|----------|------|--------|
| Auth/RBAC tests | RBAC tests | PASS |

**EVIDENCE:** REPO_VERIFIED

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Complete permission matrix? | Partial |
| API token scopes? | Not documented |
| OAuth flow details? | GoogleOAuthController exists |
| Guest/anonymous access? | Not verified |
