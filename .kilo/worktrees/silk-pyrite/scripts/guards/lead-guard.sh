#!/bin/bash

# ==============================================================================
# LEAD AUTHORITY GUARD (SAB v24.0)
# ==============================================================================
# Detects direct Lead model mutations bypassing LeadAuthorityService.
# ==============================================================================

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo -e "${YELLOW}🕵️  Running Lead Authority Guard...${NC}"

# Define patterns to search (direct mutations)
PATTERNS=(
    "Lead::create"
    "new Lead"
    "->update("
    "->save("
    "Lead::updateOrCreate"
    "Lead::firstOrCreate"
)

# Define exclusions (Authorized locations)
EXCLUSIONS=(
    "app/Services/CRM/LeadAuthorityService.php"
    "app/Services/CRM/LeadScoringService.php" # Pure calculation persistence
    "tests/"
    "database/factories/"
    "database/seeders/"
    "database/migrations/"
    "app/Models/Lead.php"
)

VIOLATIONS_FOUND=0

# Loop through patterns and search
for pattern in "${PATTERNS[@]}"; do
    echo -e "Checking pattern: ${YELLOW}${pattern}${NC}..."
    
    # Use -e to handle patterns starting with -
    # Use [[:space:]] to distinguish 'new Lead' from 'New lead' in logs
    # Fixed pattern for 'new Lead' to be case-sensitive for the class name
    SEARCH_PATTERN="${pattern}"
    if [[ "${pattern}" == "new Lead" ]]; then
        SEARCH_PATTERN="new Lead"
    fi

    RESULTS=$(grep -rnE -e "${SEARCH_PATTERN}" app/ \
        --exclude="LeadAuthorityService.php" \
        --exclude="LeadScoringService.php" \
        --exclude="Lead.php" \
        --exclude-dir="tests" \
        --exclude-dir="database")
    
    if [ ! -z "$RESULTS" ]; then
        # Filter for files that likely use the Lead model
        while IFS= read -r line; do
            file=$(echo "$line" | cut -d: -f1)
            # Only report if the file likely deals with Lead (imports it or works in CRM)
            if grep -q "App\\\\Models\\\\Lead" "$file" || [[ "$file" == *"CRM"* ]] || [[ "$file" == *"Lead"* ]]; then
                 echo -e "${RED}Violation Found in ${file}:${NC}"
                 echo "  $line"
                 VIOLATIONS_FOUND=$((VIOLATIONS_FOUND + 1))
            fi
        done <<< "$RESULTS"
    fi
done

if [ $VIOLATIONS_FOUND -eq 0 ]; then
    echo -e "${GREEN}✅ Lead Authority Guard Passed! No shadow mutations detected.${NC}"
    exit 0
else
    echo -e "${RED}❌ Lead Authority Guard Failed! ${VIOLATIONS_FOUND} shadow mutation(s) detected.${NC}"
    echo -e "${YELLOW}Please delegate all Lead mutations to LeadAuthorityService.${NC}"
    exit 1
fi
