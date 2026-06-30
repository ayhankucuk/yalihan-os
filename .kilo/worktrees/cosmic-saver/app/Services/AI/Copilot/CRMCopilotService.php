<?php

namespace App\Services\AI\Copilot;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * §8 CRM Copilot Deep
 *
 * Lead scoring integration, decay detection, segmentation,
 * follow-up suggestions, match quality prediction.
 */
class CRMCopilotService
{
    /**
     * Analyze a contact and return CRM intelligence.
     */
    public function analyzeContact(int $kisiId): array
    {
        try {
            $kisi = DB::table('kisiler')->find($kisiId);
            if (!$kisi) {
                return ['error' => 'Kişi bulunamadı'];
            }

            return [
                'lead_score' => $this->calculateLeadScore($kisi),
                'decay' => $this->detectDecay($kisi),
                'segmentation' => $this->segmentContact($kisi),
                'follow_up' => $this->suggestFollowUp($kisi),
            ];
        } catch (\Exception $e) {
            Log::warning('CRMCopilotService analysis failed', ['kisi_id' => $kisiId, 'error' => $e->getMessage()]);
            return [];
        }
    }

    /**
     * §8.1 Lead scoring — Deterministic profile + activity score
     */
    protected function calculateLeadScore(object $kisi): array
    {
        $score = 0;
        $factors = [];

        // Profile completeness (max 40 points)
        if (!empty($kisi->telefon)) {
            $score += 10;
            $factors[] = ['factor' => 'Telefon mevcut', 'points' => 10];
        }
        if (!empty($kisi->email)) {
            $score += 5;
            $factors[] = ['factor' => 'E-posta mevcut', 'points' => 5];
        }
        if (!empty($kisi->il_id)) {
            $score += 10;
            $factors[] = ['factor' => 'Konum belirtilmiş', 'points' => 10];
        }
        if (!empty($kisi->meslek)) {
            $score += 5;
            $factors[] = ['factor' => 'Meslek bilgisi var', 'points' => 5];
        }
        if (!empty($kisi->ad) && !empty($kisi->soyad)) {
            $score += 10;
            $factors[] = ['factor' => 'Tam isim mevcut', 'points' => 10];
        }

        // Activity score (max 60 points)
        $talepCount = DB::table('talepler')->where('kisi_id', $kisi->id)->count();
        $acikTalepCount = DB::table('talepler')
            ->where('kisi_id', $kisi->id)
            ->where('talep_durumu', 'acik')
            ->count();
        $eslesmeCount = DB::table('eslesmeler')
            ->whereIn('talep_id', function ($q) use ($kisi) {
                $q->select('id')->from('talepler')->where('kisi_id', $kisi->id);
            })
            ->count();

        if ($talepCount > 0) {
            $activityPoints = min(20, $talepCount * 10);
            $score += $activityPoints;
            $factors[] = ['factor' => $talepCount . ' talep kaydı', 'points' => $activityPoints];
        }
        if ($acikTalepCount > 0) {
            $score += 20;
            $factors[] = ['factor' => $acikTalepCount . ' açık talep', 'points' => 20];
        }
        if ($eslesmeCount > 0) {
            $matchPoints = min(20, $eslesmeCount * 5);
            $score += $matchPoints;
            $factors[] = ['factor' => $eslesmeCount . ' eşleşme geçmişi', 'points' => $matchPoints];
        }

        $score = min(100, $score);

        return [
            'score' => $score,
            'label' => match (true) {
                $score >= 80 => 'Sıcak',
                $score >= 50 => 'Ilık',
                default => 'Soğuk',
            },
            'factors' => $factors,
            'profile_score' => min(40, array_sum(array_filter(
                array_column($factors, 'points'),
                fn($p) => $p <= 10
            ))),
            'activity_score' => max(0, $score - 40),
        ];
    }

    /**
     * §8.2 Decay detection — Identify leads going cold
     */
    protected function detectDecay(object $kisi): array
    {
        $createdAt = $kisi->created_at ?? null;
        $updatedAt = $kisi->updated_at ?? null;

        $daysSinceCreation = $createdAt ? now()->diffInDays($createdAt) : 0;
        $daysSinceUpdate = $updatedAt ? now()->diffInDays($updatedAt) : $daysSinceCreation;

        // Last activity is either last talep or last update
        $lastTalepDate = DB::table('talepler')
            ->where('kisi_id', $kisi->id)
            ->orderByDesc('created_at')
            ->value('created_at');

        $daysSinceLastActivity = $lastTalepDate
            ? now()->diffInDays($lastTalepDate)
            : $daysSinceUpdate;

        $decayLevel = match (true) {
            $daysSinceLastActivity > 180 => 'critical',
            $daysSinceLastActivity > 90 => 'high',
            $daysSinceLastActivity > 30 => 'medium',
            default => 'low',
        };

        return [
            'decay_level' => $decayLevel,
            'days_since_last_activity' => $daysSinceLastActivity,
            'days_since_creation' => $daysSinceCreation,
            'last_activity_date' => $lastTalepDate ?? $updatedAt,
            'recommendation' => match ($decayLevel) {
                'critical' => 'Bu lead 6+ aydır hareketsiz. İletişime geçin veya pasife alın.',
                'high' => 'Bu lead 3+ aydır sessiz. Takip araması önerilir.',
                'medium' => 'Son 1-3 ay içinde aktivite düşük. Durum güncellemesi yapın.',
                'low' => 'Lead aktif — düzenli takip yeterli.',
            },
        ];
    }

