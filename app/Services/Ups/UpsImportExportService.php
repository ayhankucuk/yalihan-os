<?php

declare(strict_types=1);

namespace App\Services\Ups;

/**
 * @sab-ignore-catch
 */

use App\Models\Feature;
use App\Models\FeatureAssignment;
use App\Models\FeatureCategory;
use App\Models\IlanKategori;
use App\Models\MasterTemplate;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Import/Export Service for UPS configuration
 *
 * Context7 Compliant:
 * - Uses aktiflik_durumu (Canonical)
 * - Uses display_order (Canonical)
 * - Wildcard cache pattern (NO Cache::tags)
 */
class UpsImportExportService
{
    private const EXPORT_VERSION = '1.0.0';

    public function __construct(
        private readonly UpsCacheService $cacheService
    ) {}

    /**
     * Export all UPS configuration to JSON
     */
    public function exportAll(): array
    {
        $data = [
            'version' => self::EXPORT_VERSION,
            'exported_at' => now()->toIso8601String(),
            'exported_by' => auth()->user()?->name ?? 'System',
            'features' => $this->exportFeatures(),
            'feature_categories' => $this->exportFeatureCategories(),
            'templates' => $this->exportTemplates(),
            'assignments' => $this->exportAssignments(),
            'statistics' => $this->getExportStatistics(),
        ];

        Log::channel('daily')->info('UPS configuration exported', [
            'features_count' => count($data['features']),
            'categories_count' => count($data['feature_categories']),
            'templates_count' => count($data['templates']),
            'assignments_count' => count($data['assignments']),
        ]);

        return $data;
    }

    /**
     * Export only features
     */
    public function exportFeatures(): array
    {
        return Feature::with(['category'])
            ->get()
            ->map(fn($feature) => [
                'id' => $feature->id,
                'name' => $feature->name,
                'slug' => $feature->slug,
                'description' => $feature->description,
                'type' => $feature->type, // context7-ignore
                'options' => $feature->options,
                'aktiflik_durumu' => $feature->aktiflik_durumu,
                'display_order' => $feature->display_order,
                'category_slug' => $feature->category?->slug,
                'icon' => $feature->icon,
            ])
            ->toArray();
    }

