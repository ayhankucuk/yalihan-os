<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Helpers\CacheHelper;

/**
 * Konum servisi - İl/İlçe/Mahalle yönetimi
 * 
 * Context7: Turkish geography structure (il → ilce → mahalle)
 * Used by: Admin forms, API endpoints, filtering
 */
class LocationService
{
    /**
     * Tüm illeri getir
     */
    public function getIller(): Collection
    {
        return CacheHelper::remember(
            'location',
            'iller',
            'long',
            function () {
                return DB::table('iller')
                    ->where('aktiflik_durumu', true)
                    ->orderBy('adi', 'asc') // context7-ignore
                    ->get(['id', 'adi', 'plaka_kodu']);
            }
        );
    }

    /**
     * Belirli bir ile ait ilçeleri getir
     */
    public function getIlceler(int $ilId): Collection
    {
        return CacheHelper::remember(
            'location',
            "ilceler_{$ilId}",
            'long',
            function () use ($ilId) {
                return DB::table('ilceler')
                    ->where('il_id', $ilId)
                    ->where('aktiflik_durumu', true)
                    ->orderBy('adi', 'asc') // context7-ignore
                    ->get(['id', 'il_id', 'adi']);
            }
        );
    }

    /**
     * Belirli bir ilçeye ait mahalleleri getir
     */
    public function getMahalleler(int $ilceId): Collection
    {
        return CacheHelper::remember(
            'location',
            "mahalleler_{$ilceId}",
            'long',
            function () use ($ilceId) {
                return DB::table('mahalleler')
                    ->where('ilce_id', $ilceId)
                    ->where('aktiflik_durumu', true)
                    ->orderBy('adi', 'asc') // context7-ignore
                    ->get(['id', 'ilce_id', 'adi', 'lat', 'lng']);
            }
        );
    }

    /**
     * İl adından ID bul
     */
    public function getIlIdByName(string $ilAdi): ?int
    {
        $il = DB::table('iller')
            ->where('adi', 'LIKE', "%{$ilAdi}%")
            ->first(['id']);

        return $il?->id;
    }

    /**
     * İlçe adından ID bul
     */
    public function getIlceIdByName(int $ilId, string $ilceAdi): ?int
    {
        $ilce = DB::table('ilceler')
            ->where('il_id', $ilId)
            ->where('adi', 'LIKE', "%{$ilceAdi}%")
            ->first(['id']);

        return $ilce?->id;
    }

    /**
     * Mahalle adından ID bul
     */
    public function getMahalleIdByName(int $ilceId, string $mahalleAdi): ?int
    {
        $mahalle = DB::table('mahalleler')
            ->where('ilce_id', $ilceId)
            ->where('adi', 'LIKE', "%{$mahalleAdi}%")
            ->first(['id']);

        return $mahalle?->id;
    }
}
