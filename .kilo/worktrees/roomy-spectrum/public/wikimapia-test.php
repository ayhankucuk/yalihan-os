<?php
/**
 * WikiMapia API Direct Test
 *
 * Test both API keys and see real responses
 * URL: http://127.0.0.1:8000/wikimapia-test.php
 */
header('Content-Type: text/html; charset=utf-8');

// API Keys
$keys = [
    'production' => '2A164909-AFCD1C06-7F3C5F21-526B8425-306B474E-B58D4B62-1A9A5C7D-968D43B0',
    'localhost' => '2A164909-650A324F-D72B6DE0-5295B19C-008407CA-7BD6D642-B24C38D5-5E6E7A7A',
];

// Test coordinates: Bodrum center
$lat = 37.0344;
$lon = 27.4305;
$radius = 0.05;

$lonMin = $lon - $radius;
$latMin = $lat - $radius;
$lonMax = $lon + $radius;
$latMax = $lat + $radius;

?>
<!DOCTYPE html>
<html>
<head>
    <title>WikiMapia API Test</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #1a1a1a; color: #00ff00; }
        .test-result { margin: 20px 0; padding: 15px; background: #2a2a2a; border-left: 4px solid #00ff00; }
        .error { border-left-color: #ff0000; color: #ff6b6b; }
        .success { border-left-color: #00ff00; }
        .warning { border-left-color: #ffaa00; color: #ffaa00; }
        h2 { color: #00ffff; }
        pre { background: #000; padding: 10px; overflow-x: auto; border-radius: 5px; }
        .key-info { background: #333; padding: 10px; margin: 10px 0; border-radius: 5px; }
    </style>
</head>
<body>
    <h1>🗺️ WikiMapia API Direct Test</h1>
    <p>Testing API keys and real responses from WikiMapia</p>

    <?php
    foreach ($keys as $keyName => $apiKey) {
        echo "<div class='test-result'>";
        echo "<h2>🔑 Testing: {$keyName} key</h2>";
        echo "<div class='key-info'>Key: ".substr($apiKey, 0, 20).'...</div>';

        // Test 1: box.getbyarea (nearby search)
        echo '<h3>📍 Test 1: box.getbyarea (Bodrum area)</h3>';
        $url = 'https://api.wikimapia.org/?function=box.getbyarea'
             ."&bbox={$lonMin},{$latMin},{$lonMax},{$latMax}"
             ."&key={$apiKey}"
             .'&format=json'
             .'&category=203' // Residential
             .'&page=1'
             .'&count=10';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200) {
            $data = json_decode($response, true);
            $found = $data['found'] ?? 0;
            $places = $data['places'] ?? [];

            if ($found > 0) {
                echo "<div class='success'>";
                echo "✅ SUCCESS! Found {$found} places<br>";
                echo 'First place: <strong>'.($places[0]['title'] ?? 'N/A').'</strong><br>';
                echo 'Description: '.substr($places[0]['description'] ?? 'N/A', 0, 100).'...';
                echo '</div>';

                // Check if test data
                if (isset($places[0]['description']) &&
                    str_contains(strtolower($places[0]['description']), 'deneme')) {
                    echo "<div class='warning'>⚠️ WARNING: This looks like TEST DATA (contains 'deneme')</div>";
                }
            } else {
                echo "<div class='warning'>⚠️ No places found</div>";
            }

            echo '<details><summary>📄 Full Response (click to expand)</summary>';
            echo '<pre>'.json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>';
            echo '</details>';
        } else {
            echo "<div class='error'>❌ HTTP Error: {$httpCode}</div>";
            echo "<pre>{$response}</pre>";
        }

        // Test 2: place.getbyid (if we have a place from test 1)
        if (! empty($places) && isset($places[0]['id'])) {
            $placeId = $places[0]['id'];
            echo "<h3>📍 Test 2: place.getbyid (ID: {$placeId})</h3>";

            $url = 'https://api.wikimapia.org/?function=place.getbyid'
                 ."&id={$placeId}"
                 ."&key={$apiKey}"
                 .'&format=json'
                 .'&data_blocks=main,location';

            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            $response = curl_exec($ch);
            curl_close($ch);

            $data = json_decode($response, true);

            echo '<details><summary>📄 Place Details (click to expand)</summary>';
            echo '<pre>'.json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE).'</pre>';
            echo '</details>';
        }

        echo '</div>'; // test-result
        echo "<hr style='border-color: #444; margin: 30px 0;'>";
    }
?>

    <div class='test-result'>
        <h2>📊 Summary</h2>
        <ul>
            <li><strong>Production Key:</strong> <?= $keys['production'] ?></li>
            <li><strong>Localhost Key:</strong> <?= $keys['localhost'] ?></li>
            <li><strong>Test Location:</strong> Bodrum (<?= $lat ?>, <?= $lon ?>)</li>
            <li><strong>Search Radius:</strong> <?= $radius * 111 ?>km (~<?= round($radius * 111, 1) ?>km)</li>
        </ul>

        <h3>🔧 Next Steps:</h3>
        <ul>
            <li>✅ If you see "Found X places" with real titles → Key is working!</li>
            <li>⚠️ If you see "deneme" in descriptions → Key NOT verified, test data only</li>
            <li>❌ If HTTP error → API key invalid or blocked</li>
        </ul>

        <h3>🔑 To Verify Keys:</h3>
        <ol>
            <li>Go to: <a href="https://wikimapia.org/api/#my-keys" target="_blank" style="color: #00ffff;">https://wikimapia.org/api/#my-keys</a></li>
            <li>Click "Edit" on each key</li>
            <li>For LOCALHOST key: Select "No verification" (development mode)</li>
            <li>For PRODUCTION key: Add HTML verification file or meta tag</li>
            <li>Save and wait 5-10 minutes</li>
        </ol>
    </div>

    <p style="text-align: center; margin-top: 40px; color: #666;">
        🚀 Yalıhan Emlak - WikiMapia Integration Test<br>
        Made with ❤️ by Claude
    </p>
</body>
</html>
