#!/bin/bash

set -e

# Initialize FAIL flag
FAIL=0

echo "============================================"
echo "🚀 PROPERTY HUB RELEASE CANDIDATE CHECK"
echo ""
echo "6️⃣ Checking DB::enableQueryLog misuse..."
# Exclude tests and scripts where it might be valid
QUERY_LOG_FOUND=$(grep -RIn --exclude-dir=vendor --exclude-dir=tests --exclude-dir=scripts --exclude="UpsHealthCommand.php" "enableQueryLog" app/ || true)

if [ ! -z "$QUERY_LOG_FOUND" ]; then
  echo "⚠️ QueryLog usage detected (verify intentional):"
  echo "$QUERY_LOG_FOUND"
else
  echo "✅ No unexpected QueryLog usage"
fi

echo ""
echo "7️⃣ Running Final Release Verification & Performance Check (Threshold: 200ms)..."
php scripts/final_release_check.php

if [ $? -ne 0 ]; then
    FAIL=1
fi

echo ""
echo "============================================"

if [ $FAIL -eq 0 ]; then
  echo "🟢 RELEASE CANDIDATE VERIFIED"
  echo "============================================"
  exit 0
else
  echo "🔴 RELEASE BLOCKED – Fix issues above"
  echo "============================================"
  exit 1
fi
