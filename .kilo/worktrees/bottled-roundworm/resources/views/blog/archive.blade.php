@extends('layouts.frontend')

@section('title', 'Blog Arşivi - ' . $year . ($month ? ' / ' . $monthName : ''))

@section('content')
    <div class="bg-gradient-to-r from-gray-600 to-gray-800 text-white py-16">
        <div class="container mx-auto px-4">
            <div class="max-w-4xl mx-auto text-center">
                <h1 class="text-4xl font-bold mb-4">Blog Arşivi</h1>
                <p class="text-xl opacity-90">
                    {{ $year }} {{ $month ? $monthName : 'Yılı' }} - {{ $posts->total() }} yazı
                </p>

                <!-- Arşiv Navigasyonu -->
                <div class="mt-8 flex flex-wrap justify-center gap-4">
                    @if ($month)
                        <a href="{{ route('blog.archive.year', $year) }}"
                            class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg transition-colors dark:bg-slate-900/10 dark:bg-slate-800/40">
                            <span class="material-symbols-outlined mr-2">arrow_back</span>
                            {{ $year }} Yılı
                        </a>
                    @endif
                    <a href="{{ route('blog.index') }}"
                        class="px-4 py-2 bg-white/10 hover:bg-white/20 rounded-lg transition-colors dark:bg-slate-900/10 dark:bg-slate-800/40">
                        <span class="material-symbols-outlined mr-2">home</span>
                        Ana Sayfa
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container mx-auto px-4 py-12">
        <div class="flex flex-wrap -mx-4">
            <!-- Ana İçerik -->
            <div class="w-full lg:w-2/3 px-4">
                @if ($posts->count() > 0)
                    <!-- Filtreleme -->
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-8 dark:bg-slate-900 dark:shadow-none">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div class="flex items-center space-x-4">
                                <span class="text-gray-600">Sırala:</span>
                                <select id="sortOrder" class="border border-gray-300 rounded-lg px-4 py-2.5">
                                    <option value="newest">En Yeni</option>
                                    <option value="oldest">En Eski</option>
                                    <option value="popular">En Popüler</option>
                                </select>
                            </div>

                            <div class="flex items-center space-x-4">
                                <span class="text-gray-600">Kategori:</span>
                                <select id="categoryFilter" class="border border-gray-300 rounded-lg px-4 py-2.5">
                                    <option value="">Tüm Kategoriler</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Zaman Çizelgesi -->
                    <div class="space-y-8">
                        @php
                            $currentMonth = null;
                        @endphp

                        @foreach ($posts as $post)
                            @php
                                $postMonth = $post->created_at->format('Y-m');
                            @endphp

                            @if ($currentMonth !== $postMonth && !$month)
                                @php $currentMonth = $postMonth; @endphp
                                <div class="flex items-center my-8">
                                    <div class="flex-1 h-px bg-gray-300"></div>
                                    <div class="px-4 py-2 bg-gray-100 rounded-lg text-sm font-medium text-gray-600 dark:bg-slate-900">
                                        {{ $post->created_at->format('F Y') }}
                                    </div>
                                    <div class="flex-1 h-px bg-gray-300"></div>
                                </div>
                            @endif

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
                                            @if ($post->comments_count > 0)
                                                <span>•</span>
                                                <span>{{ $post->comments_count }} yorum</span>
                                            @endif
                                        </div>

                                        <h2 class="text-xl font-bold text-gray-900 mb-3 dark:text-slate-100 dark:text-white">
                                            <a href="{{ route('blog.show', $post) }}"
                                                class="hover:text-blue-600 transition-colors">
                                                {{ $post->title }}
                                            </a>
                                        </h2>

                                        <p class="text-gray-600 mb-4 line-clamp-3">
                                            {{ Str::limit(strip_tags($post->content), 200) }}
                                        </p>

                                        <div class="flex items-center justify-between">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach ($post->categories->take(2) as $category)
                                                    <span class="px-2 py-1 text-xs rounded-full text-white"
                                                        style="background-color: {{ $category->color }}">
                                                        {{ $category->name }}
                                                    </span>
                                                @endforeach

                                                @if ($post->tags->count() > 0)
                                                    <div class="flex flex-wrap gap-1 ml-2">
                                                        @foreach ($post->tags->take(3) as $tag)
                                                            <span
                                                                class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded dark:bg-slate-900">
                                                                #{{ $tag->name }}
                                                            </span>
                                                        @endforeach
                                                    </div>
                                                @endif
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
                        {{ $posts->links() }}
                    </div>
                @else
                    <!-- Sonuç Bulunamadı -->
                    <div class="bg-white rounded-lg shadow-sm p-12 text-center dark:bg-slate-900 dark:shadow-none">
                        <div class="text-gray-400 text-6xl mb-6">
                            <span class="material-symbols-outlined">calendar_month</span>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Bu Dönemde Yazı Bulunamadı</h3>
                        <p class="text-gray-600 mb-8">
                            {{ $year }} {{ $month ? $monthName : 'yılı' }}nda henüz blog yazısı yayınlanmamış.
                        </p>

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
                <!-- Arşiv Yılları -->
                <div class="bg-white rounded-lg shadow-sm p-6 mb-8 dark:bg-slate-900 dark:shadow-none">
                    <h3 class="text-lg font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Arşiv Yılları</h3>
                    <div class="space-y-2">
                        @foreach ($archiveYears as $archiveYear)
                            <a href="{{ route('blog.archive.year', $archiveYear->year) }}"
                                class="flex items-center justify-between p-3 rounded-lg hover:bg-gray-50 transition-colors {{ $archiveYear->year == $year ? 'bg-blue-50 text-blue-600' : 'text-gray-700' }}">
                                <span class="font-medium">{{ $archiveYear->year }}</span>
                                <span class="text-sm text-gray-500">{{ $archiveYear->posts_count }} yazı</span>
                            </a>
                        @endforeach
                    </div>
                </div>

                <!-- Aylık Arşiv (Sadece yıl seçiliyse) -->
                @if (!$month && isset($archiveMonths))
                    <div class="bg-white rounded-lg shadow-sm p-6 mb-8 dark:bg-slate-900 dark:shadow-none">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">{{ $year }} Ayları</h3>
                        <div class="grid grid-cols-2 gap-2">
                            @foreach ($archiveMonths as $archiveMonth)
                                <a href="{{ route('blog.archive.month', [$year, str_pad($archiveMonth->month, 2, '0', STR_PAD_LEFT)]) }}"
                                    class="flex flex-col items-center p-3 rounded-lg hover:bg-gray-50 transition-colors text-center">
                                    <span class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                        {{ Carbon\Carbon::create($year, $archiveMonth->month)->format('F') }}
                                    </span>
                                    <span class="text-xs text-gray-500">{{ $archiveMonth->posts_count }} yazı</span>
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

                <!-- Popüler Yazılar -->
                @if (isset($popularPosts) && $popularPosts->count() > 0)
                    <div class="bg-white rounded-lg shadow-sm p-6 dark:bg-slate-900 dark:shadow-none">
                        <h3 class="text-lg font-bold text-gray-900 mb-4 dark:text-slate-100 dark:text-white">Bu Dönemin Popüler Yazıları</h3>
                        <div class="space-y-4">
                            @foreach ($popularPosts as $popularPost)
                                <article class="flex space-x-3">
                                    @if ($popularPost->kapak_resmi)
                                        <img src="{{ asset('storage/' . $popularPost->kapak_resmi) }}"
                                            alt="{{ $popularPost->title }}"
                                            class="w-16 h-16 rounded-lg object-cover flex-shrink-0">
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <h4 class="text-sm font-medium text-gray-900 line-clamp-2 mb-1 dark:text-slate-100 dark:text-white">
                                            <a href="{{ route('blog.show', $popularPost) }}" class="hover:text-blue-600">
                                                {{ $popularPost->title }}
                                            </a>
                                        </h4>
                                        <div class="flex items-center text-xs text-gray-500 space-x-2">
                                            <span>{{ $popularPost->created_at->format('d M Y') }}</span>
                                            <span>•</span>
                                            <span>{{ $popularPost->views }} görüntüleme</span>
                                        </div>
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
        .line-clamp-3 {
            display: -webkit-box;
            -webkit-line-clamp: 3;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
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
