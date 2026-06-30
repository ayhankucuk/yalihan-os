@extends('admin.layouts.admin')

@section('title', 'Görev Detayı')

@section('content')
    <div class="p-6">
        <!-- Modern Page Header -->
        <div class="mb-8">
            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-6">
                <div class="flex-1">
                    <div class="flex items-center gap-3 mb-2">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-indigo-500 to-purple-600 rounded-xl flex items-center justify-center">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                            </svg>
                        </div>
                        <div>
                            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Görev Detayı</h1>
                            <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $gorev->baslik }}</p>
                        </div>
                    </div>
                    <!-- Breadcrumb -->
                    <nav class="flex items-center text-sm text-gray-500 dark:text-gray-400 mt-2">
                        <a href="{{ route('admin.dashboard.index') }}"
                            class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200">Dashboard</a>
                        <span class="px-2">/</span>
                        <a href="{{ route('admin.takim.gorevler.index') }}"
                            class="hover:text-gray-700 dark:hover:text-gray-300 transition-colors duration-200">Görevler</a>
                        <span class="px-2">/</span>
                        <span class="text-gray-900 dark:text-white">{{ Str::limit($gorev->baslik, 30) }}</span>
                    </nav>
                </div>
                <div class="flex flex-wrap gap-3">
                    <!-- Düzenle Butonu -->
                    <a href="{{ route('admin.takim.gorevler.edit', $gorev) }}"
                        class="group inline-flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white font-medium rounded-xl border border-gray-200 dark:border-slate-800 hover:bg-yellow-50 dark:hover:bg-yellow-900/20 hover:border-yellow-300 dark:hover:border-yellow-600 hover:text-yellow-600 dark:hover:text-yellow-400 transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-yellow-300 dark:focus:ring-yellow-600 shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 text-gray-500 group-hover:text-yellow-500 transition-colors duration-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span>Düzenle</span>
                    </a>

                    <!-- Ata Butonu -->
                    @if ($gorev->atanabilirMi())
                        <button type="button"
                            class="group relative inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-green-600 to-emerald-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-green-300 dark:focus:ring-green-800"
                            onclick="gorevAta({{ $gorev->id }})">
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-green-600 to-emerald-600 rounded-xl blur opacity-0 group-hover:opacity-75 transition-opacity duration-300">
                            </div>
                            <svg class="w-4 h-4 relative z-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z" />
                            </svg>
                            <span class="relative z-10">Ata</span>
                        </button>
                    @endif

                    <!-- Geri Dön Butonu -->
                    <a href="{{ route('admin.takim.gorevler.index') }}"
                        class="group inline-flex items-center gap-2 px-4 py-2.5 bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white font-medium rounded-xl border border-gray-200 dark:border-slate-800 hover:bg-gray-50 dark:hover:bg-gray-700 hover:border-gray-300 dark:hover:border-gray-600 transform hover:-translate-y-0.5 transition-all duration-300 focus:outline-none focus:ring-4 focus:ring-gray-300 dark:focus:ring-gray-600 shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 text-gray-500 group-hover:text-gray-700 dark:group-hover:text-gray-300 transition-colors duration-300"
                            fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span>Geri Dön</span>
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Sol Kolon - Görev Detayları -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Modern Görev Bilgileri -->
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 bg-gradient-to-r from-blue-500 to-cyan-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Görev Bilgileri</h3>
                            </div>
                            <div class="flex gap-2">
                                {!! $gorev->oncelik_etiketi !!}
                                {!! $gorev->status_etiketi !!}
                                {!! $gorev->gorev_tipi_etiketi !!}
                            </div>
                        </div>

                        <div class="space-y-6">
                            <!-- Görev Başlığı ve Açıklama -->
                            <div>
                                <h4 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">{{ $gorev->baslik }}
                                </h4>
                                @if ($gorev->aciklama)
                                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 dark:bg-slate-900">
                                        <h6 class="text-sm font-medium text-gray-900 dark:text-white mb-2">Açıklama:</h6>
                                        <p class="text-gray-600 dark:text-gray-400 leading-relaxed">{{ $gorev->aciklama }}
                                        </p>
                                    </div>
                                @endif
                            </div>

                            <!-- Tarih Bilgileri -->
                            <div class="grid grid-cols-2 gap-4">
                                <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                                    <div class="text-xs font-medium text-blue-600 dark:text-blue-400 mb-1">Oluşturulma</div>
                                    <div class="text-sm font-semibold text-blue-900 dark:text-blue-100">
                                        {{ $gorev->created_at->format('d.m.Y H:i') }}</div>
                                </div>
                                <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                                    <div class="text-xs font-medium text-green-600 dark:text-green-400 mb-1">Son Güncelleme
                                    </div>
                                    <div class="text-sm font-semibold text-green-900 dark:text-green-100">
                                        {{ $gorev->updated_at->format('d.m.Y H:i') }}</div>
                                </div>
                            </div>
                        </div>

                        <!-- Modern Görev Detayları -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                            <!-- Deadline -->
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 dark:bg-slate-900">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Deadline</span>
                                </div>
                                @if ($gorev->deadline)
                                    <div
                                        class="text-lg font-semibold {{ $gorev->geciktiMi() ? 'text-red-600' : ($gorev->deadlineYaklasiyorMu() ? 'text-yellow-600' : 'text-green-600') }}">
                                        {{ $gorev->deadline->format('d.m.Y H:i') }}
                                    </div>
                                    @if ($gorev->geciktiMi())
                                        <div class="text-xs text-red-600 dark:text-red-400 mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            {{ $gorev->gecikme_gunu }} gün gecikti!
                                        </div>
                                    @elseif($gorev->deadlineYaklasiyorMu())
                                        <div
                                            class="text-xs text-yellow-600 dark:text-yellow-400 mt-1 flex items-center gap-1">
                                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd"
                                                    d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z"
                                                    clip-rule="evenodd" />
                                            </svg>
                                            Deadline yaklaşıyor!
                                        </div>
                                    @endif
                                @else
                                    <div class="text-lg font-semibold text-gray-500 dark:text-gray-400">Belirtilmemiş</div>
                                @endif
                            </div>

                            <!-- Tahmini Süre -->
                            <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 dark:bg-slate-900">
                                <div class="flex items-center gap-2 mb-2">
                                    <svg class="w-4 h-4 text-gray-500 dark:text-gray-400" fill="none"
                                        stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13 10V3L4 14h7v7l9-11h-7z" />
                                    </svg>
                                    <span class="text-sm font-medium text-gray-900 dark:text-white">Tahmini Süre</span>
                                </div>
                                @if ($gorev->tahmini_sure)
                                    <div class="text-lg font-semibold text-blue-600 dark:text-blue-400">
                                        {{ $gorev->tahmini_sure }} dakika</div>
                                @else
                                    <div class="text-lg font-semibold text-gray-500 dark:text-gray-400">Belirtilmemiş</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Görev Takibi -->
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="w-10 h-10 bg-gradient-to-r from-orange-500 to-red-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Görev Takibi</h3>
                        </div>

                        @if ($gorev->gorevTakip && $gorev->gorevTakip->count() > 0)
                            <div class="space-y-4">
                                @foreach ($gorev->gorevTakip->sortBy('created_at') as $takip)
                                    @php
                                        $takipDurumu = $takip->islem_statusu ?? 'bekliyor';
                                    @endphp
                                    <div class="flex gap-4 p-4 bg-gray-50 dark:bg-gray-800/50 rounded-lg dark:bg-slate-900">
                                        <div
                                            class="w-8 h-8 {{ $takipDurumu === 'tamamlandi' ? 'bg-green-500' : ($takipDurumu === 'devam_ediyor' ? 'bg-yellow-500' : 'bg-blue-500') }} rounded-full flex items-center justify-center flex-shrink-0">
                                            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M5 13l4 4L19 7" />
                                            </svg>
                                        </div>
                                        <div class="flex-1">
                                            <div class="flex items-center justify-between mb-2">
                                                <h4 class="text-sm font-semibold text-gray-900 dark:text-white">
                                                    {{ ucfirst(str_replace('_', ' ', $takipDurumu)) }}</h4>
                                                {!! $takip->status_etiketi !!}
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">
                                                {{ $takip->user->name ?? 'Bilinmeyen' }}</p>
                                            @if ($takip->notlar)
                                                <p class="text-sm text-gray-600 dark:text-gray-400 mb-2">
                                                    {{ $takip->notlar }}</p>
                                            @endif
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $takip->created_at->format('d.m.Y H:i') }}
                                                @if ($takip->harcanan_sure)
                                                    • {{ $takip->harcanan_sure_formatli }}
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div
                                    class="w-16 h-16 bg-gray-100 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Henüz görev takibi
                                    bulunmuyor</h3>
                                <p class="text-gray-600 dark:text-gray-400">Görev üzerinde çalışmaya başladığınızda takip
                                    kayıtları burada görünecek.</p>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Dosyalar -->
                <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                    <div class="p-6">
                        <div class="flex items-center gap-3 mb-6">
                            <div
                                class="w-10 h-10 bg-gradient-to-r from-indigo-500 to-purple-500 rounded-lg flex items-center justify-center">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Görev Dosyaları</h3>
                        </div>

                        @if ($gorev->dosyalar && $gorev->dosyalar->count() > 0)
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                @foreach ($gorev->dosyalar as $dosya)
                                    <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-4 text-center dark:bg-slate-900">
                                        <div
                                            class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mx-auto mb-3">
                                            {!! $dosya->dosya_tipi_icon !!}
                                        </div>
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-white mb-1">
                                            {{ $dosya->dosya_adi }}</h4>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                                            {{ $dosya->dosya_boyutu }}</p>
                                        <a href="{{ $dosya->dosya_yolu }}" target="_blank"
                                            class="inline-flex items-center gap-1 px-3 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-100 dark:bg-blue-900/30 rounded-lg hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-colors duration-200">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor"
                                                viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14" />
                                            </svg>
                                            İndir
                                        </a>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8">
                                <div
                                    class="w-16 h-16 bg-gray-100 dark:bg-slate-900 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">Henüz dosya yüklenmemiş
                                </h3>
                                <p class="text-gray-600 dark:text-gray-400">Görev ile ilgili dosyalar burada görünecek.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Sağ Kolon - İlişkili Bilgiler -->
            <div class="space-y-6">
                <!-- Modern Müşteri Bilgileri -->
                @if ($gorev->kisi)
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div
                                    class="w-10 h-10 bg-gradient-to-r from-green-500 to-emerald-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Müşteri Bilgileri</h3>
                            </div>

                            <div class="space-y-4">
                                <!-- Müşteri Avatar ve İsim -->
                                <div class="flex items-center gap-3">
                                    <div
                                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-500 rounded-full flex items-center justify-center">
                                        <span class="text-white font-semibold text-lg">
                                            {{ strtoupper(substr($gorev->kisi->tam_ad ?? ($gorev->kisi->firma_adi ?? 'B'), 0, 1)) }}
                                        </span>
                                    </div>
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900 dark:text-white">
                                            {{ $gorev->kisi->tam_ad ?? ($gorev->kisi->firma_adi ?? 'Bilinmeyen') }}
                                        </h4>
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900/20 dark:text-green-400">
                                            {{ ucfirst($gorev->kisi->musteri_tipi ?? 'Müşteri') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- İletişim Bilgileri -->
                                <div class="space-y-3">
                                    @if ($gorev->kisi->telefon)
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg dark:bg-slate-900">
                                            <div
                                                class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z" />
                                                </svg>
                                            </div>
                                            <a href="tel:{{ $gorev->kisi->telefon }}"
                                                class="text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition-colors duration-200">
                                                {{ $gorev->kisi->telefon }}
                                            </a>
                                        </div>
                                    @endif

                                    @if ($gorev->kisi->email)
                                        <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg dark:bg-slate-900">
                                            <div
                                                class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center">
                                                <svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                                </svg>
                                            </div>
                                            <a href="mailto:{{ $gorev->kisi->email }}"
                                                class="text-purple-600 dark:text-purple-400 hover:text-purple-800 dark:hover:text-purple-300 transition-colors duration-200">
                                                {{ $gorev->kisi->email }}
                                            </a>
                                        </div>
                                    @endif

                                    @if ($gorev->kisi->adres)
                                        <div class="flex items-start gap-3 p-3 bg-gray-50 dark:bg-gray-800/50 rounded-lg dark:bg-slate-900">
                                            <div
                                                class="w-8 h-8 bg-orange-100 dark:bg-orange-900/30 rounded-lg flex items-center justify-center flex-shrink-0 mt-0.5">
                                                <svg class="w-4 h-4 text-orange-600 dark:text-orange-400" fill="none"
                                                    stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                                </svg>
                                            </div>
                                            <span
                                                class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">{{ $gorev->kisi->adres }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Modern Proje Bilgileri -->
                @if ($gorev->proje)
                    <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-shadow duration-200">
                        <div class="p-6">
                            <div class="flex items-center gap-3 mb-4">
                                <div
                                    class="w-10 h-10 bg-gradient-to-r from-purple-500 to-pink-500 rounded-lg flex items-center justify-center">
                                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Proje Bilgileri</h3>
                            </div>

                            <div class="space-y-4">
                                <div>
                                    <h4 class="text-lg font-semibold text-gray-900 dark:text-white mb-2">
                                        {{ $gorev->proje->ad }}</h4>
                                    @if ($gorev->proje->aciklama)
                                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 mb-4 dark:bg-slate-900">
                                            <p class="text-sm text-gray-600 dark:text-gray-400 leading-relaxed">
                                                {{ Str::limit($gorev->proje->aciklama, 100) }}</p>
                                        </div>
                                    @endif

                                    <!-- Proje Durumu -->
                                    <div class="flex items-center gap-2 mb-4">
                                        <span class="text-sm font-medium text-gray-900 dark:text-white">Durum:</span>
                                        {!! $gorev->proje->status_etiketi !!}
                                    </div>

                                    <!-- İlerleme Çubuğu -->
                                    <div class="mb-4">
                                        <div class="flex items-center justify-between mb-2">
                                            <span
                                                class="text-sm font-medium text-gray-900 dark:text-white">İlerleme</span>
                                            <span
                                                class="text-sm font-semibold text-blue-600 dark:text-blue-400">{{ $gorev->proje->ilerleme_yuzdesi }}%</span>
                                        </div>
                                        <div class="w-full bg-gray-200 dark:bg-slate-900 rounded-full h-2">
                                            <div class="bg-gradient-to-r from-blue-500 to-cyan-500 h-2 rounded-full transition-all duration-300"
                                                style="width: {{ $gorev->proje->ilerleme_yuzdesi }}%"></div>
                                        </div>
                                    </div>

                                    <!-- Tarih Aralığı -->
                                    @if ($gorev->proje->baslangic_tarihi || $gorev->proje->bitis_tarihi)
                                        <div class="bg-gray-50 dark:bg-gray-800/50 rounded-lg p-3 dark:bg-slate-900">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white mb-1">Tarih
                                                Aralığı</div>
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                @if ($gorev->proje->baslangic_tarihi)
                                                    {{ $gorev->proje->baslangic_tarihi->format('d.m.Y') }}
                                                @endif
                                                @if ($gorev->proje->baslangic_tarihi && $gorev->proje->bitis_tarihi)
                                                    -
                                                @endif
                                                @if ($gorev->proje->bitis_tarihi)
                                                    {{ $gorev->proje->bitis_tarihi->format('d.m.Y') }}
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <script>
        function gorevAta(gorevId) {
            // Görev atama işlemi
            console.log('Görev atanıyor:', gorevId);
        }
    </script>
@endsection
