@extends('admin.layouts.admin')

@section('title', 'Yeni Not Oluştur - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div
                        class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-plus text-white text-xl"></i>
                    </div>
                    Yeni Not Oluştur
                </h1>
                <p class="text-lg text-gray-600 mt-2">Yeni müşteri notu oluşturun</p>
            </div>
            <a href="{{ route('admin.kisi-not.index') }}"
               class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none">
                <i class="fas fa-arrow-left mr-2"></i>
                Geri Dön
            </a>
        </div>
    </div>

    <div class="px-6">
        <form id="noteForm" class="max-w-4xl mx-auto">
            @csrf

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Temel Bilgiler</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Kişi Seçimi *</label>
                        <select style="color-scheme: light dark;" name="kisi_id" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                            <option value="">Kişi seçin...</option>
                            @if (isset($kisi) && $kisi)
                                <option value="{{ $kisi['id'] }}" selected>
                                    {{ $kisi['name'] ?? ($kisi['tam_ad'] ?? 'Seçili Kişi') }}</option>
                            @endif
                            @foreach ($recentKisiler ?? [] as $recentKisi)
                                <option value="{{ $recentKisi['id'] }}">
                                    {{ $recentKisi['name'] ?? ($recentKisi['tam_ad'] ?? 'Kişi ' . $recentKisi['id']) }}
                                </option>
                            @endforeach
                        </select>
                        <a href="#" class="text-sm text-blue-600 hover:text-blue-800 mt-1 inline-block">Yeni kişi
                            ekle</a>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Kategori *</label>
                        <select style="color-scheme: light dark;" name="kategori" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                            <option value="">Kategori seçin...</option>
                            @foreach ($kategoriler ?? [] as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Not Başlığı *</label>
                    <input type="text" name="baslik" required maxlength="200" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                        placeholder="Not başlığını girin...">
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Not İçeriği *</label>
                    <textarea name="icerik" required maxlength="5000" rows="8" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200"
                        placeholder="Not içeriğini girin..."></textarea>
                    <div class="text-sm text-gray-500 mt-1">
                        <span id="contentCount">0</span> / 5000 karakter
                    </div>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Detaylar</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Önem Derecesi *</label>
                        <select style="color-scheme: light dark;" name="onem_derecesi" required class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                            <option value="">Önem derecesi seçin...</option>
                            @foreach ($onemDereceleri ?? [] as $key => $value)
                                <option value="{{ $key }}">{{ $value }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Bitiş Tarihi</label>
                        <input type="date" name="due_date" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Hatırlatma Tarihi</label>
                        <input type="datetime-local" name="reminder_date" class="w-full px-4 py-2.5 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-black dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">İlan Bağlantısı</label>
                        <select style="color-scheme: light dark;" name="related_ilan_id" class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200">
                            <option value="">İlan seçin...</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Etiketler</label>
                    <div class="flex flex-wrap gap-2 mb-2" id="tagContainer">
                        <!-- Selected tags will appear here -->
                    </div>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($tags ?? [] as $tag)
                            <button type="button" onclick="addTag('{{ $tag }}')"
                                class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 hover:bg-blue-100 hover:text-blue-800 transition-colors dark:bg-slate-900 dark:text-slate-200">
                                #{{ $tag }}
                            </button>
                        @endforeach
                    </div>
                    <input type="hidden" name="tags" id="tagsInput">
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Ayarlar</h2>

                <div class="space-y-4">
                    <label class="flex items-center">
                        <input type="checkbox" name="is_private" class="w-5 h-5 text-blue-600 bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer mr-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Özel not (sadece ben görebilirim)</span>
                    </label>

                    <label class="flex items-center">
                        <input type="checkbox" name="is_completed" class="w-5 h-5 text-blue-600 bg-white dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer mr-3">
                        <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Tamamlandı olarak işaretle</span>
                    </label>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 mb-6 dark:shadow-none dark:border-slate-700">
                <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Şablonlar</h2>

                @if (isset($templates) && count($templates) > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach ($templates as $template)
                            <button type="button" onclick="applyTemplate('{{ $template['id'] }}')"
                                class="p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors text-left dark:border-slate-700">
                                <div class="font-medium text-gray-900 dark:text-slate-100 dark:text-white">{{ $template['name'] ?? 'Şablon' }}</div>
                                <div class="text-sm text-gray-600 mt-1">
                                    {{ Str::limit($template['description'] ?? '', 100) }}</div>
                            </button>
                        @endforeach
                    </div>
                @else
                    <p class="text-gray-500">Henüz şablon bulunmuyor.</p>
                @endif
            </div>

            <div class="flex justify-end space-x-4">
                <a href="{{ route('admin.kisi-not.index') }}"
                   class="inline-flex items-center px-6 py-3 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none">
                    <i class="fas fa-times mr-2"></i>
                    İptal
                </a>
                <button type="submit"
                        class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-semibold shadow-md hover:shadow-lg hover:scale-105 active:scale-95 dark:shadow-none">
                    <i class="fas fa-save mr-2"></i>
                    Notu Kaydet
                </button>
            </div>
        </form>
    </div>
@endsection

@push('scripts')
    <script>
        let selectedTags = [];

        document.getElementById('noteForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            formData.set('tags', JSON.stringify(selectedTags));

            fetch('{{ route('admin.kisi-not.store') }}', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content')
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        if (data.redirect) {
                            setTimeout(() => {
                                window.location.href = data.redirect;
                            }, 1000);
                        }
                    } else {
                        showToast(data.message, 'error');
                        if (data.errors) {
                            displayErrors(data.errors);
                        }
                    }
                })
                .catch(error => {
                    showToast('Not kaydedilirken hata oluştu', 'error');
                });
        });

        function addTag(tag) {
            if (!selectedTags.includes(tag)) {
                selectedTags.push(tag);
                updateTagDisplay();
            }
        }

        function removeTag(tag) {
            selectedTags = selectedTags.filter(t => t !== tag);
            updateTagDisplay();
        }

        function updateTagDisplay() {
            const container = document.getElementById('tagContainer');
            container.innerHTML = selectedTags.map(tag => `
        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
            #${tag}
            <button type="button" onclick="removeTag('${tag}')" class="ml-1 text-blue-600 hover:text-blue-800">
                <i class="fas fa-times"></i>
            </button>
        </span>
    `).join('');

            document.getElementById('tagsInput').value = JSON.stringify(selectedTags);
        }

        function applyTemplate(templateId) {
            // Template uygulama işlemi
            showToast('Şablon uygulandı', 'info');
        }

        function displayErrors(errors) {
            // Hata mesajlarını göster
            Object.keys(errors).forEach(field => {
                const errorElement = document.createElement('div');
                errorElement.className = 'text-red-600 text-sm mt-1';
                errorElement.textContent = errors[field][0];

                const fieldElement = document.querySelector(`[name="${field}"]`);
                if (fieldElement) {
                    fieldElement.parentNode.appendChild(errorElement);
                }
            });
        }

        // Character count for content
        document.querySelector('[name="icerik"]').addEventListener('input', function() {
            const count = this.value.length;
            document.getElementById('contentCount').textContent = count;

            if (count > 4500) {
                document.getElementById('contentCount').className = 'text-red-600';
            } else if (count > 4000) {
                document.getElementById('contentCount').className = 'text-yellow-600';
            } else {
                document.getElementById('contentCount').className = 'text-gray-500';
            }
        });

        function showToast(message, type = 'info') {
            const toast = document.createElement('div');
            toast.className = `fixed top-4 right-4 px-4 py-2 rounded-lg text-white z-50 ${
        type === 'success' ? 'bg-green-500' :
        type === 'error' ? 'bg-red-500' : 'bg-blue-500'
    }`;
            toast.textContent = message;

            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
@endpush
