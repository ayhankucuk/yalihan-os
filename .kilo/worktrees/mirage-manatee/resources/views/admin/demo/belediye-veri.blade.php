@extends('admin.layouts.admin')

@section('title', 'Belediye Açık Veri Demo')

@section('content')
    <div x-data="belediyeVeriDemo()" class="container mx-auto px-4 py-6">
        {{-- Breadcrumb / Dashboard Link --}}
        <div class="mb-4">
            <a href="{{ route('admin.dashboard') }}"
                class="inline-flex items-center gap-2 text-sm text-blue-600 transition-colors duration-200 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18">
                    </path>
                </svg>
                <span>Dashboard'a Dön</span>
            </a>
        </div>

        <div class="mb-8">
            <div class="mb-4 flex items-start justify-between gap-4">
                <div class="flex-1">
                    <h1 class="mb-2 text-3xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">
                        🏛️ Belediye Açık Veri Demo
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400">
                        Belediye açık veri entegrasyonu, imar planı ve çevre verileri test sayfası
                    </p>
                </div>
                <a href="{{ route('admin.dashboard') }}"
                    class="inline-flex items-center gap-2 whitespace-nowrap rounded-lg bg-blue-600 px-4 py-2 text-white shadow-md transition-all duration-200 hover:bg-blue-700 hover:shadow-lg dark:shadow-none">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6">
                        </path>
                    </svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        {{-- TABS --}}
        <div
            class="mb-6 rounded-xl border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
            <div class="flex gap-2 border-b border-gray-200 px-6 dark:border-slate-700 dark:border-slate-800"
                role="tablist">
                <button @click="activeTab = 'belediye'"
                    :class="activeTab === 'belediye' ?
                        'border-blue-600 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' :
                        'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
                    class="border-b-2 px-4 py-3 text-sm font-medium transition-all duration-200">
                    🏛️ Belediye Verileri
                </button>
                <button @click="activeTab = 'imar'"
                    :class="activeTab === 'imar' ?
                        'border-blue-600 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' :
                        'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
                    class="border-b-2 px-4 py-3 text-sm font-medium transition-all duration-200">
                    📐 İmar Planı
                </button>
                <button @click="activeTab = 'cevre'"
                    :class="activeTab === 'cevre' ?
                        'border-blue-600 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' :
                        'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
                    class="border-b-2 px-4 py-3 text-sm font-medium transition-all duration-200">
                    🌿 Çevre Verileri
                </button>
                <button @click="activeTab = 'kira'"
                    :class="activeTab === 'kira' ?
                        'border-blue-600 text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20' :
                        'border-transparent text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200'"
                    class="border-b-2 px-4 py-3 text-sm font-medium transition-all duration-200">
                    💰 Kira Tahmini (ML)
                </button>
            </div>

            <div class="p-6">
                {{-- BELEDİYE VERİLERİ TAB --}}
                <div x-show="activeTab === 'belediye'" class="space-y-6">
                    {{-- Belediye Seçimi --}}
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-slate-900">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            Belediye Açık Veri API Testi
                        </h3>

                        <div class="mb-4">
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                Belediye Seçin
                            </label>
                            <select x-model="belediyeForm.belediye" @change="belediyeFormChanged()"
                                class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                <option value="istanbul">🏛️ İstanbul Büyükşehir Belediyesi</option>
                                <option value="mugla">🏖️ Muğla (TurkiyeAPI)</option>
                            </select>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400"
                                x-show="belediyeForm.belediye === 'mugla'">
                                💡 Muğla BB'nin resmi açık veri portalı yok. TurkiyeAPI kullanılıyor.
                            </p>
                        </div>

                        {{-- İstanbul BB Formu --}}
                        <div x-show="belediyeForm.belediye === 'istanbul'" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                        Resource ID
                                    </label>
                                    <input type="text" x-model="belediyeForm.resourceId"
                                        placeholder="örn: imar_planlari_resource_id"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                        Limit
                                    </label>
                                    <input type="number" x-model="belediyeForm.limit" value="100"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                </div>
                            </div>

                            <button @click="fetchBelediyeData()" :disabled="loading.belediye"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-all duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!loading.belediye">📥 İstanbul BB Veri Çek</span>
                                <span x-show="loading.belediye">⏳ Yükleniyor...</span>
                            </button>
                        </div>

                        {{-- Muğla Formu --}}
                        <div x-show="belediyeForm.belediye === 'mugla'" class="space-y-4">
                            <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                        Veri Tipi
                                    </label>
                                    <select x-model="belediyeForm.muglaType"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                        <option value="districts">İlçeler (Bodrum, Marmaris, Fethiye...)</option>
                                        <option value="towns">Beldeler (Yalıkavak, Gümüşlük...)</option>
                                        <option value="villages">Köyler</option>
                                        <option value="all_locations">Tüm Lokasyonlar</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                        İlçe ID (Beldeler/Köyler için)
                                    </label>
                                    <input type="number" x-model="belediyeForm.muglaId" placeholder="Bodrum: 4801"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        İlçeler için boş bırakın
                                    </p>
                                </div>
                            </div>

                            <div
                                class="rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/20">
                                <p class="text-sm text-blue-800 dark:text-blue-200">
                                    <strong>💡 Bilgi:</strong> Muğla için popüler ilçeler:
                                </p>
                                <ul class="mt-1 list-inside list-disc text-xs text-blue-700 dark:text-blue-300">
                                    <li>Bodrum (4801) - Yalıkavak, Gümüşlük, Türkbükü</li>
                                    <li>Marmaris (4802) - İçmeler, Turunç</li>
                                    <li>Fethiye (4803) - Ölüdeniz, Çalış</li>
                                    <li>Datça (4804) - Palamutbükü</li>
                                </ul>
                            </div>

                            <button @click="fetchMuglaData()" :disabled="loading.mugla"
                                class="rounded-lg bg-green-600 px-4 py-2 text-white transition-all duration-200 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!loading.mugla">🏖️ Muğla Veri Çek</span>
                                <span x-show="loading.mugla">⏳ Yükleniyor...</span>
                            </button>
                        </div>
                    </div>

                    {{-- İstanbul BB Sonuçları --}}
                    <div x-show="belediyeResult && belediyeForm.belediye === 'istanbul'"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-2 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">İstanbul BB
                            Sonuçları:</h4>
                        <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                            <span x-show="belediyeResult?.success">✅ Başarılı</span>
                            <span x-show="!belediyeResult?.success">❌ Hata</span>
                            <span x-show="belediyeResult?.total"> - Toplam: <strong
                                    x-text="belediyeResult.total"></strong> kayıt</span>
                        </div>
                        <pre class="max-h-96 overflow-auto rounded bg-gray-50 p-4 text-xs dark:bg-slate-900"
                            x-text="JSON.stringify(belediyeResult, null, 2)"></pre>
                    </div>

                    {{-- Muğla Sonuçları --}}
                    <div x-show="muglaResult && belediyeForm.belediye === 'mugla'"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-2 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Muğla Verileri
                            (TurkiyeAPI):</h4>
                        <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                            <span x-show="muglaResult?.success">✅ Başarılı</span>
                            <span x-show="!muglaResult?.success">❌ Hata</span>
                            <span x-show="muglaResult?.data?.length"> - Toplam: <strong
                                    x-text="muglaResult.data.length"></strong> kayıt</span>
                        </div>
                        <div x-show="muglaResult?.data?.length" class="mb-4">
                            <div class="grid grid-cols-1 gap-3 md:grid-cols-2 lg:grid-cols-3">
                                <template x-for="(item, index) in muglaResult.data.slice(0, 12)" :key="index">
                                    <div
                                        class="rounded border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                        <div class="font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                            x-text="item.name || item.mahalle_adi || item.belde_adi || item.koy_adi || '-'">
                                        </div>
                                        <div class="mt-1 text-xs text-gray-600 dark:text-gray-400" x-show="item.nufus">
                                            👥 Nüfus: <span x-text="item.nufus?.toLocaleString('tr-TR') || '-'"></span>
                                        </div>
                                        <div class="text-xs text-gray-600 dark:text-gray-400" x-show="item.alan">
                                            📐 Alan: <span x-text="item.alan"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                        <pre class="max-h-96 overflow-auto rounded bg-gray-50 p-4 text-xs dark:bg-slate-900"
                            x-text="JSON.stringify(muglaResult, null, 2)"></pre>
                    </div>
                </div>

                {{-- İMAR PLANI TAB --}}
                <div x-show="activeTab === 'imar'" class="space-y-6">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-slate-900">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            İmar Planı Sorgulama
                        </h3>

                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    Mahalle ID
                                </label>
                                <input type="text" x-model="imarForm.mahalleId" placeholder="Mahalle ID veya adı"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    Koordinatlar (Lat, Lng)
                                </label>
                                <div class="flex gap-2">
                                    <input type="number" step="0.000001" x-model="imarForm.lat" placeholder="Lat"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                    <input type="number" step="0.000001" x-model="imarForm.lng" placeholder="Lng"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                </div>
                            </div>
                        </div>

                        <button @click="fetchImarPlani()" :disabled="loading.imar"
                            class="mr-2 rounded-lg bg-blue-600 px-4 py-2 text-white transition-all duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="!loading.imar">📐 İmar Planı Getir</span>
                            <span x-show="loading.imar">⏳ Yükleniyor...</span>
                        </button>

                        <button @click="checkImarUygunlugu()" :disabled="loading.imarUygunluk"
                            class="rounded-lg bg-green-600 px-4 py-2 text-white transition-all duration-200 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-show="!loading.imarUygunluk">✅ Uygunluk Kontrolü</span>
                            <span x-show="loading.imarUygunluk">⏳ Kontrol ediliyor...</span>
                        </button>
                    </div>

                    <div x-show="imarResult"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-2 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">İmar Planı Sonucu:
                        </h4>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">İmar Durumu</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="imarResult?.imar_durumu || '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">KAKS</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="imarResult?.kaks || '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">TAKS</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="imarResult?.taks || '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Gabari</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="imarResult?.gabari ? imarResult.gabari + 'm' : '-'"></div>
                            </div>
                        </div>
                    </div>

                    <div x-show="imarUygunlukResult"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-2 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Uygunluk Kontrolü:
                        </h4>
                        <div class="mb-2 flex items-center gap-2">
                            <span class="text-sm font-medium"
                                :class="imarUygunlukResult?.uygun ? 'text-green-600' : 'text-red-600'">
                                <span x-show="imarUygunlukResult?.uygun">✅ Uygun</span>
                                <span x-show="!imarUygunlukResult?.uygun">❌ Uygun Değil</span>
                            </span>
                        </div>
                        <div x-show="imarUygunlukResult?.mesajlar?.length"
                            class="text-sm text-gray-600 dark:text-gray-400">
                            <ul class="list-inside list-disc">
                                <template x-for="mesaj in imarUygunlukResult.mesajlar" :key="mesaj">
                                    <li x-text="mesaj"></li>
                                </template>
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- ÇEVRE VERİLERİ TAB --}}
                <div x-show="activeTab === 'cevre'" class="space-y-6">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-slate-900">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            Çevre Verileri Sorgulama
                        </h3>

                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    Latitude
                                </label>
                                <input type="number" step="0.000001" x-model="cevreForm.lat" placeholder="37.0361"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    Longitude
                                </label>
                                <input type="number" step="0.000001" x-model="cevreForm.lng" placeholder="27.4305"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button @click="fetchHavaKalitesi()" :disabled="loading.havaKalitesi"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-all duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!loading.havaKalitesi">🌬️ Hava Kalitesi</span>
                                <span x-show="loading.havaKalitesi">⏳ Yükleniyor...</span>
                            </button>
                            <button @click="fetchCevreSkoru()" :disabled="loading.cevreSkoru"
                                class="rounded-lg bg-green-600 px-4 py-2 text-white transition-all duration-200 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!loading.cevreSkoru">📊 Çevre Skoru</span>
                                <span x-show="loading.cevreSkoru">⏳ Yükleniyor...</span>
                            </button>
                            <button @click="fetchYakinCevre()" :disabled="loading.yakinCevre"
                                class="rounded-lg bg-purple-600 px-4 py-2 text-white transition-all duration-200 hover:bg-purple-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!loading.yakinCevre">🗺️ Yakın Çevre</span>
                                <span x-show="loading.yakinCevre">⏳ Yükleniyor...</span>
                            </button>
                        </div>
                    </div>

                    <div x-show="havaKalitesiResult"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-4 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Hava Kalitesi:
                        </h4>
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">AQI</div>
                                <div class="text-2xl font-bold" :class="getAqiColor(havaKalitesiResult?.aqi)"
                                    x-text="havaKalitesiResult?.aqi || '-'"></div>
                                <div class="text-xs text-gray-500 dark:text-gray-400"
                                    x-text="havaKalitesiResult?.aqi_level || '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">PM2.5</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="havaKalitesiResult?.pm25 ? havaKalitesiResult.pm25 + ' μg/m³' : '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">PM10</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="havaKalitesiResult?.pm10 ? havaKalitesiResult.pm10 + ' μg/m³' : '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">NO₂</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="havaKalitesiResult?.no2 ? havaKalitesiResult.no2 + ' μg/m³' : '-'"></div>
                            </div>
                        </div>
                    </div>

                    <div x-show="cevreSkoruResult"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-4 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Çevre Skoru:</h4>
                        <div class="flex items-center gap-4">
                            <div class="text-4xl font-bold" :class="getSkorColor(cevreSkoruResult?.skor)"
                                x-text="cevreSkoruResult?.skor || 0"></div>
                            <div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="cevreSkoruResult?.seviye || '-'"></div>
                                <div class="text-sm text-gray-600 dark:text-gray-400"
                                    x-text="cevreSkoruResult?.oneri || '-'"></div>
                            </div>
                        </div>
                    </div>

                    <div x-show="yakinCevreResult"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-4 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Yakın Çevre
                            Analizi:</h4>
                        <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Yeşil Alan</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="yakinCevreResult?.yesil_alan_orani ? yakinCevreResult.yesil_alan_orani + '%' : '-'">
                                </div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Okul</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="yakinCevreResult?.okul_sayisi || '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Hastane</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="yakinCevreResult?.hastane_sayisi || '-'"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-3 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Plaj Uzaklık</div>
                                <div class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="yakinCevreResult?.plaj_uzaklik ? yakinCevreResult.plaj_uzaklik + 'm' : '-'">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- KİRA TAHMİNİ TAB --}}
                <div x-show="activeTab === 'kira'" class="space-y-6">
                    <div class="rounded-lg bg-gray-50 p-4 dark:bg-slate-900">
                        <h3 class="mb-4 text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                            🤖 Makine Öğrenmesi ile Kira Tahmini
                        </h3>
                        <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                            İzmir, Aydın, Muğla ve Denizli için ML tabanlı kira tahmini
                            <br>
                            <span class="text-xs">Kaynak: <a href="https://github.com/gizemtursunn/KiraTahmini"
                                    target="_blank" class="text-blue-600 hover:underline">KiraTahmini
                                    Repository</a></span>
                        </p>

                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-2">
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    İl
                                </label>
                                <select x-model="kiraForm.il_id"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                    <option value="">Seçiniz</option>
                                    <option value="35">İzmir</option>
                                    <option value="9">Aydın</option>
                                    <option value="48">Muğla</option>
                                    <option value="20">Denizli</option>
                                </select>
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    Metrekare (m²)
                                </label>
                                <input type="number" x-model="kiraForm.metrekare" placeholder="örn: 120"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    Oda Sayısı
                                </label>
                                <input type="number" x-model="kiraForm.oda_sayisi" placeholder="örn: 3"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                            </div>
                            <div>
                                <label
                                    class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    Bina Yaşı
                                </label>
                                <input type="number" x-model="kiraForm.bina_yasi" placeholder="örn: 5"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-gray-900 focus:border-transparent focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label
                                class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                Özellikler
                            </label>
                            <div class="grid grid-cols-2 gap-2 md:grid-cols-4">
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" x-model="kiraForm.esyali" class="rounded border-gray-300">
                                    <span class="text-sm">Eşyalı</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" x-model="kiraForm.balkon" class="rounded border-gray-300">
                                    <span class="text-sm">Balkon</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" x-model="kiraForm.asansor" class="rounded border-gray-300">
                                    <span class="text-sm">Asansör</span>
                                </label>
                                <label class="flex items-center gap-2">
                                    <input type="checkbox" x-model="kiraForm.otopark" class="rounded border-gray-300">
                                    <span class="text-sm">Otopark</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2">
                            <button @click="predictRentalPrice()" :disabled="loading.kiraTahmini"
                                class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-all duration-200 hover:bg-blue-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!loading.kiraTahmini">💰 Aylık Kira Tahmini</span>
                                <span x-show="loading.kiraTahmini">⏳ Hesaplanıyor...</span>
                            </button>
                            <button @click="predictYazlikRental()" :disabled="loading.yazlikKira"
                                class="rounded-lg bg-green-600 px-4 py-2 text-white transition-all duration-200 hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-50">
                                <span x-show="!loading.yazlikKira">🏖️ Yazlık Kira Tahmini</span>
                                <span x-show="loading.yazlikKira">⏳ Hesaplanıyor...</span>
                            </button>
                        </div>
                    </div>

                    {{-- Kira Tahmini Sonuçları --}}
                    <div x-show="kiraTahminiResult"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-4 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Kira Tahmini
                            Sonuçları:</h4>
                        <div class="mb-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded bg-gray-50 p-4 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Minimum</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="formatPrice(kiraTahminiResult?.prediction?.min || 0)"></div>
                            </div>
                            <div class="rounded border-2 border-blue-500 bg-blue-50 p-4 dark:bg-blue-900/20">
                                <div class="text-sm text-blue-600 dark:text-blue-400">Önerilen</div>
                                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400"
                                    x-text="formatPrice(kiraTahminiResult?.prediction?.recommended || kiraTahminiResult?.prediction?.monthly_rent || 0)">
                                </div>
                            </div>
                            <div class="rounded bg-gray-50 p-4 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Maksimum</div>
                                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="formatPrice(kiraTahminiResult?.prediction?.max || 0)"></div>
                            </div>
                        </div>
                        <div class="text-sm text-gray-600 dark:text-gray-400">
                            <div>Güven Skoru: <strong x-text="kiraTahminiResult?.confidence || 0"></strong>%</div>
                            <div x-show="kiraTahminiResult?.prediction?.unit_price_per_m2">
                                Birim Fiyat: <strong x-text="kiraTahminiResult.prediction.unit_price_per_m2"></strong>
                                TL/m²
                            </div>
                        </div>
                    </div>

                    {{-- Yazlık Kira Tahmini Sonuçları --}}
                    <div x-show="yazlikKiraResult"
                        class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                        <h4 class="mb-4 font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Yazlık Kira
                            Tahmini Sonuçları:</h4>
                        <div class="mb-2 text-sm text-gray-600 dark:text-gray-400">
                            Sezon: <strong x-text="yazlikKiraResult?.season || '-'"></strong>
                            <span x-show="yazlikKiraResult?.prediction?.season_multiplier">
                                (Çarpan: <strong x-text="yazlikKiraResult.prediction.season_multiplier"></strong>x)
                            </span>
                        </div>
                        <div class="grid grid-cols-1 gap-4 md:grid-cols-3">
                            <div class="rounded bg-gray-50 p-4 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Günlük</div>
                                <div class="text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="formatPrice(yazlikKiraResult?.prediction?.daily_rent || 0)"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-4 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Haftalık</div>
                                <div class="text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="formatPrice(yazlikKiraResult?.prediction?.weekly_rent || 0)"></div>
                            </div>
                            <div class="rounded bg-gray-50 p-4 dark:bg-slate-900">
                                <div class="text-sm text-gray-600 dark:text-gray-400">Aylık</div>
                                <div class="text-xl font-bold text-gray-900 dark:text-slate-100 dark:text-white"
                                    x-text="formatPrice(yazlikKiraResult?.prediction?.monthly_rent || 0)"></div>
                            </div>
                        </div>
                        <div class="mt-4 text-sm text-gray-600 dark:text-gray-400">
                            <div>Güven Skoru: <strong x-text="yazlikKiraResult?.confidence || 0"></strong>%</div>
                            <div x-show="yazlikKiraResult?.prediction?.base_monthly_rent">
                                Temel Aylık Kira: <strong
                                    x-text="formatPrice(yazlikKiraResult.prediction.base_monthly_rent)"></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            function belediyeVeriDemo() {
                return {
                    activeTab: 'belediye',
                    loading: {
                        belediye: false,
                        mugla: false,
                        imar: false,
                        imarUygunluk: false,
                        havaKalitesi: false,
                        cevreSkoru: false,
                        yakinCevre: false,
                        kiraTahmini: false,
                        yazlikKira: false,
                    },
                    belediyeForm: {
                        belediye: 'istanbul',
                        resourceId: '',
                        limit: 100,
                        muglaType: 'districts',
                        muglaId: '',
                    },
                    imarForm: {
                        mahalleId: '',
                        lat: '',
                        lng: '',
                        kaks: '',
                        taks: '',
                        gabari: '',
                    },
                    cevreForm: {
                        lat: '37.0361', // Bodrum örnek koordinat
                        lng: '27.4305',
                    },
                    kiraForm: {
                        il_id: '48', // Muğla
                        metrekare: '',
                        oda_sayisi: '',
                        bina_yasi: '',
                        esyali: false,
                        balkon: false,
                        asansor: false,
                        otopark: false,
                    },
                    belediyeResult: null,
                    muglaResult: null,
                    imarResult: null,
                    imarUygunlukResult: null,
                    havaKalitesiResult: null,
                    cevreSkoruResult: null,
                    yakinCevreResult: null,
                    kiraTahminiResult: null,
                    yazlikKiraResult: null,

                    belediyeFormChanged() {
                        // Form değiştiğinde sonuçları temizle
                        this.belediyeResult = null;
                        this.muglaResult = null;
                    },

                    async fetchBelediyeData() {
                        this.loading.belediye = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.istanbul-data') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    resource_id: this.belediyeForm.resourceId,
                                    limit: this.belediyeForm.limit,
                                }),
                            });
                            this.belediyeResult = await response.json();
                        } catch (error) {
                            console.error('Belediye veri hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.belediye = false;
                        }
                    },

                    async fetchMuglaData() {
                        this.loading.mugla = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.mugla-data') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    type: this.belediyeForm.muglaType,
                                    id: this.belediyeForm.muglaId || null,
                                }),
                            });
                            this.muglaResult = await response.json();
                        } catch (error) {
                            console.error('Muğla veri hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.mugla = false;
                        }
                    },

                    async fetchImarPlani() {
                        this.loading.imar = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.imar-plani') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(this.imarForm),
                            });
                            this.imarResult = await response.json();
                        } catch (error) {
                            console.error('İmar planı hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.imar = false;
                        }
                    },

                    async checkImarUygunlugu() {
                        this.loading.imarUygunluk = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.imar-uygunluk') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(this.imarForm),
                            });
                            this.imarUygunlukResult = await response.json();
                        } catch (error) {
                            console.error('İmar uygunluk hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.imarUygunluk = false;
                        }
                    },

                    async fetchHavaKalitesi() {
                        this.loading.havaKalitesi = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.hava-kalitesi') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(this.cevreForm),
                            });
                            this.havaKalitesiResult = await response.json();
                        } catch (error) {
                            console.error('Hava kalitesi hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.havaKalitesi = false;
                        }
                    },

                    async fetchCevreSkoru() {
                        this.loading.cevreSkoru = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.cevre-skoru') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(this.cevreForm),
                            });
                            this.cevreSkoruResult = await response.json();
                        } catch (error) {
                            console.error('Çevre skoru hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.cevreSkoru = false;
                        }
                    },

                    async fetchYakinCevre() {
                        this.loading.yakinCevre = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.yakin-cevre') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(this.cevreForm),
                            });
                            this.yakinCevreResult = await response.json();
                        } catch (error) {
                            console.error('Yakın çevre hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.yakinCevre = false;
                        }
                    },

                    getAqiColor(aqi) {
                        if (!aqi) return 'text-gray-500';
                        if (aqi <= 50) return 'text-green-600';
                        if (aqi <= 100) return 'text-yellow-600';
                        if (aqi <= 150) return 'text-orange-600';
                        if (aqi <= 200) return 'text-red-600';
                        return 'text-purple-600';
                    },

                    getSkorColor(skor) {
                        if (!skor) return 'text-gray-500';
                        if (skor >= 80) return 'text-green-600';
                        if (skor >= 60) return 'text-yellow-600';
                        if (skor >= 40) return 'text-orange-600';
                        return 'text-red-600';
                    },

                    async predictRentalPrice() {
                        this.loading.kiraTahmini = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.kira-tahmini') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify(this.kiraForm),
                            });
                            this.kiraTahminiResult = await response.json();
                        } catch (error) {
                            console.error('Kira tahmini hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.kiraTahmini = false;
                        }
                    },

                    async predictYazlikRental() {
                        this.loading.yazlikKira = true;
                        try {
                            const response = await fetch('{{ route('admin.demo.belediye-veri.yazlik-kira-tahmini') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                },
                                body: JSON.stringify({
                                    ...this.kiraForm,
                                    season: 'yaz',
                                }),
                            });
                            this.yazlikKiraResult = await response.json();
                        } catch (error) {
                            console.error('Yazlık kira tahmini hatası:', error);
                            alert('Hata: ' + error.message);
                        } finally {
                            this.loading.yazlikKira = false;
                        }
                    },

                    formatPrice(price) {
                        return new Intl.NumberFormat('tr-TR', {
                            style: 'currency',
                            currency: 'TRY',
                            minimumFractionDigits: 0,
                            maximumFractionDigits: 0,
                        }).format(price);
                    },
                };
            }
        </script>
    @endpush
@endsection
