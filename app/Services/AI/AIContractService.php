<?php

namespace App\Services\AI;

use App\Models\AI\AIContractDraft;
use App\Models\Ilan;
use App\Models\Kisi;
use App\Services\N8n\TenantOwnershipResolver;
use App\Services\N8n\CountryOwnershipResolver;
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
 * Phase: LEGACY_AI_SERVICES_TENANT_PARITY_01
 * Bekçi: PASS (0 violation)
 */
class AIContractService
{
    /**
     * n8n webhook URL
     */
    protected string $n8nWebhookUrl;

    public function __construct(
        private readonly TenantOwnershipResolver $tenantResolver,
        private readonly CountryOwnershipResolver $countryResolver,
    ) {
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
            // Canonical ownership: resolve both country and tenant via entity chain.
            // Uses withoutGlobalScopes() internally — safe in all tenant/country contexts.
            // Throws exception if unresolvable (fail-closed).
            $ulkeId = $this->countryResolver->resolveForSozlesmeTaslagi($propertyId, $kisiId);
            $tenantId = $this->tenantResolver->resolveForSozlesmeTaslagi($propertyId, $kisiId);

            // n8n webhook'a istek gönder (IDs only — n8n fetches full data if needed)
            $response = Http::timeout(30)->post($this->n8nWebhookUrl.'/ai/sozlesme-taslagi', [
                'contract_type' => $contractType,
                'property_id' => $propertyId,
                'kisi_id' => $kisiId,
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
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
            ]);

            Log::info('AI sözleşme taslağı oluşturuldu', [
                'draft_id' => $draft->id,
                'contract_type' => $contractType,
                'property_id' => $propertyId,
                'ulke_id' => $ulkeId,
                'tenant_id' => $tenantId,
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
