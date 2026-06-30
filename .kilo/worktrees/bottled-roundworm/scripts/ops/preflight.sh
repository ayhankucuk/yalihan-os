#!/bin/bash
# Preflight: Start MCP servers and run ASA kontrol
set -euo pipefail

PROJECT_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$PROJECT_ROOT"

./scripts/services/start-all-mcp-servers.sh
./yalihan-bekci/tools/asa-kontrol.sh
