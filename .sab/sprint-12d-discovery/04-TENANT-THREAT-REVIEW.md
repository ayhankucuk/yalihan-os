# 04-TENANT-THREAT-REVIEW.md

## Tenant Isolation and Security Threat Review — Sprint 12D

---

### Threat Model

All entities in Sprint 12D operate in a multi-tenant environment where:
- `tenant_id` is the isolation boundary
- All queries MUST be scoped by tenant
- Cross-tenant access is the highest severity violation

---

## Threat 1: Cross-Tenant Owner Assignment

**Scenario:** Attacker in Tenant A assigns ownership of Property B (Tenant B) to Kisi C (Tenant A).

**Attack vector:**
```php
// Malicious actor injects property_id from another tenant
POST /api/property-ownership
{
  "property_id": 999,      // belongs to Tenant B
  "kisi_id": 123,         // belongs to Tenant A
  "pay_orani": 1.0
}
```

**Mitigation:**
```php
// In PropertyOwnershipService::assignOwnership()
$property = Property::query()
    ->where('id', $data['property_id'])
    ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
    ->firstOrFail();

$kisi = Kisi::query()
    ->where('id', $data['kisi_id'])
    ->where('tenant_id', $this->tenantContext->getCurrentTenantId())
    ->firstOrFail();
```

**Service-layer responsibility:** `PropertyOwnershipService` MUST verify both `property.tenant_id == kisi.tenant_id == current_tenant` before any write.

**Verdict:** MITIGATED — Service-layer dual-tenant verification required.

---

## Threat 2: Cross-Tenant Property Association

**Scenario:** Attacker associates their Property with another tenant's Workspace.

**Attack vector:** Inject `workspace_id` from another tenant into Property creation.

**Mitigation:**
```php
// Property::booted() invariant already checks workspace_id
// Sprint 12C: Property requires Workspace
// Workspace has TenantScope — global scope enforces tenant isolation
```

**Verdict:** MITIGATED — Sprint 12C invariant + Workspace TenantScope.

---

## Threat 3: Direct ID Injection (Property ID)

**Scenario:** Attacker accesses ownership records of Property they don't own.

**Attack vector:**
```php
GET /api/properties/999/ownership
// property 999 belongs to Tenant B
// attacker is in Tenant A
```

**Mitigation:**
```php
// PropertyOwnershipService::getOwnership()
$property = Property::query()
    ->where('id', $propertyId)
    ->where('tenant_id', $this->tenant->id())
    ->firstOrFail();

$ownerships = PropertyOwnership::query()
    ->where('property_id', $property->id)
    ->where('tenant_id', $this->tenant->id())
    ->get();
```

**Verdict:** MITIGATED — All reads are gated through tenant-scoped Property lookup.

---

## Threat 4: Global-Scope Bypass

**Scenario:** A model without TenantScope allows global access.

**Current state:**
- `Property` — has `BelongsToTenant` trait ✅
- `PropertyWorkspace` — has TenantScope ✅
- `Kisi` — has NO explicit TenantScope (only HasActiveScope) ⚠️
- `PortfolioDriveWorkspace` — has TenantScope ✅

**Threat:** `Kisi` model has `tenant_id` column but no global scope enforcing it.

**Mitigation for Sprint 12D:**
```php
// Add TenantScope to Kisi OR enforce via service layer
class KisiService
{
    public function assignOwnership(Kisi $kisi, Property $property): void
    {
        if ($kisi->tenant_id !== $this->tenant->id()) {
            throw new TenantIsolationViolation('Kisi does not belong to current tenant.');
        }
    }
}
```

**Verdict:** PARTIAL RISK — Kisi lacks TenantScope. Enforce via service-layer in Sprint 12D ownership service. Consider adding TenantScope to Kisi as a separate micro-task.

---

## Threat 5: Unauthorized Ownership Transfer

**Scenario:** Advisor A transfers ownership of Property X from Owner B to Owner C without authorization.

**Attack vector:**
```php
// Advisor A (Tenant A) calls transferOwnership on Property X owned by Advisor B (Tenant A)
// Both in same tenant — this is an authorization problem, not tenant problem
```

