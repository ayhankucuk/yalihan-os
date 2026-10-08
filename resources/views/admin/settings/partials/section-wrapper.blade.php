@props([
    'title',
    'description' => null,
])

<div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700">
    {{-- Section Header --}}
    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
        <h2 class="text-lg font-semibold text-slate-900 dark:text-white">{{ $title }}</h2>
        @if($description)
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">{{ $description }}</p>
        @endif
    </div>

    {{-- Section Content --}}
    <div class="p-6">
        {{ $slot }}
    </div>
</div>
