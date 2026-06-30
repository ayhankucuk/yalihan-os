#!/bin/bash
# Dark Mode CSS Variants Auto-Fixer
# Runs: Whenever dark mode warnings detected
# Purpose: Auto-add dark:* variants to Tailwind colors

set -e

WORKSPACE="/Users/macbookpro/Projects/yalihan2026"
cd "$WORKSPACE"

echo "🌓 Dark Mode Variants Auto-Fixer başlıyor..."

# Color mapping: light → dark variants
declare -A COLOR_MAP=(
    ["text-gray-900"]="text-gray-900 dark:text-gray-50"
    ["text-gray-800"]="text-gray-800 dark:text-gray-100"
    ["text-gray-700"]="text-gray-700 dark:text-gray-300"
    ["bg-white"]="bg-white dark:bg-gray-900"
    ["bg-gray-50"]="bg-gray-50 dark:bg-gray-950"
    ["bg-gray-100"]="bg-gray-100 dark:bg-gray-800"
    ["bg-gray-200"]="bg-gray-200 dark:bg-gray-700"
    ["bg-blue-50"]="bg-blue-50 dark:bg-blue-950"
    ["bg-blue-500"]="bg-blue-500 dark:bg-blue-600"
    ["text-blue-500"]="text-blue-500 dark:text-blue-400"
)

COUNT=0

# Process all blade templates
for file in $(find resources/views -name "*.blade.php" -type f); do
    for light_class in "${!COLOR_MAP[@]}"; do
        dark_class="${COLOR_MAP[$light_class]}"
        
        # Only add if dark: variant not already present
        if grep -q "$light_class" "$file" && ! grep -q "${dark_class}" "$file"; then
            sed -i '' "s/$light_class/$dark_class/g" "$file"
            ((COUNT++))
            echo "✅ Fixed: $file"
        fi
    done
done

echo ""
echo "🎉 Dark Mode Variants Eklendi:"
echo "   • Dosyalar işlendi: $COUNT"
echo ""

# Verify with Context7 scan
echo "🔍 Context7 scan başlıyor..."
php artisan sab:integrity-scan 2>&1 | tail -10
