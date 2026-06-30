{{--
🎨 ELEGANT INPUT COMPONENT - Modern Input Design
Context7: %100, Tailwind CSS ONLY
Features: Floating Label, Icon Support, Validation States
--}}

@props([
    'name' => '',
    'type' => 'text',
    'label' => '',
    'placeholder' => '',
    'value' => '',
    'required' => false,
    'icon' => null,
    'helpText' => null,
    'floating' => true, // Floating label feature
    'maxLength' => null,
    'rows' => 4, // For textarea
])

@php
    // Use provided ID or generate one
    $inputId = isset($id) ? $id : $name;
    $hasError = $errors->has($name);
    $isTextarea = $type === 'textarea';
    $isSelect = $type === 'select';
@endphp

<div class="relative group">
    {{-- Label (Non-floating mode) --}}
    @if ($label && !$floating)
        <label for="{{ $inputId }}"
            class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2
                  flex items-center gap-2">
            @if ($icon)
                <span class="text-blue-600 dark:text-blue-400">{!! $icon !!}</span>
            @endif
            {{ $label }}
            @if ($required)
                <span class="text-red-500 text-base">*</span>
            @endif
        </label>
    @endif

    {{-- Input Container --}}
    <div class="relative">
        {{-- Icon (Left Side) --}}
        @if ($icon && !$isSelect)
            <div
                class="absolute left-4 top-1/2 -translate-y-1/2
                    text-gray-400 dark:text-gray-500
                    transition-colors duration-200
                    group-focus-within:text-blue-600 dark:group-focus-within:text-blue-400">
                {!! $icon !!}
            </div>
        @endif

        {{-- Input Field --}}
        @if ($isTextarea)
            <textarea name="{{ $name }}" id="{{ $inputId }}" rows="{{ $rows }}" {{ $required ? 'required' : '' }}
                placeholder="{{ $floating ? ' ' : $placeholder }}"
                {{ $attributes->merge([
                    'class' =>
                        'peer w-full px-4 py-3.5 text-base
                                           ' .
                        ($icon ? 'pl-12' : 'pl-4') .
                        '
                                           border-2 ' .
                        ($hasError ? 'border-red-500' : 'border-gray-300 dark:border-gray-600') .
                        '
                                           rounded-xl
                                           bg-gray-50 dark:bg-gray-800
                                           text-gray-900 dark:text-white
                                           placeholder-transparent
                                           focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10
                                           dark:focus:border-blue-400 dark:focus:ring-blue-400/10
                                           transition-all duration-300
                                           hover:border-gray-400 dark:hover:border-gray-500
                                           resize-none',
                ]) }}>{{ old($name, $value) }}</textarea>
        @elseif($isSelect)
            <select name="{{ $name }}" id="{{ $inputId }}" {{ $required ? 'required' : '' }}
                {{ $attributes->merge([
                    'class' =>
                        'peer w-full px-4 py-3.5 text-base
                                           border-2 ' .
                        ($hasError ? 'border-red-500' : 'border-gray-300 dark:border-gray-600') .
                        '
                                           rounded-xl
                                           bg-gray-50 dark:bg-gray-800
                                           text-gray-900 dark:text-white
                                           focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10
                                           dark:focus:border-blue-400 dark:focus:ring-blue-400/10
                                           transition-all duration-300
                                           hover:border-gray-400 dark:hover:border-gray-500
                                           cursor-pointer
                                           appearance-none
                                           bg-[url(\'data:image/svg+xml;charset=utf-8,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20fill%3D%22none%22%20viewBox%3D%220%200%2020%2020%22%3E%3Cpath%20stroke%3D%22%236B7280%22%20stroke-linecap%3D%22round%22%20stroke-linejoin%3D%22round%22%20stroke-width%3D%221.5%22%20d%3D%22m6%208%204%204%204-4%22%2F%3E%3C%2Fsvg%3E\')]
                                           bg-[length:1.5rem] bg-[right_0.5rem_center] bg-no-repeat
                                           pr-10',
                ]) }}>
                {{ $slot }}
            </select>
        @else
            <input type="{{ $type }}" name="{{ $name }}" id="{{ $inputId }}"
                value="{{ old($name, $value) }}" {{ $required ? 'required' : '' }}
                {{ $maxLength ? 'maxlength=' . $maxLength : '' }} placeholder="{{ $floating ? ' ' : $placeholder }}"
                {{ $attributes->merge([
                    'class' =>
                        'peer w-full px-4 py-3.5 text-base
                                           ' .
                        ($icon ? 'pl-12' : 'pl-4') .
                        '
                                           border-2 ' .
                        ($hasError ? 'border-red-500' : 'border-gray-300 dark:border-gray-600') .
                        '
                                           rounded-xl
                                           bg-gray-50 dark:bg-gray-800
                                           text-gray-900 dark:text-white
                                           placeholder-transparent
                                           focus:border-blue-500 focus:ring-4 focus:ring-blue-500/10
                                           dark:focus:border-blue-400 dark:focus:ring-blue-400/10
                                           transition-all duration-300
                                           hover:border-gray-400 dark:hover:border-gray-500',
                ]) }}>
        @endif

        {{-- Floating Label --}}
        @if ($label && $floating)
            @php
                $hasValue = old($name, $value) ? true : false;
                // Select için her zaman yukarıda göster (placeholder yok)
                $floatUp = $hasValue || $isSelect;
            @endphp
            <label for="{{ $inputId }}"
                class="absolute {{ $icon && !$isSelect ? 'left-12' : 'left-4' }}
                      text-gray-500 dark:text-gray-400
                      transition-all duration-200
                      pointer-events-none
                      @if ($floatUp) top-0 -translate-y-1/2 text-xs bg-gray-50 dark:bg-gray-800 px-2 py-0.5 rounded font-semibold text-blue-600 dark:text-blue-400
                      @else
                          top-3.5 text-base
                          peer-placeholder-shown:top-3.5 peer-placeholder-shown:text-base
                          peer-focus:top-0 peer-focus:-translate-y-1/2 peer-focus:text-xs
                          peer-focus:text-blue-600 dark:peer-focus:text-blue-400
                          peer-focus:bg-gray-50 dark:peer-focus:bg-gray-800
                          peer-focus:px-2 peer-focus:py-0.5 peer-focus:rounded
                          peer-focus:font-semibold @endif">
                {{ $label }}
                @if ($required)
                    <span class="text-red-500 ml-0.5">*</span>
                @endif
            </label>
        @endif

        {{-- Character Counter --}}
        @if ($maxLength)
            <div
                class="absolute right-4 top-1/2 -translate-y-1/2
                    text-xs text-gray-400 dark:text-gray-500
                    font-mono">
                <span
                    class="char-count-{{ $inputId }}">{{ strlen(old($name, $value)) }}</span>/{{ $maxLength }}
            </div>
        @endif
    </div>

    {{-- Help Text --}}
    @if ($helpText)
        <p class="mt-2 text-xs text-gray-600 dark:text-gray-400 flex items-center gap-1.5">
            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                    clip-rule="evenodd" />
            </svg>
            {{ $helpText }}
        </p>
    @endif

    {{-- Error Message --}}
    @error($name)
        <p class="mt-2 text-sm text-red-600 dark:text-red-400 flex items-center gap-1.5 animate-shake">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd"
                    d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z"
                    clip-rule="evenodd" />
            </svg>
            {{ $message }}
        </p>
    @enderror
</div>

{{-- Character Counter Script (Only when maxLength is set) --}}
@if ($maxLength)
    @push('scripts')
        <script>
            (function() {
                const input = document.getElementById('{{ $inputId }}');
                const counter = document.querySelector('.char-count-{{ $inputId }}');
                if (input && counter) {
                    input.addEventListener('input', function() {
                        counter.textContent = this.value.length;
                        if (this.value.length > {{ $maxLength }}) {
                            counter.classList.add('text-red-500');
                        } else {
                            counter.classList.remove('text-red-500');
                        }
                    });
                }
            })();
        </script>
    @endpush
@endif
