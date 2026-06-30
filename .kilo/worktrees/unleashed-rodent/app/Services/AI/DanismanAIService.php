<?php

namespace App\Services\AI;

/**
 * @sab-ignore-catch
 */

use App\Models\DanismanChatMessage;
use App\Models\DanismanChatSession;
use App\Models\User;
use Illuminate\Support\Str;

class DanismanAIService
{
    protected OllamaService $ollama;
    protected OpenAIService $openai;
    protected EmbeddingService $embeddingService;
    protected AiCostGuardService $costGuard;

    /**
     * @D3-A SAB v24.0: title/description path artık YalihanCortex üzerinden
     */
    protected YalihanCortex $cortex;

    public function __construct(
        OllamaService $ollama,
        OpenAIService $openai,
        EmbeddingService $embeddingService,
        AiCostGuardService $costGuard,
        YalihanCortex $cortex
    ) {
        $this->ollama = $ollama;
        $this->openai = $openai;
        $this->embeddingService = $embeddingService;
        $this->costGuard = $costGuard;
        $this->cortex = $cortex;
    }

    /**
     * @D3-B QUARANTINE: Bu metod domain-specific mantığı ve stateful chat yapısı
     * nedeniyle şimdilik Cortex'e taşınmamıştır. 
     * 
     * 🔐 INTERFACE LOCK: Girdi ve çıktı kontratı bozulmadan korunmalıdır.
     * 🛡️ GUARD POLICY: DanismanAIService içinde provider call (Ollama/OpenAI) 
     * SADECE bu metod ve generateContent() içinde yapılabilir.
     */
    public function chat(User $user, string $message, ?string $sessionId = null, array $context = []): array
    {
        Log::info('AI:Danisman:Chat:Request', [
            'user_id' => $user->id,
            'session_id' => $sessionId,
            'message_length' => mb_strlen($message),
            'timestamp' => now()->toIso8601String()
        ]);

        // 🛡️ SAB Prompt Injection & Sanitize Guard
        $message = $this->sanitizeInput($message);

        if (empty($message)) {
            throw new \InvalidArgumentException("Geçersiz veya zararlı girdi tespit edildi.");
        }

        // 1. Oturum Yönetimi
        $session = $this->getOrCreateSession($user, $sessionId, $context);

        // 2. Kullanıcı Mesajını Kaydet
        $this->saveMessage($session, 'user', $message);

        // 3. RAG: İlgili İlanları Bul
        $relevantIlans = $this->searchRelevantListings($message);
        $ragContext = "";
        if (!empty($relevantIlans)) {
            $ragContext = "\n\nBULUNAN SLGİLİ İLANLAR:\n" . implode("\n---\n", $relevantIlans) . "\n\n";
        }

        // Model Seçimi (Context veya Default)
        $model = $context['model'] ?? 'gemma2:2b';

        // 🛡️ Cost Guard — chat() provider bypass'ı önlemek için
        $chatProvider = $this->isLocalModel($model) ? 'ollama' : 'openai';
        $budget = $this->costGuard->checkBudget($chatProvider);
        if (!$budget['allowed']) {
            throw new \RuntimeException('AI bütçe sınırı aşıldı: ' . ($budget['reason'] ?? $chatProvider));
        }

        try {
            if ($this->isLocalModel($model)) {
                // LOCAL (OLLAMA)
                // Prompt Hazırlığı (Text Base)
                $prompt = $this->buildPromptForLocal($session, $message, $ragContext);

                $response = $this->ollama->generateCompletion($prompt);

                $aiContent = $response['response'] ?? 'Üzgünüm, şu an yanıt veremiyorum.';
                $metadata = [
                    'model' => $response['model'] ?? $model,
                    'provider' => 'ollama',
                    'total_duration' => $response['total_duration'] ?? 0,
                    'rag_hits' => count($relevantIlans)
                ];

            } else {
                // CLOUD (OPENAI / DEEPSEEK)
                // Prompt Hazırlığı (Chat Message Array)
                $messages = $this->buildMessagesForCloud($session, $message, $ragContext);

                $response = $this->openai->chat($messages, $model);

                $aiContent = $response['content'];
                $metadata = [
                    'model' => $response['model'],
                    'provider' => 'openai_compatible',
                    'usage' => $response['usage'],
                    'rag_hits' => count($relevantIlans)
                ];
            }

            // 5. AI Yanıtını Kaydet
            $this->saveMessage($session, 'assistant', $aiContent, $metadata);

            return [
                'session_id' => $session->session_id,
                'message' => $aiContent,
                'metadata' => $metadata
            ];

        } catch (\Exception $e) {
            // Hata Durumu
            $this->saveMessage($session, 'assistant', 'Bir hata oluştu.', [], true, $e->getMessage());
            throw $e;
        }
    }

