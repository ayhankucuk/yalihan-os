@extends('admin.layouts.admin')

@section('title', $post->title)
@section('page-title', 'Blog Yazısı')

@push('styles')
    <style>
        /* Modern Dashboard Styles */
        .btn-modern {
            @apply inline-flex items-center px-6 py-3 rounded-xl font-semibold transition-all duration-200 transform hover:scale-105 active:scale-95 shadow-lg;
        }

        .btn-modern-primary {
            @apply bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 shadow-blue-500/25;
        }

        .btn-modern-secondary {
            @apply bg-gradient-to-r from-gray-600 to-gray-700 text-white hover:from-gray-700 hover:to-gray-800 shadow-gray-500/25;
        }

        /* Prose Styles */
        .prose {
            @apply text-gray-900;
        }

        .prose h1,
        .prose h2,
        .prose h3,
        .prose h4,
        .prose h5,
        .prose h6 {
            @apply text-gray-900 font-bold;
        }

        .prose p {
            @apply text-gray-700 leading-relaxed;
        }

        .prose ul,
        .prose ol {
            @apply text-gray-700;
        }

        .prose li {
            @apply text-gray-700;
        }

        .prose a {
            @apply text-blue-600 hover:text-blue-800 underline;
        }

        .prose blockquote {
            @apply border-l-4 border-blue-500 pl-4 italic text-gray-700;
        }

        .prose code {
            @apply bg-gray-100 text-gray-800 px-2 py-1 rounded text-sm;
        }

        .prose pre {
            @apply bg-gray-100 text-gray-800 p-4 rounded-lg overflow-x-auto;
        }
    </style>
