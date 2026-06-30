<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class ValidateValidationRules extends Command
{
    protected $signature = 'validation-rules:validate';
    protected $description = 'Validate validation rules against database schema';

    public function handle()
    {
        $this->info('🔍 Validation Rules validation başlatılıyor...');

        $validationRules = config('validation-rules', []);
        $errors = [];
        $warnings = [];

        // Tüm validation rules'ları kontrol et
        $this->validateRules($validationRules, $errors, $warnings);

        // Sonuçları göster
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Tüm validation rules geçerli!');
            return 0;
        }

        if (!empty($errors)) {
            $this->error('❌ Hatalar bulundu:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Uyarılar:');
            foreach ($warnings as $warning) {
                $this->warn("  - {$warning}");
            }
        }

        return 1;
    }

    protected function validateRules(array $rules, array &$errors, array &$warnings, string $prefix = '')
    {
        foreach ($rules as $key => $value) {
            $currentPath = $prefix ? "{$prefix}.{$key}" : $key;

            // 'hints' ve 'common' gibi özel key'leri atla
            if (in_array($key, ['hints', 'common'])) {
                continue;
            }

            if (is_array($value)) {
                // Validation rules array'i kontrol et
                if (isset($value[0]) && is_string($value[0])) {
                    // Bu bir validation rules array'i değil, nested structure
                    $this->validateRules($value, $errors, $warnings, $currentPath);
                } else {
                    // Validation rules kontrolü
                    foreach ($value as $field => $rule) {
                        $this->validateRule($field, $rule, $errors, $warnings, "{$currentPath}.{$field}");
                    }
                }
            }
        }
    }

    protected function validateRule(string $field, $rule, array &$errors, array &$warnings, string $path)
    {
        if (!is_string($rule)) {
            return;
        }

        $rules = explode('|', $rule);

        foreach ($rules as $singleRule) {
            $parts = explode(':', $singleRule);
            $ruleName = $parts[0];

            // Exists rule kontrolü
            if ($ruleName === 'exists') {
                if (!isset($parts[1])) {
                    $errors[] = "Exists rule'da table belirtilmemiş: {$path}";
                    continue;
                }

                $tableInfo = explode(',', $parts[1]);
                $table = $tableInfo[0];

                if (!Schema::hasTable($table)) {
                    $warnings[] = "Table mevcut değil: {$table} ({$path})";
                } elseif (isset($tableInfo[1])) {
                    $column = $tableInfo[1];
                    if (!Schema::hasColumn($table, $column)) {
                        $warnings[] = "Column mevcut değil: {$table}.{$column} ({$path})";
                    }
                }
            }

            // Unique rule kontrolü
            if ($ruleName === 'unique') {
                if (!isset($parts[1])) {
                    $errors[] = "Unique rule'da table belirtilmemiş: {$path}";
                    continue;
                }

                $tableInfo = explode(',', $parts[1]);
                $table = $tableInfo[0];

                if (!Schema::hasTable($table)) {
                    $warnings[] = "Table mevcut değil: {$table} ({$path})";
                } elseif (isset($tableInfo[1])) {
                    $column = $tableInfo[1];
                    if (!Schema::hasColumn($table, $column)) {
                        $warnings[] = "Column mevcut değil: {$table}.{$column} ({$path})";
                    }
                }
            }

            // In rule kontrolü
            if ($ruleName === 'in') {
                if (!isset($parts[1])) {
                    $errors[] = "In rule'da değerler belirtilmemiş: {$path}";
                }
            }
        }
    }
}

