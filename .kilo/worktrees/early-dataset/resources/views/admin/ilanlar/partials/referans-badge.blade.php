{{--
    Referans Badge Component

    Gemini AI Önerisi: 3 Katmanlı Referans Sistemi
    - Kısa Referans: Müşteri görür (Ref: 001)
    - Orta Referans: Danışman hover'da görür (kopyalanabilir)
    - Uzun Referans: Dosya adı (sistem kullanır)

    Context7: REFNOMATİK Sistemi
    Yalıhan Bekçi: Frontend UX Optimization

    Usage:
    @include('admin.ilanlar.partials.referans-badge', ['ilan' => $ilan])
--}}

@php
    $kisaRef = $ilan->kisa_referans ?? '000';
    $ortaRef = $ilan->orta_referans ?? 'Referans bilgisi yok';
    $uzunRef = $ilan->uzun_referans ?? '';
    $tamRef = $ilan->referans_no ?? '';
@endphp

<div class="group relative inline-block">
    {{-- KISA REFERANS BADGE (Müşteri görür) --}}
    <span
        class="inline-flex items-center gap-2 px-3 py-1.5 bg-gradient-to-r from-blue-500 to-blue-600 text-white rounded-lg text-sm font-semibold shadow-md hover:shadow-lg transition-all duration-200 cursor-pointer dark:shadow-none"
        onclick="copyReferansToClipboard('{{ $ortaRef }}', this, 'orta')">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z">
            </path>
        </svg>
        Ref: {{ $kisaRef }}
    </span>

    {{-- HOVER TOOLTIP (Danışman görür - Uzun Referans) --}}
    <div
        class="absolute z-50 hidden group-hover:block left-0 top-full mt-2 w-[400px] bg-gray-900 text-white text-sm rounded-lg shadow-2xl p-4 transition-all duration-200 animate-fade-in">
        {{-- Tam Referans --}}
        <div class="mb-3 pb-3 border-b border-gray-700">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Tam Referans</span>
                <button onclick="copyReferansToClipboard('{{ $tamRef }}', this, 'tam'); event.stopPropagation();"
                    class="px-2 py-1 bg-gray-700 hover:bg-gray-600 rounded text-xs transition-colors duration-200">
                    📋 Kopyala
                </button>
            </div>
            <p class="font-mono text-xs text-gray-300 break-all bg-gray-800 px-2 py-1 rounded">{{ $tamRef }}</p>
        </div>

        {{-- Orta Referans (Kopyalanabilir) --}}
        <div class="mb-3 pb-3 border-b border-gray-700">
            <div class="flex items-center justify-between mb-1">
                <span class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Detay Bilgisi</span>
                <button
                    onclick="copyReferansToClipboard('{{ $ortaRef }}', this, 'detay'); event.stopPropagation();"
                    class="px-2 py-1 bg-blue-600 hover:bg-blue-500 rounded text-xs transition-colors duration-200">
                    📋 Kopyala
                </button>
            </div>
            <p class="font-semibold text-sm leading-relaxed">{{ $ortaRef }}</p>
        </div>

        {{-- Dosya Adı --}}
        @if ($uzunRef)
            <div class="mb-3">
                <div class="flex items-center justify-between mb-1">
                    <span class="text-xs text-gray-400 uppercase tracking-wide font-semibold">Dosya Adı</span>
                    <button
                        onclick="copyReferansToClipboard('{{ $uzunRef }}', this, 'dosya'); event.stopPropagation();"
                        class="px-2 py-1 bg-green-600 hover:bg-green-500 rounded text-xs transition-colors duration-200">
                        📁 Kopyala
                    </button>
                </div>
                <p class="font-mono text-xs text-gray-300 break-all bg-gray-800 px-2 py-1 rounded leading-relaxed">
                    {{ $uzunRef }}</p>
            </div>
        @endif

        {{-- Hızlı Eylemler --}}
        <div class="flex gap-2 pt-2 border-t border-gray-700">
            <button onclick="copyReferansToClipboard('{{ $ortaRef }}', this, 'detay'); event.stopPropagation();"
                class="flex-1 px-3 py-2 bg-blue-600 hover:bg-blue-500 rounded text-xs font-medium transition-colors duration-200 flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M8 5H6a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2v-1M8 5a2 2 0 002 2h2a2 2 0 002-2M8 5a2 2 0 012-2h2a2 2 0 012 2m0 0h2a2 2 0 012 2v3m2 4H10m0 0l3-3m-3 3l3 3">
                    </path>
                </svg>
                Detayı Kopyala
            </button>
            @if ($uzunRef)
                <button
                    onclick="copyReferansToClipboard('{{ $uzunRef }}', this, 'dosya'); event.stopPropagation();"
                    class="flex-1 px-3 py-2 bg-green-600 hover:bg-green-500 rounded text-xs font-medium transition-colors duration-200 flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z">
                        </path>
                    </svg>
                    Dosya Adı
                </button>
            @endif
        </div>

        {{-- Tooltip Arrow --}}
        <div
            class="absolute bottom-full left-4 w-0 h-0 border-l-[8px] border-r-[8px] border-b-[8px] border-l-transparent border-r-transparent border-b-gray-900">
        </div>
    </div>
