@extends('admin.layouts.admin')

@section('title', 'SAB Governance Dashboard')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Section 1 — Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Governance Dashboard</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">AI decisions, proposal lifecycle, audit trail and runtime health</p>
        </div>
        <div>
            <a href="{{ route('admin.governance.dashboard') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Yenile
            </a>
        </div>
    </div>

    {{-- Section 2 — SAB Pipeline Runtime Strip --}}
    @include('admin.governance.partials.runtime-strip', ['runtimeStrip' => $overview])

    {{-- Section 3 — Overview Cards --}}
    @include('admin.governance.partials.overview-cards', ['overview' => $overview])

    {{-- Section 3.5 — Decision Engine Strip --}}
    @if(isset($decisionEngine))
    <div class="mb-8 rounded-lg border border-indigo-200 bg-indigo-50 p-4 dark:border-indigo-800 dark:bg-indigo-900/20">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-6">
                <h3 class="text-sm font-semibold text-indigo-900 dark:text-indigo-300">Karar Motoru</h3>
                <div class="flex gap-4 text-sm">
                    <span class="text-yellow-700 dark:text-yellow-400">{{ $decisionEngine['pending'] }} bekleyen</span>
                    <span class="text-green-700 dark:text-green-400">{{ $decisionEngine['approved'] }} onaylı</span>
                    <span class="text-red-700 dark:text-red-400">{{ $decisionEngine['rejected'] }} red</span>
                    <span class="text-blue-700 dark:text-blue-400">{{ $decisionEngine['auto_applied'] }} otomatik</span>
                </div>
            </div>
            <a href="{{ route('admin.governance.review-queue') }}"
               class="rounded-lg bg-indigo-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                İnceleme Kuyruğu
            </a>
        </div>
    </div>
    @endif

    {{-- Section 3 — Three-column: OpenClaw + Health + (empty/reserved) --}}
    <div class="mb-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            @include('admin.governance.partials.openclaw-status', ['health' => $health])
        </div>
        <div>
            @include('admin.governance.partials.system-health', ['health' => $health])
        </div>
    </div>

    {{-- Section 3.1 — Pending --}}
    <div class="mb-8">
        @include('admin.governance.partials.pending-table', ['pending' => $pending, 'filters' => $filters])
    </div>


    {{-- Section 4 — Full width: Audit Timeline --}}
    <div class="mb-8">
        @include('admin.governance.partials.audit-timeline', ['audit' => $audit])
    </div>

    {{-- Section 5 — Two-column: History + Authority --}}
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            @include('admin.governance.partials.history-table', ['history' => $history, 'filters' => $filters])
        </div>
        <div>
            @include('admin.governance.partials.authority-summary', ['authority' => $authority])
        </div>
    </div>
</div>
@endsection
