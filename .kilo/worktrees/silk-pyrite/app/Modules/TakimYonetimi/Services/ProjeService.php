<?php

namespace App\Modules\TakimYonetimi\Services;

use App\Modules\TakimYonetimi\Models\Gorev;
use App\Models\Proje;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\DB;

/**
 * ProjeService — Application Service
 *
 * SAB v4.1 Kural 1/11: Controller'dan mutation logic taşıması
 */
class ProjeService
{
    use GuardsAgentWrites;

    /**
     * Proje oluştur
     */
    public function store(array $data): Proje
    {
        $this->blockAgentWrite('store');

        return DB::transaction(function () use ($data) {
            $proje = Proje::create($data);
            return $proje;
        });
    }

    /**
     * Projeye görev ekle
     */
    public function gorevEkle(Proje $proje, array $data): Gorev
    {
        $this->blockAgentWrite('gorevEkle');

        return DB::transaction(function () use ($proje, $data) {
            $data['proje_id'] = $proje->id;
            $gorev = Gorev::create($data);
            
            // Proje progress'ini güncelle
            $proje->updateProgress();
            
            return $gorev;
        });
    }
}
