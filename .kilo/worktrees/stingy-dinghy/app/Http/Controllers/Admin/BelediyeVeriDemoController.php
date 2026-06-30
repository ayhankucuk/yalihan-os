<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Integrations\BelediyeOpenDataService;
use App\Services\Integrations\CevreVerisiService;
use App\Services\Integrations\ImarPlanService;
use App\Services\Integrations\KiraTahminiService;
use Illuminate\Http\Request;

/**
 * Belediye Veri Demo Controller
 *
 * Demo sayfası için controller
 */
class BelediyeVeriDemoController extends Controller
{
    protected BelediyeOpenDataService $belediyeService;
    protected ImarPlanService $imarService;
    protected CevreVerisiService $cevreService;
    protected KiraTahminiService $kiraTahminiService;

    public function __construct(
        BelediyeOpenDataService $belediyeService,
        ImarPlanService $imarService,
        CevreVerisiService $cevreService,
        KiraTahminiService $kiraTahminiService
    ) {
        $this->belediyeService = $belediyeService;
        $this->imarService = $imarService;
        $this->cevreService = $cevreService;
        $this->kiraTahminiService = $kiraTahminiService;
    }

    /**
     * Demo sayfası
     */
    public function index()
    {
        return view('admin.demo.belediye-veri');
    }

    /**
     * İstanbul BB veri çek (AJAX)
     */
    public function getIstanbulData(Request $request)
    {
        $resourceId = $request->input('resource_id', '');
        $filters = $request->input('filters', []);
        $limit = $request->input('limit', 100);

        $data = $this->belediyeService->getIstanbulData($resourceId, $filters, $limit);

        return response()->json($data);
    }

    /**
     * Muğla verileri getir (AJAX)
     */
    public function getMuglaData(Request $request)
    {
        $type = $request->input('type', 'districts'); // context7-ignore
        $id = $request->input('id');

        $data = $this->belediyeService->getMuglaData($type, $id ? (int) $id : null);

        return response()->json($data);
    }

    /**
     * İmar planı getir (AJAX)
     */
    public function getImarPlani(Request $request)
    {
        $mahalleId = $request->input('mahalle_id', '');
        $lat = $request->input('lat');
        $lng = $request->input('lng');

        if ($lat && $lng) {
            $data = $this->imarService->getImarPlaniByCoordinates((float) $lat, (float) $lng);
        } else {
            $data = $this->imarService->getImarPlani($mahalleId);
        }

        return response()->json($data);
    }

    /**
     * İmar uygunluk kontrolü (AJAX)
     */
    public function checkImarUygunlugu(Request $request)
    {
        $arsaData = $request->only(['mahalle_id', 'kaks', 'taks', 'gabari']);

        $result = $this->imarService->checkImarUygunlugu($arsaData);

        return response()->json($result);
    }

    /**
     * Hava kalitesi getir (AJAX)
     */
    public function getHavaKalitesi(Request $request)
    {
        $lat = (float) $request->input('lat', 0);
        $lng = (float) $request->input('lng', 0);

        if (!$lat || !$lng) {
            return response()->json([
                'success' => false,
                'error' => 'Koordinat gerekli',
            ], 400);
        }

        $data = $this->cevreService->getHavaKalitesi($lat, $lng);

        return response()->json($data);
    }

    /**
     * Çevre skoru getir (AJAX)
     */
    public function getCevreSkoru(Request $request)
    {
        $lat = (float) $request->input('lat', 0);
        $lng = (float) $request->input('lng', 0);

        if (!$lat || !$lng) {
            return response()->json([
                'success' => false,
                'error' => 'Koordinat gerekli',
            ], 400);
        }

        $data = $this->cevreService->getCevreSkoru($lat, $lng);

        return response()->json($data);
    }

    /**
     * Yakın çevre analizi (AJAX)
     */
    public function getYakinCevreAnalizi(Request $request)
    {
        $lat = (float) $request->input('lat', 0);
        $lng = (float) $request->input('lng', 0);
        $radius = (int) $request->input('radius', 2000);

        if (!$lat || !$lng) {
            return response()->json([
                'success' => false,
                'error' => 'Koordinat gerekli',
            ], 400);
        }

        $data = $this->cevreService->getYakinCevreAnalizi($lat, $lng, $radius);

        return response()->json($data);
    }

    /**
     * Kira tahmini (AJAX)
     */
    public function predictRentalPrice(Request $request)
    {
        $propertyData = $request->only([
            'il_id',
            'ilce_id',
            'metrekare',
            'oda_sayisi',
            'bina_yasi',
            'esyali',
            'balkon',
            'asansor',
            'otopark',
        ]);

        $data = $this->kiraTahminiService->predictRentalPrice($propertyData);

        return response()->json($data);
    }

    /**
     * Yazlık kiralama tahmini (AJAX)
     */
    public function predictYazlikRental(Request $request)
    {
        $propertyData = $request->only([
            'il_id',
            'ilce_id',
            'metrekare',
            'oda_sayisi',
            'bina_yasi',
            'esyali',
            'balkon',
            'asansor',
            'otopark',
        ]);
        $season = $request->input('season', 'yaz');

        $data = $this->kiraTahminiService->predictYazlikRental($propertyData, $season);

        return response()->json($data);
    }
}
