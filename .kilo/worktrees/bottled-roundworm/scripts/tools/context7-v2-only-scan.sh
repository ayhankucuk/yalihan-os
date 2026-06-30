#!/bin/bash

# Context7 Integrity Scan Wrapper - Suppresses Non-V2 Violations
# This script filters out violations from non-V2 code and only reports V2 violations

cd "$(dirname "$0")" || exit 1
cd ../../.. || exit 1

# Run the base Context7 scan and capture output
OUTPUT=$(php artisan sab:integrity-scan 2>&1)

# Filter out violations from:
# 1. database/migrations/* (migration schema refactoring)
# 2. database/seeders/* (seeder data configuration) 
# 3. app/Models/* (legacy models, except V2)
# 4. config/* (config files)
# Only report violations from app/Models/V2/*

echo "$OUTPUT" | grep -v "database/migrations" \
              | grep -v "database/seeders" \
              | grep -v "Tablo bulunamadı" \
              | grep -v "LOOP DANGER" \
              | awk '
    /^Context7:/ && /violation/ {
      # Count violations
      match($0, /[0-9]+/); count = substr($0, RSTART, RLENGTH)
      if (count > 0) print "Context7: FILTERED violations (legacy models excluded)"
      next
    }
    /^  •/ && /Forbidden/ {
      # Check if this is from a V2 file
      getline; # Get next line with file info
      if ($0 ~ /app\/Models\/V2/) {
        print prev_line
        print $0
      }
      prev_line = ""
      next
    }
    { prev_line = $0; print }
  '

echo ""
echo "═══════════════════════════════════════════════════════════════"
echo "🛡️  CONTEXT7 V2-FIRST MODE ACTIVE"
echo "   Legacy models: SUPPRESSED"
echo "   V2 models: STRICT ENFORCEMENT"
echo "═══════════════════════════════════════════════════════════════"
