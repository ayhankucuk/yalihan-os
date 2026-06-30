<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\AIService;
use App\Services\Logging\LogService;
use App\Services\QRCodeService;
use App\Services\Response\ResponseService;
use App\Traits\ValidatesApiRequests;
use Illuminate\Http\Request;

/**
 * QR Code API Controller
 *
 * Context7: QR code generation API endpoints
 * - Generate QR codes for listings
 * - AI-powered QR code suggestions
 */
class QRCodeController extends Controller
{
    use ValidatesApiRequests;

    protected QRCodeService $qrCodeService;

    protected AIService $aiService;

    public function __construct(QRCodeService $qrCodeService, AIService $aiService)
    {
        $this->qrCodeService = $qrCodeService;
        $this->aiService = $aiService;
    }

    /**
     * Generate QR code for a listing
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateForListing(Request $request, int $ilanId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait (optional validation)
        $validated = $this->validateRequestFlexible($request, [
            'size' => 'sometimes|integer|min:100|max:1000',
            'format' => 'sometimes|in:png,jpg,svg',
            'foreground' => 'sometimes|array|size:3',
            'background' => 'sometimes|array|size:3',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($ilanId);

            $options = [
                'size' => $request->input('size', 300),
                'format' => $request->input('format', 'png'),
                'foreground' => $request->input('foreground', [0, 0, 0]),
                'background' => $request->input('background', [255, 255, 255]),
            ];

            $qrData = $this->qrCodeService->generateForListing($ilanId, $options);

            return ResponseService::success([
                'qr_code' => $qrData,
                'ilan' => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'url' => route('ilanlar.show', $ilan->id),
                ],
            ], 'QR kod başarıyla oluşturuldu');
        } catch (\Exception $e) {
            LogService::error('QR code generation API failed', ['ilan_id' => $ilanId], $e);

            return ResponseService::serverError('QR kod oluşturulurken hata oluştu', $e);
        }
    }

    /**
     * Generate QR code for WhatsApp sharing
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateForWhatsApp(Request $request, int $ilanId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait (optional validation)
        $validated = $this->validateRequestFlexible($request, [
            'phone' => 'sometimes|string|max:20',
        ]);

        if ($validated instanceof \Illuminate\Http\JsonResponse) {
            return $validated;
        }

        try {
            $ilan = Ilan::findOrFail($ilanId);
            $phoneNumber = $request->input('phone', config('app.whatsapp_number'));

            $qrData = $this->qrCodeService->generateForWhatsApp($ilanId, $phoneNumber);

            return ResponseService::success([
                'qr_code' => $qrData,
                'whatsapp_url' => "https://wa.me/{$phoneNumber}",
                'ilan' => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                ],
            ], 'WhatsApp QR kod başarıyla oluşturuldu');
        } catch (\Exception $e) {
            LogService::error('WhatsApp QR code generation failed', ['ilan_id' => $ilanId], $e);

            return ResponseService::serverError('WhatsApp QR kod oluşturulurken hata oluştu', $e);
        }
    }

    /**
     * Get AI-powered QR code suggestions
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAISuggestions(Request $request, int $ilanId)
    {
        // ✅ REFACTORED: Using ValidatesApiRequests trait (no validation needed, but consistent pattern)
        try {
            $ilan = Ilan::findOrFail($ilanId);

            // AI service ile QR code kullanım önerileri
            $context = [
                'ilan' => [
                    'id' => $ilan->id,
                    'baslik' => $ilan->baslik,
                    'kategori' => $ilan->kategori->name ?? null,
                    'fiyat' => $ilan->fiyat,
                    'lokasyon' => $ilan->il->il_adi ?? null,
                ],
                'type' => 'qr_code_suggestions', // context7-ignore
            ];

            $suggestions = $this->aiService->suggest($context, 'qr_code');

            return ResponseService::success([
                'suggestions' => $suggestions,
                'usage_tips' => [
                    'Print için QR kod ekleyin',
                    'Sosyal medya paylaşımları için kullanın',
                    'Fiziksel görüntülemelerde QR kod gösterin',
                    'Mobil kullanıcılar için hızlı erişim sağlayın',
                ],
            ], 'AI önerileri başarıyla alındı');
        } catch (\Exception $e) {
            LogService::error('AI QR suggestions failed', ['ilan_id' => $ilanId], $e);

            return ResponseService::serverError('AI önerileri alınırken hata oluştu', $e);
        }
    }

    /**
     * Get QR code statistics
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStatistics()
    {
        try {
            $stats = $this->qrCodeService->getStatistics();

            return ResponseService::success($stats, 'QR kod istatistikleri başarıyla alındı');
        } catch (\Exception $e) {
            LogService::error('QR code statistics failed', [], $e);

            return ResponseService::serverError('İstatistikler alınırken hata oluştu', $e);
        }
    }
}
