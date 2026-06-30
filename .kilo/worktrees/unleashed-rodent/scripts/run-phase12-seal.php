#!/usr/bin/env php
<?php
/**
 * Phase 12 Seal Executor - Wrapper Script
 * Bu script phase12-complete-seal.php'yi çalıştırır ve çıktıyı kaydeder
 */

$output = [];
$returnCode = 0;

echo "🚀 Executing Phase 12 Complete Seal...\n\n";

exec('php ' . __DIR__ . '/phase12-complete-seal.php 2>&1', $output, $returnCode);

$fullOutput = implode("\n", $output);
echo $fullOutput . "\n";

// Çıktıyı dosyaya kaydet
file_put_contents(__DIR__ . '/../logs/phase12-seal-output.log', $fullOutput);

echo "\n📝 Output saved to: logs/phase12-seal-output.log\n";
echo "📊 Exit Code: {$returnCode}\n";

if ($returnCode === 0) {
    echo "\n✅ Phase 12 seal completed successfully!\n";
} else {
    echo "\n❌ Phase 12 seal failed with exit code: {$returnCode}\n";
}

exit($returnCode);
