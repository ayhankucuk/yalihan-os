@extends('admin.layouts.admin')

@section('title', 'Blog Etiketi Düzenle')

@section('content')
    <div class="content-header mb-8">
        <div class="container-fluid">
            <div class="flex justify-between items-center">
                <div class="space-y-2">
                    <h1 class="text-3xl font-bold text-gray-800 dark:text-slate-200 flex items-center">
                        <div
                            class="w-12 h-12 bg-gradient-to-r from-purple-500 to-pink-500 rounded-xl flex items-center justify-center mr-4">
                            <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                            </svg>
                        </div>
                        Blog Etiketi Düzenle: {{ $tag->name }}
                    </h1>
                    <p class="text-lg text-gray-600 dark:text-gray-400">
                        Blog etiketi bilgilerini güncelleyin.
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <a href="{{ route('admin.blog.tags.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                        </svg>
                        Etiketlere Geri Dön
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="container-fluid">
        <form action="{{ route('admin.blog.tags.update', $tag) }}" method="POST">
            @csrf
            @method('PUT')

            <div class="space-y-8 p-6">
                {{-- Hata Mesajları --}}
                @if ($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-400 p-4 rounded-r-lg">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="w-5 h-5 mr-2 text-red-500" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-sm font-medium text-red-800">Form Hataları!</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc list-inside space-y-1">
                                        @foreach ($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                {{-- Form İçeriği --}}
                <div class="bg-gradient-to-r from-purple-50 to-pink-50 rounded-xl border border-purple-200 shadow-sm dark:shadow-none">
                    <div class="p-6">
                        <h2 class="text-xl font-bold text-purple-800 mb-6 flex items-center">
                            <svg class="w-6 h-6 mr-3 text-purple-600" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                            🏷️ Etiket Bilgileri
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="form-field">
                                <label for="name" class="admin-label">
                                    Etiket Adı <span class="text-red-500">*</span>
                                </label>
                                <input type="text" id="name" name="name" value="{{ old('name', $tag->name) }}"
                                    class="admin-input" placeholder="Örn: Emlak, Kiralık, Satılık" required>
                            </div>

                            <div class="form-field">
                                <label for="slug" class="admin-label">URL Slug</label>
                                <input type="text" id="slug" name="slug" value="{{ old('slug', $tag->slug) }}"
                                    class="admin-input" placeholder="otomatik oluşturulacak">
                                <p class="text-sm text-gray-500 mt-1">Boş bırakırsanız isimden otomatik oluşturulacaktır.
                                </p>
                            </div>

                            <div class="form-field">
                                <label for="color" class="admin-label">
                                    Etiket Rengi <span class="text-red-500">*</span>
                                </label>
                                <div class="flex items-center space-x-3">
                                    <input type="color" id="color" name="color"
                                        value="{{ old('color', $tag->color) }}"
                                        class="h-12 w-20 rounded-lg border-2 border-gray-300 cursor-pointer">
                                    <span class="text-sm text-gray-600">{{ old('color', $tag->color) }}</span>
                                </div>
                            </div>

                            <div class="form-field">
                                <label class="admin-label">Durum</label>
                                <div class="flex items-center">
                                    <input type="checkbox" id="yayin_durumu" name="yayin_durumu" value="1"
                                        {{ old('yayin_durumu', $tag->yayin_durumu) ? 'checked' : '' }}
                                        class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                                    <label for="yayin_durumu" class="ml-2 text-sm text-gray-700 dark:text-slate-300">Aktif Etiket</label>
                                </div>
                                <p class="text-sm text-gray-500 mt-1">Bu etiket blog yazılarında kullanılabilecek.</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Butonlar --}}
                <div class="flex justify-end space-x-3">
                    <a href="{{ route('admin.blog.tags.index') }}" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 border border-gray-300 bg-white text-gray-700 hover:bg-gray-50 hover:scale-105 active:scale-95 focus:ring-gray-500 dark:bg-slate-900 dark:border-slate-800 dark:text-slate-200 dark:hover:bg-gray-700 touch-target-optimized dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        İptal
                    </a>
                    <button type="submit" class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg transition-all duration-200 focus:ring-2 focus:ring-offset-2 bg-blue-600 text-white hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-blue-500 shadow-md hover:shadow-lg touch-target-optimized dark:shadow-none">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Güncelle
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        // Slug otomatik oluşturma
        document.getElementById('name').addEventListener('input', function() {
            const name = this.value;
            const slug = name.toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim('-');

            document.getElementById('slug').value = slug;
        });

        // Form field focus effects
        document.addEventListener('DOMContentLoaded', function() {
            const formInputs = document.querySelectorAll('.admin-input');

            formInputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.parentElement.classList.add('ring-2', 'ring-blue-200');
                });

                input.addEventListener('blur', function() {
                    this.parentElement.classList.remove('ring-2', 'ring-blue-200');
                });
            });
        });
    </script>
@endpush
