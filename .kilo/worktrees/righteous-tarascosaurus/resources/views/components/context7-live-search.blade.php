{{--
    Context7 Live Search Component

    Bu component Context7 standartlarına uygun canlı arama arayüzü sağlar.
    Kişi, danışman ve site/apartman aramaları için kullanılabilir.

    Kullanım:
    @component('components.context7-live-search', [
    'searchType' => 'kisiler', // kisiler, danismanlar, sites, unified
    'name' => 'kisi_id',
    'placeholder' => 'Kişi ara...',
    'value' => old('kisi_id'),
    'filters' => ['musteri_tipi' => 'ev_sahibi'],
    'maxResults' => 20,
    'required' => true,
    'class' => 'form-control',
])
    @endcomponent

    @version 2.0.0
    @since 2025-10-05
    @author Context7 System
--}}

@php
    $searchType = $searchType ?? 'kisiler';
    $name = $name ?? 'search_input';
    $placeholder = $placeholder ?? 'Arama yapın...';
    $value = $value ?? '';
    $filters = $filters ?? [];
    $maxResults = $maxResults ?? 20;
    $required = $required ?? false;
    $class = $class ?? 'w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-gray-800 dark:border-gray-700 dark:text-white';
    $id = $id ?? 'context7-search-' . uniqid();
    $hiddenInputName = $hiddenInputName ?? $name . '_id';
    $showSearchHints = $showSearchHints ?? true;
    $enableKeyboardNavigation = $enableKeyboardNavigation ?? true;

    // Context7 uyumlu placeholder'lar
$placeholders = [
    'kisiler' => 'Kişi ara (ad, soyad, telefon, email, TC)...',
    'danismanlar' => 'Danışman ara (ad, email)...',
    'sites' => 'Site/Apartman ara (ad, adres)...',
    'unified' => 'Birleşik arama (kişi, danışman, site)...',
    ];

    $placeholder = $placeholders[$searchType] ?? $placeholder;
@endphp

