{{--
    Neo Select Component - Context7 Standard
    Yalıhan Bekçi Onaylı Select Dropdown

    Kullanım:
    <x-w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-800 dark:text-gray-100
        name="para_birimi"
        label="Para Birimi"
        :required="true"
        :options="['TRY' => '₺ Türk Lirası', 'USD' => '$ Dolar', 'EUR' => '€ Euro']" />
--}}

@props(['name', 'label', 'options' => [], 'required' => false, 'value' => '', 'placeholder' => 'Seçiniz', 'helpText' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => '']) }}>
    {{-- Label --}}
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-700 mb-2 dark:text-slate-300">
        @if($icon)
            <i class="{{ $icon }} mr-1"></i>
        @endif
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    {{-- Select Dropdown --}}
    <select name="{{ $name }}"
            id="{{ $name }}"
            class="w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:border-slate-800 @error($name) border-red-500 @enderror dark:border-slate-700"
            {{ $required ? 'required' : '' }}
            {{ $attributes->except(['class']) }}>

        {{-- Placeholder Option --}}
        <option value="">{{ $placeholder }}</option>

        {{-- Options --}}
        @if(is_array($options) || $options instanceof \Illuminate\Support\Collection)
            @foreach($options as $key => $optionLabel)
                <option value="{{ $key }}" {{ old($name, $value) == $key ? 'selected' : '' }}>
                    {{ $optionLabel }}
                </option>
            @endforeach
        @else
            {{-- Slot için --}}
            {{ $slot }}
        @endif
    </select>

    {{-- Help Text --}}
    @if($helpText)
        <p class="text-xs text-gray-500 mt-1">
            <span class="material-symbols-outlined mr-1">info</span>{{ $helpText }}
        </p>
    @endif

    {{-- Error Message --}}
    @error($name)
        <p class="text-red-500 text-xs mt-1">
            <span class="material-symbols-outlined mr-1">error</span>{{ $message }}
        </p>
    @enderror
</div>
