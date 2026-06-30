<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Models\Ilan;
use App\Services\Calendar\IlanCalendarIcsService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;

/**
 * Admin Calendar Feed Controller
 *
 * Context7 Compliance: Feed yönetimi (admin)
 */
class IlanCalendarFeedAdminController extends Controller
{
    public function __construct(
        private IlanCalendarIcsService $icsService
    ) {
        $this->middleware('can:manage-ilanlar');
    }

    /**
     * Feed göster
     */
    public function show(Ilan $ilan)
    {
        $feed = $ilan->calendarFeed()->where('yayin_durumu', true)->first();

        $feedUrl = null;
        if ($feed) {
            $feedUrl = url('/calendar/ilan/' . $feed->token . '.ics');
        }

        return view('admin.ilanlar.calendar.feed', [
            'ilan' => $ilan,
            'feed' => $feed,
            'feedUrl' => $feedUrl,
        ]);
    }

    /**
     * Feed oluştur (idempotent)
     */
    public function create(Ilan $ilan)
    {
        $feed = $this->icsService->getOrCreateFeed($ilan, auth()->user());

        $feedUrl = url('/calendar/ilan/' . $feed->token . '.ics');

        return ResponseService::redirectSuccess(
            route('admin.ilanlar.calendar', $ilan),
            'Takvim feed\'i oluşturuldu'
        );
    }

    /**
     * Feed iptal et
     */
    public function revoke(Ilan $ilan)
    {
        $this->icsService->revokeFeed($ilan, auth()->user());

        return ResponseService::redirectSuccess(
            route('admin.ilanlar.calendar', $ilan),
            'Takvim feed\'i iptal edildi'
        );
    }
}