    protected function searchRelevantListings(string $query): array
    {
        // 1. Generate query embedding
        $queryEmbedding = $this->embeddingService->getEmbedding($query);
        if (!$queryEmbedding) return [];

        // 2. Fetch all listing embeddings (Optimization: Cache or Limit)
        // For < 1000 items, PHP calculation is fast enough.
        $embeddings = \App\Models\IlanEmbedding::where('aktiflik_durumu', 1)->get();

        $scores = [];
        foreach ($embeddings as $item) {
            if (empty($item->embedding)) continue;

            $score = $this->embeddingService->cosineSimilarity($queryEmbedding, $item->embedding);
            if ($score > 0.4) { // Threshold
                $scores[] = [
                    'id' => $item->ilan_id,
                    'score' => $score
                ];
            }
        }

        // 3. Sort by score
        usort($scores, fn($a, $b) => $b['score'] <=> $a['score']);

        // 4. Take top 3
        $topIds = array_slice(array_column($scores, 'id'), 0, 3);
        if (empty($topIds)) return [];

        // 5. Fetch Listing Details
        $ilans = \App\Models\Ilan::whereIn('id', $topIds)->get();

        $results = [];
        foreach ($ilans as $ilan) {
            $results[] = sprintf(
                "ID: %d | Başlık: %s | Fiyat: %s %s | Konum: %s/%s | Oda: %s",
                $ilan->id,
                $ilan->baslik,
                number_format($ilan->fiyat, 0, ',', '.'),
                $ilan->para_birimi,
                $ilan->ilce?->ilce_adi ?? '?',
                $ilan->mahalle?->mahalle_adi ?? '?',
                $ilan->oda_sayisi ?? '?'
            );
        }

        return $results;
    }

    /**
     * SAB: Basic input sanitization and prompt injection protection
     */
    protected function sanitizeInput(string $input): string
    {
        // Temel XSS/HTML taglerini temizle
        $clean = strip_tags($input);

        // Sık kullanılan prompt injection anahtar kelimelerini filtrele (Basic Check)
        $forbiddenKeywords = [
            'ignore all previous', 'ignore previous', 'forget previous', 'bütün talimatları unut',
            'önceki talimatları unut', 'sistem istemini', 'system prompt', 'you are now', 'artık şusun',
            'drop table', 'delete from'
        ];

        foreach ($forbiddenKeywords as $keyword) {
            if (stripos($clean, $keyword) !== false) {
                Log::warning('Cortex AI: Prompt Injection girişimi engellendi.', ['input' => $input]);
                return ''; // Engelle
            }
        }

        // Max token limit koruması (Kaba karakter hesabı)
        if (mb_strlen($clean) > 2000) {
            $clean = mb_substr($clean, 0, 2000);
            Log::info('Cortex AI: Input çok uzun, 2000 karaktere kırpıldı.');
        }

        return trim($clean);
    }

