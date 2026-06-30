@foreach ($posts as $post)
    <article class="bg-white rounded-lg shadow-sm overflow-hidden hover:shadow-md transition-shadow group dark:bg-slate-900 dark:shadow-none">
        <!-- Görsel -->
        @if ($post->kapak_resmi)
            <div class="relative overflow-hidden">
                <img src="{{ asset('storage/' . $post->kapak_resmi) }}" alt="{{ $post->title }}"
                    class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">

                @if ($post->one_cikan)
                    <div class="absolute top-4 left-4">
                        <span class="bg-red-500 text-white px-2 py-1 text-xs rounded-full font-medium">
                            <span class="material-symbols-outlined mr-1">star</span>Öne Çıkan
                        </span>
                    </div>
                @endif

                @if ($post->is_breaking_news)
                    <div class="absolute top-4 right-4">
                        <span class="bg-red-600 text-white px-2 py-1 text-xs rounded-full font-bold animate-pulse">
                            <span class="material-symbols-outlined mr-1">bolt</span>SON DAKİKA
                        </span>
                    </div>
                @endif
            </div>
        @endif

        <!-- İçerik -->
        <div class="p-6">
            <!-- Meta Bilgiler -->
            <div class="flex items-center text-sm text-gray-500 mb-3">
                @if ($post->category)
                    <span class="px-2 py-1 rounded-full text-white text-xs font-medium mr-3"
                        style="background-color: {{ $post->category->color }}">
                        {{ $post->category->name }}
                    </span>
                @endif
                <span>{{ $post->published_at->format('d M Y') }}</span>
                <span class="mx-2">•</span>
                <span>{{ $post->author->name }}</span>
                <span class="mx-2">•</span>
                <span>{{ $post->view_count ?? 0 }} görüntüleme</span>
            </div>

            <!-- Başlık -->
            <h2 class="text-xl font-bold text-gray-900 mb-3 line-clamp-2 dark:text-slate-100 dark:text-white">
                <a href="{{ route('blog.show', $post) }}" class="hover:text-blue-600 transition-colors">
                    {{ $post->title }}
                </a>
            </h2>

            <!-- Özet -->
            @if ($post->excerpt)
                <p class="text-gray-600 mb-4 line-clamp-3">
                    {{ $post->excerpt }}
                </p>
            @else
                <p class="text-gray-600 mb-4 line-clamp-3">
                    {{ Str::limit(strip_tags($post->content), 150) }}
                </p>
            @endif

            <!-- Alt Bilgiler -->
            <div class="flex items-center justify-between">
                <!-- Etiketler -->
                <div class="flex flex-wrap gap-2">
                    @foreach ($post->tags->take(3) as $tag)
                        <a href="{{ route('blog.tag', $tag) }}"
                            class="px-2 py-1 text-xs bg-gray-100 text-gray-600 rounded hover:bg-gray-200 transition-colors dark:bg-slate-900">
                            #{{ $tag->name }}
                        </a>
                    @endforeach
                </div>

                <!-- Okuma Devam Et -->
                <a href="{{ route('blog.show', $post) }}"
                    class="inline-flex items-center text-blue-600 hover:text-blue-800 font-medium text-sm">
                    Devamını Oku
                    <span class="material-symbols-outlined ml-1 text-xs">arrow_forward</span>
                </a>
            </div>

            <!-- Sosyal ve Etkileşim -->
            @if ($post->comments_count > 0 || $post->like_count > 0)
                <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100 dark:border-slate-800">
                    <div class="flex items-center space-x-4 text-sm text-gray-500">
                        @if ($post->like_count > 0)
                            <span class="flex items-center">
                                <span class="material-symbols-outlined text-red-500 mr-1">favorite</span>
                                {{ $post->like_count }}
                            </span>
                        @endif

                        @if ($post->comments_count > 0)
                            <span class="flex items-center">
                                <span class="material-symbols-outlined text-blue-500 mr-1">chat_bubble</span>
                                {{ $post->comments_count }} yorum
                            </span>
                        @endif
                    </div>

                    <!-- Paylaşım Butonları -->
                    <div class="flex items-center space-x-2">
                        <button type="button" class="text-gray-400 hover:text-blue-600 transition-colors"
                            title="Facebook'ta Paylaş"
                            onclick='sharePost("facebook", @json(route('blog.show', $post)), @json($post->title))'>
                            <span class="material-symbols-outlined text-sm">share</span>
                        </button>
                        <button type="button" class="text-gray-400 hover:text-blue-400 transition-colors"
                            title="Twitter'da Paylaş"
                            onclick='sharePost("twitter", @json(route('blog.show', $post)), @json($post->title))'>
                            <span class="material-symbols-outlined text-sm">share</span>
                        </button>
                        <button type="button" class="text-gray-400 hover:text-green-600 transition-colors"
                            title="WhatsApp'ta Paylaş"
                            onclick='sharePost("whatsapp", @json(route('blog.show', $post)), @json($post->title))'>
                            <span class="material-symbols-outlined text-sm">chat</span>
                        </button>
                    </div>
                </div>
            @endif
        </div>
    </article>
@endforeach

<style>
    .line-clamp-2 {
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .line-clamp-3 {
        display: -webkit-box;
        -webkit-line-clamp: 3;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }
</style>

<script>
    function sharePost(platform, url, title) {
        let shareUrl = '';

        switch (platform) {
            case 'facebook':
                shareUrl = `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(url)}`;
                break;
            case 'twitter':
                shareUrl =
                    `https://twitter.com/intent/tweet?url=${encodeURIComponent(url)}&text=${encodeURIComponent(title)}`;
                break;
            case 'whatsapp':
                shareUrl = `https://wa.me/?text=${encodeURIComponent(title + ' ' + url)}`;
                break;
        }

        if (shareUrl) {
            window.open(shareUrl, '_blank', 'width=600,height=400');
        }
    }
</script>
