<?php

namespace App\Http\Controllers\Api;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Response\ResponseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class SiteOzellikleriController extends Controller
{
    /**
     * Site özellikleri listesi
     */
    public function index(): JsonResponse
    {
        try {
            $ozellikler = [
                ['id' => 'guvenlik', 'name' => 'Güvenlik', 'icon' => '🛡️'],
                ['id' => 'otopark', 'name' => 'Otopark', 'icon' => '🚗'],
                ['id' => 'havuz', 'name' => 'Havuz', 'icon' => '🏊'],
                ['id' => 'spor', 'name' => 'Spor Alanı', 'icon' => '🏋️'],
                ['id' => 'cocuk_parki', 'name' => 'Çocuk Parkı', 'icon' => '🎠'],
                ['id' => 'asansor', 'name' => 'Asansör', 'icon' => '🛗'],
                ['id' => 'jenerator', 'name' => 'Jeneratör', 'icon' => '⚡'],
                ['id' => 'kamerali_guvenlik', 'name' => 'Kameralı Güvenlik', 'icon' => '📹'],
                ['id' => 'yesil_alan', 'name' => 'Yeşil Alan', 'icon' => '🌳'],
                ['id' => 'kamelya', 'name' => 'Kamelya', 'icon' => '🏡'],
                ['id' => 'gosterim_saati', 'name' => 'Gösteri Salonu', 'icon' => '🎭'],
                ['id' => 'kapi_gorevlisi', 'name' => 'Kapı Görevlisi', 'icon' => '🚪'],
            ];

            return ResponseService::success([
                'data' => $ozellikler,
            ], 'Site özellikleri başarıyla getirildi');

        } catch (\Exception $e) {
            Log::error('Site özellikleri yükleme hatası: '.$e->getMessage());

            return ResponseService::serverError('Site özellikleri yüklenirken hata oluştu.', $e);
        }
    }

    /**
     * Aktif site özellikleri
     */
    public function active(): JsonResponse
    {
        return $this->index(); // Tüm özellikler aktif
    }
}
