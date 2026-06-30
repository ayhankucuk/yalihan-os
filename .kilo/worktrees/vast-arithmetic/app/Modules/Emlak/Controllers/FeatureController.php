<?php

namespace App\Modules\Emlak\Controllers;

use App\Modules\BaseModule\Controllers\BaseController;
use App\Modules\Emlak\Models\Feature;
use App\Modules\Emlak\Models\FeatureCategory;
use App\Modules\Emlak\Models\FeatureTranslation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class FeatureController extends BaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Özellikleri ve kategorileri çevirileriyle birlikte yükle
        $features = Feature::with(['category.translations', 'translations'])->get();
        $categories = FeatureCategory::with('translations')->get();

        // Kategorilerde name sütununu çevirilerden set et
        $categories->each(function ($category) {
            $category->setNameFromTranslations();
        });

        // Özelliklerde name sütununu çevirilerden set et
        $features->each(function ($feature) {
            $feature->setNameFromTranslations();

            // Kategori name'ini de set et
            if ($feature->category) {
                $feature->category->setNameFromTranslations();
            }
        });

        return view('emlak::features.index', compact('features', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = FeatureCategory::with('translations')->get();

        // Her kategori için çeviriden gelen ismi set et
        $categories->each(function ($category) {
            $category->setNameFromTranslations();
        });

        return view('emlak::features.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:feature_categories,id',
            'description' => 'nullable|string',
            'applies_to' => 'nullable|string|in:konut,arsa,isyeri',
            'display_order' => 'nullable|integer|min:0',
            'is_filterable' => 'boolean',
            'show_on_card' => 'boolean',
        ]);

        // Özellik oluştur
        $feature = Feature::create([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'slug' => Str::slug($request->name),
            'applies_to' => $request->applies_to,
            'display_order' => $request->display_order ?? 0,
            'is_filterable' => $request->boolean('is_filterable'),
            'show_on_card' => $request->boolean('show_on_card'),
        ]);

        // Ana dildeki çeviriyi ekle
        FeatureTranslation::create([
            'feature_id' => $feature->id,
            'locale' => app()->getLocale(),
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('module.ozellikler.index')->with('success', 'Özellik başarıyla oluşturuldu');
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        // Feature'ı id ile bul
        $feature = Feature::with(['category', 'category.translations', 'translations', 'ilanlar'])->findOrFail($id);

        // Çevirileri kullanarak name sütununu set et
        $feature->setNameFromTranslations();

        if ($feature->category) {
            $feature->category->setNameFromTranslations();
        }

        return view('emlak::features.show', compact('feature'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        // Feature'ı id ile bul ve kategoriyi, çevirileri yükle
        $feature = Feature::with(['category', 'category.translations', 'translations'])->findOrFail($id);

        // Tüm kategorileri çevirileriyle birlikte getir
        $categories = FeatureCategory::with('translations')->get();

        // Çevirileri kullanarak name sütununu set et
        $feature->setNameFromTranslations();

        if ($feature->category) {
            $feature->category->setNameFromTranslations();
        }

        $categories->each(function ($category) {
            $category->setNameFromTranslations();
        });

        return view('emlak::features.edit', compact('feature', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $feature = Feature::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:feature_categories,id',
            'description' => 'nullable|string',
            'applies_to' => 'nullable|string|in:konut,arsa,isyeri',
            'display_order' => 'nullable|integer|min:0',
            'is_filterable' => 'boolean',
            'show_on_card' => 'boolean',
        ]);

        // Özelliği güncelle
        $feature->update([
            'category_id' => $request->category_id,
            'slug' => Str::slug($request->name),
            'applies_to' => $request->applies_to,
            'display_order' => $request->display_order ?? 0,
            'is_filterable' => $request->boolean('is_filterable'),
            'show_on_card' => $request->boolean('show_on_card'),
        ]);

        // Ana dildeki çeviriyi güncelle
        $translation = $feature->translations()->where('locale', app()->getLocale())->first();

        if ($translation) {
            $translation->update([
                'name' => $request->name,
                'description' => $request->description,
            ]);
        } else {
            FeatureTranslation::create([
                'feature_id' => $feature->id,
                'locale' => app()->getLocale(),
                'name' => $request->name,
                'description' => $request->description,
            ]);
        }

        // Feature modelinin name alanını da güncelle
        $feature->update(['name' => $request->name]);

        return redirect()->route('module.ozellikler.index')->with('success', 'Özellik başarıyla güncellendi');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $feature = Feature::findOrFail($id);

        // Önce çevirileri sil
        $feature->translations()->delete();

        // Sonra özelliği sil
        $feature->delete();

        return redirect()->route('module.ozellikler.index')->with('success', 'Özellik başarıyla silindi');
    }

    /**
     * API: Emlak türüne göre özellikleri getir
     */
    public function getFeaturesByPropertyType($propertyType)
    {
        try {
            $features = Feature::with(['category.translations', 'translations'])
                ->where(function ($query) use ($propertyType) {
                    $query->whereNull('applies_to')
                        ->orWhere('applies_to', 'like', "%{$propertyType}%")
                        ->orWhere('applies_to', 'all');
                })
                ->where('is_filterable', true)
                ->orderBy('display_order')
                ->get();

            // Çevirileri set et
            $features->each(function ($feature) {
                $feature->setNameFromTranslations();
                if ($feature->category) {
                    $feature->category->setNameFromTranslations();
                }
            });

            // Kategorilere göre grupla
            $grouped = $features->groupBy(function ($feature) {
                return $feature->category ? $feature->category->name : 'Diğer';
            });

            return response()->json([
                'status' => 'success',
                'data' => $grouped,
                'property_type' => $propertyType,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Özellikler yüklenirken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * API: Tüm özellik kategorilerini getir
     */
    public function getFeatureCategories()
    {
        try {
            $categories = FeatureCategory::with(['translations', 'features.translations'])
                ->orderBy('display_order')
                ->get();

            // Çevirileri set et
            $categories->each(function ($category) {
                $category->setNameFromTranslations();
                $category->features->each(function ($feature) {
                    $feature->setNameFromTranslations();
                });
            });

            return response()->json([
                'status' => 'success',
                'data' => $categories,
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Kategori verileri yüklenirken hata oluştu: '.$e->getMessage(),
            ], 500);
        }
    }
}
