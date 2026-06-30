@extends('admin.layouts.admin')

@section('title', 'Kategori Düzenle')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="kategoriForm()">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">Kategori Düzenle</h1>
                <a href="{{ route('admin.ilan-kategorileri.index') }}"
                    class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-gray-600 dark:bg-slate-900 dark:text-slate-200 dark:text-slate-300 dark:shadow-none dark:hover:bg-gray-700 dark:focus-visible:ring-offset-gray-900">
                    ← Geri Dön
                </a>
            </div>
        </div>

        @if (session('success'))
            <div
                class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800/30 dark:bg-green-900/20">
                <span class="text-green-700 dark:text-green-200">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800/30 dark:bg-red-900/20">
                <span class="text-red-700 dark:text-red-200">{{ session('error') }}</span>
            </div>
        @endif

        <div
            class="rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="p-6">
                <form method="POST" action="{{ route('admin.ilan-kategorileri.update', $kategori->id) }}"
                    @submit="submitForm">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                        <div class="space-y-2">
                            <label for="name"
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Kategori
                                Adı *</label>
                            <input type="text" id="name" name="name"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-black placeholder-gray-400 transition-all duration-200 focus:border-transparent focus:outline-none focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500 dark:focus:ring-blue-400"
                                value="{{ old('name', $kategori->name) }}" required placeholder="Örn: Daire, Villa">
                            @error('name')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="seviye"
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Seviye
                                *</label>
                            <select style="color-scheme: light dark;" id="seviye" name="seviye"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-black transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white dark:focus-visible:ring-blue-400"
                                required @change="updateParentOptions()">
                                <option value="">Seçiniz</option>
                                <option value="0" {{ old('seviye', $kategori->seviye) == 0 ? 'selected' : '' }}>Ana
                                    Kategori</option>
                                <option value="1" {{ old('seviye', $kategori->seviye) == 1 ? 'selected' : '' }}>Alt
                                    Kategori</option>
                                <option value="2" {{ old('seviye', $kategori->seviye) == 2 ? 'selected' : '' }}>Yayın
                                    Tipi</option>
                            </select>
                            @error('seviye')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2" id="parent-field" x-show="parentRequired" x-cloak>
                            <label for="parent_id"
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Üst
                                Kategori <span x-show="parentRequired" x-cloak>*</span></label>
                            <select style="color-scheme: light dark;" id="parent_id" name="parent_id"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-black transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white dark:focus-visible:ring-blue-400">
                                <option value="">Seçiniz</option>
                                @foreach ($parentCategories as $anaKategori)
                                    @if ($anaKategori->id != $kategori->id)
                                        <option value="{{ $anaKategori->id }}"
                                            {{ old('parent_id', $kategori->parent_id) == $anaKategori->id ? 'selected' : '' }}>
                                            {{ $anaKategori->name }}
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="display_order"
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Sıra</label>
                            <input type="number" id="display_order" name="display_order"
                                class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-black placeholder-gray-400 transition-all duration-200 focus-visible:border-transparent focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-white dark:placeholder-gray-500 dark:focus-visible:ring-blue-400"
                                value="{{ old('display_order', $kategori->display_order ?? 0) }}" min="0">
                            @error('display_order')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label
                            class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Aktiflik
                            Durumu</label>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="aktiflik_durumu" value="1"
                                    class="h-4 w-4 border-gray-300 bg-gray-100 text-blue-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:bg-slate-900 dark:text-blue-400 dark:ring-offset-gray-800 dark:focus-visible:ring-blue-600"
                                    {{ old('aktiflik_durumu', $kategori->aktiflik_durumu) == 1 ? 'checked' : '' }}>
                                <span class="ml-2 text-gray-900 dark:text-slate-100 dark:text-white">Aktif</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="aktiflik_durumu" value="0"
                                    class="h-4 w-4 border-gray-300 bg-gray-100 text-blue-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:bg-slate-900 dark:text-blue-400 dark:ring-offset-gray-800 dark:focus-visible:ring-blue-600"
                                    {{ old('aktiflik_durumu', $kategori->aktiflik_durumu) == 0 ? 'checked' : '' }}>
                                <span class="ml-2 text-gray-900 dark:text-slate-100 dark:text-white">Pasif</span>
                            </label>
                        </div>
                    </div>

                    <div
                        class="flex justify-end space-x-4 border-t border-gray-200 pt-6 dark:border-slate-700 dark:border-slate-800">
                        <a href="{{ route('admin.ilan-kategorileri.index') }}"
                            class="inline-flex items-center rounded-lg border border-gray-300 bg-white px-4 py-2.5 text-sm font-medium text-gray-700 shadow-sm transition-all duration-200 hover:bg-gray-50 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white dark:border-gray-600 dark:bg-slate-900 dark:text-slate-200 dark:text-slate-300 dark:shadow-none dark:hover:bg-gray-700 dark:focus-visible:ring-offset-gray-900">İptal</a>
                        <button type="submit"
                            class="inline-flex items-center bg-gradient-to-r from-blue-600 to-purple-600 px-4 py-2.5 text-sm font-medium text-white shadow-md transition-all duration-200 hover:from-blue-700 hover:to-purple-700 hover:shadow-lg focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2 focus-visible:ring-offset-white active:scale-95 dark:from-blue-500 dark:to-purple-500 dark:shadow-none dark:hover:from-blue-400 dark:hover:to-purple-400 dark:focus-visible:ring-offset-gray-900"
                            :disabled="loading">
                            <span x-show="!loading">Kaydet</span>
                            <span x-show="loading">Kaydediliyor...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function kategoriForm() {
            return {
                loading: false,
                parentRequired: {{ $kategori->seviye > 0 ? 'true' : 'false' }},
                updateParentOptions() {
                    const seviye = document.getElementById('seviye').value;

                    if (seviye == '1' || seviye == '2') {
                        this.parentRequired = true;
                    } else {
                        this.parentRequired = false;
                        document.getElementById('parent_id').value = '';
                    }
                },
                submitForm(event) {
                    if (this.parentRequired && !document.getElementById('parent_id').value) {
                        event.preventDefault();
                        alert('Üst Kategori seçmelisiniz!');
                        return false;
                    }

                    this.loading = true;
                    // Form'un doğal submit işlemini gerçekleştir (CSRF token ile)
                    return true;
                }
            }
        }
    </script>
@endsection
