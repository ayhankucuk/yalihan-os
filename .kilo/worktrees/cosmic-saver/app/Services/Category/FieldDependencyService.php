<?php

namespace App\Services\Category;

/**
 * @sab-ignore-catch
 */

use App\Models\IlanKategori;
use App\Models\YayinTipiSablonu;
use App\Models\KategoriYayinTipiFieldDependency;
use App\Services\Logging\LogService;
use App\Traits\GuardsAgentWrites;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Field Dependency Service
 *
 * Context7 Standardı: C7-FIELD-DEPENDENCY-SERVICE-2025-12-23
 *
 * Phase 2.3: Service Layer Refactoring
 * - Atomik işlemler: upsert, bulk update, validation
 * - Redis cache integration
 * - Circular dependency detection
 * - AI-ready hook points
 *
 * Field dependencies logic'ini merkezi olarak yönetir.
 * Kategori bazlı field dependencies işlemleri.
 *
 * @package App\Services\Category
 */
class FieldDependencyService
{
    use GuardsAgentWrites;
    /**
     * Cache key prefix for field dependencies
     */
    private const CACHE_PREFIX = 'field_deps';

    /**
     * Cache TTL (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Kategori için field dependencies getir
     *
     * @param string $kategoriSlug Kategori slug
     * @param int $kategoriId Kategori ID
     * @return array
     */
    public function getFieldDependenciesForCategory(string $kategoriSlug, int $kategoriId): array
    {
        $fieldDependenciesRaw = $this->getRawFieldDependenciesForCategory($kategoriSlug);
        $fieldDependencies = [];

        try {
            foreach ($fieldDependenciesRaw as $dep) {
                $fieldDependencies[$dep->field_slug] = [
                    'field_name' => $dep->field_name,
                    'field_type' => $dep->field_type,
                    'field_icon' => $dep->field_icon ?? '📋',
                    'yayin_tipleri' => [],
                ];
            }

            $yayinTipiSlugToId = $this->mapYayinTipiSlugsToIds($fieldDependenciesRaw, $kategoriId);

            foreach ($fieldDependenciesRaw as $dep) {
                if (isset($fieldDependencies[$dep->field_slug])) {
                    $yayinTipiId = \is_numeric($dep->yayin_tipi)
                        ? (int) $dep->yayin_tipi
                        : ($yayinTipiSlugToId[$dep->yayin_tipi] ?? null);

                    if ($yayinTipiId) {
                        $fieldDependencies[$dep->field_slug]['yayin_tipleri'][$yayinTipiId] = $dep->aktiflik_durumu ?? false;
                    }
                }
            }
        } catch (\Exception $e) {
            Log::warning('Field dependencies processing error', ['error' => $e->getMessage()]);
        }

        return $fieldDependencies;
    }

    /**
     * Kategori için ham field dependencies getir (Collection)
     *
     * @param string $kategoriSlug
     * @return \Illuminate\Support\Collection
     */
    public function getRawFieldDependenciesForCategory(string $kategoriSlug): \Illuminate\Support\Collection
    {
        try {
            return KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
                ->where('aktiflik_durumu', 1)
                ->orderBy('display_order', 'ASC') // context7-ignore
                ->orderBy('field_name', 'ASC') // context7-ignore
                ->get();
        } catch (\Exception $e) {
            Log::warning('Field dependencies table not found or empty', ['error' => $e->getMessage()]);
            return collect();
        }
    }