@endpush

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">
        <!-- Modern Header -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-8 p-8 dark:shadow-none dark:border-slate-700">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center space-y-4 sm:space-y-0">
                <div class="min-w-0 flex-1">
                    <div class="flex items-center space-x-3 mb-2">
                        <h1
                            class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                            {{ $post->title }}</h1>

                        <!-- Status Badge -->
                        @php
                            $statusLabel = $post->yayin_durumu === 'published' ? 'Yayında' : ($post->yayin_durumu === 'draft' ? 'Taslak' : 'Programlı');
                        @endphp
                        <x-neo.status-badge :value="$statusLabel" category="status" />

                        @if ($post->one_cikan)
                            <x-neo.status-badge value="Öne Çıkan" category="flag" />
                        @endif

                        @if ($post->is_sticky)
                            <x-neo.status-badge value="Sabit" category="flag" />
                        @endif

                        @if ($post->is_breaking_news)
                            <x-neo.status-badge value="Son Dakika" category="flag" />
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center text-sm text-gray-500 space-x-4">
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z"
                                    clip-rule="evenodd"></path>
                            </svg>{{ $post->user->name }}
                        </span>
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"
                                    clip-rule="evenodd"></path>
                            </svg>{{ $post->created_at->format('d.m.Y H:i') }}
                        </span>
                        @if ($post->category)
                            <span class="flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z">
                                    </path>
                                </svg>{{ $post->category->name }}
                            </span>
                        @endif
                        <span class="flex items-center">
                            <svg class="w-4 h-4 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd"
                                    d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z"
                                    clip-rule="evenodd"></path>
                            </svg>{{ $post->reading_time_formatted }}
                        </span>
                    </div>
                </div>

                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.blog.posts.edit', $post) }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                            </path>
                        </svg>
                        Düzenle
                    </a>
                    <a href="{{ route('blog.show', $post->slug) }}" target="_blank" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path>
                        </svg>
                        Sitede Görüntüle
                    </a>
                    <a href="{{ route('admin.blog.posts.index') }}" class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Geri Dön
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Main Content -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Post Content -->
                <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                    <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                            <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                </path>
                            </svg>
                            İçerik
                        </h3>
                    </div>
                    <div class="p-6">
                        @if ($post->kapak_resmi)
                            <div class="mb-6">
                                <img src="{{ $post->kapak_resmi }}"
                                    alt="{{ $post->kapak_resmi_alt ?? $post->title }}"
                                    class="w-full max-w-2xl rounded-lg shadow-sm dark:shadow-none">
                                @if ($post->kapak_resmi_alt)
                                    <p class="text-sm text-gray-500 mt-2">
                                        {{ $post->kapak_resmi_alt }}</p>
                                @endif
                            </div>
                        @endif

                        @if ($post->excerpt)
                            <div class="bg-gray-50 rounded-lg p-4 mb-6 dark:bg-slate-900">
                                <h4 class="font-medium text-gray-900 mb-2 dark:text-slate-100 dark:text-white">Özet</h4>
                                <p class="text-gray-700 dark:text-slate-300">{{ $post->excerpt }}</p>
                            </div>
                        @endif

                        <div class="prose prose-gray max-w-none">
                            {!! $post->content !!}
                        </div>

                        @if ($post->tags->isNotEmpty())
                            <div class="mt-6 pt-6 border-t border-gray-200 dark:border-slate-700">
                                <h4 class="font-medium text-gray-900 mb-3 dark:text-slate-100 dark:text-white">Etiketler</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($post->tags as $tag)
                                        <span
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200">
                                            #{{ $tag->name }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- SEO Information -->
                @if ($post->meta_title || $post->meta_description || $post->meta_keywords)
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                SEO Bilgileri
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            @if ($post->meta_title)
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1 dark:text-slate-100 dark:text-white">Meta Başlık</h4>
                                    <p class="text-gray-700 dark:text-slate-300">{{ $post->meta_title }}</p>
                                </div>
                            @endif

                            @if ($post->meta_description)
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1 dark:text-slate-100 dark:text-white">Meta Açıklama</h4>
                                    <p class="text-gray-700 dark:text-slate-300">{{ $post->meta_description }}</p>
                                </div>
                            @endif

                            @if ($post->meta_keywords)
                                <div>
                                    <h4 class="font-medium text-gray-900 mb-1 dark:text-slate-100 dark:text-white">Anahtar Kelimeler</h4>
                                    <p class="text-gray-700 dark:text-slate-300">{{ $post->meta_keywords }}</p>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- Comments -->
                @if ($post->comments->isNotEmpty())
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <div class="flex items-center justify-between">
                                <h3 class="text-xl font-bold text-gray-800 dark:text-white dark:text-slate-200">Yorumlar ({{ $post->comments->count() }})</h3>
                                <a href="{{ route('admin.blog.comments.index', ['post' => $post->id]) }}"
                                    class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z">
                                        </path>
                                    </svg>
                                    Tüm Yorumları Yönet
                                </a>
                            </div>
                        </div>
                        <div class="p-6">
                            <div class="space-y-4">
                                @foreach ($post->comments()->latest()->limit(5)->get() as $comment)
                                    <div class="border border-gray-200 rounded-lg p-4 dark:border-slate-700">
                                        <div class="flex items-start justify-between">
                                            <div class="flex-1">
                                                <div class="flex items-center space-x-2 mb-2">
                                                    <h5 class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                                        {{ $comment->author_name }}</h5>
                                                    @php
                                                        $commentStatus = $comment->yayin_durumu ?? 'pending';
                                                    @endphp
                                                    <x-neo.status-badge :value="ucfirst($commentStatus)" category="status" />
                                                    <span
                                                        class="text-sm text-gray-500">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-gray-700 dark:text-slate-300">
                                                    {{ Str::limit($comment->content, 150) }}</p>
                                            </div>
                                            <div class="flex items-center space-x-2 ml-4">
                                                @if ($commentStatus === 'pending')
                                                    <form method="POST"
                                                        action="{{ route('admin.blog.comments.approve', $comment) }}"
                                                        class="inline">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-white bg-gradient-to-r from-green-600 to-emerald-600 rounded-lg hover:from-green-700 hover:to-emerald-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-green-500 transition-all duration-200 shadow-sm dark:shadow-none"
                                                            title="Onayla">
                                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                                <path fill-rule="evenodd"
                                                                    d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                                                                    clip-rule="evenodd"></path>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                                <button class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300" title="Reddet">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                        <path fill-rule="evenodd"
                                                            d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"
                                                            clip-rule="evenodd"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="space-y-6">
                <!-- Statistics -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                            <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 012 2v6a2 2 0 002 2h2a2 2 0 002-2v-6">
                                </path>
                            </svg>
                            İstatistikler
                        </h3>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="text-center">
                                <div class="stat-card-value text-blue-600">
                                    {{ number_format($stats['views_today']) }}</div>
                                <div class="text-sm text-gray-500">Bugün</div>
                            </div>
                            <div class="text-center">
                                <div class="stat-card-value text-green-600">
                                    {{ number_format($stats['views_week']) }}</div>
                                <div class="text-sm text-gray-500">Bu Hafta</div>
                            </div>
                            <div class="text-center">
                                <div class="stat-card-value text-purple-600">
                                    {{ number_format($stats['views_month']) }}</div>
                                <div class="text-sm text-gray-500">Bu Ay</div>
                            </div>
                            <div class="text-center">
                                <div class="stat-card-value text-orange-600">
                                    {{ number_format($post->view_count) }}</div>
                                <div class="text-sm text-gray-500">Toplam</div>
                            </div>
                        </div>

                        <hr class="border-gray-200 my-4 dark:border-slate-700">

                        <div class="space-y-3 text-sm">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Onaylı Yorumlar:</span>
                                <span class="font-medium">{{ $stats['comments_approved'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Bekleyen Yorumlar:</span>
                                <span class="font-medium">{{ $stats['comments_pending'] }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Ortalama Okuma Süresi:</span>
                                <span class="font-medium">{{ number_format($stats['avg_reading_time'] / 60, 1) }}dk</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tamamlama Oranı:</span>
                                <span class="font-medium">%{{ number_format($stats['completion_rate'], 1) }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                            <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                            </svg>
                            Hızlı İşlemler
                        </h3>
                    </div>
                    <div class="p-6 space-y-3">
                        <!-- Status Toggle -->
                        @if ($post->yayin_durumu === 'published')
                            <form method="POST" action="{{ route('admin.blog.posts.unpublish', $post) }}"
                                class="w-full">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.878 9.878L3 3m6.878 6.878L21 21">
                                        </path>
                                    </svg>
                                    Yayından Kaldır
                                </button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.blog.posts.publish', $post) }}" class="w-full">
                                @csrf
                                <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                        </path>
                                    </svg>
                                    Yayınla
                                </button>
                            </form>
                        @endif

                        <!-- Feature Toggle -->
                        <form method="POST" action="{{ route('admin.blog.posts.feature', $post) }}" class="w-full">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-amber-600 to-orange-600 rounded-lg hover:from-amber-700 hover:to-orange-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-amber-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z">
                                    </path>
                                </svg>
                                {{ $post->one_cikan ? 'Öne Çıkarmayı Kaldır' : 'Öne Çıkar' }}
                            </button>
                        </form>

                        <!-- Stick Toggle -->
                        <form method="POST" action="{{ route('admin.blog.posts.stick', $post) }}" class="w-full">
                            @csrf
                            <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd"
                                        d="M3 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1zm0 4a1 1 0 011-1h12a1 1 0 110 2H4a1 1 0 01-1-1z"
                                        clip-rule="evenodd"></path>
                                </svg>
                                {{ $post->is_sticky ? 'Sabitlemeyi Kaldır' : 'Sabit Yap' }}
                            </button>
                        </form>

                        <hr class="border-gray-200 dark:border-slate-800 dark:border-slate-700">

                        <!-- Delete -->
                        <form method="POST" action="{{ route('admin.blog.posts.destroy', $post) }}"
                            onsubmit="return confirm('Bu yazıyı silmek istediğinizden emin misiniz?')" class="w-full">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="inline-flex items-center justify-center w-full px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-red-600 to-pink-600 rounded-lg hover:from-red-700 hover:to-pink-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                    </path>
                                </svg>
                                Yazıyı Sil
                            </button>
                        </form>
                    </div>
                </div>

                <!-- Post Information -->
                <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                    <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                        <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                            <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Yazı Bilgileri
                        </h3>
                    </div>
                    <div class="p-6 space-y-3 text-sm">
                        <div class="flex justify-between">
                            <span class="text-gray-600">ID:</span>
                            <span class="font-medium">{{ $post->id }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Slug:</span>
                            <span class="font-medium text-xs">{{ $post->slug }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Oluşturulma:</span>
                            <span class="font-medium">{{ $post->created_at->format('d.m.Y H:i') }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Son Güncelleme:</span>
                            <span class="font-medium">{{ $post->updated_at->format('d.m.Y H:i') }}</span>
                        </div>
                        @if ($post->published_at)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Yayın Tarihi:</span>
                                <span class="font-medium">{{ $post->published_at->format('d.m.Y H:i') }}</span>
                            </div>
                        @endif
                        @if ($post->scheduled_at)
                            <div class="flex justify-between">
                                <span class="text-gray-600">Programlı Tarih:</span>
                                <span class="font-medium">{{ $post->scheduled_at->format('d.m.Y H:i') }}</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
