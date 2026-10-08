{{-- Workforce Chains Table --}}
<div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
    <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-base font-semibold text-gray-900 dark:text-white">AI Workforce Zincirleri</h3>
                <p class="mt-0.5 text-xs text-gray-500 dark:text-gray-400">
                    Son çalışan workforce chain'leri — ilan bazlı ajan yürütmeleri
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-purple-50 px-2.5 py-1 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-purple-600"></span>
                    {{ $stats['active_chains'] }} aktif
                </span>
                @if(($stats['failed_chains'] ?? 0) > 0)
                <span class="inline-flex items-center gap-1.5 rounded-full bg-red-50 px-2.5 py-1 text-xs font-medium text-red-700 dark:bg-red-900/30 dark:text-red-400">
                    <span class="h-1.5 w-1.5 rounded-full bg-red-600"></span>
                    {{ $stats['failed_chains'] }} başarısız
                </span>
                @endif
            </div>
        </div>
    </div>

    @if(empty($chains))
        <div class="px-6 py-12 text-center">
            <svg class="mx-auto h-10 w-10 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
            </svg>
            <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">Henüz workforce chain çalışmamış.</p>
        </div>
    @else
        <div class="divide-y divide-gray-100 dark:divide-slate-700">
            @foreach($chains as $chain)
                <div class="px-6 py-4 hover:bg-gray-50 dark:hover:bg-slate-700/40 transition-colors">
                    <div class="flex items-start justify-between gap-4">
                        <div class="min-w-0 flex-1">
                            {{-- Chain header --}}
                            <div class="flex items-center gap-3 flex-wrap">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium
                                    @if($chain['overall_status'] === 'completed') bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400
                                    @elseif($chain['overall_status'] === 'failed') bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400
                                    @elseif($chain['overall_status'] === 'running') bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400
                                    @else bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400
                                    @endif">
                                    {{ $chain['overall_status'] }}
                                </span>
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400" title="Chain ID">
                                    {{ \Illuminate\Support\Str::limit($chain['chain_id'], 24, '…') }}
                                </span>
                                @if($chain['ilan_id'])
                                    <a href="{{ route('admin.ilanlar.edit', $chain['ilan_id']) }}"
                                       class="text-xs font-medium text-blue-600 hover:text-blue-700 dark:text-blue-400 dark:hover:text-blue-300">
                                        İlan #{{ $chain['ilan_id'] }}
                                    </a>
                                @endif
                                <span class="text-xs text-gray-400 dark:text-gray-500">
                                    {{ $chain['started_at'] ? \Carbon\Carbon::parse($chain['started_at'])->diffForHumans() : '—' }}
                                </span>
                            </div>

                            {{-- Agent steps --}}
                            <div class="mt-2 flex items-center gap-1.5 flex-wrap">
                                @foreach($chain['agents'] as $agent)
                                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium
                                        @if($agent['status'] === 'completed') bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400
                                        @elseif($agent['status'] === 'failed') bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-400
                                        @elseif($agent['status'] === 'running') bg-blue-50 text-blue-700 dark:bg-blue-900/20 dark:text-blue-400
                                        @elseif($agent['status'] === 'skipped') bg-gray-100 text-gray-500 dark:bg-slate-700 dark:text-gray-400
                                        @else bg-yellow-50 text-yellow-700 dark:bg-yellow-900/20 dark:text-yellow-400
                                        @endif"
                                        title="{{ $agent['error'] ?? $agent['status'] }}">
                                        {{ $agent['agent'] }}
                                        @if($agent['duration_ms'])
                                            <span class="opacity-70">{{ round($agent['duration_ms'], 1) }}s</span>
                                        @endif
                                    </span>
                                    @if(!$loop->last)
                                        <svg class="h-3 w-3 flex-shrink-0 text-gray-300 dark:text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    @endif
                                @endforeach
                            </div>
                        </div>

                        {{-- Chain actions --}}
                        <div class="flex flex-shrink-0 items-center gap-1.5">
                            <span class="text-xs text-gray-400 dark:text-gray-500" title="Tamamlanan / Toplam">
                                {{ $chain['completed'] }}/{{ $chain['total_steps'] }}
                            </span>
                            @if($chain['failed'] > 0)
                                <form action="{{ route('admin.hermes.chain.abort', $chain['chain_id']) }}" method="POST" class="inline"
                                      onsubmit="return confirm('Zinciri iptal etmek istediğinize emin misiniz?')">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 hover:bg-red-100 dark:bg-red-900/30 dark:text-red-400 dark:hover:bg-red-900/50"
                                            title="Zinciri iptal et">
                                        İptal
                                    </button>
                                </form>
                            @endif
                            @if($chain['overall_status'] === 'completed' || $chain['overall_status'] === 'failed')
                                <form action="{{ route('admin.hermes.chain.resume', $chain['chain_id']) }}" method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                            class="inline-flex items-center gap-1 rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-400 dark:hover:bg-blue-900/50"
                                            title="Zinciri yeniden başlat">
                                        Devam
                                    </button>
                                </form>
                            @endif
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>
