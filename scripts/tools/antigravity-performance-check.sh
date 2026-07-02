#!/bin/bash
# Antigravity Performance Profiler
# Purpose: Detect N+1 queries, slow queries, and cache optimization opportunities
# Author: WenOX AI (Yalıhan Bekçi Performance Module)
# Version: 1.0.0
# Created: 2026-05-20

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m'

# Configuration
REPORT_DIR="reports/performance"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
REPORT_FILE="${REPORT_DIR}/performance_report_${TIMESTAMP}.json"
PHP_DETECTOR="scripts/tools/performance-n1-detector.php"
SLOW_QUERY_THRESHOLD=100 # ms

# Counters
TOTAL_ISSUES=0
N1_ISSUES=0
SLOW_QUERY_ISSUES=0
CACHE_ISSUES=0
EAGER_LOAD_MISSING=0

echo -e "${CYAN}╔════════════════════════════════════════════════════════════╗${NC}"
echo -e "${CYAN}║   🚀 Antigravity Performance Profiler v1.0.0             ║${NC}"
echo -e "${CYAN}║   N+1 Query • Slow Query • Cache Optimization            ║${NC}"
echo -e "${CYAN}╚════════════════════════════════════════════════════════════╝${NC}"
echo ""

# Create report directory
mkdir -p "$REPORT_DIR"

# Function: Print section header
print_section() {
    echo ""
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━${NC}"
}

# Function: Check if PHP detector exists
check_php_detector() {
    if [ ! -f "$PHP_DETECTOR" ]; then
        echo -e "${YELLOW}⚠️  PHP N+1 detector not found. Creating...${NC}"
        return 1
    fi
    return 0
}

# ============================================================================
# 1. N+1 QUERY DETECTION
# ============================================================================
print_section "1️⃣  N+1 Query Detection"

echo "🔍 Scanning for N+1 query patterns..."
echo ""

# Pattern 1: foreach without eager loading
echo "  📌 Pattern 1: foreach loops without eager loading"
N1_FOREACH=$(grep -rn "foreach.*->.*as" app/ --include="*.php" | \
    grep -v "with(" | \
    grep -v "load(" | \
    grep -v "// context7-ignore" | \
    wc -l || echo "0")

if [ "$N1_FOREACH" -gt 0 ]; then
    echo -e "    ${RED}❌ Found $N1_FOREACH potential N+1 issues in foreach loops${NC}"
    N1_ISSUES=$((N1_ISSUES + N1_FOREACH))

    # Show first 5 examples
    echo "    Examples:"
    grep -rn "foreach.*->.*as" app/ --include="*.php" | \
        grep -v "with(" | \
        grep -v "load(" | \
        grep -v "// context7-ignore" | \
        head -5 | \
        while IFS= read -r line; do
            echo -e "      ${YELLOW}→ $line${NC}"
        done
else
    echo -e "    ${GREEN}✅ No N+1 issues in foreach loops${NC}"
fi
echo ""

# Pattern 2: Query without with() or load()
echo "  📌 Pattern 2: Eloquent queries without eager loading"
N1_QUERY=$(grep -rn "::where\|::find\|::all\|::get" app/Http/Controllers --include="*.php" | \
    grep -v "->with(" | \
    grep -v "->load(" | \
    grep -v "// N+1 safe" | \
    wc -l || echo "0")

if [ "$N1_QUERY" -gt 0 ]; then
    echo -e "    ${YELLOW}⚠️  Found $N1_QUERY queries without eager loading${NC}"
    echo "    (May cause N+1 if relationships are accessed later)"
    N1_ISSUES=$((N1_ISSUES + N1_QUERY))
else
    echo -e "    ${GREEN}✅ All queries use eager loading${NC}"
fi
echo ""

# Pattern 3: Relationship access in loops
echo "  📌 Pattern 3: Relationship access inside loops"
N1_RELATIONSHIP=$(grep -rn "foreach" app/Http/Controllers --include="*.php" -A 5 | \
    grep -E "->user|->kisi|->ilan|->danisman|->kategori" | \
    wc -l || echo "0")

if [ "$N1_RELATIONSHIP" -gt 0 ]; then
    echo -e "    ${RED}❌ Found $N1_RELATIONSHIP relationship accesses in loops${NC}"
    N1_ISSUES=$((N1_ISSUES + N1_RELATIONSHIP))
else
    echo -e "    ${GREEN}✅ No relationship access in loops${NC}"
fi

TOTAL_ISSUES=$((TOTAL_ISSUES + N1_ISSUES))

