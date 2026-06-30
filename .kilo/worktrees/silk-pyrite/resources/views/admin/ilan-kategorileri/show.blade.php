@extends('admin.layouts.admin')

@section('title', $kategori->name . ' - Kategori Detayı')

@section('content')
    <div class="container mx-auto px-4 py-6">
        <!-- Page Header -->
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <a href="{{ route('admin.ilan-kategorileri.index') }}"
                            class="inline-flex items-center text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white transition-colors">
                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                            </svg>
                            Kategorilere Dön
                        </a>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $kategori->name }}
                    </h1>
                    <p class="mt-2 text-gray-600 dark:text-gray-400">
                        @if ($kategori->parent)
                            <span
                                class="inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded-full text-xs font-medium">
                                {{ $kategori->parent->name }}
                            </span>
                            <span class="mx-2">→</span>
                        @endif
                        <span class="text-sm">{{ $kategori->slug ?? 'Slug yok' }}</span>
                    </p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.ilan-kategorileri.edit', $kategori->id) }}"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Düzenle
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div
                class="bg-gradient-to-br from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 border border-blue-200 dark:border-blue-800/30 rounded-xl p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-blue-600 dark:text-blue-400 mb-1">Toplam İlan</p>
                        <p class="text-3xl font-bold text-blue-900 dark:text-blue-100">{{ $stats['toplam_ilan'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-blue-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 border border-green-200 dark:border-green-800/30 rounded-xl p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-green-600 dark:text-green-400 mb-1">Aktif İlan</p>
                        <p class="text-3xl font-bold text-green-900 dark:text-green-100">{{ $stats['aktif_ilan'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-green-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 border border-purple-200 dark:border-purple-800/30 rounded-xl p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-purple-600 dark:text-purple-400 mb-1">Son 30 Gün</p>
                        <p class="text-3xl font-bold text-purple-900 dark:text-purple-100">{{ $stats['son_30_gun'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-purple-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>

            <div
                class="bg-gradient-to-br from-amber-50 to-amber-100 dark:from-amber-900/20 dark:to-amber-800/20 border border-amber-200 dark:border-amber-800/30 rounded-xl p-6 shadow-sm dark:shadow-none">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-amber-600 dark:text-amber-400 mb-1">Alt Kategoriler</p>
                        <p class="text-3xl font-bold text-amber-900 dark:text-amber-100">{{ $stats['alt_kategoriler'] }}</p>
                    </div>
                    <div class="w-12 h-12 bg-amber-500 rounded-xl flex items-center justify-center shadow-lg">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Kategori Bilgileri -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Kategori Detayları -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Kategori
                            Bilgileri</h2>
                    </div>
                    <div class="p-6">
                        <dl class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kategori Adı</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-semibold dark:text-slate-100">
                                    {{ $kategori->name }}
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Slug</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono dark:text-slate-100">
                                    {{ $kategori->slug ?? '-' }}</dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Seviye</dt>
                                <dd class="mt-1">
                                    @if ($kategori->seviye == 0)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">Ana
                                            Kategori</span>
                                    @elseif($kategori->seviye == 1)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Alt
                                            Kategori</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200">Yayın
                                            Tipi</span>
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Aktiflik</dt>
                                <dd class="mt-1">
                                    @if ($kategori->aktiflik_durumu)
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Aktif</span>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200">Pasif</span>
                                    @endif
                                </dd>
                            </div>
                            {{-- ✅ SAB: parent null kontrolü eklendi --}}
                            @if ($kategori->parent)
                                <div>
                                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ana Kategori</dt>
                                    <dd class="mt-1">
                                        <a href="{{ route('admin.ilan-kategorileri.show', $kategori->parent->id) }}"
                                            class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                            {{ $kategori->parent->name }}
                                        </a>
                                    </dd>
                                </div>
                            @endif
                            <div>
                                <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Oluşturulma</dt>
                                <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $kategori->created_at->format('d.m.Y H:i') }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <!-- Alt Kategoriler -->
                @if ($kategori->children->count() > 0)
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Alt
                                Kategoriler
                                ({{ $kategori->children->count() }})</h2>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                @foreach ($kategori->children as $child)
                                    <a href="{{ route('admin.ilan-kategorileri.show', $child->id) }}"
                                        class="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg border border-gray-200 dark:border-gray-600 hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 group dark:bg-slate-900 dark:border-slate-700">
                                        <div class="flex items-center gap-3">
                                            <div
                                                class="w-10 h-10 bg-gradient-to-br from-green-500 to-emerald-600 rounded-lg flex items-center justify-center text-white font-bold shadow-md dark:shadow-none">
                                                {{ substr($child->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <p
                                                    class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">
                                                    {{ $child->name }}</p>
                                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                                    {{ $child->slug ?? 'Slug yok' }}</p>
                                            </div>
                                        </div>
                                        <svg class="w-5 h-5 text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Son İlanlar -->
                @if ($son_ilanlar->count() > 0)
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 flex items-center justify-between dark:border-slate-700">
                            <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Son İlanlar
                            </h2>
                            <a href="{{ route('admin.ilanlar.index', ['kategori_id' => $kategori->id]) }}"
                                class="text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 font-medium">
                                Tümünü Gör →
                            </a>
                        </div>
                        <div class="divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach ($son_ilanlar as $ilan)
                                <div class="p-6 hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <div class="flex items-start justify-between">
                                        <div class="flex-1">
                                            <a href="{{ route('admin.ilanlar.show', $ilan->id) }}"
                                                class="text-lg font-semibold text-gray-900 dark:text-white hover:text-blue-600 dark:hover:text-blue-400 transition-colors dark:text-slate-100">
                                                {{ $ilan->baslik }}
                                            </a>
                                            <div
                                                class="mt-2 flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400">
                                                @if ($ilan->fiyat)
                                                    <span class="font-medium text-green-600 dark:text-green-400">
                                                        {{ number_format($ilan->fiyat, 0, ',', '.') }}
                                                        {{ $ilan->para_birimi ?? 'TL' }}
                                                    </span>
                                                @endif
                                                <span>{{ $ilan->created_at->format('d.m.Y') }}</span>
                                                @if ($ilan->ilanSahibi)
                                                    <span>{{ $ilan->ilanSahibi->ad }}
                                                        {{ $ilan->ilanSahibi->soyad }}</span>
                                                @endif
                                            </div>
                                        </div>
                                        <div class="ml-4">
                                            @if (($ilan->yayin_durumu ?? '') === \App\Enums\IlanDurumu::YAYINDA->value)
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">Yayında</span>
                                            @else
                                                <span
                                                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200">Taslak</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div
                        class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                        <div class="p-12 text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Henüz
                                ilan yok</h3>
                            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bu kategoride henüz ilan bulunmuyor.
                            </p>
                            <div class="mt-6">
                                <a href="{{ route('admin.ilanlar.create', ['kategori_id' => $kategori->id]) }}"
                                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 4v16m8-8H4" />
                                    </svg>
                                    Yeni İlan Oluştur
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Hızlı İşlemler -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Hızlı İşlemler
                        </h2>
                    </div>
                    <div class="p-6 space-y-3">
                        <a href="{{ route('admin.ilan-kategorileri.edit', $kategori->id) }}"
                            class="flex items-center gap-3 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 group dark:bg-slate-900">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                            <span
                                class="font-medium text-gray-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors dark:text-slate-100">Kategoriyi
                                Düzenle</span>
                        </a>
                        <a href="{{ route('admin.ilanlar.create', ['kategori_id' => $kategori->id]) }}"
                            class="flex items-center gap-3 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 group dark:bg-slate-900">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4v16m8-8H4" />
                            </svg>
                            <span
                                class="font-medium text-gray-900 dark:text-white group-hover:text-green-600 dark:group-hover:text-green-400 transition-colors dark:text-slate-100">Yeni
                                İlan Ekle</span>
                        </a>
                        <a href="{{ route('admin.ilanlar.index', ['kategori_id' => $kategori->id]) }}"
                            class="flex items-center gap-3 w-full px-4 py-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-700 transition-all duration-200 group dark:bg-slate-900">
                            <svg class="w-5 h-5 text-gray-600 dark:text-gray-400 group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors"
                                fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                            <span
                                class="font-medium text-gray-900 dark:text-white group-hover:text-purple-600 dark:group-hover:text-purple-400 transition-colors dark:text-slate-100">Tüm
                                İlanları Gör</span>
                        </a>
                    </div>
                </div>

                <!-- Kategori Bilgileri -->
                <div
                    class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Bilgiler</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Kategori ID</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white font-mono dark:text-slate-100">
                                {{ $kategori->id }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Display Order</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $kategori->display_order ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500 dark:text-gray-400 uppercase">Güncellenme</dt>
                            <dd class="mt-1 text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $kategori->updated_at->format('d.m.Y H:i') }}</dd>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