**Mitigation:**
```php
// Authorization rule: only tenant admin or property manager can transfer ownership
// Policy check before service call
PropertyOwnershipPolicy::canTransferOwnership(User $user, Property $property): bool
```

**Verdict:** MITIGATED — Policy layer + service-layer authorization check.

---

## Threat 6: Unauthorized Percentage Changes

**Scenario:** Advisor changes ownership share from 50%/50% to 100% without both owners' consent.

**Attack vector:** Direct PATCH to ownership record.

**Mitigation:**
```php
// PropertyOwnership record is IMMUTABLE — no UPDATE allowed
// Changing share = close current record + open new record
// This requires a TRANSFER command, not a PATCH
PropertyOwnershipService::changeShare(
    propertyId,
    oldOwnershipId,
    newShare,
    newOwnerId,     // may be same or different
    authorizationActor
): void

// The command creates a CLOSING event + OPENING event
// Both owners (or authorized rep) must sign off
```

**Verdict:** MITIGATED — Immutable records prevent in-place mutation. Share changes require closing + opening with proper authorization.

---

## Threat 7: Unauthorized Key Transfer

**Scenario:** Person A transfers custody of a property key to Person B without authorization.

**Attack vector:**
```php
POST /api/property-access-assets/5/transfer
{ "new_holder_id": 999, "note": "temporary loan" }
```

**Mitigation:**
```php
// Only property owner or authorized representative can transfer key custody
PropertyAccessAssetPolicy::canTransferCustody(User $user, PropertyAccessAsset $asset): bool

// Tenant check on both asset and new holder
PropertyAccessAssetService::transfer(assetId, newHolderId, actor, note)
{
    $asset = PropertyAccessAsset::where('id', $assetId)
        ->where('tenant_id', $this->tenant->id())
        ->firstOrFail();

    $newHolder = Kisi::where('id', $newHolderId)
        ->where('tenant_id', $this->tenant->id())
        ->firstOrFail();
}
```

**Verdict:** MITIGATED — Policy + service-layer tenant verification.

---

## Threat 8: Sensitive Key-Code Exposure

**Scenario:** Alarm code or smart lock credential is exposed in API response to unauthorized user.

**Attack vector:**
```php
GET /api/property-access-assets/5
// Response includes: { "tanimlayici_no": "1234" } // alarm code
```

**Mitigation:**
```php
// PropertyAccessAsset: sensitive fields are NOT exposed in default serialization
// Only users with PropertyAccessAssetPolicy::VIEW_CREDENTIALS can see codes
class PropertyAccessAsset extends Model
{
    protected $hidden = ['tanimlayici_no']; // hidden by default

    public function getCredentialForViewer(User $user, PropertyAccessAsset $asset): ?string
    {
        if (!$user->can('view-credentials', $asset)) {
            return null;
        }
        return $asset->tanimlayici_no;
    }
}
```

**Verdict:** MITIGATED — Sensitive fields hidden, revealed only via explicit policy-gated accessor.

---

## Threat 9: Unauthorized Document Access

**Scenario:** Advisor accesses title deed of a property they don't manage.

**Attack vector:**
```php
GET /api/property-documents?property_id=999
```

**Mitigation:**
```php
// Document access gated through Property ownership check
PropertyDocumentController::index(Property $property)
{
    $this->authorize('view', $property); // Policy checks ownership/authorization
}
```

**Verdict:** MITIGATED — Controller-level authorization via PropertyPolicy.

---

## Threat 10: Ownership History Mutation

**Scenario:** Attacker updates `bitis_tarihi` on an ownership record to hide a previous owner.

**Attack vector:**
```php
PATCH /api/property-ownership/5
{ "bitis_tarihi": "2025-01-01" }  // retroactive closure
```

**Mitigation:**
```php
// PropertyOwnership model: no UPDATE allowed
// Only close operation via dedicated command
class PropertyOwnership extends Model
{
    public static function boot(): void
    {
        parent::boot();

        static::updating(function (PropertyOwnership $record) {
            throw new \DomainException(
                'PropertyOwnership records are immutable. Use closeOwnership() command.'
            );
        });

        static::deleting(function (PropertyOwnership $record) {
            throw new \DomainException(
                'PropertyOwnership records cannot be deleted. History must be preserved.'
            );
        });
    }
}
```

