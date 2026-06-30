@props([
    'name' => 'fiyat',
    'currencyName' => 'para_birimi',
    'label' => 'Fiyat',
    'required' => false,
    'currencies' => [1 => '₺', 2 => '$', 3 => '€', 4 => '£'],
    'value' => null,
    'currencyValue' => null,
    'hint' => null,
    'showTryConversion' => true,
])
@php($inputId = $name . '__input')
<div class="form-field">
    <label for="{{ $inputId }}" class="form-label">
        {{ $label }} @if ($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    <div class="relative group">
        <input type="number" step="0.01" name="{{ $name }}" id="{{ $inputId }}"
            value="{{ old($name, $value) }}" @if ($required) required @endif
            {{ $attributes->merge(['class' => 'form-input pr-24']) }}>
        <select name="{{ $currencyName }}" x-ref="currencySelect"
            @change="calculatePriceAnalysis(); $refs.currencyCode.value = (function(v){const m={1:'TRY',2:'USD',3:'EUR',4:'GBP'}; return m[v]||'TRY';})(event.target.value)"
            class="form-select !px-2 !py-0 absolute right-0 top-0 h-full rounded-r-lg border-l border-gray-300 dark:border-gray-600 bg-gray-50 dark:bg-slate-900 text-sm w-16">
            <option value="">-</option>
            @foreach ($currencies as $cid => $symbol)
                <option value="{{ $cid }}"
                    {{ (string) old($currencyName, $currencyValue) === (string) $cid ? 'selected' : '' }}>
                    {{ $symbol }}</option>
            @endforeach
        </select>
        <!-- Gerçek currency code backend için -->
        <input type="hidden" name="para_birimi" x-ref="currencyCode" value="{{ old('para_birimi') }}">
    </div>
    @if ($showTryConversion)
        <div x-show="tryConversionText" x-transition
            class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1">
            <span class="material-symbols-outlined text-gray-400">swap_horiz</span>
            <span x-text="tryConversionText"></span>
        </div>
    @endif
    @if ($hint)
        <p class="form-hint">{!! $hint !!}</p>
    @endif
    @error($name)
        <p class="form-error-message">{{ $message }}</p>
    @enderror
    @error($currencyName)
        <p class="form-error-message">{{ $message }}</p>
    @enderror
</div>
