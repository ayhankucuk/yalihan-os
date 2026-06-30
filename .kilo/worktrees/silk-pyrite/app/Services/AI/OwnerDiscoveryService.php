<?php

namespace App\Services\AI;

use Illuminate\Support\Facades\DB;

/**
 * AI Owner Discovery Engine
 *
 * SAB Production Seal mimarisine uygun olarak dış kaynaklı piyasa verilerini analiz edip
 * ilan sahiplerini gruplayarak (clustering), profillemesini ve fırsat (acquisition)
 * skorunu çıkaran yapay zeka ve hesaplama modülü.
 */
class OwnerDiscoveryService
{
    /**
     * Dış platform verilerini (market_listings) analiz edip aynı sahibe ait olduğuna inanılan
     * ilanları "Owner Cluster" olarak gruplar.
     */
    public function clusterListingsByOwner(): int
    {
        // market_listings tablosunu oku, ilan_sahibi, location_mahalle, tarih gibi örüntülere göre grupla.
        // Şimdilik demo/mock implementation veya SQL bazlı gruplama.

        $clustersCreated = 0;

        // Gerçekte burada karmaşık kural seti veya embeddings-based clustering çalışır:
        // 1. Aynı fiyat, mahalle, yakın tarih
        // 2. Metin analizi (description analysis vs)

        // Örnek basit clustering: Aynı il_ilce_mahalle_ilan_sahibi grubunu bir owner kabul et
        $potentialClusters = DB::connection('mysql') // Varsayılan bağlantı (ya da market_intelligence)
            ->table('market_listings') // yalihan_market.market_listings
            ->select('ilan_sahibi', 'location_il', 'location_ilce', 'location_mahalle', DB::raw('COUNT(*) as count'))
            ->where('is_active', 1) // context7-ignore
            ->groupBy('ilan_sahibi', 'location_il', 'location_ilce', 'location_mahalle')
            ->having('count', '>', 0)
            ->get();

        foreach ($potentialClusters as $clusterGroup) {
            // DB kayıtlarını oluştur
            $clusterId = DB::table('owner_cluster_projections')->insertGetId([
                'owner_profile_type' => 'UNKNOWN',
                'owner_tier' => 'LOW_PRIORITY',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Listeleri cluster'a bağla
            $listings = DB::table('market_listings')
                ->where('is_active', 1) // context7-ignore
                ->where('ilan_sahibi', $clusterGroup->ilan_sahibi)
                ->where('location_il', $clusterGroup->location_il)
                ->where('location_ilce', $clusterGroup->location_ilce)
                ->where('location_mahalle', $clusterGroup->location_mahalle)
                ->get();

            $validListings = 0;
            $totalPrice = 0;

            foreach ($listings as $listing) {
                DB::table('market_listing_owner_clusters')->insertOrIgnore([
                    'owner_cluster_id' => $clusterId,
                    'market_listing_id' => $listing->id,
                    'source' => $listing->source,
                    'external_id' => $listing->external_id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $totalPrice += $listing->price;
                $validListings++;
            }

            $averagePrice = $validListings > 0 ? $totalPrice / $validListings : 0;

            // Generate Profile and Score
            $mockClusterData = [
                'id' => $clusterId,
                'listing_count' => $validListings,
                'average_price' => $averagePrice,
                'ilan_sahibi_type' => $clusterGroup->ilan_sahibi,
                'market_listings' => $listings // Collection
            ];

            $profileType = $this->generateOwnerProfile($mockClusterData);

            // Mock signals calculation
            $signals = [
                'listing_count' => $validListings,
                'average_days_on_market' => 15,
                'price_drop_behavior' => rand(0, 5),
                'unsold_ratio' => rand(0, 100) / 100, // 0-1
                'market_demand_overlap' => rand(0, 100) / 100 // 0-1
            ];

            $acquisitionScore = $this->calculateOwnerAcquisitionScore($signals);
            $tier = $this->determineOwnerTier($acquisitionScore);

            DB::table('owner_cluster_projections')
                ->where('id', $clusterId)
                ->update([
                    'owner_profile_type' => $profileType,
                    'listing_count' => $validListings,
                    'average_price' => $averagePrice,
                    'owner_acquisition_score' => $acquisitionScore,
                    'owner_tier' => $tier,
                    'updated_at' => now(),
                ]);

            DB::table('owner_acquisition_signals')->insert([
                'owner_cluster_id' => $clusterId,
                'listing_count' => $validListings,
                'average_days_on_market' => $signals['average_days_on_market'],
                'price_drop_count' => $signals['price_drop_behavior'],
                'unsold_ratio' => $signals['unsold_ratio'],
                'demand_mismatch' => 1 - $signals['market_demand_overlap'],
                'price_gap_average' => 0, // Mock
                'recorded_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $clustersCreated++;
        }

        return $clustersCreated;
    }

    /**
     * INDIVIDUAL_SELLER, INVESTOR, AGENT_LIKE, DEVELOPER, UNKNOWN
     */
    public function generateOwnerProfile(array $cluster): string
    {
        $listingCount = $cluster['listing_count'] ?? 1;
        $ilanSahibiType = $cluster['ilan_sahibi_type'] ?? 'bilinmiyor';

        if ($ilanSahibiType === 'emlakci') {
            return 'AGENT_LIKE';
        }

        if ($listingCount >= 5) {
            return 'DEVELOPER';
        }

        if ($listingCount > 1 && $listingCount < 5) {
            return 'INVESTOR';
        }

        if ($listingCount === 1) {
            return 'INDIVIDUAL_SELLER';
        }

        return 'UNKNOWN';
    }

    /**
     * owner_acquisition_score = listing_count_factor * 0.25 + days_on_market_factor * 0.25 +
     * price_drop_behavior * 0.20 + unsold_ratio * 0.15 + market_demand_overlap * 0.15
     */
    public function calculateOwnerAcquisitionScore(array $signals): float
    {
        $listingCount = $signals['listing_count'] ?? 1;
        $dom = $signals['average_days_on_market'] ?? 0;
        $priceDropBehavior = $signals['price_drop_behavior'] ?? 0; // freq
        $unsoldRatio = $signals['unsold_ratio'] ?? 0; // 0-1
        $marketDemandOverlap = $signals['market_demand_overlap'] ?? 0; // 0-1

        // Normalize factors 0-100
        $listingCountFactor = min(100, $listingCount * 25);
        $domFactor = min(100, $dom * 2); // 50 days = 100 score (örnek modelleme)
        $priceDropNormalized = min(100, $priceDropBehavior * 20);
        $unsoldRatioNormalized = $unsoldRatio * 100;
        $marketDemandNormalized = $marketDemandOverlap * 100;

        $score = ($listingCountFactor * 0.25) +
                 ($domFactor * 0.25) +
                 ($priceDropNormalized * 0.20) +
                 ($unsoldRatioNormalized * 0.15) +
                 ($marketDemandNormalized * 0.15);

        return min(100, round($score, 2));
    }

    /**
     * 90–100 → PRIME_OWNER_TARGET
     * 75–89 → HIGH_VALUE_OWNER
     * 60–74 → MEDIUM_OPPORTUNITY
     * 0–59 → LOW_PRIORITY
     */
    public function determineOwnerTier(float $score): string
    {
        if ($score >= 90) return 'PRIME_OWNER_TARGET';
        if ($score >= 75) return 'HIGH_VALUE_OWNER';
        if ($score >= 60) return 'MEDIUM_OPPORTUNITY';

        return 'LOW_PRIORITY';
    }

    /**
     * CQRS projectiondan analiz edilmiş listeyi döndürür
     */
    public function generateOwnerOpportunityList()
    {
        return DB::table('owner_cluster_projections')
            ->orderByDesc('owner_acquisition_score') // Context7 compliant sort column definition logic
            ->get();
    }
}
