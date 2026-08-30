# Property Type Manager Review — Turistik Tesisler

Status: PLANNED / READ-ONLY FINDING
Evidence: BROWSER_VERIFIED, 2026-08-27
Scope: `/admin/property-type-manager/5`

## Finding

The live authenticated screen shows Turistik Tesisler with 3 subtypes, active `Turistik Tesisler Kiralik` and `Turistik Tesisler Satilik` publication types, `0 Alan`, and no assigned category features.

The field-dependencies screen confirms two publication-type tabs, `Turistik Tesisler Kiralik 0` and `Turistik Tesisler Satilik 0`, but renders no field rows or required/hidden/AI rule controls.

## Risks

- Category-specific hotel, pension, and holiday-village fields may not appear in the listing wizard.
- Required-field and publication-type rules may be incomplete or inconsistent.
- The absence of field rows makes it impossible to verify whether the UI is empty because configuration is missing or because the endpoint/rendering failed silently.
- Applying all global features without a reviewed matrix could create noisy or incorrect forms.

## Recommended work plan

1. Read-only inspect the 3 subtypes and their feature-manager/field-dependency payloads.
2. Map each subtype to required, optional, hidden, and AI-suggested fields.
3. Verify the network/API response and backend query for both tabs; distinguish empty configuration from a rendering/data-contract defect.
4. Compare against the Global Feature Pool and SMART FORMS rules; identify duplicates, missing domain fields, and type mismatches.
5. Run local/disposable contract and wizard tests; do not seed or modify production.
6. Prepare an impact analysis, data-contract check, and rollback plan.
7. Request explicit authorization before any production feature assignment, rule change, migration, or save action.

## Current boundary

No feature assignment, rule change, save, migration, seed, or production data mutation was performed.

## Global feature pool follow-up — 2026-08-27

The live Global Feature Pool shows 36 total features, 30 active, 0 passive, and 7 categories; the list is paginated 30 + 6. Visible feature types include Number, Boolean, Select, Text, and Multiselect. Every visible row shows `0 atama`.

Negative findings and recommendations:

- The counters are internally inconsistent (`36 total`, `30 active`, `0 passive` leaves 6 unexplained). Verify the query/count definitions before using these metrics operationally.
- All visible features show `0 atama`, while category screens also show no assigned features. This suggests the Property Engine is not yet configured for category-specific forms, or the usage counter is not counting the relevant relation.
- `Oda Sayısı` is a `Text` feature and `Bina Yaşı` is a `Select`; validate their canonical values and DB normalization against wizard/API contracts.
- `Isıtma` and `Soğutma` are `Multiselect`; validate array-to-string/pivot persistence and display behavior.
- Domain fit is not yet proven for Turistik Tesisler: pool, room capacity, license, fire safety, restaurant and accommodation fields are not visible in the global pool review.

Next work-plan item: reconcile counters and assignment relations, then define and test category/publication-type mappings before any production save.

## Template Manager follow-up — 2026-08-27

The live Template Manager shows 91 master templates, 35 total assignments and 0.4 average features/template. Most visible cards are empty (`0`, `Boş`); `Villa Gunluk` is the visible exception with 35 assignments and `İyi` status.

Risks and recommendations:

- Validate why edit links use `kategori_id=0`; prevent category context loss before enabling edits.
- Reconcile the assignment counters and define whether “assignment” means feature-to-template, category mapping, or another relation.
- Replace ASCII-only display labels with Turkish UI labels while preserving stable slugs.
- Add category/publication-type filters, grouped empty states, explicit completeness criteria, and a single prioritized “complete template” action.
- Treat `AI ile Oluştur` as a controlled write operation requiring preview, provenance, human approval, sealing, and rollback.