</div>

{{-- Toast Notification Container --}}
<div id="referans-toast-container" class="fixed top-4 right-4 z-[100] space-y-2"></div>

@once
    @push('scripts')
        <script>
            /**
             * Referans bilgisini clipboard'a kopyala ve toast göster
             *
             * Gemini AI Önerisi: Kopyalama özelliği ile danışman verimliliği
             * Context7: Modern clipboard API kullanımı
             */
            function copyReferansToClipboard(text, button, type = 'genel') {
                // Clipboard API ile kopyala
                navigator.clipboard.writeText(text).then(() => {
                    // Success toast göster
                    showReferansToast('✅ Kopyalandı!', text, 'success', type);

                    // Button feedback (eğer button element varsa)
                    if (button && button.tagName) {
                        const originalHTML = button.innerHTML;
                        const originalClass = button.className;

                        button.innerHTML = '✅ Kopyalandı!';
                        button.classList.add('bg-green-500');

                        setTimeout(() => {
                            button.innerHTML = originalHTML;
                            button.className = originalClass;
                        }, 1500);
                    }

                    console.log('✅ Referans kopyalandı:', text);
                }).catch(err => {
                    // Error toast göster
                    showReferansToast('❌ Kopyalama Başarısız', 'Lütfen tekrar deneyin', 'error');
                    console.error('❌ Kopyalama hatası:', err);
                });
            }

            /**
             * Toast notification göster
             */
            function showReferansToast(title, message, type = 'success', referansType = '') {
                const container = document.getElementById('referans-toast-container');
                if (!container) return;

                const toast = document.createElement('div');
                const bgColor = type === 'success' ? 'bg-green-500' : 'bg-red-500';
                const icon = type === 'success' ? '✅' : '❌';

                // Referans tipi badge
                const typeBadge = referansType ?
                    `<span class="px-2 py-0.5 bg-white/20 rounded text-xs">${referansType}</span> dark:bg-slate-900/20` : '';

                toast.className =
                    `${bgColor} text-white px-4 py-3 rounded-lg shadow-2xl flex items-start gap-3 min-w-[300px] max-w-[400px] transform transition-all duration-300 translate-x-0 opacity-100`;
                toast.innerHTML = `
        <span class="text-2xl flex-shrink-0">${icon}</span>
        <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2 mb-1">
                <p class="font-semibold text-sm">${title}</p>
                ${typeBadge}
            </div>
            <p class="text-xs opacity-90 break-all line-clamp-2">${message}</p>
        </div>
        <button onclick="this.parentElement.remove()" class="text-white/80 hover:text-white flex-shrink-0 ml-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
        </button>
    `;

                container.appendChild(toast);

                // Animasyon: Slide in
                setTimeout(() => {
                    toast.style.transform = 'translateX(0)';
                    toast.style.opacity = '1';
                }, 10);

                // 5 saniye sonra otomatik kaldır
                setTimeout(() => {
                    toast.style.transform = 'translateX(100%)';
                    toast.style.opacity = '0';
                    setTimeout(() => {
                        if (toast.parentNode) {
                            toast.remove();
                        }
                    }, 300);
                }, 5000);
            }

            // Global scope'a ekle
            window.copyReferansToClipboard = copyReferansToClipboard;
            window.showReferansToast = showReferansToast;
        </script>

        <style>
            /* Fade-in animation */
            @keyframes fadeIn {
                from {
                    opacity: 0;
                    transform: translateY(-10px);
                }

                to {
                    opacity: 1;
                    transform: translateY(0);
                }
            }

            .animate-fade-in {
                animation: fadeIn 0.2s ease-out;
            }

            /* Line clamp utility */
            .line-clamp-2 {
                display: -webkit-box;
                -webkit-line-clamp: 2;
                -webkit-box-orient: vertical;
                overflow: hidden;
            }
        </style>
    @endpush
@endonce
