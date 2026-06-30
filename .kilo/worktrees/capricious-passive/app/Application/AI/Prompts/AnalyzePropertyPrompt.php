<?php

namespace App\Application\AI\Prompts;

/**
 * ️ AnalyzePropertyPrompt
 * 
 * Logic to build the gap analysis prompt for property features.
 */
final class AnalyzePropertyPrompt extends BasePropertyPrompt
{
    public function getUserPrompt(): string
    {
        $kategori = $this->resolveCategoryName($this->input['kategori_id'] ?? null, $this->input['category_name'] ?? null);
        $currentList = implode(', ', $this->input['current_features'] ?? []);

        return "--- GÖREV (EKSİK ÖZELLİK ANALİZİ) ---\n" .
               "Kategori: {$kategori}\n" .
               "Mevcut Özellikler: {$currentList}\n\n" .
               "TALİMAT: Bu kategorideki PREMIUM ve profesyonel ilanlarda olması gereken ancak yukarıdaki listede EKSİK olan özellikleri belirle. Mevcut özellikleri TEKRAR ETME.\n\n" .
               "ÇIKTI FORMATI (SAF JSON):\n" .
               "{\n" .
               "  \"groups\": [\n" .
               "    {\n" .
               "      \"name\": \"Eksik Özellik Grubu\",\n" .
               "      \"features\": [\n" .
               "        {\"name\": \"Eksik Özellik Adı\", \"type\": \"checkbox\", \"options\": []}\n" .
               "      ]\n" .
               "    }\n" .
               "  ]\n" .
               "}\n\n" .
               "KURALLAR:\n" .
               "1. Sadece eksik olan kritik özellikleri öner.\n" .
               "2. Türkçe karakter kullan.\n" .
               "3. Gereksiz veya çok nadir özellikleri önerme.\n" .
               "JSON:";
    }

    public function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            'max_tokens' => 1200,
        ]);
    }
}
