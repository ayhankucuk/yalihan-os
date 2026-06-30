@extends('admin.layouts.admin')

@section('title', 'Yeni İlan Kategorisi')

@section('content')
    <div class="container mx-auto px-4 py-6" x-data="kategoriForm()">
        <div class="mb-8 flex items-center justify-between">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Yeni Kategori Ekle</h1>
            <a href="{{ route('admin.ilan-kategorileri.index') }}"
                class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
                ← Geri Dön
            </a>
        </div>

        @if (session('success'))
            <div
                class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/30 rounded-lg p-4 mb-6">
                <span class="text-green-700 dark:text-green-200">{{ session('success') }}</span>
            </div>
        @endif

        @if (session('error'))
            <div class="bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/30 rounded-lg p-4 mb-6">
                <span class="text-red-700 dark:text-red-200">{{ session('error') }}</span>
            </div>
        @endif

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
            <div class="p-6">
                <form method="POST" action="{{ route('admin.ilan-kategorileri.store') }}" @submit.prevent="submitForm">
                    @csrf

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="name"
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Kategori Adı *</label>
                            <input type="text" id="name" name="name"
                                class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:border-transparent transition-all duration-200"
                                value="{{ old('name') }}" required placeholder="Örn: Daire, Villa">
                            @error('name')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="seviye" class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Seviye
                                *</label>
                            <select style="color-scheme: light dark;" id="seviye" name="seviye"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 transition-all duration-200"
                                required @change="updateParentOptions()">
                                <option value="">Seçiniz</option>
                                <option value="0" {{ old('seviye') == 0 ? 'selected' : '' }}>Ana Kategori</option>
                                <option value="1" {{ old('seviye') == 1 ? 'selected' : '' }}>Alt Kategori</option>
                                <option value="2" {{ old('seviye') == 2 ? 'selected' : '' }}>Yayın Tipi</option>
                            </select>
                            @error('seviye')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2 md:col-span-2" id="parent-field" x-show="parentRequired" x-cloak>
                            <label for="parent_id" class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Üst
                                Kategori <span x-show="parentRequired" x-cloak>*</span></label>
                            <select style="color-scheme: light dark;" id="parent_id" name="parent_id"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 transition-all duration-200">
                                <option value="">Seçiniz</option>
                                @foreach ($anaKategoriler ?? [] as $kategori)
                                    <option value="{{ $kategori->id }}"
                                        {{ old('parent_id') == $kategori->id ? 'selected' : '' }}>
                                        {{ $kategori->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('parent_id')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="sira"
                                class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Sıra</label>
                            <input type="number" id="sira" name="sira"
                                class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-400 focus-visible:ring-offset-0 transition-all duration-200"
                                value="{{ old('sira', 0) }}" min="0">
                            @error('sira')
                                <div class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Aktiflik Durumu</label>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="aktiflik_durumu" value="1"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-600 dark:ring-offset-gray-800 focus-visible:outline-none dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900"
                                    {{ old('aktiflik_durumu', 1) == 1 ? 'checked' : '' }}>
                                <span class="ml-2 text-gray-900 dark:text-white dark:text-slate-100">Aktif</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="aktiflik_durumu" value="0"
                                    class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 focus-visible:ring-2 focus-visible:ring-blue-500 dark:focus-visible:ring-blue-600 dark:ring-offset-gray-800 focus-visible:outline-none dark:bg-gray-700 dark:border-gray-600 dark:bg-slate-900"
                                    {{ old('aktiflik_durumu') == 0 ? 'checked' : '' }}>
                                <span class="ml-2 text-gray-900 dark:text-white dark:text-slate-100">Pasif</span>
                            </label>
                        </div>
                    </div>

                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <a href="{{ route('admin.ilan-kategorileri.index') }}"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:border-gray-600 dark:hover:bg-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">İptal</a>
                        <button type="submit"
                            class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-white bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg hover:from-blue-700 hover:to-purple-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500 focus-visible:ring-offset-white dark:focus-visible:ring-offset-gray-900 transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 dark:shadow-none"
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
                parentRequired: false,
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
                    event.target.submit();
                }
            }
        }
    </script>
@endsection
