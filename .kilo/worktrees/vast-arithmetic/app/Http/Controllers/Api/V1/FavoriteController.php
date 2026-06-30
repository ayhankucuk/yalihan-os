<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Mobile\FavoriteService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    public function __construct(private readonly FavoriteService $favoriteService) {}

    public function index(Request $request)
    {
        $favorites = $this->favoriteService->getFavorites($request->user())->get();
        return ResponseService::success($favorites, 'Favori ilanlar getirildi');
    }

    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ilan_id' => 'required|exists:ilanlar,id',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        $isFavorited = $this->favoriteService->toggle($request->user(), $request->input('ilan_id'));

        return ResponseService::success(
            ['is_favorited' => $isFavorited],
            $isFavorited ? 'İlan favorilere eklendi' : 'İlan favorilerden çıkarıldı'
        );
    }
}
