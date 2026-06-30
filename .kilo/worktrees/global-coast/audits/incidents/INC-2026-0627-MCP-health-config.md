# INC-2026-0627-MCP-health-config — MCP Health Config Mismatch

**Date:** 2026-06-27
**Severity:** P3 (Low-Medium)
**Type:** Configuration Issue
**Status:** KNOWN_BUG
**Agent:** Kilo

---

## Summary

MCP server process is active (PID 45220) but `bekci:health` reports MCP at 0%.
The health check command references a missing script instead of detecting the running process.

---

## Evidence

### Process Status
```
PID 45220: node mcp-servers/yalihan-bekci-mcp.js
Status: Active
Started: 2026-06-27 08:56:24
```

### bekci:health Output
```
🏥 Yalıhan Bekçi Health Check
==================================================
⚠️ MCP Server: MCP Server offline or unreachable (0%)
✅ Knowledge Base: 41 learning entries, 5 recent (100%)
✅ Learning Activity: Learning from 40 actions (100%)
❌ Project Health: Overall project health: 59.25% (59.25%)

💡 Recommendations:
   • Start MCP server: ./scripts/services/start-bekci-server.sh
```

### Missing Resource
```
scripts/services/start-bekci-server.sh — DOES NOT EXIST
```

---

## Root Cause

The `bekci:health` command uses a hardcoded script path (`./scripts/services/start-bekci-server.sh`)
to detect MCP server status instead of checking for the actual running process by PID or port.

---

## Classification

| Aspect | Value |
|--------|-------|
| **Category** | Configuration Issue |
| **Runtime Crash** | NO — MCP process is healthy |
| **Health Score Impact** | 61.85% → 61.85% (MCP component is 0% of total) |
| **Sprint Blocker** | NO — MCP server is functional |
| **CI/CD Impact** | NO |

---

## Impact Assessment

- **Health Score:** 61.85% (unchanged — MCP was already 0% before this session)
- **MCP Functionality:** Working — tools are available
- **Test Coverage:** No impact
- **Production Risk:** None

---

## Recommended Fix

1. **Short-term:** Create `scripts/services/start-bekci-server.sh` to match expected path
   OR
   Update `bekci:health` command to detect running MCP process by:
   - Process name: `yalihan-bekci-mcp.js`
   - PID file: `storage/pids/mcp.pid`
   - Port check: stdio (no port-based detection possible)

2. **Long-term:** Implement proper MCP health endpoint:
   ```php
   // Add health check endpoint to MCP server
   // that responds to HTTP GET /health
   ```

---

## Related Files

- `mcp-servers/yalihan-bekci-mcp.js` — Running MCP server
- `app/Console/Commands/BekciHealthCommand.php` — Health check command
- `scripts/services/start-bekci-server.sh` — Missing script (should exist)

---

## Audit Trail

| Date | Agent | Action |
|------|-------|--------|
| 2026-06-27 | Kilo | Issue identified during Session 44 |
| 2026-06-27 | Kilo | Audit note created |

---

## Decision

**No immediate action required.** MCP server is functional.
This is a monitoring/reporting issue, not a runtime issue.
Fix scheduled for Sprint 3.x maintenance cycle.