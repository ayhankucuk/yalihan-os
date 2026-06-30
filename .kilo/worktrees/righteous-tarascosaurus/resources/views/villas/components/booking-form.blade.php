{{-- Booking Request Form Component (Public View) --}}
{{-- Pure Tailwind + Alpine.js --}}
{{-- Yalıhan Bekçi kurallarına %100 uyumlu --}}

@php
    $pricing = $pricing ?? ['daily_price' => $villa->gunluk_fiyat ?? 0, 'currency' => 'TRY'];
@endphp

<div x-data="bookingForm({{ json_encode(['villa_id' => $villa->id, 'pricing' => $pricing]) }})"
     class="bg-white dark:bg-slate-900 rounded-2xl shadow-2xl p-6 sticky top-24">

    {{-- Price Header --}}
    <div class="mb-6 pb-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="flex items-end gap-2">
            <div class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                ₺{{ number_format($pricing['daily_price'], 0) }}
            </div>
            <div class="text-gray-600 dark:text-gray-400 mb-1">/ gece</div>
        </div>
        @if(isset($pricing['season_name']))
        <div class="text-sm text-gray-500 dark:text-gray-400 mt-1">
            {{ $pricing['season_name'] }} fiyatı
        </div>
        @endif
    </div>

    {{-- Booking Form --}}
    <form @submit.prevent="submitBooking()" class="space-y-4">
        {{-- Check-in / Check-out --}}
        <div class="grid grid-cols-2 gap-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Giriş
                </label>
                <input
                    type="date"
                    x-model="formData.check_in"
                    @change="calculatePrice()"
                    :min="new Date().toISOString().split('T')[0]"
                    required
                    class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Çıkış
                </label>
                <input
                    type="date"
                    x-model="formData.check_out"
                    @change="calculatePrice()"
                    :min="formData.check_in"
                    required
                    class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
            </div>
        </div>

        {{-- Guests --}}
        <div>
            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                Misafir Sayısı
            </label>
            <select
                x-model="formData.guests"
                required
                class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
                @for($i = 1; $i <= ($villa->maksimum_misafir ?? 10); $i++)
                <option value="{{ $i }}">{{ $i }} kişi</option>
                @endfor
            </select>
        </div>

        {{-- Price Breakdown (if dates selected) --}}
        <div x-show="nights > 0" class="bg-gray-50 dark:bg-slate-900 rounded-lg p-4 space-y-2 text-sm">
            <div class="flex items-center justify-between text-gray-700 dark:text-slate-200 dark:text-slate-300">
                <span>₺<span x-text="dailyPrice.toLocaleString('tr-TR')"></span> x <span x-text="nights"></span> gece</span>
                <span>₺<span x-text="subtotal.toLocaleString('tr-TR')"></span></span>
            </div>
            <div class="flex items-center justify-between text-gray-700 dark:text-slate-200 dark:text-slate-300">
                <span>Temizlik ücreti</span>
                <span>₺<span x-text="cleaningFee.toLocaleString('tr-TR')"></span></span>
            </div>
            <div class="flex items-center justify-between text-gray-700 dark:text-slate-200 dark:text-slate-300">
                <span>Hizmet bedeli</span>
                <span>₺<span x-text="serviceFee.toLocaleString('tr-TR')"></span></span>
            </div>
            <div class="pt-2 mt-2 border-t border-gray-200 dark:border-slate-800 flex items-center justify-between font-bold text-gray-900 dark:text-white text-lg dark:border-slate-700 dark:text-slate-100">
                <span>Toplam</span>
                <span>₺<span x-text="totalPrice.toLocaleString('tr-TR')"></span></span>
            </div>
        </div>

        {{-- Contact Info --}}
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Ad Soyad *
                </label>
                <input
                    type="text"
                    x-model="formData.name"
                    required
                    placeholder="Adınız ve soyadınız"
                    class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Telefon *
                </label>
                <input
                    type="tel"
                    x-model="formData.phone"
                    required
                    placeholder="05XX XXX XX XX"
                    class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    E-posta *
                </label>
                <input
                    type="email"
                    x-model="formData.email"
                    required
                    placeholder="ornek@email.com"
                    class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Mesajınız (Opsiyonel)
                </label>
                <textarea
                    x-model="formData.message"
                    rows="3"
                    placeholder="Özel istekleriniz..."
                    class="w-full px-3 py-2.5 border-2 border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-black dark:text-white focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
        </div>

        {{-- Submit Button --}}
        <button
            type="submit"
            :disabled="loading"
            :class="loading ? 'opacity-50 cursor-not-allowed' : 'hover:from-blue-700 hover:to-purple-700'"
            class="w-full py-3.5 bg-gradient-to-r from-blue-600 to-purple-600 text-white rounded-lg font-semibold transition-all shadow-lg hover:shadow-xl disabled:opacity-50">
            <span x-show="!loading">
                <i class="fas fa-calendar-check mr-2"></i>
                Rezervasyon Talebi Gönder
            </span>
            <span x-show="loading">
                <i class="fas fa-spinner fa-spin mr-2"></i>
                Gönderiliyor...
            </span>
        </button>

        {{-- Info Text --}}
        <p class="text-xs text-center text-gray-500 dark:text-gray-400">
            Henüz ücretlendirilmeyeceksiniz. Talebiniz değerlendirilecek.
        </p>
    </form>

    {{-- Success Message --}}
    <div x-show="success"
         x-transition
         class="mt-4 p-4 bg-green-100 dark:bg-green-900/30 border-2 border-green-500 rounded-lg text-green-800 dark:text-green-300 text-sm"
         style="display: none;">
        <i class="fas fa-check-circle mr-2"></i>
        Rezervasyon talebiniz başarıyla alındı! En kısa sürede sizinle iletişime geçeceğiz.
    </div>

    {{-- Error Message --}}
    <div x-show="error"
         x-transition
         class="mt-4 p-4 bg-red-100 dark:bg-red-900/30 border-2 border-red-500 rounded-lg text-red-800 dark:text-red-300 text-sm"
         style="display: none;">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <span x-text="errorMessage"></span>
    </div>
