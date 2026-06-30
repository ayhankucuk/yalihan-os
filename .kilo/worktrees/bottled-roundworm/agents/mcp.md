# MCP Agent Instructions

> Yalıhan Bekçi MCP Server — Konfigürasyon ve kullanım.

## MCP Server Yapısı

```
yalihan2026/
├── mcp/                          # TypeScript MCP Bridge
│   ├── src/index.ts              # Kaynak (TypeScript)
│   ├── build/index.js             # Derlenmiş (Node.js)
│   └── package.json
│
├── mcp-servers/                  # JavaScript MCP Server
│   ├── yalihan-bekci-mcp.js      # Ana MCP server
│   ├── mcp-health-bridge.js
│   └── notebooklm-mcp/
```

## İki Implementasyon

| | TypeScript Bridge | JavaScript Server |
|--|--|--|
| **Kullanıcı** | Windsurf (`.roo/mcp.json`) | Cursor (`.cursor/mcp.json`), Claude |
| **Tool sayısı** | 3 | 9 |
| **PHP entegrasyonu** | ✅ artisan çağırır | ✅ guard scripts çalıştırır |
| **Knowledge base** | ❌ | ✅ |
| **Port** | stdio | stdio |

## MCP Tool'ları (JavaScript)

| Tool | Açıklama |
|------|---------|
| `validate_file` | Dosya guard kontrolü |
| `get_canonical` | Context7 canonical isim sorgula |
| `check_violation` | Kod snippet ihlal kontrolü |
| `get_project_health` | Proje sağlık raporu |
| `get_authority` | authority.json sorgula |
| `record_learning` | Knowledge base'e kaydet |
| `scan_telescope` | Telescope audit tarama |
| `get_audit_report` | Audit raporu getir |
| `get_learning_history` | Son 7 gün öğrenme kayıtları |

## Başlatma

```bash
# Cursor/Claude için
node mcp-servers/yalihan-bekci-mcp.js

# Windsurf için (önce build)
cd mcp && npm run build && npm start

# Background
node mcp/build/index.js &
```

## MCP Config Dosyaları

```json
// .cursor/mcp.json
{
  "mcpServers": {
    "yalihan-bekci": {
      "command": "node",
      "args": ["mcp-servers/yalihan-bekci-mcp.js"],
      "cwd": "/Users/macbookpro/dev/yalihan2026"
    }
  }
}

// .roo/mcp.json
{
  "mcpServers": {
    "yalihan-bekci": {
      "command": "node",
      "args": ["mcp/build/index.js"]
    }
  }
}
```

## authority.json Konfigürasyonu

```json
"mcp_server": {
  "url": "http://localhost:4001",
  "port": 4001,
  "status": "active"
},
"mcp_server_ecosystem": {
  "servers": [
    {
      "name": "yalihan-bekci-mcp",
      "port": 4001,
      "path": "mcp-servers/yalihan-bekci-mcp.js"
    }
  ]
}
```

## Port 4001

Ana Yalihan Bekçi MCP portu. Health check:

```bash
php artisan bekci:health --detailed
```

## Knowledge Base

```
yalihan-bekci/knowledge/     → Node MCP öğrenmeleri
yalihan-bekci/learning/       → PHP Audit öğrenmeleri
```

## Bilinen Sorunlar

- `scripts/services/start-mcp-server.sh` dosyası eksik (authority.json'da referans var)
- TypeScript bridge PHP path'i hardcoded: `/opt/homebrew/bin/php`
