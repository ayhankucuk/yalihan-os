{{-- 🔌 FieldMCP Hardware Integration Dashboard --}}
@extends('admin.layouts.admin')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-4xl font-bold text-gray-900 dark:text-white transition-colors duration-200 dark:text-slate-100">
                🔌 FieldMCP Hardware Entegrasyonu
            </h1>
            <p class="mt-2 text-lg text-gray-600 dark:text-slate-200 transition-colors duration-200">
                Bosch GLM ve FLIR ONE cihazlarından gelen ölçümleri yönet
            </p>
        </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
        {{-- Bosch GLM --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Bosch GLM Ölçümler
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ $stats['bosch_total'] ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-blue-100 dark:bg-blue-900 rounded-full">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                    ↗️ +{{ $stats['bosch_today'] ?? 0 }} bugün
                </span>
            </div>
        </div>

        {{-- FLIR Analysis --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        FLIR Analizler
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ $stats['flir_total'] ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-orange-100 dark:bg-orange-900 rounded-full">
                    <svg class="w-8 h-8 text-orange-600 dark:text-orange-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-green-600 dark:text-green-400 font-medium">
                    ↗️ +{{ $stats['flir_today'] ?? 0 }} bugün
                </span>
            </div>
        </div>

        {{-- Verified Properties --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Onaylı İlanlar
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        {{ $stats['verified_count'] ?? 0 }}
                    </p>
                </div>
                <div class="p-3 bg-green-100 dark:bg-green-900 rounded-full">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    %{{ $stats['verification_rate'] ?? 0 }} doğruluk
                </span>
            </div>
        </div>

        {{-- Avg Accuracy --}}
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow-md p-6 
                    transition-all duration-200 hover:shadow-lg">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-gray-600 dark:text-gray-400">
                        Ort. Hassasiyet
                    </p>
                    <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        ±{{ $stats['avg_accuracy_mm'] ?? 0 }}mm
                    </p>
                </div>
                <div class="p-3 bg-purple-100 dark:bg-purple-900 rounded-full">
                    <svg class="w-8 h-8 text-purple-600 dark:text-purple-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
            </div>
            <div class="mt-4">
                <span class="text-sm text-gray-600 dark:text-gray-400">
                    Bosch GLM standart
                </span>
            </div>
        </div>
    </div>

    {{-- Recent Measurements --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-md overflow-hidden dark:shadow-none">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                📊 Son Ölçümler
            </h2>
        </div>
        
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            İlan
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Cihaz
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Ölçüm Tipi
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Değer
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Hassasiyet
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Tarih
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Durum
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($recent_measurements ?? [] as $measurement)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $measurement->ilan->baslik ?? '—' }}
                            </div>
                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                #{{ $measurement->ilan_id }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900 dark:text-white dark:text-slate-100">
                                {{ $measurement->device_name ?? '—' }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="px-3 py-1 text-xs font-semibold rounded-full transition-colors duration-200
                                @if($measurement->measurement_type === 'area')
                                    bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-200
                                @elseif($measurement->measurement_type === 'thermal_insulation')
                                    bg-orange-100 text-orange-800 dark:bg-orange-900/30 dark:text-orange-200
                                @else
                                    bg-gray-100 text-gray-800 dark:bg-gray-900/30 dark:text-gray-200
                                @endif">
                                {{ $measurement->measurement_type }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            {{ number_format($measurement->value, 2) }} {{ $measurement->unit ?? 'm²' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white dark:text-slate-100">
                            ±{{ $measurement->accuracy_mm ?? 0 }}mm
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $measurement->created_at->diffForHumans() ?? '—' }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($measurement->verified)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300 transition-colors duration-200">
                                    ✅ Onaylı
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-100 text-yellow-800 dark:bg-yellow-900/40 dark:text-yellow-300 transition-colors duration-200">
                                    ⏳ Bekliyor
                                </span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            Henüz ölçüm yapılmadı
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- API Integration Guide --}}
    <div class="mt-8 bg-blue-50 dark:bg-blue-900/20 rounded-lg p-6 border border-blue-200 dark:border-blue-800 transition-colors duration-200">
        <h3 class="text-lg font-semibold text-blue-900 dark:text-blue-300 mb-4">
            🔌 API Entegrasyon Bilgileri
        </h3>
        
        <div class="space-y-4">
            <div>
                <p class="text-sm font-medium text-blue-800 dark:text-blue-400 mb-2">Bosch GLM Endpoint:</p>
                <code class="block bg-white dark:bg-slate-900 text-sm text-gray-800 dark:text-slate-100 p-3 rounded border border-blue-300 dark:border-blue-700 font-mono overflow-x-auto transition-colors duration-200 dark:text-slate-200">
                    POST {{ config('app.url') }}/api/v1/field-mcp/bosch-glm/measurement
                </code>
            </div>
            
            <div>
                <p class="text-sm font-medium text-blue-800 dark:text-blue-400 mb-2">FLIR ONE Endpoint:</p>
                <code class="block bg-white dark:bg-slate-900 text-sm text-gray-800 dark:text-slate-100 p-3 rounded border border-blue-300 dark:border-blue-700 font-mono overflow-x-auto transition-colors duration-200 dark:text-slate-200">
                    POST {{ config('app.url') }}/api/v1/field-mcp/flir-one/analysis
                </code>
            </div>
            
            <div>
                <p class="text-sm font-medium text-blue-800 dark:text-blue-400 mb-2">Ölçüm Geçmişi:</p>
                <code class="block bg-white dark:bg-slate-900 text-sm text-gray-800 dark:text-slate-100 p-3 rounded border border-blue-300 dark:border-blue-700 font-mono overflow-x-auto transition-colors duration-200 dark:text-slate-200">
                    GET {{ config('app.url') }}/api/v1/field-mcp/measurements/{ilanId}
                </code>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
// Real-time measurement updates (Alpine.js)
document.addEventListener('alpine:init', () => {
    Alpine.data('fieldMcpDashboard', () => ({
        stats: @json($stats ?? []),
        autoRefresh: true,
        
        init() {
            if (this.autoRefresh) {
                setInterval(() => {
                    this.refreshStats();
                }, 30000); // 30 saniyede bir
            }
        },
        
        async refreshStats() {
            try {
                const response = await fetch('/api/v1/field-mcp/stats');
                const data = await response.json();
                this.stats = data;
            } catch (error) {
                console.error('Stats refresh failed:', error);
            }
        }
    }));
});
</script>
@endpush
