@props([
    'appliesTo' => null,
    'kategori' => null,
])

@php
    // Kategori model'inden applies_to al
    if ($kategori) {
        $rawAppliesTo = $kategori->getRawOriginal('applies_to');
        $appliesToArray = (is_null($rawAppliesTo) || trim($rawAppliesTo) === '')
            ? []
            : ($kategori->applies_to ?? []);
    } else {
        $appliesToArray = is_array($appliesTo) ? $appliesTo : [];
    }

    $typeColors = [
        'konut' => 'from-blue-100 to-blue-200 dark:from-blue-900/30 dark:to-blue-800/30 border-blue-200 dark:border-blue-800 text-blue-700 dark:text-blue-300',
        'arsa' => 'from-green-100 to-green-200 dark:from-green-900/30 dark:to-green-800/30 border-green-200 dark:border-green-800 text-green-700 dark:text-green-300',
        'yazlik' => 'from-amber-100 to-amber-200 dark:from-amber-900/30 dark:to-amber-800/30 border-amber-200 dark:border-amber-800 text-amber-700 dark:text-amber-300',
        'isyeri' => 'from-indigo-100 to-indigo-200 dark:from-indigo-900/30 dark:to-indigo-800/30 border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300',
    ];
@endphp

<div class="flex flex-wrap gap-1">
    @if (is_array($appliesToArray) && count($appliesToArray) > 0)
        @foreach ($appliesToArray as $type)
            @php
                $colorClass = $typeColors[strtolower($type)] ?? 'from-purple-100 to-pink-100 dark:from-purple-900/30 dark:to-pink-900/30 border-purple-200 dark:border-purple-800 text-purple-700 dark:text-purple-300';
            @endphp
            <span
                class="px-2 py-1 text-xs rounded-full bg-gradient-to-r {{ $colorClass }} font-medium transition-all duration-200 hover:scale-105"
                title="Bu kategori {{ ucfirst($type) }} emlak türleri için geçerlidir">
                {{ ucfirst($type) }}
            </span>
        @endforeach
    @else
        <span
            class="px-2 py-1 text-xs rounded-full bg-gray-100 dark:bg-slate-900 text-gray-600 dark:text-gray-400 border border-gray-200 dark:border-slate-800 dark:border-slate-700"
            title="Bu kategori tüm emlak türleri için geçerlidir">
            Tümü
        </span>
    @endif
</div>
