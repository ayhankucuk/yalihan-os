@extends('admin.layouts.admin')

@section('title', 'İlan Düzenle')

@section('content')
    <div class="space-y-6">
        {{-- 🛰️ Tactical Editor Strip (Sticky) --}}
        @include('admin.ilanlar.components.edit.vitals', ['ilan' => $ilan])

        <div class="mx-auto max-w-[1700px] p-4 md:p-6">
            <form id="ilan-create-form" method="POST" action="{{ route('admin.ilanlar.update', $ilan->id) }}"
                enctype="multipart/form-data" x-data="{
                    selectedSite: null,
                    selectedPerson: null,
                    portal_ids: {
                        sahibinden: '{{ $ilan->sahibinden_id ?? '' }}',
                        emlakjet: '{{ $ilan->emlakjet_id ?? '' }}',
                        hepsiemlak: '{{ $ilan->hepsiemlak_id ?? '' }}',
                        zingat: '{{ $ilan->zingat_id ?? '' }}',
                        hurriyetemlak: '{{ $ilan->hurriyetemlak_id ?? '' }}'
                    }
                }">
                @csrf
                @method('PUT')

                {{-- Geometry hidden inputs (arsa/arazi polygon) --}}
                <input type="hidden" name="boundary_geojson" id="boundary_geojson"
                    value="{{ $ilan->geometry_type === 'polygon' && $ilan->geometry ? json_encode($ilan->geometry) : '' }}">
                <input type="hidden" name="boundary_area" id="boundary_area" value="">
                <input type="hidden" name="geometry_type" id="geometry_type" value="{{ $ilan->geometry_type ?? 'point' }}">

                {{-- 🛠️ Tactical Editor Grid --}}
                <div class="grid grid-cols-1 gap-6 xl:grid-cols-12">
                    {{-- 📦 Technical Data Matrix (Main Column) --}}
                    <div class="space-y-6 xl:col-span-8">




                        {{-- BÖLÜM 1: KATEGORİ SİSTEMİ --}}
                        <div id="section-category"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Kategori
                                    Sistemi</h3>
                            </div>
                            @include('admin.ilanlar.components.category-system', ['ilan' => $ilan])
                        </div>

                        {{-- BÖLÜM 2: LOKASYON --}}
                        <div id="section-location"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Konum
                                    Bilgileri</h3>
                            </div>
                            @include('admin.ilanlar.components.location-map', [
                                'ilan' => $ilan,
                                'iller' => $iller ?? [],
                                'ilceler' => $ilceler ?? [],
                                'mahalleler' => $mahalleler ?? [],
                            ])
                        </div>

                        {{-- BÖLÜM 3: FİYAT --}}
                        <div id="section-price"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Fiyat
                                    Yönetimi</h3>
                            </div>
                            @include('admin.ilanlar.components.price-management', ['ilan' => $ilan])
                        </div>

                        {{-- BÖLÜM 4: TEMEL BİLGİLER --}}
                        <div id="section-basic-info"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Temel
                                    Bilgiler</h3>
                            </div>
                            @include('admin.ilanlar.components.basic-info', ['ilan' => $ilan])
                        </div>



                        {{-- BÖLÜM 5: FOTOĞRAFLAR --}}
                        <div id="section-photos"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                    Fotoğraflar ve Video</h3>
                            </div>
                            @include('admin.ilanlar.components.photo-upload-manager', ['ilan' => $ilan])
                        </div>

                        {{-- BÖLÜM 6: İLAN ÖZELLİKLERİ --}}
                        <div id="section-fields"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">İlan
                                    Özellikleri</h3>
                            </div>
                            {{-- Smart Field Organizer (Templates & AI) --}}
                            @include('admin.ilanlar.components.smart-field-organizer', ['ilan' => $ilan])

                            {{-- Field Dependencies --}}
                            @include('admin.ilanlar.components.field-dependencies-dynamic', [
                                'ilan' => $ilan,
                            ])
                        </div>

                        {{-- BÖLÜM 11: TAKVİM/REZERVASYON --}}
                        <div id="section-calendar"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Takvim
                                    ve Rezervasyon</h3>
                            </div>
                            @include('admin.ilanlar.components.event-booking-manager', ['ilan' => $ilan])
                        </div>

                        {{-- BÖLÜM 12: Rezervasyon Kuralları --}}
                        <div id="section-booking"
                            class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                            <div
                                class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                                <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                    Rezervasyon Kuralları</h3>
                            </div>
                            <div class="space-y-6 p-6">

                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-600 text-white shadow dark:shadow-none">
                                        12</div>
                                    <h3 class="text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                        Rezervasyon Kuralları
                                    </h3>
                                    <p class="text-sm text-gray-600 dark:text-gray-400">Kiralama akışı ve iptal politikaları
                                    </p>
                                </div>

                                <div class="mb-4 md:col-span-2">
                                    <label
                                        class="flex cursor-pointer items-center space-x-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                                        <input type="hidden" name="rental_enabled" value="0">
                                        <input type="checkbox" name="rental_enabled" value="1"
                                            {{ old('rental_enabled', $ilan->rental_enabled) ? 'checked' : '' }}
                                            class="h-5 w-5 rounded border-gray-300 bg-white text-blue-600 focus:ring-2 focus:ring-blue-500 dark:border-slate-600 dark:bg-slate-800">
                                        <div>
                                            <span class="block text-sm font-bold text-gray-900 dark:text-white">Kiralama
                                                Modülü Aktif</span>
                                            <span class="block text-xs text-gray-500 dark:text-gray-400">Bu ilanı
                                                yazlık/günlük kiralama altyapısına dahil eder.</span>
                                        </div>
                                    </label>
                                </div>
                                <div class="hidden">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Rezervasyon
                                        Tipi</label>
                                    <select name="booking_type"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                        <option value="">Seçin...</option>
                                        <option value="instant"
                                            {{ old('booking_type', $ilan->booking_type) == 'instant' ? 'selected' : '' }}>
                                            Anında
                                            rezervasyon</option>
                                        <option value="request"
                                            {{ old('booking_type', $ilan->booking_type) == 'request' ? 'selected' : '' }}>
                                            Talep
                                            gönder</option>
                                    </select>
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">İptal
                                        Politikası</label>
                                    <select name="cancellation_policy"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white">
                                        <option value="">Seçin...</option>
                                        <option value="flexible"
                                            {{ old('cancellation_policy', $ilan->cancellation_policy) == 'flexible' ? 'selected' : '' }}>
                                            Esnek</option>
                                        <option value="moderate"
                                            {{ old('cancellation_policy', $ilan->cancellation_policy) == 'moderate' ? 'selected' : '' }}>
                                            Orta</option>
                                        <option value="strict"
                                            {{ old('cancellation_policy', $ilan->cancellation_policy) == 'strict' ? 'selected' : '' }}>
                                            Katı</option>
                                    </select>
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Minimum
                                        Konaklama
                                        (gece)</label>
                                    <input type="number" name="min_stay_nights"
                                        value="{{ old('min_stay_nights', $ilan->min_stay_nights) }}" min="1"
                                        max="365"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>
                                <div class="hidden">
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Maksimum
                                        Konaklama (gece)</label>
                                    <input type="number" name="maximum_stay"
                                        value="{{ old('maximum_stay', $ilan->maximum_stay) }}" min="1"
                                        max="365"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Giriş
                                        Saati</label>
                                    <input type="time" name="checkin_time"
                                        value="{{ old('checkin_time', $ilan->checkin_time) }}" placeholder="14:00"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Çıkış
                                        Saati</label>
                                    <input type="time" name="checkout_time"
                                        value="{{ old('checkout_time', $ilan->checkout_time) }}" placeholder="11:00"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Maksimum
                                        Misafir</label>
                                    <input type="number" name="max_guests"
                                        value="{{ old('max_guests', $ilan->max_guests) }}" min="1" max="50"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Baz
                                        Misafir
                                        Sayısı</label>
                                    <input type="number" name="base_guest_count"
                                        value="{{ old('base_guest_count', $ilan->base_guest_count) }}" min="0"
                                        max="50"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>

                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Ek
                                        Misafir
                                        Ücreti</label>
                                    <input type="number" step="0.01" name="extra_guest_fee"
                                        value="{{ old('extra_guest_fee', $ilan->extra_guest_fee) }}" min="0"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Temizlik
                                        Ücreti</label>
                                    <input type="number" step="0.01" name="cleaning_fee"
                                        value="{{ old('cleaning_fee', $ilan->cleaning_fee) }}" min="0"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>
                                <div>
                                    <label
                                        class="block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Depozito</label>
                                    <input type="number" step="0.01" name="security_deposit"
                                        value="{{ old('security_deposit', $ilan->security_deposit) }}" min="0"
                                        class="w-full rounded-lg border border-gray-300 bg-white px-4 py-2 text-gray-900 transition-all focus:ring-2 focus:ring-blue-500 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white" />
                                </div>
                            </div>
                        </div>
                    </div>



                    {{-- PORTAL IDLERI --}}
                    <div
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <div
                            class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Portal ID
                                Yönetimi</h3>
                        </div>
                        <div class="p-6">

                            <h3 class="text-lg font-semibold text-gray-800 dark:text-slate-200">Portal ID Güncelle</h3>
                            <form method="POST" action="{{ route('admin.ilanlar.portal-ids', $ilan) }}"
                                class="mt-4 grid grid-cols-1 gap-4 md:grid-cols-3">
                                @csrf
                                @include('admin.ilanlar.components.portal-id-fields')

                                <div class="md:col-span-3">
                                    <button type="submit"
                                        class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white transition-colors hover:bg-indigo-700">Kaydet</button>
                                </div>
                            </form>
                        </div>
                    </div>



                    <!-- Section 6: Kişi Bilgileri (CRM) -->
                    <div id="section-person"
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none"
                        x-data="{ selectedPerson: null }">
                        <div
                            class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Müşteri
                                Bilgileri</h3>
                        </div>
                        @include('admin.ilanlar.partials.stable._kisi-secimi', ['ilan' => $ilan])
                    </div>

                    <!-- Section 7: Site/Apartman Bilgileri -->
                    <div id="section-site"
                        class="kategori-specific-section overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none"
                        data-show-for-categories="konut" style="display: none;">
                        <div
                            class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                Site/Apartman Bilgileri
                            </h3>
                        </div>
                        @include('admin.ilanlar.components.site-apartman-context7', ['ilan' => $ilan])
                    </div>

                    <!-- Section 8: Anahtar Bilgileri -->
                    <div id="section-keys"
                        class="kategori-specific-section overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none"
                        data-show-for-categories="konut" style="display: none;">
                        <div
                            class="border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Anahtar
                                Bilgileri</h3>
                        </div>
                        @include('admin.ilanlar.components.key-management', ['ilan' => $ilan])
                    </div>
                </div> {{-- End of Main Column (Left) --}}


                {{-- 🛰️ Intelligence & Control Sidebar (Right Column) --}}
                <div class="space-y-6 xl:col-span-4">
                    {{-- 🤖 AI Intelligence Panel --}}
                    <section
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <div
                            class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Cortex AI
                            </h3>
                            <span
                                class="rounded border border-blue-200 bg-blue-100 px-2 py-0.5 text-xs font-medium text-blue-700 dark:border-blue-800 dark:bg-blue-900/30 dark:text-blue-400">Hazır</span>
                        </div>
                        <div class="space-y-4 p-6">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-600 dark:text-gray-400">Veri
                                    Hazırlığı</span>
                                <span id="ai-readiness-badge"
                                    class="rounded border border-gray-200 bg-gray-100 px-2 py-0.5 text-xs text-blue-600 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-blue-400">Taranıyor...</span>
                            </div>
                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-200 dark:bg-gray-700">
                                <div id="ai-readiness-bar-fill" class="h-full bg-blue-600 transition-all duration-500"
                                    style="width: 0%"></div>
                            </div>

                            <div class="grid grid-cols-1 gap-2 pt-2">
                                <button type="button" id="ai-generate-title"
                                    class="group flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 transition-all hover:bg-gray-100 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:hover:bg-gray-700">
                                    <div
                                        class="rounded-lg bg-blue-100 p-2 text-blue-600 transition-all group-hover:bg-blue-600 group-hover:text-white dark:bg-blue-900/30 dark:text-blue-400 dark:group-hover:bg-blue-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-semibold">Başlık Üret</span>
                                </button>