    /**
     * Export feature categories
     */
    public function exportFeatureCategories(): array
    {
        return FeatureCategory::all()
            ->map(fn($category) => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'description' => $category->description,
                'icon' => $category->icon,
                'display_order' => $category->display_order,
                'aktiflik_durumu' => $category->aktiflik_durumu,
            ])
            ->toArray();
    }

    /**
     * Export master templates
     */
    public function exportTemplates(): array
    {
        return MasterTemplate::all()
            ->map(fn($template) => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'description' => $template->description,
                'feature_ids' => $template->feature_ids,
                'metadata' => $template->metadata,
                'aktiflik_durumu' => $template->aktiflik_durumu,
                'display_order' => $template->display_order,
            ])
            ->toArray();
    }

    /**
     * Export feature assignments
     */
    public function exportAssignments(): array
    {
        return FeatureAssignment::with(['feature'])
            ->where('assignable_type', IlanKategori::class)
            ->get()
            ->map(fn($assignment) => [
                'feature_id' => $assignment->feature_id,
                'feature_slug' => $assignment->feature?->slug,
                'category_id' => $assignment->assignable_id,
                'display_order' => $assignment->display_order,
                'is_required' => $assignment->is_required ?? false,
                'custom_label' => $assignment->custom_label,
            ])
            ->toArray();
    }

    /**
     * Get export statistics
     */
    private function getExportStatistics(): array
    {
        return [
            'total_features' => Feature::count(),
            'active_features' => Feature::where('aktiflik_durumu', true)->count(), // context7-ignore
            'total_categories' => FeatureCategory::count(),
            'total_templates' => MasterTemplate::count(),
            'total_assignments' => FeatureAssignment::where('assignable_type', IlanKategori::class)->count(),
            'categories_with_features' => FeatureAssignment::where('assignable_type', IlanKategori::class)
                ->select('assignable_id')
                ->distinct()
                ->count(),
        ];
    }

    /**
     * Export to downloadable JSON file
     */
    public function exportToFile(string $type = 'all'): string
    {
        $data = match ($type) {
            'features' => ['version' => self::EXPORT_VERSION, 'features' => $this->exportFeatures()],
            'templates' => ['version' => self::EXPORT_VERSION, 'templates' => $this->exportTemplates()],
            'assignments' => ['version' => self::EXPORT_VERSION, 'assignments' => $this->exportAssignments()],
            default => $this->exportAll(),
        };

        $filename = "ups_export_{$type}_" . now()->format('Y-m-d_His') . '.json';
        $path = "exports/{$filename}";

        Storage::disk('local')->put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }

    /**
     * Import UPS configuration from JSON
     */
    public function import(array $data, array $options = []): array
    {
        $results = [
            'success' => true,
            'features' => ['added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []],
            'groups' => ['added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []],
            'templates' => ['added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []],
            'assignments' => ['added' => 0, 'skipped' => 0, 'errors' => []],
        ];

        $mode = $options['mode'] ?? 'merge'; // merge, replace, skip_existing
        $dryRun = $options['dry_run'] ?? false;

        DB::beginTransaction();

        try {
            // Import feature categories first
            if (isset($data['feature_categories']) && ($options['import_categories'] ?? true)) {
                $results['categories'] = $this->importFeatureCategories($data['feature_categories'], $mode, $dryRun);
            }

            // Import features
            if (isset($data['features']) && ($options['import_features'] ?? true)) {
                $results['features'] = $this->importFeatures($data['features'], $mode, $dryRun);
            }

            // Import templates
            if (isset($data['templates']) && ($options['import_templates'] ?? true)) {
                $results['templates'] = $this->importTemplates($data['templates'], $mode, $dryRun);
            }

            // Import assignments
            if (isset($data['assignments']) && ($options['import_assignments'] ?? true)) {
                $results['assignments'] = $this->importAssignments($data['assignments'], $mode, $dryRun);
            }

            if ($dryRun) {
                DB::rollBack();
                $results['dry_run'] = true;
            } else {
                DB::commit();
                $this->cacheService->invalidateAll();
            }

            Log::channel('daily')->info('UPS configuration imported', [
                'mode' => $mode,
                'dry_run' => $dryRun,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            $results['success'] = false;
            $results['error'] = $e->getMessage();

            Log::channel('daily')->error('UPS import failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }

        return $results;
    }

    /**
     * Import from uploaded file
     */
    public function importFromFile(UploadedFile $file, array $options = []): array
    {
        $content = file_get_contents($file->getRealPath());
        $data = json_decode($content, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return [
                'success' => false,
                'error' => 'Geçersiz JSON formatı: ' . json_last_error_msg(),
            ];
        }

        // Validate version
        if (isset($data['version']) && version_compare($data['version'], self::EXPORT_VERSION, '>')) {
            return [
                'success' => false,
                'error' => "Desteklenmeyen versiyon: {$data['version']}. Maksimum desteklenen: " . self::EXPORT_VERSION,
            ];
        }

        return $this->import($data, $options);
    }

    /**
     * Import feature categories
     */
    private function importFeatureCategories(array $categories, string $mode, bool $dryRun): array
    {
        $results = ['added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($categories as $categoryData) {
            try {
                $existing = FeatureCategory::where('slug', $categoryData['slug'])->first();

                if ($existing) {
                    if ($mode === 'skip_existing') {
                        $results['skipped']++;
                        continue;
                    }

                    if (!$dryRun && in_array($mode, ['merge', 'replace'])) {
                        $existing->update([
                            'name' => $categoryData['name'],
                            'description' => $categoryData['description'] ?? null,
                            'icon' => $categoryData['icon'] ?? null,
                            'display_order' => $categoryData['display_order'] ?? 0,
                            'aktiflik_durumu' => $categoryData['aktiflik_durumu'] ?? true,
                        ]);
                    }
                    $results['updated']++;
                } else {
                    if (!$dryRun) {
                        FeatureCategory::create([
                            'name' => $categoryData['name'],
                            'slug' => $categoryData['slug'],
                            'description' => $categoryData['description'] ?? null,
                            'icon' => $categoryData['icon'] ?? null,
                            'display_order' => $categoryData['display_order'] ?? 0,
                            'aktiflik_durumu' => $categoryData['aktiflik_durumu'] ?? true,
                        ]);
                    }
                    $results['added']++;
                }
            } catch (\Exception $e) {
                report($e);
                $results['errors'][] = "Category '{$categoryData['slug']}': {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Import features
     */
    private function importFeatures(array $features, string $mode, bool $dryRun): array
    {
        $results = ['added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        // Build category slug to ID map
        $categoryMap = FeatureCategory::pluck('id', 'slug')->toArray();

        foreach ($features as $featureData) {
            try {
                $existing = Feature::where('slug', $featureData['slug'])->first();

                $categoryId = null;
                if (isset($featureData['category_slug']) && isset($categoryMap[$featureData['category_slug']])) {
                    $categoryId = $categoryMap[$featureData['category_slug']];
                }

                if ($existing) {
                    if ($mode === 'skip_existing') {
                        $results['skipped']++;
                        continue;
                    }

                    if (!$dryRun && in_array($mode, ['merge', 'replace'])) {
                        $existing->update([
                            'name' => $featureData['name'],
                            'description' => $featureData['description'] ?? null,
                            'type' => $featureData['type'] ?? 'text', // context7-ignore
                            'options' => $featureData['options'] ?? null,
                            'aktiflik_durumu' => $featureData['aktiflik_durumu'] ?? true,
                            'display_order' => $featureData['display_order'] ?? 0,
                            'feature_category_id' => $categoryId,
                            'icon' => $featureData['icon'] ?? null,
                        ]);
                    }
                    $results['updated']++;
                } else {
                    if (!$dryRun) {
                        Feature::create([
                            'name' => $featureData['name'],
                            'slug' => $featureData['slug'],
                            'description' => $featureData['description'] ?? null,
                            'type' => $featureData['type'] ?? 'text', // context7-ignore
                            'options' => $featureData['options'] ?? null,
                            'aktiflik_durumu' => $featureData['aktiflik_durumu'] ?? true,
                            'display_order' => $featureData['display_order'] ?? 0,
                            'feature_category_id' => $categoryId,
                            'icon' => $featureData['icon'] ?? null,
                        ]);
                    }
                    $results['added']++;
                }
            } catch (\Exception $e) {
                report($e);
                $results['errors'][] = "Feature '{$featureData['slug']}': {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Import templates
     */
    private function importTemplates(array $templates, string $mode, bool $dryRun): array
    {
        $results = ['added' => 0, 'updated' => 0, 'skipped' => 0, 'errors' => []];

        foreach ($templates as $templateData) {
            try {
                $existing = MasterTemplate::where('slug', $templateData['slug'])->first();

                if ($existing) {
                    if ($mode === 'skip_existing') {
                        $results['skipped']++;
                        continue;
                    }

                    if (!$dryRun && in_array($mode, ['merge', 'replace'])) {
                        $existing->update([
                            'name' => $templateData['name'],
                            'description' => $templateData['description'] ?? null,
                            'feature_ids' => $templateData['feature_ids'] ?? [],
                            'metadata' => $templateData['metadata'] ?? [],
                            'aktiflik_durumu' => $templateData['aktiflik_durumu'] ?? true,
                            'display_order' => $templateData['display_order'] ?? 0,
                        ]);
                    }
                    $results['updated']++;
                } else {
                    if (!$dryRun) {
                        MasterTemplate::create([
                            'name' => $templateData['name'],
                            'slug' => $templateData['slug'],
                            'description' => $templateData['description'] ?? null,
                            'feature_ids' => $templateData['feature_ids'] ?? [],
                            'metadata' => $templateData['metadata'] ?? [],
                            'aktiflik_durumu' => $templateData['aktiflik_durumu'] ?? true,
                            'display_order' => $templateData['display_order'] ?? 0,
                            'created_by' => auth()->id(),
                        ]);
                    }
                    $results['added']++;
                }
            } catch (\Exception $e) {
                report($e);
                $results['errors'][] = "Template '{$templateData['slug']}': {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Import assignments
     */
    private function importAssignments(array $assignments, string $mode, bool $dryRun): array
    {
        $results = ['added' => 0, 'skipped' => 0, 'errors' => []];

        // Build feature slug to ID map
        $featureMap = Feature::pluck('id', 'slug')->toArray();

        // If replace mode, delete existing assignments first
        if ($mode === 'replace' && !$dryRun) {
            FeatureAssignment::where('assignable_type', IlanKategori::class)->delete();
        }

        foreach ($assignments as $assignmentData) {
            try {
                // Resolve feature ID from slug if provided
                $featureId = $assignmentData['feature_id'];
                if (isset($assignmentData['feature_slug']) && isset($featureMap[$assignmentData['feature_slug']])) {
                    $featureId = $featureMap[$assignmentData['feature_slug']];
                }

                // Check if category exists
                $categoryExists = IlanKategori::where('id', $assignmentData['category_id'])->exists();
                if (!$categoryExists) {
                    $results['errors'][] = "Category ID {$assignmentData['category_id']} not found";
                    continue;
                }

                // Check if feature exists
                $featureExists = Feature::where('id', $featureId)->exists();
                if (!$featureExists) {
                    $results['errors'][] = "Feature ID {$featureId} not found";
                    continue;
                }

                // Check for existing assignment
                $existing = FeatureAssignment::where('feature_id', $featureId)
                    ->where('assignable_type', IlanKategori::class)
                    ->where('assignable_id', $assignmentData['category_id'])
                    ->first();

                if ($existing) {
                    $results['skipped']++;
                    continue;
                }

                if (!$dryRun) {
                    FeatureAssignment::create([
                        'feature_id' => $featureId,
                        'assignable_type' => IlanKategori::class,
                        'assignable_id' => $assignmentData['category_id'],
                        'display_order' => $assignmentData['display_order'] ?? 0,
                        'is_required' => $assignmentData['is_required'] ?? false,
                        'custom_label' => $assignmentData['custom_label'] ?? null,
                    ]);
                }
                $results['added']++;
            } catch (\Exception $e) {
                report($e);
                $results['errors'][] = "Assignment: {$e->getMessage()}";
            }
        }

        return $results;
    }

    /**
     * Preview import without making changes
     */
    public function previewImport(array $data): array
    {
        return $this->import($data, ['dry_run' => true]);
    }

    /**
     * Validate import data structure
     */
    public function validateImportData(array $data): array
    {
        $errors = [];

        // Check version
        if (isset($data['version'])) {
            if (version_compare($data['version'], self::EXPORT_VERSION, '>')) {
                $errors[] = "Desteklenmeyen versiyon: {$data['version']}";
            }
        }

        // Validate features structure
        if (isset($data['features'])) {
            foreach ($data['features'] as $index => $feature) {
                if (!isset($feature['ad']) || !isset($feature['slug'])) {
                    $errors[] = "Feature #{$index}: 'ad' ve 'slug' alanları zorunlu";
                }
            }
        }

        // Validate categories structure
        if (isset($data['feature_categories'])) {
            foreach ($data['feature_categories'] as $index => $category) {
                if (!isset($category['name']) || !isset($category['slug'])) {
                    $errors[] = "Category #{$index}: 'name' ve 'slug' alanları zorunlu";
                }
            }
        }

        // Validate templates structure
        if (isset($data['templates'])) {
            foreach ($data['templates'] as $index => $template) {
                if (!isset($template['name']) || !isset($template['slug'])) {
                    $errors[] = "Template #{$index}: 'name' ve 'slug' alanları zorunlu";
                }
            }
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors,
            'summary' => [
                'features' => count($data['features'] ?? []),
                'categories' => count($data['feature_categories'] ?? []),
                'templates' => count($data['templates'] ?? []),
                'assignments' => count($data['assignments'] ?? []),
            ],
        ];
    }

    /**
     * Get list of available export files
     */
    public function getExportFiles(): Collection
    {
        $files = Storage::disk('local')->files('exports');

        return collect($files)
            ->filter(fn($file) => str_ends_with($file, '.json'))
            ->map(function ($file) {
                /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
                $disk = Storage::disk('local');
                $fullPath = $disk->path($file);
                return [
                    'path' => $file,
                    'filename' => basename($file),
                    'size' => $disk->size($file),
                    'created_at' => date('Y-m-d H:i:s', filemtime($fullPath)),
                ];
            })
            ->sortByDesc('created_at')
            ->values();
    }

    /**
     * Delete an export file
     */
    public function deleteExportFile(string $path): bool
    {
        if (!str_starts_with($path, 'exports/') || !str_ends_with($path, '.json')) {
            return false;
        }

        return Storage::disk('local')->delete($path);
    }
}
