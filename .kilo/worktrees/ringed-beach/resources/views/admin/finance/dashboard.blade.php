@extends('layouts.app')

@section('title', 'Finans Yönetimi')

@section('content')
<div class="container-fluid px-4 py-6">
    {{-- Header --}}
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
            💰 Finans Yönetimi
        </h1>
        <p class="text-gray-600 dark:text-gray-400">
            Komisyon, tahsilat ve prim yönetimi merkezi
        </p>
    </div>
    
    {{-- Stats Widgets --}}
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        {{-- Monthly Revenue --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Aylık Ciro</span>
                <span class="text-2xl">📈</span>
            </div>
            <p class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                ₺{{ number_format($monthlyRevenue, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Bu ay toplam komisyon geliri
            </p>
        </div>
        
        {{-- Pending Commissions --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen Hakedişler</span>
                <span class="text-2xl">⏳</span>
            </div>
            <p class="text-2xl font-bold text-yellow-600 dark:text-yellow-400">
                ₺{{ number_format($pendingCommissions, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Onay bekliyor
            </p>
        </div>
        
        {{-- Approved Unpaid --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Ödenecek</span>
                <span class="text-2xl">💸</span>
            </div>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                ₺{{ number_format($approvedUnpaid, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Onaylı ama ödenmemiş
            </p>
        </div>
        
        {{-- Unpaid Bonuses --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 border border-gray-200 dark:border-gray-700 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between mb-2">
                <span class="text-sm font-medium text-gray-600 dark:text-gray-400">Bekleyen Primler</span>
                <span class="text-2xl">🏆</span>
            </div>
            <p class="text-2xl font-bold text-purple-600 dark:text-purple-400">
                ₺{{ number_format($unpaidBonuses, 0, ',', '.') }}
            </p>
            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                Ödenecek performans primleri
            </p>
        </div>
    </div>
    
    {{-- Quick Actions --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg p-6 border border-gray-200 dark:border-slate-800 mb-6 dark:border-slate-700">
        <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
            ⚡ Hızlı İşlemler
        </h2>
        <div class="flex flex-wrap gap-3">
            <a href="{{ route('admin.finance.commissions.index') }}" 
               class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg 
                      transition-all duration-200 hover:scale-105">
                💼 Hakedişleri Görüntüle
            </a>
            <a href="{{ route('admin.finance.transactions.index') }}" 
               class="px-4 py-2 bg-green-500 hover:bg-green-600 text-white rounded-lg 
                      transition-all duration-200 hover:scale-105">
                💳 Tahsilatları Görüntüle
            </a>
            <a href="{{ route('admin.finance.bonuses.index') }}" 
               class="px-4 py-2 bg-purple-500 hover:bg-purple-600 text-white rounded-lg 
                      transition-all duration-200 hover:scale-105">
                🏆 Primleri Görüntüle
            </a>
            <button onclick="showCalculator()" 
                    class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg 
                           transition-all duration-200 hover:scale-105">
                🧮 Gelir Simulatörü
            </button>
        </div>
        
        {{-- Unverified Transactions Alert --}}
        @if($unverifiedCount > 0)
        <div class="mt-4 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-700 rounded-lg">
            <p class="text-yellow-800 dark:text-yellow-200">
                ⚠️ <strong>{{ $unverifiedCount }} adet</strong> doğrulanmamış tahsilat var. 
                <a href="{{ route('admin.finance.transactions.index', ['verified' => '0']) }}" 
                   class="underline hover:no-underline">
                    Hemen incele →
                </a>
            </p>
        </div>
        @endif
    </div>
    
    {{-- Income Simulator Modal --}}
    <div id="calculatorModal" class="hidden fixed inset-0 bg-black/50 flex items-center justify-center z-50" 
         x-data="incomeSimulator()" 
         @click.self="closeModal()">
        <div class="bg-white dark:bg-slate-900 rounded-lg p-8 max-w-2xl w-full mx-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                    🧮 Gelir Simulatörü
                </h2>
                <button @click="closeModal()" 
                        class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            {{-- Commission Calculator --}}
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">💼 Komisyon Hesaplama</h3>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Satış Fiyatı
                        </label>
                        <input type="number" x-model="salePrice" @input="calculateCommission()"
                               class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                      bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                      focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                               placeholder="Örn: 5000000">
                    </div>
                    
                    {{-- Results --}}
                    <template x-if="commissionResult">
                        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Toplam Komisyon:</span>
                                <span class="font-bold text-gray-900 dark:text-white dark:text-slate-100" x-text="formatPrice(commissionResult.total_commission)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Ofis Payı (40%):</span>
                                <span class="font-semibold text-gray-900 dark:text-white dark:text-slate-100" x-text="formatPrice(commissionResult.office_amount)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Danışman Payı (60%):</span>
                                <span class="font-bold text-blue-600 dark:text-blue-400" x-text="formatPrice(commissionResult.agent_amount)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
            
            {{-- Bonus Calculator --}}
            <div>
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🏆 Prim Hesaplama</h3>
                <div class="space-y-4">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Aylık Hedef
                            </label>
                            <input type="number" x-model="monthlyTarget" @input="calculateBonus()"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                          bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                          focus:ring-2 focus:ring-purple-500 transition-all duration-200"
                                   placeholder="Örn: 10000000">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                                Gerçekleşen
                            </label>
                            <input type="number" x-model="achievedAmount" @input="calculateBonus()"
                                   class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                                          bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                                          focus:ring-2 focus:ring-purple-500 transition-all duration-200"
                                   placeholder="Örn: 12000000">
                        </div>
                    </div>
                    
                    {{-- Bonus Results --}}
                    <template x-if="bonusResult">
                        <div class="bg-purple-50 dark:bg-purple-900/20 rounded-lg p-4 space-y-2">
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Başarı Oranı:</span>
                                <span class="font-bold" :class="getBadgeColor(bonusResult.bonus_tier)" x-text="bonusResult.achievement_percentage.toFixed(0) + '%'"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Seviye:</span>
                                <span class="font-bold" :class="getBadgeColor(bonusResult.bonus_tier)" x-text="getTierName(bonusResult.bonus_tier)"></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600 dark:text-gray-400">Prim Tutarı:</span>
                                <span class="font-bold text-purple-600 dark:text-purple-400" x-text="formatPrice(bonusResult.bonus_amount)"></span>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function incomeSimulator() {
    return {
        salePrice: 5000000,
        monthlyTarget: 10000000,
        achievedAmount: 12000000,
        commissionResult: null,
        bonusResult: null,
        
        async init() {
            await this.calculateCommission();
            await this.calculateBonus();
        },
        
        async calculateCommission() {
            if (!this.salePrice || this.salePrice < 0) return;
            
            const response = await fetch('{{ route("admin.finance.simulate.commission") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({ sale_price: this.salePrice })
            });
            
            this.commissionResult = await response.json();
        },
        
        async calculateBonus() {
            if (!this.monthlyTarget || !this.achievedAmount) return;
            
            const response = await fetch('{{ route("admin.finance.simulate.bonus") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    monthly_target: this.monthlyTarget,
                    achieved_amount: this.achievedAmount
                })
            });
            
            this.bonusResult = await response.json();
        },
        
        formatPrice(value) {
            return '₺' + Number(value).toLocaleString('tr-TR', { minimumFractionDigits: 0 });
        },
        
        getTierName(tier) {
            const names = {
                'bronze': '🥉 Bronz',
                'silver': '🥈 Gümüş',
                'gold': '🥇 Altın',
                'none': '— Yok'
            };
            return names[tier] ?? '—';
        },
        
        getBadgeColor(tier) {
            const colors = {
                'bronze': 'text-orange-600 dark:text-orange-400',
                'silver': 'text-gray-600 dark:text-gray-400',
                'gold': 'text-yellow-600 dark:text-yellow-400',
                'none': 'text-gray-400'
            };
            return colors[tier] ?? 'text-gray-400';
        },
        
        closeModal() {
            document.getElementById('calculatorModal').classList.add('hidden');
        }
    }
}

function showCalculator() {
    const modal = document.getElementById('calculatorModal');
    modal.classList.remove('hidden');
}
</script>
@endpush
@endsection
