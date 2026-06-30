sed -i '' -e '/public function requestLlmGeneration/i\
    /**\
     * Legacy Content proxy for AIContentController\
     */\
    public function generateFromLegacyPrompt(string $prompt, string $provider): string\
    {\
        $response = $this->aiService->generate($prompt, ['\''provider'\'' => $provider]);\
        if (is_array($response)) {\
            return trim(str_replace('\'"\'', '\'\'', $response['\''content'\''] ?? ($response['\''text'\''] ?? '\'\'')));\
        }\
        return trim(str_replace('\'"\'', '\'\'', (string)$response));\
    }\
' app/Services/AI/YalihanCortex.php
