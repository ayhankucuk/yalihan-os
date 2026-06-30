<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use App\Models\Ilce;

class WikimapiaSearchRepository
{
    /**
     * Haversine formülü ile yakındaki mahalleleri bulur (raw SQL, testlenebilir)
     */
    public function findNearbyMahalleler(float $lat, float $lon, int $km = 5): array
    {
        // SAB uyumlu: SQL'de sadece mahalleleri çek, mesafeyi PHP'de hesapla
        $mahalleler = DB::table('mahalleler')
            ->select('id', 'mahalle_adi', 'ilce_id', 'enlem', 'boylam')
            ->whereNotNull('enlem')
            ->whereNotNull('boylam')
            ->get();

        $result = [];
        foreach ($mahalleler as $mahalle) {
            $distance = $this->haversineDistance($lat, $lon, $mahalle->enlem, $mahalle->boylam);
            $mahalle->distance = $distance;
            $result[] = $mahalle;
        }
        return $result;
    }

    /**
     * İki koordinat arası mesafeyi (km) hesaplar (Haversine)
     */
    private function haversineDistance($lat1, $lon1, $lat2, $lon2)
    {
        $earthRadius = 6371;
        $lat1 = deg2rad($lat1);
        $lon1 = deg2rad($lon1);
        $lat2 = deg2rad($lat2);
        $lon2 = deg2rad($lon2);
        $dlat = $lat2 - $lat1;
        $dlon = $lon2 - $lon1;
        $a = sin($dlat/2) * sin($dlat/2) + cos($lat1) * cos($lat2) * sin($dlon/2) * sin($dlon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    }

    /**
     * İlgili ilçe ve ili eager load ile getirir
     */
    public function findIlceWithIlById(int $ilceId)
    {
        return Ilce::with('il')->find($ilceId);
    }
}
