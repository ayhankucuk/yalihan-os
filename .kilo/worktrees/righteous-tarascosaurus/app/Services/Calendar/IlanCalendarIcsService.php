<?php

namespace App\Services\Calendar;

use App\Models\Ilan;
use App\Models\IlanCalendarFeed;
use App\Models\IlanReservation;
use App\Models\User;
use App\Services\Logging\LogService;
use Carbon\Carbon;
use Illuminate\Support\Str;

/**
 * Ilan Calendar ICS Service
 *
 * Context7 Compliance: ICS generation (read-only)
 * - NO content_type in logs
 * - NO write to UPS/Reservation
 */
class IlanCalendarIcsService
{
    /**
     * Feed getir veya oluştur (idempotent)
     */
    public function getOrCreateFeed(Ilan $ilan, ?User $actor = null): IlanCalendarFeed
    {
        $feed = IlanCalendarFeed::where('ilan_id', $ilan->id)
            ->where('aktiflik_durumu', true)
            ->whereNull('revoked_at')
            ->first();

        if ($feed) {
            return $feed;
        }

        // Yeni token oluştur
        $token = Str::random(64);

        $feed = IlanCalendarFeed::create([
            'ilan_id' => $ilan->id,
            'token' => $token,
            'aktiflik_durumu' => true,
            'created_by_user_id' => $actor?->id,
        ]);

        // Log (NO content_type)
        LogService::info('calendar_feed_created', [
            'ilan_id' => $ilan->id,
            'feed_id' => $feed->id,
            'token_prefix' => $feed->tokenPrefix(),
            'user_id' => $actor?->id,
        ]);

        return $feed;
    }

    /**
     * Feed'i iptal et
     */
    public function revokeFeed(Ilan $ilan, ?User $actor = null): void
    {
        $feeds = IlanCalendarFeed::where('ilan_id', $ilan->id)
            ->where('aktiflik_durumu', true)
            ->get();

        foreach ($feeds as $feed) {
            $feed->update([
                'aktiflik_durumu' => false,
                'revoked_at' => now(),
            ]);

            // Log (NO content_type)
            LogService::warning('calendar_feed_revoked', [
                'ilan_id' => $ilan->id,
                'feed_id' => $feed->id,
                'token_prefix' => $feed->tokenPrefix(),
                'user_id' => $actor?->id,
            ]);
        }
    }

    /**
     * İlan için ICS dosyası oluştur
     */
    public function buildIcsForIlan(Ilan $ilan): string
    {
        $reservations = IlanReservation::forIlan($ilan->id)
            ->active() // context7-ignore
            ->where('end_date', '>=', now()->subDays(30)->toDateString())
            ->orderBy('start_date') // context7-ignore
            ->get();

        $lines = [];
        $lines[] = 'BEGIN:VCALENDAR';
        $lines[] = 'VERSION:2.0';
        $lines[] = 'PRODID:-//Yalihan//Calendar Feed//TR';
        $lines[] = 'CALSCALE:GREGORIAN';
        $lines[] = 'METHOD:PUBLISH';
        $lines[] = 'X-WR-CALNAME:' . $this->escapeIcs($ilan->baslik);
        $lines[] = 'X-WR-TIMEZONE:Europe/Istanbul';

        foreach ($reservations as $res) {
            $lines[] = 'BEGIN:VEVENT';
            $lines[] = 'UID:ilan-' . $ilan->id . '-res-' . $res->id . '@yalihan.com';
            $lines[] = 'DTSTAMP:' . now()->format('Ymd\THis\Z');
            $lines[] = 'DTSTART;VALUE=DATE:' . $res->start_date->format('Ymd');
            $lines[] = 'DTEND;VALUE=DATE:' . $res->end_date->format('Ymd');
            $lines[] = 'SUMMARY:' . $this->escapeIcs('Rezervasyon');
            $lines[] = 'DESCRIPTION:' . $this->escapeIcs('Rezervasyon bloğu');
            $lines[] = 'STATUS:CONFIRMED'; // context7-ignore
            $lines[] = 'TRANSP:OPAQUE';
            $lines[] = 'END:VEVENT';
        }

        $lines[] = 'END:VCALENDAR';

        return $this->foldLines(implode("\r\n", $lines));
    }

    /**
     * ICS meta bilgileri
     */
    public function getIcsMeta(Ilan $ilan): array
    {
        $lastModified = IlanReservation::forIlan($ilan->id)
            ->max('updated_at');

        $count = IlanReservation::forIlan($ilan->id)
            ->active() // context7-ignore — reservation_state filtresi
            ->count();

        $etagData = $ilan->id . '-' . ($lastModified ?? 'none') . '-' . $count;
        $etag = sha1($etagData);

        return [
            'last_modified' => $lastModified ? Carbon::parse($lastModified) : now(),
            'etag' => $etag,
            'count' => $count,
        ];
    }

    /**
     * ICS escape (RFC 5545)
     */
    private function escapeIcs(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace(',', '\\,', $text);
        $text = str_replace(';', '\\;', $text);
        $text = str_replace("\n", '\\n', $text);
        $text = str_replace("\r", '', $text);
        return $text;
    }

    /**
     * Line folding (75 octets max)
     */
    private function foldLines(string $content): string
    {
        $lines = explode("\r\n", $content);
        $folded = [];

        foreach ($lines as $line) {
            if (strlen($line) <= 75) {
                $folded[] = $line;
            } else {
                $folded[] = substr($line, 0, 75);
                $remaining = substr($line, 75);
                while (strlen($remaining) > 0) {
                    $folded[] = ' ' . substr($remaining, 0, 74);
                    $remaining = substr($remaining, 74);
                }
            }
        }

        return implode("\r\n", $folded);
    }
}