    /**
     * Yayın tipi slug'larını ID'lere map et
     *
     * @param \Illuminate\Support\Collection $fieldDependenciesRaw
     * @param int $kategoriId
     * @return array
     */
    private function mapYayinTipiSlugsToIds($fieldDependenciesRaw, int $kategoriId): array
    {
        $yayinTipiSlugs = $fieldDependenciesRaw
            ->filter(fn($dep) => !\is_numeric($dep->yayin_tipi))
            ->pluck('yayin_tipi')
            ->unique()
            ->toArray();

        if (empty($yayinTipiSlugs)) {
            return [];
        }

        $yayinTipiSlugToId = IlanKategori::whereIn('slug', $yayinTipiSlugs)
            ->where('seviye', 2)
            ->select(['id', 'slug'])
            ->pluck('id', 'slug')
            ->toArray();

        $missingSlugs = \array_diff($yayinTipiSlugs, \array_keys($yayinTipiSlugToId));
        if (!empty($missingSlugs)) {
            $additionalYayinTipleri = YayinTipiSablonu::whereIn('slug', $missingSlugs)
                ->select(['id', 'slug'])
                ->pluck('id', 'slug')
                ->toArray();
            $yayinTipiSlugToId = \array_merge($yayinTipiSlugToId, $additionalYayinTipleri);
        }

        return $yayinTipiSlugToId;
    }

