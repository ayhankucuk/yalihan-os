# DOMAIN KNOWLEDGE: TELESCOPE

**LAST_VERIFIED_HEAD:** 32236d54
**Evidence Level:** REPO_VERIFIED (config)

---

## Business Concept: Telescope

**Tanım:**
- Laravel geliştirme/debugging aracı
- Request/response izleme
- Query ve cache debugging
- Event ve job monitoring

**PATH:** /telescope (default)
**CONFIG:** config/telescope.php

---

## Active Watchers

| Watcher | Status | Purpose |
|---------|--------|---------|
| Cache | OFF | Cache operations |
| Client Request | ON | External API calls |
| Command | OFF | CLI commands |
| DUMP | OFF | Var_dump output |
| Event | OFF | Event firing |
| Gate | ON | Authorization checks |
| Job | ON | Queue jobs |
| Log | OFF | Log entries |
| Mail | ON | Email sending |
| Model | ON | Model CRUD |
| Query | ON | Database queries |
| Request | ON | HTTP requests |

---

## Telescope URL

```
/telescope
```

**Auth:** Requires authentication (Telescope protected)

---

## What Gets Logged

| Category | What's Tracked |
|----------|----------------|
| Requests | Method, URI, headers, response |
| Queries | SQL, bindings, time |
| Models | Create, update, delete |
| Jobs | Queue name, payload, status |
| Gates | Policy/Gate checks |
| Mail | Email content, recipients |
| Client Requests | External API calls |

---

## Debugging Use Cases

| Scenario | How to Use |
|----------|------------|
| N+1 Query | Query watcher → slow queries |
| Auth failure | Gate watcher |
| Job failure | Job watcher |
| Model mutation | Model watcher |
| External API call | Client Request watcher |

---

## Configuration

**Enabled:** `TELESCOPE_ENABLED=true` (default: true in local)
**Path:** `TELESCOPE_PATH=telescope`

---

## Production Note

⚠️ **NOT RECOMMENDED in production** (performance impact)

Typically disabled in production:
```env
TELESCOPE_ENABLED=false
```

---

## Related Domains

| Domain | Connection |
|--------|------------|
| All domains | Debugging via watchers |
| Database | Query monitoring |
| Queue | Job monitoring |

---

## BUSINESS_UNKNOWN

| Question | Why Unknown |
|----------|------------|
| Actual production status? | Not verified |
| Retention policy? | Not documented |
| Storage driver? | Default: database |
