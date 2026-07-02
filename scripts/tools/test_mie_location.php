<?php

// MIE V4 Location Intelligence Smoke Test
// Usage: php scripts/test_mie_location.php (runs via artisan tinker)

use App\Services\MarketIntelligence\LocationIntelligenceService;

$service = app(LocationIntelligenceService::class);
$poiCount = \App\Models\PointOfInterest::count();

$tests = [
    ['name' => 'BODRUM MERKEZ (ilan #24)', 'lat' => 37.0335, 'lng' => 27.4330],
    ['name' => 'MUMCULAR (ilan #33)', 'lat' => 37.1382, 'lng' => 27.5662],
    ['name' => 'YALIKAVAK (ilan #14)', 'lat' => 37.1015, 'lng' => 27.2970],
    ['name' => 'TURGUTREIS (ilan #27)', 'lat' => 37.0100, 'lng' => 27.2600],
    ['name' => 'GUMUSLUK (ilan #31)', 'lat' => 37.0545, 'lng' => 27.2358],
    ['name' => 'KONACIK (ilan #30)', 'lat' => 37.0508, 'lng' => 27.4100],
];

echo str_repeat('=', 70) . PHP_EOL;
echo "MIE V4 LOCATION INTELLIGENCE - SMOKE TEST" . PHP_EOL;
echo "POI Count: " . $poiCount . PHP_EOL;
echo str_repeat('=', 70) . PHP_EOL;

foreach ($tests as $test) {
    $r = $service->analyze($test['lat'], $test['lng']);
    $groups = array_map(fn($g) => $g['group'] ?? $g, $r->top_nearby_groups);
    echo PHP_EOL . "--- " . $test['name'] . " ---" . PHP_EOL;
    echo "  Score:       " . ($r->location_signal_score ?? 'N/A') . "/100" . PHP_EOL;
    echo "  Label:       " . $r->confidence_label . PHP_EOL;
    echo "  Data:        " . $r->data_status . PHP_EOL;
    echo "  Demand Mod:  " . $r->demand_modifier . PHP_EOL;
    echo "  Access:      " . $r->poi_access_score . "/40" . PHP_EOL;
    echo "  Density:     " . $r->poi_density_score . "/30" . PHP_EOL;
    echo "  Coverage:    " . $r->poi_coverage_score . "/30" . PHP_EOL;
    echo "  Top Groups:  " . implode(', ', $groups) . PHP_EOL;
    echo "  Reasons:     " . implode('; ', $r->reason_codes) . PHP_EOL;
}

echo PHP_EOL . str_repeat('=', 70) . PHP_EOL;
echo "DONE" . PHP_EOL;
