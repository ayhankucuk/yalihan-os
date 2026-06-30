@extends('admin.layouts.app')

@section('title', 'Yayın Tipi Şablonları')

@section('content')
    <div class="container mx-auto px-4 py-6">
        {{-- Header --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    Yayın Tipi Şablonları
                </h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    9 Master Template - Yayın Tipi Bazlı Sistem v2.0
                </p>
            </div>
            <div class="flex items-center space-x-3">
                <span
                    class="px-3 py-1 text-xs font-medium bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200 rounded-full">
                    {{ $stats['total_sablonlar'] }} Şablon
                </span>
                <span
                    class="px-3 py-1 text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200 rounded-full">
                    {{ $stats['total_assignments'] }} Atama
                </span>
                <span
                    class="px-3 py-1 text-xs font-medium bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200 rounded-full">
                    {{ $stats['total_features'] }} Özellik
                </span>
            </div>
        </div>

        {{-- Info Alert --}}
        <div class="mb-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
            <div class="flex items-start">
                <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd"
                        d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                        clip-rule="evenodd" />
                </svg>
                <div>
                    <h3 class="text-sm font-medium text-blue-800 dark:text-blue-200">
                        Yayın Tipi Bazlı Sistem
                    </h3>
                    <p class="text-sm text-blue-700 dark:text-blue-300 mt-1">
                        Artık tüm özellikler <strong>yayın tipine</strong> göre yönetiliyor.
                        Kategori seçimi artık özellik listesini etkilemiyor.
                        Her şablona atadığınız özellikler, o yayın tipinin tüm kategorilerinde görünecek.
                    </p>
                </div>
            </div>
        </div>

        {{-- Şablon Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach ($sablonlar as $sablon)
                <div
                    class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden hover:shadow-md transition-shadow dark:shadow-none dark:border-slate-700">
                    <div class="p-5">
                        {{-- Header --}}
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $sablon->ad }}
                            </h3>
                            <span
                                class="px-2.5 py-0.5 text-xs font-medium rounded-full
                        {{ $sablon->aktiflik_durumu ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' : 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' }}">
                                {{ $sablon->aktiflik_durumu ? 'Aktif' : 'Pasif' }}
                            </span>
                        </div>

                        {{-- Slug Badge --}}
                        <div class="mb-4">
                            <code
                                class="px-2 py-1 text-xs bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded dark:bg-slate-900 dark:text-slate-300">
                                {{ $sablon->slug }}
                            </code>
                        </div>

                        {{-- Feature Count --}}
                        <div class="flex items-center text-sm text-gray-600 dark:text-gray-400 mb-4">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M4 6h16M4 10h16M4 14h16M4 18h16" />
                            </svg>
                            <span><strong>{{ $sablon->feature_assignments_count }}</strong> özellik atanmış</span>
                        </div>

                        {{-- Progress Bar --}}
                        @php
                            $coverage =
                                $stats['total_features'] > 0
                                    ? round(($sablon->feature_assignments_count / $stats['total_features']) * 100)
                                    : 0;
                        @endphp
                        <div class="mb-4">
                            <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mb-1">
                                <span>Kapsam</span>
                                <span>{{ $coverage }}%</span>
                            </div>
                            <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                                <div class="bg-blue-600 h-2 rounded-full transition-all"
                                    style="width: {{ $coverage }}%"></div>
                            </div>
                        </div>

                        {{-- Actions --}}
                        <div class="flex space-x-2">
                            <a href="{{ route('admin.property-hub.yayin-tipi-sablonlari.show', $sablon->id) }}"
                                class="flex-1 inline-flex items-center justify-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                Düzenle
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Feature Kategorileri Özeti --}}
        <div class="mt-8">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                Tüm Özellikler ({{ $features->count() }})
            </h2>
            <div
                class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none dark:border-slate-700">
                <div class="p-4 grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                    @foreach ($features->groupBy(fn($f) => $f->category->ad ?? 'Diğer') as $categoryName => $categoryFeatures)
                        <div class="p-3 bg-gray-50 dark:bg-gray-700/50 rounded-lg dark:bg-slate-900">
                            <h4 class="font-medium text-sm text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                {{ $categoryName }}
                            </h4>
                            <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $categoryFeatures->count() }}
                            </p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">özellik</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
@endsection
