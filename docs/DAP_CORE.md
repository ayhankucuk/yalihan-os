# 🛡️ DAP CORE — Yalıhan Deterministik Otopilot Sistemi

> **Status**: Operational SSOT
> **Components**: Navigation Map + Decision Matrix
> **Version**: 1.1.0 (Consolidated)

---

## 1. Navigasyon Haritası (DAP MAP)

Tüm otomasyon ve AI ajanları bu haritaya danışarak çalışır.

### A) Tek Kaynaklar (SSOTs)

| Kaynak | Dosya | Not |
| :--- | :--- | :--- |
| **Otorite (Source)** | `.sab/authority.json` | **EDITABLE** (JSON SSOT) |
| **Decision (Source)** | `docs/DAP_DECISION_TABLE.json` | **EDITABLE** (JSON SSOT) |
| **Gate** | `./scripts/quality-gate.sh` | Tek geçit. Exit 0 = PASS |
| **Workflow** | `npm run dap:autopilot` | Tek canonical entrypoint |
| **Scanner Config** | `.context7/scanning-config.json` | V2_CORE_ONLY mode |

### B) Yürütme Sırası (DAP Autopilot)

1. **dap:detect** → `sab:integrity-scan` + `security-audit`
2. **dap:drift** → `dap-drift-check.cjs` (Workflow bütünlüğü)
3. **dap:docs** → `dap-docs-governance.cjs` (Stale doc arşivleme)
4. **dap:verify** → `quality-gate.sh` (TEK GATE — fail = stop)
5. **dap:seal** → `dap-seal.cjs` (Mühürleme)

---

## 2. Karar Matrisi (DAP DECISION TABLE)

Aşağıdaki tablo `DAP_DECISION_TABLE.json` kaynağından otomatik üretilir.

| Scope | Match Patterns | Scripts |
| :--- | :--- | :--- |
| **migrations** | `database/migrations/` | `php artisan sab:integrity-scan` |
| **models** | `app/Models/` | `php artisan sab:integrity-scan`<br>`npm run dap:drift` |
| **services** | `app/Services/` | `php artisan test`<br>`./scripts/quality-gate.sh` |
| **wizard** | `app/Http/Controllers/Wizard`<br>`resources/views/wizard` | `npm run e2e:wizard:smart` |
| **security** | `app/Http/Controllers/Auth`<br>`app/Policies` | `npm run security:audit` |
| **blade_css** | `resources/views/`<br>`resources/css/` | `npm run ci:darkmode` |
| **docs_only** | `docs/` | `node scripts/dap-docs-governance.cjs` |

### Always Run
- `./scripts/quality-gate.sh`
- `npm run dap:seal`

---

## 3. Operasyonel Kurallar

### Yazma İzinleri
- `docs/_reports/*` (Raporlar)
- `docs/registry/FAZLAR_GECMIS_RAPORLAR.md` (Mühür kaydı)

### Yasaklar
- `.sab/authority.json` manuel düzenleme (Governance task olmadan).
- `status`, `active`, `order` isimlendirmeleri (Context7 aykırı).
- `quality-gate.sh` atlama.

---
*Son Güncelleme: 2026-05-14 | Consolidation of DAP_MAP and DAP_DECISION_TABLE*
