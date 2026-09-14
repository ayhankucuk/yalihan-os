@extends('admin.layouts.admin')

@section('title', 'İlan Kokpiti | ' . $ilan->kisa_referans)

@section('content')
    <div class="space-y-6" x-data="cockpitManager({{ $ilan->id }})"
        x-on:show-toast.window="addToast($event.detail.message)">

        {{-- 🛰️ Tactical Vitals (Sticky) --}}
        @include('admin.ilanlar.components.cockpit.vitals', ['ilan' => $ilan])

        <div class="max-w-[1700px] mx-auto p-4 md:p-6 space-y-6">

            {{-- 🎯 SAB Executive Strip: Tek satır karar özeti --}}
            @include('admin.ilanlar.components.cockpit.executive-strip', [
                'actionMode' => $actionMode ?? null,
                'locationInsight' => $locationInsight ?? null,
                'pricingInsight' => $pricingInsight ?? null,
            ])

            {{--  Trust Breakdown: Karar Dağılımı --}}
            @if (!empty($trustBreakdown))
                <x-market-intelligence.trust-breakdown :data="$trustBreakdown" />
            @endif

            {{-- 🗺️ Unified Intelligence Map: Hero Position --}}
            @include('admin.ilanlar.components.cockpit.intelligence-map', [
                'ilan' => $ilan,
                'locationInsight' => $locationInsight ?? null,
                'advisorInsight' => $advisorInsight ?? null,
                'pricingInsight' => $pricingInsight ?? null,
                'actionMode' => $actionMode ?? null,
            ])

            {{-- 🛠️ Tactical Data Grid Layout --}}
            <div class="grid grid-cols-1 xl:grid-cols-12 gap-6">

                {{-- LEFT COLUMN: Intelligence & Tech Matrix (8 Units) --}}
                <div class="xl:col-span-8 space-y-6">
                    {{-- 🛸 Radar & AI Insights --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <x-icon name="harita" class="w-4 h-4 text-[#C9A84C]" />
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Bölge & Piyasa Radarı</h3>
                            </div>
                            <span
                                class="px-2.5 py-0.5 bg-[#C9A84C]/15 text-[#C9A84C] text-xs font-bold rounded border border-[#C9A84C]/30">Canlı</span>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.radar')
                        </div>
                    </section>

                    {{-- 📦 Technical Data Matrix --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <x-icon name="liste" class="w-4 h-4 text-[#C9A84C]" />
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Teknik Özellikler</h3>
                            </div>
                            <span class="text-xs font-medium text-gray-500 dark:text-slate-400">Detaylı Matris</span>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.data-grid')
                        </div>
                    </section>

                    {{-- 🎨 Multimedia Gallery --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <x-icon name="kamera" class="w-4 h-4 text-[#C9A84C]" />
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Fotoğraf Galerisi</h3>
                            </div>
                            <span
                                class="text-xs font-semibold px-2.5 py-0.5 bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 rounded-md border border-slate-200 dark:border-slate-700">{{ $ilan->fotograflar->count() }} Fotoğraf</span>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                @forelse ($ilan->fotograflar as $photo)
                                    <div
                                        class="group relative aspect-video rounded-xl overflow-hidden border border-gray-200 dark:border-slate-800 bg-gray-100 dark:bg-slate-900 shadow-sm cursor-pointer hover:border-[#C9A84C]/50 transition-all">
                                        <img src="{{ Storage::url($photo->dosya_yolu) }}"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                                        <div
                                            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-[#0A1628]/95 via-[#0A1628]/60 to-transparent py-2 px-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span
                                                class="text-xs font-semibold text-white">{{ $photo->oda_tipi ?? 'Genel' }}</span>
                                        </div>
                                    </div>
                                @empty
                                    <div class="col-span-full py-8 text-center text-gray-400 dark:text-slate-500 text-xs">
                                        Bu ilan için henüz fotoğraf yüklenmemiş.
                                    </div>
                                @endforelse
                            </div>
                        </div>
                    </section>
                </div>

                {{-- RIGHT COLUMN: CRM, Access & Logs (4 Units) --}}
                <div class="xl:col-span-4 space-y-6">
                    {{-- 💰 MIE v1 Alpha: Pricing Insight --}}
                    @include('admin.ilanlar.components.cockpit.pricing-insight', [
                        'pricingInsight' => $pricingInsight ?? null,
                    ])

                    {{-- 📍 MIE v4: Location Intelligence --}}
                    @include('admin.ilanlar.components.cockpit.location-signal', [
                        'locationInsight' => $locationInsight ?? null,
                    ])

                    {{-- 🧠 MIE v3: AI Advisor Insight --}}
                    @include('admin.ilanlar.components.cockpit.advisor-insight', [
                        'advisorInsight' => $advisorInsight ?? null,
                        'pricingInsight' => $pricingInsight ?? null,
                        'ilan' => $ilan,
                    ])

                    {{-- 👤 Client Information & CRM --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex justify-between items-center">
                            <div class="flex items-center gap-2">
                                <x-icon name="kullanici" class="w-4 h-4 text-[#C9A84C]" />
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Müşteri Bilgileri</h3>
                            </div>
                            <span
                                class="px-2 py-0.5 bg-emerald-100 dark:bg-emerald-900/30 text-emerald-700 dark:text-emerald-400 text-xs font-bold rounded border border-emerald-200 dark:border-emerald-800">Aktif</span>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.social-crm')
                        </div>
                    </section>

                    {{-- 📜 Audit Logs & Archive --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl overflow-hidden shadow-sm dark:shadow-none">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50/50 dark:bg-slate-800/40 flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <x-icon name="kalkan" class="w-4 h-4 text-[#C9A84C]" />
                                <h3 class="text-sm font-bold text-gray-900 dark:text-white">Kayıt ve Arşiv</h3>
                            </div>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.logs-vault')
                        </div>
                    </section>
                </div>
            </div>

            {{-- 🎯 Potansiyel Alıcılar --}}
            @if (!empty($potentialBuyers) && count($potentialBuyers) > 0)
                <section class="pt-8 border-t border-gray-200 dark:border-slate-800">
                    <div class="flex items-center justify-between gap-4 mb-6">
                        <div class="flex items-center gap-4">
                            <div
                                class="p-3 bg-emerald-100 dark:bg-emerald-950/40 text-emerald-600 dark:text-emerald-400 rounded-xl border border-emerald-200 dark:border-emerald-800/60 shadow-sm">
                                <x-icon name="kullanicilar" class="w-6 h-6" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-white">Eşleşmiş Alıcılar</h2>
                                <p class="text-xs text-gray-500 dark:text-slate-400 font-medium mt-0.5">
                                    Portföydeki aktif taleplerden {{ count($potentialBuyers) }} kişi eşleşti</p>
                            </div>
                        </div>
                        <span class="px-3 py-1 bg-[#C9A84C]/15 text-[#C9A84C] text-xs font-bold rounded-lg border border-[#C9A84C]/30">
                            AI Match Matrix
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($potentialBuyers as $match)
                            @php
                                $talep = $match['talep'];
                                $kisi = $talep->kisi;
                                $score = $match['yuzde'];
                                $scoreColor = $score >= 90 ? 'emerald' : ($score >= 80 ? 'blue' : 'amber');
                                $phoneClean = preg_replace('/[^0-9]/', '', $kisi->telefon ?: ($kisi->gsm ?? ''));
                                if (str_starts_with($phoneClean, '0')) {
                                    $phoneClean = '90' . substr($phoneClean, 1);
                                } elseif (!str_starts_with($phoneClean, '90') && strlen($phoneClean) === 10) {
                                    $phoneClean = '90' . $phoneClean;
                                }
                                $pitchText = "Merhaba " . ($kisi->ad ?? 'Değerli Müşterimiz') . ",\n\n"
                                    . "Yalıhan Emlak portföyümüze yeni eklenen '" . ($ilan->baslik) . "' (" . ($ilan->kisa_referans ?: '#' . $ilan->id) . ") ilanımız kriterlerinizle %" . $score . " oranında eşleşmiştir.\n\n"
                                    . "Fiyat: " . number_format($ilan->fiyat) . " " . $ilan->para_birimi . "\n"
                                    . "Lokasyon: " . ($ilan->mahalle?->mahalle_adi ? $ilan->mahalle->mahalle_adi . ', ' : '') . ($ilan->ilce?->ilce_adi ?: 'Bodrum') . "\n\n"
                                    . "Detaylı sunum ve yer gösterimi için benimle iletişime geçebilirsiniz.";
                                $waUrl = "https://wa.me/" . $phoneClean . "?text=" . rawurlencode($pitchText);
                            @endphp
                            <div
                                class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-xl p-5 hover:border-[#C9A84C]/50 transition-all group shadow-sm flex flex-col justify-between">
                                <div>
                                    <div class="flex items-center gap-3.5 mb-4">
                                        <div
                                            class="w-11 h-11 rounded-full bg-{{ $scoreColor }}-100 dark:bg-{{ $scoreColor }}-900/30 flex items-center justify-center text-{{ $scoreColor }}-600 dark:text-{{ $scoreColor }}-400 font-bold border border-{{ $scoreColor }}-200 dark:border-{{ $scoreColor }}-800 text-sm shrink-0">
                                            {{ mb_substr($kisi->ad ?? 'M', 0, 1) }}{{ mb_substr($kisi->soyad ?? '', 0, 1) }}
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <h4 class="text-sm font-bold text-gray-900 dark:text-white truncate">
                                                {{ $kisi->ad ?? 'İsimsiz' }} {{ $kisi->soyad ?? '' }}
                                            </h4>
                                            <p class="text-xs font-medium text-gray-500 dark:text-slate-400">
                                                {{ $match['kategori'] ?? 'Alıcı Talebi' }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            <span class="text-lg font-black text-{{ $scoreColor }}-600 dark:text-{{ $scoreColor }}-400">%{{ $score }}</span>
                                        </div>
                                    </div>

                                    {{-- İletişim Bilgileri --}}
                                    <div class="space-y-1.5 text-xs text-gray-600 dark:text-slate-400 mb-4 bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-lg border border-slate-200/60 dark:border-slate-700/60">
                                        @if($kisi->telefon || $kisi->gsm)
                                            <div class="flex items-center gap-2">
                                                <x-icon name="telefon" class="w-3.5 h-3.5 text-[#C9A84C]" />
                                                <span class="font-mono text-gray-900 dark:text-slate-200">{{ $kisi->telefon ?: $kisi->gsm }}</span>
                                            </div>
                                        @endif
                                        @if($kisi->eposta)
                                            <div class="flex items-center gap-2 truncate">
                                                <x-icon name="posta" class="w-3.5 h-3.5 text-[#C9A84C]" />
                                                <span class="truncate">{{ $kisi->eposta }}</span>
                                            </div>
                                        @endif
                                        @if($talep->butce_max ?? false)
                                            <div class="flex items-center justify-between pt-1 border-t border-slate-200/60 dark:border-slate-700/60 text-[11px]">
                                                <span>Bütçe Aralığı:</span>
                                                <span class="font-bold text-gray-900 dark:text-white">{{ number_format($talep->butce_min ?? 0) }} - {{ number_format($talep->butce_max) }} {{ $talep->para_birimi ?? '₺' }}</span>
                                            </div>
                                        @endif
                                    </div>
                                </div>

                                {{-- Aksiyonlar --}}
                                <div class="grid grid-cols-3 gap-2 pt-2 border-t border-gray-100 dark:border-slate-800">
                                    @if($phoneClean)
                                        <a href="{{ $waUrl }}" target="_blank"
                                           class="inline-flex items-center justify-center gap-1 py-2 px-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold rounded-lg transition-all shadow-sm"
                                           title="WhatsApp ile Portföy Gönder">
                                            <x-icon name="mesaj" class="w-3.5 h-3.5" />
                                            <span>WhatsApp</span>
                                        </a>
                                        <a href="tel:{{ $kisi->telefon ?: $kisi->gsm }}"
                                           class="inline-flex items-center justify-center gap-1 py-2 px-2 bg-[#0A1628] hover:bg-[#112240] text-[#C9A84C] border border-[#C9A84C]/40 text-xs font-bold rounded-lg transition-all"
                                           title="Doğrudan Ara">
                                            <x-icon name="telefon" class="w-3.5 h-3.5" />
                                            <span>Ara</span>
                                        </a>
                                    @endif
                                    <button type="button"
                                            @click="copyToClipboard({{ json_encode($pitchText) }}, 'Sunum metni kopyalandı 📋')"
                                            class="inline-flex items-center justify-center gap-1 py-2 px-2 bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-xs font-bold rounded-lg transition-all border border-slate-200 dark:border-slate-700 col-span-{{ $phoneClean ? 1 : 3 }}"
                                            title="Özel Sunum Metnini Kopyala">
                                        <x-icon name="kopyala" class="w-3.5 h-3.5" />
                                        <span>{{ $phoneClean ? 'Kopyala' : 'Metni Kopyala' }}</span>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>

        {{-- Toast Notifications (Mediterranean Luxury Dark Navy + Gold) --}}
        <div class="fixed bottom-6 right-6 z-[9999] space-y-3 pointer-events-none">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.show" x-transition
                    class="bg-[#0A1628] text-slate-100 px-5 py-3 rounded-xl shadow-2xl border border-[#C9A84C]/40 flex items-center gap-3 pointer-events-auto">
                    <span class="w-2 h-2 rounded-full bg-[#C9A84C] shrink-0"></span>
                    <span x-text="toast.message" class="text-xs font-bold"></span>
                </div>
            </template>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function cockpitManager(ilanId) {
            return {
                ilanId: ilanId,
                currentTab: 'radar',
                processing: false,
                toasts: [],
                tabs: [{
                        id: 'radar',
                        label: 'Radar',
                        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 2v2m0 16v2m10-10h-2M4 12H2m15.07-7.07l-1.41 1.41M7.41 16.59l-1.41 1.41m0-12.12l1.41 1.41m9.19 9.19l1.41 1.41M12 12m-3 0a3 3 0 1 0 6 0 3 3 0 1 0-6 0" stroke-width="2" stroke-linecap="round"/></svg>'
                    },
                    {
                        id: 'data',
                        label: 'Veriler',
                        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 7v10c0 1.1.9 2 2 2h12a2 2 0 0 0 2-2V7a2 2 0 0 0-2-2H6a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2zM9 5v14M15 5v14M4 11h16M4 15h16" stroke-width="2" stroke-linecap="round"/></svg>'
                    },
                    {
                        id: 'social',
                        label: 'Sosyal',
                        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2M9 7a4 4 0 1 1 0-8 4 4 0 0 1 0 8zm14 14v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" stroke-width="2" stroke-linecap="round"/></svg>'
                    },
                    {
                        id: 'logs',
                        label: 'Arşiv',
                        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M12 15V3m0 12l-4-4m4 4l4-4M2 17l.62 2.48A2 2 0 0 0 4.56 21h14.88a2 2 0 0 0 1.94-1.51L22 17" stroke-width="2" stroke-linecap="round"/></svg>'
                    },
                    {
                        id: 'gallery',
                        label: 'Galeri',
                        icon: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" stroke-width="2" stroke-linecap="round"/></svg>'
                    }
                ],

                copyToClipboard(text, message) {
                    navigator.clipboard.writeText(text).then(() => {
                        this.addToast(message);
                    }).catch(err => {
                        this.addToast('🛑 Metin kopyalanamadı');
                    });
                },

                addToast(message) {
                    const id = Date.now();
                    this.toasts.push({
                        id,
                        message,
                        show: true
                    });
                    setTimeout(() => {
                        const index = this.toasts.findIndex(t => t.id === id);
                        if (index > -1) this.toasts[index].show = false;
                        setTimeout(() => {
                            this.toasts = this.toasts.filter(t => t.id !== id);
                        }, 500);
                    }, 3000);
                },

                async publishViaGate() {
                    this.processing = true;
                    this.addToast('Hermes Yayın Denetimi Başlatılıyor... 🛡️');
                    try {
                        const response = await fetch(`/admin/ilanlar/${this.ilanId}/publish`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                override: false
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.addToast('İlan başarıyla yayına alındı! 🎉');
                            setTimeout(() => location.reload(), 1200);
                        } else {
                            this.addToast('Yayın Uyarısı: ' + (data.message || 'Eksik alanlar var.'));
                        }
                    } catch (error) {
                        this.addToast('🛑 Yayın Hatası: İletişim kurulamadı.');
                    } finally {
                        this.processing = false;
                    }
                },

                async togglePublish() {
                    this.processing = true;
                    this.addToast('Durum güncelleniyor...');
                    try {
                        const response = await fetch(`/admin/ilanlar/${this.ilanId}/yayin-durumu-toggle`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'Accept': 'application/json'
                            }
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.addToast('Yayın durumu güncellendi! ✅');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            this.addToast(data.message || 'İşlem başarısız.');
                        }
                    } catch (error) {
                        this.addToast('🛑 Sunucu hatası oluştu.');
                    } finally {
                        this.processing = false;
                    }
                },

                async analyzeWithAI() {
                    this.processing = true;
                    this.addToast('Cortex Analizi Başlatıldı... ⚡');
                    try {
                        const response = await fetch(`/admin/ilanlar/ai/bulk-analyze`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ilan_ids: [this.ilanId],
                                type: 'comprehensive'
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            this.addToast('Analiz Verisi Güncellendi! 🧠');
                            setTimeout(() => location.reload(), 2000);
                        }
                    } catch (error) {
                        this.addToast('AI İletişim Hatası! 🛑');
                    } finally {
                        this.processing = false;
                    }
                }
            }
        }

        function buyersModal() {
            return {
                showModal: false,
                loading: false,
                message: '',
                async openModal(talepId) {
                    this.showModal = true;
                    this.loading = true;
                    try {
                        const response = await fetch(
                            `/api/admin/ilanlar/{{ $ilan->id }}/generate-buyer-message/${talepId}`);
                        const data = await response.json();
                        this.message = data.success ? data.message : "Comms failure.";
                    } catch (e) {
                        this.message = "Signal lost.";
                    } finally {
                        this.loading = false;
                    }
                }
            };
        }

        document.addEventListener('DOMContentLoaded', function() {
            if (!document.querySelector("#cockpitPriceChart")) return;

            const options = {
                series: [{
                    name: 'Fiyat',
                    data: [{
                            x: 'Bölge Ort.',
                            y: {{ $marketData['avg_price'] ?? 0 }}
                        },
                        {
                            x: 'Payload',
                            y: {{ $ilan->fiyat }}
                        }
                    ]
                }],
                chart: {
                    type: 'bar',
                    height: 220,
                    toolbar: {
                        show: false
                    },
                    background: 'transparent'
                },
                theme: {
                    mode: 'dark'
                },
                colors: ['#334155', '#6366f1'],
                plotOptions: {
                    bar: {
                        borderRadius: 8,
                        columnWidth: '40%',
                        distributed: true
                    }
                },
                grid: {
                    borderColor: '#1e293b',
                    strokeDashArray: 4
                },
                xaxis: {
                    labels: {
                        style: {
                            colors: '#64748b',
                            fontWeight: 900
                        }
                    }
                },
                yaxis: {
                    labels: {
                        show: false
                    }
                },
                dataLabels: {
                    enabled: true,
                    formatter: val => val.toLocaleString() + ' ₺'
                }
            };

            new ApexCharts(document.querySelector("#cockpitPriceChart"), options).render();
        });
    </script>
@endpush
