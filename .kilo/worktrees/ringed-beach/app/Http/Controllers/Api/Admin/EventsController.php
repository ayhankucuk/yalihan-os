<?php

namespace App\Http\Controllers\Api\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Admin\AdminController;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * Events API Controller
 *
 * Context7 Standard: C7-EVENTS-API-2025-12-06
 * Yalıhan Bekçi: Temiz, düzenli, merkezi yönetim
 *
 * Event definitions ve metadata için API endpoint'leri.
 */
class EventsController extends AdminController
{
    /**
     * Get all event definitions
     *
     * @return JsonResponse
     */
    public function getDefinitions(): JsonResponse
    {
        try {
            $definitions = config('events.definitions', []);
            $categories = config('events.categories', []);

            return ResponseService::success([
                'definitions' => $definitions,
                'categories' => $categories,
                'total' => count($definitions),
            ], 'Event definitions başarıyla getirildi');
        } catch (\Exception $e) {
            Log::error('EventsController::getDefinitions hatası', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return ResponseService::serverError('Event definitions yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Get event metadata by key
     *
     * @param string $eventKey
     * @return JsonResponse
     */
    public function getMetadata(string $eventKey): JsonResponse
    {
        try {
            $definition = config("events.definitions.{$eventKey}", null);

            if (!$definition) {
                return ResponseService::error('Event tanımı bulunamadı', 404);
            }

            return ResponseService::success([
                'event_key' => $eventKey,
                'metadata' => $definition,
            ], 'Event metadata başarıyla getirildi');
        } catch (\Exception $e) {
            Log::error('EventsController::getMetadata hatası', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return ResponseService::serverError('Event metadata yüklenirken hata oluştu.', $e);
        }
    }
}

