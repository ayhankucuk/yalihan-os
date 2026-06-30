<?php

namespace App\Application\AI\Prompts;

/**
 * ️ ExtractFeaturesPrompt
 * 
 * Logic to build the features extraction prompt from raw text.
 */
final class ExtractFeaturesPrompt extends BasePropertyPrompt
{
    public function getUserPrompt(): string
    {
        $text = $this->input['text'] ?? '';
        $safeText = mb_substr($text, 0, 4000);

        return "--- GÖREV (METİNDEN ÖZELLİK ÇIKARIMI) ---\n" .
               "METİN:\n{$safeText}\n\n" .
               "TALİMAT: Metinde geçen tüm özellikleri gruplayarak JSON formatında listele.\n\n" .
               "ÇIKTI FORMATI (SAF JSON):\n" .
               "{\n" .
               "  \"groups\": [\n" .
               "    {\n" .
               "      \"name\": \"Grup Adı\",\n" .
               "      \"features\": [\n" .
               "        {\"name\": \"Özelllik Adı\", \"type\": \"checkbox\", \"options\": []}\n" .
               "      ]\n" .
               "    }\n" .
               "  ]\n" .
               "}\n\n" .
               "KURALLAR:\n" .
               "1. Sadece metinde açıkça geçen özellikleri çıkar.\n" .
               "2. Grupları anlamlı hale getir (Örn: İç Özellikler, Konum, Mutfak).\n" .
               "3. Türkçe karakter kullan.\n" .
               "JSON:";
    }

    public function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            'max_tokens' => 1500,
        ]);
    }
}
