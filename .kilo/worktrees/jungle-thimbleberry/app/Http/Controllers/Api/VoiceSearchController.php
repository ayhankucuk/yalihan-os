<?php

namespace App\Http\Controllers\Api;

use App\Enums\IlanDurumu;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
// ❌ REMOVED: use App\Models\Deprecated\KisiTask; (deprecated)
use App\Services\AI\AudioService;
use App\Services\AI\VoiceSearchService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;
use Exception;

/**
 * Voice Search Controller
 *
 * Context7 Standardı: C7-VOICE-SEARCH-CONTROLLER-2026-01-01
 */
class VoiceSearchController extends Controller
{
    use ValidatesApiRequests;

    protected VoiceSearchService $voiceSearchService;
    protected AudioService $audioService;

    public function __construct(VoiceSearchService $voiceSearchService, AudioService $audioService)
    {
        $this->voiceSearchService = $voiceSearchService;
        $this->audioService = $audioService;
    }

    /**
     * Voice-to-Query: Sesli komutu arama filtrelerine dönüştür ve sesli yanıt üret
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function voiceToQuery(Request $request)
    {
        $validated = $this->validateRequestWithResponse($request, [
            'text' => 'required|string|min:3|max:1000',
            'with_audio' => 'sometimes|boolean'
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $text = $validated['text'];

            // 1. Komutu parse et
            $intent = $this->voiceSearchService->parseCommand($text);

            // 2. Aktif ilan sayısını kontrol et (yayin_durumu = IlanDurumu::YAYINDA->value)
            $count = $this->getMatchCount($intent);

            // 3. Sesli yanıt üret (opsiyonel veya varsayılan)
            $audioUrl = null;
            if ($request->input('with_audio', true)) {
                $audioUrl = $this->audioService->generateSearchSummaryAudio($intent, $count);
            }

            return ResponseService::success([
                'intent' => $intent,
                'match_count' => $count,
                'audio_url' => $audioUrl,
                'redirect_url' => route('admin.ilanlar.index', $this->mapIntentToQueryParams($intent)),
                'message' => "✅ {$count} adet uygun ilan bulundu.",
            ], 'Sesli komut başarıyla işlendi');
        } catch (Exception $e) {
            return ResponseService::serverError('Sesli arama işlenirken hata oluştu', $e);
        }
    }

    /**
     * Voice-to-Task: Sesli notu mühürle
     *
     * ❌ DISABLED: KisiTask table deprecated (2026-01-29)
     * Context7 Standardı: C7-VOICE-TO-TASK-DEPRECATED-2026-01-29
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function voiceToTask(Request $request)
    {
        return ResponseService::error(
            'Sesli görev özelliği geçici olarak devre dışı (KisiTask deprecated)',
            [],
            501
        );
    }

    /**
     * Aktif ilan sayısını getir
     */
    private function getMatchCount(array $intent): int
    {
        $query = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value);

        if (!empty($intent['search_type']) && $intent['search_type'] !== 'genel') {
            // Kategori eşleştirmesi (slug üzerinden)
            $query->whereHas('kategori', function ($q) use ($intent) {
                $q->where('slug', $intent['search_type']);
            });
        }

        if (!empty($intent['location']['il'])) {
            $query->whereHas('il', function ($q) use ($intent) {
                $q->where('il_adi', 'like', '%' . $intent['location']['il'] . '%');
            });
        }

        if (!empty($intent['location']['ilce'])) {
            $query->whereHas('ilce', function ($q) use ($intent) {
                $q->where('ilce_adi', 'like', '%' . $intent['location']['ilce'] . '%');
            });
        }

        if (!empty($intent['price']['min'])) {
            $query->where('fiyat', '>=', $intent['price']['min']);
        }

        if (!empty($intent['price']['max'])) {
            $query->where('fiyat', '<=', $intent['price']['max']);
        }

        return $query->count();
    }

    /**
     * Intent verisini query parametrelerine eşle
     */
    private function mapIntentToQueryParams(array $intent): array
    {
        $params = [];

        if (!empty($intent['search_type'])) {
            $params['type'] = $intent['search_type']; // context7-ignore
        }

        if (!empty($intent['location']['il'])) {
            $params['il'] = $intent['location']['il'];
        }

        if (!empty($intent['location']['ilce'])) {
            $params['ilce'] = $intent['location']['ilce'];
        }

        if (!empty($intent['price']['min'])) {
            $params['min_price'] = $intent['price']['min'];
        }

        if (!empty($intent['price']['max'])) {
            $params['max_price'] = $intent['price']['max'];
        }

        if (!empty($intent['rooms']['min'])) {
            $params['rooms'] = $intent['rooms']['min'];
        }

        if (!empty($intent['keywords'])) {
            $params['search'] = is_array($intent['keywords']) ? implode(' ', $intent['keywords']) : $intent['keywords'];
        }

        return $params;
    }
}
