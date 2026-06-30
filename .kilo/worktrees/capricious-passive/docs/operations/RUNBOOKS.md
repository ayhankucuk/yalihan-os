# YALIHAN OS — Operations Runbooks

**Version:** 1.0  
**Office:** Operations Office (SAAB v6)  
**Parent:** OPERATIONS_MANUAL.md  
**Date:** 2026-06-28  

---

## 1. Overview

This document contains executable runbooks for daily operations, routine maintenance, and common procedures. Each runbook provides step-by-step instructions that operations staff can execute without additional decision-making.

---

## 2. Daily Operations Runbooks

### 2.1 Morning Startup Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Morning Startup
# Frequency: Daily 07:00 UTC
# Owner: Operations Office
# ============================================================

echo "=== MORNING STARTUP $(date) ==="

# Step 1: System Health Check
echo "[1/8] Running system health check..."
./scripts/health-check.sh --full
if [ $? -ne 0 ]; then
    echo "ERROR: System health check failed"
    ./scripts/alert-oncall.sh --severity P1 --message "Morning health check failed"
fi

# Step 2: Agent Pool Status
echo "[2/8] Checking agent pool status..."
yalihan-cli agent list --status active --format json > /tmp/agent-status.json
ACTIVE_COUNT=$(jq '.agents | length' /tmp/agent-status.json)
echo "Active agents: $ACTIVE_COUNT"
if [ "$ACTIVE_COUNT" -lt 100 ]; then
    echo "WARNING: Low agent count"
fi

# Step 3: Queue Health
echo "[3/8] Checking queue health..."
./scripts/queue-stats.sh --all
QUEUE_DEPTH=$(./scripts/queue-stats.sh --total-depth)
if [ "$QUEUE_DEPTH" -gt 10000 ]; then
    echo "WARNING: High queue depth: $QUEUE_DEPTH"
fi

# Step 4: MCP Server Status
echo "[4/8] Checking MCP servers..."
for mcp in $(./scripts/list-mcp-servers.sh); do
    ./scripts/mcp-health.sh --server $mcp
    if [ $? -ne 0 ]; then
        echo "ERROR: MCP $mcp unhealthy"
    fi
done

# Step 5: Vector DB Status
echo "[5/8] Checking Vector DB..."
./scripts/vector-db-health.sh --cluster yalihan-primary
if [ $? -ne 0 ]; then
    echo "ERROR: Vector DB unhealthy"
fi

# Step 6: Token Usage Review
echo "[6/8] Reviewing token usage..."
./scripts/token-usage.sh --daily --report
DAILY_TOKENS=$(./scripts/token-usage.sh --daily-total)
BUDGET_TOKENS=10000000  # Daily budget
if [ "$DAILY_TOKENS" -gt "$BUDGET_TOKENS" ]; then
    echo "WARNING: Daily token budget exceeded"
fi

# Step 7: Backup Verification
echo "[7/8] Verifying backups..."
./scripts/verify-backups.sh --last-24h
if [ $? -ne 0 ]; then
    echo "ERROR: Backup verification failed"
fi

# Step 8: Memory Maintenance
echo "[8/8] Running memory maintenance..."
./scripts/memory-maintenance.sh --aggressive

echo "=== MORNING STARTUP COMPLETE ==="
echo "Report: /var/log/yalihan/startup-$(date +%Y%m%d).log"
```

### 2.2 Evening Shutdown Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Evening Shutdown
# Frequency: Daily 20:00 UTC
# Owner: Operations Office
# ============================================================

echo "=== EVENING SHUTDOWN $(date) ==="

# Step 1: Queue Drain Notification
echo "[1/6] Notifying queue drain..."
./scripts/queue-drain.sh --mode graceful --timeout 30m
DRAIN_RESULT=$?

# Step 2: Task Completion Report
echo "[2/6] Generating task completion report..."
./scripts/task-report.sh --daily --output /tmp/task-report-$(date +%Y%m%d).json

# Step 3: Session State Persistence
echo "[3/6] Persisting session states..."
./scripts/persist-sessions.sh --all

# Step 4: Log Archival
echo "[4/6] Archiving logs..."
./scripts/archive-logs.sh --day $(date +%Y%m%d) --destination cold-storage

# Step 5: Nightly Backup
echo "[5/6] Initiating nightly backup..."
./scripts/nightly-backup.sh --full

# Step 6: System Status Report
echo "[6/6] Generating status report..."
./scripts/status-report.sh --daily --notify

echo "=== EVENING SHUTDOWN COMPLETE ==="
```

