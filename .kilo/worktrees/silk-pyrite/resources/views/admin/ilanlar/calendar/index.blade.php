@extends('admin.layouts.admin')

@section('title', 'Takvim - ' . $ilan->baslik)

@section('content')
    <div class="container-fluid px-4 py-6" x-data="calendarApp()">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Rezervasyon Takvimi</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    {{ $ilan->baslik }}
                </p>
            </div>
            <a href="{{ route('admin.ilanlar.index') }}"
                class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors dark:text-slate-300">
                ← Geri
            </a>
        </div>

        {{-- Month Selector --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 mb-6 dark:shadow-none">
            <form method="GET" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                        Ay Seçin
                    </label>
                    <input type="month" name="month" value="{{ $month }}"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Git
                </button>
            </form>
        </div>

        {{-- ICS Feed (Phase Q) --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 mb-6 dark:shadow-none" x-data="{ copied: false }">
            <h3 class="font-semibold text-gray-900 dark:text-white mb-3 dark:text-slate-100">Takvim Abonelik (ICS)</h3>
            @php
                $feed = $ilan->calendarFeed()->where('aktiflik_durumu', true)->first();
            @endphp
            @if ($feed)
                <div class="space-y-3">
                    <div>
                        <label class="block text-sm text-gray-600 dark:text-gray-400 mb-1">Feed URL:</label>
                        <div class="flex gap-2">
                            <input type="text" readonly id="feedUrl"
                                value="{{ url('/calendar/ilan/' . $feed->token . '.ics') }}"
                                class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white text-sm">
                            <button type="button"
                                @click="navigator.clipboard.writeText(document.getElementById('feedUrl').value); copied = true; setTimeout(() => copied = false, 2000)"
                                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                                <span x-show="!copied">Kopyala</span>
                                <span x-show="copied" x-cloak>✓ Kopyalandı</span>
                            </button>
                        </div>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-xs text-gray-500 dark:text-gray-400">
                            Oluşturuldu: {{ $feed->created_at->format('d.m.Y H:i') }}
                        </span>
                        <form action="{{ route('admin.ilanlar.calendar.feed.revoke', $ilan) }}" method="POST"
                            onsubmit="return confirm('Feed iptal edilsin mi?')">
                            @csrf
                            <button type="submit"
                                class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-xs transition-colors">
                                İptal Et
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <form action="{{ route('admin.ilanlar.calendar.feed.create', $ilan) }}" method="POST">
                    @csrf
                    <button type="submit"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors text-sm">
                        Feed Oluştur
                    </button>
                </form>
            @endif
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Calendar Days --}}
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 dark:shadow-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Günler</h3>
                    <div class="grid grid-cols-7 gap-1">
                        @php
                            $current = $from->copy();
                        @endphp
                        @while ($current->lte($to))
                            @php
                                $isSelected = $current->format('Y-m-d') === $selectedDay;
                                $hasReservation = $reservations
                                    ->filter(function ($r) use ($current) {
                                        return $r->starts_at->lte($current->endOfDay()) &&
                                            $r->ends_at->gte($current->startOfDay());
                                    })
                                    ->isNotEmpty();
                            @endphp
                            <a href="?month={{ $month }}&day={{ $current->format('Y-m-d') }}"
                                class="aspect-square flex items-center justify-center rounded text-sm
                                    {{ $isSelected ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-900 dark:text-white hover:bg-gray-200 dark:hover:bg-gray-600' }}
                                    {{ $hasReservation ? 'ring-2 ring-green-500' : '' }}
                                    transition-colors">
                                {{ $current->format('d') }}
                            </a>
                            @php
                                $current->addDay();
                            @endphp
                        @endwhile
                    </div>
                </div>
            </div>

            {{-- Availability Slots --}}
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 mb-6 dark:shadow-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                        Müsaitlik ({{ \Carbon\Carbon::parse($selectedDay)->format('d.m.Y') }})
                    </h3>
                    <div class="grid grid-cols-4 gap-2 max-h-96 overflow-auto">
                        @foreach ($availability as $slot)
                            @php
                                $bgClass = match ($slot['reason']) {
                                    'free' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                    'reserved'
                                        => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200 cursor-pointer',
                                    'past' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                    default => 'bg-gray-100 text-gray-800',
                                };
                            @endphp
                            <div class="px-3 py-2 rounded-lg text-xs font-medium {{ $bgClass }} transition-all"
                                @if ($slot['reason'] === 'reserved') onclick="alert('Rezervasyon ID: {{ $slot['reservation_id'] }}')" @endif>
                                {{ $slot['start'] }}-{{ $slot['end'] }}
                                @if ($slot['reason'] === 'reserved')
                                    <br><span class="text-xs">#{{ $slot['reservation_id'] }}</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>

                {{-- Reservation Form --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 mb-6 dark:shadow-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Yeni Rezervasyon</h3>

                    @if (session('success'))
                        <div
                            class="mb-4 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-green-800 dark:text-green-300 text-sm">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div
                            class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-300 text-sm">
                            {{ session('error') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div
                            class="mb-4 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg text-red-800 dark:text-red-300 text-sm">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('admin.ilanlar.calendar.store', $ilan) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                    Başlangıç <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="starts_at" required
                                    value="{{ old('starts_at', $selectedDay . 'T10:00') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                    Bitiş <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="ends_at" required
                                    value="{{ old('ends_at', $selectedDay . 'T11:00') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                    Müşteri Adı
                                </label>
                                <input type="text" name="customer_name" value="{{ old('customer_name') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                    Telefon
                                </label>
                                <input type="text" name="customer_phone" value="{{ old('customer_phone') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                Not
                            </label>
                            <textarea name="note" rows="2"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('note') }}</textarea>
                        </div>

                        <button type="submit"
                            class="w-full px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                            Rezervasyon Oluştur
                        </button>
                    </form>
                </div>

                {{-- Takvimi Kapat (Phase R) --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 mb-6 dark:shadow-none">
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">⛔ Takvimi Kapat</h3>
                    <form action="{{ route('admin.ilanlar.calendar.close', $ilan) }}" method="POST" class="space-y-4">
                        @csrf
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                    Başlangıç <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="starts_at" required
                                    value="{{ old('starts_at', $selectedDay . 'T00:00') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                    Bitiş <span class="text-red-500">*</span>
                                </label>
                                <input type="datetime-local" name="ends_at" required
                                    value="{{ old('ends_at', $selectedDay . 'T23:59') }}"
                                    class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                Sebep
                            </label>
                            <input type="text" name="reason" value="{{ old('reason', 'calendar_closed') }}"
                                class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <button type="submit"
                            class="w-full px-4 py-2 bg-orange-600 text-white rounded-lg hover:bg-orange-700 transition-colors">
                            ⛔ Takvimi Kapat
                        </button>
                    </form>
                </div>

                {{-- Reservations List --}}
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow dark:shadow-none">
                    <div class="p-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <h3 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Rezervasyonlar</h3>
                    </div>
                    <div class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($reservations as $reservation)
                            <div class="p-4 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            @if (($reservation->islem_durumu ?? 'pending') === 'active')
                                                <span
                                                    class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                                    Aktif
                                                </span>
                                            @else
                                                <span
                                                    class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-slate-200 dark:bg-slate-900">
                                                    İptal
                                                </span>
                                            @endif
                                            <span class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                                #{{ $reservation->id }}
                                            </span>
                                        </div>
                                        <div class="text-sm text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                                            {{ $reservation->starts_at->format('d.m.Y H:i') }} →
                                            {{ $reservation->ends_at->format('d.m.Y H:i') }}
                                        </div>
                                        @if ($reservation->customer_name)
                                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                                👤 {{ $reservation->customer_name }}
                                                @if ($reservation->customer_phone)
                                                    📞 {{ $reservation->customer_phone }}
                                                @endif
                                            </div>
                                        @endif
                                        @if ($reservation->note)
                                            <div class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                                {{ $reservation->note }}
                                            </div>
                                        @endif
                                    </div>
                                    @if ($reservation->isActive())
                                        <button @click="openCancelModal({{ $reservation->id }})"
                                            class="px-3 py-1 bg-red-600 text-white rounded hover:bg-red-700 text-sm transition-colors">
                                            İptal
                                        </button>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <div class="p-8 text-center text-gray-500 dark:text-gray-400">
                                Bu ay için rezervasyon yok
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        {{-- Cancel Modal --}}
        <div x-show="cancelModal" x-cloak
            class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-md mx-4">
                <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Rezervasyon İptal</h3>
                </div>
                <form :action="`{{ route('admin.ilanlar.calendar', $ilan) }}/${cancelReservationId}/cancel`"
                    method="POST" class="p-6 space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            İptal Nedeni (opsiyonel)
                        </label>
                        <textarea name="reason" rows="3"
                            class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"></textarea>
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" @click="cancelModal = false"
                            class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors dark:text-slate-300">
                            Vazgeç
                        </button>
                        <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors">
                            İptal Et
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function calendarApp() {
            return {
                cancelModal: false,
                cancelReservationId: null,

                openCancelModal(id) {
                    this.cancelReservationId = id;
                    this.cancelModal = true;
                }
            }
        }
    </script>

    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
@endsection
