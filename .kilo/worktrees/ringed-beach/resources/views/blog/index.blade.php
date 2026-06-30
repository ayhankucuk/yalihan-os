@extends('layouts.frontend')

@section('title', 'Blog & Haberler')
@section('meta-description', 'Emlak sektörü ile ilgili güncel haberler, uzman analizleri ve faydalı ipuçları.')

@push('styles')
    <style>
        /* Blog specific styles */
        .blog-hero {
            @apply bg-gradient-to-br from-blue-600 via-purple-600 to-orange-600 relative overflow-hidden;
        }

        .blog-card {
            @apply bg-white dark:bg-gray-800 rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 border border-gray-100 dark:border-gray-700;
        }

        .blog-card:hover {
            @apply transform -translate-y-1;
        }

        .blog-meta {
            @apply text-sm text-gray-500 dark:text-gray-400 flex items-center space-x-4;
        }

        .blog-content img {
            @apply rounded-lg my-6 w-full h-auto;
        }

        .blog-content h2 {
            @apply text-2xl font-bold text-gray-900 dark:text-white mt-8 mb-4;
        }

        .blog-content h3 {
            @apply text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3;
        }

        .blog-content p {
            @apply text-gray-700 dark:text-gray-300 leading-relaxed mb-4;
        }

        .blog-content blockquote {
            @apply border-l-4 border-orange-500 pl-4 py-2 my-6 bg-orange-50 dark:bg-orange-900/20 italic;
        }

        .category-badge {
            @apply inline-flex items-center px-3 py-1 rounded-full text-sm font-medium;
        }
    </style>
@endpush

