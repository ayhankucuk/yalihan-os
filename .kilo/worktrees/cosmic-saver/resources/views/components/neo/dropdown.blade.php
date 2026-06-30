@props([
    'trigger' => 'button',
    'position' => 'bottom-right', // bottom-right, bottom-left, top-right, top-left
    'size' => 'md', // sm, md, lg
])

@php
    $positionClasses = [
        'bottom-right' => 'top-full right-0 mt-1',
        'bottom-left' => 'top-full left-0 mt-1',
        'top-right' => 'bottom-full right-0 mb-1',
        'top-left' => 'bottom-full left-0 mb-1',
    ];

    $sizeClasses = [
        'sm' => 'min-w-32',
        'md' => 'min-w-48',
        'lg' => 'min-w-64',
    ];
@endphp

@php($triggerId = 'neo_dd_trigger_' . uniqid())
@php($menuId = 'neo_dd_menu_' . uniqid())
<div x-data="{ open: false }" class="relative inline-block text-left" @keydown.escape.window="open = false">
    <!-- Trigger -->
    @if ($trigger === 'button')
        <button @click="open = !open" @click.away="open = false"
            class="inline-flex items-center justify-center w-8 h-8 text-gray-500 hover:text-gray-700 hover:bg-gray-100 rounded-lg transition-colors"
            :aria-expanded="open" id="{{ $triggerId }}" aria-controls="{{ $menuId }}">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z">
                </path>
            </svg>
        </button>
    @else
        {{ $trigger }}
    @endif

    <!-- Dropdown Menu -->
    <div x-show="open" x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95" x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75" x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute {{ $positionClasses[$position] }} z-50 $sizeClasses[$size] bg-white rounded-lg shadow-lg border border-gray-200 py-1 dark:bg-slate-900 dark:border-slate-700"
        style="display: none;" role="menu" aria-orientation="vertical" id="{{ $menuId }}" :aria-labelledby="'{{ $triggerId }}'"
        x-init="(() => {
            const items = Array.from($el.querySelectorAll('a, button, [role=menuitem]'));
            items.forEach((el, idx) => {
                el.setAttribute('role','menuitem');
                el.setAttribute('tabindex','-1');
                el.addEventListener('keydown', (ev) => {
                    const k = ev.key;
                    if (k === 'ArrowDown' || k === 'ArrowRight') { ev.preventDefault(); (items[idx+1]||items[0]).focus(); }
                    if (k === 'ArrowUp' || k === 'ArrowLeft') { ev.preventDefault(); (items[idx-1]||items[items.length-1]).focus(); }
                    if (k === 'Home') { ev.preventDefault(); items[0].focus(); }
                    if (k === 'End') { ev.preventDefault(); items[items.length-1].focus(); }
                    if (k === 'Escape') { open=false; const t=document.getElementById('{{ $triggerId }}'); t && t.focus(); }
                });
            });
            $watch('open', (val) => { if (val && items[0]) { setTimeout(() => items[0].focus(), 0); } });
        })()"
    >
        {{ $slot }}
    </div>
</div>
