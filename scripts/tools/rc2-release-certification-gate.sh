#!/usr/bin/env bash
# RC2 Release Certification Gate
# Purpose: Release öncesi browser kanıtını zorunlu kılar.
#          Migration, seed, deploy veya restart yapmaz.
set -euo pipefail

repo_root="$(git rev-parse --show-toplevel)"
cd "$repo_root"
report_path="${RC2_RELEASE_REPORT:-/tmp/yalihan-rc2-release-certification.json}"
started_at="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
commit_sha="$(git rev-parse HEAD)"
overall="PASS"
results=()

write_report() {
    python3 - "$report_path" "$started_at" "$commit_sha" "$overall" "${results[@]}" <<'PY'
import json, pathlib, sys

path, started, sha, overall, *raw = sys.argv[1:]
payload = {
    "schema": "yalihan.rc2.release-certification.v1",
    "started_at_utc": started,
    "commit_sha": sha,
    "overall": overall,
    "gates": dict(item.split("=", 1) for item in raw),
    "production_deploy": "NOT_PERFORMED",
}
target = pathlib.Path(path)
target.parent.mkdir(parents=True, exist_ok=True)
target.write_text(json.dumps(payload, ensure_ascii=False, indent=2) + "\n")
print(json.dumps(payload, ensure_ascii=False))
PY
}

trap write_report EXIT

record() {
    local name="$1" result="$2"
    results+=("$name=$result")
    [[ "$result" == "PASS" ]] || overall="BLOCKED"
}

# Kirli worktree: RC2_RELEASE_GATE_ALLOW_DIRTY=1 ile override edilebilir
if [[ -n "$(git status --short)" && "${RC2_RELEASE_GATE_ALLOW_DIRTY:-0}" != "1" ]]; then
    record "worktree" "BLOCKED_DIRTY"
    echo "ERROR: Kirli worktree — değişiklikleri commitleyin veya RC2_RELEASE_GATE_ALLOW_DIRTY=1 kullanın"
    exit 1
else
    record "worktree" "PASS"
fi

run_gate() {
    local name="$1"; shift
    if "$@"; then
        record "$name" "PASS"
    else
        record "$name" "FAIL"
        exit 1
    fi
}

if command -v php >/dev/null 2>&1 && [[ -f artisan ]]; then
    run_gate "sab_integrity" php artisan sab:integrity-scan
    run_gate "feature_tests" php artisan test --testsuite=Feature
else
    record "php_gates" "BLOCKED_TOOLING"
fi

if command -v npm >/dev/null 2>&1 && [[ -f package.json ]]; then
    run_gate "asset_build" npm run build
    run_gate "browser_golden_thread" npx playwright test tests/e2e/golden-thread-wizard.spec.ts
    run_gate "browser_edit_runtime" npx playwright test tests/e2e/admin-edit-runtime-health.spec.ts
else
    record "node_gates" "BLOCKED_TOOLING"
fi

[[ "$overall" == "PASS" ]]
