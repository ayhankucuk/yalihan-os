@extends('admin.layouts.admin')

@section('title', 'Ayar Düzenle')

@section('content')
    <div class="container max-w-4xl mx-auto px-4 py-6">
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Ayar Düzenle</h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">Sistem ayarlarını yapılandırın</p>
                </div>
                <div>
                    <a href="{{ route('admin.ayarlar.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:text-slate-300">Geri Dön</a>
                </div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm hover:shadow-md transition-all dark:shadow-none dark:border-slate-700">
            <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Sistem Ayarları</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Genel sistem konfigürasyonu</p>
            </div>
            <div class="p-6">
                <form action="{{ route('admin.ayarlar.update', $ayar->id) }}" method="POST" class="space-y-6">
                    @csrf
                    @method('PUT')

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="key" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Ayar Anahtarı <span class="text-red-500">*</span></label>
                            <input id="key" name="key" type="text" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('key') border-red-500 @enderror"
                                value="{{ old('key', $ayar->key) }}" required placeholder="Ayar anahtarını girin">
                            @error('key')
                                <div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Veri Tipi <span class="text-red-500">*</span></label>
                            <select style="color-scheme: light dark;" id="type" name="type" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 @error('type') border-red-500 @enderror transition-all duration-200 dark:text-slate-100" required>
                                <option value="">Tip Seçin</option>
                                <option value="string" {{ old('type', $ayar->type) == 'string' ? 'selected' : '' }}>Metin</option>
                                <option value="integer" {{ old('type', $ayar->type) == 'integer' ? 'selected' : '' }}>Sayı</option>
                                <option value="boolean" {{ old('type', $ayar->type) == 'boolean' ? 'selected' : '' }}>Evet/Hayır</option>
                                <option value="json" {{ old('type', $ayar->type) == 'json' ? 'selected' : '' }}>JSON</option>
                            </select>
                            @error('type')
                                <div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label for="value" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Değer <span class="text-red-500">*</span></label>
                        <textarea id="value" name="value" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical @error('value') border-red-500 @enderror"
                            rows="4" placeholder="Ayar değerini girin">{{ old('value', $ayar->value) }}</textarea>
                        @error('value')
                            <div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="space-y-2">
                        <label for="description" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Açıklama</label>
                        <textarea id="description" name="description" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 resize-vertical @error('description') border-red-500 @enderror"
                            rows="3" placeholder="Ayar hakkında açıklama">{{ old('description', $ayar->description) }}</textarea>
                        @error('description')
                            <div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label for="group" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Grup</label>
                            <input id="group" name="group" type="text" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('group') border-red-500 @enderror"
                                value="{{ old('group', $ayar->group) }}" placeholder="Ayar grubu">
                            @error('group')
                                <div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="space-y-2">
                            <label for="display_order" class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Sıra</label>
                            <input id="display_order" name="display_order" type="number" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 @error('display_order') border-red-500 dark:border-red-600 @enderror"
                                value="{{ old('display_order', $ayar->display_order ?? 0) }}" min="0" placeholder="0">
                            @error('display_order')
                                <div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="space-y-2">
                        <div class="flex items-center space-x-3">
                            <input type="checkbox" name="is_public" value="1" id="is_public" class="w-5 h-5 text-blue-600 bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer"
                                {{ old('is_public', $ayar->is_public ?? false) ? 'checked' : '' }}>
                            <label for="is_public" class="text-sm font-medium text-gray-700 dark:text-slate-200 cursor-pointer dark:text-slate-300">Herkese açık ayar</label>
                        </div>
                        @error('is_public')
                            <div class="text-sm text-red-600 dark:text-red-400 mt-1">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <a href="{{ route('admin.ayarlar.index') }}" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 dark:text-slate-300">İptal</a>
                        <button type="submit" class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                            <i class="fas fa-save"></i>Güncelle
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
