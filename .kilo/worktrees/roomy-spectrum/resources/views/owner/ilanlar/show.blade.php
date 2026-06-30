@extends('layouts.owner')

@section('title', $ilan->baslik . ' — İlan Detayı')

@section('content')
<div class="mb-6 flex flex-col items-start gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <div class="flex items-center gap-2">
            <a href="{{ route('owner.ilanlar.index') }}" class="text-sm font-medium text-gray-500 hover:text-gray-700 dark:text-slate-400 dark:hover:text-slate-200">
                &larr; İlanlarım
            </a>
            <span class="text-gray-400">/</span>
            <span class="text-sm text-gray-400">İlan #{{ $ilan->ilan_no ?? $ilan->id }}</span>
        </div>
        <h1 class="mt-2 text-2xl font-bold text-gray-900 dark:text-white">{{ $ilan->baslik }}</h1>
    </div>
    
    <div class="flex items-center gap-3">
        @if($ilan->yayindami)
            <span class="inline-flex items-center gap-1.5 rounded-md bg-green-50 px-3 py-1.5 text-sm font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-900/30 dark:text-green-400 dark:ring-green-500/20">
                <span class="h-1.5 w-1.5 rounded-full bg-green-500"></span>
                Yayında
            </span>
        @else
            <span class="inline-flex items-center gap-1.5 rounded-md bg-gray-50 px-3 py-1.5 text-sm font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10 dark:bg-gray-800 dark:text-gray-400 dark:ring-gray-700">
                <span class="h-1.5 w-1.5 rounded-full bg-gray-400"></span>
                {{ ucfirst($ilan->yayin_durumu ?? 'Pasif') }}
            </span>
        @endif
    </div>
</div>

