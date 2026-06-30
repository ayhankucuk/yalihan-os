# TESTSPRITE MCP CRITICAL PATH TEST PLAN

## PHASE 1 - CQRS / ASYNC PIPELINE TEST
- **Aim**: Ensure listings created via Admin pass through the event-driven projection queue and land in AI Search.
- **Action**: Admin login -> Create listing -> Verify `ListingCreated` event -> Verify `SyncListingProjection` job -> Public search returns the new listing.
- **Fail Rule**: Search index empty / projection missing.

## PHASE 2 - SECURITY BOUNDARY TEST
- **Endpoint**: `POST /api/v1/public-ai/ilan-arama`
- **Action**: Execute public search.
- **Expected**: `owner_id`, `danisman_id`, `metadata`, and internal fields MUST NOT exist in the JSON response.
- **Fail Rule**: Private field leak.

## PHASE 3 - GRACEFUL DEGRADATION TEST
- **Aim**: AI Engine closure must not break the system.
- **Action**: Simulate Ollama timeout or disable. Execute public search.
- **Expected**: HTTP 200 OK. JSON contains `status: degraded` and `fallback_used: true`.
- **Fail Rule**: 500 Server Error.

## PHASE 4 - CANONICAL VISIBILITY TEST
- **Aim**: Public search strictly isolates internal states.
- **Dataset**: Listings with states (`taslak`, `arsiv`, `satildi`, `yayinda`).
- **Expected**: Only `yayinda` state is returned in public queries.

## PHASE 5 - AUTH JSON COMPLIANCE
- **Endpoint**: `POST /api/v1/auth/login`
- **Action**: Send valid, invalid, and missing credentials.
- **Expected**: Strict JSON response. `status: success` with `data` or `status: error` with `error_code` (e.g. `VALIDATION_ERROR`, `UNAUTHORIZED`).
- **Fail Rule**: HTML redirect or non-JSON response.

## PHASE 6 - ADMIN LOGIN TEST
- **Endpoints**: `GET /admin/login`, `POST /admin/login`
- **Action**: Check page load, valid/invalid login, and property hub redirect.

## PHASE 7 - LISTING WIZARD TEST
- **Action**: Create listing, validation rejection (empty fields), cancel flow, keyboard (Enter) submit.
- **Expected**: Correct listing stored with status `yayinda`.

## PHASE 8 - E2E LISTING → SEARCH
- **Flow**: Admin Login -> Wizard Create -> Projection Job runs -> Public AI Search retrieves it.

## PHASE 9 - SEMANTIC SEARCH TEST
- **Queries**: "bodrum havuzlu villa", "bodrum marina yakın villa", "bodrum deniz manzaralı villa"
- **Multi-lingual**: "villa with pool bodrum", "villa con piscina bodrum"
- **Expected**: Top K relevant results returned.

## PHASE 10 - CONCURRENCY TEST
- **Simulation**: 50 concurrent requests to `/api/v1/public-ai/ilan-arama`.
- **Expected**: 0 crashes, stable latency, no 500 errors.

---
**GUARD COMMANDS AFTER TESTS**
```bash
php artisan route:list
php artisan test
php artisan listings:rebuild-projection
```
