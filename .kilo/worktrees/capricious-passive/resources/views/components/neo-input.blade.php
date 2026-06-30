{{--
    Neo Input Component - Context7 Standard
    Yalıhan Bekçi Onaylı Form Input

    Kullanım:
    <x-w-full px-3 py-2 rounded-md border border-gray-200 bg-white text-sm placeholder:text-gray-400 focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-gray-800 dark:border-gray-800 dark:text-gray-100 transition-colors
        name="baslik"
        label="İlan Başlığı"
        :required="true"
        placeholder="Örn: Merkezi Konumda 3+1 Daire" />
--}}

@props(['name', 'label', 'type' => 'text', 'required' => false, 'placeholder' => '', 'value' => '', 'helpText' => null, 'icon' => null])

<div {{ $attributes->merge(['class' => '']) }}>
    {{-- Label --}}
    <label for="{{ $name }}" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
        @if($icon)
            <i class="{{ $icon }} mr-1"></i>
        @endif
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>

    {{-- Input Field --}}
    <input type="{{ $type }}"
           id="{{ $name }}"
           name="{{ $name }}"
           @error($name) aria-invalid="true" aria-describedby="{{ $name }}-error" data-error="true" @enderror
           class="w-full px-4 py-2.5 text-base rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 dark:focus:border-blue-400 transition-all duration-200 hover:border-gray-400 dark:hover:border-gray-500 disabled:bg-gray-100 disabled:cursor-not-allowed data-[error=true]:border-red-500 data-[error=true]:focus:ring-red-500"
           placeholder="{{ $placeholder }}"
           value="{{ old($name, $value) }}"
           {{ $required ? 'required' : '' }}
           {{ $attributes->except(['class']) }}>

    {{-- Help Text --}}
    @if($helpText)
        <p class="text-xs text-gray-500 mt-1">
            <span class="material-symbols-outlined mr-1">info</span>{{ $helpText }}
        </p>
    @endif

    {{-- Error Message --}}
    @error($name)
        <p id="{{ $name }}-error" role="alert" aria-live="assertive" class="text-red-600 dark:text-red-400 text-xs mt-1">
            <span class="material-symbols-outlined mr-1">error</span>{{ $message }}
        </p>
    @enderror
</div>
