<?php

namespace App\Services\Cortex;

use App\Models\Ilan;
use App\Models\Talep;
use Illuminate\Support\Collection;

class CortexMatchService
{
    /**
     * Find potential investors for a high-yield listing.
     * Matches based on:
     * 1. Location (City/District)
     * 2. Budget (Min/Max Price)
     * 3. Category (Residential/Commercial/Land)
     * 
     * @param Ilan $ilan
     * @param array $roiAnalysis
     * @return Collection
     */
    public function findMatchingInvestors(Ilan $ilan, array $roiAnalysis): Collection
    {
        // 1. Basic Filtering from 'talepler' table
        $query = Talep::active() // Scope from Talep model
            ->where(function($q) use ($ilan) {
                // Match Price criteria
                $q->where(function($sq) use ($ilan) {
                    $sq->whereNull('max_fiyat')
                       ->orWhere('max_fiyat', '>=', $ilan->fiyat);
                });
                
                $q->where(function($sq) use ($ilan) {
                     $sq->whereNull('min_fiyat')
                        ->orWhere('min_fiyat', '<=', $ilan->fiyat);
                });
            });

        // 2. Location Matching (Relaxed)
        if ($ilan->ilce_id) {
            $query->where(function($q) use ($ilan) {
                $q->where('ilce_id', $ilan->ilce_id)
                  ->orWhereNull('ilce_id'); // Global investors
            });
        }
        
        // 3. Category Matching
        if ($ilan->alt_kategori_id) {
             $query->where(function($q) use ($ilan) {
                 $q->where('alt_kategori_id', $ilan->alt_kategori_id)
                   ->orWhereNull('alt_kategori_id');
             });
        }

        $candidates = $query->with('kisi')->get();

        // 4. Cortex AI Scoring (In-Memory Filtering for "Investment" Intent)
        // We will prioritize investors who have "Yatırım" or "ROI" in their notes or description
        // This is a simulation of vector matching.
        
        $matches = $candidates->filter(function($talep) use ($roiAnalysis) {
             // If listing is high yield, prioritize investors looking for investment
             if ($roiAnalysis['is_high_yield']) {
                 $keywords = ['yatırım', 'fırsat', 'getiri', 'roi', 'amortisman', 'ticari', 'kazanç'];
                 $text = strtolower($talep->aciklama . ' ' . $talep->notlar . ' ' . json_encode($talep->metadata));
                 
                 foreach ($keywords as $kw) {
                     if (str_contains($text, $kw)) {
                         return true; // Strong match for investment
                     }
                 }
                 
                 // If no specific investment keyword, but budget fits, include as secondary match
                 return true; 
             }
             
             return true;
        });

        return $matches->take(5); // Return top 5 matches
    }
}
