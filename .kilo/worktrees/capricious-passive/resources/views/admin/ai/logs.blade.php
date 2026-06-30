@extends('admin.layouts.admin')

@section('title', 'AI System Logs')

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900 transition-colors duration-200">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 sticky top-0 z-40 dark:border-slate-700">
        <div class="px-6 py-4 sm:px-8 sm:py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">📋 AI System Logs</h1>
                    <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">View all AI operations and system events</p>
                </div>
                <a href="{{ route('admin.ai.dashboard') }}" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors duration-200">
                    ← Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <!-- Content -->
    <div class="p-6 sm:p-8">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-8">
            <!-- Total Logs -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Total Logs</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">{{ $total_count }}</p>
                    </div>
                    <div class="text-4xl text-blue-500 opacity-20">📊</div>
                </div>
            </div>

            <!-- Today's Logs -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Today's Logs</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">{{ $today_count }}</p>
                    </div>
                    <div class="text-4xl text-green-500 opacity-20">📈</div>
                </div>
            </div>

            <!-- Refresh Info -->
            <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 p-6 shadow-sm dark:shadow-none dark:border-slate-700">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Last Updated</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-white mt-2 dark:text-slate-100">Now</p>
                    </div>
                    <div class="text-4xl text-purple-500 opacity-20">⚡</div>
                </div>
            </div>
        </div>

        <!-- Logs Table -->
        <div class="bg-white dark:bg-slate-900 rounded-lg border border-gray-200 dark:border-slate-800 overflow-hidden shadow-sm dark:shadow-none dark:border-slate-700">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-gray-700 border-b border-gray-200 dark:border-gray-600 dark:bg-slate-900 dark:border-slate-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Timestamp</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Service</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Action</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Response Time</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Message</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($logs as $log)
                            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors duration-150">
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-slate-100 font-mono dark:text-white">
                                    {{ $log['timestamp'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                    <span class="px-2 py-1 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded text-xs font-medium">
                                        {{ $log['service'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-slate-100 dark:text-white">
                                    {{ $log['action'] }}
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    @if($log['status'] === 'success')
                                        <span class="px-2 py-1 bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300 rounded text-xs font-medium">
                                            ✅ Success
                                        </span>
                                    @elseif($log['status'] === 'failed')
                                        <span class="px-2 py-1 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded text-xs font-medium">
                                            ❌ Failed
                                        </span>
                                    @else
                                        <span class="px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-slate-200 rounded text-xs font-medium dark:bg-slate-900">
                                            {{ $log['status'] }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 font-mono">
                                    {{ $log['response_time'] }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 truncate max-w-xs" title="{{ $log['message'] }}">
                                    {{ $log['message'] }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <p class="text-gray-600 dark:text-gray-400 text-sm">📭 No logs found</p>
                                        <p class="text-gray-500 dark:text-gray-500 text-xs mt-1">AI logs will appear here when operations are performed</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Pagination Info -->
            <div class="px-6 py-4 bg-gray-50 dark:bg-gray-700/50 border-t border-gray-200 dark:border-slate-800 dark:bg-slate-900 dark:border-slate-700">
                <p class="text-sm text-gray-600 dark:text-gray-400">
                    Showing latest 100 logs. Total: <span class="font-semibold">{{ $total_count }}</span> logs in database.
                </p>
            </div>
        </div>

        <!-- Auto-refresh Info -->
        <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-sm text-blue-800 dark:text-blue-300">
                💡 <strong>Tip:</strong> This page shows the 100 most recent AI system logs. Logs are automatically recorded for all AI operations including analysis, generation, and system checks.
            </p>
        </div>
    </div>
</div>

<style>
    table tbody tr:hover {
        background-color: rgba(59, 130, 246, 0.05);
    }
</style>
@endsection