### 2.3 Health Verification Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Health Verification
# Frequency: On-demand / Health check
# Owner: Operations Office
# ============================================================

check_service() {
    local service=$1
    local expected=$2
    
    STATUS=$(./scripts/service-status.sh --service $service)
    if [ "$STATUS" != "$expected" ]; then
        echo "FAIL: $service is $STATUS (expected: $expected)"
        return 1
    fi
    echo "PASS: $service is $expected"
    return 0
}

echo "=== HEALTH VERIFICATION $(date) ==="

FAILED=0

# Core Services
check_service "api-gateway" "healthy" || ((FAILED++))
check_service "agent-orchestrator" "healthy" || ((FAILED++))
check_service "queue-manager" "healthy" || ((FAILED++))
check_service "mcp-bus" "healthy" || ((FAILED++))
check_service "vector-db" "healthy" || ((FAILED++))

# Supporting Services
check_service "monitoring" "healthy" || ((FAILED++))
check_service "logging" "healthy" || ((FAILED++))
check_service "backup-service" "healthy" || ((FAILED++))

# Agent Pools
check_service "pool-strategic" "healthy" || ((FAILED++))
check_service "pool-tactical" "healthy" || ((FAILED++))
check_service "pool-operational" "healthy" || ((FAILED++))

echo ""
echo "=== HEALTH VERIFICATION SUMMARY ==="
echo "Failed checks: $FAILED"
if [ $FAILED -gt 0 ]; then
    exit 1
fi
exit 0
```

---

## 3. Agent Management Runbooks

### 3.1 Agent Activation Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Agent Activation
# Purpose: Activate a newly registered agent
# ============================================================

AGENT_ID=$1
APPROVED_BY=$2

if [ -z "$AGENT_ID" ] || [ -z "$APPROVED_BY" ]; then
    echo "Usage: activate-agent.sh <agent_id> <approved_by>"
    exit 1
fi

echo "=== AGENT ACTIVATION: $AGENT_ID ==="

# Pre-flight checks
echo "[1/7] Pre-flight checks..."
./scripts/pre-flight.sh --agent $AGENT_ID
if [ $? -ne 0 ]; then
    echo "ABORT: Pre-flight checks failed"
    exit 1
fi

# Verify registry entry
echo "[2/7] Verifying registry entry..."
REG_STATUS=$(yalihan-cli agent get --agent-id $AGENT_ID --field status)
if [ "$REG_STATUS" != "registered" ]; then
    echo "ABORT: Agent not in registered state"
    exit 1
fi

# Load identity package
echo "[3/7] Loading identity package..."
./scripts/load-identity.sh --agent $AGENT_ID

# Initialize MCP connections
echo "[4/7] Initializing MCP connections..."
./scripts/init-mcp.sh --agent $AGENT_ID

# Run warming task
echo "[5/7] Running warming task..."
./scripts/agent-warmup.sh --agent $AGENT_ID
if [ $? -ne 0 ]; then
    echo "WARNING: Warming task had issues"
fi

# Register with monitoring
echo "[6/7] Registering with monitoring..."
./scripts/register-monitoring.sh --agent $AGENT_ID

# Update status to active
echo "[7/7] Updating status to active..."
yalihan-cli agent activate --agent-id $AGENT_ID --approved-by $APPROVED_BY

echo "=== AGENT $AGENT_ID NOW ACTIVE ==="
```

### 3.2 Agent Suspension Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Agent Suspension
# Purpose: Suspend an agent (emergency or planned)
# ============================================================

AGENT_ID=$1
REASON=$2
APPROVED_BY=$3

