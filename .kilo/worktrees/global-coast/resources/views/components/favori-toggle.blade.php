{{-- Context7: İlan Favori Toggle Butonu --}}
{{-- Dark mode uyumlu Tailwind CSS --}}
{{-- Kullanım: @include('components.favori-toggle', ['ilan' => $ilan]) --}}

<div class="favori-toggle-wrapper" data-ilan-id="{{ $ilan->id }}">
    @auth
        <button
            class="favori-toggle-btn inline-flex items-center justify-center gap-2 
                   px-4 py-2 rounded-lg font-medium transition-all duration-200
                   bg-white dark:bg-gray-800 
                   text-gray-900 dark:text-white
                   border border-gray-200 dark:border-gray-700
                   hover:bg-red-50 dark:hover:bg-red-900/20
                   hover:border-red-300 dark:hover:border-red-700
                   active:scale-95
                   focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900"
            data-ilan-id="{{ $ilan->id }}" type="button" aria-label="İlanı favorilere ekle/çıkar">
            <svg class="favori-icon w-5 h-5 transition-all duration-300" data-is-favori="false"
                xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                </path>
            </svg>
            <span class="favori-text">Favorilere Ekle</span>
        </button>

        {{-- AI Tavsiyesi: IlanVisionService entegrasyonu --}}
        {{-- Eğer fotoğraf analizi önerisi varsa göster --}}
        @if (isset($vision_suggestion) && $vision_suggestion)
            <div class="mt-2 p-3 rounded-lg bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700">
                <div class="flex items-start gap-2">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="currentColor"
                        viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z"
                            clip-rule="evenodd" />
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-900 dark:text-blue-200">
                            {{ $vision_suggestion['badge_message'] ?? 'AI Tavsiyesi' }}
                        </p>
                        <p class="text-xs text-blue-800 dark:text-blue-300 mt-1">
                            {{ $vision_suggestion['cta_message'] ?? 'Bu fotoğraf ilanınızın tıklanma oranını %40 artırabilir!' }}
                        </p>
                    </div>
                </div>
            </div>
        @endif
    @else
        {{-- Giriş yapmamış kullanıcı --}}
        <a href="{{ route('login') }}"
            class="inline-flex items-center justify-center gap-2 
                   px-4 py-2 rounded-lg font-medium transition-all duration-200
                   bg-white dark:bg-gray-800 
                   text-gray-900 dark:text-white
                   border border-gray-200 dark:border-gray-700
                   hover:bg-gray-100 dark:hover:bg-gray-700
                   active:scale-95">
            <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                stroke-width="2">
                <path
                    d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z">
                </path>
            </svg>
            <span>Giriş Yapıp Favorilere Ekle</span>
        </a>
    @endauth
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.favori-toggle-btn').forEach(btn => {
                btn.addEventListener('click', async function(e) {
                    e.preventDefault();
                    const ilanId = this.dataset.ilanId;
                    const icon = this.querySelector('.favori-icon');
                    const text = this.querySelector('.favori-text');

                    try {
                        const response = await fetch(`/api/v1/ilanlar/${ilanId}/favori`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector(
                                    'meta[name="csrf-token"]')?.content || '',
                            },
                        });

                        const data = await response.json();

                        if (data.success) {
                            const isFavori = data.data.is_aktif;
                            icon.dataset.isFavori = isFavori;
                            text.textContent = isFavori ? 'Favorilerde' : 'Favorilere Ekle';

                            // Icon'u doldur/boşalt
                            if (isFavori) {
                                icon.classList.add('fill-red-500');
                                icon.classList.remove('text-gray-900 dark:text-slate-100');
                            } else {
                                icon.classList.remove('fill-red-500');
                                icon.classList.add('text-gray-900 dark:text-slate-100');
                            }

                            // Toast notifikasyon (opsiyonel)
                            console.log('✅', data.message);
                        } else {
                            console.error('❌', data.message);
                        }
                    } catch (error) {
                        console.error('Favori işlemi hatası:', error);
                    }
                });
            });
        });
    </script>
@endpush