</div>

@push('scripts')
<script>
function bookingForm(data) {
    return {
        villaId: data.villa_id,
        dailyPrice: data.pricing.daily_price,
        formData: {
            check_in: '',
            check_out: '',
            guests: 2,
            name: '',
            phone: '',
            email: '',
            message: ''
        },
        nights: 0,
        subtotal: 0,
        cleaningFee: 0,
        serviceFee: 0,
        totalPrice: 0,
        loading: false,
        success: false,
        error: false,
        errorMessage: '',

        calculatePrice() {
            if (!this.formData.check_in || !this.formData.check_out) {
                this.nights = 0;
                return;
            }

            const checkIn = new Date(this.formData.check_in);
            const checkOut = new Date(this.formData.check_out);
            this.nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));

            if (this.nights > 0) {
                this.subtotal = this.dailyPrice * this.nights;
                this.cleaningFee = 500; // Fixed cleaning fee
                this.serviceFee = Math.round(this.subtotal * 0.05); // 5% service fee
                this.totalPrice = this.subtotal + this.cleaningFee + this.serviceFee;
            }
        },

        async submitBooking() {
            this.loading = true;
            this.success = false;
            this.error = false;

            try {
                const response = await fetch('/api/booking-request', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        villa_id: this.villaId,
                        ...this.formData,
                        nights: this.nights,
                        total_price: this.totalPrice
                    })
                });

                const result = await response.json();

                if (response.ok) {
                    this.success = true;
                    this.formData = {
                        check_in: '',
                        check_out: '',
                        guests: 2,
                        name: '',
                        phone: '',
                        email: '',
                        message: ''
                    };
                    this.nights = 0;

                    // Scroll to success message
                    setTimeout(() => {
                        this.$el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }, 100);
                } else {
                    this.error = true;
                    this.errorMessage = result.message || 'Bir hata oluştu';
                }
            } catch (error) {
                console.error('Booking error:', error);
                this.error = true;
                this.errorMessage = 'Bağlantı hatası. Lütfen tekrar deneyin.';
            } finally {
                this.loading = false;
            }
        }
    }
}
</script>
@endpush