echo "=== AGENT SUSPENSION: $AGENT_ID ==="
echo "Reason: $REASON"
echo "Approved by: $APPROVED_BY"

# Step 1: Stop accepting new tasks
echo "[1/5] Stopping new task acceptance..."
./scripts/drain-agent.sh --agent $AGENT_ID --mode immediate

# Step 2: Complete in-flight tasks (max 5 min)
echo "[2/5] Completing in-flight tasks..."
./scripts/drain-agent.sh --agent $AGENT_ID --mode graceful --timeout 5m

# Step 3: Save state
echo "[3/5] Saving agent state..."
./scripts/save-state.sh --agent $AGENT_ID --destination persistent-storage

# Step 4: Close MCP connections
echo "[4/5] Closing MCP connections..."
./scripts/close-mcp.sh --agent $AGENT_ID

# Step 5: Update registry
echo "[5/5] Updating registry to SUSPENDED..."
yalihan-cli agent suspend \
    --agent-id $AGENT_ID \
    --reason "$REASON" \
    --approved-by $APPROVED_BY

echo "=== AGENT $AGENT_ID SUSPENDED ==="
```

### 3.3 Agent Retirement Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Agent Retirement
# Purpose: Permanently retire an agent
# Requires: Executive approval + Legal sign-off
# ============================================================

AGENT_ID=$1
APPROVAL_ID=$2
EXECUTED_BY=$3

echo "=== AGENT RETIREMENT: $AGENT_ID ==="

# Verify approval
echo "[1/9] Verifying retirement approval..."
./scripts/verify-approval.sh --type RETIREMENT --approval-id $APPROVAL_ID
if [ $? -ne 0 ]; then
    echo "ABORT: Retirement approval not verified"
    exit 1
fi

# Verify no active tasks
echo "[2/9] Checking for active tasks..."
ACTIVE=$(./scripts/count-active-tasks.sh --agent $AGENT_ID)
if [ "$ACTIVE" -gt 0 ]; then
    echo "ABORT: Agent has $ACTIVE active tasks"
    exit 1
fi

# Export data for compliance
echo "[3/9] Exporting data for compliance..."
./scripts/export-agent-data.sh --agent $AGENT_ID --destination compliance-storage

# Purge PII data
echo "[4/9] Purging PII data..."
./scripts/purge-pii.sh --agent $AGENT_ID

# Revoke credentials
echo "[5/9] Revoking credentials..."
./scripts/revoke-credentials.sh --agent $AGENT_ID

# Rotate encryption keys
echo "[6/9] Rotating encryption keys..."
./scripts/rotate-keys.sh --agent $AGENT_ID

# Archive registry entry
echo "[7/9] Archiving registry entry..."
yalihan-cli agent retire \
    --agent-id $AGENT_ID \
    --approval $APPROVAL_ID \
    --executed-by $EXECUTED_BY

# Update documentation
echo "[8/9] Updating documentation..."
./scripts/update-runbooks.sh --remove-agent $AGENT_ID

# Final verification
echo "[9/9] Final verification..."
./scripts/verify-retirement.sh --agent $AGENT_ID

echo "=== AGENT $AGENT_ID RETIRED ==="
```

---

## 4. Deployment Runbooks

### 4.1 Standard Deployment Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Standard Deployment
# Purpose: Deploy a new release to production
# ============================================================

VERSION=$1
DEPLOYED_BY=$2

echo "=== DEPLOYMENT: v$VERSION ==="

# Pre-deployment checks
echo "[1/10] Pre-deployment checks..."
./scripts/pre-deploy.sh --version $VERSION
if [ $? -ne 0 ]; then
    echo "ABORT: Pre-deployment checks failed"
    exit 1
fi

# Backup current state
echo "[2/10] Backing up current state..."
./scripts/backup-current.sh --service yalihan-platform

# Deploy to canary (5% traffic)
echo "[3/10] Deploying to canary..."
./scripts/deploy.sh --version $VERSION --target canary --percentage 5

