#!/usr/bin/env bash

set -euo pipefail

JSON_PATH=""
if [[ "${1:-}" == "--json" ]]; then
  JSON_PATH="${2:-}"
  if [[ -z "$JSON_PATH" ]]; then
    echo "Missing path after --json"
    exit 2
  fi
fi

START_TS="$(date -u +"%Y-%m-%dT%H:%M:%SZ")"
OUTPUT_FILE="$(mktemp)"

set +e
php artisan standard:check --type=context7 >"$OUTPUT_FILE" 2>&1
EXIT_CODE=$?
set -e

cat "$OUTPUT_FILE"

if [[ -n "$JSON_PATH" ]]; then
  mkdir -p "$(dirname "$JSON_PATH")"

  ESCAPED_OUTPUT="$(python3 - <<'PY' "$OUTPUT_FILE"
import json
import pathlib
import sys
p = pathlib.Path(sys.argv[1])
print(json.dumps(p.read_text()))
PY
)"

  cat >"$JSON_PATH" <<EOF
{
  "generated_at": "$START_TS",
  "command": "php artisan standard:check --type=context7",
  "exit_code": $EXIT_CODE,
  "output": $ESCAPED_OUTPUT
}
EOF
fi

rm -f "$OUTPUT_FILE"
exit $EXIT_CODE
