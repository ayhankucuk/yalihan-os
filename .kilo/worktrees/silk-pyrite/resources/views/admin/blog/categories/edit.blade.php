@extends('admin.layouts.admin')

@section('title', 'Kategoriyi Düzenle')
@section('page-title', 'Kategoriyi Düzenle')

@section('content')
    <div class="container mx-auto">
        <!-- Header -->
        <div class="admin-card mb-6">
            <div class="admin-card-body">
                <div class="flex justify-between items-center">
                    <div>
                        <h1 class="admin-h1">Kategoriyi Düzenle</h1>
                        <p class="text-gray-600 dark:text-gray-400 mt-1">{{ $category->name }} kategorisini düzenle</p>
                    </div>
                    <div>
                        <a href="{{ route('admin.blog.categories.index') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2-outline-primary touch-target-optimized">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Kategorilere Dön
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form -->
        <div class="admin-card">
            <div
                class="admin-p-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-900/50 dark:border-slate-700">
                <h3 class="text-lg font-semibold">Kategori Bilgileri</h3>
            </div>
            <div class="admin-card-body">
                <form action="{{ route('admin.blog.categories.update', $category) }}" method="POST">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Name -->
                        <div class="form-group">
                            <label for="name" class="admin-label required">Kategori Adı</label>
                            <input type="text" id="name" name="name" class="admin-input"
                                value="{{ old('name', $category->name) }}" required>
                            @error('name')
                                <p class="form-error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Slug -->
                        <div class="form-group">
                            <label for="slug" class="admin-label">Slug (Opsiyonel)</label>
                            <input type="text" id="slug" name="slug" class="admin-input"
                                value="{{ old('slug', $category->slug) }}">
                            <p class="form-hint">Boş bırakırsanız isimden otomatik oluşturulacaktır.</p>
                            @error('slug')
                                <p class="form-error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Color -->
                        <div class="form-group">
                            <label for="color" class="admin-label required">Kategori Rengi</label>
                            <input type="color" id="color" name="color" class="h-10 w-full"
                                value="{{ old('color', $category->color) }}">
                            @error('color')
                                <p class="form-error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Icon -->
                        <div class="form-group">
                            <label for="icon" class="admin-label">İkon (Opsiyonel)</label>
                            <div class="flex space-x-2">
                                <input type="text" id="icon" name="icon" class="admin-input"
                                    value="{{ old('icon', $category->icon) }}" placeholder="fas fa-book">
                                <div class="w-10 h-10 bg-gray-100 dark:bg-slate-900 flex items-center justify-center rounded"
                                    id="icon-preview">
                                    <i class="{{ old('icon', $category->icon ?? 'fas fa-book') }}"></i>
                                </div>
                            </div>
                            <p class="form-hint">FontAwesome ikon kodu. Örn: fas fa-book</p>
                            @error('icon')
                                <p class="form-error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="form-group col-span-full">
                            <label for="description" class="admin-label">Açıklama</label>
                            <textarea id="description" name="description" class="admin-input" rows="3">{{ old('description', $category->description) }}</textarea>
                            @error('description')
                                <p class="form-error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Order -->
                        <div class="form-group">
                            <label for="display_order" class="admin-label">Sıralama</label>
                            <input type="number" id="display_order" name="display_order" class="admin-input"
                                value="{{ old('display_order', $category->display_order ?? 0) }}" min="0">
                            @error('display_order')
                                <p class="form-error-message">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Active Status -->
                        <div class="form-group">
                            <label class="form-checkbox-label">
                                <input type="checkbox" name="yayin_durumu" value="1" class="form-checkbox"
                                    {{ old('yayin_durumu', $category->yayin_durumu) ? 'checked' : '' }}>
                                <span>Aktif Kategori</span>
                            </label>
                            <p class="form-hint">Bu kategorideki yazılar sitede görüntülenecek.</p>
                            @error('yayin_durumu')
                                <p class="form-error-message">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div class="flex justify-end mt-6 space-x-3">
                        <a href="{{ route('admin.blog.categories.index') }}"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2-outline-secondary touch-target-optimized">
                            İptal
                        </a>
                        <button type="submit"
                            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized dark:shadow-none">
                            <i class="fas fa-save mr-2"></i>
                            Değişiklikleri Kaydet
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    <script>
        // Slug generator
        const nameInput = document.getElementById('name');
        const slugInput = document.getElementById('slug');

        nameInput.addEventListener('blur', function() {
            if (slugInput.value === '' || slugInput.value === '{{ $category->slug }}') {
                // Create slug from name
                let slug = nameInput.value
                    .toLowerCase()
                    .replace(/[^\w\s-]/g, '') // Remove special chars
                    .replace(/\s+/g, '-') // Replace spaces with -
                    .replace(/-+/g, '-') // Replace multiple - with single -
                    .trim(); // Trim - from start and end

                slugInput.value = slug;
            }
        });

        // Icon preview
        const iconInput = document.getElementById('icon');
        const iconPreview = document.getElementById('icon-preview');

        iconInput.addEventListener('input', function() {
            iconPreview.innerHTML = `<i class="${iconInput.value || 'fas fa-book'}"></i>`;
        });
    </script>
@endsection