**Verdict:** MITIGATED — Model-level write protection on immutable records.

---

## Threat 11: Forged Event Replay

**Scenario:** Attacker replays a captured ownership assignment event to reassign ownership.

**Attack vector:**
```php
// Event captured: PropertyOwnershipAssigned { propertyId: 5, kisiId: 123, ... }
// Replayed to duplicate assignment
```

**Mitigation:**
```php
// Idempotency key on every command
PropertyOwnershipService::assignOwnership(Property $property, Kisi $kisi, ...): PropertyOwnership
{
    $idempotencyKey = $this->idempotencyKeyGenerator->generate(...);

    // Check if already processed
    $existing = PropertyOwnership::where('idempotency_key', $idempotencyKey)->first();
    if ($existing) {
        return $existing; // Idempotent replay — return existing record
    }

    // Process new assignment
}
```

**Verdict:** MITIGATED — Idempotency key on every ownership command prevents replay duplication.

---

## Threat 12: Duplicate Command Delivery

**Scenario:** Network retry causes duplicate ownership assignment.

**Attack vector:** HTTP POST with duplicate request on timeout retry.

**Mitigation:**
```php
// Client provides idempotency key in X-Idempotency-Key header
// Service checks and returns existing result
PropertyOwnershipService::assignOwnership(
    Property $property,
    Kisi $kisi,
    string $idempotencyKey
): PropertyOwnership
```

**Verdict:** MITIGATED — Idempotency key pattern.

---

## Summary: Threat Register

| # | Threat | Severity | Status | Mitigation |
|---|--------|----------|--------|------------|
| 1 | Cross-tenant owner assignment | CRITICAL | MITIGATED | Dual-tenant verification in service |
| 2 | Cross-tenant Property-Workspace | CRITICAL | MITIGATED | Sprint 12C invariant + TenantScope |
| 3 | Direct ID injection | HIGH | MITIGATED | Tenant-gated Property lookup |
| 4 | Global-scope bypass (Kisi) | HIGH | PARTIAL RISK | Service-layer enforcement now; TenantScope later |
| 5 | Unauthorized ownership transfer | HIGH | MITIGATED | Policy + authorization |
| 6 | Unauthorized share change | HIGH | MITIGATED | Immutable records + close+open pattern |
| 7 | Unauthorized key transfer | HIGH | MITIGATED | Policy + tenant check |
| 8 | Key-code exposure | HIGH | MITIGATED | Hidden fields + policy-gated accessor |
| 9 | Unauthorized document access | MEDIUM | MITIGATED | PropertyPolicy authorization |
| 10 | Ownership history mutation | CRITICAL | MITIGATED | Model-level write protection |
| 11 | Forged event replay | HIGH | MITIGATED | Idempotency key |
| 12 | Duplicate command delivery | MEDIUM | MITIGATED | Idempotency key |

**Overall: 11 MITIGATED, 1 PARTIAL RISK (Kisi TenantScope)**

---

## Service-Layer Authorization Matrix

| Command | Actor Check | Property Owner Check | Representative Check | Tenant Check |
|---------|-------------|---------------------|---------------------|--------------|
| `assignOwnership` | ✅ | N/A | N/A | ✅ |
| `transferOwnership` | ✅ | ✅ (all active) | ✅ | ✅ |
| `changeShare` | ✅ | ✅ (all active) | ✅ | ✅ |
| `assignRepresentative` | ✅ | ✅ | N/A | ✅ |
| `revokeRepresentative` | ✅ | ✅ | ✅ (self-revoke) | ✅ |
| `registerAccessAsset` | ✅ | ✅ | ✅ | ✅ |
| `transferCustody` | ✅ | ✅ | ✅ | ✅ |
| `reportKeyLost` | ✅ | ✅ | ✅ | ✅ |
| `deactivateCredential` | ✅ | ✅ | ✅ | ✅ |
| `attachDocument` | ✅ | ✅ | ✅ | ✅ |

---

*Threat review completed: 2026-07-17*