<div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
    {{-- Sol Taraf (Ana Detaylar) --}}
    <div class="lg:col-span-2 space-y-6">
        
        {{-- Fotoğraf Galerisi + Yükleme Alanı --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800" x-data="ownerPhotoUpload({{ json_encode([
            'ilanId' => $ilan->id,
            'uploadUrl' => route('owner.ilanlar.photos.upload', $ilan->id),
            'deleteUrl' => route('owner.ilanlar.photos.delete', ['ilan' => $ilan->id, 'photo' => 'PHOTO_ID']),
        ]) }}">

            {{-- Kapak Fotoğrafı --}}
            @if($ilan->fotograflar && $ilan->fotograflar->count() > 0)
                @php $kapak = $ilan->fotograflar->where('kapak_fotografi', true)->first() ?? $ilan->fotograflar->first(); @endphp
                <div class="relative h-64 sm:h-96 w-full">
                    <img src="{{ Storage::url($kapak->dosya_yolu) }}" alt="{{ $ilan->baslik }}" class="h-full w-full object-cover">
                </div>
            @else
                <div class="flex h-64 w-full items-center justify-center bg-gray-50 text-gray-400 dark:bg-slate-800 dark:text-slate-500">
                    <div class="text-center">
                        <svg class="mx-auto h-12 w-12" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        <span class="mt-2 block text-sm">Fotoğraf Yok</span>
                    </div>
                </div>
            @endif

            {{-- Yükleme Alanı --}}
            <div class="border-t border-gray-100 p-4 dark:border-slate-700">
                {{-- Flash mesajları --}}
                <div x-show="message" x-transition class="mb-3 rounded-lg bg-green-50 p-3 text-sm text-green-700 dark:bg-green-900/20 dark:text-green-400" x-text="message"></div>
                <div x-show="error" x-transition class="mb-3 rounded-lg bg-red-50 p-3 text-sm text-red-700 dark:bg-red-900/20 dark:text-red-400" x-text="error"></div>

                {{-- Seçilen dosyalar önizleme --}}
                <div x-show="selectedFiles.length > 0" class="mb-3 grid grid-cols-4 gap-2">
                    <template x-for="(file, index) in selectedFiles" :key="index">
                        <div class="relative">
                            <img :src="file.preview" class="h-16 w-16 rounded-lg object-cover ring-1 ring-gray-200 dark:ring-slate-600">
                            <button type="button" @click="removeFile(index)" class="absolute -top-1 -right-1 flex h-5 w-5 items-center justify-center rounded-full bg-red-500 text-white hover:bg-red-600">
                                <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                    </template>
                </div>

                {{-- Upload butonu + seçim --}}
                <div class="flex items-center gap-3">
                    <label class="cursor-pointer rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-700 dark:text-slate-200 dark:hover:bg-slate-600">
                        <span>Fotoğraf Seç</span>
                        <input type="file" name="photos[]" multiple accept="image/jpeg,image/png,image/webp" class="hidden" @change="handleFileSelect($event)" :disabled="uploading">
                    </label>
                    <button type="button" @click="uploadPhotos()" :disabled="selectedFiles.length === 0 || uploading"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-50"
                        x-text="uploading ? 'Yükleniyor...' : 'Yükle'"></button>
                    <span class="text-xs text-gray-500 dark:text-slate-400">JPG, PNG, WEBP — Max 5MB × 10 fotoğraf</span>
                </div>
            </div>

            {{-- Mevcut Fotoğraflar Grid (yükleme sonrası güncellenir) --}}
            @if($ilan->fotograflar && $ilan->fotograflar->count() > 0)
            <div class="border-t border-gray-100 px-4 pb-4 pt-3 dark:border-slate-700">
                <div class="mb-2 flex items-center justify-between">
                    <span class="text-xs font-medium text-gray-500 dark:text-slate-400">
                        {{ $ilan->fotograflar->count() }} fotoğraf yüklendi
                    </span>
                </div>
                <div class="grid grid-cols-4 gap-2">
                    @foreach($ilan->fotograflar as $foto)
                    <div class="group relative">
                        <img src="{{ Storage::url($foto->dosya_yolu) }}" class="h-16 w-full rounded-lg object-cover ring-1 ring-gray-200 dark:ring-slate-600">
                        <button type="button"
                            onclick="if(confirm('Bu fotoğrafı silmek istediğinize emin misiniz?')) { document.getElementById('delete-photo-{{ $foto->id }}').submit(); }"
                            class="absolute inset-0 flex hidden items-center justify-center rounded-lg bg-black/50 group-hover:flex">
                            <svg class="h-5 w-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0110.138 21H5.79a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-9V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v8M5 7h14a1 1 0 011 1v12a1 1 0 01-1 1H5a1 1 0 01-1-1V7a1 1 0 011-1z" />
                            </svg>
                        </button>
                    </div>
                    <form id="delete-photo-{{ $foto->id }}" method="POST"
                        action="{{ route('owner.ilanlar.photos.delete', ['ilan' => $ilan->id, 'photo' => $foto->id]) }}"
                        @submit.prevent="if(confirm('Bu fotoğrafı silmek istediğinize emin misiniz?')) $el.submit()">
                        @csrf
                        @method('DELETE')
                    </form>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        @push('scripts')
        <script>
        function ownerPhotoUpload(config) {
            return {
                selectedFiles: [],
                uploading: false,
                message: '',
                error: '',

                handleFileSelect(event) {
                    const files = Array.from(event.target.files);
                    files.forEach(file => {
                        if (file.size > 5 * 1024 * 1024) {
                            this.error = '最大 5MB: ' + file.name;
                            return;
                        }
                        const reader = new FileReader();
                        reader.onload = (e) => {
                            this.selectedFiles.push({ file, preview: e.target.result });
                        };
                        reader.readAsDataURL(file);
                    });
                    event.target.value = '';
                },
                removeFile(index) {
                    this.selectedFiles.splice(index, 1);
                },
                async uploadPhotos() {
                    if (this.selectedFiles.length === 0) return;
                    this.uploading = true;
                    this.message = '';
                    this.error = '';
                    const formData = new FormData();
                    this.selectedFiles.forEach((item, i) => {
                        formData.append('photos[' + i + ']', item.file);
                    });
                    try {
                        const response = await fetch(config.uploadUrl, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content },
                            body: formData,
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.message = data.message || (this.selectedFiles.length + ' fotoğraf yüklendi.');
                            this.selectedFiles = [];
                            location.reload();
                        } else {
                            this.error = data.message || 'Yükleme başarısız.';
                        }
                    } catch (e) {
                        this.error = 'Bağlantı hatası: ' + e.message;
                    } finally {
                        this.uploading = false;
                    }
                }
            }
        }
        </script>
        @endpush

        {{-- Detaylar Tablosu --}}
        <div class="overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="border-b border-gray-200 bg-gray-50 px-5 py-4 dark:border-slate-700 dark:bg-slate-800/50">
                <h3 class="font-semibold text-gray-900 dark:text-white">İlan Özeti</h3>
            </div>
            <div class="px-5 py-5">
                <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Kategori</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->anaKategori->isim ?? '-' }} @if($ilan->altKategori) / {{ $ilan->altKategori->isim }} @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Konum</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->il->il_adi ?? '-' }}, {{ $ilan->ilce->ilce_adi ?? '-' }}, {{ $ilan->mahalle->mahalle_adi ?? '-' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Oda Sayısı</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->oda_sayisi ?? '-' }} @if($ilan->salon_sayisi) + {{ $ilan->salon_sayisi }} @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Metrekare (Brüt / Net)</dt>
                        <dd class="mt-1 text-sm text-gray-900 dark:text-white">
                            {{ $ilan->brut_m2 ?? '-' }} m² / {{ $ilan->net_m2 ?? '-' }} m²
                        </dd>
                    </div>
                </dl>
                
                @if($ilan->aciklama)
                    <div class="mt-6 border-t border-gray-100 pt-6 dark:border-slate-700">
                        <dt class="text-sm font-medium text-gray-500 dark:text-slate-400">Açıklama</dt>
                        <dd class="mt-2 text-sm text-gray-900 dark:text-slate-300 prose prose-sm max-w-none dark:prose-invert">
                            {!! nl2br(e($ilan->aciklama)) !!}
                        </dd>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Sağ Taraf (Fiyat, İstatistikler, Danışman) --}}
    <div class="space-y-6">
        
        {{-- Fiyat Kartı --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Güncel Fiyat</h3>
            <div class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">
                {{ number_format((float) $ilan->fiyat, 0, ',', '.') }} {{ $ilan->para_birimi }}
            </div>
        </div>

        {{-- Portföy Hazırlık Analizi (Sprint 3.4.3) --}}
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800"
             x-data="ownerReadiness()"
             x-init="loadReadiness()">
            {{-- Başlık --}}
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-500 dark:text-slate-400">Portföy Hazırlığı</h3>
                <span x-show="loading" class="text-xs text-gray-400">Analiz ediliyor...</span>
            </div>
            {{-- Skor --}}
            <div x-show="!loading && data" class="mb-3">
                <div class="mb-2 flex items-center gap-3">
                    <span class="text-3xl font-bold"
                       :class="scoreColor"
                       x-text="data.completion_percentage + '%'"></span>
                    <span class="rounded-full px-2 py-0.5 text-xs font-medium"
                       :class="badgeClass"
                       x-text="badgeLabel"></span>
                </div>
                <div class="h-2 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-slate-700">
                    <div class="h-full rounded-full transition-all duration-500"
                         :class="barColor"
                         :style="'width: ' + (data.completion_percentage || 0) + '%'"></div>
                </div>
            </div>
            {{-- Eksik alanlar --}}
            <div x-show="!loading && data && data.missing_fields && data.missing_fields.length > 0" class="mt-3">
                <h4 class="mb-2 text-xs font-medium text-gray-500 dark:text-slate-400">Eksik Bilgiler</h4>
                <ul class="space-y-1">
                    <template x-for="field in data.missing_fields" :key="field.field">
                        <li class="flex items-start gap-1.5 text-xs text-gray-600 dark:text-slate-300">
                            <svg class="mt-0.5 h-3.5 w-3.5 flex-shrink-0 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 2h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span x-text="field.label"></span>
                        </li>
                    </template>
                </ul>
            </div>
            {{-- Öneriler (Sprint 3.4.4) --}}
            <div x-show="!loading && data && data.recommendations && data.recommendations.length > 0" class="mt-4 border-t border-gray-100 pt-4 dark:border-slate-700">
                <h4 class="mb-2 text-xs font-semibold text-indigo-600 dark:text-indigo-400">Ne Yapmalısınız?</h4>
                <ul class="space-y-2">
                    <template x-for="rec in data.recommendations" :key="rec.field">
                        <li class="rounded-lg border border-indigo-100 bg-indigo-50/50 p-2 dark:border-indigo-900/30 dark:bg-indigo-900/10">
                            <div class="flex items-start gap-2">
                                <span class="mt-0.5 flex h-4 w-4 flex-shrink-0 items-center justify-center rounded-full text-[10px] font-bold"
                                      :class="{
                                          'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300': rec.priority === 'critical',
                                          'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300': rec.priority === 'high',
                                          'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300': rec.priority === 'medium',
                                          'bg-gray-100 text-gray-600 dark:bg-gray-800 dark:text-gray-400': rec.priority === 'low'
                                      }"
                                      x-text="rec.priority.charAt(0).toUpperCase()"></span>
                                <div class="flex-1">
                                    <p class="text-xs font-medium text-gray-700 dark:text-slate-200" x-text="rec.recommendation"></p>
                                    <p class="mt-0.5 text-[10px] text-indigo-600 dark:text-indigo-400" x-text="'→ ' + rec.action_label"></p>
                                </div>
                            </div>
                        </li>
                    </template>
                </ul>
            </div>
            {{-- Sonraki Adım (Sprint 3.4.4) --}}
            <div x-show="!loading && data && data.next_best_action" class="mt-4 rounded-lg border border-indigo-200 bg-indigo-50 p-3 dark:border-indigo-800 dark:bg-indigo-900/20">
                <div class="flex items-start gap-2">
                    <svg class="mt-0.5 h-4 w-4 flex-shrink-0 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                    <div>
                        <p class="text-xs font-semibold text-indigo-700 dark:text-indigo-300">Sıradaki Adım</p>
                        <p class="mt-0.5 text-xs text-indigo-600 dark:text-indigo-400" x-text="data.next_best_action"></p>
                    </div>
                </div>
            </div>
            {{-- Tamamlandı mesajı --}}
            <div x-show="!loading && data && (!data.missing_fields || data.missing_fields.length === 0)" class="mt-3">
                <div class="flex items-center gap-2 text-sm text-green-600 dark:text-green-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 6"/>
                    </svg>
                    <span>Tüm zorunlu alanlar dolu</span>
                </div>
            </div>
            {{-- Hata --}}
            <div x-show="error" class="mt-2 text-xs text-red-500" x-text="error"></div>
        </div>

        @push('scripts')
        <script>
        function ownerReadiness() {
            return {
                data: null,
                loading: true,
                error: null,
                async loadReadiness() {
                    this.loading = true;
                    this.error = null;
                    try {
                        const response = await fetch(
                            '{{ route('owner.ilanlar.readiness', $ilan->id) }}',
                            { headers: { 'Accept': 'application/json' } }
                        );
                        const json = await response.json();
                        if (json.success) {
                            this.data = json.data;
                        } else {
                            this.error = json.message || 'Analiz yüklenemedi.';
                        }
                    } catch (e) {
                        this.error = 'Bağlantı hatası.';
                    } finally {
                        this.loading = false;
                    }
                },
                get scoreColor() {
                    if (!this.data) return 'text-gray-400';
                    const p = this.data.completion_percentage || 0;
                    return p >= 80 ? 'text-green-600 dark:text-green-400'
                         : p >= 50 ? 'text-amber-600 dark:text-amber-400'
                         : 'text-red-600 dark:text-red-400';
                },
                get badgeClass() {
                    if (!this.data) return '';
                    const p = this.data.completion_percentage || 0;
                    return p >= 80 ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300'
                         : p >= 50 ? 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300'
                         : 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300';
                },
                get badgeLabel() {
                    if (!this.data) return '';
                    const p = this.data.completion_percentage || 0;
                    return p >= 80 ? 'Yayına Hazır'
                         : p >= 50 ? 'Eksikler Var'
                         : 'Bilgi Girilmeli';
                },
                get barColor() {
                    if (!this.data) return 'bg-gray-300';
                    const p = this.data.completion_percentage || 0;
                    return p >= 80 ? 'bg-green-500'
                         : p >= 50 ? 'bg-amber-500'
                         : 'bg-red-500';
                }
            };
        }
        </script>
        @endpush

        {{-- Danışman Kartı --}}
        @if($ilan->danisman && $ilan->danisman->id)
        <div class="rounded-xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-4 text-sm font-medium text-gray-500 dark:text-slate-400">Sorumlu Danışman</h3>
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-full bg-blue-100 text-lg font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                    {{ strtoupper(substr($ilan->danisman->name, 0, 1)) }}
                </div>
                <div>
                    <div class="font-medium text-gray-900 dark:text-white">{{ $ilan->danisman->name }}</div>
                    <div class="text-sm text-gray-500 dark:text-slate-400">{{ $ilan->danisman->email }}</div>
                    @if($ilan->danisman->phone_number)
                        <div class="text-sm text-gray-500 dark:text-slate-400">{{ $ilan->danisman->phone_number }}</div>
                    @endif
                </div>
            </div>
            <div class="mt-4">
                <a href="mailto:{{ $ilan->danisman->email }}" class="block w-full rounded-md bg-blue-50 px-3 py-2 text-center text-sm font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50">
                    Mesaj Gönder
                </a>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection
