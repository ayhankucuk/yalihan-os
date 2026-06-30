<?php
// context7-ignore: 'description' bu dosyada prompt şablonu metin içeriği. Domain model DB alanı değil.

namespace App\Application\AI\Prompts;

/**
 * ️ SuggestTemplatePrompt
 *
 * Logic to build the property template suggestion prompt.
 */
final class SuggestTemplatePrompt extends BasePropertyPrompt
{
    public function getUserPrompt(): string
    {
        $kategori = $this->resolveCategoryName($this->input['kategori_id'] ?? null, $this->input['category_name'] ?? null);
        $description = $this->input['description'] ?? '';

        return "--- GÖREV (EMLAK ŞABLON ÖNERİSİ) ---\n" .
               "Kategori: {$kategori}\n" .
               "Açıklama: {$description}\n\n" .
               "TALİMAT: Bu emlak türü için kullanıcıların filtrelemede kullanacağı ve ilan detayında görmek isteyeceği özellikleri gruplayarak listele.\n\n" .
               "ÇIKTI FORMATI (SAF JSON):\n" .
               "{\n" .
               "  \"groups\": [\n" .
               "    {\n" .
               "      \"name\": \"Özellik Grubu Adı (Örn: Mutfak)\",\n" .
               "      \"features\": [\n" .
               "        {\"name\": \"Özellik Adı (Örn: Bulaşık Makinesi)\", \"type\": \"checkbox\", \"options\": []},\n" .
               "        {\"name\": \"Özellik Adı (Örn: Mutfak Tipi)\", \"type\": \"select\", \"options\": [\"Amerikan\", \"Ayrı\", \"Kitchenette\"]}\n" .
               "      ]\n" .
               "    }\n" .
               "  ]\n" .
               "}\n\n" .
               "KURALLAR:\n" .
               "1. En az 5 grup ve her grupta en az 3 özellik olsun.\n" .
               "2. Türkçe karakter kullan.\n" .
               "3. Boolean (var/yok) özellikler için 'type': 'checkbox' kullan.\n" .
               "4. Seçenekli özellikler için 'type': 'select' ve 'options' array'i kullan.\n" .
               "JSON:";
    }

    public function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            'max_tokens' => 1200,
        ]);
    }
}
