{
  "schemaVersion": "1.0",
  "name": "Yalıhan Emlak - Google IDX Project",
  "description": "Context7-compliant Laravel real estate platform with MCP integration",
  
  "workspace": {
    "name": "yalihanai",
    "type": "laravel-php",
    "php": {
      "version": "8.2",
      "extensions": ["mbstring", "xml", "pdo", "mysql", "redis", "gd"]
    },
    "node": {
      "version": "20.x",
      "packageManager": "npm"
    }
  },

  "services": {
    "mysql": {
      "version": "8.0",
      "database": "yalihanai",
      "autoStart": true
    },
    "redis": {
      "version": "7.0",
      "autoStart": true
    },
    "mcp-servers": {
      "yalihan-bekci": {
        "port": 4000,
        "command": "node scripts/services/bekci-mcp-server.js",
        "autoStart": true,
        "healthCheck": "http://localhost:4000/health"
      },
      "context7-docs": {
        "port": 4001,
        "command": "node scripts/services/context7-mcp-server.js",
        "autoStart": true,
        "healthCheck": "http://localhost:4001/health"
      },
      "docs-server": {
        "port": 4002,
        "command": "node scripts/services/docs-mcp-server.js",
        "autoStart": true
      }
    }
  },

  "tasks": {
    "dev": {
      "command": "php artisan serve --port=8002",
      "description": "Start Laravel development server",
      "group": "dev"
    },
    "assets": {
      "command": "npm run dev",
      "description": "Watch and compile assets with Vite",
      "group": "dev"
    },
    "mcp-start": {
      "command": "./scripts/services/start-all-mcp-servers.sh",
      "description": "Start all MCP servers",
      "group": "dev"
    },
    "mcp-stop": {
      "command": "./scripts/services/stop-all-mcp-servers.sh",
      "description": "Stop all MCP servers",
      "group": "dev"
    },
    "bekci-audit": {
      "command": "php artisan bekci:audit --code",
      "description": "Run MCP Bekçi audit",
      "group": "test"
    },
    "context7-scan": {
      "command": "php artisan sab:integrity-scan",
      "description": "Run Context7 integrity scan",
      "group": "test"
    },
    "migrate": {
      "command": "php artisan migrate",
      "description": "Run database migrations",
      "group": "build"
    }
  },

  "aiConfiguration": {
    "provider": "gemini-3-pro",
    "context7Aware": true,
    "mcpIntegration": {
      "enabled": true,
      "servers": [
        {
          "name": "yalihan-bekci",
          "url": "http://localhost:4000",
          "capabilities": ["compliance-check", "auto-fix", "learning"]
        },
        {
          "name": "context7-docs",
          "url": "http://localhost:4001",
          "capabilities": ["documentation", "standards"]
        }
      ]
    },
    "customPrompts": {
      "systemPrompt": "You are an expert Laravel developer following Context7 standards. Always validate code with Yalıhan Bekçi MCP before suggesting changes. Use sealed field names: yayin_durumu (not status), aktiflik_durumu (not enabled), lat/lng (not latitude/longitude), kisi (not musteri). Always use Tailwind CSS with dark mode support, never Bootstrap or Neo Design System.",
      "codeReview": "Review this code using Yalıhan Bekçi MCP for Context7 compliance. Check: 1) Sealed field names 2) Tailwind CSS usage 3) Dark mode variants 4) Null coalescing 5) Eager loading",
      "refactor": "Refactor following Context7 standards validated by MCP. Replace forbidden patterns with sealed alternatives.",
      "create": "Create new feature Context7-compliant. Validate with MCP: sealed fields, Tailwind CSS, dark mode, eager loading, null coalescing."
    }
  },

  "extensions": {
    "required": [
      "bmewburn.vscode-intelephense-client",
      "bradlc.vscode-tailwindcss",
      "shufo.vscode-blade-formatter"
    ],
    "recommended": [
      "amiralizadeh9480.laravel-extra-intellisense",
      "onecentlin.laravel-blade",
      "stef-k.laravel-goto-controller"
    ]
  },

  "settings": {
    "php.executablePath": "/usr/bin/php",
    "php.validate.enable": true,
    "editor.formatOnSave": true,
    "files.associations": {
      "*.blade.php": "blade"
    },
    "emmet.includeLanguages": {
      "blade": "html"
    },
    "[blade]": {
      "editor.defaultFormatter": "shufo.vscode-blade-formatter"
    },
    "[php]": {
      "editor.defaultFormatter": "bmewburn.vscode-intelephense-client"
    }
  },

  "ports": {
    "8002": {
      "label": "Laravel Server",
      "onAutoForward": "openBrowser"
    },
    "5173": {
      "label": "Vite Dev Server",
      "onAutoForward": "ignore"
    },
    "4000": {
      "label": "MCP: Yalıhan Bekçi",
      "onAutoForward": "notify"
    },
    "4001": {
      "label": "MCP: Context7 Docs",
      "onAutoForward": "notify"
    },
    "4002": {
      "label": "MCP: Docs Server",
      "onAutoForward": "notify"
    }
  },

  "postStartCommand": "composer install && npm install && php artisan key:generate && php artisan migrate && ./scripts/services/start-all-mcp-servers.sh",
  
  "forwardPorts": [8002, 4000, 4001, 4002],
  
  "remoteEnv": {
    "APP_ENV": "development",
    "APP_DEBUG": "true",
    "TELESCOPE_ENABLED": "true",
    "MCP_ENABLED": "true"
  }
}