@section('content')
    <!-- Hero Section -->
    <section class="blog-hero py-16 lg:py-24">
        <div class="absolute inset-0 bg-black/30"></div>
        <div class="relative public-container">
            <div class="max-w-4xl mx-auto text-center text-white">
                <h1 class="text-4xl lg:text-6xl font-bold mb-6">
                    Blog & Haberler
                </h1>
                <p class="text-xl lg:text-2xl mb-8 opacity-90">
                    Emlak sektörü ile ilgili güncel haberler, uzman analizleri ve faydalı ipuçları
                </p>

                <!-- Search Form -->
                <form action="{{ route('blog.search') }}" method="GET" class="max-w-2xl mx-auto">
                    <div class="flex">
                        <input type="text" name="q" value="{{ request('q') }}"
                            placeholder="Blog yazılarında ara..."
                            class="flex-1 px-6 py-4 rounded-l-lg border-0 text-gray-900 placeholder-gray-500 focus:ring-2 focus:ring-orange-500 dark:text-slate-100 dark:text-white">
                        <button type="submit"
                            class="px-8 py-4 bg-orange-600 hover:bg-orange-700 text-white rounded-r-lg transition-colors">
                            <span class="material-symbols-outlined">search</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <!-- Blog Content -->
    <section class="public-section bg-gray-50 dark:bg-slate-900">
        <div class="public-container">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <!-- Featured Posts -->
                    @if ($featuredPosts->isNotEmpty())
                        <div class="mb-12">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-8 dark:text-slate-100">Öne Çıkan Yazılar</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach ($featuredPosts as $post)
                                    <article class="blog-card overflow-hidden">
                                        @if ($post->kapak_resmi)
                                            <div class="aspect-video overflow-hidden">
                                                <img src="{{ $post->kapak_resmi }}"
                                                    alt="{{ $post->kapak_resmi_alt ?? $post->title }}"
                                                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                            </div>
                                        @endif

                                        <div class="p-6">
                                            @if ($post->category)
                                                <div class="mb-3">
                                                    <span class="category-badge text-white"
                                                        style="background-color: {{ $post->category->color ?? '#6366f1' }}">
                                                        {{ $post->category->name }}
                                                    </span>
                                                </div>
                                            @endif

                                            <h3
                                                class="text-xl font-bold text-gray-900 dark:text-white mb-3 hover:text-orange-600 dark:hover:text-orange-400 transition-colors dark:text-slate-100">
                                                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                                            </h3>

                                            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $post->excerpt }}</p>

                                            <div class="blog-meta">
                                                <span><span class="material-symbols-outlined mr-1">calendar_today</span>{{ $post->published_at->format('d.m.Y') }}</span>
                                                <span><span class="material-symbols-outlined mr-1">person</span>{{ $post->user->name }}</span>
                                                <span><span class="material-symbols-outlined mr-1">visibility</span>{{ $post->view_count }}</span>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Recent Posts -->
                    <div class="mb-12">
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Son Yazılar</h2>

                            <!-- Sort Options -->
                            <div class="flex items-center space-x-4">
                                <label class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Sırala:</label>
                                <select onchange="window.location.href=this.value" class="form-select-sm">
                                    <option value="{{ route('blog.index', ['sort' => 'latest']) }}"
                                        {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>En Yeni</option>
                                    <option value="{{ route('blog.index', ['sort' => 'popular']) }}"
                                        {{ request('sort') === 'popular' ? 'selected' : '' }}>En Popüler</option>
                                    <option value="{{ route('blog.index', ['sort' => 'oldest']) }}"
                                        {{ request('sort') === 'oldest' ? 'selected' : '' }}>En Eski</option>
                                </select>
                            </div>
                        </div>

                        @if ($posts->isEmpty())
                            <div class="text-center py-12">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z">
                                    </path>
                                </svg>
                                <h3 class="mt-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Henüz yazı yok</h3>
                                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Yakında yeni yazılar eklenecek.</p>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach ($posts as $post)
                                    <article class="blog-card overflow-hidden">
                                        @if ($post->kapak_resmi)
                                            <div class="aspect-video overflow-hidden">
                                                <img src="{{ $post->kapak_resmi }}"
                                                    alt="{{ $post->kapak_resmi_alt ?? $post->title }}"
                                                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                            </div>
                                        @endif

                                        <div class="p-6">
                                            @if ($post->category)
                                                <div class="mb-3">
                                                    <a href="{{ route('blog.category', $post->category->slug) }}"
                                                        class="category-badge text-white hover:opacity-80 transition-opacity"
                                                        style="background-color: {{ $post->category->color ?? '#6366f1' }}">
                                                        {{ $post->category->name }}
                                                    </a>
                                                </div>
                                            @endif

                                            <h3
                                                class="text-xl font-bold text-gray-900 dark:text-white mb-3 hover:text-orange-600 dark:hover:text-orange-400 transition-colors dark:text-slate-100">
                                                <a href="{{ route('blog.show', $post->slug) }}">{{ $post->title }}</a>
                                            </h3>

                                            <p class="text-gray-600 dark:text-gray-400 mb-4">{{ $post->excerpt }}</p>

                                            <div class="blog-meta">
                                                <span><span class="material-symbols-outlined mr-1">calendar_today</span>{{ $post->published_at->format('d.m.Y') }}</span>
                                                <span><span class="material-symbols-outlined mr-1">person</span>{{ $post->user->name }}</span>
                                                <span><span class="material-symbols-outlined mr-1">visibility</span>{{ $post->view_count }}</span>
                                            </div>

                                            @if ($post->tags->isNotEmpty())
                                                <div class="mt-4 flex flex-wrap gap-2">
                                                    @foreach ($post->tags->take(3) as $tag)
                                                        <a href="{{ route('blog.tag', $tag->slug) }}"
                                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200 hover:bg-orange-100 hover:text-orange-800 dark:hover:bg-orange-900/30 dark:hover:text-orange-400 transition-colors">
                                                            #{{ $tag->name }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </article>
                                @endforeach
                            </div>

                            <!-- Pagination -->
                            @if ($posts->hasPages())
                                <div class="mt-12">
                                    {{ $posts->appends(request()->query())->links() }}
                                </div>
                            @endif
                        @endif
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="space-y-8">
                        <!-- Categories -->
                        @if ($sidebarData['categories']->isNotEmpty())
                            <div class="blog-card p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kategoriler</h3>
                                <div class="space-y-2">
                                    @foreach ($sidebarData['categories'] as $category)
                                        <a href="{{ route('blog.category', $category->slug) }}"
                                            class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-3 h-3 rounded-full"
                                                    style="background-color: {{ $category->color ?? '#6366f1' }}"></div>
                                                <span
                                                    class="text-gray-700 dark:text-slate-200 group-hover:text-orange-600 dark:group-hover:text-orange-400 dark:text-slate-300">{{ $category->name }}</span>
                                            </div>
                                            <span
                                                class="text-sm text-gray-500 dark:text-gray-400">{{ $category->posts_count }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Popular Posts -->
                        @if ($sidebarData['popular_posts']->isNotEmpty())
                            <div class="blog-card p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Popüler Yazılar</h3>
                                <div class="space-y-4">
                                    @foreach ($sidebarData['popular_posts'] as $popularPost)
                                        <div class="flex space-x-3">
                                            @if ($popularPost->kapak_resmi)
                                                <img src="{{ $popularPost->kapak_resmi }}"
                                                    alt="{{ $popularPost->title }}"
                                                    class="w-16 h-12 object-cover rounded">
                                            @else
                                                <div
                                                    class="w-16 h-12 bg-gray-200 dark:bg-slate-900 rounded flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-gray-400">image</span>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <h4
                                                    class="text-sm font-medium text-gray-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400 transition-colors dark:text-slate-100">
                                                    <a
                                                        href="{{ route('blog.show', $popularPost->slug) }}">{{ Str::limit($popularPost->title, 50) }}</a>
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    <span class="material-symbols-outlined mr-1">visibility</span>{{ $popularPost->view_count }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Recent Posts -->
                        @if ($sidebarData['recent_posts']->isNotEmpty())
                            <div class="blog-card p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Son Yazılar</h3>
                                <div class="space-y-4">
                                    @foreach ($sidebarData['recent_posts'] as $recentPost)
                                        <div class="flex space-x-3">
                                            @if ($recentPost->kapak_resmi)
                                                <img src="{{ $recentPost->kapak_resmi }}"
                                                    alt="{{ $recentPost->title }}"
                                                    class="w-16 h-12 object-cover rounded">
                                            @else
                                                <div
                                                    class="w-16 h-12 bg-gray-200 dark:bg-slate-900 rounded flex items-center justify-center">
                                                    <span class="material-symbols-outlined text-gray-400">image</span>
                                                </div>
                                            @endif
                                            <div class="flex-1 min-w-0">
                                                <h4
                                                    class="text-sm font-medium text-gray-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400 transition-colors dark:text-slate-100">
                                                    <a
                                                        href="{{ route('blog.show', $recentPost->slug) }}">{{ Str::limit($recentPost->title, 50) }}</a>
                                                </h4>
                                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                    {{ $recentPost->published_at->diffForHumans() }}
                                                </p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Tags -->
                        @if ($sidebarData['popular_tags']->isNotEmpty())
                            <div class="blog-card p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Popüler Etiketler</h3>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($sidebarData['popular_tags'] as $tag)
                                        <a href="{{ route('blog.tag', $tag->slug) }}"
                                            class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200 hover:bg-orange-100 hover:text-orange-800 dark:hover:bg-orange-900/30 dark:hover:text-orange-400 transition-colors">
                                            #{{ $tag->name }}
                                            <span class="ml-1 text-xs text-gray-500">{{ $tag->posts_count }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Newsletter Signup -->
                        <div class="blog-card p-6 bg-gradient-to-br from-orange-500 to-red-600 text-white">
                            <h3 class="text-lg font-bold mb-2">Bülten Aboneliği</h3>
                            <p class="text-sm opacity-90 mb-4">Yeni yazılarımızdan haberdar olmak için e-posta adresinizi
                                bırakın.</p>
                            <form class="space-y-3">
                                <input type="email" placeholder="E-posta adresiniz"
                                    class="w-full px-4 py-2.5 rounded text-gray-900 placeholder-gray-500 dark:text-slate-100 dark:text-white">
                                <button type="submit"
                                    class="w-full py-2 bg-white text-orange-600 rounded font-medium hover:bg-gray-100 transition-colors dark:bg-slate-900">
                                    Abone Ol
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
