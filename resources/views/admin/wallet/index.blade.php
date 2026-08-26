@extends('admin.layouts.admin')

@section('title', 'Cüzdanım & Hakedişler')

@section('content')
<div class="space-y-6" x-data="walletMaster({{ auth()->id() }})">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-3xl font-bold text-gray-900 dark:text-slate-100 mb-2">
            Cüzdanım & Hakediş Yönetimi
        </h1>
        <p class="text-base text-gray-600 dark:text-gray-400">
            Kazançlarınız, hakedişleriniz ve performans takibi
        </p>
    </div>
    
    {{-- Earnings Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
        {{-- This Month --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Bu Ay Kazancım</span>
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                ₺{{ number_format($monthlyEarnings, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                {{ $salesCount }} satış tamamlandı
            </p>
        </div>
        
        {{-- Pending --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen Hakediş</span>
                <div class="w-10 h-10 rounded-xl bg-amber-50 dark:bg-amber-900/30 text-amber-600 dark:text-amber-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                ₺{{ number_format($pendingCommissions, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Onay sürecinde
            </p>
        </div>
        
        {{-- Total Lifetime --}}
        <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800 shadow-sm">
            <div class="flex items-center justify-between mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Toplam Kazancım</span>
                <div class="w-10 h-10 rounded-xl bg-emerald-50 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400 flex items-center justify-center">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                    </svg>
                </div>
            </div>
            <p class="text-3xl font-bold text-gray-900 dark:text-slate-100">
                ₺{{ number_format($totalEarnings, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                Tüm zamanlar
            </p>
        </div>
    </div>
    
    {{-- Performance Tracker --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">
            Bu Ay Performansım
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Progress Bar --}}
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hedef İlerleme</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-slate-100">
                        {{ number_format($achievementPercentage, 1) }}%
                    </span>
                </div>
                <div class="w-full bg-gray-100 dark:bg-slate-800 rounded-full h-3 overflow-hidden">
                    <div class="h-full bg-blue-600 rounded-full transition-all duration-500"
                         style="width: {{ min(100, $achievementPercentage) }}%">
                    </div>
                </div>
                <div class="flex justify-between mt-2 text-xs text-gray-500 dark:text-gray-400">
                    <span>₺{{ number_format($thisMonthSales, 0, ',', '.') }} gerçekleşti</span>
                    <span>Hedef: ₺{{ number_format($monthlyTarget, 0, ',', '.') }}</span>
                </div>
            </div>
            
            {{-- Projected Bonus --}}
            @if($projectedBonus)
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-xl p-4 border border-purple-200 dark:border-purple-800">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-200">
                        Tahmini Prim
                    </h3>
                    <span class="px-2.5 py-0.5 text-xs font-semibold rounded-full
                                 @if($projectedBonus['bonus_tier'] === 'gold') bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-300
                                 @elseif($projectedBonus['bonus_tier'] === 'silver') bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-300
                                 @elseif($projectedBonus['bonus_tier'] === 'bronze') bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-300
                                 @else bg-gray-100 text-gray-700 @endif">
                        @if($projectedBonus['bonus_tier'] === 'gold') Altın Tier
                        @elseif($projectedBonus['bonus_tier'] === 'silver') Gümüş Tier
                        @elseif($projectedBonus['bonus_tier'] === 'bronze') Bronz Tier
                        @else — @endif
                    </span>
                </div>
                <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                    ₺{{ number_format($projectedBonus['bonus_amount'], 0, ',', '.') }}
                </p>
                <p class="text-xs text-purple-700 dark:text-purple-300 mt-1">
                    {{ number_format($projectedBonus['achievement_percentage'], 0) }}% başarı oranı ile
                </p>
            </div>
            @endif
        </div>
    </div>
    
    {{-- Income Simulator --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800 mb-6 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">
            Gelir Hesaplayıcı
        </h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2">
                    Satış Fiyatı (TL)
                </label>
                <input type="range" x-model="simulatorPrice" @input="calculateIncome()"
                       min="100000" max="20000000" step="100000"
                       class="w-full h-2 bg-gray-200 dark:bg-slate-700 rounded-lg appearance-none cursor-pointer focus:outline-none">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span>100K</span>
                    <span class="text-base font-bold text-blue-600 dark:text-blue-400" x-text="formatPrice(simulatorPrice)"></span>
                    <span>20M</span>
                </div>
            </div>
            
            <div class="bg-blue-50/70 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-100 dark:border-blue-800/40">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-slate-200 font-medium">
                        Sizin Kazancınız (60%):
                    </span>
                    <span class="text-2xl font-bold text-blue-600 dark:text-blue-400" x-text="formatPrice(agentShare)">
                        —
                    </span>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-2">
                    Ofis payı: <span x-text="formatPrice(officeShare)">—</span> (40%)
                </p>
            </div>
        </div>
    </div>
    
    {{-- Quick Links --}}
    <div class="bg-white dark:bg-slate-900 rounded-xl p-6 border border-gray-200 dark:border-slate-800 shadow-sm">
        <h2 class="text-lg font-bold text-gray-900 dark:text-slate-100 mb-4">
            Hızlı Erişim
        </h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.wallet.commissions') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Hakedişlerim
            </a>
            <a href="{{ route('admin.wallet.bonuses') }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-purple-600 hover:bg-purple-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z" />
                </svg>
                Primlerim
            </a>
            <a href="{{ route('admin.ilanlar.index', ['kullanici_id' => auth()->id(), 'yayin_durumu' => 'Satıldı']) }}" 
               class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-medium text-sm rounded-lg shadow-sm hover:shadow transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                Satışlarım
            </a>
        </div>
        
        @if($unpaidBonuses > 0)
        <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-800 rounded-xl">
            <p class="text-purple-800 dark:text-purple-200 text-sm">
                Ödenecek <strong>₺{{ number_format($unpaidBonuses, 0, ',', '.') }}</strong> priminiz bulunmaktadır.
            </p>
        </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
function walletMaster(agentId) {
    return {
        simulatorPrice: 5000000,
        agentShare: 0,
        officeShare: 0,
        
        init() {
            this.calculateIncome();
        },
        
        calculateIncome() {
            const commissionRate = 0.03; // 3%
            const totalCommission = this.simulatorPrice * commissionRate;
            
            this.agentShare = totalCommission * 0.60; // 60%
            this.officeShare = totalCommission * 0.40; // 40%
        },
        
        formatPrice(value) {
            return '₺' + Number(value).toLocaleString('tr-TR', { minimumFractionDigits: 0 });
        }
    }
}
</script>
@endpush
@endsection
