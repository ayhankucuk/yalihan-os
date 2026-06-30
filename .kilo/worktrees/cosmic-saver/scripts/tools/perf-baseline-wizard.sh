#!/bin/bash
# Performance Baseline Capture - Wizard Context API
# Part of L5 Self-Protecting System
#
# Purpose: Measure wizard context API p95 latency to establish baseline
# Usage: ./scripts/perf-baseline-wizard.sh [--save]
#
# Output: JSON with p95 latency metrics

set -e

# Configuration
URL="${PERF_URL:-http://127.0.0.1:8000}/api/v1/wizard/context?alt_kategori_id=15&junction_id=1"
COUNT=${PERF_COUNT:-30}
BASELINE_FILE="reports/performance-baseline.json"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "🔍 Performance Baseline Capture - Wizard Context API"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "URL: $URL"
echo "Samples: $COUNT"
echo ""

# Array to store latencies
TIMES=()

echo "📊 Collecting samples..."
for i in $(seq 1 $COUNT); do
    # Measure request time using curl
    START=$(date +%s%3N)
    HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" "$URL")
    END=$(date +%s%3N)

    DURATION=$((END - START))
    TIMES+=($DURATION)

    # Progress indicator
    if [ $((i % 5)) -eq 0 ]; then
        echo -n "."
    fi

    # Check HTTP status
    if [ "$HTTP_CODE" != "200" ]; then
        echo -e "\n${RED}⚠️  Warning: HTTP $HTTP_CODE at sample $i${NC}"
    fi

    # Small delay between requests
    sleep 0.1
done

echo ""
echo ""

# Calculate statistics
IFS=$'\n' SORTED=($(sort -n <<<"${TIMES[*]}"))

P50_INDEX=$((COUNT * 50 / 100))
P95_INDEX=$((COUNT * 95 / 100))
P99_INDEX=$((COUNT * 99 / 100))

MIN=${SORTED[0]}
MAX=${SORTED[-1]}
P50=${SORTED[$P50_INDEX]}
P95=${SORTED[$P95_INDEX]}
P99=${SORTED[$P99_INDEX]}

# Calculate average
TOTAL=0
for TIME in "${TIMES[@]}"; do
    TOTAL=$((TOTAL + TIME))
done
AVG=$((TOTAL / COUNT))

# Display results
echo "📈 Performance Metrics:"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  Min:     ${MIN} ms"
echo "  Average: ${AVG} ms"
echo "  Median:  ${P50} ms"
echo "  p95:     ${P95} ms"
echo "  p99:     ${P99} ms"
echo "  Max:     ${MAX} ms"
echo ""

# Threshold check (warning only)
THRESHOLD=400
if [ "$P95" -gt "$THRESHOLD" ]; then
    echo -e "${YELLOW}⚠️  Warning: p95 (${P95}ms) exceeds threshold (${THRESHOLD}ms)${NC}"
else
    echo -e "${GREEN}✅ p95 within acceptable limits (<${THRESHOLD}ms)${NC}"
fi

echo ""

# Save to baseline file if --save flag provided
if [ "$1" = "--save" ]; then
    mkdir -p reports

    # Create JSON with timestamp
    TIMESTAMP=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

    cat > "$BASELINE_FILE" << EOF
{
  "wizard_context_api": {
    "min": $MIN,
    "avg": $AVG,
    "p50": $P50,
    "p95": $P95,
    "p99": $P99,
    "max": $MAX,
    "samples": $COUNT,
    "timestamp": "$TIMESTAMP",
    "url": "$URL"
  },
  "thresholds": {
    "wizard_context_p95": 400,
    "ai_generation_p95": 3000,
    "dashboard_load_p95": 1500
  }
}
EOF

    echo -e "${GREEN}💾 Baseline saved to: $BASELINE_FILE${NC}"
    echo ""
fi

# Output JSON for programmatic use
echo "{\"wizard_context_p95\": $P95, \"wizard_context_avg\": $AVG, \"samples\": $COUNT}"
