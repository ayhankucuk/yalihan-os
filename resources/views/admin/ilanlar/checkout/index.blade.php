@extends('admin.layouts.admin')

@section('title', 'Checkout - ' . $ilan->baslik)

@section('content')
    <div class="container-fluid px-4 py-6" x-data="checkoutApp()">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Checkout / Ödeme</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $ilan->baslik }} · Rezervasyon #{{ $reservation->id }}
                </p>
            </div>
            <a href="{{ route('admin.ilanlar.calendar', $ilan) }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors">
                ← Takvime Dön
            </a>
        </div>

        {{-- Flash messages --}}
        @if (session('success'))
            <div
                class="mb-6 px-4 py-3 rounded-lg bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 text-green-800 dark:text-green-200 text-sm">
                {{ session('success') }}
            </div>
        @endif
        @if (session('error'))
            <div
                class="mb-6 px-4 py-3 rounded-lg bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-800 dark:text-red-200 text-sm">
                {{ session('error') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Left: Reservation summary + payment form --}}
            <div class="lg:col-span-2 space-y-6">
                {{-- Reservation summary --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Rezervasyon Özeti</h3>
                    <dl class="grid grid-cols-2 gap-4 text-sm">
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Misafir</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $reservation->guest_name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">İletişim</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $reservation->guest_phone ?? '—' }}
                                @if ($reservation->guest_email)
                                    <span class="text-gray-500">· {{ $reservation->guest_email }}</span>
                                @endif
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Giriş</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $reservation->start_date ? \Carbon\Carbon::parse($reservation->start_date)->format('d.m.Y') : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Çıkış</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">
                                {{ $reservation->end_date ? \Carbon\Carbon::parse($reservation->end_date)->format('d.m.Y') : '—' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Gece</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $reservation->nights }}</dd>
                        </div>
                        <div>
                            <dt class="text-gray-500 dark:text-gray-400">Misafir Sayısı</dt>
                            <dd class="font-medium text-gray-900 dark:text-white">{{ $reservation->guest_count ?? '—' }}
                            </dd>
                        </div>
                    </dl>

                    <div class="mt-6 pt-4 border-t border-gray-200 dark:border-gray-700">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Toplam Tutar</span>
                            <span class="text-lg font-bold text-gray-900 dark:text-white">
                                {{ number_format($totalAmount, 2, ',', '.') }} {{ $currency }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Ödenen</span>
                            <span class="font-medium text-green-600 dark:text-green-400">
                                {{ number_format($paidTotal, 2, ',', '.') }} {{ $currency }}
                            </span>
                        </div>
                        <div class="flex justify-between items-center mt-2">
                            <span class="text-sm text-gray-500 dark:text-gray-400">Kalan</span>
                            <span
                                class="font-medium {{ $outstanding > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white' }}">
                                {{ number_format($outstanding, 2, ',', '.') }} {{ $currency }}
                            </span>
                        </div>
                        @if ($isFullyPaid)
                            <div
                                class="mt-4 px-3 py-2 rounded-lg bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-200 text-sm font-medium">
                                ✓ Ödeme tamamlandı
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Payment form --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Yeni Ödeme Kaydı</h3>
                    <form method="POST" action="{{ route('admin.ilanlar.checkout.store', [$ilan, $reservation]) }}"
                        class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1">Tutar
                                    *</label>
                                <input type="number" name="amount" step="0.01" min="0.01" required
                                    value="{{ old('amount', $outstanding > 0 ? number_format($outstanding, 2, '.', '') : '') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                @error('amount')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Para
                                    Birimi</label>
                                <input type="text" name="currency" maxlength="3"
                                    value="{{ old('currency', $currency) }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white uppercase">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Ödeme Yöntemi
                                    *</label>
                                <select name="payment_method" required
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                                    <option value="mock" {{ old('payment_method') === 'mock' ? 'selected' : '' }}>Mock
                                        (Manuel)</option>
                                    <option value="kart" {{ old('payment_method') === 'kart' ? 'selected' : '' }}>Kredi
                                        Kartı</option>
                                    <option value="eft" {{ old('payment_method') === 'eft' ? 'selected' : '' }}>EFT
                                    </option>
                                    <option value="havale" {{ old('payment_method') === 'havale' ? 'selected' : '' }}>
                                        Havale</option>
                                    <option value="nakit" {{ old('payment_method') === 'nakit' ? 'selected' : '' }}>Nakit
                                    </option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Referans /
                                    Makbuz No</label>
                                <input type="text" name="reference" maxlength="100" value="{{ old('reference') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-200 mb-1">Not</label>
                            <textarea name="notes" rows="2" maxlength="1000"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('notes') }}</textarea>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                Ödeme Kaydı Oluştur
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Right: Payment history --}}
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 h-fit">
                <h3 class="font-semibold text-gray-900 dark:text-white mb-4">Ödeme Geçmişi</h3>
                @if ($payments->isEmpty())
                    <p class="text-sm text-gray-500 dark:text-gray-400">Henüz ödeme kaydı yok.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($payments as $payment)
                            <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-3">
                                <div class="flex justify-between items-start">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-white">
                                            {{ number_format($payment->amount, 2, ',', '.') }} {{ $payment->currency }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                            {{ ucfirst($payment->payment_method) }} ·
                                            {{ $payment->created_at?->format('d.m.Y H:i') }}
                                        </div>
                                        @if ($payment->reference)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">Ref:
                                                {{ $payment->reference }}</div>
                                        @endif
                                        @if ($payment->notes)
                                            <div
                                                class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 whitespace-pre-line">
                                                {{ $payment->notes }}</div>
                                        @endif
                                    </div>
                                    <span
                                        class="text-xs font-medium px-2 py-1 rounded-full
                                        {{ $payment->status === 'paid' ? 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300' : '' }}
                                        {{ $payment->status === 'pending' ? 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300' : '' }}
                                        {{ $payment->status === 'failed' ? 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300' : '' }}">
                                        {{ match ($payment->status) {'paid' => 'Onaylandı','pending' => 'Bekliyor','failed' => 'Başarısız',default => $payment->status} }}
                                    </span>
                                </div>
                                @if ($payment->status === 'pending')
                                    <div class="flex gap-2 mt-3">
                                        <form method="POST"
                                            action="{{ route('admin.ilanlar.checkout.approve', [$ilan, $reservation, $payment]) }}">
                                            @csrf
                                            <button type="submit"
                                                class="px-3 py-1.5 text-xs bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                                                Onayla
                                            </button>
                                        </form>
                                        <form method="POST"
                                            action="{{ route('admin.ilanlar.checkout.fail', [$ilan, $reservation, $payment]) }}"
                                            x-data="{ showReason: false }">
                                            @csrf
                                            <template x-if="showReason">
                                                <input type="text" name="reason" placeholder="Başarısızlık nedeni"
                                                    class="w-40 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white mr-2">
                                            </template>
                                            <button type="button" @click="showReason = !showReason"
                                                class="px-3 py-1.5 text-xs bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                                                Başarısız
                                            </button>
                                            <button type="submit" x-show="showReason" x-cloak
                                                class="px-3 py-1.5 text-xs bg-red-700 text-white rounded-lg hover:bg-red-800 transition-colors">
                                                Kaydet
                                            </button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function checkoutApp() {
            return {};
        }
    </script>
@endpush
