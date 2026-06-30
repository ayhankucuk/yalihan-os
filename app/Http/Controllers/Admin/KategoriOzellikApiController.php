<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use Illuminate\Http\Request;

class KategoriOzellikApiController extends AdminController
{
    public function index(Request $request)
    {
        return response()->json(['message' => 'Kategori Ozellik API endpoint - to be implemented']);
    }

    public function getCategoryData()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getFeatureCategories()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getFeaturesByPublishingType()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getFeaturesForFrontend()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function getIlanFeatures()
    {
        return response()->json(['success' => true, 'data' => []]);
    }

    public function updateCategoryFeatures(Request $request)
    {
        return response()->json(['success' => true, 'message' => 'Kategori özellikleri güncellendi']);
    }
}
