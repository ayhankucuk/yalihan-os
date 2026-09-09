# Deployment Log

**Format:** Deployment events
**Last Updated:** 2026-09-03
**SSOT:** Bu dosya = Deployment geçmişi

---

## DEPLOYMENT LOG

| Date | Time | Commit | Branch | Environment | Migration | Rollback Plan | Deployed By | Status | Evidence |
|------|------|--------|--------|-------------|----------|---------------|-------------|--------|----------|
| 2026-09-03 | ~15:30 | `17aba4b` | `kilo/578f204-merge` | Production | ? | ? | Kilo | ✅ PUSHED | Remote tracked |
| 2026-09-02 | - | `578f204` | `codex/phase2-release` | Production | ✅ | ? | Codex | ✅ DONE | PRODUCTION_VERIFIED |
| 2026-08-29 | - | `7d402de` | - | Production | ✅ | ? | Codex | ✅ DONE | INCIDENT_LOG |
| 2026-08-28 | ~05:20 | `5198cbe` | - | Production | ✅ | ? | - | ✅ DONE | PROJECT_STATE |

---

## PRODUCTION INFRASTRUCTURE

| Component | Value | Status | Last Verified |
|-----------|-------|--------|--------------|
| Host | `157.180.116.63` | ✅ | 2026-08-28 |
| App Path | `/opt/yalihan2026/current` | ✅ | 2026-08-28 |
| Containers | `yalihanai-app-v2`, `yalihanai-nginx-v2`, `yalihanai-queue-v2` | ✅ | 2026-08-28 |
| Health Check | `{"success":true}` | ✅ | 2026-08-28 |
| MySQL Version | MySQL 8.0 | ✅ | - |
| Redis | 7-alpine with AOF | ✅ | - |

---

## MIGRATION STATUS

| Migration | Status | Risk | Notes |
|-----------|--------|------|-------|
| `add_missing_ozellikler_columns` | STAGED | MEDIUM | Wenox branch'te |
| `fix_ilanlar_aktiflik_durumu_schema_drift` | STAGED | MEDIUM | Wenox branch'te |
| `add_display_order_to_ups_feature_packs` | STAGED | LOW | Wenox branch'te |
| `add_deprecated_at_to_features` | STAGED | LOW | Wenox branch'te |
| `align_sqlite_schema_contract` | DELETED | - | Codex silmiş |

**Not:** Production migration deployment için explicit user authorization gerekli.

---

## BLOCKED_PENDING_PRODUCTION_AUTH

| Item | Reason | Submitted By | Date |
|------|--------|-------------|------|
| Schema drift migrations | Test environment only | Wenox | 2026-09-03 |

---

*Generated: 2026-09-03T13:50:00Z*
*Note: Production evidence requires live VPS access*
