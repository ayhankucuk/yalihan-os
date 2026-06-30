# MCP Platform Architecture

> **Version:** 1.0.0  
> **Office:** Integration Office (SAAB v6)  
> **Date:** 2026-06-28  
> **Status:** APPROVED

---

## 1. Overview

The Model Context Protocol (MCP) Platform provides **standardized AI tool access** for YALIHAN OS. All AI services interact with external systems through MCP servers, enabling:

- **Uniform Interface** — One protocol for all AI-to-system interactions
- **Hot-Pluggable Tools** — Add new capabilities without AI service changes
- **Type Safety** — Strongly typed tool schemas
- **Observability** — Per-tool latency, error rates, usage metrics

---

## 2. Architecture

```
┌────────────────────────────────────────────────────────────────┐
│                        AI SERVICES                             │
│  YalihanCortex  │  AI Workforce  │  Agent Framework          │
└────────────────────────┬───────────────────────────────────────┘
                         │ MCP Client (stdio / HTTP)
                         │
┌────────────────────────▼───────────────────────────────────────┐
│                    MCP PLATFORM                                │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │              MCP Gateway (Router + Auth)                  │ │
│  └──────────────────────────────────────────────────────────┘ │
│                         │                                     │
│  ┌──────────┬──────────┴──────────┬──────────┬──────────┐ │
│  │ Google   │ Telegram  │ WhatsApp  │ Market   │ Custom   │ │
│  │ Workspace│   MCP     │   MCP     │  MCP     │  MCP     │ │
│  └──────────┴───────────┴───────────┴──────────┴──────────┘ │
└───────────────────────────────────────────────────────────────┘
```

---

## 3. MCP Server Registry

### 3.1 Google Workspace MCP

**Capabilities:**
- Gmail: read, send, search, labels
- Calendar: events CRUD, availability check
- Drive: file upload, download, share, search
- Contacts: read, write, group management

**Authentication:** OAuth 2.0 (user delegate or service account)

**Rate Limits:**
- Gmail: 1,000,000 quota units/day
- Calendar: 1,000,000 quota units/day
- Drive: 10,000 requests/day (write), unlimited (read)

### 3.2 Telegram MCP

**Capabilities:**
- Send/forward messages
- Manage groups and channels
- Handle callbacks and inline queries
- File operations

**Authentication:** Bot Token (BotFather)

**Rate Limits:**
- 30 msg/sec across all chats
- 20 msg/sec to specific chat

### 3.3 WhatsApp MCP

**Capabilities:**
- Send templates and reactive messages
- Media upload/download
- Group management
- Webhook event handling

**Authentication:** Business API credentials

**Rate Limits:** Per WhatsApp Business Policy

### 3.4 Marketplace MCP

**Capabilities:**
- Airbnb: listings sync, availability, pricing
- Sahibinden: property post, update, stats
- Hepsiemlak: listings sync, lead management

**Authentication:** Platform-specific API keys + OAuth

**Rate Limits:** Per platform (see EXTERNAL_SYSTEMS.md)

### 3.5 Custom MCP Servers

| Server | Purpose | Status |
|--------|---------|--------|
| CRM MCP | CRM integration (future) | Planned |
| Finance MCP | Accounting/Invoice sync | Planned |
| OpenClaw MCP | AI workforce task dispatch | Planned |

---

## 4. Tool Schema Standard

All MCP tools follow this schema:

```json
{
  "name": "string (snake_case)",
  "description": "string",
  "inputSchema": {
    "type": "object",
    "properties": {},
    "required": []
  },
  "outputSchema": {
    "type": "object"
  },
  "rateLimit": {
    "requests": 100,
    "window": "minute"
  }
}
```

---

## 5. MCP Gateway

### 5.1 Responsibilities
- **Routing:** Map AI requests to appropriate MCP server
- **Authentication:** Validate tenant + user credentials
- **Rate Limiting:** Per-tenant, per-tool limits
- **Caching:** Cache read-only tool results
- **Circuit Breaking:** Disable failing MCP servers

### 5.2 Configuration

```yaml
mcp_gateway:
  servers:
    google_workspace:
      type: stdio
      command: "php artisan mcp:server google-workspace"
      timeout: 30s
      
    telegram:
      type: stdio
      command: "php artisan mcp:server telegram"
      timeout: 10s
      
    whatsapp:
      type: http
      url: "http://whatsapp-mcp:8080"
      timeout: 15s
      
  routing:
    default_strategy: "least_loaded"
    fallback_strategy: "round_robin"
```

---

## 6. Implementation

### 6.1 Server Implementation Pattern

```php
// app/Mcp/Servers/GoogleWorkspaceServer.php
class GoogleWorkspaceServer implements McpServerInterface
{
    public function name(): string => 'google_workspace';
    
    public function tools(): array => [
        new McpTool('gmail_send', 'Send email', $this->gmailSchema()),
        new McpTool('calendar_create_event', 'Create calendar event', $this->calendarSchema()),
        new McpTool('drive_upload', 'Upload file to Drive', $this->driveSchema()),
    ];
    
    public function authenticate(McpCredentials $credentials): bool
    {
        // Validate OAuth token
    }
}
```

### 6.2 Client Usage

```php
// In AI service
$mcpResult = $this->mcpClient
    ->forTenant($tenant->id)
    ->tool('google_workspace', 'gmail_send')
    ->run([
        'to' => 'client@example.com',
        'subject' => 'Property Viewing Confirmation',
        'body' => 'Your viewing is confirmed for...'
    ]);
```

---

## 7. Error Handling

| Error Type | Behavior |
|------------|----------|
| **Auth Expired** | Auto-refresh token, retry once |
| **Rate Limited** | Queue with exponential backoff |
| **Server Down** | Circuit breaker trips, fallback response |
| **Timeout** | Return cached if available, else error |
| **Invalid Input** | Structured error with field validation |

---

## 8. Monitoring

### Metrics
- `mcp_tool_invocations_total{server, tool, status}`
- `mcp_tool_latency_seconds{server, tool}`
- `mcp_circuit_breaker_state{server}`
- `mcp_auth_failures_total{server}`

### Health Checks
- `/health/mcp` — Gateway health
- `/health/mcp/{server}` — Individual server health

---

*Document approved by SAAB v6 Integration Office*
