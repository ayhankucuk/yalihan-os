#!/usr/bin/env bash
# requires: bash 3.2+, jq, git
# ==============================================================================
# Yalıhan Governance Engine — Policy-Check (SAB v13.0 / Hardening v1.1)
# Boundary: Draft in yalihan2026, Run in yalihanai
# ==============================================================================

JSON_FILE=".agent/policy/controller_guard.json"

# --- Dependency check ---
if ! command -v jq &>/dev/null; then
  echo "[POLICY][INTERNAL] jq bulunamadı. Governance check atlandı." >&2
  exit 0
fi

if [ ! -f "$JSON_FILE" ]; then
  echo "[POLICY][INTERNAL] Policy dosyası bulunamadı: $JSON_FILE" >&2
  exit 0
fi

# --- 1. File scope resolution (priority: $1 → git diff → git ls-files → find) ---
declare -a FILES=()

if [ -n "$1" ]; then
  # Explicit single file
  FILES=("$1")
else
  # Attempt: git diff (changed files only — SAB v13 primary)
  mapfile -t FILES < <(git diff --name-only --diff-filter=ACMRTUXB HEAD 2>/dev/null \
    | grep -E '^app/Http/Controllers/.*\.php$' || true)

  # Fallback: git ls-files (tracked files)
  if [ "${#FILES[@]}" -eq 0 ]; then
    mapfile -t FILES < <(git ls-files \
      'app/Http/Controllers/**/*.php' \
      'app/Http/Controllers/*.php' 2>/dev/null || true)
  fi

  # Last resort: find
  if [ "${#FILES[@]}" -eq 0 ]; then
    mapfile -t FILES < <(find app/Http/Controllers -type f -name "*.php" 2>/dev/null || true)
  fi
fi

# Deduplicate and filter existing files (bash 3.2 compatible)
declare -a CLEAN_FILES=()
if [ "${#FILES[@]}" -gt 0 ]; then
  while IFS= read -r f; do
    [ -f "$f" ] && CLEAN_FILES+=("$f")
  done < <(printf '%s\n' "${FILES[@]}" | sort -u)
fi

if [ "${#CLEAN_FILES[@]}" -eq 0 ]; then
  exit 0
fi

# --- 2. Load rules sorted by priority DESC ---
SORTED_RULES=$(jq -c 'sort_by(.priority) | reverse | .[]' "$JSON_FILE")

# --- 3. State flags (parent shell — process substitution, NOT pipe) ---
IS_BLOCKED=0

# --- 4. Evaluation loop ---
while IFS= read -r rule; do
  LEVEL=$(echo "$rule"   | jq -r '.level')
  REASON=$(echo "$rule"  | jq -r '.reason')
  PATTERN=$(echo "$rule" | jq -r '.patterns | join("|")')

  for file in "${CLEAN_FILES[@]}"; do
    # Scope guard: only app/Http/Controllers/**/*.php
    case "$file" in
      app/Http/Controllers/*.php|\
      app/Http/Controllers/*/*.php|\
      app/Http/Controllers/*/*/*.php|\
      app/Http/Controllers/*/*/*/*.php) ;;
      *) continue ;;
    esac

    # Pattern match (grep no-match is not an error)
    MATCHES=$(grep -nE "$PATTERN" "$file" 2>/dev/null || true)
    [ -z "$MATCHES" ] && continue

    while IFS= read -r matched_line; do
      [ -z "$matched_line" ] && continue
      LINE_NO=$(echo "$matched_line" | cut -d: -f1)
      echo "[POLICY][$LEVEL] $file:$LINE_NO -> $REASON"

      if [ "$LEVEL" = "BLOCK" ]; then
        IS_BLOCKED=1
      fi
    done <<< "$MATCHES"
  done

done <<< "$SORTED_RULES"

# --- 5. Exit contract ---
if [ "$IS_BLOCKED" -eq 1 ]; then
  exit 1
fi

exit 0