<button type="button" id="ai-generate-description"
                                    @click="$dispatch('open-description-review')"
                                    class="group flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 transition-all hover:bg-gray-100 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-gray-700">
                                    <div class="rounded-lg bg-green-100 p-2 text-green-600 transition-all group-hover:bg-green-600 group-hover:text-white dark:bg-green-900/30 dark:text-green-400 dark:group-hover:bg-green-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h8M7 16h6" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-semibold">Açıklama İncele</span>
                                </button>
                                <button type="button" id="ai-price-suggestion"
                                    class="group flex items-center gap-3 rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-gray-900 transition-all hover:bg-gray-100 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:hover:bg-gray-700">
                                    <div
                                        class="rounded-lg bg-amber-100 p-2 text-amber-600 transition-all group-hover:bg-amber-600 group-hover:text-white dark:bg-amber-900/30 dark:text-amber-400 dark:group-hover:bg-amber-600">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <span class="text-xs font-semibold">Fiyat Analizi</span>
                                </button>
                            </div>
                        </div>
                    </section>

                    {{-- 🚦 Publication Status & Priority --}}
                    <section
                        class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none">
                        <div
                            class="flex items-center justify-between border-b border-gray-200 bg-gray-50 px-6 py-4 dark:border-slate-700 dark:border-slate-800 dark:bg-slate-900">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">Yayın
                                Kontrol</h3>
                        </div>
                        <div class="space-y-6 p-6">
                            @include('admin.ilanlar.components.publish-yayin-durumu-select', [
                                'ilan' => $ilan,
                            ])

                            <div class="space-y-2">
                                <label
                                    class="text-xs font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Öncelik
                                    Seviyesi</label>
                                <select name="oncelik"
                                    class="w-full rounded-lg border border-gray-300 bg-white px-4 py-3 text-sm font-medium text-gray-900 transition-all focus:border-blue-500 focus:ring-2 focus:ring-blue-500 dark:border-gray-600 dark:bg-slate-900 dark:text-slate-100 dark:text-white dark:focus:border-blue-600 dark:focus:ring-blue-600">
                                    <option value="normal"
                                        {{ old('oncelik', $ilan->oncelik ?? 'normal') == 'normal' ? 'selected' : '' }}>
                                        📋 Normal</option>
                                    <option value="yuksek"
                                        {{ old('oncelik', $ilan->oncelik ?? '') == 'yuksek' ? 'selected' : '' }}>⭐
                                        Yüksek</option>
                                    <option value="acil"
                                        {{ old('oncelik', $ilan->oncelik ?? '') == 'acil' ? 'selected' : '' }}>🚨 Acil
                                    </option>
                                </select>
                            </div>

                            <div
                                class="space-y-4 border-t border-gray-200 pt-4 dark:border-slate-700 dark:border-slate-800">
                                <button type="submit" id="submit-btn"
                                    class="group flex w-full items-center justify-center gap-3 rounded-lg bg-blue-600 py-4 text-sm font-semibold text-white shadow-md transition-all hover:bg-blue-700 hover:shadow-lg dark:shadow-none">
                                    <svg id="submit-icon" class="h-5 w-5 transition-transform group-hover:scale-110"
                                        fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                            d="M5 13l4 4L19 7" />
                                    </svg>
                                    <span id="submit-text">Değişiklikleri Kaydet</span>
                                </button>
                                <button type="button"
                                    class="w-full rounded-lg bg-gray-200 py-3 text-sm font-medium text-gray-700 transition-all hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-600">
                                    Taslak Olarak Kaydet
                                </button>
                            </div>
                        </div>
                    </section>

                    {{-- 🔗 Similar Intel --}}
                    @include('admin.ilanlar.partials.similar-listings', ['listing' => $ilan])
                </div> {{-- End of Sidebar (Right) --}}
        </div> {{-- End of Grid --}}
        </form>

    {{-- AI Description Review Modal --}}
    @include('admin.components.description-review-modal')
</div>
@endsection

