@extends('admin.layouts.admin')

@section('content')
<div x-data="cortexAnalytics()"
     x-init="init()"
     class="min-h-screen bg-gray-900 text-white font-sans selection:bg-indigo-500 selection:text-white">

    {{-- Header --}}
    <div class="px-8 py-6 border-b border-gray-800 flex justify-between items-center bg-gray-900/50 backdrop-blur-md sticky top-0 z-30">
        <div>
            <h1 class="text-2xl font-bold bg-gradient-to-r from-blue-400 to-indigo-500 bg-clip-text text-transparent">
                Cortex Analytics
            </h1>
            <p class="text-sm text-gray-400">Real-time revenue intelligence</p>
        </div>

        <div class="flex items-center space-x-4">
            {{-- Property Selector (Mock) --}}
            <select x-model="listingId"
                    class="bg-gray-800 border-gray-700 text-gray-300 text-sm rounded-lg focus:ring-indigo-500 focus:border-indigo-500 block p-2.5">
                <option value="">Select Property</option>
                @foreach(\App\Models\Ilan::where('yayin_tipi_id', 3)->limit(10)->get() as $p)
                    <option value="{{ $p->id }}">{{ Str::limit($p->baslik, 30) }}</option>
                @endforeach
            </select>

            {{-- Period Filter --}}
            <div class="flex bg-gray-800 rounded-lg p-1">
                <button @click="setPeriod('last_month')"
                        :class="{'bg-gray-700 text-white': period==='last_month', 'text-gray-400 hover:text-gray-200': period!=='last_month'}"
                        class="px-4 py-1.5 text-sm rounded-md transition-colors">
                    Last Month
                </button>
                <button @click="setPeriod('this_month')"
                        :class="{'bg-indigo-600 text-white shadow-lg': period==='this_month', 'text-gray-400 hover:text-gray-200': period!=='this_month'}"
                        class="px-4 py-1.5 text-sm rounded-md transition-colors">
                    This Month
                </button>
            </div>
        </div>
    </div>

    {{-- Main Content --}}
    <div class="p-8 space-y-8">

        {{-- Loading State --}}
        <div x-show="isLoading" class="fixed inset-0 bg-gray-900/80 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="flex flex-col items-center">
                <div class="animate-spin rounded-full h-12 w-12 border-t-2 border-b-2 border-indigo-500"></div>
                <span class="mt-4 text-indigo-400 font-medium tracking-wide">Crunching numbers...</span>
            </div>
        </div>

        {{-- No Selection State --}}
        <div x-show="!listingId && !isLoading" class="text-center py-20">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-800 mb-4">
                <svg class="w-8 h-8 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            </div>
            <h3 class="text-lg font-medium text-gray-300">Select a Property</h3>
            <p class="text-gray-500">Choose a listing to view its performance metrics.</p>
        </div>

        {{-- Dashboard Grid --}}
        <div x-show="listingId" x-transition.opacity.duration.500ms class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">

            {{-- KPI Card: Occupancy --}}
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700/50 shadow-xl relative overflow-hidden group hover:border-indigo-500/30 transition-colors">
                <div class="absolute top-0 right-0 p-4 opacity-10 group-hover:opacity-20 transition-opacity">
                    <svg class="w-16 h-16 text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <h3 class="text-gray-400 text-xs font-semibold uppercase tracking-wider mb-2">Occupancy Rate</h3>
                <div class="flex items-baseline space-x-2">
                    <span class="text-3xl font-bold text-white" x-text="metrics.occupancy_rate + '%'"></span>
                    {{-- Mock Trend --}}
                    <span class="text-xs font-medium text-green-400 bg-green-400/10 px-1.5 py-0.5 rounded flex items-center">
                        <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 10l7-7m0 0l7 7m-7-7v18"></path></svg>
                        2.4%
                    </span>
                </div>
                <div class="mt-4 w-full bg-gray-700 rounded-full h-1.5 overflow-hidden">
                    <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-1000" :style="`width: ${metrics.occupancy_rate}%`"></div>
                </div>
            </div>

            {{-- KPI Card: ADR --}}
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700/50 shadow-xl relative overflow-hidden group hover:border-emerald-500/30 transition-colors">
                <h3 class="text-gray-400 text-xs font-semibold uppercase tracking-wider mb-2">Average Daily Rate (ADR)</h3>
                <div class="flex items-baseline space-x-2">
                    <span class="text-3xl font-bold text-white" x-text="'₺' + metrics.adr"></span>
                    <span class="text-xs font-medium text-gray-500">per night</span>
                </div>
            </div>

            {{-- KPI Card: RevPAR --}}
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700/50 shadow-xl relative overflow-hidden group hover:border-amber-500/30 transition-colors">
                <h3 class="text-gray-400 text-xs font-semibold uppercase tracking-wider mb-2">RevPAR</h3>
                <div class="flex items-baseline space-x-2">
                    <span class="text-3xl font-bold text-amber-400" x-text="'₺' + metrics.revpar"></span>
                </div>
                <p class="text-xs text-gray-500 mt-2">Revenue per available room</p>
            </div>

            {{-- KPI Card: Revenue --}}
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700/50 shadow-xl relative overflow-hidden group hover:border-blue-500/30 transition-colors">
                 <h3 class="text-gray-400 text-xs font-semibold uppercase tracking-wider mb-2">Est. Revenue</h3>
                <div class="flex items-baseline space-x-2">
                    {{-- Rough calc for v1: RevPAR * Available Days (30) --}}
                    <span class="text-3xl font-bold text-blue-400" x-text="'₺' + (metrics.revenue ? metrics.revenue : (metrics.revpar * 30).toFixed(0))"></span>
                </div>
            </div>

        </div>

        {{-- Main Chart Section --}}
        <div x-show="listingId" x-transition.opacity.delay.200ms class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Revenue Trend --}}
            <div class="lg:col-span-2 bg-gray-800 rounded-xl p-6 border border-gray-700/50 shadow-xl">
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-lg font-semibold text-gray-200">Revenue Performance</h3>
                    <button class="text-gray-400 hover:text-white transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v.01M12 12v.01M12 19v.01M12 6a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2zm0 7a1 1 0 110-2 1 1 0 010 2z"></path></svg>
                    </button>
                </div>
                <div id="revenue-chart" class="h-80 w-full flex items-center justify-center text-gray-600">
                    {{-- Chart will be rendered here by Apex --}}
                    [Revenue Chart Placeholder - Connect to Series]
                </div>
            </div>

            {{-- Occupancy Gauge --}}
            <div class="bg-gray-800 rounded-xl p-6 border border-gray-700/50 shadow-xl">
                 <h3 class="text-lg font-semibold text-gray-200 mb-6">Efficiency</h3>
                 <div id="occupancy-gauge" class="h-64 flex items-center justify-center">
                    {{-- Chart will be rendered here --}}
                     [Gauge Placeholder]
                 </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
@vite(['resources/js/dashboard/cortex-analytics.js'])
@endpush
@endsection
