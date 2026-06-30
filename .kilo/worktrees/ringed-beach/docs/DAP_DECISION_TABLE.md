# DAP DECISION TABLE — Deterministik Çalıştırma Matrisi

> **Description**: Deterministic script execution matrix for DAP
> **Version**: 1.0

## Decision Matrix (Generated from JSON)

| Scope | Match Patterns | Scripts |
| :--- | :--- | :--- |
| **migrations** | `database/migrations/` | `php artisan sab:integrity-scan` |
| **models** | `app/Models/` | `php artisan sab:integrity-scan`<br>`npm run dap:drift` |
| **services** | `app/Services/` | `php artisan test`<br>`./scripts/quality-gate.sh` |
| **wizard** | `app/Http/Controllers/Wizard`<br>`resources/views/wizard` | `npm run e2e:wizard:smart` |
| **security** | `app/Http/Controllers/Auth`<br>`app/Policies` | `npm run security:audit` |
| **blade_css** | `resources/views/`<br>`resources/css/` | `npm run ci:darkmode` |
| **docs_only** | `docs/` | `node scripts/dap-docs-governance.cjs` |

## Always Run
The following scripts run on every execution:

- `./scripts/quality-gate.sh`
- `npm run dap:seal`

## Karar Algoritması
1. **git diff --name-only** ile değişen dosyaları al.
2. JSON kurallarına göre (**match**) eşleşenleri bul.
3. İlgili **scripts** listesini birleştir (merge unique).
4. **always** listesini ekle.
5. Fail durumunda `docs/_reports/FAIL_ANALYSIS.md` oluştur.

---
<!-- AUTO-GENERATED FROM DAP_DECISION_TABLE.json -->
<!-- DO NOT EDIT MANUALLY -->
<!-- TIMESTAMP: 2026-05-14T13:28:13.088Z -->
