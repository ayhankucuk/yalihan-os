@extends('admin.layouts.admin')

@section('title', 'Hermes Dashboard')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Section 1 — Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div class="flex items-center gap-3">
            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 dark:bg-purple-900/40">
                <svg class="h-5 w-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" />
                </svg>
            </div>
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Hermes Dashboard</h1>
                <p class="mt-0.5 text-sm text-gray-500 dark:text-gray-400">
                    Event bus monitoring, workforce chains, and replay controls
                </p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('admin.hermes.api.stats') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700"
               target="_blank">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4" />
                </svg>
                API
            </a>
            <a href="{{ route('admin.dashboard.index') }}"
               class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm transition-colors hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                Geri
            </a>
        </div>
    </div>

    {{-- Section 2 — Stats Strip --}}
    @include('admin.hermes.partials._stats-strip', ['stats' => $stats])

    {{-- Section 3 — Two column: Failed Events + Workforce Chains --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div>
            @include('admin.hermes.partials._failed-events', ['failedPage' => $failedPage])
        </div>
        <div>
            @include('admin.hermes.partials._workforce-chains', ['chains' => $chains, 'stats' => $stats])
        </div>
    </div>

    {{-- Section 4 — Event Vocabulary --}}
    <div class="mb-6">
        @include('admin.hermes.partials._event-vocabulary', ['vocabulary' => $vocabulary])
    </div>

</div>
@endsection
