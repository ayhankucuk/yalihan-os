@extends('admin.layouts.admin')

@section('title', 'İlan Kokpiti | ' . $ilan->kisa_referans)

@section('content')
    <div class="space-y-6" x-data="{ toasts: [] }"
        x-on:show-toast.window="toasts.push({ id: Date.now(), message: $event.detail.message, show: true }); setTimeout(() => { const idx = toasts.findIndex(t => t.id === Date.now()); if(idx > -1) toasts[idx].show = false; }, 3000)">

        {{-- 🛰️ Tactical Vitals (Sticky) --}}
        @include('admin.ilanlar.components.cockpit.vitals', ['ilan' => $ilan])

        <div class="max-w-[1700px] mx-auto p-4 md:p-6 space-y-6">

            {{-- 🎯 SAB Executive Strip: Tek satır karar özeti --}}
            @include('admin.ilanlar.components.cockpit.executive-strip', [
                'actionMode' => $actionMode ?? null,
                'locationInsight' => $locationInsight ?? null,
                'pricingInsight' => $pricingInsight ?? null,
            ])

            {{-- � Trust Breakdown: Karar Dağılımı --}}
            @if (!empty($trustBreakdown))
                <x-market-intelligence.trust-breakdown :data="$trustBreakdown" />
            @endif

            {{-- �🗺️ Unified Intelligence Map: Hero Position --}}
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
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 flex justify-between items-center dark:border-slate-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Bölge Analizi
                            </h3>
                            <span
                                class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs font-medium rounded border border-blue-200 dark:border-blue-800">Canlı</span>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.radar')
                        </div>
                    </section>

                    {{-- 📦 Technical Data Matrix --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 flex justify-between items-center dark:border-slate-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Teknik
                                Özellikler</h3>
                            <span class="text-xs font-medium text-gray-500 dark:text-gray-400">Detaylı Bilgiler</span>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.data-grid')
                        </div>
                    </section>

                    {{-- 🎨 Multimedia Gallery --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 flex justify-between items-center dark:border-slate-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Fotoğraf
                                Galerisi</h3>
                            <span
                                class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $ilan->fotograflar->count() }}
                                Fotoğraf</span>
                        </div>
                        <div class="p-6">
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-5 gap-3">
                                @foreach ($ilan->fotograflar as $photo)
                                    <div
                                        class="group relative aspect-video rounded-lg overflow-hidden border border-gray-200 dark:border-slate-800 bg-gray-100 dark:bg-slate-900 shadow-sm cursor-pointer hover:border-gray-300 dark:hover:border-gray-600 transition-all dark:shadow-none dark:border-slate-700">
                                        <img src="{{ Storage::url($photo->dosya_yolu) }}"
                                            class="w-full h-full object-cover transition-transform duration-700 group-hover:scale-110" />
                                        <div
                                            class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-gray-900/90 py-2 px-3 opacity-0 group-hover:opacity-100 transition-opacity">
                                            <span
                                                class="text-xs font-semibold text-white">{{ $photo->oda_tipi ?? 'Genel' }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                </div>

                {{-- RIGHT COLUMN: CRM, Access & Logs (4 Units) --}}
                <div class="xl:col-span-4 space-y-6">
                    {{-- � MIE v1 Alpha: Pricing Insight --}}
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

                    {{-- �👤 Client Information & CRM --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 flex justify-between items-center dark:border-slate-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Müşteri
                                Bilgileri</h3>
                            <span
                                class="px-2 py-0.5 bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-400 text-xs font-medium rounded border border-green-200 dark:border-green-800">Aktif</span>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.social-crm')
                        </div>
                    </section>

                    {{-- 📜 Audit Logs & Archive --}}
                    <section
                        class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
                        <div
                            class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 bg-gray-50 dark:bg-slate-900 flex justify-between items-center dark:border-slate-700">
                            <h3 class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">Kayıt ve
                                Arşiv</h3>
                        </div>
                        <div class="p-6">
                            @include('admin.ilanlar.components.cockpit.logs-vault')
                        </div>
                    </section>
                </div>
            </div>

            {{-- 🎯 Potansiyel Alıcılar --}}
            @if (!empty($potentialBuyers) && count($potentialBuyers) > 0)
                <section class="pt-12 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <div class="flex items-center gap-4 mb-8">
                        <div
                            class="p-3 bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400 rounded-lg border border-green-200 dark:border-green-800 shadow-sm dark:shadow-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                            </svg>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Eşleşmiş
                                Alıcılar</h2>
                            <p class="text-xs text-gray-600 dark:text-gray-400 font-medium mt-0.5">
                                {{ count($potentialBuyers) }} kişi tespit edildi</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        @foreach ($potentialBuyers as $match)
                            @php
                                $talep = $match['talep'];
                                $kisi = $talep->kisi;
                                $score = $match['yuzde'];
                                $scoreColor = $score >= 90 ? 'emerald' : ($score >= 80 ? 'blue' : 'amber');
                            @endphp
                            <div
                                class="bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg p-6 hover:border-gray-300 dark:hover:border-gray-600 transition-all group shadow-sm dark:shadow-none dark:border-slate-700">
                                <div class="flex items-center gap-4 mb-6">
                                    <div
                                        class="w-12 h-12 rounded-full bg-{{ $scoreColor }}-100 dark:bg-{{ $scoreColor }}-900/30 flex items-center justify-center text-{{ $scoreColor }}-600 dark:text-{{ $scoreColor }}-400 font-bold border border-{{ $scoreColor }}-200 dark:border-{{ $scoreColor }}-800">
                                        {{ mb_substr($kisi->ad, 0, 1) }}{{ mb_substr($kisi->soyad ?? '', 0, 1) }}
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <h4
                                            class="text-sm font-semibold text-gray-900 dark:text-white truncate dark:text-slate-100">
                                            {{ $kisi->ad }} {{ $kisi->soyad }}</h4>
                                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">
                                            {{ $match['kategori'] ?? 'Eşleşme' }}</p>
                                    </div>
                                    <div class="text-right">
                                        <span
                                            class="text-xl font-bold text-{{ $scoreColor }}-600 dark:text-{{ $scoreColor }}-400">{{ $score }}%</span>
                                    </div>
                                </div>

                                <button
                                    class="w-full py-3 bg-{{ $scoreColor }}-600 hover:bg-{{ $scoreColor }}-700 text-white text-xs font-semibold rounded-lg transition-all shadow-md dark:shadow-none">
                                    İletişim Başlat
                                </button>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

        </div>

        {{-- Toast Notifications --}}
        <div class="fixed bottom-6 right-6 z-[9999] space-y-3 pointer-events-none">
            <template x-for="toast in toasts" :key="toast.id">
                <div x-show="toast.show" x-transition
                    class="bg-blue-600 text-white px-6 py-3 rounded-lg shadow-xl border border-blue-500 flex items-center gap-3 pointer-events-auto">
                    <span x-text="toast.message" class="text-sm font-semibold"></span>
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
                        this.addToast('🛑 Arıza: Uplink Hatası');
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

                async sealDraft() {
                    if (!confirm('İlan yayına alınsın mı? (Seal Mission)')) return;
                    this.processing = true;
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
                            this.addToast('Mission Sealed! 🎖️');
                            setTimeout(() => location.reload(), 1500);
                        }
                    } catch (error) {
                        this.addToast('🛑 Seal Failure');
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
