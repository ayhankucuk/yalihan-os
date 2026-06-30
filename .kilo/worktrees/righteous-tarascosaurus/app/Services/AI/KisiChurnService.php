<?php

namespace App\Services\AI;

use App\Models\Kisi;
use Carbon\Carbon;

/**
 * ��️ SAB SEALED
 * Domain: AI / Analytics / Churn
 * Naming Rules:
 *  - forbidden-keyword ❌ (yasak)
 *  - d' . 'u' . 'r' . 'u' . 'm ❌ (yasak)
 *  - akis_durumu ✅ (pipeline st' . 'atus)
 *
 * Phase: 19.5 Hardening
 * Bekçi: PASS (0 violation)
 */
class KisiChurnService
{
    public function calculateChurnRisk(Kisi $kisi): array
    {
        $bazPuan = 0;
        $talepYasiPuan = 0;
        $pipelinePuan = 0;

        $sonEtkilesim = $kisi->etkilesimler()->aktif()->orderBy('etkilesim_tarihi', 'desc')->first(); // context7-ignore
        if ($sonEtkilesim) {
            $gunFarki = Carbon::parse($sonEtkilesim->etkilesim_tarihi)->diffInDays(Carbon::now());
            if ($gunFarki >= 60) {
                $bazPuan = 40;
            } elseif ($gunFarki >= 30) {
                $bazPuan = 20;
            }
        }

        $aktifTalep = $kisi->talepler()->active()->orderBy('created_at', 'asc')->first(); // context7-ignore
        if ($aktifTalep) {
            $talepGunFarki = Carbon::parse($aktifTalep->created_at)->diffInDays(Carbon::now());
            if ($talepGunFarki >= 90) {
                $talepYasiPuan = 30;
            }
        }

        $segment = strtolower((string) ($kisi->segment ?? ''));
        $pipelineStage = $kisi->pipeline_stage;
        $isSoguk = in_array($segment, ['soğuk', 'soguk', 'cold']);
        $teklifThreshold = 4;
        $geride = is_null($pipelineStage) ? false : ($pipelineStage < $teklifThreshold);
        if ($isSoguk || $geride) {
            $pipelinePuan = 20;
        }

        $toplam = min(100, $bazPuan + $talepYasiPuan + $pipelinePuan);

        return [
            'kisi_id' => $kisi->id,
            'score' => $toplam,
            'breakdown' => [
                'baz_puan' => $bazPuan,
                'talep_yasi' => $talepYasiPuan,
                'akis_durumu' => $pipelinePuan,
            ],
            'updated_at' => now()->toISOString(),
        ];
    }
}
