# Sprint 12D Discovery Evidence
## Repository Baseline — 2026-07-17

---

### A. Git State

| Item | Value |
|------|-------|
| Current Branch | `main` |
| HEAD | `f9c7d89` (feat(S12C): canonicalize PropertyWorkspace ownership) |
| Sprint 12C Tag | `v12c-property-workspace-certified` ✅ EXISTS |
| Working Tree | Modified (skill files, CLAUDE.md, controllers, YalihanCortex) |
| Sprint 12C Certified | YES — commit f9c7d89 |

---

### B. Migration Status

```
Migration 49: create_properties_table           — Ran
Migration 50: backfill_property_id_for_legacy — Ran
Migration 51: add_property_id_fk_constraint    — Ran
Migration 52: add_property_id_to_property_workspaces — Ran
```

All Sprint 12C schema migrations applied and verified.

---

### C. Test Baseline

```
Tests:    8 failed, 6 incomplete, 144 passed (338 assertions)
Duration: 89.17s
```

Failures in `tests/Feature/Listing/ListingAggregateTest.php` — unrelated to Sprint 12D scope.

---

### D. Model Inventory (Relevant to Sprint 12D)

#### CANONICAL (Sprint 12C Certified)

| Model | File | Status | Notes |
|-------|------|--------|-------|
| `Property` | `app/Models/Property.php` | CANONICAL | Owns `workspace_id`, `tenant_id`. Immutable: `tkgm_id`, `ada`, `parsel`. `uuid`, `idempotency_key`. |
| `PropertyWorkspace` | `app/Models/PropertyWorkspace.php` | CANONICAL | 1:1 with Property. `workspace_uuid`, `intent`, `template_id`, `state`. |

#### EXISTING — Party Models

| Model | File | Classification | Notes |
|-------|------|----------------|-------|
| `Kisi` | `app/Models/Kisi.php` | REUSABLE | Natural person. 28 columns. `kisi_tipi` (Müşteri enum), `aktiflik_durumu`, `tc_kimlik`, `meslek`, `user_id`. FK: `ilan_sahibi_id` → `kisiler.id` |
| `Ilan` | `app/Models/Ilan.php` | REUSABLE (legacy) | Has `ilan_sahibi_id` FK to `kisiler`. Has `property_id` FK to `properties`. `workspace_id`. |
| `User` | `app/Models/User.php` | REUSABLE | Advisor/authenticated user. `owner_id` in report tables refers to User. |

#### EXISTING — Site/Property Structure

| Model | File | Classification | Notes |
|-------|------|----------------|-------|
| `Site` | `app/Models/Site.php` | LEGACY | `name`, `blok_adi`, `is_active`, `il_id`. SoftDeletes. |
| `SiteApartman` | `app/Models/SiteApartman.php` | LEGACY | Compound/apartment building. `toplam_daire_sayisi`, `kat_sayisi`, `aidat`, `yonetici`. |
| `PropertyReservation` | `app/Models/PropertyReservation.php` | REUSABLE | Rental reservation. Has `property_id`. |

#### EXISTING — OBS-1 Candidates

| Model | File | Classification | Notes |
|-------|------|----------------|-------|
| `PortfolioDriveWorkspace` | `app/Models/PortfolioDriveWorkspace.php` | VALID SEPARATE MODEL | Google Drive metadata + lifecycle state machine. `ilan_id` FK. `TenantScope`. AI completion tracking. 40+ methods. |
| `WorkspaceDashboardController` | `app/Http/Controllers/Admin/WorkspaceDashboardController.php` | VALID SEPARATE MODEL | Cockpit controller for PortfolioDriveWorkspace. Thin — delegates to services. |

#### LEGACY — Report Models (owner_id = User FK)

| Model | File | Notes |
|-------|------|-------|
| `OwnerReportMetric` | `app/Models/OwnerReportMetric.php` | `owner_id` → `users.id` |
| `OwnerReportExport` | `app/Models/OwnerReportExport.php` | `owner_id` → `users.id` |
| `OwnerReportRow` | `app/Models/OwnerReportRow.php` | `owner_id` → `users.id` |

⚠️ These `owner_id` references point to `User`, NOT to a Party/Owner entity. This is a **CONFLICTING** usage.

#### MISSING — No Existing Models

| Concept | Status |
|---------|--------|
| `PropertyOwnership` / `PropertyOwner` | MISSING — to be designed |
| `PropertyRepresentative` | MISSING — to be designed |
| `PropertyAccessAsset` / `PropertyKey` | MISSING — to be designed |
| `KeyCustody` / `KeyTransfer` | MISSING — to be designed |
| `PropertyDocument` | MISSING — to be reviewed against existing |
| `Party` / `LegalEntity` | MISSING — Kisi covers natural persons only |

---

### E. Schema Snapshots

#### `properties` table (Sprint 12C canonical)

```
id, tenant_id, canonical_reference, lifecycle_state, created_at, updated_at, deleted_at
```

**NOTE:** Properties table has NO `owner_id`, NO address fields, NO structural identity fields.
These are all MISSING — confirmed by schema.

#### `kisiler` table (Kisi / natural person)

```
id, danisman_id, referans_kisi_id, tenant_id, ad, soyad, eposta, telefon,
kaynak, telefon_2, tc_kimlik, adres, il_id, ilce_id, mahalle_id, meslek,
kisi_tipi (enum), crm_surec_asamasi, skor, aktiflik_durumu (bool),
notlar, son_etkilesim_tarihi, user_id, ulke_id
```

#### `ilanlar` table (relevant columns)

```
id, ..., ilan_sahibi_id → kisiler.id, property_id → properties.id,
workspace_id, ..., il_id, ilce_id, mahalle_id, lat, lng,
ada_no, parsel_no, ...
```

#### `property_workspaces` table (Sprint 12C canonical)

```
id, tenant_id, property_id (unique FK), workspace_uuid (unique),
intent, template_id, state, created_at, updated_at, deleted_at
```

---

### F. Key Discovery Findings

1. **No `owner_id` on Property** — Properties table has no ownership column. This is correct for immutable history design.
2. **No `PropertyOwnership` aggregate exists** — Must be created.
3. **No `Company` / `LegalEntity` model** — `Kisi` handles natural persons only. Company representation is MISSING.
4. **No key/access/custody model** — Completely absent from codebase.
5. **No document classification model** — No `PropertyDocument` or equivalent.
6. **No immutable ownership event model** — Domain events for ownership do not exist.
7. **`PortfolioDriveWorkspace` is a VALID SEPARATE MODEL** — Google Drive metadata, lifecycle state machine, AI completion. Not a competing Workspace aggregate. Does NOT conflict with `PropertyWorkspace`.
8. **Report tables use `owner_id` → `User`** — CONFLICTING: "owner" means advisor/report owner, not property owner.
9. **`Site` and `SiteApartman` are LEGACY** — Not referenced in Sprint 12C canonical Property model. Ad-hoc compound/building representation.
10. **`ilan_sahibi_id` → `kisiler`** — Legacy Ilan has owner as Kisi FK. Property does NOT have this — correct design.

---

*Evidence collected: 2026-07-17T20:15 EEST*
*Collector: Sprint 12D Discovery Agent*
