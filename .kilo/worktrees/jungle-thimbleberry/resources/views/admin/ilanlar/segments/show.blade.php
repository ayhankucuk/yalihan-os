@extends('admin.layouts.admin')

@section('title', $currentSegment->getTitle())

@section('content')
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">{{ $currentSegment->getTitle() }}</h1>
            <p class="text-gray-600 dark:text-gray-400">{{ $currentSegment->getDescription() }}</p>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-[0.8fr_0.2fr] gap-6">
            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm p-6 dark:shadow-none dark:border-slate-700">
                <form method="POST"
                    action="{{ isset($ilan->id) ? route('admin.ilanlar.segments.store', ['ilan' => $ilan->id, 'segment' => $currentSegment->value]) : route('admin.ilanlar.segments.store.create', ['segment' => $currentSegment->value]) }}">
                    @csrf

                    @if ($currentSegment === \App\Enums\IlanSegment::INTERNATIONAL_CITIZENSHIP)
                        <div class="space-y-6">
                            <div>
                                <label for="ulke_id"
                                    class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Ülke</label>
                                <select id="ulke_id" name="ulke_id"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                                    <option value="">Ülke Seçiniz</option>
                                    @foreach (\App\Models\Ulke::getActiveCountries() as $ulke)
                                        <option value="{{ $ulke->id }}" data-code="{{ $ulke->ulke_kodu ?? '' }}"
                                            {{ (string) old('ulke_id', $segmentData['ulke_id'] ?? '') === (string) $ulke->id ? 'selected' : '' }}>
                                            {{ $ulke->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ulke_id')
                                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror
                            </div>

                            <div>
                                <label for="citizenship_eligible"
                                    class="inline-flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                    <input type="checkbox" id="citizenship_eligible" name="citizenship_eligible"
                                        value="1" class="rounded border-gray-300 text-green-600 focus:ring-green-500"
                                        {{ old('citizenship_eligible', $segmentData['citizenship_eligible'] ?? false) ? 'checked' : '' }}>
                                    Vatandaşlık Uygun
                                </label>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div x-data="{
                                    il_id: '{{ old('il_id', $segmentData['il_id'] ?? '') }}',
                                    ilce_id: '{{ old('ilce_id', $segmentData['ilce_id'] ?? '') }}',
                                    ilceler: [],
                                    loadingIlceler: false,
                                    async fetchIlceler() {
                                        if (!this.il_id) { this.ilceler = []; return; }
                                        this.loadingIlceler = true;
                                        try {
                                            const res = await fetch(`/api/ilceler/${this.il_id}`);
                                            const data = await res.json();
                                            this.ilceler = (data.data?.districts || data.ilceler || []).filter(d => d);
                                        } catch (e) { this.ilceler = []; }
                                        this.loadingIlceler = false;
                                    }
                                }" x-init="il_id && fetchIlceler()">
                                    <label for="il_id"
                                        class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İl
                                        (opsiyonel)</label>
                                    <select id="il_id" name="il_id" x-model="il_id" @change="fetchIlceler()"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                                        <option value="">İl Seçiniz</option>
                                        @foreach (\App\Models\Il::orderBy('il_adi')->get(['id', 'il_adi']) as $il)
                                            <option value="{{ $il->id }}"
                                                {{ (string) old('il_id', $segmentData['il_id'] ?? '') === (string) $il->id ? 'selected' : '' }}>
                                                {{ $il->il_adi }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mt-4">
                                        <label for="ilce_id"
                                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">İlçe
                                            (opsiyonel)</label>
                                        <select id="ilce_id" name="ilce_id" x-model="ilce_id"
                                            :disabled="!il_id || loadingIlceler"
                                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                                            <option value=""
                                                x-text="!il_id ? 'Önce il seçin...' : (loadingIlceler ? 'Yükleniyor...' : 'İlçe seçin...')">
                                            </option>
                                            <template x-for="ilce in ilceler" :key="ilce.id">
                                                <option :value="ilce.id" x-text="ilce.ilce_adi"></option>
                                            </template>
                                        </select>
                                        <div class="mt-2 space-x-2">
                                            <span class="badge badge-blue" x-show="loadingIlceler" role="presentation">İlçeler
                                                yükleniyor…</span>
                                            <span class="badge badge-red"
                                                x-show="!loadingIlceler && il_id && ilceler.length === 0"
                                                role="presentation">İlçeler yüklenemedi, daha sonra tekrar deneyin.</span>
                                        </div>
                                    </div>
                                </div>
                                <div>
                                    <label for="citizenship_program"
                                        class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Program</label>
                                    <select id="citizenship_program" name="citizenship_program"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                                        <option value="">Seçiniz</option>
                                        <option value="golden-visa"
                                            {{ old('citizenship_program') === 'golden-visa' ? 'selected' : '' }}>Golden
                                            Visa</option>
                                        <option value="investor"
                                            {{ old('citizenship_program') === 'investor' ? 'selected' : '' }}>Yatırımcı
                                            Programı</option>
                                        <option value="skilled-worker"
                                            {{ old('citizenship_program') === 'skilled-worker' ? 'selected' : '' }}>
                                            Nitelikli Çalışan</option>
                                    </select>
                                    <p id="program-info" class="mt-2 text-xs text-gray-600 dark:text-slate-200"></p>
                                </div>
                                <div>
                                    <label for="program_notes"
                                        class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Program
                                        Notları</label>
                                    <textarea id="program_notes" name="program_notes" rows="3"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">{{ old('program_notes') }}</textarea>
                                </div>
                            </div>

                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <a href="{{ isset($ilan->id) ? route('admin.ilanlar.show', $ilan->id) : route('admin.ilanlar.index') }}"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200 dark:border-slate-800 text-sm font-medium text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 dark:text-slate-300 dark:border-slate-700">İptal</a>
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-all duration-200">Kaydet
                                    ve Devam Et</button>
                            </div>
                        </div>
                    @elseif($currentSegment === \App\Enums\IlanSegment::PORTFOLIO_INFO)
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="baslik"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Başlık</label>
                                    <input type="text" id="baslik" name="baslik"
                                        value="{{ old('baslik', $segmentData['baslik'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('baslik')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="fiyat"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Fiyat</label>
                                    <input type="number" step="1" id="fiyat" name="fiyat"
                                        value="{{ old('fiyat', $segmentData['fiyat'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('fiyat')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <label for="para_birimi"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Para Birimi</label>
                                    <select id="para_birimi" name="para_birimi"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                        <option value="TRY"
                                            {{ old('para_birimi', $segmentData['para_birimi'] ?? 'TRY') === 'TRY' ? 'selected' : '' }}>
                                            TRY</option>
                                        <option value="USD"
                                            {{ old('para_birimi', $segmentData['para_birimi'] ?? '') === 'USD' ? 'selected' : '' }}>
                                            USD</option>
                                        <option value="EUR"
                                            {{ old('para_birimi', $segmentData['para_birimi'] ?? '') === 'EUR' ? 'selected' : '' }}>
                                            EUR</option>
                                    </select>
                                    @error('para_birimi')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="emlak_turu"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Emlak Türü</label>
                                    <select id="emlak_turu" name="emlak_turu"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                        <option value="konut"
                                            {{ old('emlak_turu', $segmentData['emlak_turu'] ?? 'konut') === 'konut' ? 'selected' : '' }}>
                                            Konut</option>
                                        <option value="ticari"
                                            {{ old('emlak_turu', $segmentData['emlak_turu'] ?? '') === 'ticari' ? 'selected' : '' }}>
                                            Ticari</option>
                                        <option value="arsa"
                                            {{ old('emlak_turu', $segmentData['emlak_turu'] ?? '') === 'arsa' ? 'selected' : '' }}>
                                            Arsa</option>
                                    </select>
                                    @error('emlak_turu')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="ilan_turu"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">İlan Türü</label>
                                    <select id="ilan_turu" name="ilan_turu"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                        <option value="satilik"
                                            {{ old('ilan_turu', $segmentData['ilan_turu'] ?? '') === 'satilik' ? 'selected' : '' }}>
                                            Satılık</option>
                                        <option value="kiralik"
                                            {{ old('ilan_turu', $segmentData['ilan_turu'] ?? 'kiralik') === 'kiralik' ? 'selected' : '' }}>
                                            Kiralık</option>
                                    </select>
                                    @error('ilan_turu')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <label for="brut_metrekare"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Brüt m²</label>
                                    <input type="number" step="1" id="brut_metrekare" name="brut_metrekare"
                                        value="{{ old('brut_metrekare', $segmentData['brut_metrekare'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('brut_metrekare')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="ada_no"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Ada No</label>
                                    <input type="text" id="ada_no" name="ada_no"
                                        value="{{ old('ada_no', $segmentData['ada_no'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                </div>
                                <div class="space-y-2">
                                    <label for="parsel_no"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Parsel No</label>
                                    <input type="text" id="parsel_no" name="parsel_no"
                                        value="{{ old('parsel_no', $segmentData['parsel_no'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <a href="{{ isset($ilan->id) ? route('admin.ilanlar.show', $ilan->id) : route('admin.ilanlar.index') }}"
                                    class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-all dark:text-slate-300">İptal</a>
                                <button type="submit"
                                    class="btn px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all">Kaydet
                                    ve Devam Et</button>
                            </div>
                        </div>
                    @elseif($currentSegment === \App\Enums\IlanSegment::DOCUMENTS_NOTES)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label for="documents"
                                    class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Dökümanlar</label>
                                <input type="file" id="documents" name="documents[]" multiple
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                @error('documents')
                                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="space-y-2">
                                <label for="notes" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">İç
                                    Notlar</label>
                                <textarea id="notes" name="notes" rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">{{ old('notes', $segmentData['notes'] ?? '') }}</textarea>
                                @error('notes')
                                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <a href "{{ isset($ilan->id) ? route('admin.ilanlar.show', $ilan->id) : route('admin.ilanlar.index') }}"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200 dark:border-slate-800 text-sm font-medium text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-800 transition-all duration-200 dark:text-slate-300 dark:border-slate-700">İptal</a>
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 transition-all duration-200">Kaydet
                                    ve Devam Et</button>
                            </div>
                        </div>
                    @elseif($currentSegment === \App\Enums\IlanSegment::PORTAL_LISTING)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Portal
                                    Açıklamaları</label>
                                <textarea name="portal_descriptions[default]" rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">{{ old('portal_descriptions.default', $segmentData['portal_descriptions']['default'] ?? '') }}</textarea>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="portal_sync" name="portal_sync" value="1"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    {{ old('portal_sync', $segmentData['portal_sync'] ?? false) ? 'checked' : '' }}>
                                <label for="portal_sync" class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Portal
                                    Senkron Açık</label>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <a href="{{ isset($ilan->id) ? route('admin.ilanlar.show', $ilan->id) : route('admin.ilanlar.index') }}"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200 dark:border-slate-800 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300 dark:border-slate-700">İptal</a>
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold">Kaydet
                                    ve Devam Et</button>
                            </div>

                            {{-- Sezonluk Fiyatlandırma (Kiralama Odaklı) --}}
                            @include('admin.ilanlar.components.season-pricing-manager', ['ilan' => $ilan])
                        </div>
                    @elseif($currentSegment === \App\Enums\IlanSegment::PORTFOLIO_INFO)
                        <div class="space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="baslik"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Başlık</label>
                                    <input type="text" id="baslik" name="baslik"
                                        value="{{ old('baslik', $segmentData['baslik'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('baslik')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="fiyat"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Fiyat</label>
                                    <input type="number" step="1" id="fiyat" name="fiyat"
                                        value="{{ old('fiyat', $segmentData['fiyat'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('fiyat')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <label for="para_birimi"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Para Birimi</label>
                                    <select id="para_birimi" name="para_birimi"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                        <option value="TRY" selected>TRY</option>
                                        <option value="USD" {{ old('para_birimi') === 'USD' ? 'selected' : '' }}>USD
                                        </option>
                                        <option value="EUR" {{ old('para_birimi') === 'EUR' ? 'selected' : '' }}>EUR
                                        </option>
                                    </select>
                                    @error('para_birimi')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="emlak_turu"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Emlak Türü</label>
                                    <select id="emlak_turu" name="emlak_turu"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                        <option value="konut" selected>Konut</option>
                                    </select>
                                    @error('emlak_turu')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="ilan_turu"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">İlan Türü</label>
                                    <select id="ilan_turu" name="ilan_turu"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                        <option value="kiralik" selected>Kiralık</option>
                                    </select>
                                    @error('ilan_turu')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <div class="space-y-2">
                                    <label for="brut_metrekare"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Brüt m²</label>
                                    <input type="number" step="1" id="brut_metrekare" name="brut_metrekare"
                                        value="{{ old('brut_metrekare', $segmentData['brut_metrekare'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('brut_metrekare')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="ada_no"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Ada No</label>
                                    <input type="text" id="ada_no" name="ada_no"
                                        value="{{ old('ada_no', $segmentData['ada_no'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                </div>
                                <div class="space-y-2">
                                    <label for="parsel_no"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Parsel No</label>
                                    <input type="text" id="parsel_no" name="parsel_no"
                                        value="{{ old('parsel_no', $segmentData['parsel_no'] ?? '') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="min_konaklama"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Minimum Konaklama
                                        (gece)</label>
                                    <input type="number" step="1" id="min_konaklama" name="min_konaklama"
                                        value="{{ old('min_konaklama') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('min_konaklama')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="max_misafir"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Maksimum
                                        Misafir</label>
                                    <input type="number" step="1" id="max_misafir" name="max_misafir"
                                        value="{{ old('max_misafir') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('max_misafir')
                                        <div class="mt-2 text sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label for="gunluk_fiyat"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Günlük Fiyat
                                        (₺)</label>
                                    <input type="number" step="1" id="gunluk_fiyat" name="gunluk_fiyat"
                                        value="{{ old('gunluk_fiyat') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('gunluk_fiyat')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                                <div class="space-y-2">
                                    <label for="haftalik_fiyat"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Haftalık Fiyat
                                        (₺)</label>
                                    <input type="number" step="1" id="haftalik_fiyat" name="haftalik_fiyat"
                                        value="{{ old('haftalik_fiyat') }}"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                    @error('haftalik_fiyat')
                                        <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-2">
                                    <label
                                        class="inline-flex items-center gap-2 text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                        <input type="checkbox" id="havuz" name="havuz" value="1"
                                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            {{ old('havuz') ? 'checked' : '' }}>
                                        Havuz Mevcut
                                    </label>
                                </div>
                                <div class="space-y-2">
                                    <label for="havuz_turu"
                                        class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Havuz Türü</label>
                                    <select id="havuz_turu" name="havuz_turu"
                                        class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                        <option value="">Seçiniz</option>
                                        <option value="özel" {{ old('havuz_turu') === 'özel' ? 'selected' : '' }}>Özel
                                        </option>
                                        <option value="ortak" {{ old('havuz_turu') === 'ortak' ? 'selected' : '' }}>Ortak
                                        </option>
                                    </select>
                                </div>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <a href="{{ isset($ilan->id) ? route('admin.ilanlar.show', $ilan->id) : route('admin.ilanlar.index') }}"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200 dark:border-slate-800 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300 dark:border-slate-700">İptal</a>
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold">Kaydet
                                    ve Devam Et</button>
                            </div>
                        </div>
                    @elseif($currentSegment === \App\Enums\IlanSegment::DOCUMENTS_NOTES)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label for="documents"
                                    class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Dökümanlar</label>
                                <input type="file" id="documents" name="documents[]" multiple
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">
                                @error('documents')
                                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="space-y-2">
                                <label for="notes" class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">İç
                                    Notlar</label>
                                <textarea id="notes" name="notes" rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">{{ old('notes', $segmentData['notes'] ?? '') }}</textarea>
                                @error('notes')
                                    <div class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <a href="{{ isset($ilan->id) ? route('admin.ilanlar.show', $ilan->id) : route('admin.ilanlar.index') }}"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200 dark:border-slate-800 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300 dark:border-slate-700">İptal</a>
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold">Kaydet
                                    ve Devam Et</button>
                            </div>
                        </div>
                    @elseif($currentSegment === \App\Enums\IlanSegment::PORTAL_LISTING)
                        <div class="space-y-6">
                            <div class="space-y-2">
                                <label class="block text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Portal
                                    Açıklamaları</label>
                                <textarea name="portal_descriptions[default]" rows="4"
                                    class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white dark:text-slate-100">{{ old('portal_descriptions.default', $segmentData['portal_descriptions']['default'] ?? '') }}</textarea>
                            </div>
                            <div class="flex items-center gap-2">
                                <input type="checkbox" id="portal_sync" name="portal_sync" value="1"
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    {{ old('portal_sync', $segmentData['portal_sync'] ?? false) ? 'checked' : '' }}>
                                <label for="portal_sync" class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">Portal
                                    Senkron Açık</label>
                            </div>
                            <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                                <a href="{{ isset($ilan->id) ? route('admin.ilanlar.show', $ilan->id) : route('admin.ilanlar.index') }}"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg border border-gray-200 dark:border-slate-800 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300 dark:border-slate-700">İptal</a>
                                <button type="submit"
                                    class="inline-flex items-center px-5 py-2.5 rounded-lg bg-blue-600 text-white text-sm font-semibold">Kaydet
                                    ve Devam Et</button>
                            </div>
                        </div>
                    @else
                        <div class="text-gray-600 dark:text-slate-200">Bu segment için form henüz tanımlanmadı.</div>
                    @endif
                </form>
            </div>

            <div class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl shadow-sm p-4 dark:shadow-none dark:border-slate-700">
                <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">İlerleme</h3>
                <ul class="space-y-2">
                    @foreach ($segments as $seg)
                        @php $p = $progress[$seg->value]; @endphp
                        <li class="flex items-center gap-2">
                            <span
                                class="w-2 h-2 rounded-full {{ $p['completed'] ? 'bg-green-500' : 'bg-gray-400' }}"></span>
                            <span
                                class="text-sm {{ $p['current'] ? 'font-semibold text-blue-600 dark:text-blue-400' : 'text-gray-700 dark:text-gray-300' }}">{{ $p['title'] }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        (function() {
            const ulke = document.getElementById('ulke_id');
            const prog = document.getElementById('citizenship_program');
            const info = document.getElementById('program-info');
            const cfg = @json(config('citizenship_programs'));
            const rules = cfg.countries || {};
            const programLabels = cfg.programs || {};

            function updateInfo() {
                if (!ulke || !prog || !info) return;
                const code = ulke.options[ulke.selectedIndex]?.getAttribute('data-code') || '';
                const p = prog.value || '';
                const text = (rules[p] && rules[p][code]) ? rules[p][code] : '';
                info.textContent = text;
            }

            function filterProgramsByCountry() {
                if (!ulke || !prog) return;
                const code = ulke.options[ulke.selectedIndex]?.getAttribute('data-code') || '';
                const available = new Set(Object.keys(rules).filter(k => rules[k] && rules[k][code]));
                const opts = Array.from(prog.options);
                opts.forEach(o => {
                    if (!o.value) return;
                    const show = available.size === 0 || available.has(o.value);
                    o.disabled = !show;
                    o.hidden = !show;
                });
            }
            if (ulke) ulke.addEventListener('change', updateInfo);
            if (prog) prog.addEventListener('change', updateInfo);
            if (ulke) ulke.addEventListener('change', filterProgramsByCountry);
            filterProgramsByCountry();
            updateInfo();
        })();
    </script>
@endpush