    protected function isLocalModel(string $model): bool
    {
        // Bilinen local model önekleri veya isimleri
        $localModels = ['gemma', 'llama', 'mistral', 'phi', 'qwen', 'local'];
        foreach ($localModels as $l) {
            if (Str::contains(strtolower($model), $l)) {
                return true;
            }
        }
        return false;
    }

    protected function getOrCreateSession(User $user, ?string $sessionId, array $context): DanismanChatSession
    {
        if ($sessionId) {
            return DanismanChatSession::firstOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_id' => $user->id,
                    'title' => 'Yeni Sohbet',
                    'context_data' => $context
                ]
            );
        }

        return DanismanChatSession::create([
            'session_id' => (string) Str::uuid(),
            'user_id' => $user->id,
            'title' => 'Yeni Sohbet ' . now()->format('d.m H:i'),
            'context_data' => $context
        ]);
    }

    protected function saveMessage(DanismanChatSession $session, string $role, string $content, array $metadata = [], bool $isError = false, ?string $errorMessage = null)
    {
        return DanismanChatMessage::create([
            'session_id' => $session->id,
            'role' => $role,
            'content' => $content,
            'metadata' => $metadata,
            'is_error' => $isError,
            'error_message' => $errorMessage
        ]);
    }

    protected function buildPromptForLocal(DanismanChatSession $session, string $newMessage, string $ragContext = ""): string
    {
        $systemPrompt = $this->getSystemPrompt();

        if (!empty($ragContext)) {
            $systemPrompt .= $ragContext . "\nYukarıdaki ilan bilgilerini kullanarak soruları yanıtla. Bilmediğin bilgiyi uydurma.";
        }

        $history = $session->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse()
            ->map(function ($msg) {
                return ($msg->role === 'user' ? 'User: ' : 'Assistant: ') . $msg->content;
            })
            ->implode("\n");

        return $systemPrompt . "\n\n" . $history . "\nUser: " . $newMessage . "\nAssistant:";
    }

    protected function buildMessagesForCloud(DanismanChatSession $session, string $newMessage, string $ragContext = ""): array
    {
        $systemContent = $this->getSystemPrompt();
        if (!empty($ragContext)) {
            $systemContent .= $ragContext . "\nYukarıdaki ilan bilgilerini kullanarak soruları yanıtla.";
        }

        $messages = [
            ['role' => 'system', 'content' => $systemContent]
        ];

        $history = $session->messages()
            ->latest()
            ->take(10)
            ->get()
            ->reverse();

        foreach ($history as $msg) {
            $messages[] = [
                'role' => $msg->role,
                'content' => $msg->content
            ];
        }

        $messages[] = ['role' => 'user', 'content' => $newMessage];

        return $messages;
    }

    protected function getSystemPrompt(): string
    {
        return <<<EOT
Sen Yalıhan Emlak'ın yapay zeka asistanısın. Görevin emlak danışmanlarına (kullanıcılara) işlerinde yardımcı olmak.
Aşağıdaki kurallara uy:
- Yanıtların kısa, öz ve profesyonel olsun.
- Emlak terimlerini doğru kullan.
- Yardımcı olabileceğin konular: İlan açıklaması yazma, fiyat analizi, müşteri iletişimi, sosyal medya içerik önerileri.
- Bilmediğin konularda dürüst ol.
- Türkçe yanıt ver.
EOT;
    }

    /**
     * İlan Başlığı Üretimi
     *
     * @D3-A SAB v24.0: Shadow orchestration kapatıldı.
     * Bu method artık YalihanCortex::generateIlanTitle() üzerinden çalışır.
     * Doğrudan OllamaService / OpenAIService çağrısı yapılmaz.
     *
     * Response shape (consumer contract korundu):
     *   success, content (primary title), provider, model
     */
    public function generateListingTitle(array $ilanData, array $options = []): array
    {
        try {
            $cortexResult = $this->cortex->generateIlanTitle($ilanData, [
                'tone'     => $options['tone'] ?? 'seo',
                'provider' => $options['provider'] ?? config('ai.default_provider', 'ollama'),
            ]);

            if (!($cortexResult['success'] ?? false)) {
                return [
                    'success'  => false,
                    'content'  => '',
                    'error'    => $cortexResult['error'] ?? 'Başlık üretilemedi',
                    'provider' => $cortexResult['provider'] ?? 'cortex',
                    'model'    => $cortexResult['model'] ?? 'unknown',
                ];
            }

            // Cortex titles shape: [['text' => '...', 'poi_badges' => [...]], ...]
            // Consumer (GenerateIlanTitleAction::formatOutput) $result['content'] bekliyor:
            // Her satır bir başlık alternatifi → newline ile birleştir
            $titles = $cortexResult['titles'] ?? [];
            $lines  = array_map(fn($t) => is_array($t) ? ($t['text'] ?? '') : (string) $t, $titles);
            $content = implode("\n", array_filter($lines));

            return [
                'success'  => !empty($content),
                'content'  => $content,
                'provider' => $cortexResult['provider'] ?? 'cortex',
                'model'    => $cortexResult['model'] ?? config('ai.ollama_model', 'ollama'),
            ];
        } catch (\Exception $e) {
            return [
                'success'  => false,
                'content'  => '',
                'error'    => $e->getMessage(),
                'provider' => 'cortex',
            ];
        }
    }

    /**
     * İlan Açıklaması Üretimi
     *
     * @D3-A SAB v24.0: Shadow orchestration kapatıldı.
     * Bu method artık YalihanCortex::generateIlanDescription() üzerinden çalışır.
     * Doğrudan OllamaService / OpenAIService çağrısı yapılmaz.
     *
     * Response shape (consumer contract korundu):
     *   success, content (description text), provider, model
     */
    public function generateListingDescription(array $ilanData, array $options = []): array
    {
        try {
            $cortexResult = $this->cortex->generateIlanDescription($ilanData, [
                'tone'       => $options['tone'] ?? 'seo',
                'provider'   => $options['provider'] ?? config('ai.default_provider', 'ollama'),
                'max_tokens' => $options['max_tokens'] ?? 500,
            ]);

            if (!($cortexResult['success'] ?? false)) {
                return [
                    'success'  => false,
                    'content'  => '',
                    'error'    => $cortexResult['error'] ?? 'Açıklama üretilemedi',
                    'provider' => $cortexResult['provider'] ?? 'cortex',
                    'model'    => $cortexResult['model'] ?? 'unknown',
                ];
            }

            // Cortex description shape: description (string)
            // Consumer (CopilotListingGenerator) $result['content'] bekliyor
            $content = $cortexResult['description'] ?? '';

            return [
                'success'  => !empty($content),
                'content'  => $content,
                'provider' => $cortexResult['provider'] ?? 'cortex',
                'model'    => $cortexResult['model'] ?? config('ai.ollama_model', 'ollama'),
            ];
        } catch (\Exception $e) {
            return [
                'success'  => false,
                'content'  => '',
                'error'    => $e->getMessage(),
                'provider' => 'cortex',
            ];
        }
    }

    /**
     * Generic Content Generation (Private Helper)
     *
     * @D3-B QUARANTINE: Bu helper sadece chat() path'i tarafından kullanılıyor.
     * generateListingTitle / generateListingDescription artık buraya gelmiyor.
     * chat() bu turda dokunulmadı — D3-B backlog.
     */
    protected function generateContent(string $prompt, array $options): array
    {
        $provider = $options['provider'] ?? config('ai.default_provider', 'ollama');
        $model = $options['model'] ?? ($provider === 'ollama' ? config('ai.ollama_model', 'gemma2:2b') : config('ai.openai_model', 'gpt-3.5-turbo'));

        // 🛡️ Phase 23: Budget Guard
        $budget = $this->costGuard->checkBudget($provider);
        if (!$budget['allowed']) {
            return [
                'success' => false,
                'error' => 'AI bütçe sınırı aşıldı: ' . $budget['reason'],
                'provider' => $provider
            ];
        }

        try {
            if ($provider === 'ollama') {
                $response = $this->ollama->generateCompletion($prompt, $options['max_tokens'] ?? 200);

                if (isset($response['error']) && $response['error'] === true) {
                    throw new \Exception($response['message'] ?? 'Ollama error');
                }

                $content = $response['response'] ?? '';
            } else {
                $messages = [
                    ['role' => 'system', 'content' => 'Sen profesyonel bir emlak asistanısın.'],
                    ['role' => 'user', 'content' => $prompt]
                ];
                $response = $this->openai->chat($messages, $model);
                $content = $response['content'] ?? '';
            }

            return [
                'success' => !empty($content),
                'content' => $content,
                'provider' => $provider,
                'model' => $model
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'provider' => $provider
            ];
        }
    }

    /**
     * Prompt Builder: Title
     */
    protected function buildTitlePrompt(array $data): string
    {
        $kategori = $data['kategori'] ?? 'Gayrimenkul';
        $lokasyon = $data['lokasyon'] ?? '';
        $yayin_tipi_val = $data['yayin_tipi_id'] ?? $data['yayin_tipi'] ?? 'Satılık';
        $fiyat = $data['fiyat'] ?? '';
        $tone = $data['tone'] ?? 'seo';

        $ozelliklerText = !empty($data['features']) ? "\nÖzellikler: {$data['features']}" : "";

        return "Sen bir emlak uzmanısın. Aşağıdaki bilgilere göre {$tone} tonunda 3 farklı ilan başlığı oluştur.

        Kategori: {$kategori}
        Yayın Tipi: {$yayin_tipi_val}
        Lokasyon: {$lokasyon}
        Fiyat: {$fiyat}{$ozelliklerText}
        Ton: {$tone}

        Kurallar:
        - Her başlık 60-80 karakter arası
        - Lokasyon mutlaka geçmeli
        - SEO uyumlu anahtar kelimeler
        - Sadece başlıkları yaz, numaralama yapma
        - Emoji kullanma

        Başlıklar:";
    }

    /**
     * Prompt Builder: Description
     */
    protected function buildDescriptionPrompt(array $data): string
    {
        $kategori = $data['kategori'] ?? 'Gayrimenkul';
        $yayin_tipi_val = $data['yayin_tipi_id'] ?? $data['yayin_tipi'] ?? 'Satılık';
        $lokasyon = $data['lokasyon'] ?? '';
        $fiyat = $data['fiyat'] ?? '';
        $metrekare = $data['metrekare'] ?? '';
        $oda_sayisi = $data['oda_sayisi'] ?? '';
        $tone = $data['tone'] ?? 'seo';

        $prompt = "Sen profesyonel bir emlak danışmanısın. Aşağıdaki özellikte profesyonel ilan açıklaması yaz.\n\n";
        $prompt .= "Kategori: {$kategori}\n";
        $prompt .= "Yayın Tipi: {$yayin_tipi_val}\n";
        $prompt .= "Lokasyon: {$lokasyon}\n";
        $prompt .= "Fiyat: {$fiyat}\n";
        $prompt .= "Metrekare: {$metrekare} m²\n";
        $prompt .= "Oda Sayısı: {$oda_sayisi}\n";
        $prompt .= "Ton: {$tone}\n";

        if (!empty($data['features'])) {
            $prompt .= "Özellikler: {$data['features']}\n";
        }

        $prompt .= "\nKurallar:\n";
        $prompt .= "- 200-250 kelime\n";
        $prompt .= "- 3 paragraf (Giriş, Detaylar, Kapanış)\n";
        $prompt .= "- Türkçe dilbilgisi kurallarına uygun\n";
        $prompt .= "- SEO uyumlu ve satış odaklı\n\n";
        $prompt .= "Açıklama:";

        return $prompt;
    }
}
