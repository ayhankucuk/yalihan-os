#!/bin/bash
# probe-hermes-cli.sh — Probe Hermes CLI non-interactive entrypoint
# Tests:
# 1. HERMES_PROFILE env var works in CLI mode
# 2. sandbox-exec applies to CLI invocations
# 3. Basic --query --oneshot execution

set -e

HERMES_HOME="${HERMES_HOME:-$HOME/.hermes}"
HERMES_PROFILE="${HERMES_PROFILE:-yalihan-verifier}"
HERMES_CLI="$HERMES_HOME/hermes-agent/cli.py"
SANDBOX_SB="$HERMES_HOME/profiles/$HERMES_PROFILE/sandbox/${HERMES_PROFILE}-isolated.sb"

PROBE_PROMPT="Respond with exactly one word: PONG"
OUTPUT_DIR="/tmp/hermes-probe-$$"
mkdir -p "$OUTPUT_DIR"

echo "=== Hermes CLI Probe ==="
echo "HERMES_HOME: $HERMES_HOME"
echo "HERMES_PROFILE: $HERMES_PROFILE"
echo "HERMES_CLI: $HERMES_CLI"
echo "SANDBOX_SB: $SANDBOX_SB"
echo ""

# Test 1: Check if CLI exists
echo "--- Test 1: CLI existence ---"
if [ -f "$HERMES_CLI" ]; then
    echo "✓ CLI found: $HERMES_CLI"
else
    echo "✗ CLI not found: $HERMES_CLI"
    exit 1
fi

# Test 2: Check if venv python exists
echo ""
echo "--- Test 2: venv python ---"
VENV_PY="$HERMES_HOME/hermes-agent/venv/bin/python"
if [ -f "$VENV_PY" ]; then
    echo "✓ venv python found: $VENV_PY"
    PYTHON="$VENV_PY"
else
    echo "⚠ venv python not found, using system python"
    PYTHON="python3"
fi

# Test 3: HERMES_PROFILE env var
echo ""
echo "--- Test 3: HERMES_PROFILE env var ---"
export HERMES_HOME
export HERMES_PROFILE
export HERMES_QUIET=1

# Try to get config
echo "Trying: HERMES_PROFILE=$HERMES_PROFILE $PYTHON $HERMES_CLI --help 2>&1 | head -30"
echo ""

HELP_OUTPUT=$("$PYTHON" "$HERMES_CLI" --help 2>&1 || echo "EXIT_CODE=$?")
echo "$HELP_OUTPUT" | head -30

# Test 4: Non-interactive query
echo ""
echo "--- Test 4: Non-interactive query (--query --oneshot) ---"
echo "PROMPT: $PROBE_PROMPT"
echo ""

QUERY_OUTPUT=$("$PYTHON" "$HERMES_CLI" \
    --query "$PROBE_PROMPT" \
    --oneshot \
    --max-turns 2 \
    --non-interactive \
    --provider aiwebmodel \
    2>&1 || true)

echo "$QUERY_OUTPUT" | tail -20

# Test 5: sandbox-exec
echo ""
echo "--- Test 5: sandbox-exec wrapper ---"
if [ -f "$SANDBOX_SB" ]; then
    echo "✓ Sandbox profile found: $SANDBOX_SB"
    echo "Testing: sandbox-exec -f $SANDBOX_SB -- $PYTHON $HERMES_CLI --version"
    
    SANDBOX_OUTPUT=$(sandbox-exec -f "$SANDBOX_SB" -- "$PYTHON" "$HERMES_CLI" --help 2>&1 || true)
    echo "$SANDBOX_OUTPUT" | head -10
else
    echo "⚠ Sandbox profile not found: $SANDBOX_SB"
    echo "sandbox-exec test SKIPPED"
fi

echo ""
echo "=== Probe Complete ==="
rm -rf "$OUTPUT_DIR"
