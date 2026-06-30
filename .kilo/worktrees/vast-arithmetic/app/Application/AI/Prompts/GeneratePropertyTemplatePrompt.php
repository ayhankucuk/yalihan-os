<?php

namespace App\Application\AI\Prompts;

/**
 * ️ GeneratePropertyPrompt
 * 
 * Context for legacy property template generation.
 * Although this is currently handled by a JSON-based generator, 
 * treating it as a Prompt ensures architectural consistency.
 */
final class GeneratePropertyTemplatePrompt extends BasePropertyPrompt
{
    public function getUserPrompt(): string
    {
        $kategori = $this->input['kategori'] ?? 'Bilinmiyor';
        $yayinTipi = $this->input['yayin_tipi'] ?? 'Bilinmiyor';
        $altTur = $this->input['alt_tur'] ?? 'Bilinmiyor';

        return "--- GÖREV (STRÜKTÜR ÜRETİMİ) ---\n" .
               "Kombinasyon:\n" .
               "- Kategori: {$kategori}\n" .
               "- Yayın Tipi: {$yayinTipi}\n" .
               "- Alt Tür: {$altTur}\n\n" .
               "TALİMAT: Bu kombinasyon için sistemde tanımlı olan şablon yapısını çözümle.";
    }

    public function getOptions(): array
    {
        return array_merge(parent::getOptions(), [
            'is_legacy' => true,
        ]);
    }
}
