@extends('layouts.frontend')

@section('title', $post->meta_title ?: $post->title)
@section('meta-description', $post->meta_description ?: $post->excerpt_or_content)
@section('meta-keywords', $post->meta_keywords)

@push('styles')
    <style>
        /* Blog post specific styles */
        .blog-post-header {
            @apply bg-gradient-to-br from-gray-900 via-gray-800 to-gray-900 relative overflow-hidden;
        }

        .blog-post-content {
            @apply prose prose-lg prose-gray dark:prose-invert max-w-none;
        }

        .blog-post-content img {
            @apply rounded-lg shadow-lg my-8 w-full h-auto;
        }

        .blog-post-content h1 {
            @apply text-3xl font-bold text-gray-900 dark:text-white mt-8 mb-4;
        }

        .blog-post-content h2 {
            @apply text-2xl font-bold text-gray-900 dark:text-white mt-8 mb-4;
        }

        .blog-post-content h3 {
            @apply text-xl font-semibold text-gray-900 dark:text-white mt-6 mb-3;
        }

        .blog-post-content p {
            @apply text-gray-700 dark:text-gray-300 leading-relaxed mb-6;
        }

        .blog-post-content blockquote {
            @apply border-l-4 border-orange-500 pl-6 py-4 my-8 bg-orange-50 dark:bg-orange-900/20 italic text-lg;
        }

        .blog-post-content ul,
        .blog-post-content ol {
            @apply my-6 pl-6 space-y-2;
        }

        .blog-post-content li {
            @apply text-gray-700 dark:text-gray-300;
        }

        .blog-post-content a {
            @apply text-orange-600 dark:text-orange-400 hover:underline;
        }

        .blog-post-content table {
            @apply w-full border-collapse border border-gray-300 dark:border-gray-600 my-8;
        }

        .blog-post-content th,
        .blog-post-content td {
            @apply border border-gray-300 dark:border-gray-600 px-4 py-2 text-left;
        }

        .blog-post-content th {
            @apply bg-gray-50 dark:bg-gray-800 font-semibold;
        }

        .comment-form {
            @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
        }

        .comment-card {
            @apply bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-gray-200 dark:border-gray-700 p-6;
        }

        .comment-reply {
            @apply ml-12 mt-4;
        }

        .social-share-btn {
            @apply inline-flex items-center justify-center w-10 h-10 rounded-full text-white transition-all duration-200 hover:scale-110;
        }
    </style>
@endpush

@section('content')
    <!-- Post Header -->
    <section class="blog-post-header py-16 lg:py-20">
        <div class="absolute inset-0 bg-black/50"></div>
        <div class="relative public-container">
            <div class="max-w-4xl mx-auto text-center text-white">
                @if ($post->category)
                    <div class="mb-4">
                        <a href="{{ route('blog.category', $post->category->slug) }}"
                            class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium text-white border border-white/30 hover:bg-white/10 transition-colors"
                            style="border-color: {{ $post->category->color ?? '#ffffff' }}; color: {{ $post->category->color ?? '#ffffff' }}">
                            {{ $post->category->name }}
                        </a>
                    </div>
                @endif

                <h1 class="text-3xl lg:text-5xl font-bold mb-6">{{ $post->title }}</h1>

                @if ($post->excerpt)
                    <p class="text-xl lg:text-2xl mb-8 opacity-90">{{ $post->excerpt }}</p>
                @endif

                <div class="flex flex-wrap items-center justify-center text-sm space-x-6 opacity-90">
                    <div class="flex items-center space-x-2">
                        <span class="material-symbols-outlined">person</span>
                        <span>{{ $post->user->name }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="material-symbols-outlined">calendar_today</span>
                        <span>{{ $post->published_at->format('d.m.Y') }}</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="material-symbols-outlined">schedule</span>
                        <span>{{ $post->reading_time_formatted }} okuma</span>
                    </div>
                    <div class="flex items-center space-x-2">
                        <span class="material-symbols-outlined">visibility</span>
                        <span>{{ number_format($post->view_count) }} görüntülenme</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Post Content -->
    <section class="public-section bg-white dark:bg-slate-900">
        <div class="public-container">
            <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-3">
                    <article
                        class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden dark:shadow-none">
                        @if ($post->kapak_resmi)
                            <div class="aspect-video overflow-hidden">
                                <img src="{{ $post->kapak_resmi }}"
                                    alt="{{ $post->kapak_resmi_alt ?? $post->title }}"
                                    class="w-full h-full object-cover">
                            </div>
                        @endif

                        <div class="p-8 lg:p-12">
                            <!-- Breaking News Badge -->
                            @if ($post->is_breaking_news)
                                <div class="mb-6">
                                    <span
                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                                        <span class="material-symbols-outlined mr-2">error</span>
                                        Son Dakika
                                    </span>
                                </div>
                            @endif

                            <!-- Content -->
                            <div class="blog-post-content">
                                {!! $post->content !!}
                            </div>

                            <!-- Tags -->
                            @if ($post->tags->isNotEmpty())
                                <div class="mt-12 pt-8 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Etiketler</h3>
                                    <div class="flex flex-wrap gap-2">
                                        @foreach ($post->tags as $tag)
                                            <a href="{{ route('blog.tag', $tag->slug) }}"
                                                class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 text-gray-800 dark:bg-slate-900 dark:text-slate-200 hover:bg-orange-100 hover:text-orange-800 dark:hover:bg-orange-900/30 dark:hover:text-orange-400 transition-colors">
                                                #{{ $tag->name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Social Share -->
                            <div class="mt-8 pt-8 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Paylaş</h3>
                                <div class="flex items-center space-x-3">
                                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(request()->url()) }}"
                                        target="_blank" class="social-share-btn bg-blue-600 hover:bg-blue-700"
                                        title="Facebook'ta Paylaş">
                                        <span class="material-symbols-outlined">share</span>
                                    </a>
                                    <a href="https://twitter.com/intent/tweet?url={{ urlencode(request()->url()) }}&text={{ urlencode($post->title) }}"
                                        target="_blank" class="social-share-btn bg-sky-500 hover:bg-sky-600"
                                        title="Twitter'da Paylaş">
                                        <span class="material-symbols-outlined">share</span>
                                    </a>
                                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ urlencode(request()->url()) }}"
                                        target="_blank" class="social-share-btn bg-blue-700 hover:bg-blue-800"
                                        title="LinkedIn'de Paylaş">
                                        <span class="material-symbols-outlined">share</span>
                                    </a>
                                    <a href="https://wa.me/?text={{ urlencode($post->title . ' - ' . request()->url()) }}"
                                        target="_blank" class="social-share-btn bg-green-500 hover:bg-green-600"
                                        title="WhatsApp'ta Paylaş">
                                        <span class="material-symbols-outlined">chat</span>
                                    </a>
                                    <button onclick='copyToClipboard(@json(request()->url()))'
                                        class="social-share-btn bg-gray-600 hover:bg-gray-700" title="Linki Kopyala">
                                        <span class="material-symbols-outlined">link</span>
                                    </button>
                                </div>
                            </div>

                            <!-- Post Navigation -->
                            @if ($prevPost || $nextPost)
                                <div class="mt-12 pt-8 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                        @if ($prevPost)
                                            <a href="{{ route('blog.show', $prevPost->slug) }}"
                                                class="group block p-4 rounded-lg border border-gray-200 dark:border-slate-800 hover:border-orange-300 dark:hover:border-orange-600 transition-colors dark:border-slate-700">
                                                <div
                                                    class="flex items-center text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                    <span class="material-symbols-outlined mr-2">arrow_back</span>
                                                    Önceki Yazı
                                                </div>
                                                <h4
                                                    class="font-medium text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors dark:text-slate-100">
                                                    {{ Str::limit($prevPost->title, 60) }}
                                                </h4>
                                            </a>
                                        @endif

                                        @if ($nextPost)
                                            <a href="{{ route('blog.show', $nextPost->slug) }}"
                                                class="group block p-4 rounded-lg border border-gray-200 dark:border-slate-800 hover:border-orange-300 dark:hover:border-orange-600 transition-colors text-right dark:border-slate-700">
                                                <div
                                                    class="flex items-center justify-end text-sm text-gray-500 dark:text-gray-400 mb-2">
                                                    Sonraki Yazı
                                                    <span class="material-symbols-outlined ml-2">arrow_forward</span>
                                                </div>
                                                <h4
                                                    class="font-medium text-gray-900 dark:text-white group-hover:text-orange-600 dark:group-hover:text-orange-400 transition-colors dark:text-slate-100">
                                                    {{ Str::limit($nextPost->title, 60) }}
                                                </h4>
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            @endif
                        </div>
                    </article>

                    <!-- Related Posts -->
                    @if ($relatedPosts->isNotEmpty())
                        <div class="mt-12">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-8 dark:text-slate-100">İlgili Yazılar</h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                @foreach ($relatedPosts as $relatedPost)
                                    <article
                                        class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 overflow-hidden hover:shadow-lg transition-shadow dark:shadow-none">
                                        @if ($relatedPost->kapak_resmi)
                                            <div class="aspect-video overflow-hidden">
                                                <img src="{{ $relatedPost->kapak_resmi }}"
                                                    alt="{{ $relatedPost->title }}"
                                                    class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                                            </div>
                                        @endif

                                        <div class="p-6">
                                            @if ($relatedPost->category)
                                                <div class="mb-3">
                                                    <span
                                                        class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium text-white"
                                                        style="background-color: {{ $relatedPost->category->color ?? '#6366f1' }}">
                                                        {{ $relatedPost->category->name }}
                                                    </span>
                                                </div>
                                            @endif

                                            <h3
                                                class="text-lg font-bold text-gray-900 dark:text-white mb-3 hover:text-orange-600 dark:hover:text-orange-400 transition-colors dark:text-slate-100">
                                                <a
                                                    href="{{ route('blog.show', $relatedPost->slug) }}">{{ $relatedPost->title }}</a>
                                            </h3>

                                            <p class="text-gray-600 dark:text-gray-400 mb-4">
                                                {{ Str::limit($relatedPost->excerpt, 100) }}</p>

                                            <div
                                                class="flex items-center text-sm text-gray-500 dark:text-gray-400 space-x-4">
                                                <span><span class="material-symbols-outlined mr-1">calendar_today</span>{{ $relatedPost->published_at->format('d.m.Y') }}</span>
                                                <span><span class="material-symbols-outlined mr-1">visibility</span>{{ $relatedPost->view_count }}</span>
                                            </div>
                                        </div>
                                    </article>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <!-- Comments Section -->
                    @if ($post->allow_comments)
                        <div class="mt-12" id="comments">
                            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-8 dark:text-slate-100">
                                Yorumlar ({{ $comments->total() }})
                            </h2>

                            <!-- Comment Form -->
                            <div class="comment-form mb-8">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Yorum Yap</h3>

                                <form method="POST" action="{{ route('blog.comments.store', $post) }}"
                                    class="space-y-6">
                                    @csrf

                                    @guest
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <x-form.input name="guest_name" label="Adınız" required placeholder="Adınız" />
                                            <x-form.input type="email" name="guest_email" label="E-posta" required
                                                placeholder="ornek@mail.com" />
                                        </div>
                                        <x-form.input name="guest_website" label="Web Sitesi (Opsiyonel)" type="url"
                                            placeholder="https://..." />
                                    @endguest

                                    <x-form.textarea name="content" label="Yorumunuz" rows="4" required
                                        placeholder="Yorumunuzu yazın..." />

                                    <div>
                                        <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg dark:shadow-none">
                                            <span class="material-symbols-outlined mr-2">chat_bubble</span>
                                            Yorum Gönder
                                        </button>
                                    </div>
                                </form>
                            </div>

                            <!-- Comments List -->
                            @if ($comments->isNotEmpty())
                                <div class="space-y-6">
                                    @foreach ($comments as $comment)
                                        <div class="comment-card" id="comment-{{ $comment->id }}">
                                            <div class="flex items-start space-x-4">
                                                <div class="flex-shrink-0">
                                                    @if ($comment->user)
                                                        <div
                                                            class="w-12 h-12 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                                                            <span
                                                                class="text-lg font-medium text-orange-600 dark:text-orange-400">
                                                                {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                                            </span>
                                                        </div>
                                                    @else
                                                        <div
                                                            class="w-12 h-12 bg-gray-100 dark:bg-slate-900 rounded-full flex items-center justify-center">
                                                            <span class="material-symbols-outlined text-gray-400">person</span>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="flex-1">
                                                    <div class="flex items-center space-x-3 mb-2">
                                                        <h4 class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                            {{ $comment->author_name }}</h4>
                                                        <span
                                                            class="text-sm text-gray-500 dark:text-gray-400">{{ $comment->created_at->diffForHumans() }}</span>
                                                        @if ($comment->user)
                                                            <span
                                                                class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                                Üye
                                                            </span>
                                                        @endif
                                                    </div>

                                                    <p class="text-gray-700 dark:text-slate-200 mb-4 dark:text-slate-300">
                                                        {{ $comment->content }}</p>

                                                    <div class="flex items-center space-x-4 text-sm">
                                                        <button onclick="likeComment({{ $comment->id }})"
                                                            class="flex items-center space-x-1 text-gray-500 hover:text-green-600 transition-colors">
                                                            <span class="material-symbols-outlined">thumb_up</span>
                                                            <span
                                                                id="likes-{{ $comment->id }}">{{ $comment->like_count }}</span>
                                                        </button>

                                                        <button onclick="dislikeComment({{ $comment->id }})"
                                                            class="flex items-center space-x-1 text-gray-500 hover:text-red-600 transition-colors">
                                                            <span class="material-symbols-outlined">thumb_down</span>
                                                            <span
                                                                id="dislikes-{{ $comment->id }}">{{ $comment->dislike_count }}</span>
                                                        </button>

                                                        <button onclick="toggleReplyForm({{ $comment->id }})"
                                                            class="text-gray-500 hover:text-orange-600 transition-colors">
                                                            <span class="material-symbols-outlined mr-1">reply</span>
                                                            Yanıtla
                                                        </button>
                                                    </div>

                                                    <!-- Reply Form -->
                                                    <div id="reply-form-{{ $comment->id }}"
                                                        class="hidden mt-4 p-4 bg-gray-50 dark:bg-slate-900 rounded-lg">
                                                        <form method="POST"
                                                            action="{{ route('blog.comments.store', $post) }}">
                                                            @csrf
                                                            <input type="hidden" name="parent_id"
                                                                value="{{ $comment->id }}">

                                                            @guest
                                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                                                    <x-form.input name="guest_name" label="Adınız" required />
                                                                    <x-form.input type="email" name="guest_email"
                                                                        label="E-posta" required />
                                                                </div>
                                                            @endguest

                                                            <x-form.textarea name="content" label="Yanıt" rows="3"
                                                                required placeholder="Yanıtınızı yazın..." />

                                                            <div class="flex space-x-3">
                                                                <button type="submit"
                                                                    class="inline-flex items-center justify-center gap-2 px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-md hover:shadow-lg dark:bg-blue-700 dark:hover:bg-blue-800 dark:shadow-none">
                                                                    <span class="material-symbols-outlined text-xs">send</span>
                                                                    <span>Yanıt Gönder</span>
                                                                </button>
                                                                <button type="button"
                                                                    onclick="toggleReplyForm({{ $comment->id }})"
                                                                    class="inline-flex items-center justify-center gap-2 px-3 py-1.5 text-sm border border-gray-300 bg-white text-gray-700 rounded-lg hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-gray-500 transition-all duration-200 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 dark:text-slate-300">
                                                                    <span class="material-symbols-outlined text-xs">close</span>
                                                                    <span>İptal</span>
                                                                </button>
                                                            </div>
                                                        </form>
                                                    </div>

                                                    <!-- Replies -->
                                                    @if ($comment->replies->isNotEmpty())
                                                        <div class="comment-reply">
                                                            @foreach ($comment->replies as $reply)
                                                                <div class="comment-card mt-4">
                                                                    <div class="flex items-start space-x-4">
                                                                        <div class="flex-shrink-0">
                                                                            @if ($reply->user)
                                                                                <div
                                                                                    class="w-10 h-10 bg-orange-100 dark:bg-orange-900/30 rounded-full flex items-center justify-center">
                                                                                    <span
                                                                                        class="text-sm font-medium text-orange-600 dark:text-orange-400">
                                                                                        {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                                                                                    </span>
                                                                                </div>
                                                                            @else
                                                                                <div
                                                                                    class="w-10 h-10 bg-gray-100 dark:bg-slate-900 rounded-full flex items-center justify-center">
                                                                                    <span class="material-symbols-outlined text-gray-400 text-sm">person</span>
                                                                                </div>
                                                                            @endif
                                                                        </div>

                                                                        <div class="flex-1">
                                                                            <div class="flex items-center space-x-3 mb-2">
                                                                                <h5
                                                                                    class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                                                    {{ $reply->author_name }}</h5>
                                                                                <span
                                                                                    class="text-sm text-gray-500 dark:text-gray-400">{{ $reply->created_at->diffForHumans() }}</span>
                                                                                @if ($reply->user)
                                                                                    <span
                                                                                        class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                                                                                        Üye
                                                                                    </span>
                                                                                @endif
                                                                            </div>

                                                                            <p class="text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                                                                {{ $reply->content }}</p>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            @endforeach
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Pagination -->
                                @if ($comments->hasPages())
                                    <div class="mt-8">
                                        {{ $comments->links() }}
                                    </div>
                                @endif
                            @else
                                <div class="text-center py-8">
                                    <span class="material-symbols-outlined text-4xl text-gray-300 dark:text-gray-600 mb-4">forum</span>
                                    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Henüz yorum yok</h3>
                                    <p class="text-gray-600 dark:text-gray-400">İlk yorumu siz yazın!</p>
                                </div>
                            @endif
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="lg:col-span-1">
                    <div class="sticky top-8 space-y-8">
                        <!-- Categories -->
                        @if ($sidebarData['categories']->isNotEmpty())
                            <div
                                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 dark:shadow-none">
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
                            <div
                                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 dark:shadow-none">
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

                        <!-- Popular Tags -->
                        @if ($sidebarData['popular_tags']->isNotEmpty())
                            <div
                                class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-100 dark:border-slate-800 p-6 dark:shadow-none">
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
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        // Copy to clipboard function
        function copyToClipboard(text) {
            navigator.clipboard.writeText(text).then(function() {
                // Show success message
                const button = event.target.closest('button');
                const icon = button.querySelector('.material-symbols-outlined');
                if (icon) {
                    icon.textContent = 'check';
                    setTimeout(() => { icon.textContent = 'link'; }, 2000);
                }
            });
        }

        // Like comment function
        function likeComment(commentId) {
            fetch(`/blog/comments/${commentId}/like`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`likes-${commentId}`).textContent = data.likes;
                    }
                });
        }

        // Dislike comment function
        function dislikeComment(commentId) {
            fetch(`/blog/comments/${commentId}/dislike`, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                    },
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById(`dislikes-${commentId}`).textContent = data.dislikes;
                    }
                });
        }

        // Toggle reply form
        function toggleReplyForm(commentId) {
            const form = document.getElementById(`reply-form-${commentId}`);
            form.classList.toggle('hidden');
        }

        // Reading progress indicator (optional)
        document.addEventListener('DOMContentLoaded', function() {
            const article = document.querySelector('article');
            if (article) {
                // Track reading progress for analytics
                let readingStarted = false;
                let readingTime = 0;

                const observer = new IntersectionObserver((entries) => {
                    entries.forEach(entry => {
                        if (entry.isIntersecting && !readingStarted) {
                            readingStarted = true;
                            const startTime = Date.now();

                            // Track reading time every 5 seconds
                            setInterval(() => {
                                readingTime += 5;
                                // Send reading time to server (optional)
                            }, 5000);
                        }
                    });
                });

                observer.observe(article);
            }
        });
    </script>
@endpush