# ============================================================================
# 2. EAGER LOADING ANALYSIS
# ============================================================================
print_section "2️⃣  Eager Loading Analysis"

echo "🔍 Checking for missing eager loading..."
echo ""

# Check Controllers
CONTROLLERS_WITH_EAGER=$(grep -rl "->with(" app/Http/Controllers --include="*.php" | wc -l || echo "0")
TOTAL_CONTROLLERS=$(find app/Http/Controllers -name "*.php" | wc -l || echo "1")
EAGER_PERCENTAGE=$((CONTROLLERS_WITH_EAGER * 100 / TOTAL_CONTROLLERS))

echo "  📊 Eager Loading Coverage:"
echo "    Controllers with eager loading: $CONTROLLERS_WITH_EAGER / $TOTAL_CONTROLLERS ($EAGER_PERCENTAGE%)"

if [ "$EAGER_PERCENTAGE" -lt 70 ]; then
    echo -e "    ${RED}❌ Low eager loading coverage (<70%)${NC}"
    EAGER_LOAD_MISSING=$((TOTAL_CONTROLLERS - CONTROLLERS_WITH_EAGER))
    TOTAL_ISSUES=$((TOTAL_ISSUES + EAGER_LOAD_MISSING))
elif [ "$EAGER_PERCENTAGE" -lt 90 ]; then
    echo -e "    ${YELLOW}⚠️  Moderate eager loading coverage (70-90%)${NC}"
else
    echo -e "    ${GREEN}✅ Good eager loading coverage (>90%)${NC}"
fi
echo ""

# Check Repositories
REPOS_WITH_EAGER=$(grep -rl "->with(" app/Repositories --include="*.php" 2>/dev/null | wc -l || echo "0")
TOTAL_REPOS=$(find app/Repositories -name "*.php" 2>/dev/null | wc -l || echo "1")

if [ "$TOTAL_REPOS" -gt 0 ]; then
    REPO_EAGER_PERCENTAGE=$((REPOS_WITH_EAGER * 100 / TOTAL_REPOS))
    echo "  📊 Repository Eager Loading:"
    echo "    Repositories with eager loading: $REPOS_WITH_EAGER / $TOTAL_REPOS ($REPO_EAGER_PERCENTAGE%)"

    if [ "$REPO_EAGER_PERCENTAGE" -lt 80 ]; then
        echo -e "    ${YELLOW}⚠️  Consider adding eager loading to repositories${NC}"
    else
        echo -e "    ${GREEN}✅ Good repository eager loading${NC}"
    fi
fi

# ============================================================================
# 3. CACHE OPTIMIZATION DETECTION
# ============================================================================
print_section "3️⃣  Cache Optimization Detection"

echo "🔍 Analyzing cache usage patterns..."
echo ""

# Check for Cache::remember usage
CACHE_REMEMBER=$(grep -rn "Cache::remember" app/ --include="*.php" | wc -l || echo "0")
CACHE_GET=$(grep -rn "Cache::get" app/ --include="*.php" | wc -l || echo "0")
CACHE_TOTAL=$((CACHE_REMEMBER + CACHE_GET))

echo "  📊 Cache Usage:"
echo "    Cache::remember calls: $CACHE_REMEMBER"
echo "    Cache::get calls: $CACHE_GET"
echo "    Total cache operations: $CACHE_TOTAL"
echo ""

# Check for queries that should be cached
echo "  📌 Queries that should be cached:"

# Pattern 1: Static data queries (categories, settings, etc.)
UNCACHED_STATIC=$(grep -rn "::where.*get()\|::all()" app/Http/Controllers --include="*.php" | \
    grep -E "Kategori|Setting|Config|Feature" | \
    grep -v "Cache::" | \
    wc -l || echo "0")

if [ "$UNCACHED_STATIC" -gt 0 ]; then
    echo -e "    ${YELLOW}⚠️  Found $UNCACHED_STATIC static data queries without cache${NC}"
    CACHE_ISSUES=$((CACHE_ISSUES + UNCACHED_STATIC))

    # Show examples
    echo "    Examples:"
    grep -rn "::where.*get()\|::all()" app/Http/Controllers --include="*.php" | \
        grep -E "Kategori|Setting|Config|Feature" | \
        grep -v "Cache::" | \
        head -3 | \
        while IFS= read -r line; do
            echo -e "      ${YELLOW}→ $line${NC}"
        done
else
    echo -e "    ${GREEN}✅ Static data queries are cached${NC}"
