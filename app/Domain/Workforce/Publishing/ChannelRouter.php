<?php

namespace App\Domain\Workforce\Publishing;

use App\Models\Ilan;

/**
 * ChannelRouter — Sprint 7.4
 *
 * ListingAgent sonucuna gore hangi kanallara yayinlanacagini secerr.
 * Quality score + yayin tipi bazli routing yapar.
 */
class ChannelRouter
{
    /**
     * Ilan icin uygun kanallari dondurur.
     *
     * @return array<PublishingChannel>
     */
    /**
     * @return array<PublishingChannel>
     */
    public function route(Ilan $ilan, int $qualityScore): array
    {
        /** @var array<string, PublishingChannel> $map */
        $map = [];

        $map[PublishingChannel::YALIHAN->value] = PublishingChannel::YALIHAN;

        $yayinTipi = mb_strtolower($ilan->yayin_tipi ?? '');
        $isKiralik = str_contains($yayinTipi, 'kiralik');
        $isGünlük = str_contains($yayinTipi, 'gunluk') || str_contains($yayinTipi, 'günlük');

        if ($isKiralik || $isGünlük) {
            if ($qualityScore >= PublishingChannel::AIRBNB->minQualityScore()) {
                $map[PublishingChannel::AIRBNB->value] = PublishingChannel::AIRBNB;
            }
            if ($qualityScore >= PublishingChannel::BOOKİNG->minQualityScore()) {
                $map[PublishingChannel::BOOKİNG->value] = PublishingChannel::BOOKİNG;
            }
        } else {
            if ($qualityScore >= PublishingChannel::SAHİBİNDEN->minQualityScore()) {
                $map[PublishingChannel::SAHİBİNDEN->value] = PublishingChannel::SAHİBİNDEN;
            }
            if ($qualityScore >= PublishingChannel::HEPSİEMLAK->minQualityScore()) {
                $map[PublishingChannel::HEPSİEMLAK->value] = PublishingChannel::HEPSİEMLAK;
            }
            if ($qualityScore >= PublishingChannel::EMLAKJET->minQualityScore()) {
                $map[PublishingChannel::EMLAKJET->value] = PublishingChannel::EMLAKJET;
            }
            if ($qualityScore >= PublishingChannel::ZİNGAT->minQualityScore()) {
                $map[PublishingChannel::ZİNGAT->value] = PublishingChannel::ZİNGAT;
            }
        }

        return array_values($map);
    }

    /**
     * Routing kararini aciklar.
     *
     * @param array<PublishingChannel> $channels
     * @return array<string, mixed>
     */
    public function explain(Ilan $ilan, int $qualityScore, array $channels): array
    {
        $reasons = [];

        foreach ($channels as $ch) {
            $threshold = $ch->minQualityScore();
            $eligible = $qualityScore >= $threshold;
            $reasons[$ch->value] = [
                'eligible' => $eligible,
                'score' => $qualityScore,
                'threshold' => $threshold,
                'reason' => $eligible
                    ? "Kalite yeterli ({$qualityScore} >= {$threshold})"
                    : "Kalite yetersiz ({$qualityScore} < {$threshold})",
            ];
        }

        return $reasons;
    }
}
