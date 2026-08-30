# Test Failure Categorization — 2026-08-30

> Repository Commit: 166663c
> Working Tree: integration/era-v-phase2a-e01 (26 commits ahead of remote)
> Evidence Date: 2026-08-30
> Evidence Level: VERIFIED — full `php artisan test` run
> Production Authorization: NONE — read-only analysis
> Analysis Level: P0 ROOT CAUSE — read-only code inspection

## Summary

```
Tests: 301 failed, 3 risky, 11 incomplete, 83 skipped, 2615 passed
Assertions: 13740
Duration: 1626.03s (~27 minutes)
Total tests: 3010
Fail test classes: 68
Fail test cases (⨯): 305
```

## Category 1 — SQLite Schema Gap (HIGH PRIORITY)

**Root cause:** `phpunit.xml` uses SQLite (`DB_CONNECTION=sqlite`) but some tables are not created by migrations — they only exist in MySQL schema dump.

**Affected tables:**
- `tenants` — `no such table: tenants` (3+ occurrences)
- `property_availability` — `no such table: property_availability` (known-debt #37)

**Affected test classes:**
- Tests\Feature\ChannelManager\AvailabilitySynchronizationServiceTest
- Tests\Feature\ChannelManager\ChannexCanonicalMutationTest
- Tests\Feature\ChannelManager\SynchronizeAvailabilityJobRetryTest
- Tests\Feature\CheckinCheckoutWave3Test
- Tests\Feature\CheckinCheckoutWave4Test
- Tests\Feature\CheckinCheckoutWave5Test
- Tests\Feature\Ydl\Reservation\YdlReservationCancellationTest

**Fix:** Either add SQLite-compatible migration for `property_availability` and `tenants`, or switch `phpunit.xml` to MySQL test database.

## Category 2 — UniqueConstraint: iller.id (HIGH PRIORITY)

**Root cause:** Test fixtures insert into `iller` table with hardcoded IDs (1, 48) that already exist from seeder. SQLite enforces UNIQUE constraint.

**Pattern:** `UNIQUE constraint failed: iller.id` — 14 occurrences

**Affected test classes:**
- Tests\Unit\Services\Location\TKGMGeocodeJobTest
- Tests\Feature\AI\DescriptionReviewModalTest
- Tests\Feature\Workspace\WorkspaceSubmissionTest
- Tests\Feature\Finance\PropertyReservationsSchemaConvergenceTest

**Fix:** Use `firstOrCreate` or `updateOrCreate` instead of `insert` with hardcoded IDs in fixtures. Or use `RefreshDatabase` trait to get clean DB per test.

## Category 3 — NOT NULL Constraint: tenant_id (HIGH PRIORITY)

**Root cause:** `kisiler` table requires `tenant_id` but test fixtures don't set it.

**Pattern:** `NOT NULL constraint failed: kisiler.tenant_id` — 2+ occurrences

**Affected test classes:**
- Tests\Feature\Domain\Kisi\KisiDomainIsolationTest
- Tests\Feature\Security\TenantIsolationSafetyTest

**Fix:** Set `tenant_id` in test fixtures, or use factory with tenant_id default.

## Category 4 — ReflectionException / Class Not Found (MEDIUM)

**Root cause:** 16 occurrences — class references that don't resolve in test environment.

**Affected test classes:**
- Tests\Feature\Architecture\HasActiveScopeTraitTest
- Tests\Feature\GuardCoverageRegressionTest
- Tests\Unit\Scripts\CiGuardRawDbWriteTest

**Fix:** Verify class aliases, autoload paths, or mark as skipped if testing internal architecture.

## Category 5 — Authorization / Tenant Isolation (MEDIUM)

**Root cause:** Cross-tenant access tests expecting 404/403 but getting different status codes.

**Affected test classes:**
- Tests\Feature\Admin\IlanControllerAuthorizationTest (9 fails)
- Tests\Feature\Admin\TalepControllerAuthorizationTest (8 fails)
- Tests\Feature\Owner\OwnerIlanCrudTest
- Tests\Feature\Owner\OwnerIlanValuationTest

**Fix:** Verify tenant context is properly set in test setup. Check `SetTenantContext` middleware behavior in test environment.

## Category 6 — AI / Service Mock Issues (MEDIUM)

**Root cause:** AI service tests failing due to mock configuration, API key absence, or service contract changes.

**Affected test classes:**
- Tests\Feature\AI\AIResilienceTest (3 fails)
- Tests\Feature\AI\ConversationalAdvisorIntentTest (1 fail)
- Tests\Feature\AI\DescriptionReviewModalTest (6 fails)
- Tests\Feature\AI\FeatureFeedbackContractTest (2 fails)
- Tests\Unit\Service\PriceAdvisorServiceTest (3 fails)

**Fix:** Update mock configurations, verify service contracts, check env requirements.

## Category 7 — Finance / Channel (MEDIUM)

**Affected test classes:**
- Tests\Feature\Finance\C4ChannelFeeSnapshotTest
- Tests\Feature\Finance\C5\C5WizardMediaContractTest
- Tests\Feature\Finance\FinanceSmokeSealTest
- Tests\Feature\Finance\YalihanTreasuryTest
- Tests\Feature\Finance\PropertyReservationsSchemaConvergenceTest

**Fix:** Verify finance module schema alignment, check reservation canonical columns.

## Category 8 — Other (LOW-MEDIUM)

**Affected test classes:**
- Tests\Feature\Chaos\ChaosPerformanceTest
- Tests\Feature\Chaos\ChaosRedisFailureTest
- Tests\Feature\Chaos\ChaosSignatureTamperTest
- Tests\Feature\Concierge\GuestConciergePhase1Test (30 fails)
- Tests\Feature\Concierge\GuestConciergePilotReadinessTest
- Tests\Feature\Telegram\CallbackQueryProcessorTest
- Tests\Feature\Performance\N1QueryOptimizationTest
- Tests\Feature\Reliability\CqrsDriftRecoveryTest
- Tests\Feature\Reliability\IdempotentBillingTest
- Tests\Feature\Repositories\IlanRepositoryWriteHardeningTest
- Tests\Feature\RootSmokeTest
- Tests\Feature\SmartProviderSelectionTest
- Tests\Feature\TakimYonetimiSmokeTest
- Tests\Feature\TaskAuthorityTest
- Tests\Feature\Wizard\Step4GisPersistenceContractTest
- Tests\Feature\Wizard\WizardStep1TemplateDataTest
- Tests\Feature\Workspace\WorkspaceSubmissionTest
- Tests\Feature\Property\PropertyAggregateTest
- Tests\Feature\Property\S12D\PropertyOwnershipTest
- Tests\Feature\Rental\InvestorIntelligenceTest
- Tests\Feature\Governance\GovernanceTimelineTest
- Tests\Feature\CRMRadarTest
- Tests\Feature\AdvisorPhotoUploadTest (6 fails)
- Tests\Feature\Api\BulkListingAuthorityBridgeTest
- Tests\Feature\Api\IlanQueryConsistencyTest (3 fails)
- Tests\Feature\Api\Mobile\NotificationTest
- Tests\Feature\Api\Mobile\ProfileTest
- Tests\Feature\Api\Mobile\SavedSearchTest
- Tests\Feature\Admin\DashboardControllerTest
- Tests\Feature\Admin\DeepSeekSettingsTest
- Tests\Feature\Admin\PropertyHubAIAuthorityBridgeTest
- Tests\Feature\Admin\WizardCopilotActionApiTest (4 fails)
- Tests\Unit\Domain\PropertyWorkspace\PropertyWorkspaceAggregateTest
- Tests\Unit\Domain\PropertyWorkspace\Timeline\WorkspaceTimelineTest
- Tests\Unit\Models\IlanKategoriTest
- Tests\Unit\Models\UserTest
- Tests\Unit\Services\Matching\DemandMatchingEngineTest (3 fails)
- Tests\Unit\Services\Property\PropertyBulkOperationsServiceTest (7 fails)

## Priority Matrix

| Priority | Category | Est. Fail Count | Fix Effort | Impact |
|----------|----------|-----------------|------------|--------|
| P0 | SQLite schema gap (tenants, property_availability) | ~40 | Medium — add migrations or switch to MySQL | Unblocks Channel, Checkin, Ydl tests |
| P0 | UniqueConstraint iller.id | ~14 | Low — use firstOrCreate in fixtures | Unblocks Location, AI, Workspace tests |
| P0 | NOT NULL tenant_id | ~5 | Low — set tenant_id in fixtures | Unblocks Kisi, Security tests |
| P1 | Authorization/tenant isolation | ~25 | Medium — verify tenant context setup | Unblocks Admin, Owner tests |
| P1 | AI/service mock | ~15 | Medium — update mocks/contracts | Unblocks AI module tests |
| P1 | Finance/channel | ~15 | Medium — schema alignment | Unblocks Finance tests |
| P2 | ReflectionException | ~16 | Low — fix autoload/skip | Unblocks Architecture tests |
| P2 | Other | ~175 | Varied | Remaining fails |

## Recommended First Wave Fixes

1. **SQLite schema gap** — Add `property_availability` and `tenants` table migrations for SQLite, or switch phpunit.xml to MySQL
2. **iller.id fixture** — Replace hardcoded ID inserts with `firstOrCreate` in all test fixtures
3. **tenant_id fixture** — Ensure all model factories set `tenant_id` by default
4. **Authorization setup** — Verify `SetTenantContext` middleware in test environment

These 4 fixes would resolve an estimated ~84 failures (28% of total).

---

## P0 Root Cause Analysis (Read-Only Code Inspection)

### P0-A: SQLite Schema Gap — property_availability & tenants

Root cause: tests/TestCase.php line 156-163 — SQLite mode runs only `Artisan::call('migrate')`, does NOT load mysql-schema.sql dump. `property_availability` has NO migration (only in SQL dump). `tenants` migration exists but may fail due to prior migration halting the chain.

Fix: Add SQLite migration for property_availability, OR load testing-schema.sql for SQLite, OR switch phpunit.xml to MySQL.

### P0-B: iller.id UniqueConstraint

Root cause: Test fixtures use `DB::table('iller')->insert()` with hardcoded IDs. Safe pattern exists: `insertOrIgnore` in TalepControllerAuthorizationTest.

Fix: Replace `insert` with `insertOrIgnore` or `firstOrCreate` in iller fixtures.

### P0-C: kisiler.tenant_id NOT NULL

Root cause: Direct `DB::table('kisiler')->insertGetId()` in KisiTest.php and TalepTest.php bypasses BelongsToTenant trait. TestCase.php already provides `getDefaultTenantId()` method.

Fix: Add `'tenant_id' => $this->getDefaultTenantId()` to direct kisiler inserts.

### P0 Cascade: Fixing tenants table (P0-A) may auto-resolve tenant_id (P0-C) because injectDefaultTenantContext() would succeed.
