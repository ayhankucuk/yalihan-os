{{--
    AI Telemetry Dashboard (Phase 13 - Epic 2 - Task 2.3)

    Route: /admin/ai/telemetry
    Access: Super Admin only
    Components: Shell + 6 Livewire Widgets
--}}

@extends('admin.layouts.admin')

@section('title', 'AI Telemetry Dashboard')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
    {{-- Header --}}
    <div class="bg-white dark:bg-slate-900 shadow-sm border-b border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="md:flex md:items-center md:justify-between">
                <div class="flex-1 min-w-0">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                        🤖 AI Telemetry Dashboard
                    </h1>
                    <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                        Real-time Performance monitoring ve cost tracking
                    </p>
                </div>

                {{-- Filter Controls --}}
                <div class="mt-4 flex md:mt-0 md:ml-4 space-x-3">
                    {{-- Period Filter --}}
                    <select id="period-filter" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-white">
                        <option value="24h">Son 24 Saat</option>
                        <option value="7d" selected>Son 7 Gün</option>
                        <option value="30d">Son 30 Gün</option>
                    </select>

                    {{-- Provider Filter --}}
                    <select id="provider-filter" class="rounded-md border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-slate-100 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-white">
                        <option value="">Tüm Provider'lar</option>
                        <option value="openai">OpenAI</option>
                        <option value="gemini">Gemini</option>
                        <option value="ollama">Ollama (Local)</option>
                    </select>

                    {{-- Refresh Button --}}
                    <button id="refresh-btn" class="inline-flex items-center px-4 py-2 border border-transparent rounded-md shadow-sm text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors dark:shadow-none">
                        <svg class="h-4 w-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Yenile
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Widget Grid --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
            {{-- Widget 1: Cost Overview --}}
            <div class="col-span-1 lg:col-span-2 xl:col-span-3">
                <livewire:ai-telemetry.cost-overview-widget />
            </div>

            {{-- Widget 2: Request Volume --}}
            <div class="col-span-1 lg:col-span-2">
                <livewire:ai-telemetry.request-volume-widget />
            </div>

            {{-- Widget 3: Provider Performance --}}
            <div class="col-span-1">
                <livewire:ai-telemetry.provider-performance-widget />
            </div>

            {{-- Widget 4: Error Rate --}}
            <div class="col-span-1">
                <livewire:ai-telemetry.error-rate-widget />
            </div>

            {{-- Widget 5: Token Leaderboard --}}
            <div class="col-span-1 lg:col-span-2">
                <livewire:ai-telemetry.token-leaderboard-widget />
            </div>

            {{-- Widget 6: Live Activity --}}
            <div class="col-span-1 lg:col-span-2 xl:col-span-3">
                <livewire:ai-telemetry.live-activity-widget />
            </div>
        </div>
    </div>
</div>

{{-- Filter State Management (Vanilla JS) --}}
<script>
document.addEventListener('DOMContentLoaded', function() {
    const periodFilter = document.getElementById('period-filter');
    const providerFilter = document.getElementById('provider-filter');
    const refreshBtn = document.getElementById('refresh-btn');

    // Filter change handler
    function handleFilterChange() {
        const filters = {
            period: periodFilter.value,
            provider: providerFilter.value
        };

        // Emit Livewire event to all widgets
        Livewire.emit('filtersChanged', filters);
    }

    // Attach listeners
    periodFilter.addEventListener('change', handleFilterChange);
    providerFilter.addEventListener('change', handleFilterChange);
    refreshBtn.addEventListener('click', handleFilterChange);

    // Initialize with default filters
    handleFilterChange();
});
</script>
@endsection