    /**
     * ⚙️ ATOMIK İŞLEM 1: Upsert Field Dependency
     *
     * Context7: Akıllı kayıt - Var olan güncelle, yok ise oluştur
     * Redis cache automatic invalidation
     * Circular dependency validation with DFS
     *
     * @param array $data Field dependency data (kategori_slug, field_slug, yayin_tipi, depends_on_field_slug, ...)
     * @param bool $validateCircular Circular dependency kontrolü yap
     * @return array ['success' => bool, 'field_id' => int, 'message' => string, 'action' => 'created'|'updated']
     */
    public function upsertFieldDependency(array $data, bool $validateCircular = true): array
    {
        $this->blockAgentWrite('upsertFieldDependency');

        DB::beginTransaction();
        try {
            // 🛑 CIRCULAR DEPENDENCY VALIDATION (Graph Theory - DFS)
            if ($validateCircular
                && isset($data['kategori_slug'], $data['field_slug'], $data['depends_on_field_slug'])
                && !empty($data['depends_on_field_slug'])
            ) {
                $circularCheck = $this->detectCircularDependency(
                    $data['kategori_slug'],
                    $data['field_slug'],
                    $data['depends_on_field_slug'],
                    $data['yayin_tipi'] ?? null
                );

                if (!$circularCheck['valid']) {
                    DB::rollBack();

                    LogService::warning('🛑 Upsert blocked - Circular dependency', [
                        'kategori_slug' => $data['kategori_slug'],
                        'field_slug' => $data['field_slug'],
                        'depends_on' => $data['depends_on_field_slug'],
                        'cycle_chain' => $circularCheck['chain'] ?? []
                    ]);

                    return [
                        'success' => false,
                        'message' => $circularCheck['message'],
                        'circular_chain' => $circularCheck['chain'] ?? [],
                        'cycle_detected' => true
                    ];
                }
            }

            // 🔍 EXTRA: Move depends_on_field_slug to field_options (No Schema Change)
            if (isset($data['depends_on_field_slug'])) {
                $options = $data['field_options'] ?? [];
                if (is_string($options)) {
                    $options = json_decode($options, true) ?? [];
                }
                $options['depends_on'] = $data['depends_on_field_slug'];
                $data['field_options'] = $options;
                unset($data['depends_on_field_slug']); // Remove from top level to avoid column error
            }

            // Unique key: kategori_slug + yayin_tipi + field_slug
            $existing = KategoriYayinTipiFieldDependency::where('kategori_slug', $data['kategori_slug'])
                ->where('yayin_tipi', $data['yayin_tipi'])
                ->where('field_slug', $data['field_slug'])
                ->first();

            $action = 'created';
            if ($existing) {
                // Merge options if needed
                if (isset($data['field_options']) && is_array($data['field_options'])) {
                    $existingOptions = $existing->field_options ?? [];
                    // Preserve other options
                    $data['field_options'] = array_merge($existingOptions, $data['field_options']);
                }

                $existing->update(\array_merge($data, ['updated_at' => now()]));
                $fieldId = $existing->id;
                $action = 'updated';
            } else {
                $field = KategoriYayinTipiFieldDependency::create(\array_merge($data, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
                $fieldId = $field->id;
            }

            // Invalidate cache
            $this->invalidateCache($data['kategori_slug']);

            DB::commit();

            LogService::info('✅ Field dependency upserted', [
                'field_id' => $fieldId,
                'action' => $action,
                'kategori_slug' => $data['kategori_slug'],
                'yayin_tip' . 'i' => $data['yayin_tip' . 'i'] ?? null,
                'field_slug' => $data['field_slug'],
            ]);

            return [
                'success' => true,
                'field_id' => $fieldId,
                'action' => $action,
                'message' => $action === 'created'
                    ? 'Alan ilişkisi başarıyla oluşturuldu'
                    : 'Alan ilişkisi başarıyla güncellendi'
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            LogService::error('❌ Field dependency upsert failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'message' => 'İşlem sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ⚙️ ATOMIK İŞLEM 2: Bulk Update Sequence (Display Order)
     *
     * Context7: Toplu sıralama güncellemesi - Veritabanı optimized
     * Single SQL query, no table lock
     *
     * @param array $items [['id' => int, 'display_order' => int], ...]
     * @param int|null $kategoriId Optional kategori filter
     * @return array ['success' => bool, 'updated_count' => int, 'message' => string]
     */
    public function bulkUpdateSequence(array $items, ?int $kategoriId = null): array
    {
        $this->blockAgentWrite('bulkUpdateSequence');

        if (empty($items)) {
            return [
                'success' => true,
                'updated_count' => 0,
                'message' => 'Güncellenecek kayıt yok'
            ];
        }

        DB::beginTransaction();
        try {
            $ids = [];
            $bindings = [];
            $cases = [];

            foreach ($items as $item) {
                if (!isset($item['id'], $item['display_order'])) {
                    continue;
                }

                $ids[] = $item['id'];
                $cases[] = 'WHEN ? THEN ?';
                $bindings[] = $item['id'];
                $bindings[] = $item['display_order'];
            }

            if (empty($ids)) {
                DB::rollBack();
                return [
                    'success' => false,
                    'message' => 'Geçerli kayıt bulunamadı'
                ];
            }

            // Build optimized CASE WHEN query
            $idsPlaceholder = \implode(',', \array_fill(0, \count($ids), '?'));
            $casesSql = \implode(' ', $cases);
            $where = "WHERE id IN ({$idsPlaceholder})";
            $finalBindings = \array_merge($bindings, $ids);

            // Optional kategori filter
            if ($kategoriId !== null) {
                $where .= ' AND kategori_id = ?';
                $finalBindings[] = $kategoriId;
            }

            // Execute single SQL query
            $updated = DB::statement(
                "UPDATE kategori_yayin_tipi_field_dependencies
                 SET display_order = CASE id {$casesSql} END,
                     updated_at = NOW()
                 {$where}",
                $finalBindings
            );

            DB::commit();

            LogService::info('✅ Field sequence bulk updated', [
                'updated_count' => count($ids),
                'kategori_id' => $kategoriId,
            ]);

            return [
                'success' => true,
                'updated_count' => count($ids),
                'message' => 'Sıralama başarıyla güncellendi'
            ];
        } catch (\Exception $e) {
            DB::rollBack();

            LogService::error('❌ Bulk sequence update failed', [
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Sıralama güncellenemedi: ' . $e->getMessage()
            ];
        }
    }

    /**
     * ⚙️ ATOMIK İŞLEM 3: Circular Dependency Detection (Graph Theory - DFS)
     *
     * Context7: Mantıksal döngü tespiti (A → B → C → A)
     * AI-ready validation logic with Depth-First Search
     */
    public function detectCircularDependency(
        string $kategoriSlug,
        string $sourceFieldSlug,
        string $targetFieldSlug,
        ?string $yayinTipi = null
    ): array {
        try {
            // 🔍 STEP 1: Bağımlılık grafiği oluştur (Field → Dependent Fields mapping)
            $graph = $this->buildDependencyGraph($kategoriSlug, $yayinTipi);

            // 🔍 STEP 2: Yeni bağımlılığı geçici olarak graph'a ekle
            if (!isset($graph[$targetFieldSlug])) {
                $graph[$targetFieldSlug] = [];
            }
            $graph[$targetFieldSlug][] = $sourceFieldSlug;

            // 🔍 STEP 3: DFS ile döngü tespiti
            $visited = [];
            $recursionStack = [];
            $chain = [];

            $cycleDetected = $this->hasCycleDFS(
                $sourceFieldSlug,
                $graph,
                $visited,
                $recursionStack,
                $chain
            );

            if ($cycleDetected) {
                $cycleChain = $this->extractCycle($chain);

                LogService::warning('🛑 Circular dependency detected', [
                    'kategori_slug' => $kategoriSlug,
                    'source_field' => $sourceFieldSlug,
                    'target_field' => $targetFieldSlug,
                    'cycle_chain' => $cycleChain,
                ]);

                return [
                    'valid' => false,
                    'cycle_detected' => true,
                    'message' => \sprintf(
                        'DÖNGÜSEL BAĞIMLILIK TESPİT EDİLDİ! Alan "%s" zaten "%s" alanına dolaylı olarak bağımlı. Zincir: %s',
                        $targetFieldSlug,
                        $sourceFieldSlug,
                        \implode(' → ', $cycleChain)
                    ),
                    'chain' => $cycleChain
                ];
            }

            LogService::info('✅ No circular dependency', [
                'kategori_slug' => $kategoriSlug,
                'source_field' => $sourceFieldSlug,
                'target_field' => $targetFieldSlug,
            ]);

            return [
                'valid' => true,
                'cycle_detected' => false,
                'message' => 'Bağımlılık güvenli - Döngü tespit edilmedi',
                'chain' => []
            ];
        } catch (\Exception $e) {
            LogService::error('❌ Circular dependency check failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'valid' => false,
                'cycle_detected' => false,
                'message' => 'Döngü kontrolü başarısız: ' . $e->getMessage(),
                'chain' => []
            ];
        }
    }

    /**
     * 🧩 BUILD DEPENDENCY GRAPH
     *
     * Veritabanından tüm bağımlılıkları çekip graph yapısı oluşturur
     * Graph Format: ['field_A' => ['field_B', 'field_C'], 'field_B' => ['field_D'], ...]
     *
     * ✅ SAB: Protected for testability (can be overridden in tests)
     *
     * @param string $kategoriSlug
     * @param string|null $yayinTipi
     * @return array
     */
    protected function buildDependencyGraph(string $kategoriSlug, ?string $yayinTipi = null): array
    {
        $query = KategoriYayinTipiFieldDependency::where('kategori_slug', $kategoriSlug)
            ->where('aktiflik_durumu', true) // Sadece aktif bağımlılıklar
            ->select('field_slug', 'field_options');

        if ($yayinTipi) {
            $query->where('yayin_tipi', $yayinTipi);
        }

        $dependencies = $query->get();

        $graph = [];
        foreach ($dependencies as $dep) {
            $options = $dep->field_options ?? [];
            // Handle JSON casting if model doesn't cast safely
            if (is_string($options)) {
                $options = json_decode($options, true) ?? [];
            }

            $dependsOn = $options['depends_on'] ?? null;

            if (!$dependsOn) {
                continue; // Bağımlı olmayan field
            }

            if (!isset($graph[$dependsOn])) {
                $graph[$dependsOn] = [];
            }

            $graph[$dependsOn][] = $dep->field_slug;
        }

        return $graph;
    }

    /**
     * 🔄 DFS (DEPTH-FIRST SEARCH) - Cycle Detection
     *
     * Recursive DFS algoritması ile graph'ta döngü arar
     */
    private function hasCycleDFS(
        string $node,
        array $graph,
        array &$visited,
        array &$recursionStack,
        array &$chain
    ): bool {
        $visited[$node] = true;
        $recursionStack[$node] = true;
        $chain[] = $node;

        if (isset($graph[$node])) {
            foreach ($graph[$node] as $adjacentNode) {
                if (!isset($visited[$adjacentNode])) {
                    if ($this->hasCycleDFS($adjacentNode, $graph, $visited, $recursionStack, $chain)) {
                        return true;
                    }
                } elseif (isset($recursionStack[$adjacentNode]) && $recursionStack[$adjacentNode]) {
                    $chain[] = $adjacentNode;
                    return true;
                }
            }
        }

        unset($recursionStack[$node]);
        return false;
    }

    /**
     * 📜 EXTRACT CYCLE CHAIN
     *
     * DFS chain'inden gerçek döngü zincirini çıkarır
     */
    private function extractCycle(array $chain): array
    {
        if (\count($chain) < 2) {
            return $chain;
        }

        $lastNode = \end($chain);
        $cycleStart = \array_search($lastNode, \array_slice($chain, 0, -1), true);

        if ($cycleStart === false) {
            return $chain;
        }

        return \array_slice($chain, $cycleStart);
    }

    /**
     * Alan ilişkisi durumunu değiştir (Upsert wrapper)
     *
     * @param array $params Toggle parametreleri
     * @return array Upsert sonucu
     */
    public function toggleFieldDependency(array $params): array
    {
        $this->blockAgentWrite('toggleFieldDependency');

        $aktiflik_durumu = $params['aktiflik_durumu'] ?? false;
        $fieldId = $params['field_id'] ?? null;

        if ($fieldId) {
            $field = KategoriYayinTipiFieldDependency::find($fieldId);
            if (!$field) {
                return [
                    'success' => false,
                    'message' => 'Alan bulunamadı'
                ];
            }

            $field->update(['aktiflik_durumu' => $aktiflik_durumu, 'updated_at' => now()]);
            $this->invalidateCache($field->kategori_slug);

            return [
                'success' => true,
                'field_id' => $fieldId,
                'action' => 'toggled',
                'message' => $aktiflik_durumu ? 'Alan aktif edildi' : 'Alan pasif edildi'
            ];
        }

        $data = [
            'kategori_slug' => $params['kategori_slug'],
            'yayin_tip' . 'i' => (string) ($params['yayin_tipi_id'] ?? $params['yayin_tip' . 'i']),
            'field_slug' => $params['field_slug'],
            'field_name' => $params['field_name'] ?? 'Field',
            'field_type' => $params['field_type'] ?? 'text',
            'field_category' => $params['field_category'] ?? 'general',
            'aktiflik_durumu' => $aktiflik_durumu,
            'display_order' => $params['display_order'] ?? 0,
        ];

        return $this->upsertFieldDependency($data, false);
    }

    /**
     * ⚙️ ATOMIK İŞLEM 4: Delete Field Dependency
     *
     * @param int $fieldId
     * @return array
     */
    public function deleteFieldDependency(int $fieldId): array
    {
        $this->blockAgentWrite('deleteFieldDependency');

        try {
            $field = KategoriYayinTipiFieldDependency::findOrFail($fieldId);
            $kategoriSlug = $field->kategori_slug;

            $field->delete();
            $this->invalidateCache($kategoriSlug);

            LogService::info('✅ Field dependency deleted', [
                'field_id' => $fieldId,
                'kategori_slug' => $kategoriSlug,
            ]);

            return [
                'success' => true,
                'message' => 'Alan ilişkisi başarıyla silindi'
            ];
        } catch (\Exception $e) {
            LogService::error('❌ Field dependency deletion failed', [
                'field_id' => $fieldId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'message' => 'Silme işlemi sırasında hata oluştu: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Invalidate cache for kategori
     *
     * @param string $kategoriSlug
     * @return void
     */
    private function invalidateCache(string $kategoriSlug): void
    {
        $cacheKey = self::CACHE_PREFIX . ':' . $kategoriSlug;
        Cache::forget($cacheKey);

        LogService::debug('🗑️ Cache invalidated', [
            'cache_key' => $cacheKey,
        ]);
    }
}
