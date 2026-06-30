#!/usr/bin/env bash
# ═══════════════════════════════════════════════════════
# 🛡️ Guard E: Migration Guard — Context7 Şema Taraması
# ═══════════════════════════════════════════════════════
# Yeni migration'larda yasak:
#   - $table->string('status')
#   - $table->boolean('is_active') (direkt, aktiflik_durumu olmadan)
#   - $table->integer('order')
#   - $table->string('sort_order')
# ═══════════════════════════════════════════════════════
# Ensure standard paths for Node.js/PHP (Homebrew/Intel/Silicon)
export PATH="/opt/homebrew/bin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:$PATH"

set -euo pipefail

MIGRATION_DIR="${1:-database/migrations}"
VIOLATIONS=0
REPORT=""

# Sadece staged/değişen migration dosyalarını tara (git staged)
STAGED_MIGRATIONS=$(git diff --cached --name-only 2>/dev/null | grep "database/migrations" || true)

if [ -z "$STAGED_MIGRATIONS" ]; then
    echo "✅ MIGRATION GUARD: No staged migrations to check."
    exit 0
fi

echo "🔍 Migration Guard Context7 Scan (staged migrations)"
echo "═══════════════════════════════════════════════════════"

for migration_file in $STAGED_MIGRATIONS; do
    if [ ! -f "$migration_file" ]; then
        continue
    fi

    # Yasak: ->string('status') veya ->integer('status') vb.
    while IFS=: read -r file line content; do
        # Rename migration'ları istisna (qrcode_status→qrcode_durumu gibi)
        if echo "$migration_file" | grep -qE "rename.*status|status.*rename|durumu"; then
            continue
        fi
        VIOLATIONS=$((VIOLATIONS + 1))
        REPORT+="❌ [migration:status-column] $file:$line → $content\n"
    done < <(grep -n "\->.*('status'" "$migration_file" 2>/dev/null | grep -v "rename\|durumu\|where" || true)

    # Yasak: ->boolean('is_active') — aktiflik_durumu kullanılmalı
    while IFS=: read -r file line content; do
        VIOLATIONS=$((VIOLATIONS + 1))
        REPORT+="❌ [migration:is_active-column] $file:$line → $content\n"
    done < <(grep -n "\->.*('is_active'" "$migration_file" 2>/dev/null || true)

    # Yasak: ->integer('order') — display_order kullanılmalı
    while IFS=: read -r file line content; do
        VIOLATIONS=$((VIOLATIONS + 1))
        REPORT+="❌ [migration:order-column] $file:$line → $content\n"
    done < <(grep -n "\->.*('\(sort_\)\?order'" "$migration_file" 2>/dev/null | grep -v "display_order" || true)

done

echo ""
if [ "$VIOLATIONS" -gt 0 ]; then
    echo "🚨 MIGRATION GUARD FAILED — $VIOLATIONS violation(s) found:"
    echo ""
    echo -e "$REPORT"
    echo ""
    echo "Kurallar:"
    echo "  'status' → 'aktiflik_durumu' (tinyInteger(1)) veya 'yayin_durumu'"
    echo "  'is_active' → 'aktiflik_durumu'"
    echo "  'order'/'sort_order' → 'display_order'"
    exit 1
else
    echo "✅ MIGRATION GUARD PASSED — 0 forbidden columns in staged migrations."
    exit 0
fi
