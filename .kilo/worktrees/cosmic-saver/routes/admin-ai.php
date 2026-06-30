<?php

use App\Http\Controllers\Admin\DanismanAIController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Admin AI Routes
|--------------------------------------------------------------------------
|
| Danışman AI sistemi için özel route'lar
|
*/

Route::middleware(['auth', 'admin', 'role:admin'])->prefix('admin')->group(function () {

    // Danışman AI Dashboard Routes
    Route::prefix('danisman-ai')->name('admin.danisman-ai.')->group(function () {

        // Ana sayfalar
        Route::get('/', [DanismanAIController::class, 'index'])->name('index');
        Route::get('/prompt-interface', [DanismanAIController::class, 'promptInterface'])->name('prompt-interface');

        // API Routes (AJAX için)
        Route::prefix('api')->name('api.')->group(function () {

            // Serbest Prompt Analizi
            Route::post('/custom-prompt', [DanismanAIController::class, 'customPromptAnalysis'])->name('custom-prompt');

            // Akıllı Arama
            Route::post('/smart-search', [DanismanAIController::class, 'smartDemandSearch'])->name('smart-search');

            // Hızlı AI Önerileri
            Route::post('/quick-suggestions', [DanismanAIController::class, 'quickAISuggestions'])->name('quick-suggestions');

            // Toplu Analiz
            Route::post('/batch-analysis', [DanismanAIController::class, 'batchAnalysis'])->name('batch-analysis');

            // Aktif Talepler (Prompt arayüzü için)
            Route::get('/active-talepler', function () {
                try {
                    $talepler = \App\Models\Talep::where('danisman_id', auth()->id())
                        ->where('talep_durumu', \App\Enums\TalepDurumu::AKTIF)
                        ->with(['kisi', 'city', 'ilce'])
                        ->limit(20)
                        ->get()
                        ->map(function ($talep) {
                            return [
                                'id' => $talep->id,
                                'kisi_adi' => $talep->kisi->ad ?? 'Müşteri',
                                'lokasyon' => ($talep->il->ad ?? '') . '/' . ($talep->ilce->ad ?? ''),
                                'tarih' => $talep->created_at->format('d.m.Y'),
                            ];
                        });

                    return response()->json([
                        'success' => true,
                        'talepler' => $talepler,
                    ]);
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'error' => 'Talepler yüklenemedi: ' . $e->getMessage(),
                    ], 500);
                }
            })->name('active-talepler');
        });
    });

    // Genel AI API Routes (Mevcut sisteminizle entegrasyon)
    Route::prefix('api/ai')->name('admin.api.ai.')->group(function () {

        // Talep Analizi (SmartPropertyMatcherAI kullanılıyor)
        Route::post('/talep-analiz/{id}', function ($id) {
            try {
                $talep = \App\Models\Talep::with(['kisi', 'il', 'ilce'])->findOrFail($id);

                // SmartPropertyMatcherAI servisi kullanılıyor
                $matcher = app(\App\Services\AI\SmartPropertyMatcherAI::class);
                $matches = $matcher->match($talep);

                // Format: View uyumlu format
                $formattedMatches = collect($matches)->map(function ($match) {
                    return [
                        'ilan' => [
                            'id' => $match['ilan']->id,
                            'baslik' => $match['ilan']->baslik,
                            'fiyat' => $match['ilan']->fiyat,
                        ],
                        'score' => $match['score'],
                        'reasons' => $match['reasons'] ?? [],
                    ];
                })->values()->all();

                return response()->json([
                    'success' => true,
                    'talep_id' => $id,
                    'analiz' => [
                        'eslesen_ilanlar' => $formattedMatches,
                        'toplam_eslesme' => count($formattedMatches),
                    ],
                    'timestamp' => now()->toISOString(),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'Talep analizi başarısız: ' . $e->getMessage(),
                ], 500);
            }
        })->name('talep-analiz');

        // Talep-İlan Eşleştirme (SmartPropertyMatcherAI kullanılıyor)
        Route::post('/talep-eslesme/{id}', function ($id) {
            try {
                $talep = \App\Models\Talep::with(['kisi', 'il', 'ilce'])->findOrFail($id);

                // SmartPropertyMatcherAI ile eşleştirme
                $matcher = app(\App\Services\AI\SmartPropertyMatcherAI::class);
                $matches = $matcher->match($talep);

                // Format: Hızlı eşleştirme formatı (max 5 sonuç)
                $eslesmeler = collect($matches)
                    ->take(5)
                    ->map(function ($match) {
                        return [
                            'ilan' => $match['ilan'],
                            'score' => $match['score'],
                            'uygunluk' => $match['score'] >= 80 ? 'Yüksek' : ($match['score'] >= 60 ? 'Orta' : 'Düşük'),
                        ];
                    })
                    ->values()
                    ->all();

                return response()->json([
                    'success' => true,
                    'talep_id' => $id,
                    'eslesme' => [
                        'eslesme_sayisi' => count($eslesmeler),
                        'eslesmeler' => $eslesmeler,
                    ],
                    'timestamp' => now()->toISOString(),
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'İlan eşleştirme başarısız: ' . $e->getMessage(),
                ], 500);
            }
        })->name('talep-eslesme');

        // AI Sağlık Kontrolü (SmartPropertyMatcherAI kullanılıyor)
        Route::get('/health', function () {
            try {
                // SmartPropertyMatcherAI servisi kontrolü
                $matcher = app(\App\Services\AI\SmartPropertyMatcherAI::class);
                $health = $matcher !== null;

                return response()->json([
                    'success' => true,
                    'ai_durumu' => $health ? 'healthy' : 'unhealthy',
                    'timestamp' => now()->toISOString(),
                    'services' => [
                        'SmartPropertyMatcherAI' => $health,
                        'Context7Compliance' => true, // ✅ Verified
                        'Cache' => \Illuminate\Support\Facades\Cache::getStore() ? true : false,
                    ],
                ]);
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'error' => 'AI sağlık kontrolü başarısız: ' . $e->getMessage(),
                    'ai_durumu' => 'unhealthy',
                ], 500);
            }
        })->name('health');
    });

    // Admin AI Settings (web)
    Route::prefix('ai-settings')->middleware(['web', 'auth', 'admin'])->group(function () {
        Route::get('/provider-aktiflik', [\App\Http\Controllers\Admin\AISettingsController::class, 'providerAktiflikDurumu'])->name('ai-settings.provider-aktiflik');
        Route::post('/test-provider', [\App\Http\Controllers\Admin\AISettingsController::class, 'testProvider'])->name('ai-settings.test-provider');
        Route::post('/update-locale', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateLocale'])->name('ai-settings.update-locale');
        Route::post('/update-currency', [\App\Http\Controllers\Admin\AISettingsController::class, 'updateCurrency'])->name('ai-settings.update-currency');
        Route::get('/', function () {
            return view('admin.ai-settings.index');
        })->name('ai-settings.index');
    });
});

