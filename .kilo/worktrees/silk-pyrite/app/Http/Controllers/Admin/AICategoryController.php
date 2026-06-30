<?php

namespace App\Http\Controllers\Admin;

/**
 * @sab-ignore-catch
 */

/**
 * @sab-ignore-thin
 */

use App\Models\IlanKategori;
use App\Services\AICategoryManager;
use Illuminate\Http\Request;

class AICategoryController extends AdminController
{
    private $aiCategoryManager;

    public function __construct()
    {
        parent::__construct();
        $this->aiCategoryManager = new AICategoryManager;
        $this->middleware('can:manage-settings');
    }

    /**
     * AI destekli kategori yönetimi sayfası
     */
    public function index()
    {
        $categories = IlanKategori::whereNull('parent_id')
            ->with(['children', 'features'])
            ->orderBy('display_order') // context7-ignore
            ->get();

        // Debug: Kategorileri kontrol et
        // \Log::info('AI Category Controller - Kategoriler:', $categories->toArray());

        return view('admin.ai-category.index', compact('categories'));
    }


    /**
     * Kategori AI analizi
     */
    public function analyzeCategory(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
        ]);

        $categorySlug = $request->category_slug;

        try {
            $analysis = $this->aiCategoryManager->analyzeCategory($categorySlug);

            return response()->json([
                'success' => true,
                'analysis' => $analysis,
                'category' => $categorySlug,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kategori AI önerileri
     */
    public function getCategorySuggestions(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
        ]);

        $categorySlug = $request->category_slug;

        try {
            $suggestions = $this->aiCategoryManager->getCategorySuggestions($categorySlug);

            return response()->json([
                'success' => true,
                'suggestions' => $suggestions,
                'category' => $categorySlug,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Hibrit sıralama oluştur
     */
    public function generateHibritSiralama(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
        ]);

        $categorySlug = $request->category_slug;

        try {
            $siralama = $this->aiCategoryManager->generateHibritSiralama($categorySlug);

            return response()->json([
                'success' => true,
                'siralama' => $siralama,
                'category' => $categorySlug,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Akıllı form üret
     */
    public function generateSmartForm(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
            'publication_type' => 'nullable|string',
        ]);

        $categorySlug = $request->category_slug;
        $publicationType = $request->publication_type;

        try {
            $form = $this->aiCategoryManager->generateSmartForm($categorySlug, $publicationType);

            return response()->json([
                'success' => true,
                'form' => $form,
                'category' => $categorySlug,
                'publication_type' => $publicationType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Matrix yönetimi
     */
    public function manageMatrix(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
        ]);

        $categorySlug = $request->category_slug;

        try {
            $matrix = $this->aiCategoryManager->manageMatrix($categorySlug);

            return response()->json([
                'success' => true,
                'matrix' => $matrix,
                'category' => $categorySlug,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * AI'yi kategori hakkında öğret
     */
    public function teachAICategory(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
            'examples' => 'required|array',
            'examples.*.task' => 'required|string',
            'examples.*.expected_output' => 'required|string',
        ]);

        $categorySlug = $request->category_slug;
        $examples = $request->examples;

        try {
            $result = $this->aiCategoryManager->teachAICategory($categorySlug, $examples);

            return response()->json([
                'success' => true,
                'message' => 'AI başarıyla öğretildi!',
                'category' => $categorySlug,
                'examples_count' => count($examples),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Tüm kategoriler için AI analizi
     */
    public function analyzeAllCategories()
    {
        try {
            $results = $this->aiCategoryManager->analyzeAllCategories();

            return response()->json([
                'success' => true,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Kategori AI başarı oranını güncelle
     */
    public function updateAISuccess(Request $request)
    {
        $request->validate([
            'category_slug' => 'required|string',
            'task_type' => 'required|string',
            'is_success' => 'required|boolean',
        ]);

        $categorySlug = $request->category_slug;
        $taskType = $request->task_type;
        $isSuccess = $request->is_success;

        try {
            $result = $this->aiCategoryManager->updateCategoryAISuccess($categorySlug, $taskType, $isSuccess);

            return response()->json([
                'success' => true,
                'message' => 'AI başarı oranı güncellendi!',
                'category' => $categorySlug,
                'task_type' => $taskType,
                'is_success' => $isSuccess,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
