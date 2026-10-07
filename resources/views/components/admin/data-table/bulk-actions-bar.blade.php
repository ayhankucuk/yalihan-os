{{-- resources/views/components/admin/data-table/bulk-actions-bar.blade.php --}}
@props([
    'actions' => [],
])

@php
    $defaultActions = [
        [
            'label' => 'Delete',
            'action' => '#',
            'method' => 'DELETE',
            'icon' => 'trash',
            'variant' => 'danger',
        ],
        [
            'label' => 'Export',
            'action' => '#',
            'method' => 'POST',
            'icon' => 'download',
            'variant' => 'secondary',
        ],
    ];
    $actions = !empty($actions) ? $actions : $defaultActions;
@endphp

<div x-show="selectedCount > 0"
     x-transition:enter="transition ease-out duration-200"
     x-transition:enter-start="opacity-0 translate-y-4"
     x-transition:enter-end="opacity-100 translate-y-0"
     x-transition:leave="transition ease-in duration-150"
     x-transition:leave-start="opacity-100 translate-y-0"
     x-transition:leave-end="opacity-0 translate-y-4"
     class="fixed bottom-6 left-1/2 -translate-x-1/2 z-50"
>
    <div class="flex items-center gap-4 px-6 py-4 bg-slate-900 dark:bg-slate-800 text-white rounded-2xl shadow-2xl border border-slate-700 dark:border-slate-600">
        {{-- Selection Count --}}
        <div class="flex items-center gap-3">
            <div class="flex items-center justify-center w-8 h-8 rounded-full bg-indigo-600">
                <span x-text="selectedCount" class="text-sm font-bold"></span>
            </div>
            <span class="text-sm font-medium text-slate-300">selected</span>
        </div>

        {{-- Divider --}}
        <div class="w-px h-8 bg-slate-700"></div>

        {{-- Action Buttons --}}
        <div class="flex items-center gap-2">
            @foreach($actions as $action)
            <form method="POST" action="{{ $action['action'] }}" class="inline">
                @csrf
                @method($action['method'] ?? 'POST')
                <input type="hidden" name="ids" x-model="selected.join(',')">

                @php
                    $variantClasses = match($action['variant'] ?? 'secondary') {
                        'danger' => 'bg-rose-600 hover:bg-rose-700 text-white',
                        'primary' => 'bg-indigo-600 hover:bg-indigo-700 text-white',
                        default => 'bg-slate-700 hover:bg-slate-600 text-white',
                    };

                    $icon = match($action['icon'] ?? 'action') {
                        'trash' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>',
                        'download' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>',
                        'edit' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path>',
                        default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14"></path>',
                    };
                @endphp

                <button type="submit"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $variantClasses }}">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $icon !!}
                    </svg>
                    {{ $action['label'] }}
                </button>
            </form>
            @endforeach
        </div>

        {{-- Clear Selection --}}
        <button @click="selected = []"
                class="ml-2 p-2 text-slate-400 hover:text-white transition-colors"
                title="Clear selection">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    </div>
</div>
