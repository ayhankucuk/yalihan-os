{{-- resources/views/components/workspace/dynamic-field.blade.php --}}
@props(['field', 'value' => null, 'isMissing' => false])

@php
    $key = $field['key'];
    $label = $field['label'];
    $type = $field['alan_tipi'];
    $required = $field['required'] ?? false;
    $min = $field['min'] ?? null;
    $max = $field['max'] ?? null;
    $options = $field['options'] ?? [];
@endphp

@if($type === 'hidden')
    <input type="hidden" id="field_{{ $key }}" name="data[{{ $key }}]" value="{{ old('data.'.$key, $value) }}" />
@else
    <div class="space-y-1.5 p-4 rounded-xl transition-all duration-200 {{ $isMissing ? 'bg-red-50/50 border border-red-200 dark:bg-red-950/10 dark:border-red-900/30' : 'bg-transparent' }}" data-field-key="{{ $key }}" data-field-type="{{ $type }}">
        <label for="field_{{ $key }}" class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">
            {{ $label }}
            @if($required)
                <span class="text-red-500 font-bold" title="Zorunlu Alan">*</span>
            @endif
            @if($isMissing)
                <span class="text-[10px] font-medium text-red-500 bg-red-100 px-2 py-0.5 rounded-full dark:bg-red-900/30 dark:text-red-400">Eksik</span>
            @endif
        </label>

        @if($type === 'textarea')
            <textarea
                id="field_{{ $key }}"
                name="data[{{ $key }}]"
                @if($required) required @endif
                @if($max) maxlength="{{ $max }}" @endif
                class="w-full min-h-[100px] rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-all focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 {{ $isMissing ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                placeholder="{{ $label }} giriniz..."
            >{{ old('data.'.$key, $value) }}</textarea>

        @elseif($type === 'select')
            <select
                id="field_{{ $key }}"
                name="data[{{ $key }}]"
                @if($required) required @endif
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-all focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 {{ $isMissing ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
            >
                <option value="">Seçiniz...</option>
                @foreach($options as $opt)
                    <option value="{{ $opt }}" {{ old('data.'.$key, $value) == $opt ? 'selected' : '' }}>
                        {{ ucfirst($opt) }}
                    </option>
                @endforeach
            </select>

        @elseif($type === 'number')
            <input
                type="number"
                id="field_{{ $key }}"
                name="data[{{ $key }}]"
                value="{{ old('data.'.$key, $value) }}"
                @if($required) required @endif
                @if($min !== null) min="{{ $min }}" @endif
                @if($max !== null) max="{{ $max }}" @endif
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-all focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 {{ $isMissing ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                placeholder="{{ $label }} giriniz..."
            />

        @elseif($type === 'boolean')
            <div class="flex items-center h-9">
                <input
                    type="hidden"
                    name="data[{{ $key }}]"
                    value="0"
                />
                <label class="relative inline-flex items-center cursor-pointer select-none">
                    <input
                        type="checkbox"
                        id="field_{{ $key }}"
                        name="data[{{ $key }}]"
                        value="1"
                        {{ old('data.'.$key, $value) ? 'checked' : '' }}
                        class="sr-only peer"
                    />
                    <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer dark:bg-slate-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all dark:border-slate-600 peer-checked:bg-gold"></div>
                    <span class="ml-3 text-sm font-medium text-slate-900 dark:text-slate-300">Evet</span>
                </label>
            </div>

        @elseif($type === 'calendar')
            <div class="space-y-1">
                <input
                    type="text"
                    id="field_{{ $key }}"
                    name="data[{{ $key }}]"
                    value="{{ is_array(old('data.'.$key, $value)) ? implode(', ', old('data.'.$key, $value)) : old('data.'.$key, $value) }}"
                    @if($required) required @endif
                    class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-all focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 {{ $isMissing ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                    placeholder="Örn: 2026-07-01, 2026-07-31 veya takvim verisi"
                />
                <p class="text-[10px] text-slate-400 dark:text-slate-500">Müsaitlik veya fiyat periyotları için tarihler.</p>
            </div>

        @elseif($type === 'date')
            <input
                type="date"
                id="field_{{ $key }}"
                name="data[{{ $key }}]"
                value="{{ old('data.'.$key, $value) }}"
                @if($required) required @endif
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-all focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 {{ $isMissing ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
            />

        @elseif($type === 'image')
            <div class="space-y-2">
                @if($value)
                    <div class="flex items-center gap-3 p-2 bg-slate-100 rounded-lg dark:bg-slate-800">
                        <span class="text-xs text-slate-600 dark:text-slate-400 truncate max-w-[200px]">{{ $value }}</span>
                        <span class="text-[10px] bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded-full dark:bg-emerald-900/30 dark:text-emerald-400">Yüklü</span>
                    </div>
                @else
                    <div class="flex items-center justify-center border-2 border-dashed border-slate-200 rounded-lg p-4 bg-slate-50/50 hover:bg-slate-50 transition-colors dark:border-slate-800 dark:bg-slate-950/20 dark:hover:bg-slate-900/30">
                        <span class="text-xs text-slate-400 dark:text-slate-500">Görsel yüklemesi bekleniyor</span>
                    </div>
                @endif
                <input
                    type="file"
                    id="field_{{ $key }}"
                    name="data[{{ $key }}]"
                    class="hidden"
                    accept="image/*"
                />
            </div>

        @else
            <input
                type="text"
                id="field_{{ $key }}"
                name="data[{{ $key }}]"
                value="{{ old('data.'.$key, $value) }}"
                @if($required) required @endif
                @if($max) maxlength="{{ $max }}" @endif
                class="w-full rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm text-slate-900 shadow-sm transition-all focus:border-gold focus:outline-none focus:ring-1 focus:ring-gold dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 {{ $isMissing ? 'border-red-300 focus:border-red-500 focus:ring-red-500' : '' }}"
                placeholder="{{ $label }} giriniz..."
            />
        @endif
    </div>
@endif
