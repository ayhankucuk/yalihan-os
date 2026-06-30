@extends('layouts.frontend')

@section('title', '#' . $tag->name . ' - Blog')
@section('description', $tag->name . ' etiketli yazılar')

@section('content')
    <div class="min-h-screen bg-gray-50 dark:bg-slate-900">
        <!-- Tag Header -->
        <div class="bg-gradient-to-r from-blue-500 to-purple-600 text-white py-16">
            <div class="container mx-auto px-4">
                <div class="max-w-4xl mx-auto text-center">
                    <div class="flex items-center justify-center mb-4">
                        <div class="w-16 h-16 rounded-full flex items-center justify-center text-white text-2xl"
                            style="background-color: {{ $tag->color ?? 'rgba(255,255,255,0.2)' }}">
                            <span class="material-symbols-outlined">label</span>
                        </div>
                    </div>
                    <h1 class="text-4xl md:text-5xl font-bold mb-4">#{{ $tag->name }}</h1>
                    <p class="text-xl text-white/90 max-w-2xl mx-auto">
                        {{ $tag->name }} konulu yazılar
                    </p>
                    <div class="mt-6 flex items-center justify-center space-x-6 text-white/80">
                        <span><span class="material-symbols-outlined mr-2">description</span>{{ $posts->total() }} yazı</span>
                        <span><span class="material-symbols-outlined mr-2">tag</span>{{ $tag->usage_count }} kullanım</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="container mx-auto px-4 py-12">
            <div class="max-w-7xl mx-auto">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Main Content -->
                    <div class="lg:col-span-2">
                        <!-- Sorting Options -->
                        <div class="flex items-center justify-between mb-8">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                                #{{ $tag->name }} Yazıları
                            </h2>

                            <div class="flex items-center space-x-4">
                                <label class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Sırala:</label>
                                <select onchange="window.location.href=this.value" class="form-select-sm">
                                    <option value="{{ route('blog.tag', ['slug' => $tag->slug, 'sort' => 'latest']) }}"
                                        {{ request('sort', 'latest') === 'latest' ? 'selected' : '' }}>En Yeni</option>
                                    <option value="{{ route('blog.tag', ['slug' => $tag->slug, 'sort' => 'popular']) }}"
                                        {{ request('sort') === 'popular' ? 'selected' : '' }}>En Popüler</option>
                                    <option value="{{ route('blog.tag', ['slug' => $tag->slug, 'sort' => 'oldest']) }}"
                                        {{ request('sort') === 'oldest' ? 'selected' : '' }}>En Eski</option>
                                </select>
                            </div>
                        </div>

                        <!-- Posts Grid -->
                        @if ($posts->isEmpty())
                            <div class="blog-card p-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400 mb-4" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.99 1.99 0 013 12V7a4 4 0 014-4z">
                                    </path>
                                </svg>
                                <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Bu etikette henüz yazı
                                    yok</h3>
                                <p class="text-gray-500 dark:text-gray-400">Bu etiket için yeni yazılar yayınlandığında
                                    burada görünecek.</p>
                                <div class="mt-6">
                                    <a href="{{ route('blog.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                                        <span class="material-symbols-outlined mr-2">arrow_back</span>
                                        Tüm Yazılara Dön
                                    </a>
                                </div>
                            </div>
                        @else
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                @foreach ($posts as $post)
                                    <article class="blog-card group">
                                        @if ($post->kapak_resmi)
                                            <div class="aspect-w-16 aspect-h-9 mb-6">
                                                <img src="{{ $post->kapak_resmi }}" alt="{{ $post->title }}"
                                                    class="w-full h-48 object-cover rounded-lg group-hover:scale-105 transition-transform duration-300">
                                            </div>
                                        @endif

                                        <div class="p-6">
                                            <div class="flex items-center justify-between mb-4">
                                                @if ($post->category)
                                                    <span class="category-badge text-white"
                                                        style="background-color: {{ $post->category->color ?? '#6366f1' }}">
                                                        {{ $post->category->name }}
                                                    </span>
                                                @endif
                                                @if ($post->one_cikan)
                                                    <span
                                                        class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400">
                                                        <span class="material-symbols-outlined mr-1">star</span>
                                                        Öne Çıkan
                                                    </span>
                                                @endif
                                            </div>

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
                                                    @foreach ($post->tags->take(4) as $postTag)
                                                        <a href="{{ route('blog.tag', $postTag->slug) }}"
                                                            class="inline-flex items-center px-2 py-1 rounded text-xs font-medium
                                                              {{ $postTag->id === $tag->id ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' : 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-300' }}
                                                              hover:bg-orange-100 hover:text-orange-800 dark:hover:bg-orange-900/30 dark:hover:text-orange-400 transition-colors">
                                                            #{{ $postTag->name }}
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

                    <!-- Sidebar -->
                    <div class="lg:col-span-1">
                        <div class="space-y-8">
                            <!-- Related Tags -->
                            @if ($sidebarData['popular_tags']->where('id', '!=', $tag->id)->isNotEmpty())
                                <div class="blog-card p-6">
                                    <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">İlgili Etiketler</h3>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($sidebarData['popular_tags']->where('id', '!=', $tag->id)->take(15) as $relatedTag)
<a href="{{ route('blog.tag', $relatedTag->slug) }}"
                                           class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200 hover:bg-orange-100 hover:text-orange-800 dark:hover:bg-orange-900/30 dark:hover:text-orange-400 transition-colors">
                                            #{{ $relatedTag->name }}
                                            <span class="ml-1 text-xs text-gray-500">{{ $relatedTag->posts_count }}</span>
                                        </a>
@endforeach
                                </div>
                            </div>
@endif

                        <!-- Categories -->
                        @if ($sidebarData['categories']->isNotEmpty())
                            <div class="blog-card p-6">
                                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kategoriler</h3>
                                <div class="space-y-2">
                                    @foreach ($sidebarData['categories'] as $category)
    <a href="{{ route('blog.category', $category->slug) }}"
                                                   class="flex items-center justify-between p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors group">
                                                    <div class="flex items-center space-x-3">
                                                        <div class="w-3 h-3 rounded-full" style="background-color: {{ $category->color ?? '#6366f1' }}"></div>
                                                        <span class="text-gray-700 dark:text-slate-200 group-hover:text-orange-600 dark:group-hover:text-orange-400 dark:text-slate-300">{{ $category->name }}</span>
                                                    </div>
                                                    <span class="text-sm text-gray-500 dark:text-gray-400">{{ $category->posts_count }}</span>
                                                </a>
     @endforeach
                                            </div>
                                            </div>
                                        @endif

                                        <!-- Popular Posts -->
                                        @if ($sidebarData['popular_posts']->isNotEmpty())
                                        <div class="blog-card p-6">
                                        <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Popüler
                                        Yazılar</h3>
                                        <div class="space-y-4">
                                        @foreach ($sidebarData['popular_posts'] as $popularPost)
                                        <div class="flex items-start space-x-3">
                                        @if ($popularPost->kapak_resmi)
                                        <img src="{{ $popularPost->kapak_resmi }}"
                                        alt="{{ $popularPost->title }}"
                                        class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                                    @else
                                        <div
                                        class="w-16 h-16 bg-gray-100 dark:bg-slate-900 rounded-lg flex items-center justify-center flex-shrink-0">
                                        <span class="material-symbols-outlined text-gray-400">image</span>
                                        </div>
                            @endif
                            <div class="flex-1 min-w-0">
                            <h4
                            class="text-sm font-medium text-gray-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400 transition-colors dark:text-slate-100">
                            <a
                            href="{{ route('blog.show', $popularPost->slug) }}">{{ $popularPost->title }}</a>
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
                            <div class="flex items-start space-x-3">
                            @if ($recentPost->kapak_resmi)
                            <img src="{{ $recentPost->kapak_resmi }}"
                            alt="{{ $recentPost->title }}"
                            class="w-16 h-16 object-cover rounded-lg flex-shrink-0">
                        @else
                            <div
                            class="w-16 h-16 bg-gray-100 dark:bg-slate-900 rounded-lg flex items-center justify-center flex-shrink-0">
                            <span class="material-symbols-outlined text-gray-400">image</span>
                            </div>
                            @endif
                            <div class="flex-1 min-w-0">
                            <h4
                            class="text-sm font-medium text-gray-900 dark:text-white hover:text-orange-600 dark:hover:text-orange-400 transition-colors dark:text-slate-100">
                            <a
                            href="{{ route('blog.show', $recentPost->slug) }}">{{ $recentPost->title }}</a>
                            </h4>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span class="material-symbols-outlined mr-1">calendar_today</span>{{ $recentPost->published_at->format('d.m.Y') }}
                            </p>
                            </div>
                            </div>
                            @endforeach
                            </div>
                            </div>
                            @endif

                            <!-- Newsletter Signup -->
                            <div class="blog-card p-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Bülten
                            Aboneliği</h3>
                            <p class="text-gray-600 dark:text-gray-400 text-sm mb-4">
                            Yeni yazılarımızdan haberdar olmak için e-posta adresinizi girin.
                            </p>
                            <form class="space-y-3">
                            <input type="email" placeholder="E-posta adresiniz"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg text-sm bg-white dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                            <button type="submit" class="w-full inline-flex items-center justify-center gap-2 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:bg-blue-700 dark:hover:bg-blue-800 dark:shadow-none">
                            <span class="material-symbols-outlined mr-2">mail</span>
                            Abone Ol
                            </button>
                            </form>
                            </div>
                            </div>
                            </div>
                            </div>
                            </div>
                            </div>
                            </div>
                        @endsection

                        @push('styles')
                            <style>
                            .blog-card {
                            @apply bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-gray-100
                            dark:border-gray-700 transition-all duration-300;
                            }

                            .blog-card:hover {
                            @apply shadow-lg transform -translate-y-1;
                            }

                            .category-badge {
                            @apply inline-flex items-center px-3 py-1 rounded-full text-xs font-medium;
                            }

                            .blog-meta {
                            @apply flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400;
                            }

                            .blog-meta span {
                            @apply flex items-center;
                            }
                            </style>
                        @endpush)
