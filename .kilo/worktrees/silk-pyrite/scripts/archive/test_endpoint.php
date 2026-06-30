<?php
$ch = curl_init('http://localhost:8002/api/v1/wizard/validate-step-2');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json',
]);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
    'alt_kategori_id' => 'arsa',
    'yayin_tipi_id' => 'satilik',
    'baslik' => 'Test',
]));
$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
echo "HTTP: $http_code\n";
echo "Response: $response\n";
?>