<div class="context7-live-search" data-context7-search="{{ $searchType }}"
    data-context7-max-results="{{ $maxResults }}" data-context7-hidden-input-name="{{ $hiddenInputName }}"
    data-context7-show-search-hints="{{ $showSearchHints ? 'true' : 'false' }}"
    data-context7-enable-keyboard-navigation="{{ $enableKeyboardNavigation ? 'true' : 'false' }}"
    @foreach ($filters as $key => $filterValue)
         data-context7-{{ $key }}="{{ $filterValue }}" @endforeach>

    {{-- Ana Arama Input'u --}}
    <input type="text" id="{{ $id }}" name="{{ $name }}" class="{{ $class }}"
        placeholder="{{ $placeholder }}" value="{{ $value }}" autocomplete="off"
        @if ($required) required @endif aria-label="{{ $placeholder }}"
        aria-describedby="{{ $id }}-help" role="combobox" aria-expanded="false" aria-autocomplete="list">

    {{-- Context7 Uyumluluk Rozeti --}}
    <span class="context7-compliant-badge" title="Context7 Uyumlu">C7</span>

    {{-- Yardım Metni --}}
    @if ($showSearchHints)
        <small id="{{ $id }}-help" class="text-gray-500 dark:text-gray-400 text-sm">
            <strong>Context7 Live Search:</strong>
            @switch($searchType)
                @case('kisiler')
                    En az 2 karakter girin. Kişi adı, soyadı, telefon, email veya TC kimlik numarası ile arama yapabilirsiniz.
                @break

                @case('danismanlar')
                    En az 2 karakter girin. Danışman adı veya email adresi ile arama yapabilirsiniz.
                @break

                @case('sites')
                    En az 2 karakter girin. Site/Apartman adı veya adresi ile arama yapabilirsiniz.
                @break

                @case('unified')
                    En az 2 karakter girin. Kişi, danışman veya site/apartman ile birleşik arama yapabilirsiniz.
                @break
            @endswitch
            <br>
            <span class="text-info">💡 İpucu:</span>
            <kbd>↑↓</kbd> ile gezinin,
            <kbd>Enter</kbd> ile seçin,
            <kbd>Esc</kbd> ile kapatın.
        </small>
    @endif

    {{-- Loading Indicator --}}
    <div class="context7-loading-indicator hidden items-center">
        <span class="inline-block w-4 h-4 border-2 border-gray-300 border-t-blue-600 rounded-full animate-spin" aria-hidden="true"></span>
        <span class="ml-2 text-gray-600 dark:text-slate-200">Aranıyor...</span>
    </div>

    {{-- Context7 Status Indicator --}}
    <div class="context7-status-indicator mt-2" style="display: none;">
        <span class="status-icon">✅</span>
        <span class="status-text">Context7 Uyumlu</span>
    </div>
</div>

{{-- Context7 Live Search Styles --}}
@push('styles')
    <link href="{{ asset('css/context7-live-search.css') }}" rel="stylesheet">
@endpush

{{-- Context7 Live Search Scripts --}}
@push('scripts')
    <script src="{{ asset('js/context7-live-search.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Context7 Live Search instance'ı oluştur
            const searchElement = document.getElementById('{{ $id }}');
            if (searchElement && window.context7LiveSearchInstance) {
                const instanceId = window.context7LiveSearchInstance.addSearchInstance(
                    searchElement,
                    '{{ $searchType }}', {
                        maxResults: {{ $maxResults }},
                        hiddenInputName: '{{ $hiddenInputName }}',
                        showSearchHints: {{ $showSearchHints ? 'true' : 'false' }},
                        enableKeyboardNavigation: {{ $enableKeyboardNavigation ? 'true' : 'false' }},
                        @foreach ($filters as $key => $filterValue)
                            {{ $key }}: '{{ $filterValue }}',
                        @endforeach
                    }
                );

                // Context7 uyumluluk event'i
                searchElement.addEventListener('context7:search:selected', function(e) {
                    console.log('🔍 Context7 Live Search: Seçim yapıldı', e.detail);

                    // Status indicator'ı göster
                    const statusIndicator = searchElement.parentNode.querySelector(
                        '.context7-status-indicator');
                    if (statusIndicator) {
                        statusIndicator.style.display = 'flex';
                        setTimeout(() => {
                            statusIndicator.style.display = 'none';
                        }, 3000);
                    }

                    // Custom event tetikle
                    const customEvent = new CustomEvent('live-search:selected', {
                        detail: {
                            searchType: '{{ $searchType }}',
                            result: e.detail.result,
                            instance: e.detail.instance
                        }
                    });
                    document.dispatchEvent(customEvent);
                });

                // Context7 debug bilgisi
                if (window.location.hostname === 'localhost' || window.location.hostname === '127.0.0.1') {
                    console.log('🔍 Context7 Live Search initialized:', {
                        instanceId: instanceId,
                        searchType: '{{ $searchType }}',
                        element: '{{ $id }}',
                        filters: @json($filters),
                        context7Compliant: true
                    });
                }
            }
        });
    </script>
@endpush

{{-- Context7 Live Search Validation Rules --}}
@if ($required)
    @push('validation-rules')
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                const form = document.querySelector('form');
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const searchInput = document.getElementById('{{ $id }}');
                        const hiddenInput = document.querySelector('input[name="{{ $hiddenInputName }}"]');

                        if (searchInput && hiddenInput) {
                            if (!hiddenInput.value) {
                                e.preventDefault();

                                // Context7 uyumlu hata mesajı
                                const errorMessage = document.createElement('div');
                                errorMessage.className = 'mt-2 px-4 py-2 rounded-lg bg-red-50 text-red-700 border border-red-200 dark:bg-red-900/30 dark:text-red-200 dark:border-red-800 transition-all duration-200';
                                errorMessage.innerHTML = `
                                    <strong>Context7 Validation Error:</strong>
                                    Lütfen {{ $placeholder }} alanından bir seçim yapın.
                                `;

                                searchInput.parentNode.appendChild(errorMessage);

                                // Input'a focus
                                searchInput.focus();

                                // Error message'ı 5 saniye sonra kaldır
                                setTimeout(() => {
                                    errorMessage.remove();
                                }, 5000);
                            }
                        }
                    });
                }
            });
        </script>
    @endpush
@endif
