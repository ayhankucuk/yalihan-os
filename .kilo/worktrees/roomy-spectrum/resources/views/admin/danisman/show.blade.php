@php
    use Illuminate\Support\Facades\Storage;
@endphp

@extends('admin.layouts.admin')

@section('title', $danisman->name . ' - Danışman Detayı')

@section('content')
    <div class="content-header mb-8">
        <div class="container-fluid">
            <div class="flex flex-col lg:flex-row lg:justify-between lg:items-center gap-4">
                <div class="space-y-2">
                    <h1 class="text-2xl lg:text-3xl font-bold text-gray-800 dark:text-slate-100 flex items-center dark:text-slate-200">
                        <div
                            class="w-10 h-10 lg:w-12 lg:h-12 bg-gradient-to-r from-purple-500 to-pink-600 rounded-xl flex items-center justify-center mr-3 lg:mr-4">
                            @php
                                $pp = $danisman->profile_photo_path;
                                $ppUrl = $pp && Storage::exists($pp) ? Storage::url($pp) : null;
                            @endphp
                            @if ($ppUrl)
                                <img class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl object-cover" src="{{ $ppUrl }}"
                                    alt="{{ $danisman->name }}"
                                    onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                <div class="w-10 h-10 lg:w-12 lg:h-12 rounded-xl bg-gray-200 flex items-center justify-center"
                                    style="display: none;">
                                    <svg class="w-5 h-5 lg:w-6 lg:h-6 text-gray-500" fill="none" stroke="currentColor"
                                        viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                </div>
                            @else
                                <svg class="w-5 h-5 lg:w-6 lg:h-6 text-white" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                </svg>
                            @endif
                        </div>
                        <span class="hidden sm:inline">👤</span> {{ $danisman->name }}
                    </h1>
                    <p class="text-base lg:text-lg text-gray-600 dark:text-gray-400">
                        {{ $danisman->title ?? 'Danışman' }} - Danışman Detayları ve Performans Analizi
                    </p>
                </div>
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-2 sm:gap-3">
                    <x-neo.button variant="primary" size="sm" class="w-full sm:w-auto"
                        href="{{ route('admin.danisman.edit', $danisman) }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        <span class="hidden sm:inline">Düzenle</span>
                        <span class="sm:hidden">Düzenle</span>
                    </x-neo.button>
                    <form action="{{ route('admin.danisman.destroy', $danisman) }}" method="POST"
                        class="inline-block w-full sm:w-auto"
                        onsubmit="return confirm('Bu danışmanı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                            class="w-full sm:w-auto px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-all duration-200 flex items-center justify-center gap-2 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                            </svg>
                            <span class="hidden sm:inline">Sil</span>
                            <span class="sm:hidden">Sil</span>
                        </button>
                    </form>
                    <x-neo.button variant="secondary" size="sm" class="w-full sm:w-auto"
                        href="{{ route('admin.danisman.index') }}">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        <span class="hidden sm:inline">Geri Dön</span>
                        <span class="sm:hidden">Geri</span>
                    </x-neo.button>
                </div>
            </div>
        </div>
    </div>

    <!-- 🤖 Context7 AI Destekli Performans İstatistikleri -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4 md:gap-6 mb-8">
        <x-neo.card variant="primary" class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-blue-800">📊 Toplam İlan</h4>
                    <p class="text-2xl font-bold text-blue-900">{{ $performans['toplam_ilan'] }}</p>
                </div>
            </div>
        </x-neo.card>

        <x-neo.card variant="success" class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-green-500 to-emerald-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-green-800">✅ Aktif İlan</h4>
                    <p class="text-2xl font-bold text-green-900">{{ $performans['status_ilan'] }}</p>
                </div>
            </div>
        </x-neo.card>

        <x-neo.card variant="purple" class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-purple-500 to-violet-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-purple-800">🎯 Toplam Talep</h4>
                    <p class="text-2xl font-bold text-purple-900">{{ $performans['toplam_talep'] }}</p>
                </div>
            </div>
        </x-neo.card>

        <x-neo.card variant="warning" class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-orange-500 to-amber-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-orange-800">🤖 AI Başarı Oranı</h4>
                    <p class="text-2xl font-bold text-orange-900">{{ number_format($performans['basari_orani'], 1) }}%</p>
                </div>
            </div>
        </x-neo.card>

        <x-neo.card variant="danger" class="p-6">
            <div class="flex items-center">
                <div class="flex-shrink-0">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-red-500 to-pink-600 rounded-lg flex items-center justify-center">
                        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z" />
                        </svg>
                    </div>
                </div>
                <div class="ml-4">
                    <h4 class="text-sm font-medium text-red-800">⭐ Performans Puanı</h4>
                    <p class="text-2xl font-bold text-red-900">{{ $performans['performans_puani'] }}</p>
                </div>
            </div>
        </x-neo.card>
    </div>

    <!-- Remax Style Tabs -->
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-8 dark:shadow-none dark:border-slate-700">
        <div class="border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <nav class="flex -mb-px space-x-8 px-6" aria-label="Tabs">
                <a href="{{ route('admin.danisman.show', ['danisman' => $danisman->id, 't' => 'hakkimda']) }}"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 {{ $activeTab === 'hakkimda' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    <i class="fas fa-user mr-2"></i>
                    Hakkımda
                </a>
                <a href="{{ route('admin.danisman.show', ['danisman' => $danisman->id, 't' => 'portfoy']) }}"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 {{ $activeTab === 'portfoy' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    <i class="fas fa-briefcase mr-2"></i>
                    Portföy ({{ $performans['status_ilan'] }})
                </a>
                <a href="{{ route('admin.danisman.show', ['danisman' => $danisman->id, 't' => 'yorumlar']) }}"
                    class="py-4 px-1 border-b-2 font-medium text-sm transition-all duration-200 {{ $activeTab === 'yorumlar' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300' }}">
                    <i class="fas fa-star mr-2"></i>
                    Yorumlar ({{ $performans['onayli_yorum'] }})
                    @if ($performans['ortalama_rating'] > 0)
                        <span class="ml-1 text-xs">⭐ {{ $performans['ortalama_rating'] }}</span>
                    @endif
                </a>
            </nav>
        </div>

        <div class="p-6">
            @if ($activeTab === 'hakkimda')
                @include('admin.danisman.tabs.hakkimda')
            @elseif($activeTab === 'portfoy')
                @include('admin.danisman.tabs.portfoy')
            @elseif($activeTab === 'yorumlar')
                @include('admin.danisman.tabs.yorumlar')
            @endif
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const progressBars = document.querySelectorAll('.bg-gradient-to-r');
            progressBars.forEach(bar => {
                const width = bar.style.width;
                bar.style.width = '0%';
                setTimeout(() => {
                    bar.style.width = width;
                }, 500);
            });
        });
    </script>
@endpush
