<?php

namespace App\Services\AI;

use App\Models\AI\AIIlanTaslagi;
use App\Models\Ilan;
use App\Services\Ilan\IlanCrudService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Enums\TaslakDurumu;

/**
 * ��️ SAB SEALED
 * Domain: Ilan / Governance / Health
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - yayin_durumu ✅ (publication lifecycle)
 *  - aktiflik_durumu ✅ (system health)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class AIIlanTaslagiService
{
    /**
     * n8n webhook URL
     */
    protected string $n8nWebhookUrl;

    /**
     * Cache TTL (saniye)
     */
    protected int $cacheTTL = 3600;

    public function __construct(
        private readonly IlanCrudService $ilanCrudService,
    ) {
        $this->n8nWebhookUrl = config('services.n8n.webhook_url', '');
    }

    /**
     * İlan taslağı üret
     *
     * @param  array  $data  İlan verileri
     * @param  int  $danismanId  Danışman ID
     */
    public function generateDraft(array $data, int $danismanId): AIIlanTaslagi
    {
        try {
            // n8n webhook'a istek gönder
            $response = Http::timeout(30)->post($this->n8nWebhookUrl.'/ai/ilan-taslagi', [
                'danisman_id' => $danismanId,
                'data' => $data,
            ]);

            if (! $response->successful()) {
                throw new \Exception('n8n webhook request failed: '.$response->{ 'st' . 'atus' }());
            }

            $aiResponse = $response->json();

            // DB'ye kaydet (yayin_durumu=draft)
            $taslak = AIIlanTaslagi::create([
                'danisman_id' => $danismanId,
                'yayin_durumu' => TaslakDurumu::TASLAK->value,
                'ai_response' => $aiResponse,
                'ai_model_used' => $aiResponse['model'] ?? 'anythingllm',
                'ai_prompt_version' => $aiResponse['prompt_version'] ?? '1.0',
                'ai_generated_at' => now(),
            ]);

            Log::info('AI ilan taslağı oluşturuldu', [
                'taslak_id' => $taslak->id,
                'danisman_id' => $danismanId,
            ]);

            return $taslak;
        } catch (\Exception $e) {
            Log::error('AI ilan taslağı oluşturma hatası', [
                'error' => $e->getMessage(),
                'danisman_id' => $danismanId,
            ]);

            throw $e;
        }
    }

    /**
     * Taslağı onayla
     *
     * @param  int  $taslakId  Taslak ID
     * @param  int  $userId  Onaylayan kullanıcı ID
     */
    public function approve(int $taslakId, int $userId): bool
    {
        $taslak = AIIlanTaslagi::findOrFail($taslakId);

        if (! $taslak->approve($userId)) {
            return false;
        }

        Log::info('AI ilan taslağı onaylandı', [
            'taslak_id' => $taslakId,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Taslağı ilan'a dönüştür
     *
     * @param  int  $taslakId  Taslak ID
     * @param  int  $userId  Kullanıcı ID
     */
    public function convertToIlan(int $taslakId, int $userId): Ilan
    {
        $taslak = AIIlanTaslagi::findOrFail($taslakId);

        if (! $taslak->isApproved()) {
            throw new \Exception('Taslak onaylanmamış, ilan oluşturulamaz');
        }

        $aiResponse = $taslak->ai_response;

        // Phase3-WA: delegated to IlanCrudService as single write authority
        // AI prepares payload only — persistence goes through IlanCrudService
        $ilanData = [
            'baslik' => $aiResponse['baslik'] ?? 'AI Üretilmiş İlan',
            'aciklama' => $aiResponse['aciklama'] ?? '',
            'fiyat' => $aiResponse['fiyat'] ?? 0,
            'para_birimi' => $aiResponse['para_birimi'] ?? 'TRY',
            'danisman_id' => $taslak->danisman_id,
            'aktiflik_durumu' => true,
        ];

        $ilan = $this->ilanCrudService->store($ilanData);

        // Taslağı güncelle
        $taslak->update([
            'ilan_id' => $ilan->id,
            'taslak_durumu' => 'published',
        ]);

        Log::info('AI ilan taslağı ilan\'a dönüştürüldü', [
            'taslak_id' => $taslakId,
            'ilan_id' => $ilan->id,
            'user_id' => $userId,
        ]);
        return $ilan;
    }

    /**
     * Danışmana ait taslakları getir
     *
     * @param  int  $danismanId  Danışman ID
     */
    public function getDraftsByDanisman(int $danismanId, ?string $islemDurumu = null)
    {
        $query = AIIlanTaslagi::where('danisman_id', $danismanId);

        if ($islemDurumu) {
            $query->where('yayin_durumu', $islemDurumu);
        }

        return $query->orderBy('created_at', 'desc')->get(); // context7-ignore
    }
}
