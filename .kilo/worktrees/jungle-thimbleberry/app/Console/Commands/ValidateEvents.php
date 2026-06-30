<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Event System Validation Command
 *
 * Context7 Standard: C7-EVENT-VALIDATION-2025-12-06
 * Yalıhan Bekçi: Temiz, düzenli, merkezi yönetim
 *
 * Event tanımlarını ve listener mapping'lerini validate eder.
 */
class ValidateEvents extends Command
{
    protected $signature = 'events:validate';

    protected $description = 'Validate event definitions and listener mappings';

    public function handle(): int
    {
        $this->info('🔍 Event System Validation Başlatılıyor...');
        $this->newLine();

        $errors = [];
        $warnings = [];

        // 1. Config dosyasını kontrol et
        $definitions = config('events.definitions', []);

        if (empty($definitions)) {
            $errors[] = 'Event definitions boş!';
            $this->error('❌ Event definitions bulunamadı');
            return 1;
        }

        $this->info('✅ Event definitions yüklendi: ' . count($definitions) . ' event');

        // 2. Her event tanımını kontrol et
        foreach ($definitions as $eventKey => $definition) {
            $this->line("  📋 Event: {$eventKey}");

            // Class kontrolü
            if (!isset($definition['class'])) {
                $errors[] = "Event '{$eventKey}' için class tanımlı değil";
                $this->error("    ❌ Class tanımlı değil");
                continue;
            }

            $class = $definition['class'];

            // Class var mı kontrol et
            if (!class_exists($class)) {
                $errors[] = "Event '{$eventKey}' için class bulunamadı: {$class}";
                $this->error("    ❌ Class bulunamadı: {$class}");
                continue;
            }

            $this->info("    ✅ Class: {$class}");

            // Listener kontrolü
            if (isset($definition['listeners']) && is_array($definition['listeners'])) {
                foreach ($definition['listeners'] as $listener) {
                    if (!class_exists($listener)) {
                        $warnings[] = "Event '{$eventKey}' için listener bulunamadı: {$listener}";
                        $this->warn("    ⚠️  Listener bulunamadı: {$listener}");
                    } else {
                        $this->info("    ✅ Listener: {$listener}");
                    }
                }
            }

            // Category kontrolü
            if (!isset($definition['category'])) {
                $warnings[] = "Event '{$eventKey}' için category tanımlı değil";
                $this->warn("    ⚠️  Category tanımlı değil");
            } else {
                $category = $definition['category'];
                $categories = config('events.categories', []);
                if (!isset($categories[$category])) {
                    $warnings[] = "Event '{$eventKey}' için category tanımlı değil: {$category}";
                    $this->warn("    ⚠️  Category tanımlı değil: {$category}");
                }
            }
        }

        // 3. EventServiceProvider ile karşılaştır
        $this->newLine();
        $this->info('🔍 EventServiceProvider kontrol ediliyor...');

        $eventServiceProvider = app_path('Providers/EventServiceProvider.php');
        if (File::exists($eventServiceProvider)) {
            $content = File::get($eventServiceProvider);
            
            foreach ($definitions as $eventKey => $definition) {
                $class = $definition['class'];
                
                // EventServiceProvider'da bu event var mı?
                if (strpos($content, $class) === false) {
                    $warnings[] = "Event '{$eventKey}' EventServiceProvider'da tanımlı değil";
                    $this->warn("    ⚠️  EventServiceProvider'da tanımlı değil: {$class}");
                }
            }
        }

        // 4. Sonuçları göster
        $this->newLine();
        $this->info('📊 Validation Sonuçları:');
        $this->newLine();

        if (empty($errors) && empty($warnings)) {
            $this->info('✅ Tüm event tanımları geçerli!');
            return 0;
        }

        if (!empty($errors)) {
            $this->error('❌ Hatalar:');
            foreach ($errors as $error) {
                $this->error("  - {$error}");
            }
            $this->newLine();
        }

        if (!empty($warnings)) {
            $this->warn('⚠️  Uyarılar:');
            foreach ($warnings as $warning) {
                $this->warn("  - {$warning}");
            }
            $this->newLine();
        }

        return empty($errors) ? 0 : 1;
    }
}

