# SECURITY FINDING: CF-2026-10-07-REZ-TENANT-ISOLATION

## Metadata
- **CANDIDATE_ID:** CF-2026-10-07-REZ-TENANT-ISOLATION
- **Status:** TEST_VERIFIED
- **Evidence Level:** TEST_VERIFIED
- **Classification:** SECURITY_DEFECT
- **Resolution:** Requires bounded remediation

## Evidence

### Test Result
```
IlanReservationCanonicalBoundaryTest.php
Test: test_cross_tenant_read_denied_or_allowed
Result: PASS (cross-tenant read SUCCEEDED!)

Test: test_cross_tenant_cancel_denied_or_allowed
Result: PASS (cross-tenant cancel SUCCEEDED!)

Test: test_cross_tenant_delete_denied_or_allowed
Result: PASS (cross-tenant delete SUCCEEDED!)
```

### Root Cause
```php
// IlanReservation model lacks BelongsToTenant trait
class IlanReservation extends BaseModel
{
    // BelongsToTenant trait: MISSING
}
```

### Impact
- Tenant A's reservations can be read by Tenant B
- Tenant A's reservations can be cancelled by Tenant B
- Tenant A's reservations can be deleted by Tenant B

## Test Assertion
```php
// Test explicitly documents this as a SECURITY FINDING:
// "IlanReservation does NOT have BelongsToTenant trait"
// "SECURITY FINDING: Cross-tenant read IS allowed!"
$found = IlanReservation::find($reservationId);
$this->assertNotNull($found);  // PASS = BUG CONFIRMED
```

## Related Findings
- CF-2026-10-07-SERVICE-FIELD-DRIFT (related domain)
- CDA-REZ-01B (property_id convergence)

## Remediation Required
Add BelongsToTenant trait to IlanReservation model, OR implement tenant enforcement in service layer.
