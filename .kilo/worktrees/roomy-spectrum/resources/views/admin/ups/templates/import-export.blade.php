@extends('admin.layouts.admin')

@section('title', 'Template Import/Export')

@section('content')
<div class="px-4 py-6">
    <div class="max-w-7xl mx-auto space-y-6">

        <!-- ✅ IMPROVED: Messages with better styling -->
        @if(session('success'))
            <div class="flex items-center gap-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 px-4 py-4 rounded-lg relative" role="alert">
                <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <span class="block sm:inline font-medium">{{ session('success') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-green-500 hover:text-green-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        @endif
        @if(session('error'))
            <div class="flex items-center gap-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 px-4 py-4 rounded-lg relative" role="alert">
                <svg class="w-6 h-6 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                </svg>
                <span class="block sm:inline font-medium">{{ session('error') }}</span>
                <button type="button" onclick="this.parentElement.remove()" class="ml-auto text-red-500 hover:text-red-700">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>
                </button>
            </div>
        @endif

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Template İçe/Dışa Aktar</h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Template özelliklerini JSON formatında dışa aktarın veya içe aktarın</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- ✅ Export Section -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-blue-500 to-blue-600 border-b border-blue-600">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Template Dışa Aktar
                    </h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('admin.ups.templates.export') }}" method="POST" class="space-y-4">
                        @csrf
                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Kategori Seçin</label>
                            <select id="export_kategori_id" name="kategori_id"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition dark:bg-slate-900 dark:text-slate-100"
                                required>
                                <option value="">🔍 Kategori seçin...</option>
                                @foreach($kategoriler as $kategori)
                                    <option value="{{ $kategori->id }}" data-types="{{ json_encode($kategori->yayinTipleri) }}">{{ $kategori->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Yayın Tipi Seçin</label>
                            <select id="export_yayin_tipi_id" name="yayin_tipi_id"
                                class="w-full px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition dark:bg-slate-900 dark:text-slate-100"
                                required disabled>
                                <option value="">Kategori seçtikten sonra yayın tipi seçilecek</option>
                            </select>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full inline-flex justify-center items-center gap-2 py-3 px-4 rounded-lg font-semibold text-white bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 transition shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                                </svg>
                                JSON Olarak İndir
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ✅ Import Section -->
            <div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
                <div class="px-6 py-4 bg-gradient-to-r from-green-500 to-green-600 border-b border-green-600">
                    <h3 class="text-lg font-bold text-white flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4-4m0 0L8 8m4 4V4"/>
                        </svg>
                        Template İçe Aktar
                    </h3>
                </div>
                <div class="p-6">
                    <form action="{{ route('admin.ups.templates.import') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                        @csrf
                        <div class="text-sm text-gray-600 dark:text-gray-400 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-3 space-y-2">
                            <div class="flex items-start gap-2">
                                <svg class="w-5 h-5 flex-shrink-0 text-blue-600 dark:text-blue-400 mt-0.5" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                                </svg>
                                <div>
                                    <p class="font-semibold text-gray-800 dark:text-slate-200 mb-1">📥 İçe Aktarma Nasıl Çalışır?</p>
                                    <ul class="space-y-1 text-xs">
                                        <li>✅ Bu sistemde daha önce dışa aktarılan JSON dosyasını yükleyin</li>
                                        <li>✅ Dosya kategori ve yayın tipi bilgisini içerdiğinden manuel seçim yapmanıza gerek yoktur</li>
                                        <li>⚠️ <strong>MERGE MODU:</strong> Mevcut feature'lar korunur, sadece yeniler eklenir</li>
                                        <li>⚠️ Duplicate kontrolü yapılır (zaten varsa atlanır)</li>
                                        <li>❌ <strong>Replace modu henüz desteklenmemektedir</strong> - tümünü silip yeniden yüklemek için önce manuel temizleyin</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">JSON Dosyasını Seçin</label>
                            <label class="flex items-center justify-center w-full px-4 py-6 rounded-lg border-2 border-dashed border-gray-300 dark:border-gray-600 cursor-pointer hover:border-green-500 hover:bg-green-50 dark:hover:bg-green-900/10 transition">
                                <div class="text-center">
                                    <svg class="mx-auto h-8 w-8 text-gray-400 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6"/>
                                    </svg>
                                    <p class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">JSON dosyasını sürükleyin veya <span class="text-green-600 dark:text-green-400">tıklayın</span></p>
                                    <p class="text-xs text-gray-500 mt-1">Max. 10MB</p>
                                </div>
                                <input type="file" name="file" accept=".json" class="hidden" required/>
                            </label>
                        </div>

                        <div class="pt-2">
                            <button type="submit" class="w-full inline-flex justify-center items-center gap-2 py-3 px-4 rounded-lg font-semibold text-white bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 transition shadow-lg hover:shadow-xl">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M9 19l3 3m0 0l3-3m-3 3v-6"/>
                                </svg>
                                Yükle & Geri Yükle
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('export_kategori_id').addEventListener('change', function() {
        const typeSelect = document.getElementById('export_yayin_tipi_id');
        typeSelect.innerHTML = '<option value="">Yayın tipi seçiliyor...</option>';
        typeSelect.disabled = true;

        if (this.value) {
            const types = JSON.parse(this.options[this.selectedIndex].dataset.types);
            if (types.length > 0) {
                typeSelect.innerHTML = '<option value="">Yayın tipi seçin...</option>';
                types.forEach(type => {
                    const option = document.createElement('option');
                    option.value = type.id;
                    option.textContent = type.name;
                    typeSelect.appendChild(option);
                });
                typeSelect.disabled = false;
            } else {
                typeSelect.innerHTML = '<option value="" disabled>Bu kategoride yayın tipi yok</option>';
                typeSelect.disabled = true;
            }
        }
    });
</script>

@endsection
