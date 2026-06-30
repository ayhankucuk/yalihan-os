<?php

namespace App\Services;

use App\Services\Cache\CacheHelper;

class AIPromptManager
{
    private $promptTemplates;

    private $contextRules;

    public function __construct()
    {
        $this->promptTemplates = $this->loadPromptTemplates();
        $this->contextRules = $this->loadContextRules();
    }

    /**
     * Context'e göre prompt oluştur
     */
    public function generatePrompt($context, $task)
    {
        $template = $this->getTemplate($context);
        $rules = $this->getContextRules($context);

        return $this->buildPrompt($template, $rules, $task);
    }

    /**
     * Öğrenilen prompt'ları kaydet
     */
    public function saveLearnedPrompt($context, $prompt, $successRate)
    {
        \App\Models\AICoreSystem::updateOrCreate(
            ['context' => $context, 'task_type' => 'learned_prompt'],
            [
                'prompt_template' => $prompt,
                'success_rate' => $successRate,
                'usage_count' => 1,
                'aktiflik_durumu' => true, // Context7: status → aktiflik_durumu
            ]
        );
    }

    /**
     * En iyi prompt'u seç
     */
    public function getBestPrompt($context)
    {
        // ✅ STANDARDIZED: Using CacheHelper
        $learnedPrompts = CacheHelper::remember(
            'ai',
            'best_prompt',
            'medium',
            function () use ($context) {
                return \App\Models\AICoreSystem::where('context', $context)
                    ->where('aktiflik_durumu', 1) // Context7: aktiflik_durumu (boolean)
                    ->orderBy('success_rate', 'desc') // context7-ignore
                    ->first();
            },
            ['context' => $context]
        );

        if ($learnedPrompts) {
            return $learnedPrompts->prompt_template;
        }

        return $this->getDefaultPrompt($context);
    }

    /**
     * Prompt template'leri yükle
     */
    private function loadPromptTemplates()
    {
        return [
            'form_generation' => [
                'template' => "Sen bir emlak formu uzmanısın. {category} kategorisi için {publication_type} yayın tipinde form alanları öner.\n\nKategori: {category}\nYayın Tipi: {publication_type}\n\nÖneriler:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Emlak sektörüne uygun',
                    'Kullanıcı dostu',
                    'Zorunlu alanları belirt',
                ],
            ],
            'matrix_management' => [
                'template' => "Sen bir emlak matrix uzmanısın. {category} kategorisi için field dependency matrix oluştur.\n\nKategori: {category}\nAlanlar: {fields}\n\nMatrix:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Mantıklı bağımlılıklar',
                    'AI destekli alanları belirt',
                    'Kullanıcı deneyimi odaklı',
                ],
            ],
            'suggestion_engine' => [
                'template' => "Sen bir emlak öneri uzmanısın. {context} bağlamında {input} için öneriler ver.\n\nBağlam: {context}\nGiriş: {input}\n\nÖneriler:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Pratik öneriler',
                    'Kullanıcı odaklı',
                    'Detaylı açıklama',
                ],
            ],
            'hibrit_siralama' => [
                'template' => "Sen bir emlak sıralama uzmanısın. {category} kategorisi için özellikleri önem sırasına göre sırala.\n\nKategori: {category}\nÖzellikler: {features}\n\nSıralama:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Önem sırasına göre',
                    'Kullanım sıklığına göre',
                    'AI önerilerine göre',
                ],
            ],
        ];
    }

    /**
     * Context kuralları yükle
     */
    private function loadContextRules()
    {
        return [
            'konut' => [
                'Alanlar: Oda sayısı, Banyo sayısı, Metrekare, Kat, Isıtma, Asansör',
                'AI Destekli: Fiyat tahmini, Özellik önerileri, Benzer ilanlar',
            ],
            'arsa' => [
                'Alanlar: Ada, Parsel, İmar statusu, KAKS, TAKS, Gabari',
                'AI Destekli: Değerleme, İmar analizi, Yatırım potansiyeli',
            ],
            'yazlik' => [
                'Alanlar: Günlük fiyat, Minimum konaklama, Havuz, Sezon',
                'AI Destekli: Fiyat optimizasyonu, Sezon analizi, Rezervasyon önerileri',
            ],
            'isyeri' => [
                'Alanlar: Metrekare, Kat, Otopark, Asansör, Lokasyon',
                'AI Destekli: Kira analizi, Lokasyon değerlendirmesi, Yatırım önerileri',
            ],
        ];
    }

    /**
     * Template getir
     */
    private function getTemplate($context)
    {
        $templateKey = $this->extractTemplateKey($context);

        return $this->promptTemplates[$templateKey]['template'] ?? $this->getDefaultTemplate();
    }

    /**
     * Context kuralları getir
     */
    private function getContextRules($context)
    {
        $category = $this->extractCategory($context);

        return $this->contextRules[$category] ?? $this->getDefaultRules();
    }

    /**
     * Prompt oluştur
     */
    private function buildPrompt($template, $rules, $task)
    {
        $prompt = $template;

        // Template değişkenlerini değiştir
        $prompt = str_replace('{category}', $task['category'] ?? 'genel', $prompt);
        $prompt = str_replace('{publication_type}', $task['publication_type'] ?? 'genel', $prompt);
        $prompt = str_replace('{context}', $task['context'] ?? 'genel', $prompt);
        $prompt = str_replace('{input}', $task['input'] ?? 'genel', $prompt);
        $prompt = str_replace('{fields}', $task['fields'] ?? 'genel', $prompt);
        $prompt = str_replace('{features}', $task['features'] ?? 'genel', $prompt);

        // Kuralları ekle
        if ($rules) {
            $prompt .= "\n\nKurallar:\n".implode("\n", $rules);
        }

        return $prompt;
    }

    /**
     * Template key çıkar
     */
    private function extractTemplateKey($context)
    {
        if (strpos($context, 'form_generation') !== false) {
            return 'form_generation';
        }
        if (strpos($context, 'matrix_management') !== false) {
            return 'matrix_management';
        }
        if (strpos($context, 'suggestion_engine') !== false) {
            return 'suggestion_engine';
        }
        if (strpos($context, 'hibrit_siralama') !== false) {
            return 'hibrit_siralama';
        }

        return 'suggestion_engine';
    }

    /**
     * Kategori çıkar
     */
    private function extractCategory($context)
    {
        if (strpos($context, 'konut') !== false) {
            return 'konut';
        }
        if (strpos($context, 'arsa') !== false) {
            return 'arsa';
        }
        if (strpos($context, 'yazlik') !== false) {
            return 'yazlik';
        }
        if (strpos($context, 'isyeri') !== false) {
            return 'isyeri';
        }

        return 'genel';
    }

    /**
     * Varsayılan prompt
     */
    private function getDefaultPrompt($context)
    {
        return "Sen bir emlak uzmanısın. {$context} bağlamında yardımcı ol.\n\nBağlam: {$context}\n\nYanıt:";
    }

    /**
     * Varsayılan template
     */
    private function getDefaultTemplate()
    {
        return "Sen bir emlak uzmanısın. {category} kategorisi için {publication_type} yayın tipinde yardımcı ol.\n\nKategori: {category}\nYayın Tipi: {publication_type}\n\nYanıt:";
    }

    /**
     * Varsayılan kurallar
     */
    private function getDefaultRules()
    {
        return [
            'Türkçe yanıt ver',
            'Emlak sektörüne uygun',
            'Kullanıcı dostu',
            'Detaylı açıklama',
        ];
    }
}