    /**
     * §8.3 Contact segmentation
     */
    protected function segmentContact(object $kisi): array
    {
        $segments = [];

        // By type
        $kisiTipi = $kisi->kisi_tipi ?? 'Müşteri';
        $segments[] = $kisiTipi;

        // By location
        if (!empty($kisi->il_id)) {
            $ilAdi = DB::table('iller')->where('id', $kisi->il_id)->value('il_adi');
            if ($ilAdi) {
                $segments[] = $ilAdi;
            }
        }

        // By activity level
        $talepCount = DB::table('talepler')->where('kisi_id', $kisi->id)->count();
        if ($talepCount >= 3) {
            $segments[] = 'Çoklu Talep';
        } elseif ($talepCount > 0) {
            $segments[] = 'Aktif Talep';
        } else {
            $segments[] = 'Talepsiz';
        }

        // By request types
        $talepTipleri = DB::table('talepler')
            ->where('kisi_id', $kisi->id)
            ->distinct()
            ->pluck('talep_tipi')
            ->toArray();

        if (in_array('Al', $talepTipleri) || in_array('Kirala_Al', $talepTipleri)) {
            $segments[] = 'Alıcı';
        }
        if (in_array('Sat', $talepTipleri) || in_array('Kirala', $talepTipleri)) {
            $segments[] = 'Satıcı/Kiralayan';
        }

        return [
            'segments' => $segments,
            'primary_segment' => $segments[0] ?? 'Belirsiz',
            'is_buyer' => in_array('Alıcı', $segments),
            'is_seller' => in_array('Satıcı/Kiralayan', $segments),
        ];
    }

    /**
     * §8.4 Follow-up suggestions
     */
    protected function suggestFollowUp(object $kisi): array
    {
        $suggestions = [];

        // Check open requests without matches
        $unmatchedTalepler = DB::table('talepler')
            ->where('kisi_id', $kisi->id)
            ->where('talep_durumu', 'acik')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('eslesmeler')
                    ->whereColumn('eslesmeler.talep_id', 'talepler.id');
            })
            ->count();

        if ($unmatchedTalepler > 0) {
            $suggestions[] = [
                'type' => 'matching',
                'priority' => 1,
                'title' => $unmatchedTalepler . ' açık talep eşleştirilmeli',
                'action' => 'AI eşleştirme çalıştırın',
            ];
        }

        // Check if contact info is incomplete
        if (empty($kisi->telefon) && empty($kisi->email)) {
            $suggestions[] = [
                'type' => 'data_completion',
                'priority' => 1,
                'title' => 'İletişim bilgisi gerekli',
                'action' => 'Telefon veya e-posta ekleyin',
            ];
        }

        // Time-based follow-up
        $lastTalepDate = DB::table('talepler')
            ->where('kisi_id', $kisi->id)
            ->orderByDesc('created_at')
            ->value('created_at');

        if ($lastTalepDate) {
            $daysSince = now()->diffInDays($lastTalepDate);
            if ($daysSince > 14) {
                $suggestions[] = [
                    'type' => 'follow_up_call',
                    'priority' => 2,
                    'title' => $daysSince . ' gündür dönüş yapılmamış',
                    'action' => 'Müşteriyi arayarak durumunu öğrenin',
                ];
            }
        }

        // Sort by priority
        usort($suggestions, fn($a, $b) => ($a['priority'] ?? 99) <=> ($b['priority'] ?? 99));

        return $suggestions;
    }

    /**
     * Aggregate CRM intelligence for list views.
     */
    public function aggregateListMetrics(): array
    {
        try {
            $totalKisi = DB::table('kisiler')->count();
            $aktifKisi = DB::table('kisiler')->where('aktiflik_durumu', 1)->count();

            $iletisimsiz = DB::table('kisiler')
                ->where(function ($q) {
                    $q->whereNull('telefon')->orWhere('telefon', '');
                })
                ->where(function ($q) {
                    $q->whereNull('email')->orWhere('email', '');
                })
                ->count();

            $talepsiz = DB::table('kisiler')
                ->where('aktiflik_durumu', 1)
                ->whereNotExists(function ($q) {
                    $q->select(DB::raw(1))
                        ->from('talepler')
                        ->whereColumn('talepler.kisi_id', 'kisiler.id');
                })
                ->count();

            $sonHaftaYeni = DB::table('kisiler')
                ->where('created_at', '>=', now()->startOfWeek())
                ->count();

            return [
                'toplam_kisi' => $totalKisi,
                'aktif_kisi' => $aktifKisi,
                'iletisimsiz' => $iletisimsiz,
                'talepsiz' => $talepsiz,
                'son_hafta_yeni' => $sonHaftaYeni,
                'crm_health' => $totalKisi > 0
                    ? round((1 - ($iletisimsiz / max($totalKisi, 1))) * 100)
                    : 0,
            ];
        } catch (\Exception $e) {
            Log::warning('CRMCopilotService aggregate failed', ['error' => $e->getMessage()]);
            return [];
        }
    }
}
