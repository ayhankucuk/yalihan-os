@extends('admin.layouts.admin')

@section('title', 'Blog Yönetimi')
@section('page-title', 'Blog & Haber Yönetimi')

@section('content')
    <div class="prose max-w-none p-6">
        <!-- Page Header -->
        <div class="mb-6 pb-4 border-b border-gray-200 dark:border-slate-800 mb-8 dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Blog Yönetimi</h1>
                    <p class="text-gray-600 mt-2">Blog yazılarınızı yönetin ve içerik performansını takip edin</p>
                </div>
                <div class="flex space-x-3">
                    <a href="{{ route('admin.blog.posts.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized dark:shadow-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Yeni Yazı
                    </a>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Toplam Yazı</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $stats['total_posts'] }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Yayınlanan</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $stats['published_posts'] }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Bekleyen Yorum</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $stats['pending_comments'] }}</p>
                    </div>
                </div>
            </div>

            <div class="stat-card">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Toplam Görüntülenme</p>
                        <p class="text-2xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ number_format($stats['total_views']) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Posts & Quick Actions -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Posts -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-800 dark:text-white dark:text-slate-200">Son Yazılar</h3>
                            <a href="{{ route('admin.blog.posts.create') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Yeni Yazı
                            </a>
                        </div>
                    </div>

                    <div class="p-6">
                        @if (isset($recentPosts) && $recentPosts->count() > 0)
                            <div class="space-y-4">
                                @foreach ($recentPosts as $post)
                                    <div
                                        class="flex items-center space-x-4 p-4 bg-gray-50 rounded-xl hover:bg-gray-100 transition-colors duration-200 dark:bg-slate-900">
                                        <div class="flex-shrink-0">
                                            @if ($post->kapak_resmi)
                                                <img src="{{ asset('storage/' . $post->kapak_resmi) }}"
                                                    alt="{{ $post->title }}" class="w-16 h-16 rounded-lg object-cover">
                                            @else
                                                <div
                                                    class="w-16 h-16 bg-gray-200 rounded-lg flex items-center justify-center">
                                                    <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor"
                                                        viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2"
                                                            d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                                        </path>
                                                    </svg>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-medium text-gray-900 truncate dark:text-slate-100 dark:text-white">
                                                {{ $post->title }}
                                            </h4>
                                            <p class="text-sm text-gray-500">
                                                {{ $post->created_at ? $post->created_at->format('d.m.Y H:i') : 'Tarih Yok' }}
                                            </p>
                                            <div class="flex items-center mt-2 space-x-2">
                                                @php
                                                    $publishedStatus = 'published';
                                                    $draftStatus = 'draft';
                                                    $postStatus = $post->yayin_durumu;
                                                @endphp
                                                @if ($postStatus === $publishedStatus)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        Yayında
                                                    </span>
                                                @elseif($postStatus === $draftStatus)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800">
                                                        Taslak
                                                    </span>
                                                @else
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200">
                                                        {{ ucfirst($postStatus) }}
                                                    </span>
                                                @endif
                                                <span class="text-xs text-gray-500">
                                                    {{ $post->views ?? 0 }} görüntülenme
                                                </span>
                                            </div>
                                        </div>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('admin.blog.posts.edit', $post->id) }}"
                                                class="text-blue-600 hover:text-blue-900 transition-colors duration-200">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                                    </path>
                                                </svg>
                                            </a>
                                            <a href="{{ route('admin.blog.posts.show', $post->id) }}"
                                                class="text-green-600 hover:text-green-900 transition-colors duration-200">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor"
                                                    viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                                    </path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-8 text-gray-500">
                                <svg class="w-16 h-16 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                <p class="text-lg font-medium">Henüz blog yazısı bulunmuyor</p>
                                <p class="text-sm">İlk blog yazınızı oluşturmak için "Yeni Yazı" butonuna tıklayın</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Quick Actions Sidebar -->
            <div class="space-y-6">
                <!-- Quick Actions -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 dark:text-slate-200">Hızlı İşlemler</h3>
                    <div class="space-y-3">
                        <a href="{{ route('admin.blog.posts.create') }}" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-800 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Yeni Blog Yazısı
                        </a>
                        <a href="{{ route('admin.blog.categories.index') }}"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200 dark:text-slate-300">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                                </path>
                            </svg>
                            Kategorileri Yönet
                        </a>
                        <a href="{{ route('admin.blog.comments.index') }}"
                            class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 active:scale-95 focus:ring-2 focus:ring-gray-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition-all duration-200 dark:text-slate-300">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                </path>
                            </svg>
                            Yorumları Yönet
                        </a>
                    </div>
                </div>

                <!-- Blog Stats -->
                <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 p-6">
                    <h3 class="text-lg font-semibold text-gray-800 dark:text-white mb-4 dark:text-slate-200">Blog İstatistikleri</h3>
                    <div class="space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600 dark:text-gray-400">Toplam Kategori</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $stats['total_categories'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Toplam Etiket</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $stats['total_tags'] ?? 0 }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-600">Bu Ay Eklenen</span>
                            <span class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $stats['monthly_posts'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- View All Posts Button -->
        <div class="mt-8 text-center">
            <a href="{{ route('admin.blog.posts.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10">
                    </path>
                </svg>
                Tüm Blog Yazılarını Görüntüle
            </a>
        </div>
    </div>
@endsection

@push('styles')
    {{-- ✅ DUPLICATE REMOVED: Common styles moved to resources/css/admin/common-styles.css --}}
    {{-- Bu sayfada sadece sayfa-spesifik stiller varsa buraya eklenebilir --}}
@endpush
