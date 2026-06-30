<?php

namespace App\Http\Livewire\Publication;

use App\Models\Talep;
use App\Services\Cortex\MatchingEngine;
use Livewire\Component;
use Illuminate\Support\Collection;

/**
 * 🛰️ MatchingResults (Phase 5: Experience Refinement)
 * Progressive Match Streaming & Neural Pulse Implementation
 */
class MatchingResults extends Component
{
    public int $talepId;
    public bool $readyToLoad = false;
    public Collection $results;
    public bool $isAiAnalysisComplete = false;

    public function mount(int $talepId)
    {
        $this->talepId = $talepId;
        $this->results = collect();
        
        // Initial Load: Saf SQL Sonuçlarını anında getir (Non-blocking)
        $this->loadInitialResults();
    }

    /**
     * Sayfa yüklendiğinde asenkron olarak AI analizini tetikler.
     */
    public function loadAiAnalysis()
    {
        $talep = Talep::findOrFail($this->talepId);
        $engine = app(MatchingEngine::class);

        // Phase 4/5 Logic: DecisionComparator üzerinden AI + SQL hibrit sonuçlarını getir
        $this->results = $engine->findMatchesForLead($talep);
        $this->isAiAnalysisComplete = true;
    }

    /**
     * İlk (Saf SQL) sonuçları yükler.
     */
    private function loadInitialResults()
    {
        $talep = Talep::findOrFail($this->talepId);
        
        // Sadece SQL motorunu taklit et (veya direkt oradan al)
        // Bu hızlı sonuçlar, AI çıkarımı sürerken kullanıcıyı oyalar.
        $this->results = \App\Models\Ilan::where('yayin_durumu', \App\Enums\IlanDurumu::YAYINDA->value)
            ->limit(3)
            ->get()
            ->map(function($ilan) {
                $ilan->is_precision_verified = false; // Henüz AI onaylamadı
                return $ilan;
            });
    }

    public function render()
    {
        return view('livewire.publication.matching-results');
    }
}