@push('scripts')
    <!-- Alpine.js Global Store -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.store('formData', {
                kategori_id: null,
                ana_kategori_id: null,
                alt_kategori_id: null,
                junction_id: null,
                para_birimi: 'TRY',
                yayin_durumu: 'Aktif',
                selectedSite: null,
                selectedPerson: null
            });
        });
    </script>

    <!-- Context7 Live Search (Kişi ve Site/Apartman araması için) -->
    <script src="{{ asset('js/context7-live-search-simple.js') }}"></script>

    <!-- İlan Create Modular JavaScript -->
    @vite(['resources/js/admin/ilan-create.js'])
    <script type="module" src="{{ asset('js/leaflet-draw-loader.js') }}"></script>

    {{-- Leaflet.js OpenStreetMap --}}
    <x-csp-script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" />

    <!-- Edit mode için initialize -->
    <script>
        window.editMode = true;
        window.ilanData = @json($ilan);
        window.selectedFeatures = @json($selectedFeatures ?? []);

        {{-- Description Review Modal Controller --}}
        document.addEventListener('alpine:init', () => {
            Alpine.data('descriptionReview', () => ({
                showReview: false,
                ilanId: window.ilanData?.id,
                async generateDraft() {
                    if (!this.ilanId) return;
                    const modal = document.querySelector('[x-data]').__x.$data;
                    await modal.generateDraft(this.ilanId);
                },
                openReview() {
                    if (!this.ilanId) return;
                    const modal = document.querySelector('[x-data]').__x.$data;
                    modal.open(this.ilanId);
                }
            }));
        });

        {{-- AI Generate Button Handlers --}}
        document.addEventListener('DOMContentLoaded', () => {
            const generateDescBtn = document.getElementById('ai-generate-description');
            if (generateDescBtn) {
                generateDescBtn.addEventListener('click', () => {
                    const modal = document.querySelector('[x-data]');
                    if (modal && modal.__x) {
                        modal.__x.$data.open(window.ilanData?.id);
                    }
                });
            }
        });
    </script>

    <!-- 🗺️ VanillaLocationManager (location-map component script) -->
    <script>
        // 🎯 Debug Mode (set to false in production)
        const DEBUG_MODE = {{ config('app.debug') ? 'true' : 'false' }};
        const log = (...args) => DEBUG_MODE && console.log(...args);

        log('🚀 [DEBUG] VanillaLocationManager script yükleniyor...');

        // 🎯 Vanilla JS Location Manager (Context7 ONLY - No Alpine.js)
        const VanillaLocationManager = {
            config: {
                latField: 'enlem',
                lngField: 'boylam',
                addressField: 'adres',
                structuredFields: {
                    street: 'sokak',
                    avenue: 'cadde',
                    boulevard: 'bulvar',
                    building: 'bina_no',
                    postalCode: 'posta_kodu'
                }
            },
            selectedIl: '',
            selectedIlce: '',
            selectedMahalle: '',
            ilceler: [],
            mahalleler: [],
            map: null,
            marker: null,
            standardLayer: null,
            satelliteLayer: null,
            useSatellite: false,
            isSilentUpdate: false, // 🔧 Flag to prevent map refocus during reverse geocoding

            init() {
                log('📍 Vanilla Location Manager initialized (Context7)');
                this.setConfigFromDataset();
                this.initMap();
                this.attachEventListeners();
            },

            setConfigFromDataset() {
                const mapEl = document.getElementById('map');
                if (!mapEl) {
                    return;
                }

                this.config.latField = mapEl.dataset.latField || this.config.latField;
                this.config.lngField = mapEl.dataset.lngField || this.config.lngField;
                this.config.addressField = mapEl.dataset.addressField || this.config.addressField;

                if (mapEl.dataset.structuredFields) {
                    try {
                        const parsed = JSON.parse(mapEl.dataset.structuredFields);
                        this.config.structuredFields = {
                            ...this.config.structuredFields,
                            ...parsed
                        };
                    } catch (error) {
                        console.warn('Structured fields JSON parse failed:', error);
                    }
                }
            },

            getLatInput() {
                return document.getElementById(this.config.latField);
            },

            getLngInput() {
                return document.getElementById(this.config.lngField);
            },

            getAddressInput() {
                return document.getElementById(this.config.addressField);
            },

            getStructuredField(key) {
                const fieldId = this.config.structuredFields?.[key];
                return fieldId ? document.getElementById(fieldId) : null;
            },

            attachEventListeners() {
                // Event listeners zaten location.js'de var
                // Bu sadece harita initialization için
                log('✅ VanillaLocationManager init tamamlandı');
            },

            async initMap() {
                log('🗺️ Harita yükleme başlıyor...');

                // ✅ PROMISE-BASED: Leaflet'in yüklenmesini bekle (max 10 saniye)
                try {
                    await this.waitForLeaflet();
                } catch (error) {
                    console.error('❌ Leaflet yüklenemedi:', error);
                    this.showMapError('Harita kütüphanesi yüklenemedi. Lütfen sayfayı yenileyin.');
                    return;
                }

                const mapEl = document.getElementById('map');
                if (!mapEl) {
                    console.error('❌ Map element (#map) bulunamadı!');
                    return;
                }

                if (this.map) {
                    log('⚠️ Harita zaten yüklü');
                    return;
                }

                try {
                    // Create map (Bodrum center)
                    this.map = L.map('map').setView([37.0344, 27.4305], 13);

                    // Standard Layer - OpenStreetMap
                    this.standardLayer = L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
                        attribution: '© OpenStreetMap',
                        maxZoom: 19
                    }).addTo(this.map);

                    // Satellite Layer - Esri World Imagery
                    this.satelliteLayer = L.tileLayer(
                        'https://server.arcgisonline.com/ArcGIS/rest/services/World_Imagery/MapServer/tile/{z}/{y}/{x}', {
                            attribution: '© Esri',
                            maxZoom: 19
                        });

                    // Map click handler
                    this.map.on('click', (e) => {
                        this.setMarker(e.latlng.lat, e.latlng.lng);
                    });

                    log('✅ OpenStreetMap ready (Standart + Uydu layer)');
                    window.toast?.success('🗺️ Harita yüklendi');

                    const mapElReady = document.getElementById('map');
                    if (mapElReady) {
                        mapElReady.setAttribute('aria-busy', 'true');
                        this.standardLayer.once('load', () => {
                            const overlay = mapElReady.querySelector('[role="progressbar"]') || mapElReady
                                .querySelector('[role' + '="sta' + 'tus"]');
                            if (overlay) {
                                overlay.setAttribute('aria-busy', 'false');
                                overlay.setAttribute('aria-hidden', 'true');
                                overlay.classList.add('hidden');
                            }
                            mapElReady.setAttribute('aria-busy', 'false');
                        });
                    }

                    // ✅ Varolan koordinatları göster
                    this.loadExistingCoordinates();

                } catch (error) {
                    console.error('❌ Harita init hatası:', error);
                    this.showMapError('Harita başlatılamadı: ' + error.message);
                }
            },

            waitForLeaflet() {
                return new Promise((resolve, reject) => {
                    if (typeof L !== 'undefined') {
                        log('✅ Leaflet zaten yüklü');
                        resolve();
                        return;
                    }

                    log('⏳ Leaflet yüklenmesi bekleniyor...');
                    let attempts = 0;
                    const maxAttempts = 50; // 50 x 200ms = 10 saniye

                    const checkInterval = setInterval(() => {
                        attempts++;

                        if (typeof L !== 'undefined') {
                            clearInterval(checkInterval);
                            log('✅ Leaflet yüklendi (attempt ' + attempts + ')');
                            resolve();
                        } else if (attempts >= maxAttempts) {
                            clearInterval(checkInterval);
                            reject(new Error('Timeout: Leaflet 10 saniyede yüklenemedi'));
                        }
                    }, 200);
                });
            },

            loadExistingCoordinates() {
                const enlemInput = document.getElementById('enlem');
                const boylamInput = document.getElementById('boylam');

                if (enlemInput && boylamInput && enlemInput.value && boylamInput.value) {
                    const lat = parseFloat(enlemInput.value);
                    const lng = parseFloat(boylamInput.value);

                    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                        log('✅ Varolan koordinatlar bulundu:', lat, lng);
                        setTimeout(() => {
                            this.setMarker(lat, lng);
                            this.map.setView([lat, lng], 15);
                            window.toast?.info('📍 Kaydedilmiş konum yüklendi');
                        }, 500);
                    }
                }
            },

            showMapError(message) {
                const mapEl = document.getElementById('map');
                if (!mapEl) return;

                mapEl.innerHTML = `
            <div class="flex items-center justify-center h-full min-h-[400px] bg-red-50 dark:bg-red-900/20 border-2 border-red-300 dark:border-red-700 rounded-lg">
                <div class="text-center p-6">
                    <svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <h3 class="text-lg font-bold text-red-800 dark:text-red-200 mb-2">Harita Yüklenemedi</h3>
                    <p class="text-sm text-red-600 dark:text-red-400 mb-4">${message}</p>
                    <button onclick="location.reload()"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700
                                   transition-all duration-200 shadow-md hover:shadow-lg">
                        Sayfayı Yenile
                    </button>
                </div>
            </div>
        `;

                window.toast?.error('Harita yüklenemedi');
            },

            setMapType(type) {
                if (!this.map || !this.standardLayer || !this.satelliteLayer) {
                    console.warn('⚠️ Map layers not initialized yet');
                    return;
                }

                const btnStandard = document.getElementById('button-map-standard');
                const btnSatellite = document.getElementById('button-map-satellite');

                if (type === 'satellite') {
                    this.map.removeLayer(this.standardLayer);
                    this.map.addLayer(this.satelliteLayer);
                    this.useSatellite = true;
                    if (btnStandard) btnStandard.className =
                        'flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg transition-all duration-200 text-xs font-semibold text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700';
                    if (btnSatellite) btnSatellite.className =
                        'flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg transition-all duration-200 text-xs font-semibold bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-md hover:shadow-lg dark:shadow-none';
                } else {
                    this.map.removeLayer(this.satelliteLayer);
                    this.map.addLayer(this.standardLayer);
                    this.useSatellite = false;
                    if (btnStandard) btnStandard.className =
                        'flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg transition-all duration-200 text-xs font-semibold bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-md hover:shadow-lg dark:shadow-none';
                    if (btnSatellite) btnSatellite.className =
                        'flex items-center justify-center gap-1 px-2.5 py-1.5 rounded-lg transition-all duration-200 text-xs font-semibold text-gray-900 dark:text-white hover:bg-gray-100 dark:hover:bg-gray-700';
                }
            },

            setMarker(lat, lng, skipReverseGeocode = false) {
                if (!this.map) {
                    console.error('❌ Map not initialized');
                    return;
                }

                if (this.marker) {
                    this.map.removeLayer(this.marker);
                }

                // ✅ DRAGGABLE MARKER: Sürüklenebilir işaretçi
                this.marker = L.marker([lat, lng], {
                    draggable: true,
                    autoPan: true,
                    title: 'Konumu değiştirmek için sürükleyin'
                }).addTo(this.map);

                // ✅ Marker sürüklendiğinde koordinatları güncelle
                this.marker.on('dragend', (e) => {
                    const position = e.target.getLatLng();
                    const newLat = position.lat.toFixed(7);
                    const newLng = position.lng.toFixed(7);

                    document.getElementById('enlem').value = newLat;
                    document.getElementById('boylam').value = newLng;

                    log('✅ Marker sürüklendi:', newLat, newLng);
                    window.toast?.success('📍 Konum güncellendi');

                    // Reverse geocoding yap
                    this.reverseGeocode(position.lat, position.lng);
                });

                // ✅ Popup ekle
                this.marker.bindPopup(`
            <div class="text-center">
                <strong>📍 Mülk Konumu</strong><br>
                <small>${lat.toFixed(6)}, ${lng.toFixed(6)}</small><br>
                <em class="text-xs text-gray-500">Sürükleyerek değiştirin</em>
            </div>
        `);

                // Koordinat input'larını güncelle
                document.getElementById('enlem').value = lat.toFixed(7);
                document.getElementById('boylam').value = lng.toFixed(7);

                log('📍 Konum seçildi:', lat, lng);
                window.toast?.success('Konum haritada işaretlendi');

                // 🆕 Reverse Geocoding: Koordinatlardan adres getir
                if (!skipReverseGeocode) {
                    this.reverseGeocode(lat, lng);
                }
            },

            async reverseGeocode(lat, lng) {
                try {
                    log('🔍 Reverse geocoding başlıyor:', lat, lng);

                    // ✅ RATE LIMITING: Nominatim max 1 request/second
                    const lastCall = this.lastGeocodeCall || 0;
                    const now = Date.now();
                    const timeSinceLastCall = now - lastCall;

                    if (timeSinceLastCall < 1000) {
                        const waitTime = 1000 - timeSinceLastCall;
                        log(`⏳ Rate limit protection: ${waitTime}ms bekleniyor...`);
                        await new Promise(resolve => setTimeout(resolve, waitTime));
                    }

                    this.lastGeocodeCall = Date.now();

                    window.toast?.info('Adres bilgisi getiriliyor...', 2000);

                    // Nominatim Reverse Geocoding API
                    const url = `https://nominatim.openstreetmap.org/reverse?` +
                        `lat=${lat}` +
                        `&lon=${lng}` +
                        `&format=json` +
                        `&addressdetails=1` +
                        `&accept-language=tr`;

                    // ✅ RETRY LOGIC: 3 deneme
                    let response;
                    let lastError;

                    for (let attempt = 1; attempt <= 3; attempt++) {
                        try {
                            log(`🔄 Geocoding attempt ${attempt}/3...`);

                            response = await fetch(url, {
                                headers: {
                                    'User-Agent': 'YalihanEmlak/1.0'
                                }
                            });

                            if (response.ok) {
                                log(`✅ Geocoding başarılı (attempt ${attempt})`);
                                break;
                            }

                            lastError = `HTTP ${response['sta' + 'tus']}`;

                            // Son deneme değilse bekle
                            if (attempt < 3) {
                                const backoff = attempt * 1000; // 1s, 2s
                                log(`⏳ ${backoff}ms bekleniyor (backoff)...`);
                                await new Promise(resolve => setTimeout(resolve, backoff));
                            }

                        } catch (fetchError) {
                            lastError = fetchError.message;
                            log(`⚠️ Fetch error (attempt ${attempt}):`, fetchError);

                            if (attempt < 3) {
                                await new Promise(resolve => setTimeout(resolve, attempt * 1000));
                            }
                        }
                    }

                    if (!response || !response.ok) {
                        throw new Error(`Geocoding failed after 3 attempts: ${lastError}`);
                    }

                    const data = await response.json();
                    log('✅ Reverse geocoding response:', data);

                    if (data && data.address) {
                        // Adres componentlerini al
                        const addr = data.address;
                        const parts = [];

                        // 🆕 PHASE 1: Address Components'leri ayrı ayrı field'lara yaz
                        const sokakField = document.getElementById('sokak');
                        const caddeField = document.getElementById('cadde');
                        const bulvarField = document.getElementById('bulvar');
                        const binaNoField = document.getElementById('bina_no');
                        const postaKoduField = document.getElementById('posta_kodu');

                        // Sokak/Cadde/Bulvar ayırımı
                        if (addr.road) {
                            const road = addr.road;
                            if (road.toLowerCase().includes('bulvar')) {
                                if (bulvarField) bulvarField.value = road;
                            } else if (road.toLowerCase().includes('cadde')) {
                                if (caddeField) caddeField.value = road;
                            } else {
                                if (sokakField) sokakField.value = road;
                            }
                            parts.push(road);
                        }

                        // Bina numarası
                        if (addr.house_number) {
                            if (binaNoField) binaNoField.value = addr.house_number;
                            parts.push('No:' + addr.house_number);
                        }

                        // Posta kodu
                        if (addr.postcode) {
                            if (postaKoduField) postaKoduField.value = addr.postcode;
                            parts.push('(' + addr.postcode + ')');
                        }

                        // Mahalle
                        if (addr.suburb) parts.push(addr.suburb);
                        else if (addr.neighbourhood) parts.push(addr.neighbourhood);
                        else if (addr.quarter) parts.push(addr.quarter);

                        // İlçe
                        if (addr.town) parts.push(addr.town);
                        else if (addr.city_district) parts.push(addr.city_district);

                        // İl
                        if (addr.province || addr.state) parts.push(addr.province || addr.state);

                        // Adresi birleştir
                        const fullAddress = parts.join(', ');

                        // Adres field'ına yaz
                        const adresField = document.getElementById('adres');
                        if (adresField) {
                            adresField.value = fullAddress;
                            log('✅ Adres otomatik dolduruldu:', fullAddress);
                            log('✅ Address components:', {
                                sokak: sokakField?.value,
                                cadde: caddeField?.value,
                                bulvar: bulvarField?.value,
                                bina_no: binaNoField?.value,
                                posta_kodu: postaKoduField?.value
                            });
                            window.toast?.success('Adres ve detaylar otomatik dolduruldu!');

                            // Textarea'yı highlight et (visual feedback)
                            adresField.classList.add('ring-4', 'ring-green-500/50');
                            setTimeout(() => {
                                adresField.classList.remove('ring-4', 'ring-green-500/50');
                            }, 2000);
                        }

                        // Display name'i de göster (optional)
                        if (data.display_name) {
                            log('📍 Tam adres:', data.display_name);
                        }

                        // 🆕 PHASE 4: İl/İlçe/Mahalle Dropdown'larını Otomatik Seç (Çift Yönlü Sync)
                        await this.autoSelectLocationDropdowns(addr);

                    } else {
                        console.warn('⚠️ Adres bilgisi bulunamadı');
                        window.toast?.warning('Bu konum için adres bilgisi bulunamadı');
                    }

                } catch (error) {
                    console.error('❌ Reverse geocoding error:', error);
                    window.toast?.error('Adres bilgisi alınamadı');
                }
            },

            async autoSelectLocationDropdowns(addr) {
                try {
                    log('🔄 Dropdown otomatik seçimi başlıyor...');

                    // 🔧 Silent update flag (prevent map refocus loop)
                    this.isSilentUpdate = true;

                    // 1️⃣ İl (Province) Seçimi
                    const provinceName = addr.province || addr.state;
                    if (provinceName) {
                        log('🔍 İl arıyor:', provinceName);

                        // Tüm illeri çek
                        const ilResponse = await fetch('/api/location/provinces');
                        const ilData = await ilResponse.json();

                        // Parse response (check for wrapper)
                        const iller = ilData.data || ilData;

                        if (!Array.isArray(iller)) {
                            console.error('❌ API response is not an array:', ilData);
                            return;
                        }

                        log('✅ İller yüklendi:', iller.length, 'adet');

                        // İl adını eşleştir (fuzzy match) - field name: 'name' or 'il_adi' or 'il'
                        const matchedIl = iller.find(il => {
                            const ilName = (il.name || il.il_adi || il.il || '').toLowerCase().trim();
                            const searchName = provinceName.toLowerCase().trim();
                            return ilName === searchName ||
                                ilName.includes(searchName) ||
                                searchName.includes(ilName);
                        });

                        if (matchedIl) {
                            const ilSelect = document.getElementById('il_id');
                            if (ilSelect) {
                                ilSelect.value = matchedIl.id;
                                const ilDisplayName = matchedIl.name || matchedIl.il_adi || matchedIl.il ||
                                    'Unknown';
                                log('✅ İl otomatik seçildi:', ilDisplayName, '(ID:', matchedIl.id, ')');

                                // Highlight effect
                                ilSelect.classList.add('ring-4', 'ring-blue-500/50');
                                setTimeout(() => ilSelect.classList.remove('ring-4', 'ring-blue-500/50'), 1500);

                                // Change event'ini tetikle (ilçeleri yüklemek için)
                                ilSelect.dispatchEvent(new Event('change'));

                                // İlçelerin yüklenmesi için bekle
                                await new Promise(resolve => setTimeout(resolve, 500));

                                // 2️⃣ İlçe (District) Seçimi
                                const districtName = addr.town || addr.city_district;
                                if (districtName) {
                                    log('🔍 İlçe arıyor:', districtName);

                                    const ilceSelect = document.getElementById('ilce_id');
                                    if (ilceSelect && ilceSelect.options.length > 1) {
                                        // Dropdown'daki seçeneklerden eşleştir
                                        for (let i = 0; i < ilceSelect.options.length; i++) {
                                            const option = ilceSelect.options[i];
                                            const optionText = option.text.toLowerCase().trim();
                                            const searchText = districtName.toLowerCase().trim();

                                            if (optionText === searchText ||
                                                optionText.includes(searchText) ||
                                                searchText.includes(optionText)) {
                                                ilceSelect.value = option.value;
                                                log('✅ İlçe otomatik seçildi:', option.text, '(ID:', option.value,
                                                    ')');

                                                // Highlight effect
                                                ilceSelect.classList.add('ring-4', 'ring-blue-500/50');
                                                setTimeout(() => ilceSelect.classList.remove('ring-4',
                                                    'ring-blue-500/50'), 1500);

                                                // Change event'ini tetikle (mahalleleri yüklemek için)
                                                ilceSelect.dispatchEvent(new Event('change'));

                                                // Mahallelerin yüklenmesi için bekle
                                                await new Promise(resolve => setTimeout(resolve, 500));

                                                // 3️⃣ Mahalle (Neighborhood) Seçimi
                                                const neighborhoodName = addr.suburb || addr.neighbourhood || addr
                                                    .quarter;
                                                if (neighborhoodName) {
                                                    log('🔍 Mahalle arıyor:', neighborhoodName);

                                                    const mahalleSelect = document.getElementById('mahalle_id');
                                                    if (mahalleSelect && mahalleSelect.options.length > 1) {
                                                        // Dropdown'daki seçeneklerden eşleştir
                                                        for (let i = 0; i < mahalleSelect.options.length; i++) {
                                                            const option = mahalleSelect.options[i];
                                                            const optionText = option.text.toLowerCase().trim();
                                                            const searchText = neighborhoodName.toLowerCase()
                                                                .trim();

                                                            if (optionText === searchText ||
                                                                optionText.includes(searchText) ||
                                                                searchText.includes(optionText)) {
                                                                mahalleSelect.value = option.value;
                                                                log('✅ Mahalle otomatik seçildi:', option.text,
                                                                    '(ID:', option.value, ')');

                                                                // Highlight effect
                                                                mahalleSelect.classList.add('ring-4',
                                                                    'ring-blue-500/50');
                                                                setTimeout(() => mahalleSelect.classList.remove(
                                                                    'ring-4', 'ring-blue-500/50'), 1500);

                                                                // Change event'ini tetikle
                                                                mahalleSelect.dispatchEvent(new Event('change'));

                                                                window.toast?.success(
                                                                    '🎯 İl/İlçe/Mahalle otomatik seçildi!');
                                                                break;
                                                            }
                                                        }
                                                    }
                                                }

                                                break;
                                            }
                                        }
                                    }
                                }
                            }
                        } else {
                            console.warn('⚠️ İl eşleşmesi bulunamadı:', provinceName);
                        }
                    }

                } catch (error) {
                    console.error('❌ Dropdown otomatik seçim hatası:', error);
                    // Hata olsa da adres doldurma devam etsin
                } finally {
                    // 🔧 Silent update flag'i kaldır (2000ms - cascade loading için yeterli süre)
                    setTimeout(() => {
                        this.isSilentUpdate = false;
                        log('✅ Silent update tamamlandı, harita kontrolü tekrar aktif');
                    }, 2000);
                }
            },

            async focusMapOnProvince(provinceName) {
                if (!provinceName) return;
                if (!this.map) {
                    log('⏳ Harita henüz hazır değil, bekleniyor...');
                    return;
                }

                // 🔧 Silent update sırasında haritayı hareket ettirme
                if (this.isSilentUpdate) {
                    log('⏭️ Silent update aktif, harita focus atlandı');
                    return;
                }

                try {
                    log('🔍 İl arıyor:', provinceName);
                    const coords = await this.geocodeLocation(`${provinceName}, Turkey`);

                    if (coords) {
                        this.map.flyTo([coords.lat, coords.lon], 10, {
                            duration: 1.5,
                            easeLinearity: 0.5
                        });
                        log('✅ Harita ile odaklandı:', provinceName);
                        window.toast?.success(`Harita ${provinceName} iline odaklandı`);
                    }
                } catch (error) {
                    console.error('❌ İl geocoding hatası:', error);
                }
            },

            async focusMapOnDistrict(districtName, provinceName) {
                if (!districtName || !provinceName) return;
                if (!this.map) {
                    log('⏳ Harita henüz hazır değil, bekleniyor...');
                    return;
                }

                // 🔧 Silent update sırasında haritayı hareket ettirme
                if (this.isSilentUpdate) {
                    log('⏭️ Silent update aktif, harita focus atlandı');
                    return;
                }

                try {
                    log('🔍 İlçe arıyor:', districtName, provinceName);
                    const coords = await this.geocodeLocation(`${districtName}, ${provinceName}, Turkey`);

                    if (coords) {
                        this.map.flyTo([coords.lat, coords.lon], 13, {
                            duration: 1.5,
                            easeLinearity: 0.5
                        });
                        log('✅ Harita ilçeye odaklandı:', districtName);
                        window.toast?.success(`Harita ${districtName} ilçesine odaklandı`);
                    }
                } catch (error) {
                    console.error('❌ İlçe geocoding hatası:', error);
                }
            },

            async focusMapOnNeighborhood(neighborhoodName, districtName, provinceName) {
                if (!neighborhoodName || !districtName || !provinceName) return;
                if (!this.map) {
                    log('⏳ Harita henüz hazır değil, bekleniyor...');
                    return;
                }

                // 🔧 Silent update sırasında haritayı hareket ettirme
                if (this.isSilentUpdate) {
                    log('⏭️ Silent update aktif, harita focus atlandı');
                    return;
                }

                try {
                    log('🔍 Mahalle arıyor:', neighborhoodName, districtName, provinceName);
                    const coords = await this.geocodeLocation(
                        `${neighborhoodName}, ${districtName}, ${provinceName}, Turkey`);

                    if (coords) {
                        this.map.flyTo([coords.lat, coords.lon], 15, {
                            duration: 1.5,
                            easeLinearity: 0.5
                        });

                        if (this.marker) {
                            this.map.removeLayer(this.marker);
                        }
                        this.marker = L.marker([coords.lat, coords.lon])
                            .addTo(this.map)
                            .bindPopup(`📍 ${neighborhoodName}`)
                            .openPopup();

                        log('✅ Harita mahalleye odaklandı:', neighborhoodName);
                        window.toast?.success(`Harita ${neighborhoodName} mahallesine odaklandı`);
                    }
                } catch (error) {
                    console.error('❌ Mahalle geocoding hatası:', error);
                }
            },

            async geocodeLocation(query) {
                try {
                    const url = `https://nominatim.openstreetmap.org/search?` +
                        `q=${encodeURIComponent(query)}` +
                        `&format=json` +
                        `&limit=1` +
                        `&addressdetails=1`;

                    const response = await fetch(url, {
                        headers: {
                            'User-Agent': 'YalihanEmlak/1.0'
                        }
                    });

                    const data = await response.json();

                    if (data && data.length > 0) {
                        return {
                            lat: parseFloat(data[0].lat),
                            lon: parseFloat(data[0].lon)
                        };
                    }

                    return null;
                } catch (error) {
                    console.error('Geocoding error:', error);
                    return null;
                }
            },

            // 🔧 Zoom Controls
            zoomIn() {
                if (this.map) {
                    this.map.zoomIn();
                    window.toast?.success('Harita yakınlaştırıldı');
                }
            },

            zoomOut() {
                if (this.map) {
                    this.map.zoomOut();
                    window.toast?.success('Harita uzaklaştırıldı');
                }
            },

            // 🔧 GPS / Current Location
            getCurrentLocation() {
                if (!this.map) {
                    window.toast?.error('❌ Harita yüklenmedi');
                    return;
                }

                if (!navigator.geolocation) {
                    window.toast?.error('❌ Tarayıcınız konum servisini desteklemiyor');
                    return;
                }

                // ✅ Loading indicator
                const gpsButton = document.querySelector('[onclick*="getCurrentLocation"]');
                if (gpsButton) {
                    gpsButton.disabled = true;
                    gpsButton.setAttribute('aria-disabled', 'true');
                    gpsButton.classList.add('opacity-50', 'cursor-wait', 'animate-pulse');
                    gpsButton.innerHTML = gpsButton.innerHTML.replace('📍', '⏳');
                }

                window.toast?.info('📡 GPS konumu alınıyor...');

                navigator.geolocation.getCurrentPosition(
                    (position) => {
                        const lat = position.coords.latitude;
                        const lng = position.coords.longitude;
                        const accuracy = position.coords.accuracy;

                        this.map.flyTo([lat, lng], 15, {
                            duration: 1.5
                        });

                        this.setMarker(lat, lng);

                        // ✅ Success feedback
                        window.toast?.success(`✅ GPS konumu alındı (±${Math.round(accuracy)}m doğruluk)`);
                        log('✅ GPS konumu alındı:', lat, lng, 'accuracy:', accuracy);

                        // ✅ GPS button'ı restore et
                        const gpsButton = document.querySelector('[onclick*="getCurrentLocation"]');
                        if (gpsButton) {
                            gpsButton.disabled = false;
                            gpsButton.setAttribute('aria-disabled', 'false');
                            gpsButton.classList.remove('opacity-50', 'cursor-wait', 'animate-pulse');
                            gpsButton.innerHTML = gpsButton.innerHTML.replace('⏳', '📍');
                        }
                    },
                    (error) => {
                        // ✅ GPS button'ı restore et
                        const gpsButton = document.querySelector('[onclick*="getCurrentLocation"]');
                        if (gpsButton) {
                            gpsButton.disabled = false;
                            gpsButton.setAttribute('aria-disabled', 'false');
                            gpsButton.classList.remove('opacity-50', 'cursor-wait', 'animate-pulse');
                            gpsButton.innerHTML = gpsButton.innerHTML.replace('⏳', '📍');
                        }

                        // ✅ User-friendly error messages
                        if (error.code === 1) {
                            window.toast?.warning(
                                '⚠️ Konum izni reddedildi. Lütfen tarayıcı ayarlarından izin verin.');
                        } else if (error.code === 2) {
                            window.toast?.error('❌ Konum bilgisi alınamadı. GPS kapalı olabilir.');
                        } else if (error.code === 3) {
                            window.toast?.error('⏱️ Konum talebi zaman aşımına uğradı. Tekrar deneyin.');
                        } else {
                            window.toast?.error('❌ Konum alınamadı: ' + error.message);
                        }
                        log('⚠️ GPS error (code ' + error.code + '):', error.message);
                    }, {
                        enableHighAccuracy: true,
                        timeout: 10000,
                        maximumAge: 0
                    }
                );
            }
        };

        // DOMContentLoaded'da init et
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(() => {
                VanillaLocationManager.init();
            }, 100);

            // ✅ Koordinat input'larına event listener ekle (Input → Map Sync)
            const enlemInput = document.getElementById('enlem');
            const boylamInput = document.getElementById('boylam');

            if (enlemInput && boylamInput) {
                // Input değişince haritayı güncelle
                function syncCoordsToMap() {
                    const lat = parseFloat(enlemInput.value);
                    const lng = parseFloat(boylamInput.value);

                    if (!isNaN(lat) && !isNaN(lng) && lat !== 0 && lng !== 0) {
                        if (VanillaLocationManager.map) {
                            // skipReverseGeocode = true (input'tan geliyorsa tekrar geocoding yapma)
                            VanillaLocationManager.setMarker(lat, lng, true);
                            VanillaLocationManager.map.setView([lat, lng], 15);
                            log('✅ Input → Map sync:', lat, lng);
                        } else {
                            log('⏳ Harita henüz hazır değil, 2 saniye sonra tekrar dene...');
                            setTimeout(syncCoordsToMap, 2000);
                        }
                    }
                }

                // Input blur'da kontrol et
                enlemInput.addEventListener('blur', syncCoordsToMap);
                boylamInput.addEventListener('blur', syncCoordsToMap);

                log('✅ Koordinat input listeners eklendi');
            }
        });

        // Global access
        log('🎯 [DEBUG] VanillaLocationManager tanımlanıyor...');
        window.VanillaLocationManager = VanillaLocationManager;
        log('✅ Vanilla Location Manager registered globally');
        log('🔍 [DEBUG] window.VanillaLocationManager:', typeof window.VanillaLocationManager);

        // Harita tipi değiştirme fonksiyonu (global scope)
        window.setMapType = function(type) {
            VanillaLocationManager.setMapType(type);
        };

        // 🆕 PHASE 2: Distance Calculator System
        window.distancePoints = [];

        // 🗺️ Kayıtlı polygon'u haritada göster (arsa/arazi ilanları)
        (function renderExistingPolygon() {
            if (!window.ilanData || window.ilanData.geometry_type !== 'polygon' || !window.ilanData.geometry) return;

            function tryRender() {
                if (!VanillaLocationManager.map) {
                    setTimeout(tryRender, 500);
                    return;
                }

                try {
                    const geojson = typeof window.ilanData.geometry === 'string' ?
                        JSON.parse(window.ilanData.geometry) :
                        window.ilanData.geometry;

                    if (!geojson || !geojson.coordinates) return;

                    if (!window.drawnItems) {
                        window.drawnItems = new L.FeatureGroup();
                        VanillaLocationManager.map.addLayer(window.drawnItems);
                    }

                    const layer = L.geoJSON(geojson, {
                        style: {
                            color: '#f59e0b',
                            fillColor: '#f59e0b',
                            fillOpacity: 0.2,
                            weight: 3,
                        }
                    });

                    layer.eachLayer(function(l) {
                        window.drawnItems.addLayer(l);
                        window.propertyBoundary = l;
                    });

                    // Haritayı polygon sınırlarına zoom'la
                    VanillaLocationManager.map.fitBounds(layer.getBounds(), {
                        padding: [50, 50]
                    });

                    // boundary_geojson hidden input'u güncelle
                    const geojsonField = document.getElementById('boundary_geojson');
                    if (geojsonField) {
                        geojsonField.value = JSON.stringify(geojson);
                    }

                    log('✅ Kayıtlı polygon haritada gösterildi');
                } catch (e) {
                    console.error('❌ Polygon render hatası:', e);
                }
            }

            setTimeout(tryRender, 600);
        })();
        window.distanceMarkers = [];
        window.distanceLines = [];
        window.measuringFor = null;

        window.addDistancePoint = function(name, icon) {
            // ✅ Harita kontrolü
            if (!VanillaLocationManager.map) {
                window.toast?.error('❌ Harita yüklenmedi! Lütfen sayfayı yenileyin.');
                console.error('❌ Map not initialized in addDistancePoint');
                return;
            }

            // ✅ Mülk konumu kontrolü (marker veya koordinat)
            const enlem = document.getElementById('enlem')?.value;
            const boylam = document.getElementById('boylam')?.value;

            if ((!VanillaLocationManager.marker) && (!enlem || !boylam)) {
                window.toast?.warning('⚠️ Önce mülk konumunu işaretleyin (haritaya tıklayın)');
                return;
            }

            // ✅ Koordinatlar var ama marker yoksa, marker oluştur
            if (!VanillaLocationManager.marker && enlem && boylam) {
                const lat = parseFloat(enlem);
                const lng = parseFloat(boylam);
                if (!isNaN(lat) && !isNaN(lng)) {
                    VanillaLocationManager.setMarker(lat, lng, true);
                    log('✅ Marker otomatik oluşturuldu (mesafe ölçümü için)');
                }
            }

            window.measuringFor = {
                name,
                icon
            };
            window.toast?.info(`${icon} ${name} için haritada bir noktaya tıklayın`);
            log('📏 Mesafe ölçümü başladı:', name);

            // Harita tıklama event'ine temp listener ekle
            const tempClickHandler = function(e) {
                // ✅ Guard: measuringFor null check
                if (!window.measuringFor) {
                    console.warn('⚠️ measuringFor is null, aborting');
                    return;
                }

                const propertyLat = parseFloat(document.getElementById('enlem').value);
                const propertyLng = parseFloat(document.getElementById('boylam').value);
                const targetLat = e.latlng.lat;
                const targetLng = e.latlng.lng;

                // Mesafe hesapla (Haversine formula)
                const distance = calculateDistance(propertyLat, propertyLng, targetLat, targetLng);

                // Distance point kaydet (with local copy to avoid race condition)
                const measuring = window.measuringFor;
                const point = {
                    name: measuring.name,
                    icon: measuring.icon,
                    lat: targetLat,
                    lng: targetLng,
                    distance: Math.round(distance),
                    unit: distance >= 1000 ? 'km' : 'm',
                    displayDistance: distance >= 1000 ? (distance / 1000).toFixed(1) : Math.round(distance)
                };

                window.distancePoints.push(point);

                // Haritaya marker ekle (measuring kullan, not window.measuringFor)
                const marker = L.marker([targetLat, targetLng], {
                    icon: L.divIcon({
                        html: `<div class="flex items-center justify-center w-8 h-8 bg-purple-600 text-white rounded-full shadow-lg border-2 border-white text-sm">${measuring.icon}</div>`,
                        className: 'distance-marker',
                        iconSize: [32, 32],
                        iconAnchor: [16, 32]
                    })
                }).addTo(VanillaLocationManager.map);

                marker.bindPopup(
                    `${point.icon} ${point.name}<br><strong>${point.displayDistance} ${point.unit}</strong>`);
                window.distanceMarkers.push(marker);

                // Çizgi çiz (property → target)
                const line = L.polyline(
                    [
                        [propertyLat, propertyLng],
                        [targetLat, targetLng]
                    ], {
                        color: '#9333ea',
                        weight: 3,
                        opacity: 0.7,
                        dashArray: '10, 10'
                    }
                ).addTo(VanillaLocationManager.map);

                window.distanceLines.push(line);

                // UI'ı güncelle
                updateDistanceList();

                // JSON field'ını güncelle
                const nearbyDistancesField = document.getElementById('nearby_distances');
                if (nearbyDistancesField) {
                    nearbyDistancesField.value = JSON.stringify(window.distancePoints);
                }

                log('✅ Mesafe eklendi:', point);
                window.toast?.success(`${point.icon} ${point.name}: ${point.displayDistance} ${point.unit}`);

                // Temp listener'ı kaldır
                VanillaLocationManager.map.off('click', tempClickHandler);
                window.measuringFor = null;
            };

            // Temp listener ekle
            VanillaLocationManager.map.once('click', tempClickHandler);
        };

        window.removeDistancePoint = function(index) {
            // Marker'ı kaldır
            if (window.distanceMarkers[index]) {
                VanillaLocationManager.map.removeLayer(window.distanceMarkers[index]);
            }

            // Çizgiyi kaldır
            if (window.distanceLines[index]) {
                VanillaLocationManager.map.removeLayer(window.distanceLines[index]);
            }

            // Array'den sil
            window.distancePoints.splice(index, 1);
            window.distanceMarkers.splice(index, 1);
            window.distanceLines.splice(index, 1);

            // UI güncelle
            updateDistanceList();

            // JSON field güncelle
            const nearbyDistancesField = document.getElementById('nearby_distances');
            if (nearbyDistancesField) {
                nearbyDistancesField.value = JSON.stringify(window.distancePoints);
            }

            window.toast?.info('Mesafe noktası silindi');
        };

        function updateDistanceList() {
            const container = document.getElementById('distance-list');
            if (!container) return;

            if (window.distancePoints.length === 0) {
                container.innerHTML = `
            <div class="text-center text-xs text-gray-500 dark:text-gray-400 py-4">
                <svg class="w-8 h-8 mx-auto mb-2 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/>
                </svg>
                Yukarıdaki butonlara tıklayın, haritada noktayı işaretleyin
            </div>
        `;
                return;
            }

            let html = '';
            window.distancePoints.forEach((point, index) => {
                html += `
            <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-slate-900 rounded-lg border border-purple-200 dark:border-purple-800/30 shadow-sm dark:shadow-none">
                <div class="flex items-center gap-3">
                    <span class="text-lg">${point.icon}</span>
                    <div>
                        <div class="font-medium text-sm text-gray-900 dark:text-white dark:text-slate-100">${point.name}</div>
                        <div class="text-xs text-purple-600 dark:text-purple-400 font-bold">${point.displayDistance} ${point.unit}</div>
                    </div>
                </div>
                <button type="button" onclick="removeDistancePoint(${index})"
                    class="p-1.5 text-red-600 dark:text-red-400 hover:bg-red-100 dark:hover:bg-red-900/30 rounded-lg transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        `;
            });

            container.innerHTML = html;
        }

        function calculateDistance(lat1, lon1, lat2, lon2) {
            // Haversine formula (mesafe hesaplama)
            const R = 6371e3; // Earth radius in meters
            const φ1 = lat1 * Math.PI / 180;
            const φ2 = lat2 * Math.PI / 180;
            const Δφ = (lat2 - lat1) * Math.PI / 180;
            const Δλ = (lon2 - lon1) * Math.PI / 180;

            const a = Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
                Math.cos(φ1) * Math.cos(φ2) *
                Math.sin(Δλ / 2) * Math.sin(Δλ / 2);
            const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

            return R * c; // Distance in meters
        }

        // 🆕 PHASE 3: Property Boundary Drawing System
        window.propertyBoundary = null;
        window.drawnItems = null;
        window.drawControl = null;

        window.startDrawingBoundary = async function() {
            // ✅ Harita kontrolü
            if (!VanillaLocationManager.map) {
                window.toast?.error('❌ Harita yüklenmedi! Lütfen sayfayı yenileyin.');
                console.error('❌ Map not initialized in startDrawingBoundary');
                return;
            }

            // ✅ Leaflet.draw kontrolü ve dinamik yükleme
            if (typeof L.Control.Draw === 'undefined') {
                console.warn('⚠️ Leaflet.draw yükleniyor...');
                window.toast?.info('🎨 Çizim aracı yükleniyor, lütfen bekleyin...');

                try {
                    await loadLeafletDraw();
                    console.log('✅ Leaflet.draw başarıyla yüklendi');
                    window.toast?.success('✅ Çizim aracı hazır!');
                    // Tekrar çağır
                    window.startDrawingBoundary();
                    return;
                } catch (error) {
                    console.error('❌ Leaflet.draw yüklenemedi:', error);
                    window.toast?.error('❌ Çizim aracı yüklenemedi. Lütfen sayfayı yenileyin.');
                    return;
                }
            }

            // Mevcut boundary varsa temizle
            if (window.propertyBoundary) {
                clearBoundary();
            }

            // FeatureGroup oluştur (drawn items için)
            if (!window.drawnItems) {
                window.drawnItems = new L.FeatureGroup();
                VanillaLocationManager.map.addLayer(window.drawnItems);
            }

            // Draw control ekle
            window.drawControl = new L.Control.Draw({
                draw: {
                    polygon: {
                        shapeOptions: {
                            color: '#10b981',
                            fillColor: '#10b981',
                            fillOpacity: 0.3,
                            weight: 3
                        },
                        showArea: true,
                        metric: true
                    },
                    polyline: false,
                    rectangle: false,
                    circle: false,
                    marker: false,
                    circlemarker: false
                },
                edit: {
                    featureGroup: window.drawnItems,
                    remove: true
                }
            });

            VanillaLocationManager.map.addControl(window.drawControl);

            // Polygon çizim event'i
            VanillaLocationManager.map.on(L.Draw.Event.CREATED, function(e) {
                const layer = e.layer;
                window.drawnItems.addLayer(layer);
                window.propertyBoundary = layer;

                // GeoJSON al
                const geojson = layer.toGeoJSON();
                const boundaryGeojsonField = document.getElementById('boundary_geojson');
                if (boundaryGeojsonField) {
                    boundaryGeojsonField.value = JSON.stringify(geojson);
                }

                // Alan hesapla (m²)
                const area = L.GeometryUtil.geodesicArea(layer.getLatLngs()[0]);
                const boundaryAreaField = document.getElementById('boundary_area');
                if (boundaryAreaField) {
                    boundaryAreaField.value = Math.round(area);
                }

                // UI güncelle
                const infoDiv = document.getElementById('boundary-info');
                const areaDisplay = document.getElementById('boundary-area-display');

                if (infoDiv) infoDiv.classList.remove('hidden');
                if (areaDisplay) {
                    if (area >= 10000) {
                        areaDisplay.textContent = (area / 10000).toFixed(2) + ' dönüm (' + Math.round(area)
                            .toLocaleString() + ' m²)';
                    } else {
                        areaDisplay.textContent = Math.round(area).toLocaleString() + ' m²';
                    }
                }

                log('✅ Mülk sınırı çizildi. Alan:', Math.round(area), 'm²');
                window.toast?.success(`Sınır çizildi! Alan: ${Math.round(area).toLocaleString()} m²`);

                // Draw control'ü kaldır (tek polygon)
                if (window.drawControl) {
                    VanillaLocationManager.map.removeControl(window.drawControl);
                    window.drawControl = null;
                }
            });

            window.toast?.info('📐 Polygon çizimi başladı! Haritada noktaları işaretleyin');
            log('📐 Boundary drawing mode aktif');
        };

        window.clearBoundary = function() {
            if (window.drawnItems) {
                window.drawnItems.clearLayers();
            }

            if (window.drawControl) {
                VanillaLocationManager.map.removeControl(window.drawControl);
                window.drawControl = null;
            }

            window.propertyBoundary = null;
            const boundaryGeojsonField = document.getElementById('boundary_geojson');
            const boundaryAreaField = document.getElementById('boundary_area');
            if (boundaryGeojsonField) boundaryGeojsonField.value = '';
            if (boundaryAreaField) boundaryAreaField.value = '';

            const infoDiv = document.getElementById('boundary-info');
            if (infoDiv) infoDiv.classList.add('hidden');

            window.toast?.info('Sınır temizlendi');
            log('🗑️ Boundary cleared');
        };

        // Leaflet.GeometryUtil (alan hesaplama için)
        if (typeof L.GeometryUtil === 'undefined') {
            L.GeometryUtil = {
                geodesicArea: function(latLngs) {
                    const pointsCount = latLngs.length;
                    let area = 0.0;
                    const d2r = Math.PI / 180;
                    let p1 = latLngs[pointsCount - 1];

                    for (let i = 0; i < pointsCount; i++) {
                        const p2 = latLngs[i];
                        area += (p2.lng - p1.lng) * d2r * (2 + Math.sin(p1.lat * d2r) + Math.sin(p2.lat * d2r));
                        p1 = p2;
                    }

                    area = area * 6378137.0 * 6378137.0 / 2.0;
                    return Math.abs(area);
                }
            };
        }

        // ============================================
        // 🎨 LEAFLET.DRAW DİNAMİK YÜKLEME FONKSİYONU
        // ============================================
        function loadLeafletDraw() {
            return new Promise((resolve, reject) => {
                log('📦 Leaflet.draw yükleniyor...');

                // CSS yükle
                const link = document.createElement('link');
                link.rel = 'stylesheet';
                link.href = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.css';
                link.onload = () => log('✅ Leaflet.draw CSS yüklendi');
                link.onerror = () => console.error('❌ Leaflet.draw CSS yüklenemedi');
                document.head.appendChild(link);

                // JS yükle
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/leaflet.draw/1.0.4/leaflet.draw.js';
                script.onload = () => {
                    log('✅ Leaflet.draw JS yüklendi');
                    resolve();
                };
                script.onerror = (error) => {
                    console.error('❌ Leaflet.draw JS yüklenemedi');
                    reject(new Error('Leaflet.draw script yüklenemedi'));
                };
                document.body.appendChild(script);
            });
        }

        // ============================================
        // 🗺️ HARİTA DURUM MONİTORİNG (Debug için)
        // ============================================
        window.mapStatus = function() {
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('🗺️ HARİTA DURUM RAPORU');
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
            console.log('✅ Leaflet yüklü:', typeof L !== 'undefined');
            console.log('✅ Map initialized:', !!VanillaLocationManager.map);
            console.log('✅ Marker var:', !!VanillaLocationManager.marker);
            console.log('✅ Leaflet.draw:', typeof L.Control?.Draw !== 'undefined');
            console.log('✅ Standard layer:', !!VanillaLocationManager.standardLayer);
            console.log('✅ Satellite layer:', !!VanillaLocationManager.satelliteLayer);
            console.log('📍 Koordinatlar:', {
                lat: document.getElementById('enlem')?.value || 'yok',
                lng: document.getElementById('boylam')?.value || 'yok'
            });
            console.log('📏 Mesafe noktaları:', window.distancePoints?.length || 0);
            console.log('🏗️ Boundary:', !!window.propertyBoundary);
            console.log('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

            // Toast ile de bildir
            if (window.toast) {
                const harita_durumu = VanillaLocationManager.map ? '✅ Çalışıyor' : '❌ Hata';
                window.toast.info('Harita durumu: ' + harita_durumu);
            }
        };

        // Console'da kullanım: window.mapStatus()
    </script>

    <!-- Save Draft Handler -->
    <script>
        document.getElementById('button-save-draft')?.addEventListener('click', function() {
            if (window.StableCreateCore && window.StableCreateCore.saveDraft) {
                window.StableCreateCore.saveDraft();
            }
        });
    </script>

    {{-- Update the button ID in the footer if exists --}}
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const oldBtn = document.getElementById('save-draft-btn');
            if (oldBtn) oldBtn.id = 'button-save-draft';
        });
    </script>

    <!-- ✅ PHASE 1 - CLIENT-SIDE VALIDATION SYSTEM -->
    <script>
        // 🎯 Real-time Validation Manager (Context7)
        const ValidationManager = {
            rules: {
                baslik: {
                    required: true,
                    minLength: 10,
                    maxLength: 200,
                    message: 'Başlık 10-200 karakter arası olmalıdır'
                },
                aciklama: {
                    required: false,
                    maxLength: 5000,
                    message: 'Açıklama en fazla 5000 karakter olabilir'
                },
                ana_kategori_id: {
                    required: true,
                    message: 'Ana kategori seçmelisiniz'
                },
                alt_kategori_id: {
                    required: true,
                    message: 'Alt kategori seçmelisiniz'
                },
                junction_id: {
                    required: true,
                    message: 'Yayın tipi seçmelisiniz'
                },
                fiyat: {
                    required: true,
                    min: 0,
                    message: 'Geçerli bir fiyat girmelisiniz'
                },
                il_id: {
                    required: true,
                    message: 'İl seçmelisiniz'
                },
                ilce_id: {
                    required: true,
                    message: 'İlçe seçmelisiniz'
                },
                adres: {
                    required: true,
                    minLength: 10,
                    message: 'Adres en az 10 karakter olmalıdır'
                }
            },

            validate(fieldName, value) {
                const rule = this.rules[fieldName];
                if (!rule) return {
                    valid: true
                };

                const isFiyatRequiredByMode = () => {
                    const modeField = document.getElementById('fiyat_gosterim_modu') || document.querySelector('[name="fiyat_gosterim_modu"]');
                    return (modeField?.value || 'exact') === 'exact';
                };

                const effectiveRequired = fieldName === 'fiyat' ? isFiyatRequiredByMode() : !!rule.required;

                // Required check
                if (effectiveRequired && (!value || value.toString().trim() === '')) {
                    return {
                        valid: false,
                        message: rule.message
                    };
                }

                // Skip other checks if field is empty and not required
                if (!value) return {
                    valid: true
                };

                // Min length check
                if (rule.minLength && value.toString().length < rule.minLength) {
                    return {
                        valid: false,
                        message: rule.message
                    };
                }

                // Max length check
                if (rule.maxLength && value.toString().length > rule.maxLength) {
                    return {
                        valid: false,
                        message: rule.message
                    };
                }

                // Min value check (for numbers)
                if (rule.min !== undefined && parseFloat(value) < rule.min) {
                    return {
                        valid: false,
                        message: rule.message
                    };
                }

                return {
                    valid: true
                };
            },

            showError(fieldName, message) {
                const field = document.getElementById(fieldName);
                if (!field) return;

                // Add error class (Tailwind)
                field.classList.add('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                field.classList.remove('border-gray-300', 'focus:ring-blue-500', 'dark:focus:ring-blue-400',
                    'focus:border-blue-500');

                // Show error message
                let errorDiv = field.parentElement.querySelector('.validation-error');
                if (!errorDiv) {
                    errorDiv = document.createElement('div');
                    errorDiv.className = 'validation-error text-red-600 text-sm mt-1 flex items-center gap-1';
                    errorDiv.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <span></span>
            `;
                    field.parentElement.appendChild(errorDiv);
                }
                errorDiv.querySelector('span').textContent = message;

                // Shake animation
                field.style.animation = 'shake 0.5s';
                setTimeout(() => {
                    field.style.animation = '';
                }, 500);
            },

            clearError(fieldName) {
                const field = document.getElementById(fieldName);
                if (!field) return;

                // Remove error class - Context7: Her class ayrı ayrı remove edilmeli
                field.classList.remove('border-red-500', 'focus:ring-red-500', 'focus:border-red-500');
                field.classList.add('border-gray-300');

                // Hide error message
                const errorDiv = field.parentElement.querySelector('.validation-error');
                if (errorDiv) {
                    errorDiv.remove();
                }
            },

            validateAll() {
                let isValid = true;
                let firstErrorField = null;

                Object.keys(this.rules).forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (!field) return;

                    const result = this.validate(fieldName, field.value);

                    if (!result.valid) {
                        this.showError(fieldName, result.message);
                        if (!firstErrorField) {
                            firstErrorField = field;
                        }
                        isValid = false;
                    } else {
                        this.clearError(fieldName);
                    }
                });

                // Scroll to first error
                if (firstErrorField) {
                    firstErrorField.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                    });
                    firstErrorField.focus();
                }

                return isValid;
            },

            getCompletionPercentage() {
                const totalFields = Object.keys(this.rules).length;
                let completedFields = 0;

                Object.keys(this.rules).forEach(fieldName => {
                    const field = document.getElementById(fieldName);
                    if (!field) return;

                    const result = this.validate(fieldName, field.value);
                    if (result.valid && field.value) {
                        completedFields++;
                    }
                });

                return Math.round((completedFields / totalFields) * 100);
            },

            updateProgressIndicator() {
                const percentage = this.getCompletionPercentage();
                const indicator = document.getElementById('form-progress-indicator');

                if (indicator) {
                    indicator.textContent = `Form Completion: ${percentage}%`;
                    indicator.className =
                        `text-sm font-medium ${percentage === 100 ? 'text-green-600' : 'text-blue-600'}`;
                }
            }
        };

        // Attach validation listeners on DOM ready
        document.addEventListener('DOMContentLoaded', () => {
            console.log('✅ Validation Manager initializing...');

            Object.keys(ValidationManager.rules).forEach(fieldName => {
                const field = document.getElementById(fieldName);
                if (!field) return;

                // Real-time validation on blur
                field.addEventListener('blur', (e) => {
                    const result = ValidationManager.validate(fieldName, e.target.value);

                    if (!result.valid) {
                        ValidationManager.showError(fieldName, result.message);
                    } else {
                        ValidationManager.clearError(fieldName);
                    }

                    ValidationManager.updateProgressIndicator();
                });

                // Clear error on input
                field.addEventListener('input', () => {
                    ValidationManager.clearError(fieldName);
                    ValidationManager.updateProgressIndicator();
                });

                // For select elements, also listen to change
                if (field.tagName === 'SELECT') {
                    field.addEventListener('change', () => {
                        ValidationManager.clearError(fieldName);
                        ValidationManager.updateProgressIndicator();
                    });
                }
            });

            // Validate on form submit
            const form = document.querySelector('form[action*="ilanlar"]');
            if (form) {
                form.addEventListener('submit', (e) => {
                    // ✅ Loading state ekle
                    const submitBtn = document.getElementById('submit-btn');
                    const submitIcon = document.getElementById('submit-icon');
                    const submitText = document.getElementById('submit-text');
                    const submitSpinner = document.getElementById('submit-spinner');

                    if (submitBtn && submitText && submitSpinner && submitIcon) {
                        submitBtn.disabled = true;
                        submitIcon.classList.add('hidden');
                        submitSpinner.classList.remove('hidden');
                        submitText.textContent = 'Kaydediliyor...';
                    }

                    // ✅ DEBUG: Validation sonuçlarını logla
                    const validationResult = ValidationManager.validateAll();
                    console.log('🔍 Validation result:', validationResult);

                    if (!validationResult) {
                        e.preventDefault();

                        // Hangi alanlar hatalı?
                        const errorFields = [];
                        Object.keys(ValidationManager.rules).forEach(fieldName => {
                            const field = document.getElementById(fieldName);
                            if (field) {
                                const result = ValidationManager.validate(fieldName, field.value);
                                if (!result.valid) {
                                    errorFields.push(fieldName + ': ' + result.message);
                                }
                            }
                        });
                        console.log('❌ Validation errors:', errorFields);

                        window.toast?.error('❌ Lütfen tüm gerekli alanları doldurun');

                        // Count errors
                        const errorCount = document.querySelectorAll('.validation-error').length;
                        window.toast?.warning(`⚠️ ${errorCount} alan hatalı veya eksik`);

                        // Loading state'i geri al
                        if (submitBtn && submitText && submitSpinner && submitIcon) {
                            submitBtn.disabled = false;
                            submitIcon.classList.remove('hidden');
                            submitSpinner.classList.add('hidden');
                            submitText.textContent = 'Güncelle ve Yayınla';
                        }

                        return false;
                    }

                    // Show success feedback
                    window.toast?.success('✅ Form doğrulandı, kaydediliyor...');
                });
            }

            // Initial progress indicator update
            setTimeout(() => {
                ValidationManager.updateProgressIndicator();
            }, 500);

            console.log('✅ Validation Manager initialized (' + Object.keys(ValidationManager.rules).length +
                ' rules)');
        });

        // Add shake animation CSS
        const style = document.createElement('style');
        style.textContent = `
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        10%, 30%, 50%, 70%, 90% { transform: translateX(-5px); }
        20%, 40%, 60%, 80% { transform: translateX(5px); }
    }

    .validation-error {
        animation: slideDown 0.3s ease-out;
    }

    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;
        document.head.appendChild(style);
    </script>

    @include('admin.ilanlar.scripts.category-logic')

    <!-- =================================== -->
    <!-- Draft Auto-save System -->
    <!-- Context7: %100, Yalıhan Bekçi: ✅ -->
    <!-- =================================== -->
    @include('admin.ilanlar.scripts.draft-autosave')

    <!-- ✅ FIX: Exchange Rates Guard -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // AdvancedPriceManager guard: ensure convertedPrices exists and guard update
            try {
                if (typeof window.advancedPriceManager === 'function') {
                    var _origAPM = window.advancedPriceManager;
                    window.advancedPriceManager = function() {
                        var obj = _origAPM();
                        if (!obj.convertedPrices) {
                            obj.convertedPrices = {
                                TRY: '',
                                USD: '',
                                EUR: '',
                                GBP: ''
                            };
                        }
                        if (!obj.exchangeRates) {
                            obj.exchangeRates = (window.currencyRates || {
                                TRY: 1,
                                USD: 34.5,
                                EUR: 37.2,
                                GBP: 43.8
                            });
                        }
                        if (typeof obj.updateConvertedPrices === 'function') {
                            var _origUpd = obj.updateConvertedPrices;
                            obj.updateConvertedPrices = function() {
                                try {
                                    // Guard: exchangeRates yoksa varsayılan değerleri kullan
                                    if (!this.exchangeRates || !this.exchangeRates.USD) {
                                        this.exchangeRates = this.exchangeRates || (window.currencyRates ||
                                        {
                                            TRY: 1,
                                            USD: 34.5,
                                            EUR: 37.2,
                                            GBP: 43.8
                                        });
                                    }
                                    return _origUpd.apply(this, arguments);
                                } catch (e) {
                                    console.warn('updateConvertedPrices guard:', e);
                                    this.convertedPrices = this.convertedPrices || {
                                        TRY: '',
                                        USD: '',
                                        EUR: '',
                                        GBP: ''
                                    };
                                    this.exchangeRates = this.exchangeRates || (window.currencyRates || {
                                        TRY: 1,
                                        USD: 34.5,
                                        EUR: 37.2,
                                        GBP: 43.8
                                    });
                                }
                            };
                        }
                        return obj;
                    };
                }
            } catch (e) {
                console.warn('AdvancedPriceManager guard error:', e);
            }
        });
    </script>

    <!-- Form submit loading states -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('ilan-create-form');
            const draftBtn = document.getElementById('save-draft-btn');
            const submitBtn = document.getElementById('submit-btn');

            if (form) {
                form.addEventListener('submit', function(e) {
                    const clickedButton = e.submitter;

                    if (clickedButton === draftBtn || clickedButton?.name === 'save_draft') {
                        const icon = document.getElementById('draft-icon');
                        const text = document.getElementById('draft-text');
                        const spinner = document.getElementById('draft-spinner');

                        if (icon && text && spinner) {
                            draftBtn.disabled = true;
                            icon.classList.add('hidden');
                            spinner.classList.remove('hidden');
                            text.textContent = 'Kaydediliyor...';
                        }
                    } else if (clickedButton === submitBtn) {
                        const icon = document.getElementById('submit-icon');
                        const text = document.getElementById('submit-text');
                        const spinner = document.getElementById('submit-spinner');

                        if (icon && text && spinner) {
                            submitBtn.disabled = true;
                            icon.classList.add('hidden');
                            spinner.classList.remove('hidden');
                            text.textContent = 'Güncelleniyor...';
                        }
                    }
                });
            }
        });

        @include('admin.ilanlar.scripts.sticky-nav')
    @endpush
