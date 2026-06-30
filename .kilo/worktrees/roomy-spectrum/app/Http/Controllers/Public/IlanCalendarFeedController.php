<?php

namespace App\Http\Controllers\Public;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\IlanCalendarFeed;
use App\Services\Calendar\IlanCalendarIcsService;
use App\Services\Logging\LogService;
use Illuminate\Http\Request;

/**
 * Public Calendar Feed Controller
 *
 * Context7 Compliance: ICS feed (read-only)
 * - NO content_type in logs
 */
class IlanCalendarFeedController extends Controller
{
    public function __construct(
        private IlanCalendarIcsService $icsService
    ) {}

    /**
     * ICS feed
     */
    public function show(Request $request, string $token)
    {
        // Token kontrolü
        $feed = IlanCalendarFeed::where('token', $token)
            ->where('aktiflik_durumu', true)
            ->whereNull('revoked_at')
            ->with('ilan')
            ->first();

        if (!$feed) {
            // Log (NO content_type)
            LogService::warning('calendar_feed_not_found', [
                'token_prefix' => substr($token, 0, 8),
                'ip' => $request->ip(),
            ]);

            abort(404, 'Feed not found');
        }

        $ilan = $feed->ilan;

        // Meta bilgileri
        $meta = $this->icsService->getIcsMeta($ilan);

        // ETag/Last-Modified kontrolü
        $etag = $meta['etag'];
        $lastModified = $meta['last_modified']->format('D, d M Y H:i:s \G\M\T');

        if (
            $request->header('If-None-Match') === '"' . $etag . '"' ||
            $request->header('If-Modified-Since') === $lastModified
        ) {

            // Log (NO content_type)
            LogService::info('calendar_feed_served', [
                'ilan_id' => $ilan->id,
                'feed_id' => $feed->id,
                'token_prefix' => $feed->tokenPrefix(),
                'http_status' => 304,
                'etag' => $etag,
            ]);

            return response('', 304);
        }

        // ICS oluştur
        $ics = $this->icsService->buildIcsForIlan($ilan);

        // Log (NO content_type)
        LogService::info('calendar_feed_served', [
            'ilan_id' => $ilan->id,
            'feed_id' => $feed->id,
            'token_prefix' => $feed->tokenPrefix(),
            'http_status' => 200,
            'etag' => $etag,
            'event_count' => $meta['count'],
        ]);

        return response($ics, 200)
            ->header('Content-Type', 'text/calendar; charset=utf-8')
            ->header('Cache-Control', 'public, max-age=300')
            ->header('ETag', '"' . $etag . '"')
            ->header('Last-Modified', $lastModified);
    }
}
