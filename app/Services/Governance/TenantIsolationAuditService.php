<?php

declare(strict_types=1);

namespace App\Services\Governance;

use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Read-only source audit for tenant-bound Eloquent models.
 *
 * This deliberately does not connect to a database and never changes schema,
 * data, model traits, or migrations. Runtime and production verification are
 * separate gates.
 */
final class TenantIsolationAuditService
{
    public function __construct(
        private readonly ?string $modelsPath = null,
        private readonly ?string $migrationsPath = null,
        private readonly ?array $globalTables = null,
        private readonly ?Filesystem $files = null,
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function audit(): array
    {
        $migrationInventory = $this->migrationInventory();
        $globalTables = array_fill_keys(
            $this->globalTables ?? (function_exists('config')
                ? config('tenant-isolation.global_tables', [])
                : []),
            true
        );
        $findings = [];
        $models = $this->modelInventory();

        foreach ($models as $model) {
            $table = $model['table'];

            if ($table === null) {
                $findings[] = $this->finding(
                    'MODEL_TABLE_UNKNOWN',
                    'review',
                    $model,
                    'Model tablosu statik olarak çözülemedi; tenant kapsamı bağımsız doğrulanmalı.'
                );
                continue;
            }

            $hasTenantColumn = $migrationInventory['tenant_tables'][$table] ?? false;
            $isGlobal = isset($globalTables[$table]);

            if ($hasTenantColumn && !$model['belongs_to_tenant']) {
                $findings[] = $this->finding(
                    'TENANT_COLUMN_WITHOUT_SCOPE',
                    'blocking',
                    $model,
                    "{$table} tenant_id içeriyor ancak BelongsToTenant kullanmıyor."
                );
                continue;
            }

            if ($model['belongs_to_tenant'] && !$hasTenantColumn) {
                $findings[] = $this->finding(
                    'TENANT_SCOPE_WITHOUT_COLUMN',
                    'blocking',
                    $model,
                    "{$table} BelongsToTenant kullanıyor ancak migration envanterinde tenant_id bulunamadı."
                );
                continue;
            }

            if (!$isGlobal && !$hasTenantColumn && !$model['belongs_to_tenant']) {
                $findings[] = $this->finding(
                    $model['country_scope']
                        ? 'COUNTRY_SCOPE_ONLY_REVIEW'
                        : 'TENANT_BOUNDARY_REVIEW',
                    'review',
                    $model,
                    $model['country_scope']
                        ? "{$table} yalnızca HasCountryScope kullanıyor; tenant kapsamı karar bekliyor."
                        : "{$table} global allowlist dışında ve tenant kapsamı bulunamadı."
                );
            }
        }

        $blocking = array_values(array_filter(
            $findings,
            static fn (array $finding): bool => $finding['severity'] === 'blocking'
        ));

        return [
            'audit' => 'tenant-isolation',
            'mode' => 'source-read-only',
            'production_verified' => false,
            'models_scanned' => count($models),
            'migration_tables_scanned' => count($migrationInventory['tables']),
            'tenant_tables_detected' => count($migrationInventory['tenant_tables']),
            'global_tables_allowlisted' => array_keys($globalTables),
            'blocking_count' => count($blocking),
            'review_count' => count($findings) - count($blocking),
            'findings' => $findings,
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function modelInventory(): array
    {
        $basePath = $this->applicationBasePath();
        $paths = $this->modelsPath !== null
            ? [$this->modelsPath]
            : [
                $basePath . DIRECTORY_SEPARATOR . 'app/Models',
                $basePath . DIRECTORY_SEPARATOR . 'app/Modules',
            ];

        if (array_filter($paths, 'is_dir') === []) {
            return [];
        }

        $files = $this->files ?? new Filesystem();
        $models = [];
        foreach ($paths as $path) {
            if (!is_dir($path)) {
                continue;
            }

            foreach ($files->allFiles($path) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $content = $files->get($file->getPathname());
                if (!preg_match('/\bclass\s+([A-Za-z_][A-Za-z0-9_]*)\b/', $content, $classMatch)) {
                    continue;
                }

                if (!preg_match('/\bclass\s+\w+\s+extends\s+[A-Za-z_\\\\][A-Za-z0-9_\\\\]*(?:Model|Pivot)\b/', $content)) {
                    continue;
                }

                $namespace = '';
                if (preg_match('/\bnamespace\s+([^;]+);/', $content, $namespaceMatch)) {
                    $namespace = trim($namespaceMatch[1]);
                }

                $class = $namespace !== ''
                    ? $namespace . '\\' . $classMatch[1]
                    : $classMatch[1];

                $table = null;
                if (preg_match('/protected\s+\$table\s*=\s*[\'\"]([^\'\"]+)[\'\"]/', $content, $tableMatch)) {
                    $table = $tableMatch[1];
                } else {
                    $table = Str::snake(Str::pluralStudly($classMatch[1]));
                }

                $models[] = [
                    'model' => $class,
                    'file' => ltrim(str_replace($basePath, '', $file->getPathname()), DIRECTORY_SEPARATOR),
                    'table' => $table,
                    'belongs_to_tenant' => str_contains($content, 'BelongsToTenant'),
                    'country_scope' => str_contains($content, 'HasCountryScope'),
                ];
            }
        }

        return $models;
    }

    /**
     * @return array{tables: array<string, bool>, tenant_tables: array<string, bool>}
     */
    private function migrationInventory(): array
    {
        $basePath = $this->applicationBasePath();
        $path = $this->migrationsPath ?? $basePath . DIRECTORY_SEPARATOR . 'database/migrations';
        $tables = [];
        $tenantTables = [];
        $files = $this->files ?? new Filesystem();

        if (!is_dir($path)) {
            return ['tables' => $tables, 'tenant_tables' => $tenantTables];
        }

        foreach ($files->allFiles($path) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $content = $files->get($file->getPathname());
            preg_match_all(
                '/Schema::(?:create|createIfNotExists|table)\(\s*[\'\"]([^\'\"]+)[\'\"]/i',
                $content,
                $matches,
                PREG_OFFSET_CAPTURE
            );

            $definitions = $matches[1] ?? [];
            foreach ($definitions as $index => [$table, $offset]) {
                $tables[$table] = true;
                $nextOffset = $definitions[$index + 1][1] ?? strlen($content);
                $definition = substr($content, $offset, $nextOffset - $offset);

                if (preg_match('/\btenant_id\b/i', $definition)) {
                    $tenantTables[$table] = true;
                }
            }
        }

        return ['tables' => $tables, 'tenant_tables' => $tenantTables];
    }

    private function applicationBasePath(): string
    {
        if (function_exists('app') && app() instanceof \Illuminate\Foundation\Application) {
            return base_path();
        }

        return getcwd();
    }

    /**
     * @param array<string, mixed> $model
     * @return array<string, mixed>
     */
    private function finding(string $rule, string $severity, array $model, string $message): array
    {
        return [
            'rule' => $rule,
            'severity' => $severity,
            'model' => $model['model'],
            'table' => $model['table'],
            'file' => $model['file'],
            'message' => $message,
        ];
    }
}
