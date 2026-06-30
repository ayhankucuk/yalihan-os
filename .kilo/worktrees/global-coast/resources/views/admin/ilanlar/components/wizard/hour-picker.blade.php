@props([
    'name',
    'value' => '',
    'id' => null,
    'label' => null,
    'placeholder' => '00:00',
    'required' => false
])

@php
    $id = $id ?? 'hour_picker_' . $name;
@endphp

<div x-data="{
    open: false,
    selectedHour: '{{ $value ? explode(':', $value)[0] : '14' }}',
    selectedMinute: '{{ $value ? explode(':', $value)[1] : '00' }}',
    hours: Array.from({length: 24}, (_, i) => i.toString().padStart(2, '0')),
    minutes: ['00', '15', '30', '45'],
    get displayValue() {
        return `${this.selectedHour}:${this.selectedMinute}`;
    }
}" @click.away="open = false" class="relative">
    <label for="{{ $id }}" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
        {{ $label ?? $slot }}
        @if($required) <span class="text-red-500">*</span> @endif
    </label>

    <div class="relative">
        <input
            type="text"
            id="{{ $id }}"
            name="{{ $name }}"
            x-model="displayValue"
            @click="open = !open"
            readonly
            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 cursor-pointer transition-all duration-200"
            placeholder="{{ $placeholder }}"
        >
        <div class="absolute inset-y-0 right-0 flex items-center pr-3 pointer-events-none">
            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>

    <!-- Dropdown -->
    <div
        x-show="open"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        class="absolute z-50 mt-2 w-64 bg-white dark:bg-slate-900 rounded-xl shadow-2xl border border-gray-100 dark:border-slate-800 p-4"
    >
        <div class="grid grid-cols-2 gap-4">
            <!-- Hours -->
            <div>
                <div class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-2 text-center">SAAT</div>
                <div class="grid grid-cols-4 gap-1 h-40 overflow-y-auto no-scrollbar">
                    <template x-for="h in hours" :key="h">
                        <button
                            type="button"
                            @click="selectedHour = h; if(selectedMinute) open = false"
                            :class="selectedHour === h ? 'bg-blue-600 text-white' : 'hover:bg-blue-50 dark:hover:bg-blue-900/40 text-gray-700 dark:text-gray-300' dark:text-slate-300"
                            class="px-1 py-1.5 text-xs rounded-md transition-colors"
                            x-text="h"
                        ></button>
                    </template>
                </div>
            </div>
            <!-- Minutes -->
            <div>
                <div class="text-[10px] uppercase tracking-wider text-gray-400 font-bold mb-2 text-center">DAKİKA</div>
                <div class="flex flex-col gap-1">
                    <template x-for="m in minutes" :key="m">
                        <button
                            type="button"
                            @click="selectedMinute = m; open = false"
                            :class="selectedMinute === m ? 'bg-blue-600 text-white' : 'hover:bg-blue-50 dark:hover:bg-blue-900/40 text-gray-700 dark:text-gray-300' dark:text-slate-300"
                            class="px-1 py-1.5 text-xs rounded-md transition-colors"
                            x-text="m"
                        ></button>
                    </template>
                </div>
            </div>
        </div>

        <div class="mt-4 pt-4 border-t border-gray-100 dark:border-slate-800 flex justify-between items-center">
            <span class="text-xs font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="displayValue"></span>
            <button
                type="button"
                @click="open = false"
                class="text-xs text-blue-600 hover:text-blue-700 font-bold"
            >Tamam</button>
        </div>
    </div>
</div>
