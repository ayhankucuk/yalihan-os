@props([
    'status' => null,
    'value' => null,
    'category' => null,
    'size' => 'sm',
])

@php
    $rawValue = $status ?? $value ?? 'taslak';

    if (is_array($rawValue)) {
        $rawValue = implode(' ', $rawValue);
    }

    $rawValue = trim((string) $rawValue);

    // Normalize key using slug
    if ($rawValue !== '') {
        $normalizedKey = \Illuminate\Support\Str::slug($rawValue, '_');
        // Convert to readable label
        $labelText = \Illuminate\Support\Str::of($rawValue)
            ->replace('_', ' ')
            ->replace('-', ' ')
            ->title()
            ->toString();
    } else {
        $normalizedKey = 'taslak';
        $labelText = 'Taslak';
    }

    $categoryKey = $category ? \Illuminate\Support\Str::slug($category, '_') : null;

    // Config'den status renklerini al (varsa)
    $configStatusColors = config('danisman.status_colors', []);

    $statusConfig = array_merge([
        'aktif' => [
            'bg' => 'bg-green-100 dark:bg-green-900',
            'text' => 'text-green-800 dark:text-green-200',
            'label' => 'Aktif',
        ],
        'yayinda' => [
            'bg' => 'bg-green-100 dark:bg-green-900',
            'text' => 'text-green-800 dark:text-green-200',
            'label' => 'Yayında',
        ],
        'onayli' => [
            'bg' => 'bg-green-100 dark:bg-green-900',
            'text' => 'text-green-800 dark:text-green-200',
            'label' => 'Onaylı',
        ],
        'onay_bekliyor' => [
            'bg' => 'bg-yellow-100 dark:bg-yellow-900',
            'text' => 'text-yellow-800 dark:text-yellow-200',
            'label' => 'Onay Bekliyor',
        ],
        'satildi' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900',
            'text' => 'text-blue-800 dark:text-blue-200',
            'label' => 'Satıldı',
        ],
        'kiralandi' => [
            'bg' => 'bg-purple-100 dark:bg-purple-900',
            'text' => 'text-purple-800 dark:text-purple-200',
            'label' => 'Kiralandı',
        ],
        'inceleme' => [
            'bg' => 'bg-yellow-100 dark:bg-yellow-900',
            'text' => 'text-yellow-800 dark:text-yellow-200',
            'label' => 'İnceleme',
        ],
        'beklemede' => [
            'bg' => 'bg-yellow-100 dark:bg-yellow-900',
            'text' => 'text-yellow-800 dark:text-yellow-200',
            'label' => 'Beklemede',
        ],
        'pasif' => [
            'bg' => 'bg-red-100 dark:bg-red-900',
            'text' => 'text-red-800 dark:text-red-200',
            'label' => 'Pasif',
        ],
        'reddedildi' => [
            'bg' => 'bg-red-100 dark:bg-red-900',
            'text' => 'text-red-800 dark:text-red-200',
            'label' => 'Reddedildi',
        ],
        'taslak' => [
            'bg' => 'bg-gray-100 dark:bg-gray-900',
            'text' => 'text-gray-800 dark:text-gray-200',
            'label' => 'Taslak',
        ],
        'arsiv' => [
            'bg' => 'bg-gray-200 dark:bg-gray-800',
            'text' => 'text-gray-700 dark:text-gray-200',
            'label' => 'Arşiv',
        ],
        'arsivlendi' => [
            'bg' => 'bg-gray-200 dark:bg-gray-800',
            'text' => 'text-gray-700 dark:text-gray-200',
            'label' => 'Arşivlendi',
        ],
    ], $configStatusColors);

    $priorityConfig = [
        'kritik' => [
            'bg' => 'bg-rose-100 dark:bg-rose-900',
            'text' => 'text-rose-800 dark:text-rose-200',
            'label' => 'Kritik',
        ],
        'critical' => [
            'bg' => 'bg-rose-100 dark:bg-rose-900',
            'text' => 'text-rose-800 dark:text-rose-200',
            'label' => 'Kritik',
        ],
        'yuksek' => [
            'bg' => 'bg-red-100 dark:bg-red-900',
            'text' => 'text-red-800 dark:text-red-200',
            'label' => 'Yüksek',
        ],
        'high' => [
            'bg' => 'bg-red-100 dark:bg-red-900',
            'text' => 'text-red-800 dark:text-red-200',
            'label' => 'High',
        ],
        'orta' => [
            'bg' => 'bg-amber-100 dark:bg-amber-900',
            'text' => 'text-amber-800 dark:text-amber-200',
            'label' => 'Orta',
        ],
        'medium' => [
            'bg' => 'bg-amber-100 dark:bg-amber-900',
            'text' => 'text-amber-800 dark:text-amber-200',
            'label' => 'Medium',
        ],
        'dusuk' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900',
            'text' => 'text-blue-800 dark:text-blue-200',
            'label' => 'Düşük',
        ],
        'low' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900',
            'text' => 'text-blue-800 dark:text-blue-200',
            'label' => 'Low',
        ],
    ];

    $flagConfig = [
        'one_cikan' => [
            'bg' => 'bg-indigo-100 dark:bg-indigo-900',
            'text' => 'text-indigo-800 dark:text-indigo-200',
            'label' => 'Öne Çıkan',
        ],
        'sabit' => [
            'bg' => 'bg-purple-100 dark:bg-purple-900',
            'text' => 'text-purple-800 dark:text-purple-200',
            'label' => 'Sabit',
        ],
        'son_dakika' => [
            'bg' => 'bg-orange-100 dark:bg-orange-900',
            'text' => 'text-orange-800 dark:text-orange-200',
            'label' => 'Son Dakika',
        ],
        'kampanya' => [
            'bg' => 'bg-teal-100 dark:bg-teal-900',
            'text' => 'text-teal-800 dark:text-teal-200',
            'label' => 'Kampanya',
        ],
    ];

    $infoConfig = [
        'online' => [
            'bg' => 'bg-emerald-100 dark:bg-emerald-900',
            'text' => 'text-emerald-800 dark:text-emerald-200',
            'label' => 'Online',
        ],
        'offline' => [
            'bg' => 'bg-gray-100 dark:bg-gray-900',
            'text' => 'text-gray-800 dark:text-gray-200',
            'label' => 'Offline',
        ],
        'yeni' => [
            'bg' => 'bg-sky-100 dark:bg-sky-900',
            'text' => 'text-sky-800 dark:text-sky-200',
            'label' => 'Yeni',
        ],
        'okunmamis' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900',
            'text' => 'text-blue-800 dark:text-blue-200',
            'label' => 'Okunmamış',
        ],
        'okunmus' => [
            'bg' => 'bg-slate-100 dark:bg-slate-900',
            'text' => 'text-slate-800 dark:text-slate-200',
            'label' => 'Okunmuş',
        ],
    ];

    $typeConfig = [
        'satilik' => [
            'bg' => 'bg-indigo-100 dark:bg-indigo-900',
            'text' => 'text-indigo-800 dark:text-indigo-200',
            'label' => 'Satılık',
        ],
        'kiralik' => [
            'bg' => 'bg-cyan-100 dark:bg-cyan-900',
            'text' => 'text-cyan-800 dark:text-cyan-200',
            'label' => 'Kiralık',
        ],
        'arsa' => [
            'bg' => 'bg-lime-100 dark:bg-lime-900',
            'text' => 'text-lime-800 dark:text-lime-100',
            'label' => 'Arsa',
        ],
        'yazlik' => [
            'bg' => 'bg-orange-100 dark:bg-orange-900',
            'text' => 'text-orange-700 dark:text-orange-200',
            'label' => 'Yazlık',
        ],
        'duyuru' => [
            'bg' => 'bg-amber-100 dark:bg-amber-900',
            'text' => 'text-amber-800 dark:text-amber-200',
            'label' => 'Duyuru',
        ],
        'blog' => [
            'bg' => 'bg-rose-100 dark:bg-rose-900',
            'text' => 'text-rose-800 dark:text-rose-200',
            'label' => 'Blog',
        ],
    ];

    $configSets = [
        'status' => $statusConfig,
        'default' => $statusConfig,
        'priority' => $priorityConfig,
        'flag' => $flagConfig,
        'info' => $infoConfig,
        'type' => $typeConfig,
        'category' => $typeConfig,
    ];

    $activeSet = $configSets[$categoryKey] ?? $statusConfig;
    $config = $activeSet[$normalizedKey] ?? null;

    if (! $config) {
        $config = [
            'bg' => 'bg-gray-100 dark:bg-gray-900',
            'text' => 'text-gray-800 dark:text-gray-200',
            'label' => $labelText ?: 'Bilinmiyor',
        ];
    } else {
        $config['label'] = $config['label'] ?? ($labelText ?: 'Bilinmiyor');
    }

    $sizeClasses = [
        'xs' => 'px-2 py-0.5 text-xs',
        'sm' => 'px-2.5 py-0.5 text-xs',
        'md' => 'px-3 py-1 text-sm',
        'lg' => 'px-4 py-1.5 text-base',
    ];

    $selectedSize = $sizeClasses[$size] ?? $sizeClasses['sm'];
@endphp

<span
    class="inline-flex items-center rounded-full font-medium {{ $config['bg'] }} $config['text'] $selectedSize"
    {{ $attributes }}>
    {{ $config['label'] }}
</span>
