@extends('admin.layouts.admin')

@section('title', 'AI Kontrol Merkezi')

@section('content')
<div class="mx-auto max-w-7xl" x-data="intelligenceCenter()">

    {{-- ═══════ HEADER ═══════ --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">AI Kontrol Merkezi</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Sistemi anlayın, kontrol edin, güvenin</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.governance.review-queue') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-700">
                Karar Kuyruğu
            </a>
            <a href="{{ route('admin.governance.decision-history') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-700">
                Karar Geçmişi
            </a>
            <form method="POST" action="{{ route('admin.governance.scan') }}" class="inline">
                @csrf
                <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    Pipeline Çalıştır
                </button>
            </form>
        </div>
    </div>

    {{-- ═══════ TAB NAVIGATION ═══════ --}}
    <div class="mb-6 border-b border-gray-200 dark:border-slate-700">
        <nav class="-mb-px flex gap-6 overflow-x-auto" aria-label="Tabs">
            @foreach([
                'overview' => 'Genel Bakış',
                'agents' => 'Agent Durumları',
                'feed' => 'Canlı Karar Akışı',
                'optimizer' => 'Optimizer Önerileri',
                'risk' => 'Risk Paneli',
                'memory' => 'Sistem Hafızası',
                'control' => 'Davranış Kontrolü',
            ] as $tab => $label)
                <button @click="activeTab = '{{ $tab }}'"
                        :class="activeTab === '{{ $tab }}'
                            ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400'
                            : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'"
                        class="whitespace-nowrap border-b-2 px-1 pb-3 text-sm font-medium transition-colors">
                    {{ $label }}
                    @if($tab === 'optimizer' && ($pendingSuggestions->count() ?? 0) > 0)
                        <span class="ml-1 rounded-full bg-red-500 px-1.5 py-0.5 text-xs text-white">{{ $pendingSuggestions->count() }}</span>
                    @endif
                    @if($tab === 'risk' && count($riskPanel['high_risk_decisions'] ?? []) > 0)
                        <span class="ml-1 rounded-full bg-yellow-500 px-1.5 py-0.5 text-xs text-white">{{ count($riskPanel['high_risk_decisions']) }}</span>
                    @endif
                </button>
            @endforeach
        </nav>
    </div>

    {{-- ═══════ TAB: OVERVIEW ═══════ --}}
    <div x-show="activeTab === 'overview'" x-transition>
        {{-- System Stats --}}
        <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
            @php
                $stats = [
                    ['label' => 'Toplam Bulgu', 'value' => $overview['total_findings'], 'color' => 'text-gray-900 dark:text-white'],
                    ['label' => 'Otomatik Çalışan', 'value' => $overview['auto_applied'], 'color' => 'text-green-600 dark:text-green-400', 'sub' => '%' . $overview['auto_run_rate'] . ' oto oran'],
                    ['label' => 'Bekleyen', 'value' => $overview['pending'], 'color' => $overview['pending'] > 0 ? 'text-amber-600 dark:text-amber-400' : 'text-gray-900 dark:text-white'],
                    ['label' => 'Geri Alınan', 'value' => $overview['rolled_back'], 'color' => $overview['rolled_back'] > 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-900 dark:text-white'],
                    ['label' => 'Bastırılan', 'value' => $overview['suppression_count'], 'color' => 'text-gray-900 dark:text-white'],
                ];
            @endphp
            @foreach($stats as $stat)
                <div class="rounded-lg border border-gray-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                    <p class="text-xs font-medium text-gray-500 dark:text-gray-400">{{ $stat['label'] }}</p>
                    <p class="mt-1 text-2xl font-bold {{ $stat['color'] }}">{{ $stat['value'] }}</p>
                    @if(isset($stat['sub']))
                        <p class="mt-0.5 text-xs text-gray-400">{{ $stat['sub'] }}</p>
                    @endif
                </div>
            @endforeach
        </div>

        {{-- Decision status breakdown --}}
        <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Karar Dağılımı</h3>
            <div class="flex flex-wrap gap-3">
                @foreach([
                    'pending' => ['Bekleyen', 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'],
                    'approved' => ['Onaylanan', 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'],
                    'rejected' => ['Reddedilen', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'],
                    'auto_applied' => ['Oto Çalışan', 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400'],
                    'blocked' => ['Engellenen', 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300'],
                    'failed' => ['Başarısız', 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'],
                    'rolled_back' => ['Geri Alınan', 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400'],
                    'overridden' => ['Override', 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400'],
                ] as $key => $cfg)
                    <span class="rounded-full px-3 py-1.5 text-sm font-medium {{ $cfg[1] }}">
                        {{ $cfg[0] }}: {{ $overview[$key] ?? 0 }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Learning Metrics --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Karar Doğruluğu</p>
                @php $accuracy = ($learningMetrics['decision_accuracy'] ?? 0) * 100; @endphp
                <p class="mt-2 text-3xl font-bold {{ $accuracy >= 90 ? 'text-green-600 dark:text-green-400' : ($accuracy >= 70 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                    %{{ number_format($accuracy, 1) }}
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Bekleyen Öneriler</p>
                <p class="mt-2 text-3xl font-bold text-indigo-600 dark:text-indigo-400">
                    {{ $learningMetrics['pending_suggestions'] ?? 0 }}
                </p>
            </div>
            <div class="rounded-lg border border-gray-200 bg-white p-5 dark:border-slate-700 dark:bg-slate-800">
                <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Ort. Güven Skoru</p>
                @php $avgConf = ($learningMetrics['avg_confidence'] ?? 0) * 100; @endphp
                <p class="mt-2 text-3xl font-bold text-gray-900 dark:text-white">%{{ number_format($avgConf, 0) }}</p>
            </div>
        </div>
    </div>

    {{-- ═══════ TAB: AGENTS ═══════ --}}
    <div x-show="activeTab === 'agents'" x-transition>
        <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
            @foreach($agentHealth as $agentName => $health)
                @php
                    $statusConfig = match($health['agent_durumu'] ?? 'idle') {
                        'healthy' => ['bg-green-50 border-green-200 dark:bg-green-900/20 dark:border-green-800', 'text-green-700 dark:text-green-400', 'bg-green-100 dark:bg-green-900/40', '🟢 Sağlıklı'],
                        'degraded' => ['bg-yellow-50 border-yellow-200 dark:bg-yellow-900/20 dark:border-yellow-800', 'text-yellow-700 dark:text-yellow-400', 'bg-yellow-100 dark:bg-yellow-900/40', '🟡 Düşük'],
                        'critical' => ['bg-red-50 border-red-200 dark:bg-red-900/20 dark:border-red-800', 'text-red-700 dark:text-red-400', 'bg-red-100 dark:bg-red-900/40', '🔴 Kritik'],
                        default => ['bg-gray-50 border-gray-200 dark:bg-slate-800 dark:border-slate-700', 'text-gray-500 dark:text-gray-400', 'bg-gray-100 dark:bg-slate-700', '⚪ Bekleniyor'],
                    };
                    $agentLabels = [
                        'cortex' => ['Cortex', 'Tespit — bulgu toplama'],
                        'governance' => ['Governance', 'Karar — risk sınıflaması'],
                        'execution' => ['Execution', 'Eylem — proposal oluşturma'],
                        'optimizer' => ['Optimizer', 'Öğrenme — pattern analizi'],
                        'watcher' => ['Watcher', 'Koordinasyon — pipeline yönetimi'],
                    ];
                    $label = $agentLabels[$agentName] ?? [ucfirst($agentName), '—'];
                @endphp
                <div class="rounded-lg border p-4 {{ $statusConfig[0] }}">
                    <div class="flex items-center justify-between">
                        <h3 class="text-sm font-bold {{ $statusConfig[1] }}">{{ $label[0] }}</h3>
                        <span class="text-xs font-medium {{ $statusConfig[1] }}">{{ $statusConfig[3] }}</span>
                    </div>
                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $label[1] }}</p>
                    <div class="mt-3 space-y-1.5 text-xs text-gray-600 dark:text-gray-300">
                        <div class="flex justify-between">
                            <span>Son Çalışma</span>
                            <span class="font-mono">{{ $health['last_run'] ? \Carbon\Carbon::parse($health['last_run'])->diffForHumans() : '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Süre</span>
                            <span class="font-mono">{{ $health['last_duration_ms'] ? $health['last_duration_ms'] . 'ms' : '—' }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span>Hata (24s)</span>
                            <span class="font-mono {{ ($health['recent_failures'] ?? 0) > 0 ? 'font-bold text-red-600 dark:text-red-400' : '' }}">
                                {{ $health['recent_failures'] ?? 0 }}
                            </span>
                        </div>
                        <div class="flex justify-between">
                            <span>Toplam (24s)</span>
                            <span class="font-mono">{{ $health['total_runs_24h'] ?? 0 }}</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Recent Agent Runs Table --}}
        <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Son Çalışmalar</h3>
        <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            @if($recentRuns->isEmpty())
                <p class="px-6 py-8 text-center text-sm text-gray-500 dark:text-gray-400">Henüz agent çalışması yok.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Agent</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Durum</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Süre</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bulgu</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Karar</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Zaman</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Hata</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($recentRuns as $run)
                                @php
                                    $runBadge = match($run->agent_durumu) {
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'running' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300',
                                    };
                                    $agentColor = match($run->agent_name) {
                                        'cortex' => 'text-cyan-600 dark:text-cyan-400',
                                        'governance' => 'text-violet-600 dark:text-violet-400',
                                        'execution' => 'text-emerald-600 dark:text-emerald-400',
                                        'optimizer' => 'text-amber-600 dark:text-amber-400',
                                        'watcher' => 'text-indigo-600 dark:text-indigo-400',
                                        default => 'text-gray-600 dark:text-gray-400',
                                    };
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                    <td class="whitespace-nowrap px-4 py-2.5 text-sm font-medium {{ $agentColor }}">{{ ucfirst($run->agent_name) }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-sm">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $runBadge }}">{{ $run->agent_durumu }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-sm font-mono text-gray-600 dark:text-gray-300">{{ $run->duration_ms ? $run->duration_ms . 'ms' : '—' }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">{{ $run->findings_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">{{ $run->decisions_count }}</td>
                                    <td class="whitespace-nowrap px-4 py-2.5 text-sm text-gray-500 dark:text-gray-400">{{ $run->started_at->diffForHumans() }}</td>
                                    <td class="max-w-xs truncate px-4 py-2.5 text-xs text-red-600 dark:text-red-400">{{ $run->error_message }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════ TAB: LIVE FEED ═══════ --}}
    <div x-show="activeTab === 'feed'" x-transition>
        <div class="rounded-lg border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            @if(empty($liveFeed))
                <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">Henüz karar kaydı yok.</p>
            @else
                <div class="divide-y divide-gray-100 dark:divide-slate-700">
                    @foreach($liveFeed as $entry)
                        @php
                            $feedBadge = match($entry['karar_durumu']) {
                                'auto_applied' => ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'OTO'],
                                'approved' => ['bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 'ONAY'],
                                'rejected' => ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'RED'],
                                'pending' => ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', 'BEKL.'],
                                'blocked' => ['bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300', 'ENGEL'],
                                'failed' => ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'HATA'],
                                'rolled_back' => ['bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'GERİ'],
                                default => ['bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300', strtoupper($entry['karar_durumu'])],
                            };
                            $severityDot = match($entry['severity']) {
                                'critical' => 'bg-red-500',
                                'high' => 'bg-orange-500',
                                'medium' => 'bg-yellow-500',
                                'low' => 'bg-green-500',
                                default => 'bg-gray-400',
                            };
                        @endphp
                        <a href="{{ route('admin.governance.decisions.show', $entry['id']) }}"
                           class="flex items-center gap-3 px-4 py-3 hover:bg-gray-50 dark:hover:bg-slate-700/50">
                            <span class="text-xs font-mono text-gray-400 dark:text-gray-500 w-10">{{ $entry['time'] }}</span>
                            <span class="h-2 w-2 rounded-full {{ $severityDot }} flex-shrink-0"></span>
                            <span class="min-w-0 flex-1 truncate text-sm text-gray-800 dark:text-gray-200">{{ $entry['title'] }}</span>
                            <span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $feedBadge[0] }}">{{ $feedBadge[1] }}</span>
                            @if($entry['confidence'])
                                <span class="text-xs font-mono text-gray-400">%{{ number_format($entry['confidence'] * 100, 0) }}</span>
                            @endif
                            @if($entry['is_overridden'])
                                <span class="text-xs text-indigo-500">override</span>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════ TAB: OPTIMIZER ═══════ --}}
    <div x-show="activeTab === 'optimizer'" x-transition>
        {{-- Pending Suggestions --}}
        <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800">
            @if($pendingSuggestions->isEmpty())
                <p class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">Henüz bekleyen öneri yok. Pipeline çalıştırarak optimizer'ı tetikleyin.</p>
            @else
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tip</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Hedef Kural</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Değişiklik</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Neden</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Güven</th>
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">İşlem</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($pendingSuggestions as $suggestion)
                                @php
                                    $typeBadge = match($suggestion->suggestion_type) {
                                        'rule_sensitivity' => ['bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400', 'Hassasiyet'],
                                        'threshold_adjustment' => ['bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-400', 'Eşik'],
                                        'automation_upgrade' => ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', 'Otomasyon'],
                                        'structural_issue' => ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', 'Yapısal'],
                                        'policy_adjustment' => ['bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', 'Politika'],
                                        default => ['bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300', ucfirst($suggestion->suggestion_type)],
                                    };
                                    $confColor = $suggestion->confidence >= 0.8
                                        ? 'text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400'
                                        : ($suggestion->confidence >= 0.6
                                            ? 'text-yellow-700 bg-yellow-100 dark:bg-yellow-900/30 dark:text-yellow-400'
                                            : 'text-red-700 bg-red-100 dark:bg-red-900/30 dark:text-red-400');
                                @endphp
                                <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $typeBadge[0] }}">{{ $typeBadge[1] }}</span>
                                    </td>
                                    <td class="px-4 py-3 text-sm font-mono text-gray-900 dark:text-white">{{ $suggestion->target_rule }}</td>
                                    <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                        <span class="text-gray-400">{{ $suggestion->current_value }}</span>
                                        <span class="mx-1 text-gray-300">→</span>
                                        <span class="font-medium text-gray-900 dark:text-white">{{ $suggestion->suggested_value }}</span>
                                    </td>
                                    <td class="max-w-xs px-4 py-3 text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($suggestion->reason, 100) }}</td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $confColor }}">%{{ number_format($suggestion->confidence * 100, 0) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-sm">
                                        <div class="flex gap-2">
                                            <form method="POST" action="{{ route('admin.governance.suggestions.approve', $suggestion) }}">
                                                @csrf
                                                <button type="submit" class="rounded bg-green-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-green-700">Onayla</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.governance.suggestions.reject', $suggestion) }}">
                                                @csrf
                                                <button type="submit" class="rounded bg-red-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-red-700">Reddet</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Applied History --}}
        @if($appliedSuggestions->isNotEmpty())
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Uygulanan Öneriler</h3>
            <div class="space-y-2">
                @foreach($appliedSuggestions as $applied)
                    <div class="flex items-center justify-between rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-900/20">
                        <div>
                            <span class="font-mono text-sm font-medium text-green-800 dark:text-green-400">{{ $applied->target_rule }}</span>
                            <span class="ml-2 text-xs text-green-600 dark:text-green-500">{{ $applied->suggestion_type }}</span>
                        </div>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $applied->applied_at?->diffForHumans() }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ═══════ TAB: RISK PANEL ═══════ --}}
    <div x-show="activeTab === 'risk'" x-transition>
        {{-- High Risk Decisions --}}
        <div class="mb-6">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                <span class="h-2 w-2 rounded-full bg-red-500"></span>
                Yüksek Riskli Kararlar
            </h3>
            @if(empty($riskPanel['high_risk_decisions']))
                <p class="rounded-lg border border-green-200 bg-green-50 px-4 py-6 text-center text-sm text-green-700 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
                    Yüksek riskli karar bulunmuyor
                </p>
            @else
                <div class="space-y-2">
                    @foreach($riskPanel['high_risk_decisions'] as $rd)
                        <a href="{{ route('admin.governance.decisions.show', $rd['id']) }}"
                           class="flex items-center justify-between rounded-lg border border-red-200 bg-red-50 p-3 hover:bg-red-100 dark:border-red-800 dark:bg-red-900/20 dark:hover:bg-red-900/30">
                            <div class="min-w-0 flex-1">
                                <span class="text-sm font-medium text-red-800 dark:text-red-400">{{ $rd['title'] }}</span>
                                <span class="ml-2 text-xs text-red-500">{{ $rd['source'] }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:bg-red-900/40 dark:text-red-400">{{ $rd['karar_durumu'] }}</span>
                                @if($rd['confidence'])
                                    <span class="text-xs font-mono text-gray-400">%{{ number_format($rd['confidence'] * 100, 0) }}</span>
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Frequent Rollback Sources --}}
        <div class="mb-6">
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                <span class="h-2 w-2 rounded-full bg-yellow-500"></span>
                Sık Geri Alınan Kaynaklar (30 gün)
            </h3>
            @if(empty($riskPanel['rollback_sources']))
                <p class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 text-center text-sm text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-400">Geri alım kaydı yok</p>
            @else
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kaynak</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Domain</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Geri Alım</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($riskPanel['rollback_sources'] as $rs)
                                <tr>
                                    <td class="px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white">{{ $rs['source'] }}</td>
                                    <td class="px-4 py-2.5 text-sm text-gray-600 dark:text-gray-300">{{ $rs['domain'] }}</td>
                                    <td class="px-4 py-2.5 text-sm font-bold text-red-600 dark:text-red-400">{{ $rs['rollback_count'] }}x</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

        {{-- Unstable Rules --}}
        <div>
            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-900 dark:text-white">
                <span class="h-2 w-2 rounded-full bg-orange-500"></span>
                Kararsız Kurallar (hem otomatik hem geri alım/hata)
            </h3>
            @if(empty($riskPanel['unstable_rules']))
                <p class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 text-center text-sm text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-400">Kararsız kural yok</p>
            @else
                <div class="space-y-2">
                    @foreach($riskPanel['unstable_rules'] as $ur)
                        <div class="rounded-lg border border-orange-200 bg-orange-50 p-3 dark:border-orange-800 dark:bg-orange-900/20">
                            <span class="font-mono text-sm font-medium text-orange-800 dark:text-orange-400">{{ $ur['rule'] }}</span>
                            <div class="mt-1 flex gap-2">
                                @foreach($ur['breakdown'] as $status => $count)
                                    <span class="text-xs text-gray-600 dark:text-gray-300">{{ $status }}: {{ $count }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════ TAB: SYSTEM MEMORY ═══════ --}}
    <div x-show="activeTab === 'memory'" x-transition>
        {{-- Agent Memories --}}
        <div class="mb-6">
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Agent Hafızası</h3>
            @if(empty($systemMemory['agent_memories']))
                <p class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-6 text-center text-sm text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-400">
                    Henüz hafıza kaydı yok
                </p>
            @else
                @foreach($systemMemory['agent_memories'] as $agentName => $memories)
                    <div class="mb-4">
                        @php
                            $memColor = match($agentName) {
                                'cortex' => 'border-cyan-200 dark:border-cyan-800',
                                'governance' => 'border-violet-200 dark:border-violet-800',
                                'execution' => 'border-emerald-200 dark:border-emerald-800',
                                'optimizer' => 'border-amber-200 dark:border-amber-800',
                                'watcher' => 'border-indigo-200 dark:border-indigo-800',
                                default => 'border-gray-200 dark:border-slate-700',
                            };
                        @endphp
                        <h4 class="mb-2 text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">{{ ucfirst($agentName) }}</h4>
                        <div class="space-y-2">
                            @foreach($memories as $mem)
                                <div class="rounded-lg border {{ $memColor }} bg-white p-3 dark:bg-slate-800" x-data="{ expanded: false }">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-sm text-gray-900 dark:text-white">{{ $mem['key'] }}</span>
                                            <span class="rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-slate-700 dark:text-gray-400">{{ $mem['type'] }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-gray-400">
                                            <span>{{ $mem['updated'] }}</span>
                                            <button @click="expanded = !expanded" class="text-indigo-500 hover:underline">
                                                <span x-text="expanded ? 'Gizle' : 'Göster'">Göster</span>
                                            </button>
                                        </div>
                                    </div>
                                    <div x-show="expanded" x-transition class="mt-2">
                                        <pre class="max-h-40 overflow-auto rounded bg-gray-50 p-2 text-xs text-gray-700 dark:bg-slate-900 dark:text-gray-300">{{ json_encode($mem['value'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @if($mem['expires'])
                                            <p class="mt-1 text-xs text-gray-400">Son kullanma: {{ $mem['expires'] }}</p>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endif
        </div>

        {{-- Optimizer Decision History --}}
        <div>
            <h3 class="mb-3 text-sm font-semibold text-gray-900 dark:text-white">Optimizer Karar Geçmişi</h3>
            @if(empty($systemMemory['optimizer_history']))
                <p class="rounded-lg border border-gray-200 bg-gray-50 px-4 py-4 text-center text-sm text-gray-500 dark:border-slate-700 dark:bg-slate-800 dark:text-gray-400">Henüz optimizer geçmişi yok</p>
            @else
                <div class="overflow-hidden rounded-lg border border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                        <thead class="bg-gray-50 dark:bg-slate-900/50">
                            <tr>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tip</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Hedef</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Sonuç</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Güven</th>
                                <th class="px-4 py-2.5 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Tarih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                            @foreach($systemMemory['optimizer_history'] as $oh)
                                @php
                                    $resultBadge = match($oh['oneri_durumu']) {
                                        'applied' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'rejected' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-slate-700 dark:text-gray-300',
                                    };
                                @endphp
                                <tr>
                                    <td class="px-4 py-2.5 text-xs text-gray-600 dark:text-gray-300">{{ $oh['type'] }}</td>
                                    <td class="px-4 py-2.5 text-sm font-mono text-gray-900 dark:text-white">{{ $oh['target'] }}</td>
                                    <td class="px-4 py-2.5 text-sm">
                                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $resultBadge }}">{{ $oh['oneri_durumu'] }}</span>
                                    </td>
                                    <td class="px-4 py-2.5 text-xs font-mono text-gray-500">%{{ number_format($oh['confidence'] * 100, 0) }}</td>
                                    <td class="px-4 py-2.5 text-xs text-gray-500 dark:text-gray-400">{{ $oh['date'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════ TAB: BEHAVIOR CONTROL ═══════ --}}
    <div x-show="activeTab === 'control'" x-transition>
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            {{-- Safe Mode Toggle --}}
            <div class="rounded-lg border-2 {{ $behaviorSettings['safe_mode'] ? 'border-blue-500 bg-blue-50 dark:border-blue-600 dark:bg-blue-900/20' : 'border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800' }} p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold {{ $behaviorSettings['safe_mode'] ? 'text-blue-800 dark:text-blue-400' : 'text-gray-900 dark:text-white' }}">
                            {{ $behaviorSettings['safe_mode'] ? '🔵 Güvenli Mod AÇIK' : 'Güvenli Mod' }}
                        </h3>
                        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                            Açıkken: otomatik çalıştırma devre dışı, tüm kararlar incelemeye gider.
                        </p>
                    </div>
                    <form method="POST" action="{{ route('admin.governance.behavior.toggle-safe-mode') }}">
                        @csrf
                        <button type="submit"
                                class="rounded-lg px-4 py-2 text-sm font-medium {{ $behaviorSettings['safe_mode']
                                    ? 'bg-gray-600 text-white hover:bg-gray-700'
                                    : 'bg-blue-600 text-white hover:bg-blue-700' }}">
                            {{ $behaviorSettings['safe_mode'] ? 'Kapat' : 'Güvenli Moda Geç' }}
                        </button>
                    </form>
                </div>
            </div>

            {{-- Settings Card --}}
            <div class="rounded-lg border border-gray-200 bg-white p-6 dark:border-slate-700 dark:bg-slate-800">
                <h3 class="mb-4 text-lg font-bold text-gray-900 dark:text-white">AI Davranış Parametreleri</h3>
                <form method="POST" action="{{ route('admin.governance.behavior.update') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Otomatik Çalıştırma Eşiği</label>
                        <select name="auto_run_threshold"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                            @foreach(['low' => 'Düşük (sadece LOW severity)', 'medium' => 'Orta (LOW + MEDIUM)', 'high' => 'Yüksek (tümü)', 'critical' => 'Kritik (sadece explicit)'] as $val => $lbl)
                                <option value="{{ $val }}" {{ $behaviorSettings['auto_run_threshold'] === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-400">Hangi seviyedeki bulgular otomatik çalıştırılsın?</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Risk Toleransı</label>
                        <select name="risk_tolerance"
                                class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                            @foreach(['low' => 'Düşük (çok temkinli)', 'medium' => 'Orta (dengeli)', 'high' => 'Yüksek (agresif)'] as $val => $lbl)
                                <option value="{{ $val }}" {{ $behaviorSettings['risk_tolerance'] === $val ? 'selected' : '' }}>{{ $lbl }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Minimum Güven Skoru</label>
                        <input type="number" name="confidence_minimum" step="0.05" min="0" max="1"
                               value="{{ $behaviorSettings['confidence_minimum'] }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-400">Bu değer altındaki güven skoru → otomatik yerine incelemeye gönderilir.</p>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Günlük Maksimum Otomatik İşlem</label>
                        <input type="number" name="max_daily_actions" min="0" max="500"
                               value="{{ $behaviorSettings['max_daily_actions'] }}"
                               class="mt-1 block w-full rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-700 dark:text-white">
                        <p class="mt-1 text-xs text-gray-400">Bu limitin üstünde tüm işlemler otomatik olarak incelemeye yönlendirilir.</p>
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                        Ayarları Kaydet
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    function intelligenceCenter() {
        return {
            activeTab: 'overview',
        };
    }
</script>
@endpush
