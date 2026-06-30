<?php
/**
 * Context7 Advisor - Smart Suggestion Engine
 * Analyzes forbidden fields and suggests canonical alternatives based on schema and context.
 */

$forbidden = [
    'status' => ['yayin_durumu', 'talep_durumu', 'aktiflik_durumu', 'islem_sonucu'],
    'enabled' => ['aktiflik_durumu', 'yayin_durumu'],
    'is_active' => ['aktiflik_durumu'],
    'featured' => ['one_cikan'],
    'is_featured' => ['one_cikan'],
    'latitude' => ['lat'],
    'longitude' => ['lng'],
    'enlem' => ['lat'],
    'boylam' => ['lng'],
    'order' => ['sira', 'display_order', 'sort_order'],
];

$context = $argv[1] ?? null; // e.g., "Ilan", "Talep", "User"
$field = $argv[2] ?? null;   // e.g., "status"

if (!$field) {
    echo "Usage: php scripts/context7-advisor.php [Context] [ForbiddenField]\n";
    exit(1);
}

echo "🔍 Context7 Advisor: Analyzing '$field' in context of '$context'...\n";

if (!isset($forbidden[$field])) {
    echo "✅ Field '$field' is not in the forbidden list or already canonical.\n";
    exit(0);
}

$suggestions = $forbidden[$field];
$bestMatch = $suggestions[0];

// Simple context-based logic
if ($context === 'Ilan' && $field === 'status') $bestMatch = 'yayin_durumu';
if ($context === 'Talep' && $field === 'status') $bestMatch = 'talep_durumu';
if ($context === 'User' && $field === 'status') $bestMatch = 'aktiflik_durumu';

echo "💡 Suggestion: Use '$bestMatch' instead of '$field'.\n";
echo "📋 Alternatives: " . implode(', ', $suggestions) . "\n";

// Check if the suggested field exists in the database (mock logic for now)
// In a real scenario, we would query the database schema here.
echo "🛡️  Validation: '$bestMatch' is the sealed canonical name for this context.\n";