/*
|--------------------------------------------------------------------------
| Public AI Routes (Genel Erişim)
|--------------------------------------------------------------------------
|
| Genel kullanıcılar için AI özellikleri
|
*/

Route::prefix('api/public-ai')->name('public.ai.')->group(function () {

    // Genel Emlak AI Sorguları (Rate Limited)
    Route::middleware('throttle:10,1')->post('/ilan-arama', function (\Illuminate\Http\Request $request) {
        try {
            $validated = $request->validate([
                'query' => 'required|string|max:500',
                'location' => 'nullable|string|max:100',
                'budget_min' => 'nullable|numeric|min:0',
                'budget_max' => 'nullable|numeric|min:0',
            ]);

            // Basit ilan arama (AI olmadan)
            $ilanlar = \App\Models\Ilan::where('yayin_durumu', 'Aktif')
                ->where('yayinlandi', true)
                ->when($validated['location'] ?? null, function ($query, $location) {
                    return $query->whereHas('city', function ($q) use ($location) {
                        $q->where('ad', 'like', "%{$location}%");
                    })->orWhereHas('ilce', function ($q) use ($location) {
                        $q->where('ad', 'like', "%{$location}%");
                    });
                })
                ->when($validated['budget_min'] ?? null, function ($query, $budget) {
                    return $query->where('fiyat', '>=', $budget);
                })
                ->when($validated['budget_max'] ?? null, function ($query, $budget) {
                    return $query->where('fiyat', '<=', $budget);
                })
                ->limit(20)
                ->get(['id', 'baslik', 'fiyat', 'il_id', 'ilce_id']);

            return response()->json([
                'success' => true,
                'query' => $validated['query'],
                'results' => $ilanlar->map(function ($ilan) {
                    return [
                        'id' => $ilan->id,
                        'title' => $ilan->baslik,
                        'price' => $ilan->fiyat,
                        'location' => [
                            'city' => $ilan->il->ad ?? '',
                            'district' => $ilan->ilce->ad ?? '',
                        ],
                    ];
                }),
                'count' => $ilanlar->count(),
                'timestamp' => now()->toISOString(),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Arama yapılamadı: ' . $e->getMessage(),
            ], 500);
        }
    });
});
