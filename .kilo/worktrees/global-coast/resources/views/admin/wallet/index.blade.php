@extends('layouts.app')

@section('title', 'Cüzdanım')

@section('content')
<div class="container-fluid px-4 py-6" x-data="walletMaster({{ auth()->id() }})">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
            👛 Cüzdanım
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Kazançlarınız, hakedişleriniz ve performans takibi
        </p>
    </div>
    
    {{-- Earnings Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        {{-- This Month --}}
        <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium opacity-90">Bu Ay Kazancım</span>
                <span class="text-2xl">💰</span>
            </div>
            <p class="text-3xl font-bold">
                ₺{{ number_format($monthlyEarnings, 0, ',', '.') }}
            </p>
            <p class="text-xs opacity-75 mt-2">
                {{ $salesCount }} satış tamamlandı
            </p>
        </div>
        
        {{-- Pending --}}
        <div class="bg-gradient-to-br from-yellow-500 to-yellow-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium opacity-90">Bekleyen Hakediş</span>
                <span class="text-2xl">⏳</span>
            </div>
            <p class="text-3xl font-bold">
                ₺{{ number_format($pendingCommissions, 0, ',', '.') }}
            </p>
            <p class="text-xs opacity-75 mt-2">
                Onay sürecinde
            </p>
        </div>
        
        {{-- Total Lifetime --}}
        <div class="bg-gradient-to-br from-green-500 to-green-600 rounded-lg p-6 text-white shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium opacity-90">Toplam Kazancım</span>
                <span class="text-2xl">📊</span>
            </div>
            <p class="text-3xl font-bold">
                ₺{{ number_format($totalEarnings, 0, ',', '.') }}
            </p>
            <p class="text-xs opacity-75 mt-2">
                Tüm zamanlar
            </p>
        </div>
    </div>
    
    {{-- Performance Tracker --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg p-6 border border-gray-200 dark:border-slate-800 mb-6 dark:border-slate-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
            📈 Bu Ay Performansım
        </h2>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            {{-- Progress Bar --}}
            <div>
                <div class="flex justify-between mb-2">
                    <span class="text-sm text-gray-600 dark:text-gray-400">Hedef İlerleme</span>
                    <span class="text-sm font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ number_format($achievementPercentage, 1) }}%
                    </span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-4 overflow-hidden">
                    <div class="h-full bg-gradient-to-r from-blue-500 to-purple-500 rounded-full transition-all duration-500"
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
            <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 border border-purple-200 dark:border-purple-700">
                <div class="flex items-center justify-between mb-2">
                    <h3 class="text-sm font-semibold text-purple-900 dark:text-purple-100">
                        🏆 Tahmini Prim
                    </h3>
                    <span class="px-2 py-1 text-xs font-bold rounded 
                                 @if($projectedBonus['bonus_tier'] === 'gold') bg-yellow-500 text-white
                                 @elseif($projectedBonus['bonus_tier'] === 'silver') bg-gray-400 text-white
                                 @elseif($projectedBonus['bonus_tier'] === 'bronze') bg-orange-500 text-white
                                 @else bg-gray-300 text-gray-700 @endif">
                        @if($projectedBonus['bonus_tier'] === 'gold') 🥇 Altın
                        @elseif($projectedBonus['bonus_tier'] === 'silver') 🥈 Gümüş
                        @elseif($projectedBonus['bonus_tier'] === 'bronze') 🥉 Bronz
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
    <div class="bg-white dark:bg-slate-900 rounded-lg p-6 border border-gray-200 dark:border-slate-800 mb-6 dark:border-slate-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
            🧮 Gelir Hesaplayıcı
        </h2>
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Satış Fiyatı (TL)
                </label>
                <input type="range" x-model="simulatorPrice" @input="calculateIncome()"
                       min="100000" max="20000000" step="100000"
                       class="w-full h-2 bg-gray-200 dark:bg-gray-700 rounded-lg appearance-none cursor-pointer
                              focus:outline-none focus:ring-2 focus:ring-blue-500">
                <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                    <span>100K</span>
                    <span class="text-base font-bold text-blue-600 dark:text-blue-400" x-text="formatPrice(simulatorPrice)"></span>
                    <span>20M</span>
                </div>
            </div>
            
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700 dark:text-slate-200 font-medium dark:text-slate-300">
                        💼 Sizin Kazancınız (60%):
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
    <div class="bg-white dark:bg-slate-900 rounded-lg p-6 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
            ⚡ Hızlı Erişim
        </h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.wallet.commissions') }}" 
               class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg 
                      transition-all duration-200 hover:scale-105">
                💼 Hakedişlerim
            </a>
            <a href="{{ route('admin.wallet.bonuses') }}" 
               class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg 
                      transition-all duration-200 hover:scale-105">
                🏆 Primlerim
            </a>
            <a href="{{ route('admin.ilanlar.index', ['kullanici_id' => auth()->id(), 'yayin_durumu' => 'Satıldı']) }}" 
               class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg 
                      transition-all duration-200 hover:scale-105">
                📊 Satışlarım
            </a>
        </div>
        
        @if($unpaidBonuses > 0)
        <div class="mt-4 p-4 bg-purple-50 dark:bg-purple-900/20 border border-purple-200 dark:border-purple-700 rounded-lg">
            <p class="text-purple-800 dark:text-purple-200">
                🎉 Ödenecek <strong>₺{{ number_format($unpaidBonuses, 0, ',', '.') }}</strong> priminiz var!
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
