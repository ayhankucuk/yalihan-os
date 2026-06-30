@extends('admin.layouts.admin')

@section('title', 'Aksiyon Döngüsü')

@section('content')
<div class="mx-auto max-w-7xl">

    {{-- ═══════ HEADER ═══════ --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Aksiyon Döngüsü</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">SAB8 — Karar → Aksiyon → Sonuç → Öğrenme</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.governance.intelligence-center') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-700">
                AI Kontrol Merkezi
            </a>
            <a href="{{ route('admin.governance.review-queue') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-700">
                Karar Kuyruğu
            </a>
        </div>
    </div>

    {{-- ═══════ PERIOD SELECTOR ═══════ --}}
    <div class="mb-6 flex items-center gap-2">
        @foreach(['24h' => 'Son 24 Saat', '7d' => '7 Gün', '30d' => '30 Gün', '90d' => '90 Gün'] as $p => $label)
            <a href="{{ route('admin.governance.action-dashboard', ['period' => $p, 'tab' => $tab]) }}"
               class="rounded-lg px-3 py-1.5 text-sm font-medium {{ $period === $p ? 'bg-indigo-600 text-white dark:bg-indigo-700' : 'bg-gray-100 text-gray-700 hover:bg-gray-200 dark:bg-slate-700 dark:text-gray-300 dark:hover:bg-slate-600' }}">
                {{ $label }}
            </a>
        @endforeach
    </div>

    {{-- ═══════ STATS GRID ═══════ --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        {{-- Total --}}
        <div class="rounded-lg border border-gray-200 bg-white p-4 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Toplam Karar</p>
            <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $stats['total'] }}</p>
        </div>
        {{-- Applied --}}
        <div class="rounded-lg border border-green-200 bg-green-50 p-4 shadow-sm dark:border-green-800 dark:bg-green-900/10">
            <p class="text-xs font-medium text-green-700 dark:text-green-400">Uygulanan</p>
            <p class="mt-1 text-2xl font-bold text-green-800 dark:text-green-300">{{ $stats['applied'] }}</p>
            <p class="text-[10px] text-green-600 dark:text-green-500">{{ $stats['auto_applied'] }} oto + {{ $stats['approved'] }} onay</p>
        </div>
        {{-- Success Rate --}}
        <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 shadow-sm dark:border-blue-800 dark:bg-blue-900/10">
            <p class="text-xs font-medium text-blue-700 dark:text-blue-400">Başarı Oranı</p>
            <p class="mt-1 text-2xl font-bold {{ $stats['success_rate'] >= 80 ? 'text-green-700 dark:text-green-400' : ($stats['success_rate'] >= 50 ? 'text-yellow-700 dark:text-yellow-400' : 'text-red-700 dark:text-red-400') }}">
                %{{ $stats['success_rate'] }}
            </p>
        </div>
        {{-- Failed --}}
        <div class="rounded-lg border border-red-200 bg-red-50 p-4 shadow-sm dark:border-red-800 dark:bg-red-900/10">
            <p class="text-xs font-medium text-red-700 dark:text-red-400">Başarısız</p>
            <p class="mt-1 text-2xl font-bold text-red-800 dark:text-red-300">{{ $stats['failed'] + $stats['action_failed'] }}</p>
        </div>
        {{-- Rollback --}}
        <div class="rounded-lg border border-purple-200 bg-purple-50 p-4 shadow-sm dark:border-purple-800 dark:bg-purple-900/10">
            <p class="text-xs font-medium text-purple-700 dark:text-purple-400">Geri Alınan</p>
            <p class="mt-1 text-2xl font-bold text-purple-800 dark:text-purple-300">{{ $stats['rolled_back'] }}</p>
            <p class="text-[10px] text-purple-600 dark:text-purple-500">%{{ $stats['rollback_rate'] }} oran</p>
        </div>
        {{-- Impact --}}
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-4 shadow-sm dark:border-amber-800 dark:bg-amber-900/10">
            <p class="text-xs font-medium text-amber-700 dark:text-amber-400">Ort. Etki</p>
            <p class="mt-1 text-2xl font-bold text-amber-800 dark:text-amber-300">
                {{ $stats['avg_impact_score'] !== null ? ($stats['avg_impact_score'] > 0 ? '+' : '') . $stats['avg_impact_score'] : '—' }}
            </p>
            @if($stats['positive_impact'] > 0 || $stats['negative_impact'] > 0)
                <p class="text-[10px] text-amber-600 dark:text-amber-500">
                    {{ $stats['positive_impact'] }} pozitif · {{ $stats['negative_impact'] }} negatif
                </p>
            @endif
        </div>
    </div>

    {{-- ═══════ ACTION TYPE BREAKDOWN ═══════ --}}
    @if(count($stats['action_type_stats']) > 0)
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Aksiyon Tipi Performansı</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="pb-3">Aksiyon</th>
                        <th class="pb-3">Toplam</th>
                        <th class="pb-3">Başarılı</th>
                        <th class="pb-3">Başarısız</th>
                        <th class="pb-3">Başarı %</th>
                        <th class="pb-3">Ort. Etki</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 dark:text-gray-300">
                    @foreach($stats['action_type_stats'] as $at)
                        <tr class="border-t border-gray-100 dark:border-slate-700">
                            <td class="py-2 font-medium">{{ $at['action'] }}</td>
                            <td class="py-2">{{ $at['total'] }}</td>
                            <td class="py-2 text-green-600 dark:text-green-400">{{ $at['successful'] }}</td>
                            <td class="py-2 text-red-600 dark:text-red-400">{{ $at['failed'] }}</td>
                            <td class="py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $at['success_rate'] >= 80 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                       ($at['success_rate'] >= 50 ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' :
                                       'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400') }}">
                                    %{{ $at['success_rate'] }}
                                </span>
                            </td>
                            <td class="py-2">{{ $at['avg_impact'] !== null ? ($at['avg_impact'] > 0 ? '+' : '') . $at['avg_impact'] : '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    {{-- ═══════ TAB NAVIGATION ═══════ --}}
    <div class="mb-4 flex flex-wrap gap-1 border-b border-gray-200 dark:border-slate-700">
        @foreach([
            'all' => ['label' => 'Tümü', 'count' => $stats['total']],
            'applied' => ['label' => 'Uygulananlar', 'count' => $stats['applied']],
            'auto' => ['label' => 'Oto-Uygulanan', 'count' => $stats['auto_applied']],
            'pending' => ['label' => 'Bekleyenler', 'count' => $stats['pending']],
            'failed' => ['label' => 'Başarısızlar', 'count' => $stats['failed'] + $stats['action_failed']],
            'rolled_back' => ['label' => 'Geri Alınanlar', 'count' => $stats['rolled_back']],
        ] as $key => $info)
            <a href="{{ route('admin.governance.action-dashboard', ['period' => $period, 'tab' => $key]) }}"
               class="relative px-4 py-2.5 text-sm font-medium {{ $tab === $key
                   ? 'border-b-2 border-indigo-600 text-indigo-600 dark:border-indigo-400 dark:text-indigo-400'
                   : 'text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-300' }}">
                {{ $info['label'] }}
                @if($info['count'] > 0)
                    <span class="ml-1 rounded-full bg-gray-100 px-1.5 py-0.5 text-[10px] font-bold text-gray-600 dark:bg-slate-700 dark:text-gray-400">{{ $info['count'] }}</span>
                @endif
            </a>
        @endforeach
    </div>

    {{-- ═══════ DECISION TABLE ═══════ --}}
    <div class="rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-gray-200 text-xs text-gray-500 dark:border-slate-700 dark:text-gray-400">
                    <tr>
                        <th class="px-4 py-3">Bulgu</th>
                        <th class="px-4 py-3">Kaynak</th>
                        <th class="px-4 py-3">Ciddiyet</th>
                        <th class="px-4 py-3">Durum</th>
                        <th class="px-4 py-3">Sonuç</th>
                        <th class="px-4 py-3">Etki</th>
                        <th class="px-4 py-3">Güven</th>
                        <th class="px-4 py-3">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($decisions as $d)
                        <tr class="border-t border-gray-100 dark:border-slate-700 hover:bg-gray-50 dark:hover:bg-slate-750">
                            {{-- Title --}}
                            <td class="px-4 py-3">
                                <a href="{{ route('admin.governance.decisions.show', $d) }}"
                                   class="font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                                    {{ Str::limit($d->title, 50) }}
                                </a>
                                <p class="text-[10px] text-gray-400 dark:text-gray-500">{{ $d->finding_id }}</p>
                            </td>
                            {{-- Source --}}
                            <td class="px-4 py-3 text-xs text-gray-600 dark:text-gray-400">{{ $d->source }}</td>
                            {{-- Severity --}}
                            <td class="px-4 py-3">
                                @php
                                    $sevColor = match($d->severity->value ?? $d->severity) {
                                        'low' => 'green', 'medium' => 'yellow', 'high' => 'orange', 'critical' => 'red', default => 'gray'
                                    };
                                @endphp
                                <span class="rounded-full bg-{{ $sevColor }}-100 px-2 py-0.5 text-xs font-medium text-{{ $sevColor }}-800 dark:bg-{{ $sevColor }}-900/30 dark:text-{{ $sevColor }}-400">
                                    {{ strtoupper($d->severity->value ?? $d->severity) }}
                                </span>
                            </td>
                            {{-- Status --}}
                            <td class="px-4 py-3">
                                @php $statusColor = $d->getStatusColor(); @endphp
                                <span class="rounded-full bg-{{ $statusColor }}-100 px-2 py-0.5 text-xs font-medium text-{{ $statusColor }}-800 dark:bg-{{ $statusColor }}-900/30 dark:text-{{ $statusColor }}-400">
                                    {{ $d->getStatusLabel() }}
                                </span>
                                @if($d->karar_durumu === 'auto_applied')
                                    <span class="ml-1 text-[10px] text-blue-500 dark:text-blue-400" title="AI tarafından otomatik uygulandı">🤖</span>
                                @endif
                                @if($d->isOverridden())
                                    <span class="ml-1 text-[10px] text-amber-500" title="Override edildi">⚡</span>
                                @endif
                            </td>
                            {{-- Result --}}
                            <td class="px-4 py-3">
                                @if($d->hasResult())
                                    @if($d->wasSuccessful())
                                        <span class="text-green-600 dark:text-green-400">✓ Başarılı</span>
                                    @else
                                        <span class="text-red-600 dark:text-red-400">✗ Başarısız</span>
                                    @endif
                                @elseif(in_array($d->karar_durumu, ['approved', 'auto_applied']))
                                    <span class="text-gray-400 text-xs">Sonuç bekleniyor</span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            {{-- Impact --}}
                            <td class="px-4 py-3">
                                @if($d->impact_score !== null)
                                    <span class="font-mono text-sm font-bold {{ $d->impact_score > 0 ? 'text-green-600 dark:text-green-400' : ($d->impact_score < 0 ? 'text-red-600 dark:text-red-400' : 'text-gray-500') }}">
                                        {{ $d->impact_score > 0 ? '+' : '' }}{{ $d->impact_score }}
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            {{-- Confidence --}}
                            <td class="px-4 py-3">
                                @if($d->confidence !== null)
                                    <span class="text-xs {{ $d->confidence >= 0.8 ? 'text-green-600 dark:text-green-400' : ($d->confidence >= 0.5 ? 'text-yellow-600 dark:text-yellow-400' : 'text-red-600 dark:text-red-400') }}">
                                        %{{ round($d->confidence * 100) }}
                                    </span>
                                @else
                                    <span class="text-gray-300 dark:text-gray-600">—</span>
                                @endif
                            </td>
                            {{-- Actions --}}
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-1">
                                    @if($d->karar_durumu === 'pending')
                                        <form method="POST" action="{{ route('admin.governance.decisions.approve', $d) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded bg-green-600 px-2 py-1 text-[10px] font-medium text-white hover:bg-green-700" title="Onayla">✓</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.governance.decisions.reject', $d) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded bg-red-600 px-2 py-1 text-[10px] font-medium text-white hover:bg-red-700" title="Reddet">✗</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.governance.decisions.simulate', $d) }}" class="inline">
                                            @csrf
                                            <button type="submit" class="rounded bg-amber-500 px-2 py-1 text-[10px] font-medium text-white hover:bg-amber-600" title="Simüle et">🧪</button>
                                        </form>
                                    @endif
                                    @if($d->isRollbackable())
                                        <a href="{{ route('admin.governance.decisions.show', $d) }}"
                                           class="rounded bg-purple-600 px-2 py-1 text-[10px] font-medium text-white hover:bg-purple-700" title="Geri al">↩</a>
                                    @endif
                                    <a href="{{ route('admin.governance.decisions.show', $d) }}"
                                       class="rounded bg-gray-200 px-2 py-1 text-[10px] font-medium text-gray-700 hover:bg-gray-300 dark:bg-slate-600 dark:text-gray-300 dark:hover:bg-slate-500" title="Detay">→</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-8 text-center text-sm text-gray-500 dark:text-gray-400">
                                Bu filtrede karar bulunamadı.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($decisions->hasPages())
            <div class="border-t border-gray-200 px-4 py-3 dark:border-slate-700">
                {{ $decisions->appends(['period' => $period, 'tab' => $tab])->links() }}
            </div>
        @endif
    </div>

    {{-- ═══════ RECENT RESULTS ═══════ --}}
    @if($recentResults->count() > 0)
    <div class="mt-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Son Aksiyon Sonuçları</h3>
        <div class="space-y-3">
            @foreach($recentResults as $r)
                <div class="flex items-center gap-3 rounded-lg border border-gray-100 p-3 dark:border-slate-700">
                    {{-- Result indicator --}}
                    <div class="flex-shrink-0">
                        @if($r->wasSuccessful())
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-green-100 text-green-600 dark:bg-green-900/30 dark:text-green-400">✓</span>
                        @else
                            <span class="flex h-8 w-8 items-center justify-center rounded-full bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400">✗</span>
                        @endif
                    </div>
                    {{-- Content --}}
                    <div class="min-w-0 flex-1">
                        <a href="{{ route('admin.governance.decisions.show', $r) }}"
                           class="text-sm font-medium text-gray-900 hover:text-indigo-600 dark:text-white dark:hover:text-indigo-400">
                            {{ Str::limit($r->title, 60) }}
                        </a>
                        @if($r->action_result['result_summary'] ?? null)
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ Str::limit($r->action_result['result_summary'], 80) }}</p>
                        @endif
                    </div>
                    {{-- Impact --}}
                    @if($r->impact_score !== null)
                        <span class="flex-shrink-0 rounded-full px-2 py-0.5 text-xs font-bold
                            {{ $r->impact_score > 0 ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                               ($r->impact_score < 0 ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                               'bg-gray-100 text-gray-600 dark:bg-slate-700 dark:text-gray-400') }}">
                            {{ $r->impact_score > 0 ? '+' : '' }}{{ $r->impact_score }}
                        </span>
                    @endif
                    {{-- Timestamp --}}
                    <span class="flex-shrink-0 text-[10px] text-gray-400 dark:text-gray-500">
                        {{ $r->action_completed_at?->diffForHumans() }}
                    </span>
                </div>
            @endforeach
        </div>
    </div>
    @endif
</div>
@endsection
