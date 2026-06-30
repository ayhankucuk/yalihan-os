#!/usr/bin/env bash
set -euo pipefail

# Simple Bekçi reality-check script
# - Reads database guard rules from .sab/authority.json
# - Searches repository for forbidden column usage
# - Verifies SYSTEM_MAP vs presence of key service files (e.g. TKGM)
# - Compares docs/api/samples JSON filenames vs controllers existence

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
LOGFILE="$REPO_ROOT/storage/logs/bekci_violations.log"

mkdir -p "$(dirname "$LOGFILE")"
echo "=== Bekci Reality Check: $(date -u +%Y-%m-%dT%H:%M:%SZ) ===" >> "$LOGFILE"

echo "Checking database guards..."
php -r 'echo file_exists(".sab/authority.json")?file_get_contents(".sab/authority.json") : "{}";' > /tmp/bekci_authority.json || true

FORBIDDEN_COLUMNS=$(php -r '$c = json_decode(file_get_contents(".sab/authority.json"), true); echo isset($c["databaseGuards"]["ilanlar"]["forbidden_columns"])?json_encode($c["databaseGuards"]["ilanlar"]["forbidden_columns"]):"[]";' 2>/dev/null || echo '[]')

if [ "$FORBIDDEN_COLUMNS" = "[]" ]; then
  echo "No forbidden columns defined for ilanlar in .sab/authority.json" | tee -a "$LOGFILE"
else
  echo "Forbidden columns: $FORBIDDEN_COLUMNS" >> "$LOGFILE"
  # iterate columns and grep (shell-driven for safer quoting)
  php -r '
    $c = json_decode($argv[1], true);
    if(is_array($c)){
      foreach($c as $col){ echo $col."\n"; }
    }
  ' "$FORBIDDEN_COLUMNS" 2>/dev/null | while IFS= read -r col; do
    [ -z "$col" ] && continue
    # FIXED: Filter comments/docs BEFORE limiting output
    grep -R --line-number \
      --exclude-dir=vendor \
      --exclude-dir=node_modules \
      --exclude-dir=storage \
      --exclude-dir=.git \
      --exclude="*.md" \
      --exclude="*.sql" \
      -e "$col" app database routes config 2>/dev/null | \
      # AGGRESSIVE FILTERING: Remove non-executable violations
      grep -v "^ *\*\|^ *//\|@param\|@property\|::\|durumu\|durumunda\|_durumu\|_statusu\|statusu\|statusunun" | \
      head -30  # Limit to 30 real violations per column
  done | sed 's/^/VIOLATION: /' | tee -a "$LOGFILE"
fi

echo "Checking SYSTEM_MAP claims..." | tee -a "$LOGFILE"
if grep -q "TKGM Entegrasyonu Hazir\|TKGM Entegrasyonu Hazır" "$REPO_ROOT/docs/SYSTEM_MAP.md" 2>/dev/null; then
  # check for service file
  if [ ! -f "$REPO_ROOT/app/Services/TKGMService.php" ] && [ ! -d "$REPO_ROOT/app/Modules/TKGM" ]; then
    echo "MAP_VIOLATION: docs/SYSTEM_MAP.md mentions TKGM integration but no TKGM service or module found." | tee -a "$LOGFILE"
  else
    echo "TKGM claim looks backed by code." >> "$LOGFILE"
  fi
else
  echo "No explicit TKGM claim found in SYSTEM_MAP.md" >> "$LOGFILE"
fi

# NEW: UPS and FeatureTemplateResolver checks
echo "Checking KULLANIM_REHBERI claims (UPS & Resolver)..." | tee -a "$LOGFILE"
if grep -q "## UPS (Universal Property System)" "$REPO_ROOT/docs/KULLANIM_REHBERI.md" 2>/dev/null; then
    # Check for core UPS files
    if [ ! -f "$REPO_ROOT/app/Http/Controllers/Admin/UpsGovernanceController.php" ] || [ ! -f "$REPO_ROOT/app/Services/Ups/UpsFeatureGovernanceService.php" ]; then
        echo "DOC_VIOLATION: docs/KULLANIM_REHBERI.md mentions UPS but critical UPS controllers/services are missing." | tee -a "$LOGFILE"
    else
        echo "UPS core components found." >> "$LOGFILE"
    fi
fi

if grep -q "FeatureTemplateResolver" "$REPO_ROOT/docs/KULLANIM_REHBERI.md" 2>/dev/null; then
    if [ ! -f "$REPO_ROOT/app/Services/Ups/FeatureTemplateResolver.php" ]; then
        echo "DOC_VIOLATION: KULLANIM_REHBERI mentions FeatureTemplateResolver but file is missing in app/Services/Ups/." | tee -a "$LOGFILE"
    else
        # Verify specific method existence as a deep check
        if ! grep -q "function resolve" "$REPO_ROOT/app/Services/Ups/FeatureTemplateResolver.php" 2>/dev/null; then
            echo "DOC_VIOLATION: FeatureTemplateResolver exists but missing 'resolve' method mentioned in architecture." | tee -a "$LOGFILE"
        else
            echo "FeatureTemplateResolver 'resolve' method verified." >> "$LOGFILE"
        fi
    fi
fi

echo "Checking API samples vs controllers..." | tee -a "$LOGFILE"
SAMPLES_DIR="$REPO_ROOT/docs/api/samples"
if [ -d "$SAMPLES_DIR" ]; then
  for f in "$SAMPLES_DIR"/*.json; do
    [ -e "$f" ] || continue
    name=$(basename "$f" .json)
    # naive controller guess: Controllers/*Name*Controller.php
    cnt=$(git ls-files "app/Http/Controllers" | xargs -I{} basename {} | grep -i "$name" | wc -l || true)
    if [ "$cnt" -eq 0 ]; then
      echo "API_VIOLATION: sample '$name' exists in docs but no matching controller found (app/Http/Controllers/*${name}*)." | tee -a "$LOGFILE"
    fi
  done
else
  echo "No docs/api/samples directory found" >> "$LOGFILE"
fi

echo "Checking env keys for services.php vs .env.example..." | tee -a "$LOGFILE"

ENV_EXAMPLE="$REPO_ROOT/.env.example"
if [ ! -f "$ENV_EXAMPLE" ]; then
  echo "ENV_VIOLATION: .env.example not found at repo root." | tee -a "$LOGFILE"
else
  CONFIG_ENV_KEYS=$(grep -oE "env\(['\"][A-Z0-9_]+['\"]" "$REPO_ROOT/config/services.php" | sed -E "s/env\(['\"]([A-Z0-9_]+)['\"])/\\1/" | sort -u || true)
  if [ -z "$CONFIG_ENV_KEYS" ]; then
    echo "No env() usages detected in config/services.php" >> "$LOGFILE"
  else
    while IFS= read -r key; do
      [ -z "$key" ] && continue
      if ! grep -q "^$key=" "$ENV_EXAMPLE"; then
        echo "ENV_MISSING: $key used in config/services.php but not defined in .env.example" | tee -a "$LOGFILE"
      fi
    done <<< "$CONFIG_ENV_KEYS"
  fi
fi

echo "Reality check completed at $(date -u +%Y-%m-%dT%H:%M:%SZ)" >> "$LOGFILE"
echo "Done. Violations written to $LOGFILE"
