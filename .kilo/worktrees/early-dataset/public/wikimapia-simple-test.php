<?php

/**
 * WikiMapia API Simple JSON Test
 *
 * Quick test to see raw API responses
 * URL: http://127.0.0.1:8000/wikimapia-simple-test.php
 */

// API Keys
$apiKey = '2A164909-AFCD1C06-7F3C5F21-526B8425-306B474E-B58D4B62-1A9A5C7D-968D43B0';

// Test 1: Get place by ID
$placeId = 12345; // örnek ID

echo '<h1>WikiMapia API Simple Test</h1>';
echo '<hr>';

// Test place.getbyid
echo "<h2>Test 1: place.getbyid (ID: $placeId)</h2>";
$url = "https://api.wikimapia.org/?function=place.getbyid&id=$placeId&key=$apiKey&format=json";
echo "<p><strong>URL:</strong> $url</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<pre style='background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;'>";
echo json_encode(json_decode($response), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo '</pre>';

echo '<hr>';

// Test 2: box.getbyarea (Bodrum area)
echo '<h2>Test 2: box.getbyarea (Bodrum)</h2>';
$lat = 37.0344;
$lon = 27.4305;
$radius = 0.05;

$lonMin = $lon - $radius;
$latMin = $lat - $radius;
$lonMax = $lon + $radius;
$latMax = $lat + $radius;

$url = "https://api.wikimapia.org/?function=box.getbyarea&bbox={$lonMin},{$latMin},{$lonMax},{$latMax}&key={$apiKey}&format=json&category=203&page=1&count=5";
echo "<p><strong>URL:</strong> $url</p>";
echo "<p><strong>Area:</strong> Bodrum ($lat, $lon) ± {$radius}°</p>";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "<p><strong>HTTP Code:</strong> $httpCode</p>";
echo "<pre style='background:#f5f5f5;padding:15px;border-radius:5px;overflow-x:auto;'>";
$data = json_decode($response, true);
echo json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
echo '</pre>';

// Analysis
if ($httpCode == 200 && isset($data['found'])) {
    echo '<hr>';
    echo '<h2>📊 Analysis</h2>';
    echo "<p><strong>Found:</strong> {$data['found']} places</p>";

    if (! empty($data['places'])) {
        $firstPlace = $data['places'][0];
        echo "<p><strong>First Place Title:</strong> {$firstPlace['title']}</p>";
        echo '<p><strong>First Place Description:</strong> '.substr($firstPlace['description'] ?? 'N/A', 0, 100).'...</p>';

        // Check if test data
        if (stripos($firstPlace['description'] ?? '', 'deneme') !== false) {
            echo "<p style='color:orange;font-weight:bold;'>⚠️ WARNING: This looks like TEST DATA!</p>";
            echo "<p>Your API key is <strong>NOT VERIFIED</strong>. Go to <a href='https://wikimapia.org/api/#my-keys' target='_blank'>WikiMapia Keys</a> and verify your domain.</p>";
        } else {
            echo "<p style='color:green;font-weight:bold;'>✅ SUCCESS: Real data returned!</p>";
        }
    }
}

echo '<hr>';
echo "<p><a href='/wikimapia-test.php'>→ Kapsamlı Test İçin Tıkla</a> (Her iki key + detaylı analiz)</p>";
