<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use App\Contracts\TemplateResolverInterface;

Artisan::command('test:resolver', function () {
    $resolver = app(TemplateResolverInterface::class);

    $this->info('✅ TemplateResolver Service Loaded');
    $this->info('Class: ' . get_class($resolver));
    $this->newLine();

    // Test 1: Resolve existing template (Konut Satılık)
    $this->info('🔍 Test 1: Resolve Konut Satılık');
    try {
        $template = $resolver->resolve(1, 'Satılık');
        if ($template) {
            $this->info('  ✅ Template found: ID ' . $template->id);
            $this->info('  Kategori ID: ' . $template->kategori_id);
            $this->info('  Yayın Tipi: ' . $template->yayin_tipi);
        } else {
            $this->warn('  ❌ Template not found');
        }
    } catch (Exception $e) {
        $this->error('  ❌ Error: ' . $e->getMessage());
    }

    $this->newLine();

    // Test 2: Resolve non-existent template
    $this->info('🔍 Test 2: Resolve non-existent template');
    $template = $resolver->resolve(1, 'NonExistent');
    if ($template === null) {
        $this->info('  ✅ Correctly returned NULL');
    } else {
        $this->warn('  ❌ Should have returned NULL');
    }

    $this->newLine();

    // Test 3: Check exists
    $this->info('🔍 Test 3: Check template existence');
    $exists = $resolver->exists(1, 'Satılık');
    $this->info('  Konut Satılık exists: ' . ($exists ? 'YES ✅' : 'NO ❌'));
    $exists = $resolver->exists(1, 'NonExistent');
    $this->info('  Konut NonExistent exists: ' . ($exists ? 'YES ❌' : 'NO ✅'));

    $this->newLine();

    // Test 4: Get all templates for category
    $this->info('🔍 Test 4: Get all templates for Konut (ID: 1)');
    $templates = $resolver->getTemplatesForCategory(1);
    $this->info('  Found ' . $templates->count() . ' templates');
    foreach ($templates->take(3) as $t) {
        $this->info('    - ' . $t->yayin_tipi . ' (ID: ' . $t->id . ')');
    }
})->purpose('Test Template Resolver implementation');
