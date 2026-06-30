@extends('admin.layouts.admin')

@section('title', 'UPS Templates')

@section('content')
    <div class="container-fluid min-h-screen bg-gray-50 px-4 py-8 dark:bg-slate-900">
        {{-- Header Section --}}
        <div class="mb-10 flex flex-col justify-between gap-6 md:flex-row md:items-center">
            <div>
                <h1
                    class="bg-gradient-to-r from-blue-600 to-indigo-600 bg-clip-text text-4xl font-black text-transparent drop-shadow-sm dark:from-blue-400 dark:to-indigo-400">
                    UPS Template Yöneticisi
                </h1>
                <p class="mt-2 text-lg font-medium text-gray-600 dark:text-gray-400">
                    Kategori ve yayın tipleri için özellik şablonlarını yönetin ve özelleştirin.
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.ups.health') }}"
                    class="inline-flex transform items-center rounded-xl border border-gray-200 bg-white px-5 py-3 font-bold text-gray-700 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:bg-gray-50 hover:shadow-md dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:text-slate-300 dark:shadow-none dark:hover:bg-gray-700">
                    <svg class="mr-2 h-5 w-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Sistem Sağlığı
                </a>
                <a href="{{ route('admin.ups.templates.import-export') }}"
                    class="inline-flex transform items-center rounded-xl bg-gradient-to-r from-blue-600 to-indigo-600 px-5 py-3 font-bold text-white shadow-lg shadow-blue-500/30 transition-all duration-300 hover:-translate-y-1 hover:scale-105 hover:shadow-blue-600/40">
                    <svg class="mr-2 h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12" />
                    </svg>
                    İçe/Dışa Aktar
                </a>
            </div>
        </div>

        {{-- Statistics Strip (Optional/Simple) --}}
        <div class="mb-10 grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-4">
            @php
                $totalCategories = $kategoriler->count();
                $totalTypes = $kategoriler->sum(function ($cat) {
                    return $cat->yayinTipleri->count();
                });
            @endphp

            <div
                class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="rounded-xl bg-blue-50 p-4 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-slate-100">{{ $totalCategories }}</h3>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktif Kategori</p>
                </div>
            </div>

            <div
                class="flex items-center gap-4 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                <div class="rounded-xl bg-purple-50 p-4 text-purple-600 dark:bg-purple-900/30 dark:text-purple-400">
                    <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4" />
                    </svg>
                </div>
                <div>
                    <h3 class="text-2xl font-black text-gray-900 dark:text-slate-100">{{ $totalTypes }}</h3>
                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">Template Kombinasyonu</p>
                </div>
            </div>
        </div>

        {{-- Templates Grid --}}
        <div class="grid grid-cols-1 gap-8 md:grid-cols-2 lg:grid-cols-3 2xl:grid-cols-4">
            @foreach ($kategoriler as $kategori)
                @php
                    // ✅ SAB: Temporary UI Solution - Hide categories with 0 templates
                    if ($kategori->yayinTipleri->count() === 0) {
                        continue;
                    }

                    // ✅ SAB: Aesthetic Enhancement - Color coding based on category/slug
                    $gradientMap = [
                        'konut' => 'from-blue-500 to-indigo-600',
                        'isyeri' => 'from-orange-500 to-red-600',
                        'arsa-arazi' => 'from-emerald-500 to-teal-600',
                        'yazlik-kiralama' => 'from-amber-400 to-orange-500',
                        'turistik-tesisler' => 'from-purple-500 to-pink-600',
                        'projeden-satis' => 'from-indigo-600 to-blue-700',
                    ];
                    $gradient = $gradientMap[$kategori->slug] ?? 'from-gray-500 to-slate-600';
                    $iconBg = str_replace(['from-', 'to-'], ['bg-', ''], explode(' ', $gradient)[0]) . '/20';
                    $glowColor = str_replace(['from-', 'to-'], ['shadow-', ''], explode(' ', $gradient)[0]) . '/40';
                    $iconColor = str_replace('from-', 'text-', explode(' ', $gradient)[0]);
                @endphp
                <div
                    class="group relative overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-xl transition-all duration-500 hover:-translate-y-2 hover:shadow-2xl dark:border-slate-800 dark:bg-slate-900">
                    {{-- Decorative Glow Background --}}
                    <div
                        class="{{ $gradient }} absolute -right-24 -top-24 h-48 w-48 bg-gradient-to-br opacity-0 blur-3xl transition-opacity duration-500 group-hover:opacity-10">
                    </div>

                    {{-- Card Header Gradient Line --}}
                    <div class="{{ $gradient }} absolute inset-x-0 top-0 h-1.5 bg-gradient-to-r"></div>

                    <div class="p-8">
                        {{-- Category Title --}}
                        <div class="mb-8 flex items-center gap-5">
                            <div
                                class="{{ $iconBg }} $iconColor $glowColor flex h-14 w-14 items-center justify-center rounded-2xl border border-white/10 shadow-sm transition-all duration-500 group-hover:scale-110 group-hover:shadow-2xl dark:border-blue-500/10 dark:shadow-none">
                                <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                                </svg>
                            </div>
                            <div>
                                <h3
                                    class="text-2xl font-black tracking-tight text-gray-900 transition-colors group-hover:text-blue-600 dark:text-slate-100 dark:group-hover:text-blue-400">
                                    {{ $kategori->name }}
                                </h3>
                                <div class="mt-1 flex items-center gap-2">
                                    <span
                                        class="{{ $iconBg }} $iconColor border-current/5 inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-black uppercase tracking-widest">
                                        {{ $kategori->yayinTipleri->count() }} TEMPLATE
                                    </span>
                                </div>
                            </div>
                        </div>

                        {{-- Publication Types List --}}
                        <div class="space-y-4">
                            @forelse($kategori->yayinTipleri->sortBy('display_order') as $yayinTipi)
                                <a href="{{ route('admin.ups.templates.edit') }}?kategori_id={{ $kategori->id }}&yayin_tipi_id={{ $yayinTipi->id }}"
                                    class="group/item relative block">
                                    <div
                                        class="absolute inset-0 scale-95 transform rounded-2xl bg-gray-50 opacity-0 transition-all duration-300 group-hover/item:scale-100 group-hover/item:opacity-100 dark:bg-gray-700/50 dark:bg-slate-900">
                                    </div>
                                    <div
                                        class="relative flex items-center justify-between rounded-2xl border border-gray-100 bg-white p-4 transition-all duration-300 hover:border-blue-200 hover:bg-gray-50 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-blue-900/30 dark:hover:bg-gray-700">
                                        <div class="flex items-center gap-4">
                                            <div
                                                class="h-2.5 w-2.5 rounded-full bg-blue-500 shadow-[0_0_10px_rgba(59,130,246,0.5)] transition-transform group-hover/item:scale-125 dark:shadow-[0_0_15px_rgba(59,130,246,0.4)]">
                                            </div>
                                            <span
                                                class="text-[15px] font-bold text-gray-700 transition-colors group-hover/item:text-blue-600 dark:text-slate-300 dark:group-hover/item:text-blue-400">
                                                {{ $yayinTipi->name }}
                                            </span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span
                                                class="translate-x-2 transform text-[10px] font-bold text-blue-500 opacity-0 transition-all group-hover/item:translate-x-0 group-hover/item:opacity-100 dark:text-blue-400">DÜZENLE</span>
                                            <svg class="h-5 w-5 text-gray-300 transition-all duration-300 group-hover/item:translate-x-1 group-hover/item:text-blue-500"
                                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                            </svg>
                                        </div>
                                    </div>
                                </a>
                            @empty
                                <div
                                    class="rounded-2xl border-2 border-dashed border-gray-200 bg-gray-50/50 px-6 py-10 text-center dark:border-slate-800 dark:bg-gray-800/50">
                                    <p class="text-sm font-bold text-gray-400 dark:text-gray-500">Bu kategoride yayın tipi
                                        bulunamadı.</p>
                                </div>
                            @endforelse
                        </div>
                    </div>

                    {{-- Quick Action Footer --}}
                    @if ($kategori->yayinTipleri->count() > 0)
                        <div
                            class="flex translate-y-4 transform items-center justify-between border-t border-gray-100 bg-gray-50/50 px-8 py-5 opacity-0 transition-all duration-500 group-hover:translate-y-0 group-hover:opacity-100 dark:border-slate-800 dark:bg-gray-800/40">
                            <span class="text-[10px] font-black uppercase tracking-widest text-gray-400">Single Source of
                                Truth</span>
                            <a href="{{ route('admin.ups.templates.edit') }}?kategori_id={{ $kategori->id }}"
                                class="flex items-center gap-1 text-xs font-black text-blue-600 transition-transform hover:scale-105 dark:text-blue-400">
                                TÜMÜNÜ DÜZENLE
                                <svg class="h-3 w-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3"
                                        d="M14 5l7 7m0 0l-7 7m7-7H3" />
                                </svg>
                            </a>
                        </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
@endsection
