<?php

/**
 * Yalıhan Frontend Tema Konfigürasyonu
 *
 * Her tema bir CSS custom property (CSS variable) paketidir.
 * layouts/frontend.blade.php → ThemeService::getCssVars() ile :root bloğuna enjekte edilir.
 *
 * Yeni tema eklemek için bu dosyaya yeni bir anahtar-dizi eklemek yeterlidir.
 * SAB: Bu dosya yalnızca veri içerir, iş mantığı içermez.
 */

return [

    // ──────────────────────────────────────────────────────────────────
    // TEMA 1: Mediterranean (Varsayılan)
    // Bodrum'un eşsiz Akdeniz mirasından ilham alan lüks navy/gold palet
    // ──────────────────────────────────────────────────────────────────
    'mediterranean' => [
        'label'       => 'Mediterranean',
        'description' => 'Derin lacivert ve altın — Bodrum\'un klasik lüks kimliği',
        'preview' => [
            'primary'    => '#0A1628',
            'accent'     => '#C9A84C',
            'background' => '#F5F2ED',
            'surface'    => '#FFFFFF',
        ],
        'vars' => [
            '--navy'         => '#0A1628',
            '--navy-mid'     => '#0F1E38',
            '--navy-light'   => '#162240',
            '--gold'         => '#C9A84C',
            '--gold-light'   => '#D4B96A',
            '--gold-dim'     => 'rgba(201,168,76,0.15)',
            '--cream'        => '#F8F6F1',
            '--cream-border' => '#E8E2D8',
            '--cream-text'   => '#F5F0E8',
            '--text-primary' => '#0A1628',
            '--text-muted'   => '#6B7280',
        ],
    ],

    // ──────────────────────────────────────────────────────────────────
    // TEMA 2: Dark Luxury
    // Saf siyah zemin + parlak altın — ultra-premium karanlık deneyim
    // ──────────────────────────────────────────────────────────────────
    'dark_luxury' => [
        'label'       => 'Dark Luxury',
        'description' => 'Siyah zemin, parlak altın — gece atmosferi premium deneyim',
        'preview' => [
            'primary'    => '#000000',
            'accent'     => '#FFD700',
            'background' => '#111111',
            'surface'    => '#1A1A1A',
        ],
        'vars' => [
            '--navy'         => '#000000',
            '--navy-mid'     => '#0D0D0D',
            '--navy-light'   => '#1A1A1A',
            '--gold'         => '#FFD700',
            '--gold-light'   => '#FFE44D',
            '--gold-dim'     => 'rgba(255,215,0,0.12)',
            '--cream'        => '#111111',
            '--cream-border' => '#2A2A2A',
            '--cream-text'   => '#F0E8C8',
            '--text-primary' => '#F0E8C8',
            '--text-muted'   => '#8A8A8A',
        ],
    ],

    // ──────────────────────────────────────────────────────────────────
    // TEMA 3: Minimal White
    // Beyaz zemin + kömür + terracotta — modern minimalist Ege estetiği
    // ──────────────────────────────────────────────────────────────────
    'minimal_white' => [
        'label'       => 'Minimal White',
        'description' => 'Beyaz & kömür, terracotta aksanlar — çağdaş minimalist',
        'preview' => [
            'primary'    => '#1C1C2E',
            'accent'     => '#C8553D',
            'background' => '#FAFAFA',
            'surface'    => '#FFFFFF',
        ],
        'vars' => [
            '--navy'         => '#1C1C2E',
            '--navy-mid'     => '#2D2D44',
            '--navy-light'   => '#3D3D5C',
            '--gold'         => '#C8553D',
            '--gold-light'   => '#DE7A65',
            '--gold-dim'     => 'rgba(200,85,61,0.12)',
            '--cream'        => '#FAFAFA',
            '--cream-border' => '#E5E7EB',
            '--cream-text'   => '#F5F5F5',
            '--text-primary' => '#1C1C2E',
            '--text-muted'   => '#6B7280',
        ],
    ],


    // ──────────────────────────────────────────────────────────────────
    // TEMA 4: Blue Modern
    // Beyaz zemin + mavi — MyHome tarzı temiz ve profesyonel görünüm
    // ──────────────────────────────────────────────────────────────────
    'blue_modern' => [
        'label'       => 'Blue Modern',
        'description' => 'Beyaz zemin, güven veren mavi — modern profesyonel',
        'preview' => [
            'primary'    => '#1D4ED8',
            'accent'     => '#2563EB',
            'background' => '#F9FAFB',
            'surface'    => '#FFFFFF',
        ],
        'vars' => [
            '--navy'         => '#1D4ED8',
            '--navy-mid'     => '#1E40AF',
            '--navy-light'   => '#2563EB',
            '--gold'         => '#2563EB',
            '--gold-light'   => '#3B82F6',
            '--gold-dim'     => 'rgba(37,99,235,0.10)',
            '--cream'        => '#F9FAFB',
            '--cream-border' => '#E5E7EB',
            '--cream-text'   => '#EFF6FF',
            '--text-primary' => '#111827',
            '--text-muted'   => '#6B7280',
        ],
    ],


    // ──────────────────────────────────────────────────────────────────
    // TEMA 5: Propertius Modern ← AKTİF TEMA
    // DESIGN.md / "Ana Sayfa" klasörü tasarımı
    // Corporate Modern: beyaz/lavanta zemin + derin mavi + Manrope
    // ──────────────────────────────────────────────────────────────────
    'propertius' => [
        'label'       => 'Propertius Modern',
        'description' => 'Kurumsal modernizm — güven, netlik, mimari hassasiyet',
        'preview' => [
            'primary'    => '#004ac6',
            'accent'     => '#2563eb',
            'background' => '#faf8ff',
            'surface'    => '#ffffff',
        ],
        'vars' => [
            // Ana renkler
            '--primary'              => '#004ac6',
            '--primary-container'    => '#2563eb',
            '--on-primary'           => '#ffffff',
            '--on-primary-container' => '#eeefff',

            // Yüzey sistemi
            '--surface'              => '#faf8ff',
            '--surface-low'          => '#f3f3fe',
            '--surface-container'    => '#ededf9',
            '--surface-high'         => '#e7e7f3',
            '--surface-highest'      => '#e1e2ed',
            '--surface-white'        => '#ffffff',
            '--surface-muted'        => '#F8FAFC',

            // Metin
            '--on-surface'           => '#191b23',
            '--on-surface-variant'   => '#434655',
            '--text-muted'           => '#737686',

            // Kenarlık
            '--outline'              => '#737686',
            '--outline-variant'      => '#c3c6d7',
            '--border-subtle'        => '#E2E8F0',

            // Durum renkleri
            '--status-sale'          => '#10B981',
            '--status-rent'          => '#F59E0B',
            '--status-sold'          => '#EF4444',

            // Secondary
            '--secondary'            => '#565e74',
            '--secondary-container'  => '#dae2fd',

            // Geriye dönük uyumluluk (eski var adları)
            '--navy'                 => '#004ac6',
            '--navy-mid'             => '#003ea8',
            '--navy-light'           => '#2563eb',
            '--gold'                 => '#2563eb',
            '--gold-light'           => '#dbe1ff',
            '--gold-dim'             => 'rgba(0,74,198,0.10)',
            '--cream'                => '#faf8ff',
            '--cream-border'         => '#c3c6d7',
            '--cream-text'           => '#eeefff',
            '--text-primary'         => '#191b23',
        ],
    ],

];
