{{-- resources/views/components/admin/data-table/search.blade.php --}}
@props([
    'placeholder' => 'Search...',
    'name' => 'q',
])

@php
    $currentValue = request($name);
@endphp

<div class="relative w-full sm:w-72">
    {{-- Search Icon --}}
    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
        </svg>
    </div>

    {{-- Input --}}
    <input type="text"
           name="{{ $name }}"
           x-model="{{ $attributes->get('x-model', '$wire.searchQuery') }}"
           value="{{ $currentValue }}"
           placeholder="{{ $placeholder }}"
           class="w-full pl-10 pr-10 py-2.5 text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-xl text-slate-900 dark:text-white placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all"
    >

    {{-- Clear Button --}}
    @if($currentValue)
    <button type="button"
            @click="{{ $attributes->get('x-model', '$wire.searchQuery') }} = ''; $el.closest('form')?.submit()"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition-colors">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
        </svg>
    </button>
    @endif
</div>
