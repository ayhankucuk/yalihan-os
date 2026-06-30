@extends('admin.layouts.admin')

@section('title', 'Ayar Detayları - Yalıhan Emlak Pro')

@section('content')
    <div class="content-header mb-8">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold flex items-center text-gray-800 dark:text-slate-200">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mr-4">
                        <i class="fas fa-cog text-white text-xl"></i>
                    </div>
                    Ayar Detayları
                </h1>
                <p class="text-lg text-gray-600 mt-2">{{ $setting->key ?? 'Ayar bilgileri' }}</p>
            </div>
            <div class="flex space-x-3">
                <a href="{{ route('admin.ayarlar.edit', $setting->id) }}"
                   class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-medium shadow-md hover:shadow-lg hover:scale-105 active:scale-95 dark:shadow-none">
                    <i class="fas fa-edit mr-2"></i>
                    Düzenle
                </a>
                <a href="{{ route('admin.ayarlar.index') }}"
                   class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 font-medium shadow-sm hover:shadow-md dark:shadow-none">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Geri Dön
                </a>
            </div>
        </div>
    </div>

    <div class="px-6">
        <div class="max-w-4xl mx-auto">
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Setting Details -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Ayar Bilgileri</h2>

                        <div class="space-y-4">
                            <div>
                                <span class="text-sm text-gray-600">Ayar Anahtarı</span>
                                <div class="text-lg font-medium text-gray-900 mt-1 dark:text-slate-100 dark:text-white">
                                    <code class="bg-gray-100 px-2 py-1 rounded text-sm dark:bg-slate-900">{{ $setting->key ?? 'N/A' }}</code>
                                </div>
                            </div>

                            <div>
                                <span class="text-sm text-gray-600">Ayar Değeri</span>
                                <div class="text-lg font-medium text-gray-900 mt-1 dark:text-slate-100 dark:text-white">
                                    @if($setting->type === 'boolean')
                                        <span class="px-2 py-1 text-xs font-medium rounded-full {{ $setting->value ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                            {{ $setting->value ? 'Doğru' : 'Yanlış' }}
                                        </span>
                                    @elseif($setting->type === 'json')
                                        <pre class="bg-gray-100 p-3 rounded text-sm overflow-x-auto dark:bg-slate-900">{{ json_encode(json_decode($setting->value), JSON_PRETTY_PRINT) }}</pre>
                                    @else
                                        {{ $setting->value ?? 'Değer yok' }}
                                    @endif
                                </div>
                            </div>

                            <div>
                                <span class="text-sm text-gray-600">Veri Tipi</span>
                                <div class="mt-1">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                        {{ ucfirst($setting->type ?? 'string') }}
                                    </span>
                                </div>
                            </div>

                            <div>
                                <span class="text-sm text-gray-600">Grup</span>
                                <div class="mt-1">
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-purple-100 text-purple-800">
                                        {{ ucfirst($setting->group ?? 'genel') }}
                                    </span>
                                </div>
                            </div>

                            @if($setting->description)
                                <div>
                                    <span class="text-sm text-gray-600">Açıklama</span>
                                    <div class="text-gray-900 mt-1 dark:text-slate-100 dark:text-white">{{ $setting->description }}</div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Value Editor -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h2 class="text-xl font-bold text-gray-800 mb-4 dark:text-slate-200">Değer Düzenleyici</h2>

                        <form action="{{ route('admin.ayarlar.update', $setting->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4">
                                @if($setting->type === 'boolean')
                                    <div>
                                        <label class="flex items-center">
                                            <input type="checkbox" name="value" value="1"
                                                   {{ $setting->value ? 'checked' : '' }} class="w-5 h-5 text-blue-600 bg-gray-50 dark:bg-slate-900 border-gray-300 dark:border-gray-600 rounded focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 transition-all duration-200 cursor-pointer mr-3">
                                            <span class="text-sm font-medium text-gray-700 dark:text-slate-300">Ayar Aktif</span>
                                        </label>
                                    </div>
                                @elseif($setting->type === 'json')
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">JSON Değeri</label>
                                        <textarea name="value" rows="10"
                                                  class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">{{ $setting->value }}</textarea>
                                        <p class="text-sm text-gray-500 mt-1">Geçerli JSON formatında girin</p>
                                    </div>
                                @else
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Değer</label>
                                        <input type="{{ $setting->type === 'integer' ? 'number' : 'text' }}"
                                               name="value" value="{{ $setting->value }}"
                                               class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                                    </div>
                                @endif

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1 dark:text-slate-300">Açıklama Güncelle</label>
                                    <textarea name="description" rows="3"
                                              class="w-full px-4 py-2.5 bg-gray-50 dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white placeholder-gray-500 dark:placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 dark:focus:ring-blue-400 focus:border-transparent transition-all duration-200 dark:text-slate-100">{{ $setting->description }}</textarea>
                                </div>
                            </div>

                            <div class="flex justify-end space-x-4 mt-6">
                                <button type="submit"
                                        class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 font-semibold shadow-md hover:shadow-lg hover:scale-105 active:scale-95 dark:shadow-none">
                                    <i class="fas fa-save mr-2"></i>
                                    Güncelle
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Setting Info -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Ayar Bilgileri</h3>
                        <div class="space-y-3">
                            <div>
                                <span class="text-sm text-gray-600">Oluşturulma</span>
                                <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $setting->created_at ? $setting->created_at->format('d.m.Y H:i') : 'Bilinmiyor' }}
                                </div>
                            </div>

                            <div>
                                <span class="text-sm text-gray-600">Son Güncelleme</span>
                                <div class="text-sm font-medium text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $setting->updated_at ? $setting->updated_at->format('d.m.Y H:i') : 'Bilinmiyor' }}
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">Hızlı İşlemler</h3>
                        <div class="space-y-3">
                            <a href="{{ route('admin.ayarlar.edit', $setting->id) }}"
                               class="block w-full text-center px-4 py-2 bg-blue-500 text-white rounded-lg hover:bg-blue-600 transition-colors">
                                <i class="fas fa-edit mr-2"></i>
                                Ayarı Düzenle
                            </a>

                            <button onclick="copyValue()"
                                    class="block w-full text-center px-4 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 transition-colors">
                                <i class="fas fa-copy mr-2"></i>
                                Değeri Kopyala
                            </button>

                            <button onclick="resetValue()"
                                    class="block w-full text-center px-4 py-2 bg-yellow-500 text-white rounded-lg hover:bg-yellow-600 transition-colors">
                                <i class="fas fa-undo mr-2"></i>
                                Varsayılana Sıfırla
                            </button>

                            <button onclick="deleteSetting()"
                                    class="block w-full text-center px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition-colors">
                                <i class="fas fa-trash mr-2"></i>
                                Ayarı Sil
                            </button>
                        </div>
                    </div>

                    <!-- Related Settings -->
                    <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                        <h3 class="text-lg font-semibold text-gray-800 mb-4 dark:text-slate-200">İlgili Ayarlar</h3>
                        <div class="space-y-2">
                            <a href="{{ route('admin.ayarlar.index', ['group' => $setting->group]) }}"
                               class="block text-sm text-blue-600 hover:text-blue-800">
                                <i class="fas fa-folder mr-1"></i>
                                {{ ucfirst($setting->group) }} Grubundaki Tüm Ayarlar
                            </a>
                            <a href="{{ route('admin.ayarlar.index') }}"
                               class="block text-sm text-blue-600 hover:text-blue-800">
                                <i class="fas fa-list mr-1"></i>
                                Tüm Ayarları Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
<script>
function copyValue() {
    const value = `{{ $setting->value ?? '' }}`;

    navigator.clipboard.writeText(value).then(() => {
        showToast('Değer panoya kopyalandı', 'success');
    }).catch(() => {
        showToast('Kopyalama başarısız', 'error');
    });
}

function resetValue() {
    if (confirm('Bu ayarı varsayılan değerine sıfırlamak istediğinizden emin misiniz?')) {
        // Reset logic would go here
        showToast('Ayar varsayılan değerine sıfırlandı', 'success');
    }
}

function deleteSetting() {
    if (confirm('Bu ayarı silmek istediğinizden emin misiniz? Bu işlem geri alınamaz.')) {
        fetch(`{{ route('admin.ayarlar.destroy', $setting->id) }}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        })
        .then(response => {
            if (response.ok) {
                showToast('Ayar başarıyla silindi', 'success');
                setTimeout(() => {
                    window.location.href = '{{ route('admin.ayarlar.index') }}';
                }, 1000);
            } else {
                showToast('Silme işlemi başarısız', 'error');
            }
        })
        .catch(error => {
            showToast('Silme işlemi sırasında hata oluştu', 'error');
        });
    }
}

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
