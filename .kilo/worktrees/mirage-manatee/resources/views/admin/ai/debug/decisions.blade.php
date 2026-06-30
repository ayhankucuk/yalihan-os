@extends('admin.layouts.admin')

@section('title', 'AI Provider Decisions - Debug')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">🔍 AI Provider Decisions</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Read-only debug interface for AI provider selection tracking</p>
    </div>

    {{-- Filters --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 mb-6 dark:shadow-none">
        <form method="GET" action="{{ route('admin.ai.debug.decisions') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Provider</label>
                <select name="provider" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-slate-100">
                    <option value="">All Providers</option>
                    @foreach($providers as $provider)
                        <option value="{{ $provider }}" {{ request('provider') == $provider ? 'selected' : '' }}>
                            {{ $provider }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Correlation ID</label>
                <input type="text" name="correlation_id" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-slate-100"
                       value="{{ request('correlation_id') }}" placeholder="Search...">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Date From</label>
                <input type="date" name="date_from" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-slate-100"
                       value="{{ request('date_from') }}">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">Date To</label>
                <input type="date" name="date_to" class="w-full px-3 py-2 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 dark:bg-slate-900 dark:text-slate-100"
                       value="{{ request('date_to') }}">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-md transition-colors">Filter</button>
                <a href="{{ route('admin.ai.debug.decisions') }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-md transition-colors">Reset</a>
            </div>
        </form>
    </div>

    {{-- Decisions Table --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm overflow-hidden dark:shadow-none">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Timestamp</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Correlation ID</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Provider</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Category</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Trigger</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Window</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Scores</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-700 dark:text-slate-200 uppercase tracking-wider dark:text-slate-300">Debug</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($decisions as $decision)
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white whitespace-nowrap dark:text-slate-100">{{ $decision->created_at->format('Y-m-d H:i:s') }}</td>
                            <td class="px-4 py-3 text-xs font-mono text-gray-600 dark:text-gray-400">{{ Str::limit($decision->correlation_id, 12) }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $decision->chosen_provider == 'openai' ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' :
                                           ($decision->chosen_provider == 'vertex' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' :
                                            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200') }}">
                                        {{ $decision->chosen_provider }}
                                    </span>

                                    {{-- Phase 12.2: Provider Health Badge --}}
                                    @if(isset($decision->debug_metadata['provider_health']))
                                        @php
                                            $health = $decision->debug_metadata['provider_health'];
                                            $healthColor = match($health) {
                                                'critical' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                                'degraded' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-200',
                                                default => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
                                            };
                                            $healthIcon = match($health) {
                                                'critical' => '🔴',
                                                'degraded' => '🟡',
                                                default => '🟢'
                                            };
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full {{ $healthColor }}"
                                              title="Error Rate: {{ round(($decision->debug_metadata['error_rate'] ?? 0) * 100, 1) }}%">
                                            {{ $healthIcon }} {{ ucfirst($health) }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $decision->kategori->baslik ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $decision->reason_json['trigger'] ?? 'N/A' }}</span>

                                    {{-- Phase 12.2: Alert Level Badge --}}
                                    @if(isset($decision->reason_json['trigger']) && str_contains($decision->reason_json['trigger'], 'cost_guard'))
                                        @php
                                            $trigger = $decision->reason_json['trigger'];
                                            $alertBadge = match(true) {
                                                str_contains($trigger, 'kill_switch') => ['text' => '🚨 KILL SWITCH', 'class' => 'bg-red-600 text-white'],
                                                str_contains($trigger, 'downgrade') => ['text' => '⚠️ CRITICAL', 'class' => 'bg-orange-600 text-white'],
                                                default => ['text' => '⚡ WARNING', 'class' => 'bg-yellow-600 text-white']
                                            };
                                        @endphp
                                        <span class="inline-flex px-2 py-1 text-xs font-bold rounded {{ $alertBadge['class'] }}">
                                            {{ $alertBadge['text'] }}
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-400">{{ $decision->debug_metadata['window_used'] ?? 'N/A' }}</td>
                            <td class="px-4 py-3">
                                <button onclick="toggleJson('scores-{{ $decision->id }}')"
                                        class="px-3 py-1 text-xs bg-gray-200 hover:bg-gray-300 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-slate-200 rounded transition-colors">
                                    View
                                </button>
                                <div id="scores-{{ $decision->id }}" class="hidden mt-2">
                                    <pre class="bg-gray-100 dark:bg-slate-900 p-3 rounded text-xs overflow-x-auto">{{ json_encode($decision->scores_json, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <button onclick="toggleJson('debug-{{ $decision->id }}')"
                                        class="px-3 py-1 text-xs bg-blue-200 hover:bg-blue-300 dark:bg-blue-700 dark:hover:bg-blue-600 text-blue-800 dark:text-blue-200 rounded transition-colors">
                                    View
                                </button>
                                <div id="debug-{{ $decision->id }}" class="hidden mt-2">
                                    <pre class="bg-gray-100 dark:bg-slate-900 p-3 rounded text-xs overflow-x-auto">{{ json_encode($decision->debug_metadata, JSON_PRETTY_PRINT) }}</pre>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                                No decisions found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            {{ $decisions->links() }}
        </div>
    </div>
</div>

<script>
function toggleJson(id) {
    const element = document.getElementById(id);
    element.classList.toggle('hidden');
}
</script>
@endsection
