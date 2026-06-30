<?php

namespace App\Services\Wizard;

use App\Models\IlanTaslak;
use App\Services\AI\SmartFieldGenerationService;
use App\Services\AIService;
use App\Services\Wizard\FieldEngine\FieldSchemaResolver;
use Illuminate\Support\Facades\Log;

class WizardAIAssistantService
{
    public function __construct(
        protected AIService $aiService,
        protected SmartFieldGenerationService $smartFieldService,
        protected FieldSchemaResolver $schemaResolver
    ) {}

    /**
     * Get field suggestions for a draft based on title and description.
     */
    public function getSuggestions(IlanTaslak $draft): array
    {
        $payload = $draft->payload ?? [];
        $title = $payload['baslik'] ?? '';
        $description = $payload['aciklama'] ?? '';

        if (empty($title) && empty($description)) {
            return [];
        }

        // 1. Get Schema Fields for Context
        $fields = $this->schemaResolver->resolveFields(
            $draft->ana_kategori_id,
            $draft->yayin_tipi_id
        );

        // 2. Build AI Prompt
        $fieldContext = array_map(function ($field) {
            return [
                'slug' => $field['slug'],
                'name' => $field['name'],
                'type' => $field['type'], // context7-ignore
                'options' => $field['options'] ?? null
            ];
        }, $fields);

        $prompt = "Aşağıdaki emlak ilanı bilgilerinden teknik özellikleri çıkar.\n\n" .
            "BAŞLIK: {$title}\n" .
            "AÇIKLAMA: {$description}\n\n" .
            "Aşağıdaki alanlar için en uygun değerleri bul:\n" .
            json_encode($fieldContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n\n" .
            "SADECE JSON döndür. Bilmediğin alanları JSON'a ekleme.\n" .
            "Format: { \"slug\": { \"value\": mixed, \"reason\": \"string\", \"confidence\": float } }";

        try {
            // 3. Call LLM
            $aiResponse = $this->aiService->generate($prompt, [
                'max_tokens' => 1000,
                'temperature' => 0.1, // High precision
            ]);

            $suggestions = $this->parseAiResponse($aiResponse);

            // 4. Merge with Regex-based extraction (SmartFieldGenerationService)
            $regexSuggestions = $this->smartFieldService->extractFromText($title . " " . $description);
            
            foreach ($regexSuggestions as $regSug) {
                $slug = $regSug['slug'];
                // Regex matches usually have higher confidence for simple keywords
                if (!isset($suggestions[$slug]) || $regSug['confidence'] > ($suggestions[$slug]['confidence'] ?? 0)) {
                    $suggestions[$slug] = [
                        'value' => $this->normalizeValue($regSug['slug'], $regSug['source_reference']), // Simplified
                        'reason' => $regSug['reason'],
                        'confidence' => $regSug['confidence'],
                        'source' => 'regex'
                    ];
                }
            }

            return $suggestions;

        } catch (\Exception $e) {
            Log::error('WizardAIAssistantService failed', [
                'draft_id' => $draft->id,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    protected function parseAiResponse($response): array
    {
        $content = is_array($response) ? ($response['content'] ?? $response['text'] ?? '') : $response;
        
        // Clean JSON from potential markdown backticks
        $content = preg_replace('/^```json|```$/m', '', trim($content));
        
        $data = json_decode($content, true);
        
        return is_array($data) ? $data : [];
    }

    protected function normalizeValue(string $slug, mixed $value): mixed
    {
        // Simple normalization for regex-based keywords
        // Most binary features in regex map to true/1
        if (in_array($slug, ['balkon', 'teras', 'asansor', 'otopark', 'kredi-uygun'])) {
            return true;
        }
        return $value;
    }
}