fi
echo ""

# Pattern 2: Repeated queries in loops
echo "  📌 Repeated queries in loops:"
LOOP_QUERIES=$(grep -rn "foreach" app/ --include="*.php" -A 10 | \
    grep -E "::find|::where" | \
    wc -l || echo "0")

if [ "$LOOP_QUERIES" -gt 0 ]; then
    echo -e "    ${RED}❌ Found $LOOP_QUERIES queries inside loops${NC}"
    echo "    Consider: Eager loading or caching"
    CACHE_ISSUES=$((CACHE_ISSUES + LOOP_QUERIES))
else
    echo -e "    ${GREEN}✅ No queries in loops${NC}"
fi

TOTAL_ISSUES=$((TOTAL_ISSUES + CACHE_ISSUES))

# ============================================================================
# 4. SLOW QUERY PATTERNS
# ============================================================================
print_section "4️⃣  Slow Query Pattern Detection"

echo "🔍 Checking for potentially slow query patterns..."
echo ""

# Pattern 1: SELECT * queries
echo "  📌 Pattern 1: SELECT * queries"
SELECT_ALL=$(grep -rn "::all()\|->get()" app/Http/Controllers --include="*.php" | \
    grep -v "->select(" | \
    wc -l || echo "0")

if [ "$SELECT_ALL" -gt 0 ]; then
    echo -e "    ${YELLOW}⚠️  Found $SELECT_ALL queries without select() optimization${NC}"
    echo "    Recommendation: Use ->select(['id', 'name', ...]) for better performance"
    SLOW_QUERY_ISSUES=$((SLOW_QUERY_ISSUES + SELECT_ALL))
else
    echo -e "    ${GREEN}✅ Queries use select() optimization${NC}"
fi
echo ""

# Pattern 2: Missing indexes (LIKE queries)
echo "  📌 Pattern 2: LIKE queries (may need indexes)"
LIKE_QUERIES=$(grep -rn "->where.*LIKE\|->orWhere.*LIKE" app/ --include="*.php" | wc -l || echo "0")

if [ "$LIKE_QUERIES" -gt 0 ]; then
    echo -e "    ${YELLOW}⚠️  Found $LIKE_QUERIES LIKE queries${NC}"
    echo "    Recommendation: Consider full-text search or proper indexes"
    SLOW_QUERY_ISSUES=$((SLOW_QUERY_ISSUES + LIKE_QUERIES))
else
    echo -e "    ${GREEN}✅ No LIKE queries found${NC}"
fi
echo ""

# Pattern 3: Queries without orderBy
echo "  📌 Pattern 3: ->first() without orderBy (non-deterministic)"
FIRST_WITHOUT_ORDER=$(grep -rn "->first()" app/ --include="*.php" | \
    grep -v "->orderBy(" | \
    grep -v "// context7-ignore" | \
    wc -l || echo "0")

if [ "$FIRST_WITHOUT_ORDER" -gt 0 ]; then
    echo -e "    ${RED}❌ Found $FIRST_WITHOUT_ORDER ->first() calls without orderBy${NC}"
    echo "    SAB Violation: Deterministic query rule"
    SLOW_QUERY_ISSUES=$((SLOW_QUERY_ISSUES + FIRST_WITHOUT_ORDER))
else
    echo -e "    ${GREEN}✅ All ->first() calls have orderBy${NC}"
fi
echo ""

# Pattern 4: Heavy joins
echo "  📌 Pattern 4: Complex joins"
COMPLEX_JOINS=$(grep -rn "->join.*->join.*->join" app/ --include="*.php" | wc -l || echo "0")

if [ "$COMPLEX_JOINS" -gt 0 ]; then
    echo -e "    ${YELLOW}⚠️  Found $COMPLEX_JOINS queries with 3+ joins${NC}"
    echo "    Recommendation: Consider eager loading or denormalization"
    SLOW_QUERY_ISSUES=$((SLOW_QUERY_ISSUES + COMPLEX_JOINS))
else
    echo -e "    ${GREEN}✅ No complex joins detected${NC}"
fi

TOTAL_ISSUES=$((TOTAL_ISSUES + SLOW_QUERY_ISSUES))

# ============================================================================
# 5. PERFORMANCE RECOMMENDATIONS
# ============================================================================
print_section "5️⃣  Performance Recommendations"

