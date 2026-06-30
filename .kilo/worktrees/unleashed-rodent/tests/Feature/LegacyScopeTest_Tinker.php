<?php
// Simulate legacy data (comma separated string) handling
$legacyData = "konut,arsa";
$jsonDecoded = json_decode($legacyData, true);
echo "Legacy String 'konut,arsa' json_decode result: " . var_export($jsonDecoded, true) . PHP_EOL;

// Simulate JSON data
$jsonData = '["konut","arsa"]';
$jsonDecodedUsing = json_decode($jsonData, true);
echo "JSON String '[\"konut\",\"arsa\"]' json_decode result: " . var_export($jsonDecodedUsing, true) . PHP_EOL;

// Proposed fix logic check
$appliesToArray = json_decode($legacyData, true);
if (json_last_error() !== JSON_ERROR_NONE && is_string($legacyData)) {
    // Fallback to comma explode
    $appliesToArray = explode(',', $legacyData);
    $appliesToArray = array_map('trim', $appliesToArray);
}
echo "Proposed Fix Result for Legacy: " . var_export($appliesToArray, true) . PHP_EOL;
