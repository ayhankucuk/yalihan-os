{{-- ========================================
     HOME FEATURED PROJECTS COMPONENT
     Backend: HomeController->index() $oneKanProjeler ile uyumlu
     ======================================== --}}

@if ($oneKanProjeler->count() > 0)
    <section class="ds-section-sm bg-white dark:bg-slate-900">
        <div class="ds-container">
            <div class="text-center mb-12">
                <h2 class="ds-heading-2 mb-4">Öne Çıkan Projeler</h2>
                <p class="text-gray-600 text-lg">En yeni ve prestijli emlak projeleri</p>
            </div>

            <div class="ds-grid-4">
                @foreach ($oneKanProjeler as $proje)
                    <div class="ds-card ds-card-hover group">
                        {{-- Project Image --}}
                        <div class="h-48 bg-gray-200 relative overflow-hidden">
                            @if ($proje->resimler && count($proje->resimler) > 0)
                                <img src="{{ $proje->resimler[0] }}" alt="{{ $proje->ad }}"
                                    class="w-full h-full object-cover group-hover:scale-110 transition-all duration-500">
                            @else
                                <div
                                    class="w-full h-full flex items-center justify-center bg-gradient-to-br from-primary-50 to-blue-50">
                                    <span class="material-symbols-outlined text-primary-300 text-5xl">apartment</span>
                                </div>
                            @endif

                            {{-- New Project Badge --}}
                            <div class="absolute top-4 right-4">
                                <span
                                    class="ds-badge bg-primary-600 bg-opacity-90 backdrop-blur-sm text-white shadow-md dark:shadow-none">
                                    Yeni Proje
                                </span>
                            </div>

                            {{-- Gradient Overlay --}}
                            <div
                                class="absolute bottom-0 inset-x-0 h-20 bg-gradient-to-t from-gray-900 to-transparent opacity-60">
                            </div>
                        </div>

                        {{-- Project Details --}}
                        <div class="p-5">
                            {{-- Category Badge --}}
                            <div class="ds-badge-primary mb-3">
                                {{ $proje->kategori ?? 'Konut Projesi' }}
                            </div>

                            {{-- Project Title --}}
                            <h3 class="ds-heading-3 mb-3 line-clamp-2 group-hover:text-primary-700 transition-colors">
                                {{ $proje->ad }}
                            </h3>

                            {{-- Location --}}
                            <div class="flex items-center text-gray-600 mb-4">
                                <span class="material-symbols-outlined text-primary-500 mr-2">location_on</span>
                                <span class="text-sm">
                                    {{ $proje->il->il_adi ?? '' }}, {{ $proje->ilce->ilce_adi ?? '' }}
                                </span>
                            </div>

                            {{-- Action Bar --}}
                            <div class="flex justify-between items-center">
                                <div class="text-sm text-primary-700 font-semibold">
                                    {{-- Context7: Proje status (non-core tablo) --}}
                                    {{ $proje->proje_durumu ?? 'Devam Ediyor' }}
                                </div>
                                <a href="#" class="ds-inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 inline-flex bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg text-sm px-3 py-1.5 dark:shadow-none">
                                    <span class="material-symbols-outlined mr-1.5">info</span>
                                    İncele
                                </a>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif
