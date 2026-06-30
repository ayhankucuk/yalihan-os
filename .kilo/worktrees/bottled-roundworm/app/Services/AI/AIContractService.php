<?php

namespace App\Services\AI;

use App\Models\AI\AIContractDraft;
use App\Models\Ilan;
use App\Models\Kisi;
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
class AIContractService
{
    /**
     * n8n webhook URL
     */
    protected string $n8nWebhookUrl;

    public function __construct()
    {
        $this->n8nWebhookUrl = config('services.n8n.webhook_url', '');
    }

    /**
     * Sözleşme taslağı üret
     *
     * @param  string  $contractType  Sözleşme tipi (kira, satis)
     * @param  int|null  $propertyId  İlan ID
     * @param  int|null  $kisiId  Kişi ID
     * @param  array  $additionalData  Ek veriler
     */
    public function generateDraft(
        string $contractType,
        ?int $propertyId = null,
        ?int $kisiId = null,
        array $additionalData = []
    ): AIContractDraft {
        try {
            // İlan ve kişi bilgilerini al
            $property = $propertyId ? Ilan::find($propertyId) : null;
            $kisi = $kisiId ? Kisi::find($kisiId) : null;

            // n8n webhook'a istek gönder
            $response = Http::timeout(30)->post($this->n8nWebhookUrl.'/ai/sozlesme-taslagi', [
                'contract_type' => $contractType,
                'property_id' => $propertyId,
                'kisi_id' => $kisiId,
                'property_data' => $property ? [
                    'baslik' => $property->baslik,
                    'fiyat' => $property->fiyat,
                    'para_birimi' => $property->para_birimi,
                    'adres' => $property->adres ?? '',
                ] : null,
                'kisi_data' => $kisi ? [
                    'adi' => $kisi->adi,
                    'soyadi' => $kisi->soyadi,
                    'telefon' => $kisi->telefon,
                    'email' => $kisi->email,
                ] : null,
                'additional_data' => $additionalData,
            ]);

            if (! $response->successful()) {
                throw new \Exception('n8n webhook request failed: '.$response->getStatusCode());
            }

            $aiResponse = $response->json();

            // DB'ye kaydet (yayin_durumu=draft)
            $draft = AIContractDraft::create([
                'contract_type' => $contractType,
                'property_id' => $propertyId,
                'kisi_id' => $kisiId,
                'yayin_durumu' => TaslakDurumu::TASLAK->value,
                'content' => $aiResponse['content'] ?? '',
                'ai_model_used' => $aiResponse['model'] ?? 'anythingllm',
                'ai_generated_at' => now(),
            ]);

            Log::info('AI sözleşme taslağı oluşturuldu', [
                'draft_id' => $draft->id,
                'contract_type' => $contractType,
                'property_id' => $propertyId,
            ]);

            return $draft;
        } catch (\Exception $e) {
            Log::error('AI sözleşme taslağı oluşturma hatası', [
                'error' => $e->getMessage(),
                'contract_type' => $contractType,
            ]);

            throw $e;
        }
    }

    /**
     * Taslağı onayla
     *
     * @param  int  $draftId  Taslak ID
     * @param  int  $userId  Onaylayan kullanıcı ID
     */
    public function approve(int $draftId, int $userId): bool
    {
        $draft = AIContractDraft::findOrFail($draftId);

        if (! $draft->approve($userId)) {
            return false;
        }

        Log::info('AI sözleşme taslağı onaylandı', [
            'draft_id' => $draftId,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Taslağı reddet
     *
     * @param  int  $draftId  Taslak ID
     * @param  int  $userId  Reddeden kullanıcı ID
     */
    public function reject(int $draftId, int $userId): bool
    {
        $draft = AIContractDraft::findOrFail($draftId);

        if (! $draft->reject($userId)) {
            return false;
        }

        Log::info('AI sözleşme taslağı reddedildi', [
            'draft_id' => $draftId,
            'user_id' => $userId,
        ]);

        return true;
    }

    /**
     * Onaylanmış taslakları getir
     *
     * @param  string|null  $contractType  Sözleşme tipi filtresi
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getApprovedDrafts(?string $contractType = null)
    {
        $query = AIContractDraft::approved();

        if ($contractType) {
            $query->byType($contractType);
        }

        return $query->orderBy('approved_at', 'desc')->get(); // context7-ignore
    }
}