# Monitor canary
echo "[4/10] Monitoring canary (15 minutes)..."
./scripts/monitor-deployment.sh --deployment canary --duration 15m
if [ $? -ne 0 ]; then
    echo "ROLLBACK: Canary failed"
    ./scripts/rollback.sh --deployment canary
    exit 1
fi

# Expand to 25%
echo "[5/10] Expanding to 25%..."
./scripts/update-traffic.sh --deployment canary --percentage 25

# Monitor 25%
echo "[6/10] Monitoring at 25% (10 minutes)..."
./scripts/monitor-deployment.sh --deployment canary --duration 10m

# Expand to 50%
echo "[7/10] Expanding to 50%..."
./scripts/update-traffic.sh --deployment canary --percentage 50

# Full rollout
echo "[8/10] Full rollout..."
./scripts/update-traffic.sh --deployment canary --percentage 100

# Post-deployment verification
echo "[9/10] Post-deployment verification..."
./scripts/post-deploy-verify.sh --version $VERSION

# Update documentation
echo "[10/10] Updating documentation..."
./scripts/update-changelog.sh --version $VERSION --deployed-by $DEPLOYED_BY

echo "=== DEPLOYMENT v$VERSION COMPLETE ==="
```

### 4.2 Emergency Rollback Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Emergency Rollback
# Purpose: Immediately rollback a deployment
# ============================================================

echo "=== EMERGENCY ROLLBACK INITIATED ==="
echo "Time: $(date)"
echo "Triggered by: $USER"

# Immediate: Stop traffic to new version
echo "[1/4] Stopping traffic to new version..."
./scripts/stop-traffic.sh --all

# Restore previous version
echo "[2/4] Restoring previous version..."
./scripts/restore-version.sh --previous

# Verify restoration
echo "[3/4] Verifying restoration..."
./scripts/health-check.sh --full
if [ $? -ne 0 ]; then
    echo "CRITICAL: Restoration verification failed"
    ./scripts/alert-oncall.sh --severity P0 --message "Rollback verification failed"
fi

# Notify stakeholders
echo "[4/4] Notifying stakeholders..."
./scripts/notify-stakeholders.sh --type ROLLBACK

echo "=== ROLLBACK COMPLETE ==="
echo "Time: $(date)"
echo "NEXT STEP: Investigate root cause"
```

---

## 5. Maintenance Runbooks

### 5.1 Memory Maintenance Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Memory Maintenance
# Frequency: Daily (during low traffic)
# ============================================================

echo "=== MEMORY MAINTENANCE $(date) ==="

# Clean expired sessions
echo "[1/4] Cleaning expired sessions..."
./scripts/cleanup-sessions.sh --older-than 7d

# Purge old cache entries
echo "[2/4] Purging old cache entries..."
./scripts/purge-cache.sh --older-than 24h

# Vacuum old logs
echo "[3/4] Vacuuming old logs..."
./scripts/vacuum-logs.sh --older-than 30d

# Optimize database
echo "[4/4] Optimizing database..."
./scripts/db-optimize.sh

echo "=== MEMORY MAINTENANCE COMPLETE ==="
```

### 5.2 Queue Cleanup Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Queue Cleanup
# Frequency: Daily 06:00 UTC
# ============================================================

echo "=== QUEUE CLEANUP $(date) ==="

# Identify stale tasks
echo "[1/5] Identifying stale tasks..."
./scripts/identify-stale-tasks.sh --older-than 24h --output /tmp/stale-tasks.json

# Move to dead letter queue
echo "[2/5] Moving stale tasks to DLQ..."
STALE_COUNT=$(jq '.tasks | length' /tmp/stale-tasks.json)
echo "Moving $STALE_COUNT stale tasks to DLQ"
./scripts/move-to-dlq.sh --tasks /tmp/stale-tasks.json

# Purge expired messages
echo "[3/5] Purging expired messages..."
./scripts/purge-messages.sh --expired-before $(date -d '7 days ago' +%s)

# Recalculate priorities
echo "[4/5] Recalculating priorities..."
./scripts/recalculate-priorities.sh

# Generate queue report
echo "[5/5] Generating queue report..."
./scripts/queue-report.sh --daily

echo "=== QUEUE CLEANUP COMPLETE ==="
```

