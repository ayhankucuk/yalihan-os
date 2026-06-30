<?php

use App\Models\User;
use Illuminate\Http\Request;
use App\Http\Controllers\Api\V1\WizardController;

require __DIR__ . '/../../vendor/autoload.php';
$app = require_once __DIR__ . '/../../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Auth simulation
$user = User::where('email', 'ayhankucuk@gmail.com')->first();
if (!$user) {
    echo "Admin user not found!\n";
    exit(1);
}
auth()->login($user);

echo "🔍 Testing Wizard Template Resolution (Konut + Satılık)...\n";

try {
    $controller = app()->make(WizardController::class);

    // Simulate Request
    $request = Request::create('/api/v1/wizard/template-auto-select', 'GET', [
        'kategori_id' => 7, // Daire
        'yayin_tipi_id' => 1 // Satılık
    ]);

    $response = $controller->templateAutoSelect($request);
    $data = $response->getData(true); // Convert to array

    if (isset($data['success']) && $data['success']) {
        echo "✅ SUCCESS: Template Resolved\n";
        echo "--------------------------------------------------\n";
        echo "Template ID: " . ($data['data']['template_id'] ?? 'NULL') . "\n";

        $features = $data['data']['features'] ?? [];
        echo "Feature Count: " . count($features) . "\n";

        // Show first 3 features
        echo "Sample Features:\n";
        foreach (array_slice($features, 0, 3) as $f) {
            echo " - " . ($f['name'] ?? $f['slug'] ?? 'Unknown') . " (Type: " . ($f['type'] ?? '?') . ")\n";
        }
        echo "--------------------------------------------------\n";
        echo "Full Response Structure (Truncated):\n";
        print_r(array_keys($data['data']));
    } else {
        echo "❌ FAILED: API returned error\n";
        print_r($data);
    }

} catch (\Exception $e) {
    echo "❌ EXCEPTION: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString();
}
