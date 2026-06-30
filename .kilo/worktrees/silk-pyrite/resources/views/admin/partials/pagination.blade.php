{{-- Pagination Partial --}}
{{-- SAB Context7: FA yasak — x-icon bileşeni kullanılıyor --}}

@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Sayfa Navigasyonu" class="flex flex-col sm:flex-row items-center justify-between gap-4">

        {{-- Mobil View --}}
        <div class="flex justify-between items-center w-full sm:hidden">
            @if ($paginator->onFirstPage())
                <span class="relative inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 cursor-default leading-5 rounded-lg dark:text-gray-500 dark:bg-slate-900 dark:border-gray-600">
                    <x-icon name="sol-chevron" class="w-4 h-4" />
                    Önceki
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}"
                   class="relative inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-lg hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-slate-300 dark:hover:bg-gray-700">
                    <x-icon name="sol-chevron" class="w-4 h-4" />
                    Önceki
                </a>
            @endif

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}"
                   class="relative inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 leading-5 rounded-lg hover:bg-gray-50 hover:text-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-slate-300 dark:hover:bg-gray-700">
                    Sonraki
                    <x-icon name="sag-chevron" class="w-4 h-4" />
                </a>
            @else
                <span class="relative inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-gray-400 bg-gray-100 border border-gray-300 cursor-default leading-5 rounded-lg dark:text-gray-500 dark:bg-slate-900 dark:border-gray-600">
                    Sonraki
                    <x-icon name="sag-chevron" class="w-4 h-4" />
                </span>
            @endif
        </div>

        {{-- Desktop View --}}
        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
            {{-- Sayfa Bilgisi --}}
            <div>
                <p class="text-sm text-gray-600 dark:text-slate-400 leading-5">
                    @if ($paginator->firstItem())
                        <span class="font-semibold text-gray-900 dark:text-slate-200">{{ $paginator->firstItem() }}</span>
                        –
                        <span class="font-semibold text-gray-900 dark:text-slate-200">{{ $paginator->lastItem() }}</span>
                    @else
                        {{ $paginator->count() }}
                    @endif
                    arası gösteriliyor, toplam
                    <span class="font-semibold text-gray-900 dark:text-slate-200">{{ $paginator->total() }}</span>
                    sonuç
                </p>
            </div>

            {{-- Sayfa Numaraları --}}
            <div>
                <span class="relative z-0 inline-flex rounded-lg shadow-sm -space-x-px dark:shadow-none">

                    {{-- Önceki Sayfa --}}
                    @if ($paginator->onFirstPage())
                        <span aria-disabled="true" aria-label="Önceki Sayfa">
                            <span class="relative inline-flex items-center justify-center w-10 h-10 text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-l-lg dark:bg-slate-900 dark:border-gray-600 dark:text-gray-500"
                                  aria-hidden="true">
                                <x-icon name="sol-chevron" class="w-4 h-4" />
                            </span>
                        </span>
                    @else
                        <a href="{{ $paginator->previousPageUrl() }}" rel="prev"
                           class="relative inline-flex items-center justify-center w-10 h-10 text-gray-500 bg-white border border-gray-300 rounded-l-lg hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-slate-400 dark:hover:bg-gray-700 dark:hover:text-blue-400"
                           aria-label="Önceki Sayfa">
                            <x-icon name="sol-chevron" class="w-4 h-4" />
                        </a>
                    @endif

                    {{-- Sayfa Numaraları --}}
                    @if ($paginator->lastPage() > 1)
                        @php
                            $start = max(1, $paginator->currentPage() - 2);
                            $end   = min($paginator->lastPage(), $paginator->currentPage() + 2);
                        @endphp

                        {{-- İlk Sayfa --}}
                        @if ($start > 1)
                            <a href="{{ $paginator->url(1) }}"
                               class="relative inline-flex items-center justify-center min-w-[2.5rem] px-3 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-slate-300 dark:hover:bg-gray-700 dark:hover:text-blue-400">
                                1
                            </a>
                            @if ($start > 2)
                                <span class="relative inline-flex items-center justify-center min-w-[2.5rem] px-3 h-10 text-sm text-gray-500 bg-white border border-gray-300 cursor-default dark:bg-slate-900 dark:border-gray-600 dark:text-slate-400">
                                    &hellip;
                                </span>
                            @endif
                        @endif

                        {{-- Sayfa Numaraları --}}
                        @for ($page = $start; $page <= $end; $page++)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page"
                                      class="relative inline-flex items-center justify-center min-w-[2.5rem] px-3 h-10 text-sm font-semibold text-white bg-blue-600 border border-blue-600 cursor-default shadow-sm dark:shadow-none">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $paginator->url($page) }}"
                                   class="relative inline-flex items-center justify-center min-w-[2.5rem] px-3 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-slate-300 dark:hover:bg-gray-700 dark:hover:text-blue-400"
                                   aria-label="{{ $page }}. sayfaya git">
                                    {{ $page }}
                                </a>
                            @endif
                        @endfor

                        {{-- Son Sayfa --}}
                        @if ($end < $paginator->lastPage())
                            @if ($end < $paginator->lastPage() - 1)
                                <span class="relative inline-flex items-center justify-center min-w-[2.5rem] px-3 h-10 text-sm text-gray-500 bg-white border border-gray-300 cursor-default dark:bg-slate-900 dark:border-gray-600 dark:text-slate-400">
                                    &hellip;
                                </span>
                            @endif
                            <a href="{{ $paginator->url($paginator->lastPage()) }}"
                               class="relative inline-flex items-center justify-center min-w-[2.5rem] px-3 h-10 text-sm font-medium text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-slate-300 dark:hover:bg-gray-700 dark:hover:text-blue-400">
                                {{ $paginator->lastPage() }}
                            </a>
                        @endif
                    @endif

                    {{-- Sonraki Sayfa --}}
                    @if ($paginator->hasMorePages())
                        <a href="{{ $paginator->nextPageUrl() }}" rel="next"
                           class="relative inline-flex items-center justify-center w-10 h-10 text-gray-500 bg-white border border-gray-300 rounded-r-lg hover:bg-gray-50 hover:text-blue-600 focus:z-10 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200 dark:bg-slate-900 dark:border-gray-600 dark:text-slate-400 dark:hover:bg-gray-700 dark:hover:text-blue-400"
                           aria-label="Sonraki Sayfa">
                            <x-icon name="sag-chevron" class="w-4 h-4" />
                        </a>
                    @else
                        <span aria-disabled="true" aria-label="Sonraki Sayfa">
                            <span class="relative inline-flex items-center justify-center w-10 h-10 text-gray-400 bg-gray-100 border border-gray-300 cursor-default rounded-r-lg dark:bg-slate-900 dark:border-gray-600 dark:text-gray-500"
                                  aria-hidden="true">
                                <x-icon name="sag-chevron" class="w-4 h-4" />
                            </span>
                        </span>
                    @endif

                </span>
            </div>
        </div>
    </nav>
@endif