---

## 6. Emergency Runbooks

### 6.1 System Outage Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: System Outage Response
# Trigger: Multiple P0 alerts or system unreachable
# ============================================================

echo "=== SYSTEM OUTAGE RESPONSE $(date) ==="
echo "IMMEDIATE ACTIONS:"

# Step 1: Assess scope
echo "[1/8] Assessing outage scope..."
./scripts/assess-outage.sh

# Step 2: Activate incident
echo "[2/8] Activating incident response..."
./scripts/create-incident.sh --severity P0 --type OUTAGE

# Step 3: Notify on-call
echo "[3/8] Notifying on-call..."
./scripts/notify-oncall.sh --severity P0 --message "System outage detected"

# Step 4: Enable incident mode
echo "[4/8] Enabling incident mode..."
./scripts/enable-incident-mode.sh

# Step 5: Initiate recovery
echo "[5/8] Initiating recovery procedures..."
./scripts/emergency-recovery.sh

# Step 6: Communicate status
echo "[6/8] Communicating status..."
./scripts/notify-status.sh --status INVESTIGATING

# Step 7: Monitor recovery
echo "[7/8] Monitoring recovery..."
./scripts/monitor-recovery.sh

# Step 8: Verify service restoration
echo "[8/8] Verifying service restoration..."
./scripts/verify-restoration.sh

echo "=== SYSTEM OUTAGE RESPONSE INITIATED ==="
echo "NEXT: Follow INCIDENT_MANAGEMENT.md procedures"
```

### 6.2 Database Failover Runbook

```bash
#!/bin/bash
# ============================================================
# RUNBOOK: Database Failover
# Trigger: Primary database unreachable
# ============================================================

echo "=== DATABASE FAILOVER $(date) ==="

# Detect primary failure
echo "[1/7] Detecting primary failure..."
PRIMARY_STATUS=$(./scripts/db-status.sh --instance primary)
if [ "$PRIMARY_STATUS" == "healthy" ]; then
    echo "ABORT: Primary is healthy, no failover needed"
    exit 0
fi

# Promote standby
echo "[2/7] Promoting standby to primary..."
./scripts/promote-standby.sh

# Update connection strings
echo "[3/7] Updating connection strings..."
./scripts/update-connections.sh --new-primary

# Verify replication
echo "[4/7] Verifying replication..."
./scripts/verify-replication.sh

# Test connectivity
echo "[5/7] Testing connectivity..."
./scripts/test-db-connectivity.sh

# Update DNS if needed
echo "[6/7] Updating DNS if needed..."
./scripts/update-dns.sh --db-hostname

# Notify stakeholders
echo "[7/7] Notifying stakeholders..."
./scripts/notify-stakeholders.sh --type DB_FAILOVER

echo "=== DATABASE FAILOVER COMPLETE ==="
```

---

## 7. Runbook Administration

### 7.1 Runbook Versioning

```yaml
runbook_versioning:
  format: Semantic versioning (1.0.0)
  major:
    description: "New runbook or major procedure change"
    approval: "Operations Director"
  minor:
    description: "Procedure refinement, new steps"
    approval: "Operations Lead"
  patch:
    description: "Typo fix, formatting"
    approval: "Self-service"
```

### 7.2 Runbook Testing

| Test Type | Frequency | Owner | Pass Criteria |
|-----------|-----------|-------|---------------|
| Dry Run | Every change | Author | All steps execute |
| Shadow Run | Monthly | Operations Lead | No side effects |
| Full Test | Quarterly | Operations Director | All steps + verification |

### 7.3 Runbook Distribution

```
Runbooks are maintained in:
  docs/operations/RUNBOOKS.md (source)
  /opt/yalihan/runbooks/ (deployed)
  Confluence: Operations/Runbooks (reference)

Distribution: Automated sync on document update
```

---

**Document Owner:** Operations Office  
**Review Cycle:** Monthly  
**Related:** OPERATIONS_MANUAL.md, INCIDENT_MANAGEMENT.md  

---

*End of Operations Runbooks*
