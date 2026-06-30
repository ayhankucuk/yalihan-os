@extends('admin.layouts.admin')

@section('title', 'RuleSet Diff Viewer')

@section('content')
    <div class="container-fluid py-4 dark:bg-gray-900">
        <div class="mb-8 flex justify-between items-end">
            <div>
                <a href="{{ route('admin.property-hub.versions.index') }}"
                    class="inline-flex items-center text-blue-600 dark:text-blue-400 hover:underline text-sm mb-2">
                    <i class="fas fa-arrow-left mr-2"></i> Back to Versions
                </a>
                <h1 class="text-3xl font-extrabold text-slate-900 dark:text-white mb-2">RuleSet Diff Viewer</h1>
                <p class="text-slate-500 dark:text-slate-400">Comparing
                    <strong>{{ substr($version->version_hash, 0, 8) }}</strong> with
                    <strong>{{ substr($other->version_hash, 0, 8) }}</strong></p>
            </div>
        </div>

        @php
            $diff = app(\App\Modules\GovernanceCore\Services\RuleSetDiffService::class)->compare($other, $version);
        @endphp

        <div class="row">
            {{-- Side-by-Side View for Modified Rules --}}
            @if (count($diff['modified']) > 0)
                <div class="col-12 mb-4">
                    <h5 class="text-xl font-bold dark:text-white mb-6 flex items-center gap-2">
                        <i class="fas fa-edit text-amber-500"></i> Modified Rules
                    </h5>
                    @foreach ($diff['modified'] as $item)
                        <div
                            class="bg-white dark:bg-slate-800 shadow-sm rounded-xl mb-6 overflow-hidden border border-gray-100 dark:border-slate-700">
                            <div
                                class="px-6 py-3 border-b border-gray-100 dark:border-slate-700 bg-gray-50/50 dark:bg-slate-800/50">
                                <strong class="text-slate-900 dark:text-white">{{ $item['name'] }}</strong>
                            </div>
                            <div class="p-0">
                                <div class="grid grid-cols-1 md:grid-cols-2">
                                    <div
                                        class="border-r border-gray-100 dark:border-slate-700 p-6 bg-red-50 dark:bg-red-900/20">
                                        <div
                                            class="text-xs font-bold text-red-600 dark:text-red-400 uppercase tracking-wider mb-3">
                                            Before (Active/Old)</div>
                                        <pre class="text-xs font-mono text-slate-700 dark:text-slate-300 p-4 bg-gray-100 dark:bg-slate-950 rounded-lg"><code>{{ json_encode($item['before'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    </div>
                                    <div class="p-6 bg-green-50 dark:bg-green-900/20">
                                        <div
                                            class="text-xs font-bold text-green-600 dark:text-green-400 uppercase tracking-wider mb-3">
                                            After (New/Target)</div>
                                        <pre class="text-xs font-mono text-slate-700 dark:text-slate-300 p-4 bg-gray-100 dark:bg-slate-950 rounded-lg"><code>{{ json_encode($item['after'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Added Rules --}}
            @if (count($diff['added']) > 0)
                <div class="col-md-6 mb-8">
                    <h5 class="text-lg font-bold dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-plus-circle text-green-500"></i> Added Rules
                    </h5>
                    @foreach ($diff['added'] as $rule)
                        <div
                            class="bg-white dark:bg-slate-800 shadow-sm rounded-xl mb-3 p-6 border border-gray-100 dark:border-slate-700">
                            <strong class="text-slate-900 dark:text-white block mb-4">{{ $rule['name'] }}</strong>
                            <pre class="text-xs font-mono dark:text-slate-300 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg"><code>{{ json_encode($rule, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Removed Rules --}}
            @if (count($diff['removed']) > 0)
                <div class="col-md-6 mb-8">
                    <h5 class="text-lg font-bold dark:text-white mb-4 flex items-center gap-2">
                        <i class="fas fa-minus-circle text-red-500"></i> Removed Rules
                    </h5>
                    @foreach ($diff['removed'] as $rule)
                        <div
                            class="bg-white dark:bg-slate-800 shadow-sm rounded-xl mb-3 p-6 border border-gray-100 dark:border-slate-700 opacity-60">
                            <del class="text-slate-500 dark:text-slate-400 block mb-4 font-bold">{{ $rule['name'] }}</del>
                            <pre
                                class="text-xs font-mono text-slate-400 dark:text-slate-500 p-4 bg-slate-50 dark:bg-slate-900/50 rounded-lg line-through"><code>{{ json_encode($rule, JSON_PRETTY_PRINT) }}</code></pre>
                        </div>
                    @endforeach
                </div>
            @endif

            @if (empty($diff['added']) && empty($diff['removed']) && empty($diff['modified']))
                <div class="col-12">
                    <div class="alert alert-info dark:bg-gray-800 dark:text-info border-0">
                        No differences found between these versions.
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection
