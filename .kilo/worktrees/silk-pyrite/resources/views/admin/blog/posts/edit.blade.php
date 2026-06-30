@extends('admin.layouts.admin')

@section('title', 'Blog Yazısı Düzenle')
@section('page-title', 'Blog Yazısı Düzenle')

@push('styles')
    {{-- ✅ DUPLICATE REMOVED: Common styles moved to resources/css/admin/common-styles.css --}}
    <style>
        /* Sayfa-spesifik: Native multiselect styling */
        select[multiple] {
            @apply border-gray-300 dark:border-gray-600 rounded-lg p-2
                   bg-white dark:bg-gray-800 text-gray-900 dark:text-white;
        }

        select[multiple]:focus {
            @apply border-orange-500 dark:border-orange-400 ring-1 ring-orange-500 dark:ring-orange-400;
        }
    </style>
@endpush

@section('content')
    <div class="min-h-screen bg-gradient-to-br from-gray-50 to-blue-50">
        <!-- Modern Header -->
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm mb-8 p-8 dark:shadow-none dark:border-slate-700">
            <div class="flex items-center justify-between">
                <div>
                    <h1
                        class="text-4xl font-bold bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent">
                        ✏️ Blog Yazısı Düzenle
                    </h1>
                    <p class="mt-3 text-lg text-gray-600">
                        "{{ $post->title }}" yazısını düzenleyin
                    </p>
                </div>
                <div class="flex gap-4">
                    <a href="{{ route('admin.blog.posts.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                        Geri Dön
                    </a>
                </div>
            </div>
        </div>

        <form method="POST" action="{{ route('admin.blog.posts.update', $post) }}" enctype="multipart/form-data"
            x-data="{
                schedulePost: {{ $post->yayin_durumu === 'scheduled' ? 'true' : 'false' }},
                status: '{{ $post->yayin_durumu }}',
                allowComments: {{ $post->allow_comments ? 'true' : 'false' }},
                isFeatured: {{ $post->one_cikan ? 'true' : 'false' }},
                isBreaking: {{ $post->is_breaking_news ? 'true' : 'false' }}
            }">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Basic Information -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                                    </path>
                                </svg>
                                Temel Bilgiler
                            </h3>
                        </div>
                        <div class="p-6 space-y-6">
                            <!-- Title -->
                            <div class="form-field">
                                <label class="admin-label admin-label-required">Başlık</label>
                                <input type="text" name="title" value="{{ old('title', $post->title) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('title') admin-input-error @enderror dark:text-slate-100" required>
                                @error('title')
                                    <span class="form-error-message">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Slug -->
                            <div class="form-field">
                                <label class="admin-label">URL (Slug)</label>
                                <input type="text" name="slug" value="{{ old('slug', $post->slug) }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('slug') admin-input-error @enderror dark:text-slate-100"
                                    placeholder="Otomatik oluşturulacak">
                                @error('slug')
                                    <span class="form-error-message">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Excerpt -->
                            <div class="form-field">
                                <label class="admin-label">Özet</label>
                                <textarea name="excerpt" rows="3" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('excerpt') admin-input-error @enderror"
                                    placeholder="Yazının kısa özeti...">{{ old('excerpt', $post->excerpt) }}</textarea>
                                @error('excerpt')
                                    <span class="form-error-message">{{ $message }}</span>
                                @enderror
                            </div>

                            <!-- Content -->
                            <div class="form-field">
                                <label class="admin-label admin-label-required">İçerik</label>
                                <textarea name="content" id="content" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('content') admin-input-error @enderror" rows="15"
                                    required>{{ old('content', $post->content) }}</textarea>
                                @error('content')
                                    <span class="form-error-message">{{ $message }}</span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z">
                                    </path>
                                </svg>
                                Öne Çıkan Görsel
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            @if ($post->kapak_resmi)
                                <div class="mb-4">
                                    <img src="{{ $post->kapak_resmi }}" alt="Current featured image"
                                        class="w-full max-w-md h-48 object-cover rounded-lg">
                                    <p class="text-sm text-gray-500 mt-2">Mevcut öne çıkan görsel</p>
                                </div>
                            @endif

                            <div class="form-field">
                                <label class="admin-label">Yeni Görsel Yükle</label>
                                <input type="file" name="kapak_resmi" accept="image/*"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('kapak_resmi') px-3 py-2 rounded-md border-gray-200 bg-white text-sm placeholder:text-gray-400 dark:border-slate-800 dark:text-slate-100 transition-colors-error @enderror dark:border-slate-700">
                                <p class="form-help">JPG, PNG veya WebP formatında, maksimum 2MB</p>
                                @error('kapak_resmi')
                                    <span class="form-error-message">{{ $message }}</span>
                                @enderror
                            </div>

                            <div class="form-field">
                                <label class="admin-label">Görsel Alt Metni</label>
                                <input type="text" name="kapak_resmi_alt"
                                    value="{{ old('kapak_resmi_alt', $post->kapak_resmi_alt) }}" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                    placeholder="Görsel açıklaması...">
                            </div>
                        </div>
                    </div>

                    <!-- SEO Settings -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                SEO Ayarları
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <div class="form-field">
                                <label class="admin-label">Meta Başlık</label>
                                <input type="text" name="meta_title"
                                    value="{{ old('meta_title', $post->meta_title) }}" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100" maxlength="60"
                                    placeholder="SEO için başlık...">
                                <p class="form-help">Maksimum 60 karakter</p>
                            </div>

                            <div class="form-field">
                                <label class="admin-label">Meta Açıklama</label>
                                <textarea name="meta_description" rows="3" maxlength="160" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                                    placeholder="SEO için açıklama...">{{ old('meta_description', $post->meta_description) }}</textarea>
                                <p class="form-help">Maksimum 160 karakter</p>
                            </div>

                            <div class="form-field">
                                <label class="admin-label">Anahtar Kelimeler</label>
                                <input type="text" name="meta_keywords"
                                    value="{{ old('meta_keywords', $post->meta_keywords) }}" class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                                    placeholder="anahtar, kelime, listesi">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Publish Settings -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Yayın Ayarları
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Status -->
                            <div class="form-field">
                                <label class="admin-label">Durum</label>
                                <select style="color-scheme: light dark;" name="yayin_durumu" x-model="status" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                                    <option value="draft" {{ $post->yayin_durumu === 'draft' ? 'selected' : '' }}>Taslak
                                    </option>
                                    <option value="published" {{ $post->yayin_durumu === 'published' ? 'selected' : '' }}>
                                        Yayınla</option>
                                    <option value="scheduled" {{ $post->yayin_durumu === 'scheduled' ? 'selected' : '' }}>
                                        Programla</option>
                                </select>
                            </div>

                            <!-- Scheduled Date -->
                            <div x-show="status === 'scheduled'" class="form-field">
                                <label class="admin-label">Yayın Tarihi</label>
                                <input type="datetime-local" name="scheduled_at"
                                    value="{{ $post->scheduled_at ? $post->scheduled_at->format('Y-m-d\TH:i') : '' }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                            </div>

                            <!-- Published Date (for editing) -->
                            <div x-show="status === 'published'" class="form-field">
                                <label class="admin-label">Yayın Tarihi</label>
                                <input type="datetime-local" name="published_at"
                                    value="{{ $post->published_at ? $post->published_at->format('Y-m-d\TH:i') : '' }}"
                                    class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                            </div>

                            <!-- Author (Admin only) -->
                            @if (auth()->user()->hasRole('admin'))
                                <div class="form-field">
                                    <label class="admin-label">Yazar</label>
                                    <select style="color-scheme: light dark;" name="user_id" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                                        @foreach (\App\Models\User::where('role', '!=', 'customer')->get() as $user)
                                            <option value="{{ $user->id }}"
                                                {{ $post->user_id == $user->id ? 'selected' : '' }}>
                                                {{ $user->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <!-- Options -->
                            <div class="space-y-3">
                                <label class="flex items-center">
                                    <input type="checkbox" name="allow_comments" value="1" x-model="allowComments"
                                        {{ $post->allow_comments ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm">Yorumlara İzin Ver</span>
                                </label>

                                <label class="flex items-center">
                                    <input type="checkbox" name="one_cikan" value="1" x-model="isFeatured"
                                        {{ $post->one_cikan ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm">Öne Çıkan Yazı</span>
                                </label>

                                <label class="flex items-center">
                                    <input type="checkbox" name="is_sticky" value="1"
                                        {{ $post->is_sticky ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm">Sabit Yazı (Üstte Göster)</span>
                                </label>

                                <label class="flex items-center">
                                    <input type="checkbox" name="is_breaking_news" value="1" x-model="isBreaking"
                                        {{ $post->is_breaking_news ? 'checked' : '' }}
                                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                    <span class="ml-2 text-sm">Son Dakika Haberi</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- Category & Tags -->
                    <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden dark:bg-slate-900 dark:border-slate-800">
                        <div class="p-6 border-b border-gray-100 dark:border-slate-800">
                            <h3 class="text-xl font-bold text-gray-800 flex items-center dark:text-slate-200">
                                <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
                                    </path>
                                </svg>
                                Kategori & Etiketler
                            </h3>
                        </div>
                        <div class="p-6 space-y-4">
                            <!-- Category -->
                            <div class="form-field">
                                <label class="admin-label">Kategori</label>
                                <select style="color-scheme: light dark;" name="blog_category_id" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                                    <option value="">Kategori Seçin</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}"
                                            {{ $post->blog_category_id == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Tags -->
                            <div class="form-field">
                                <label class="admin-label">Etiketler</label>
                                <select name="tags[]" id="tags" multiple class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200" style="width: 100%; color-scheme: light dark;">
                                    @foreach ($tags as $tag)
                                        <option value="{{ $tag->id }}"
                                            {{ in_array($tag->id, $selectedTags) ? 'selected' : '' }}>
                                            {{ $tag->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <p class="form-help">Mevcut etiketlerden seçin veya yeni etiket yazın</p>
                            </div>
                        </div>
                    </div>

                    <!-- Post Stats -->
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
                            <div class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <span class="text-gray-600">Görüntülenme:</span>
                                    <span class="font-medium">{{ number_format($post->view_count) }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Yorum:</span>
                                    <span class="font-medium">{{ $post->comment_count }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Oluşturulma:</span>
                                    <span class="font-medium">{{ $post->created_at->format('d.m.Y') }}</span>
                                </div>
                                <div>
                                    <span class="text-gray-600">Güncelleme:</span>
                                    <span class="font-medium">{{ $post->updated_at->format('d.m.Y') }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                        <div class="p-6 space-y-3">
                            <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg w-full touch-target-optimized dark:shadow-none">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M5 13l4 4L19 7"></path>
                                </svg>
                                Değişiklikleri Kaydet
                            </button>

                            <a href="{{ route('admin.blog.posts.show', $post) }}"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 w-full text-center touch-target-optimized dark:text-slate-300">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                                    </path>
                                </svg>
                                Önizleme
                            </a>

                            <a href="{{ route('blog.show', $post->slug) }}" target="_blank"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 w-full text-center touch-target-optimized dark:text-slate-300">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14">
                                    </path>
                                </svg>
                                Sitede Görüntüle
                            </a>

                            <hr class="border-gray-200 dark:border-slate-700">

                            <a href="{{ route('admin.blog.posts.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 w-full text-center touch-target-optimized dark:text-slate-300">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Geri Dön
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <!-- Select2 removed - 2025-10-21 - Using native multiselect instead -->
    <script src="https://cdn.tiny.cloud/1/{{ config('services.tinymce.api_key') }}/tinymce/6/tinymce.min.js"
        referrerpolicy="origin"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize TinyMCE
            tinymce.init({
                selector: '#content',
                height: 400,
                menubar: false,
                plugins: 'advlist autolink lists link image charmap print preview anchor searchreplace visualblocks code fullscreen insertdatetime media table paste code help wordcount',
                toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
                content_style: 'body { font-family: "Inter", sans-serif; font-size: 14px; }',
                skin: document.documentElement.classList.contains('dark') ? 'oxide-dark' : 'oxide',
                content_css: document.documentElement.classList.contains('dark') ? 'dark' : 'default',
                setup: function(editor) {
                    editor.on('change', function() {
                        editor.save();
                    });
                }
            });

            // Native multiselect - no JS needed
            // Tags can be selected using Ctrl/Cmd + Click
            console.log('✅ Native multiselect loaded');
        });

        // Auto-generate slug from title
        const titleInput = document.querySelector('input[name="title"]');
        const slugInput = document.querySelector('input[name="slug"]');

        if (titleInput && slugInput) {
            titleInput.addEventListener('input', function() {
                if (!slugInput.dataset.modified) {
                    const slug = this.value
                        .toLowerCase()
                        .replace(/[^a-z0-9]+/g, '-')
                        .replace(/^-+|-+$/g, '');
                    slugInput.value = slug;
                }
            });

            slugInput.addEventListener('input', function() {
                this.dataset.modified = 'true';
            });
        }
        });
    </script>
@endpush
