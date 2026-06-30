#!/usr/bin/env php
<?php

require __DIR__ . '/vendor/autoload.php';

use App\Services\AI\NLPProcessor;
use App\Services\AI\IntentClassifier;
use App\Services\AI\EntityExtractor;

// Initialize services
$nlp = new NLPProcessor();
$intentClassifier = new IntentClassifier();
$entityExtractor = new EntityExtractor();

echo "=== NLP SERVICE TEST ===\n\n";

// Test messages
$messages = [
    "Bodrum'da 2-3 milyon TL'ye deniz manzaralı 3+1 daire arıyorum",
    "Yalıkavak'ta satılık arsa var mı?",
    "Fiyat ne kadar?",
    "Randevu almak istiyorum",
];

foreach ($messages as $message) {
    echo "📝 Message: \"$message\"\n";
    echo str_repeat("─", 80) . "\n";
    
    // Parse full
    $result = $nlp->parseMessage($message);
    
    echo "🎯 Intent: {$result['intent']}\n";
    echo "😊 Sentiment: {$result['sentiment']}\n";
    echo "📊 Confidence: " . round($result['confidence'] * 100, 1) . "%\n";
    
    if (!empty($result['entities'])) {
        echo "\n🏷️  Entities:\n";
        foreach ($result['entities'] as $key => $value) {
            if (is_array($value)) {
                echo "  - $key: " . json_encode($value) . "\n";
            } else {
                echo "  - $key: $value\n";
            }
        }
    }
    
    echo "\n";
}

echo "\n✅ NLP SERVICE TEST COMPLETE\n";