echo ""
if [ "$N1_ISSUES" -gt 0 ]; then
    echo -e "${YELLOW}🔧 N+1 Query Fixes:${NC}"
    echo "   • Add ->with(['relation']) to queries"
    echo "   • Use eager loading in repositories"
    echo "   • Example: Ilan::with(['il', 'ilce', 'fotograflar'])->get()"
    echo ""
fi

if [ "$CACHE_ISSUES" -gt 0 ]; then
    echo -e "${YELLOW}🔧 Cache Optimization:${NC}"
    echo "   • Wrap static queries with Cache::remember()"
    echo "   • Cache TTL: 3600 for static data, 600 for dynamic"
    echo "   • Example: Cache::remember('categories', 3600, fn() => Kategori::all())"
    echo ""
fi

if [ "$SLOW_QUERY_ISSUES" -gt 0 ]; then
    echo -e "${YELLOW}🔧 Query Optimization:${NC}"
    echo "   • Use ->select(['id', 'name']) instead of SELECT *"
    echo "   • Add ->orderBy('id') to ->first() calls"
    echo "   • Consider indexes for LIKE queries"
    echo "   • Use pagination for large datasets"
    echo ""
fi

# ============================================================================
# 6. GENERATE JSON REPORT
# ============================================================================
print_section "6️⃣  Generating Report"

cat > "$REPORT_FILE" << EOF
{
  "timestamp": "$(date -u +"%Y-%m-%dT%H:%M:%SZ")",
  "version": "1.0.0",
  "summary": {
    "total_issues": $TOTAL_ISSUES,
    "n1_issues": $N1_ISSUES,
    "cache_issues": $CACHE_ISSUES,
    "slow_query_issues": $SLOW_QUERY_ISSUES,
    "eager_load_missing": $EAGER_LOAD_MISSING
  },
  "eager_loading": {
    "controllers_with_eager": $CONTROLLERS_WITH_EAGER,
    "total_controllers": $TOTAL_CONTROLLERS,
    "coverage_percentage": $EAGER_PERCENTAGE
  },
  "cache_usage": {
    "cache_remember_calls": $CACHE_REMEMBER,
    "cache_get_calls": $CACHE_GET,
    "uncached_static_queries": $UNCACHED_STATIC
  },
  "query_patterns": {
    "select_all_queries": $SELECT_ALL,
    "like_queries": $LIKE_QUERIES,
    "first_without_order": $FIRST_WITHOUT_ORDER,
    "complex_joins": $COMPLEX_JOINS
  },
  "recommendations": [
    "Add eager loading to reduce N+1 queries",
    "Cache static data queries (categories, settings)",
    "Use select() to optimize query payload",
    "Add orderBy() to all first() calls for determinism"
  ]
}
EOF

echo -e "${GREEN}✅ Report saved: $REPORT_FILE${NC}"
echo ""

# ============================================================================
# 7. FINAL SUMMARY
# ============================================================================
print_section "📊 Performance Profiler Summary"

echo ""
echo "  Total Issues Found: $TOTAL_ISSUES"
echo ""
echo "  Breakdown:"
echo "    • N+1 Query Issues:        $N1_ISSUES"
echo "    • Cache Optimization:      $CACHE_ISSUES"
echo "    • Slow Query Patterns:     $SLOW_QUERY_ISSUES"
echo "    • Missing Eager Loading:   $EAGER_LOAD_MISSING"
echo ""

if [ "$TOTAL_ISSUES" -eq 0 ]; then
    echo -e "${GREEN}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${GREEN}║   ✅ EXCELLENT! No performance issues detected           ║${NC}"
    echo -e "${GREEN}╚════════════════════════════════════════════════════════════╝${NC}"
    exit 0
elif [ "$TOTAL_ISSUES" -lt 10 ]; then
    echo -e "${YELLOW}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${YELLOW}║   ⚠️  GOOD: Minor performance optimizations needed       ║${NC}"
    echo -e "${YELLOW}╚════════════════════════════════════════════════════════════╝${NC}"
    exit 0
elif [ "$TOTAL_ISSUES" -lt 50 ]; then
    echo -e "${YELLOW}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${YELLOW}║   ⚠️  MODERATE: Performance improvements recommended     ║${NC}"
    echo -e "${YELLOW}╚════════════════════════════════════════════════════════════╝${NC}"
    exit 1
else
    echo -e "${RED}╔════════════════════════════════════════════════════════════╗${NC}"
    echo -e "${RED}║   ❌ CRITICAL: Significant performance issues detected   ║${NC}"
    echo -e "${RED}╚════════════════════════════════════════════════════════════╝${NC}"
    exit 1
fi
