@extends('admin.layouts.admin')

@section('title', 'Özellik Kategorisi Düzenle')

@section('content_header')
    <div class="flex justify-between items-center">
        <h1 class="text-2xl font-semibold text-gray-800 dark:text-slate-200">Kategori Düzenle: {{ $kategori->name }}</h1>
        <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
            class="inline-flex items-center justify-center gap-2 px-4 py-2.5 bg-white dark:bg-gray-700 text-gray-700 dark:text-slate-200 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:bg-gray-50 dark:hover:bg-gray-600 focus:ring-2 focus:ring-offset-2 transition-all duration-200 touch-target-optimized dark:shadow-none dark:bg-slate-900 dark:text-slate-300">
            <i class="fas fa-arrow-left mr-2"></i> Geri Dön
        </a>
    </div>
@endsection

@section('content')
    <!-- Geri Butonu -->
    <div class="mb-4">
        <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
            class="inline-flex items-center px-4 py-2 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200 shadow-sm dark:shadow-none dark:text-slate-300">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
            </svg>
            Geri
        </a>
    </div>

    <div class="bg-gray-50 dark:bg-slate-900 rounded-lg shadow-md overflow-hidden dark:shadow-none">
        <div class="p-6">
            @if (session('success'))
                <x-admin.alert type="success" class="mb-4">
                    {{ session('success') }}
                </x-admin.alert>
            @endif

            <form action="{{ route('admin.ozellikler.kategoriler.update', $kategori->id) }}" method="POST"
                x-data="{ showAdvanced: false }">
                @csrf
                @method('PUT')
                @if (isset($errors) && (is_object($errors) ? $errors->any() : count($errors) > 0))
                    <x-admin.alert type="error">
                        <p class="font-semibold mb-1">Lütfen aşağıdaki hataları düzeltin:</p>
                        <ul class="list-disc list-inside space-y-1">
                            @foreach ((is_object($errors) ? $errors->all() : $errors) as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </x-admin.alert>
                @endif
                <div class="grid md:grid-cols-2 gap-6">
                    <div>
                        <x-admin.input name="name" label="Kategori Adı" :required="true" :value="$kategori->name" />
                        <x-admin.textarea name="description" label="Açıklama" :value="$kategori->description" rows="4" />

                        <div class="mb-4">
                            <label class="block text-[11px] font-bold text-gray-500 dark:text-gray-400 mb-2 uppercase tracking-widest">
                                Uygulama Alanı
                            </label>
                            @php
                                $appliesToArray = old('applies_to')
                                    ? (is_array(old('applies_to')) ? old('applies_to') : explode(',', old('applies_to')))
                                    : ($kategori->applies_to_array ?? []);
                                $appliesToArray = array_map('trim', (array)$appliesToArray);
                            @endphp

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <div class="col-span-full mb-2">
                                    <span class="text-xs text-gray-500 dark:text-gray-400">
                                        Seçili emlak türlerini aşağıdan işaretleyiniz. Seçilenler <span class="text-blue-600 font-bold">renkli</span> görünecektir.
                                    </span>
                                </div>

                                <div class="col-span-full sm:col-span-2">
                                    <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-slate-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all group dark:border-slate-700"
                                           :class="{'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20 border-blue-500': $el.querySelector('input').checked}">
                                        <input type="checkbox"
                                               onchange="if(this.checked) { document.querySelectorAll('.applies-to-item').forEach(el => el.checked = false); this.checked = true; } else { this.checked = false; }"
                                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                               {{ empty($appliesToArray) ? 'checked' : '' }}>
                                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 dark:text-white">
                                            Tüm Emlak Türleri
                                        </span>
                                    </label>
                                </div>

                                @foreach(($ilanKategorileri ?? collect()) as $ilanKategori)
                                    @php
                                        $kategoriSlug = $ilanKategori->slug;
                                        $isSelected = in_array($kategoriSlug, $appliesToArray);
                                    @endphp
                                    <label class="relative flex items-center p-3 rounded-lg border border-gray-200 dark:border-slate-800 cursor-pointer hover:bg-gray-50 dark:hover:bg-gray-800 transition-all group dark:border-slate-700"
                                           :class="{'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20 border-blue-500': {{ $isSelected ? 'true' : 'false' }}, 'hover:border-blue-300': !{{ $isSelected ? 'true' : 'false' }}}"
                                           x-data="{ checked: {{ $isSelected ? 'true' : 'false' }} }"
                                           @click="checked = !checked"
                                           :class="checked ? 'ring-2 ring-blue-500 bg-blue-50 dark:bg-blue-900/20 border-blue-500' : 'bg-white dark:bg-gray-800'">
                                        <input type="checkbox" name="applies_to[]" value="{{ $kategoriSlug }}"
                                               x-model="checked"
                                               class="applies-to-item w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600">
                                        <span class="ml-3 text-sm font-medium text-gray-900 dark:text-slate-200 group-hover:text-blue-600 dark:group-hover:text-blue-400 dark:text-white">
                                            {{ $ilanKategori->name }}
                                        </span>
                                    </label>
                                @endforeach
                            </div>
                        </div>

                        <x-admin.input name="display_order" type="number" label="Sıra" :value="$kategori->display_order" />
                        <x-admin.toggle name="aktiflik_durumu" label="Aktiflik Durumu" :checked="$kategori->aktiflik_durumu" />
                    </div>
                    <div>
                        <div class="mb-4">
                            <button type="button" @click="showAdvanced=!showAdvanced"
                                class="text-sm text-indigo-600 hover:underline flex items-center">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M12 4v16m8-8H4" />
                                </svg>
                                Gelişmiş Alanlar
                            </button>
                        </div>
                        <div x-show="showAdvanced" x-cloak>
                            <div>
                                <x-admin.input name="slug" label="Slug" :value="$kategori->slug"
                                    help="Boş ise addan türetilir" />
                                <p id="slug-feedback" class="mt-1 text-xs"></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="flex justify-end space-x-3 mt-6">
                    <a href="{{ route('admin.ozellikler.kategoriler.index') }}"
                        class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 rounded-lg shadow-sm text-sm dark:shadow-none dark:text-slate-200">İptal</a>
                    <button type="submit"
                        class="inline-flex items-center justify-center gap-2 px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 hover:scale-105 active:scale-95 focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 transition-all duration-200 shadow-md hover:shadow-lg text-sm font-medium touch-target-optimized dark:shadow-none">Güncelle</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Kategori İstatistikleri -->
    <div class="mt-6 bg-gray-50 dark:bg-slate-900 rounded-lg shadow-md overflow-hidden dark:shadow-none">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">Kategori İstatistikleri</h2>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                    <h3 class="admin-h3">Özellik Sayısı</h3>
                    <p class="text-3xl font-bold text-indigo-600 dark:text-indigo-400">{{ $kategori->features->count() }}
                    </p>
                </div>

                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                    <h3 class="admin-h3">Oluşturulma Tarihi</h3>
                    <p class="text-md font-medium text-gray-600 dark:text-gray-400">
                        {{ $kategori->created_at->format('d.m.Y H:i') }}</p>
                </div>

                <div class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4">
                    <h3 class="admin-h3">Son Güncelleme</h3>
                    <p class="text-md font-medium text-gray-600 dark:text-gray-400">
                        {{ $kategori->updated_at->format('d.m.Y H:i') }}</p>
                </div>
            </div>

            <div class="mt-4">
                <a href="{{ route('admin.ilan-kategorileri.ozellikler', $kategori->id) }}"
                    class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-lg text-indigo-700 bg-indigo-100 hover:bg-indigo-200 dark:text-indigo-100 dark:bg-indigo-900 dark:hover:bg-indigo-800 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-list mr-2"></i> Bu Kategorideki Özellikleri Görüntüle
                </a>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const adInput = document.getElementById('ad');
            const slugInput = document.getElementById('slug');
            const slugFeedback = document.getElementById('slug-feedback');
            let lastCheckedSlug = '';

            function generateSlug(t) {
                return t.toLowerCase().replace(/ğ/g, 'g').replace(/ü/g, 'u').replace(/ş/g, 's').replace(/ı/g, 'i')
                    .replace(/ö/g, 'o').replace(/ç/g, 'c').replace(/[^a-z0-9]+/g, '-').replace(/-+/g, '-').replace(
                        /^-|-$/g, '');
            }

            function debounce(fn, wait = 400) {
                let to;
                return (...a) => {
                    clearTimeout(to);
                    to = setTimeout(() => fn(...a), wait);
                }
            }

            function setFeedback(state, msg) {
                if (!slugFeedback) return;
                slugFeedback.textContent = msg || '';
                slugFeedback.className = 'mt-1 text-xs ' + (state === 'ok' ? 'text-green-600 dark:text-green-400' :
                    state === 'err' ? 'text-red-600 dark:text-red-400' : 'text-gray-400');
                if (slugInput) {
                    slugInput.classList.remove('border-red-500', 'border-green-500');
                    if (state === 'ok') slugInput.classList.add('border-green-500');
                    if (state === 'err') slugInput.classList.add('border-red-500');
                }
            }
            adInput?.addEventListener('input', () => {
                if (slugInput && !slugInput.value) {
                    slugInput.value = generateSlug(adInput.value);
                }
            });
            const checkSlug = debounce(() => {
                if (!slugInput) return;
                const val = slugInput.value.trim();
                if (!val) {
                    setFeedback('neutral', '');
                    return;
                }
                if (val === lastCheckedSlug) return;
                lastCheckedSlug = val;
                setFeedback('neutral', 'Kontrol ediliyor...');
                fetch(
                        `{{ route('admin.ozellikler.kategoriler.slug.check') }}?slug=${encodeURIComponent(val)}&ignore={{ $kategori->id }}`
                        )
                    .then(r => r.json()).then(d => {
                        if (d.unique) {
                            setFeedback('ok', 'Uygun');
                        } else {
                            setFeedback('err', 'Slug kullanımda');
                        }
                    })
                    .catch(() => setFeedback('err', 'Kontrol hatası'));
            }, 500);
            slugInput?.addEventListener('input', checkSlug);
        });
    </script>
@endpush
