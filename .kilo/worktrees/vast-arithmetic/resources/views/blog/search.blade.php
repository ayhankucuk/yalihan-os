@extends('layouts.frontend')

@section('title', 'Arama Sonuçları: ' . $query)

@section('content')
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl font-bold mb-4">Arama Sonuçları</h1>
                <p class="text-xl opacity-90">
                    "<strong>{{ $query }}</strong>" için {{ $posts->total() }} sonuç bulundu
                </p>

                <!-- Arama Formu -->
                <div class="mt-8 max-w-md mx-auto">
                    <form action="{{ route('blog.search') }}" method="GET" class="flex">
                        <input type="text" name="q" value="{{ $query }}"
                            placeholder="Blog yazılarında ara..."
                            class="flex-1 px-4 py-3 rounded-l-lg text-gray-900 border-0 focus:ring-2 focus:ring-blue-300 dark:text-slate-100 dark:text-white">
                        <button type="submit"
                            class="px-6 py-3 bg-blue-500 hover:bg-blue-600 rounded-r-lg transition-colors">
                            <span class="material-symbols-outlined">search</span>
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12">
        <div class="flex flex-wrap -mx-4">
            <!-- Ana İçerik -->
            <div class="w-full lg:w-2/3 px-4">
                @if ($posts->count() > 0)
                    <!-- Sıralama ve Filtreleme -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-8 dark:bg-slate-900 dark:shadow-none">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center space-x-4">
                                <span class="text-gray-600">Sırala:</span>
                                <select id="sortOrder" class="border border-gray-300 rounded-lg px-4 py-2.5">
                                    <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>En Yeni
                                    </option>
                                    <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>En Eski
                                    </option>
                                    <option value="popular" {{ request('sort') == 'popular' ? 'selected' : '' }}>En Popüler
                                    </option>
                                </select>
                            </div>

                            <div class="flex items-center space-x-4">
                                <span class="text-gray-600">Kategori:</span>
                                <select id="categoryFilter" class="border border-gray-300 rounded-lg px-4 py-2.5">
                                    <option value="">Tüm Kategoriler</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ request('category') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Sonuçlar -->
                    <div class="space-y-8">
                        @foreach ($posts as $post)
                            <article
                                class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow dark:bg-slate-900 dark:shadow-none">
                                <div class="md:flex">
                                    @if ($post->kapak_resmi)
                                        <div class="md:w-1/3">
                                            <img src="{{ asset('storage/' . $post->kapak_resmi) }}"
                                                alt="{{ $post->title }}" class="w-full h-48 md:h-full object-cover">
                                        </div>
                                    @endif

                                    <div class="p-6 {{ $post->kapak_resmi ? 'md:w-2/3' : 'w-full' }}">
                                        <div class="flex items-center space-x-4 text-sm text-gray-500 mb-3">
                                            <span>{{ $post->created_at->format('d M Y') }}</span>
                                            <span>•</span>
                                            <span>{{ $post->author->name }}</span>
                                            <span>•</span>
                                            <span>{{ $post->views }} görüntüleme</span>
                                        </div>

                                        <h2 class="text-xl font-bold text-gray-900 mb-3 dark:text-slate-100 dark:text-white">
                                            <a href="{{ route('blog.show', $post) }}"
                                                class="hover:text-blue-600 transition-colors">
                                                {!! str_ireplace($query, '<mark>' . $query . '</mark>', $post->title) !!}
                                            </a>
                                        </h2>

                                        <p class="text-gray-600 mb-4 line-clamp-3">
                                            {!! str_ireplace($query, '<mark>' . $query . '</mark>', Str::limit(strip_tags($post->content), 200)) !!}
                                        </p>

                                        <div class="flex items-center justify-between">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($post->categories->take(2) as $category)
                                                    <span class="px-2 py-1 text-xs rounded-full text-white"
                                                        style="background-color: {{ $category->color }}">
                                                        {{ $category->name }}
                                                    </span>
                                                @endforeach
                                            </div>

                                            <a href="{{ route('blog.show', $post) }}"
                                                class="text-blue-600 hover:text-blue-800 font-medium">
                                                Devamını Oku →
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    <!-- Sayfalama -->
                    <div class="mt-12">
                        {{ $posts->appends(request()->query())->links() }}
                    </div>
                @else
                    <!-- Sonuç Bulunamadı -->
                    <div class="bg-white rounded-lg shadow-sm p-12 text-center dark:bg-slate-900 dark:shadow-none">
                        <div class="text-gray-400 text-6xl mb-6">
                            <span class="material-symbols-outlined">search</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Sonuç Bulunamadı</h3>
                        <p class="text-gray-600 mb-8">
                            "<strong>{{ $query }}</strong>" için herhangi bir blog yazısı bulunamadı.
                        </p>

                        <div class="space-y-4">
                            <h4 class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Öneriler:</h4>
                            <ul class="text-sm text-gray-600 space-y-2">
                                <li>• Farklı anahtar kelimeler deneyin</li>
                                <li>• Daha genel terimler kullanın</li>
                                <li>• Yazım hatalarını kontrol edin</li>
                            </ul>
                        </div>

                        <div class="mt-8">
                            <a href="{{ route('blog.index') }}"
                                class="inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                <span class="material-symbols-outlined mr-2">arrow_back</span>
                                Tüm Blog Yazılarına Dön
                            </a>
                        </div>
                    </div>
                @endif
            </div>

            <!-- Sidebar -->
            <div class="w-full lg:w-1/3 px-4 mt-8 lg:mt-0">
                <!-- Popüler Aramalar -->
                @if (isset($popularSearches) && $popularSearches->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-8 dark:bg-slate-900 dark:shadow-none">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Popüler Aramalar</h3>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($popularSearches as $search)
                                <a href="{{ route('blog.search', ['q' => $search->query]) }}"
                                    class="px-3 py-1 bg-gray-100 hover:bg-gray-200 rounded-full text-sm text-gray-700 transition-colors dark:text-slate-300 dark:bg-slate-900">
                                    {{ $search->query }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                <!-- Kategoriler -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8 dark:bg-slate-900 dark:shadow-none">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Kategoriler</h3>
                    <div class="space-y-3">
                        @foreach ($categories as $category)
                            <a href="{{ route('blog.category', $category) }}"
                                class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors group">
                                <div class="flex items-center">
                                    <div class="w-3 h-3 rounded-full mr-3"
                                        style="background-color: {{ $category->color }}"></div>
                                    <span class="text-gray-700 group-hover:text-gray-900 dark:text-slate-300">{{ $category->name }}</span>
                                </div>
                                <span class="text-sm text-gray-500">{{ $category->posts_count }}</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Son Yazılar -->
                @if (isset($recentPosts) && $recentPosts->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm p-6 dark:bg-slate-900 dark:shadow-none">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Son Yazılar</h3>
                        <div class="space-y-4">
                            @foreach ($recentPosts as $recentPost)
                                <article class="flex space-x-3">
                                    @if ($recentPost->kapak_resmi)
                                        <img src="{{ asset('storage/' . $recentPost->kapak_resmi) }}"
                                            alt="{{ $recentPost->title }}"
                                            class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1 dark:text-slate-100 dark:text-white">
                                            <a href="{{ route('blog.show', $recentPost) }}" class="hover:text-blue-600">
                                                {{ $recentPost->title }}
                                            </a>
                                        </h4>
                                        <p class="text-xs text-gray-500">{{ $recentPost->created_at->diffForHumans() }}</p>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <style>
        mark {
            background-color: #fef08a;
            padding: 0 2px;
            border-radius: 2px;
        }

        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sortOrder = document.getElementById('sortOrder');
            const categoryFilter = document.getElementById('categoryFilter');

            function updateResults() {
                const params = new URLSearchParams(window.location.search);
                params.set('sort', sortOrder.value);
                params.set('category', categoryFilter.value);

                window.location.search = params.toString();
            }

            sortOrder.addEventListener('change', updateResults);
            categoryFilter.addEventListener('change', updateResults);
        });
    </script>
@endsection
