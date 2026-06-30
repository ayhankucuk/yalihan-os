<?php

namespace App\Http\Controllers\Api\V1;

/**
 * @sab-ignore-thin
 */

use App\Http\Controllers\Controller;
use App\Services\Mobile\SavedSearchService;
use App\Services\Response\ResponseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SavedSearchController extends Controller
{
    public function __construct(private readonly SavedSearchService $savedSearchService) {}

    public function index(Request $request)
    {
        $searches = $this->savedSearchService->list($request->user());
        return ResponseService::success($searches, 'Kayıtlı aramalar getirildi');
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'criteria' => 'required|array',
            'notification_frequency' => 'required|in:instant,daily,off',
        ]);

        if ($validator->fails()) {
            return ResponseService::validationError($validator->errors()->toArray());
        }

        $search = $this->savedSearchService->create($request->user(), $request->all());

        return ResponseService::success($search, 'Arama kaydedildi');
    }

    public function destroy(Request $request, $id)
    {
        $deleted = $this->savedSearchService->delete($request->user(), $id);

        if (!$deleted) {
            return ResponseService::notFound('Kayıtlı arama bulunamadı');
        }

        return ResponseService::success(null, 'Kayıtlı arama silindi');
    }
}
