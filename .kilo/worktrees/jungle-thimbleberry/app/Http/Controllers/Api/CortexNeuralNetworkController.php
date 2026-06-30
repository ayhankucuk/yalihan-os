<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Intelligence\CortexNeuralNetworkService;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Cortex Neural Network API Controller
 * Context7: Cortex Sinir Ağı API Endpoint'leri
 */
class CortexNeuralNetworkController extends Controller
{
    public function __construct(
        private CortexNeuralNetworkService $neuralNetwork
    ) {}

    /**
     * Modüller arası etkileşimi kaydet
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function recordInteraction(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'source_module' => 'required|string',
                'target_module' => 'required|string',
                'connection_type' => 'required|string',
                'success' => 'boolean',
                'performance_score' => 'nullable|numeric|min:0|max:100',
                'context' => 'nullable|array',
            ]);

            $connection = $this->neuralNetwork->recordInteraction(
                $validated['source_module'],
                $validated['target_module'],
                $validated['connection_type'],
                $validated['success'] ?? true,
                $validated['performance_score'] ?? null,
                $validated['context'] ?? []
            );

            return ResponseService::success($connection, 'Etkileşim başarıyla kaydedildi.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Etkileşim kaydedilemedi', $e);
        }
    }

    /**
     * Modüller arası bağlantı önerileri
     *
     * @param string $module
     * @return JsonResponse
     */
    public function suggestConnections(string $module): JsonResponse
    {
        try {
            $result = $this->neuralNetwork->suggestConnections($module);

            return ResponseService::success($result, 'Bağlantı önerileri başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Bağlantı önerileri oluşturulamadı', $e);
        }
    }

    /**
     * Modül ağı görselleştirme verisi
     *
     * @return JsonResponse
     */
    public function getNetworkGraph(): JsonResponse
    {
        try {
            $result = $this->neuralNetwork->getNetworkGraph();

            return ResponseService::success($result, 'Ağ grafiği başarıyla oluşturuldu.');
        } catch (\Exception $e) {
            return ResponseService::serverError('Ağ grafiği oluşturulamadı', $e);
        }
    }
}

