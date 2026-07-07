{{-- =========================================================================
     Property Digital Twin Cockpit
     Sprint 4.6 — SAAB v7 APPROVED

     Primary Route: GET /admin/workspace/{id}
     Required Panels:
       1. Workspace Overview
       2. Lifecycle State
       3. AI Completion
       4. Workspace Health
       5. Hermes Timeline
       6. Drive Status
       7. CRM Status
       8. Documents
       9. Publishing
       10. Finance
       11. Reservations
       12. Next Recommended Action
======================================================================== --}}

@extends('layouts.admin')

@section('title', $workspace['workspace']['portfolio_no']
    ? "{$workspace['workspace']['portfolio_no']} — Digital Twin Cockpit"
    : 'Digital Twin Cockpit')

@php
    $ws    = $workspace['workspace'];
    $ilan  = $workspace['ilan'];
    $health = $workspace['health'];
    $lifecycle = $workspace['lifecycle'];
    $ai    = $workspace['ai'];
    $drive = $workspace['drive'];

    // Lifecycle steps (8 states, 0=DRAFT through 8=ARCHIVED)
    $stepLabels = [
        'Taslak',
        'Drive Hazır',
        'Medya Hazır',
        'Açıklama Hazır',
        'Kalite Kontrol',
        'Yayına Hazır',
        'Yayında',
        'Aktif',
        'Arşivlendi',
    ];
    $stepColors = [
        'bg-slate-200 dark:bg-slate-700',
        'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
        'bg-cyan-100 dark:bg-cyan-900 text-cyan-700 dark:text-cyan-300',
        'bg-indigo-100 dark:bg-indigo-900 text-indigo-700 dark:text-indigo-300',
        'bg-violet-100 dark:bg-violet-900 text-violet-700 dark:text-violet-300',
        'bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300',
        'bg-green-100 dark:bg-green-900 text-green-700 dark:text-green-300',
        'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300',
        'bg-slate-500 dark:bg-slate-600 text-white',
    ];
    $currentStep = $ws['lifecycle_state']
        ? \App\Domain\Workspace\Enums\WorkspaceState::tryFrom($ws['lifecycle_state'])?->step() ?? 0
        : 0;

    // Health colors
    $healthColor = match($health['color']) {
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'green'   => 'text-green-600 dark:text-green-400',
        'amber'   => 'text-amber-600 dark:text-amber-400',
        'orange'  => 'text-orange-600 dark:text-orange-400',
        'red'     => 'text-red-600 dark:text-red-400',
        default   => 'text-slate-500',
    };

    $healthBg = match($health['color']) {
        'emerald' => 'bg-emerald-50 dark:bg-emerald-950 border-emerald-200 dark:border-emerald-800',
        'green'   => 'bg-green-50 dark:bg-green-950 border-green-200 dark:border-green-800',
        'amber'   => 'bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800',
        'orange'  => 'bg-orange-50 dark:bg-orange-950 border-orange-200 dark:border-orange-800',
        'red'     => 'bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800',
        default   => 'bg-slate-50 dark:bg-slate-900 border-slate-200 dark:border-slate-700',
    };

    // Next action
    $nextAction = $health['next_action'] ?? [];
@endphp

@push('styles')
<style>
    /* Sprint 4.6 Cockpit — Premium Dark Glass Panel */
    .cockpit-panel {
        @apply bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700
               rounded-2xl shadow-sm hover:shadow-md transition-shadow duration-200;
    }
    .cockpit-panel-header {
        @apply flex items-center gap-3 px-5 py-3.5 border-b border-slate-100 dark:border-slate-700/50;
    }
    .cockpit-stat-value {
        @apply text-2xl font-black tracking-tight text-slate-900 dark:text-white;
    }
    .cockpit-stat-label {
        @apply text-xs font-bold uppercase tracking-widest text-slate-400 dark:text-slate-500;
    }
    .step-node {
        @apply flex flex-col items-center gap-1.5;
    }
    .step-dot {
        @apply w-3.5 h-3.5 rounded-full border-2 border-white dark:border-slate-900 shadow-sm transition-all;
    }
    .timeline-line {
        @apply w-0.5 h-8 bg-gradient-to-b from-current to-transparent opacity-30;
    }
    .timeline-item {
        @apply flex items-start gap-4 py-3 border-b border-slate-50 dark:border-slate-800/50 last:border-0;
    }
    .timeline-badge {
        @apply shrink-0 px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wide;
    }
    .health-bar-track {
        @apply h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden;
    }
    .health-bar-fill {
        @apply h-full rounded-full transition-all duration-700 ease-out;
    }
    .dimension-row {
        @apply flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-800/50 last:border-0;
    }
    .action-card {
        @apply flex items-start gap-4 p-4 rounded-xl border transition-all duration-150;
    }
    .action-card-ready {
        @apply bg-emerald-50 dark:bg-emerald-950 border-emerald-200 dark:border-emerald-800;
    }
    .action-card-warning {
        @apply bg-amber-50 dark:bg-amber-950 border-amber-200 dark:border-amber-800;
    }
    .action-card-critical {
        @apply bg-red-50 dark:bg-red-950 border-red-200 dark:border-red-800;
    }
    .agent-badge {
        @apply flex items-center gap-2 px-3 py-1.5 rounded-lg text-xs font-bold;
    }
    .agent-done {
        @apply bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300;
    }
    .agent-pending {
        @apply bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400;
    }
    .drive-folder-chip {
        @apply inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold border;
    }
    .drive-folder-done {
        @apply bg-emerald-50 dark:bg-emerald-950 border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300;
    }
    .drive-folder-empty {
        @apply bg-slate-50 dark:bg-slate-800 border-slate-200 dark:border-slate-700 text-slate-400 dark:text-slate-500;
    }
    .crm-badge {
        @apply inline-flex items-center gap-1.5 px-2 py-0.5 rounded-full text-xs font-bold;
    }
    .crm-ok {
        @apply bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300;
    }
    .crm-missing {
        @apply bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300;
    }
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════════════════════════════
     TOPBAR OVERRIDE — Full-width operational header
══════════════════════════════════════════════════════════════════════ --}}
<div class="mx-auto max-w-screen-2xl px-4 sm:px-6 lg:px-8 py-4 space-y-4">

    {{-- ─── Operational Header ─── --}}
    <div class="flex items-center justify-between gap-4">
        {{-- Breadcrumb + Title --}}
        <div class="flex items-center gap-4">
            <a href="{{ route('admin.ilanlar.index') }}"
               class="shrink-0 w-9 h-9 flex items-center justify-center rounded-xl bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <x-icon name="ok" class="w-4 h-4" />
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <span class="text-xs font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                        Digital Twin Cockpit
                    </span>
                    @if($ws['workspace_status'] === 'error')
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400">
                            Drive Hatası
                        </span>
                    @endif
                    @if($lifecycle['is_live'])
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400">
                            Canlı
                        </span>
                    @endif
                </div>
                <h1 class="text-xl font-black text-slate-900 dark:text-white leading-tight">
                    {{ $ws['root_folder_name'] ?? '—' }}
                    <span class="text-slate-400 font-mono text-base ml-1">{{ $ws['portfolio_no'] ?? '' }}</span>
                </h1>
            </div>
        </div>

        {{-- Ilan Quick Access --}}
        @if($ilan)
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.ilanlar.show', $ilan['id']) }}"
                   class="flex items-center gap-2 px-4 py-2 rounded-xl bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 text-sm font-bold transition-colors">
                    <x-icon name="ev" class="w-4 h-4" />
                    İlan #{{ $ilan['id'] }}
                </a>
            </div>
        @endif
    </div>

    {{-- ─── Health Score Banner ─── --}}
    <div class="cockpit-panel {{ $healthBg }} border">
        <div class="px-5 py-4 flex items-center justify-between gap-6">
            <div class="flex items-center gap-5">
                {{-- Score Circle --}}
                <div class="relative shrink-0">
                    <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                        <circle cx="40" cy="40" r="34" fill="none" stroke="currentColor"
                                stroke-width="6" class="text-slate-200 dark:text-slate-700" />
                        <circle cx="40" cy="40" r="34" fill="none" stroke="currentColor"
                                stroke-width="6" stroke-linecap="round"
                                stroke-dasharray="{{ 2 * pi() * 34 }}"
                                stroke-dashoffset="{{ 2 * pi() * 34 * (1 - $health['score'] / 100) }}"
                                class="text-{{ $health['color'] }}-500" />
                    </svg>
                    <div class="absolute inset-0 flex items-center justify-center">
                        <span class="text-xl font-black text-slate-900 dark:text-white">{{ $health['score'] }}</span>
                    </div>
                </div>

                <div>
                    <div class="text-xs font-black uppercase tracking-widest text-slate-500 dark:text-slate-400 mb-0.5">
                        Workspace Health
                    </div>
                    <div class="{{ $healthColor }} text-2xl font-black">{{ $health['label'] }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Hesaplanma: {{ \Carbon\Carbon::parse($health['calculated_at'])->diffForHumans() }}
                    </div>
                </div>
            </div>

            {{-- Dimension Pills --}}
            <div class="hidden xl:flex items-center gap-3 flex-1">
                @foreach($health['dimensions'] as $dim)
                    @php $dimColor = $dim['score'] >= 70 ? 'emerald' : ($dim['score'] >= 40 ? 'amber' : 'red'); @endphp
                    <div class="text-center">
                        <div class="text-base font-black text-slate-900 dark:text-white">{{ $dim['score'] }}</div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">{{ $dim['label'] }}</div>
                        <div class="health-bar-track mt-1 w-16">
                            <div class="health-bar-fill bg-{{ $dimColor }}-500" style="width: {{ $dim['score'] }}%"></div>
                        </div>
                    </div>
                @endforeach

                {{-- Execution Stats Pills --}}
                @if(!empty($workspace['executions']))
                    <div class="border-l border-slate-200 dark:border-slate-700 pl-3 ml-1 flex items-center gap-4">
                        @if($workspace['executions']['running_count'] > 0)
                            <div class="text-center">
                                <div class="text-base font-black text-blue-600 dark:text-blue-400">{{ $workspace['executions']['running_count'] }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Çalışıyor</div>
                            </div>
                        @endif
                        @if($workspace['executions']['queued_count'] > 0)
                            <div class="text-center">
                                <div class="text-base font-black text-slate-500">{{ $workspace['executions']['queued_count'] }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Sırada</div>
                            </div>
                        @endif
                        @if($workspace['executions']['failed_count'] > 0)
                            <div class="text-center">
                                <div class="text-base font-black text-red-600 dark:text-red-400">{{ $workspace['executions']['failed_count'] }}</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Başarısız</div>
                            </div>
                        @endif
                        @if(isset($workspace['executions']['success_rate']) && $workspace['executions']['total_count'] > 0)
                            <div class="text-center">
                                @php $execColor = $workspace['executions']['success_rate'] >= 80 ? 'emerald' : ($workspace['executions']['success_rate'] >= 50 ? 'amber' : 'red'); @endphp
                                <div class="text-base font-black text-{{ $execColor }}-600 dark:text-{{ $execColor }}-400">{{ $workspace['executions']['success_rate'] }}%</div>
                                <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500">Başarı</div>
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            {{-- Next Action Button --}}
            <div class="shrink-0">
                @if(!empty($nextAction))
                    @php
                        $actionClass = $nextAction['priority'] <= 2 ? 'action-card-critical'
                            : ($nextAction['priority'] <= 4 ? 'action-card-warning' : 'action-card-ready');
                    @endphp
                    <a href="{{ $nextAction['route'] ? route($nextAction['route'], $nextAction['route_params']) : '#' }}"
                       class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl font-black text-sm
                              {{ $nextAction['priority'] <= 2
                                  ? 'bg-red-600 hover:bg-red-700 text-white shadow-sm'
                                  : ($nextAction['priority'] <= 4
                                      ? 'bg-amber-500 hover:bg-amber-600 text-white shadow-sm'
                                      : 'bg-emerald-600 hover:bg-emerald-700 text-white shadow-sm') }}">
                        <x-icon name="{{ $nextAction['icon'] ?? 'lightning' }}" class="w-4 h-4" />
                        {{ $nextAction['label'] }}
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 1: Overview | Lifecycle | AI Completion | Next Action
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-12 gap-4">

        {{-- Panel 1: Workspace Overview ─────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="cockpit-panel h-full">
                <div class="cockpit-panel-header">
                    <x-icon name="pano" class="w-5 h-5 text-orange-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Çalışma Alanı</span>
                </div>
                <div class="p-5 space-y-4">
                    <div>
                        <div class="cockpit-stat-label mb-0.5">Portföy No</div>
                        <div class="cockpit-stat-value font-mono text-orange-600 dark:text-orange-400">
                            {{ $ws['portfolio_no'] ?? '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="cockpit-stat-label">Drive Durumu</div>
                        <div class="flex items-center gap-2 mt-1">
                            @if($ws['workspace_status'] === 'ready')
                                <span class="crm-badge crm-ok">
                                    <x-icon name="check" class="w-3 h-3" /> Hazır
                                </span>
                            @elseif($ws['workspace_status'] === 'creating')
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300">
                                    Oluşturuluyor…
                                </span>
                            @else
                                <span class="crm-badge crm-missing">
                                    <x-icon name="x" class="w-3 h-3" /> Hata
                                </span>
                            @endif
                        </div>
                    </div>
                    <div>
                        <div class="cockpit-stat-label">Alan ID</div>
                        <div class="text-sm font-mono text-slate-600 dark:text-slate-400 break-all mt-0.5">
                            {{ $ws['drive_folder_id'] ?? '—' }}
                        </div>
                    </div>
                    @if($ws['drive_folder_url'])
                        <a href="{{ $ws['drive_folder_url'] }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                            <x-icon name="link" class="w-3 h-3" /> Google Drive'da Aç
                        </a>
                    @endif
                    <div>
                        <div class="cockpit-stat-label">Oluşturulma</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                            {{ $ws['workspace_created_at'] ? \Carbon\Carbon::parse($ws['workspace_created_at'])->format('d.m.Y H:i') : '—' }}
                        </div>
                    </div>
                    <div>
                        <div class="cockpit-stat-label">Son Güncelleme</div>
                        <div class="text-sm text-slate-600 dark:text-slate-400 mt-0.5">
                            {{ $ws['updated_at'] ? \Carbon\Carbon::parse($ws['updated_at'])->diffForHumans() : '—' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel 2: Lifecycle State ─────────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="cockpit-panel h-full">
                <div class="cockpit-panel-header">
                    <x-icon name="zaman" class="w-5 h-5 text-violet-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Yaşam Döngüsü</span>
                    <span class="ml-auto text-xs font-bold text-slate-400 dark:text-slate-500">
                        {{ $lifecycle['percent'] }}% tamamlandı
                    </span>
                </div>
                <div class="p-5">
                    {{-- Progress Bar --}}
                    <div class="health-bar-track mb-5">
                        <div class="health-bar-fill bg-gradient-to-r from-violet-500 to-emerald-500"
                             style="width: {{ $lifecycle['percent'] }}%"></div>
                    </div>

                    {{-- Step Nodes --}}
                    <div class="flex items-center overflow-x-auto gap-1 pb-1">
                        @foreach($stepLabels as $idx => $label)
                            @php
                                $isDone    = $idx < $currentStep;
                                $isCurrent = $idx === $currentStep;
                                $isFuture  = $idx > $currentStep;
                                $colorClass = $isDone ? 'bg-emerald-500' : ($isCurrent ? $stepColors[$idx] : 'bg-slate-200 dark:bg-slate-700');
                                if ($isCurrent) {
                                    $colorClass = match(true) {
                                        $currentStep >= 6 => 'bg-emerald-500',
                                        $currentStep >= 4 => 'bg-amber-500',
                                        $currentStep >= 2 => 'bg-blue-500',
                                        default => 'bg-violet-500',
                                    };
                                }
                            @endphp
                            <div class="step-node shrink-0">
                                <div class="step-dot {{ $isCurrent ? 'scale-150 ring-2 ring-offset-1 ring-'.$health['color'].'-400 dark:ring-offset-slate-900' : '' }} {{ $colorClass }}"
                                     title="{{ $label }}"></div>
                                <span class="text-[9px] font-bold text-center leading-tight
                                             {{ $isCurrent ? 'text-slate-900 dark:text-white' : 'text-slate-400 dark:text-slate-600' }}">
                                    {{ $label }}
                                </span>
                            </div>
                            @if($idx < 8)
                                <div class="shrink-0 w-3 h-0.5 mt-4 rounded
                                            {{ $isDone ? 'bg-emerald-400' : 'bg-slate-200 dark:bg-slate-700' }}"></div>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel 3: AI Completion ─────────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-2">
            <div class="cockpit-panel h-full">
                <div class="cockpit-panel-header">
                    <x-icon name="yapay-zeka" class="w-5 h-5 text-purple-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">AI</span>
                </div>
                <div class="p-5 space-y-3">
                    <div class="text-center">
                        <div class="text-4xl font-black text-slate-900 dark:text-white">{{ $ai['percent'] }}%</div>
                        <div class="text-xs text-slate-400 dark:text-slate-500 font-bold mt-0.5">AI İlerleme</div>
                    </div>
                    <div class="space-y-1.5">
                        @foreach($ai['agents'] as $agent)
                            <div class="agent-badge {{ $agent['complete'] ? 'agent-done' : 'agent-pending' }}">
                                <x-icon name="{{ $agent['complete'] ? 'check' : 'bos' }}" class="w-3 h-3" />
                                {{ $agent['name'] }}
                            </div>
                        @endforeach
                    </div>
                    @if($ai['all_done'])
                        <div class="text-center text-xs font-black text-emerald-600 dark:text-emerald-400 mt-2">
                            ✓ Tüm Ajanlar Tamamlandı
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel 4: Next Recommended Action ────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="cockpit-panel h-full">
                <div class="cockpit-panel-header">
                    <x-icon name="isaret" class="w-5 h-5 text-amber-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Sonraki Adım</span>
                </div>
                <div class="p-5">
                    @if(!empty($nextAction))
                        <div class="space-y-3">
                            {{-- Priority Badge --}}
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase
                                    {{ $nextAction['priority'] <= 2 ? 'bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400'
                                      : ($nextAction['priority'] <= 4 ? 'bg-amber-100 dark:bg-amber-900 text-amber-600 dark:text-amber-400'
                                      : 'bg-emerald-100 dark:bg-emerald-900 text-emerald-600 dark:text-emerald-400') }}">
                                    {{ $nextAction['priority'] <= 2 ? 'Kritik' : ($nextAction['priority'] <= 4 ? 'Öncelikli' : 'Normal') }}
                                </span>
                                <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">#{{ $nextAction['priority'] }}</span>
                            </div>

                            {{-- Action Icon + Label --}}
                            <div class="flex items-start gap-3">
                                <div class="shrink-0 w-10 h-10 rounded-xl flex items-center justify-center
                                    {{ $nextAction['priority'] <= 2 ? 'bg-red-100 dark:bg-red-900'
                                      : ($nextAction['priority'] <= 4 ? 'bg-amber-100 dark:bg-amber-900'
                                      : 'bg-emerald-100 dark:bg-emerald-900') }}">
                                    <x-icon name="{{ $nextAction['icon'] ?? 'lightning' }}"
                                            class="w-5 h-5 {{ $nextAction['priority'] <= 2 ? 'text-red-600 dark:text-red-400'
                                              : ($nextAction['priority'] <= 4 ? 'text-amber-600 dark:text-amber-400'
                                              : 'text-emerald-600 dark:text-emerald-400') }}" />
                                </div>
                                <div>
                                    <div class="font-black text-slate-900 dark:text-white leading-tight">
                                        {{ $nextAction['label'] }}
                                    </div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400 mt-0.5 leading-relaxed">
                                        {{ $nextAction['reason'] }}
                                    </div>
                                </div>
                            </div>

                            {{-- Blocked Items --}}
                            @if(count($nextAction['blocked']) > 0)
                                <div class="space-y-1">
                                    <div class="text-[10px] font-black uppercase tracking-widest text-slate-400 dark:text-slate-500">
                                        Engelleyiciler
                                    </div>
                                    @foreach($nextAction['blocked'] as $blocked)
                                        <div class="flex items-center gap-1.5 text-xs text-red-600 dark:text-red-400">
                                            <x-icon name="bos" class="w-3 h-3 shrink-0" />
                                            {{ $blocked }}
                                        </div>
                                    @endforeach
                                </div>
                            @endif

                            {{-- Action Button --}}
                            @if($nextAction['route'])
                                <a href="{{ route($nextAction['route'], $nextAction['route_params']) }}"
                                   class="w-full flex items-center justify-center gap-2 py-2.5 rounded-xl font-black text-sm
                                          bg-slate-900 dark:bg-white text-white dark:text-slate-900 hover:opacity-90 transition-opacity">
                                    <x-icon name="ok" class="w-4 h-4" />
                                    Harekete Geç
                                </a>
                            @endif
                        </div>
                    @else
                        <div class="text-center py-6 text-slate-400 dark:text-slate-500">
                            <x-icon name="check" class="w-8 h-8 mx-auto mb-2 opacity-30" />
                            <p class="text-sm font-bold">Tüm kontroller tamam</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 2: Ilan Info | Documents | CRM | Publishing | Drive | Finance
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-12 gap-4">

        {{-- Panel 5: İlan Özeti ─────────────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="cockpit-panel">
                <div class="cockpit-panel-header">
                    <x-icon name="ev" class="w-5 h-5 text-orange-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">İlan</span>
                    @if($ilan)
                        <a href="{{ route('admin.ilanlar.show', $ilan['id']) }}"
                           class="ml-auto text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                            Detay →
                        </a>
                    @endif
                </div>
                <div class="p-5 space-y-3">
                    @if($ilan)
                        <div>
                            <div class="cockpit-stat-label">Başlık</div>
                            <div class="text-sm font-bold text-slate-900 dark:text-white leading-tight mt-0.5 line-clamp-2">
                                {{ $ilan['baslik'] ?? '—' }}
                            </div>
                        </div>
                        <div class="flex items-center justify-between">
                            <div>
                                <div class="cockpit-stat-label">Fiyat</div>
                                <div class="text-base font-black text-slate-900 dark:text-white">
                                    {{ $ilan['fiyat'] ? number_format($ilan['fiyat'], 0, ',', '.') . ' ' . ($ilan['para_birimi'] ?? 'TL') : '—' }}
                                </div>
                            </div>
                            <div class="text-right">
                                <div class="cockpit-stat-label">Yayın</div>
                                <span class="px-2 py-0.5 rounded-full text-xs font-black
                                    {{ $ilan['yayin_durumu'] === 'yayinda' ? 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300'
                                      : 'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400' }}">
                                    {{ $ilan['yayin_durumu_label'] ?? $ilan['yayin_durumu'] ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div>
                                <div class="cockpit-stat-label">Konum</div>
                                <div class="text-xs text-slate-600 dark:text-slate-400 mt-0.5">
                                    {{ $ilan['il_adi'] ?? '—' }}
                                    {{ $ilan['ilce_adi'] ? ', ' . $ilan['ilce_adi'] : '' }}
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center gap-4">
                            <div>
                                <div class="cockpit-stat-label">Fotoğraf</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                    {{ $ilan['photo_count'] }} adet
                                </div>
                            </div>
                            @if($ilan['has_video'])
                                <div>
                                    <div class="crm-badge crm-ok">
                                        <x-icon name="video" class="w-3 h-3" /> Video Var
                                    </div>
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center justify-between pt-1 border-t border-slate-100 dark:border-slate-700/50">
                            <div>
                                <div class="cockpit-stat-label">Görüntülenme</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                    {{ number_format($ilan['view_count'] ?? 0) }}
                                </div>
                            </div>
                            <div>
                                <div class="cockpit-stat-label">Danışman</div>
                                <div class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                    {{ $ilan['danisman'] ?? '—' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                            İlan bilgisi yok
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel 6: Drive Integration — Sprint 4.8 ──────────────────────── --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="cockpit-panel">
                <div class="cockpit-panel-header">
                    <x-icon name="klasor" class="w-5 h-5 text-blue-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Drive</span>
                    <span class="ml-auto text-xs font-bold text-slate-400 dark:text-slate-500">
                        {{ $drive['subfolder_count'] }}/12
                    </span>
                </div>
                <div class="p-4 space-y-4">

                    {{-- Webhook Status Row ─────────────────────────────────── --}}
                    @php $wh = $drive['webhook'] ?? []; @endphp
                    <div class="grid grid-cols-2 gap-2 text-xs">
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full {{ ($wh['connected'] ?? false) ? 'bg-emerald-400 animate-pulse' : 'bg-red-400' }}"></span>
                            <span class="text-slate-500 dark:text-slate-400">Webhook</span>
                            <span class="ml-auto font-bold {{ ($wh['connected'] ?? false) ? 'text-emerald-600' : 'text-red-500' }}">
                                {{ ($wh['connected'] ?? false) ? 'Aktif' : 'Kapalı' }}
                            </span>
                        </div>
                        <div class="flex items-center gap-1.5">
                            <span class="w-2 h-2 rounded-full {{ ($wh['last_error'] ?? null) ? 'bg-amber-400' : 'bg-blue-400' }}"></span>
                            <span class="text-slate-500 dark:text-slate-400">Sync</span>
                            <span class="ml-auto font-bold {{ ($wh['last_error'] ?? null) ? 'text-amber-600' : 'text-blue-600' }}">
                                {{ ($wh['last_count'] ?? 0) > 0 ? ($wh['last_count'].' dosya') : '—' }}
                            </span>
                        </div>
                    </div>

                    {{-- Last Sync ─────────────────────────────────────────── --}}
                    @if(($wh['last_sync_at'] ?? null))
                        <div class="text-xs text-slate-400 dark:text-slate-500">
                            Son sync: {{ \Carbon\Carbon::parse($wh['last_sync_at'])->diffForHumans() }}
                        </div>
                    @endif

                    {{-- Channel Expiration ────────────────────────────────── --}}
                    @if(($wh['expiration'] ?? null))
                        @php $expTs = $wh['expiration_ts']; $nowTs = time(); $ttlH = max(0, floor(($expTs - $nowTs) / 3600)); @endphp
                        <div class="flex items-center justify-between">
                            <span class="text-xs text-slate-500 dark:text-slate-400">Kanal TTL</span>
                            <span class="text-xs font-bold {{ $ttlH < 24 ? 'text-amber-500' : 'text-slate-600 dark:text-slate-300' }}">
                                {{ $ttlH }}s
                            </span>
                        </div>
                    @endif

                    {{-- Error ─────────────────────────────────────────────── --}}
                    @if(($wh['last_error'] ?? null))
                        <div class="text-xs text-red-500 bg-red-50 dark:bg-red-950/30 rounded p-1.5 truncate"
                             title="{{ $wh['last_error'] }}">
                            <x-icon name="warning" class="w-3 h-3 inline" />
                            {{ $wh['last_error'] }}
                        </div>
                    @endif

                    {{-- Tracked Files ─────────────────────────────────────── --}}
                    @if(($drive['files']['total'] ?? 0) > 0)
                        <div class="border-t border-slate-100 dark:border-slate-800/50 pt-3">
                            <div class="flex items-center justify-between mb-2">
                                <span class="text-xs font-bold text-slate-600 dark:text-slate-300">Dosyalar</span>
                                <span class="text-xs text-slate-400">
                                    {{ $drive['files']['docs'] ?? 0 }} doc ·
                                    {{ $drive['files']['sheets'] ?? 0 }} sheet
                                </span>
                            </div>
                            @foreach(array_slice($drive['files']['list'] ?? [], 0, 5) as $file)
                                <a href="{{ $file['web_view_link'] ?? '#' }}"
                                   target="_blank" rel="noopener"
                                   class="flex items-center gap-1.5 text-xs text-slate-500 hover:text-blue-600 truncate py-0.5">
                                    <x-icon name="yazi" class="w-3 h-3 shrink-0" />
                                    <span class="truncate">{{ $file['name'] ?? '?' }}</span>
                                </a>
                            @endforeach
                        </div>
                    @endif

                    {{-- Folder Links ─────────────────────────────────────── --}}
                    <div class="border-t border-slate-100 dark:border-slate-800/50 pt-3">
                        <div class="flex flex-wrap gap-1.5">
                            @forelse($drive['subfolders'] as $sf)
                                <a href="{{ $sf['url'] ?? '#' }}"
                                   target="_blank" rel="noopener"
                                   title="{{ $sf['name'] }}"
                                   class="drive-folder-chip {{ !empty($sf['id']) ? 'drive-folder-done' : 'drive-folder-empty' }}
                                          {{ empty($sf['id']) || empty($sf['url']) ? 'cursor-not-allowed' : 'hover:opacity-80' }}">
                                    <x-icon name="{{ !empty($sf['id']) ? 'klasor' : 'klasor-bos' }}" class="w-3 h-3" />
                                    <span class="hidden xl:inline">{{ $sf['name'] }}</span>
                                    <span class="xl:hidden">{{ \Illuminate\Support\Str::after($sf['name'], '_') }}</span>
                                </a>
                            @empty
                                <p class="text-xs text-slate-400 dark:text-slate-500">Henüz Drive klasörü yok</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel 7: CRM Status ────────────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-2">
            <div class="cockpit-panel">
                <div class="cockpit-panel-header">
                    <x-icon name="insan" class="w-5 h-5 text-teal-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">CRM</span>
                </div>
                <div class="p-5 space-y-3">
                    @if($ilan)
                        <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-800/50">
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">İlan Sahibi</span>
                            @if($ilan['ilan_sahibi'])
                                <span class="crm-badge crm-ok">
                                    <x-icon name="check" class="w-3 h-3" />
                                    {{ \Illuminate\Support\Str::limit($ilan['ilan_sahibi'], 15) }}
                                </span>
                            @else
                                <span class="crm-badge crm-missing">
                                    <x-icon name="x" class="w-3 h-3" /> Eksik
                                </span>
                            @endif
                        </div>
                        <div class="flex items-center justify-between py-2 border-b border-slate-50 dark:border-slate-800/50">
                            <span class="text-xs font-bold text-slate-600 dark:text-slate-400">Danışman</span>
                            @if($ilan['danisman'])
                                <span class="crm-badge crm-ok">
                                    <x-icon name="check" class="w-3 h-3" />
                                    {{ \Illuminate\Support\Str::limit($ilan['danisman'], 15) }}
                                </span>
                            @else
                                <span class="crm-badge crm-missing">
                                    <x-icon name="x" class="w-3 h-3" /> Yok
                                </span>
                            @endif
                        </div>
                        @php $crmScore = $health['dimensions']['crm']['score'] ?? 0; @endphp
                        <div>
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-xs font-bold text-slate-500 dark:text-slate-400">CRM Skoru</span>
                                <span class="text-sm font-black text-slate-700 dark:text-slate-300">{{ $crmScore }}/100</span>
                            </div>
                            <div class="health-bar-track">
                                <div class="health-bar-fill bg-teal-500" style="width: {{ $crmScore }}%"></div>
                            </div>
                        </div>
                    @else
                        <p class="text-xs text-slate-400 dark:text-slate-500">CRM bağlantısı yok (ilan silinmiş olabilir)</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel 8: Publishing Status ──────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-2">
            <div class="cockpit-panel">
                <div class="cockpit-panel-header">
                    <x-icon name="yayin" class="w-5 h-5 text-green-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Yayın</span>
                </div>
                <div class="p-5 space-y-3">
                    @php $pubScore = $health['dimensions']['publishing']['score'] ?? 0; @endphp
                    <div>
                        <div class="flex items-center justify-between mb-1">
                            <span class="text-xs font-bold text-slate-500">Yayın Skoru</span>
                            <span class="text-sm font-black text-slate-700 dark:text-slate-300">{{ $pubScore }}/100</span>
                        </div>
                        <div class="health-bar-track">
                            <div class="health-bar-fill bg-green-500" style="width: {{ $pubScore }}%"></div>
                        </div>
                    </div>
                    <div class="space-y-1.5">
                        @php $pubDim = $health['dimensions']['publishing'] ?? []; @endphp
                        <div class="flex items-center gap-2 text-xs">
                            @if($pubDim['is_live'] ?? false)
                                <span class="crm-badge crm-ok">
                                    <x-icon name="check" class="w-3 h-3" /> Canlı Yayında
                                </span>
                            @elseif($pubDim['is_ready'] ?? false)
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300">
                                    Yayına Hazır
                                </span>
                            @else
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-500">
                                    {{ $pubDim['lifecycle_label'] ?? '—' }}
                                </span>
                            @endif
                        </div>
                        @if(count($pubDim['missing_fields'] ?? []) > 0)
                            <div class="space-y-0.5">
                                @foreach($pubDim['missing_fields'] as $field)
                                    <div class="text-xs text-red-600 dark:text-red-400 flex items-center gap-1">
                                        <x-icon name="bos" class="w-3 h-3" /> {{ $field }}
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel 9: Health Dimensions Detail ───────────────────────────── --}}
        <div class="col-span-12 lg:col-span-2">
            <div class="cockpit-panel">
                <div class="cockpit-panel-header">
                    <x-icon name="kalp" class="w-5 h-5 text-red-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Sağlık Detay</span>
                </div>
                <div class="p-4 space-y-1">
                    @foreach($health['dimensions'] as $dim)
                        @php
                            $dColor = $dim['score'] >= 70 ? 'emerald' : ($dim['score'] >= 40 ? 'amber' : 'red');
                            $dBg    = $dim['score'] >= 70 ? 'bg-emerald-50 dark:bg-emerald-950'
                                : ($dim['score'] >= 40 ? 'bg-amber-50 dark:bg-amber-950' : 'bg-red-50 dark:bg-red-950');
                        @endphp
                        <div class="flex items-center gap-3 px-3 py-2 rounded-xl {{ $dBg }}">
                            <div class="text-sm font-black text-slate-700 dark:text-slate-300 w-16 shrink-0">
                                {{ $dim['score'] }}
                            </div>
                            <div class="flex-1">
                                <div class="text-xs font-bold text-slate-600 dark:text-slate-400">{{ $dim['label'] }}</div>
                                <div class="health-bar-track mt-0.5">
                                    <div class="health-bar-fill bg-{{ $dColor }}-500" style="width: {{ $dim['score'] }}%"></div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 3: Documents | Finance | Reservations
    ═══════════════════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-12 gap-4">

        {{-- Panel 10: Drive Documents Detail ─────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-5">
            <div class="cockpit-panel">
                <div class="cockpit-panel-header">
                    <x-icon name="klasor" class="w-5 h-5 text-blue-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Dokümanlar</span>
                    @php $docScore = $health['dimensions']['documents']['score'] ?? 0; @endphp
                    <span class="ml-auto text-xs font-bold text-slate-400 dark:text-slate-500">
                        {{ $health['dimensions']['documents']['populated'] ?? 0 }}/12
                    </span>
                </div>
                <div class="p-4">
                    <div class="space-y-1.5">
                        @php
                            $folders = $drive['subfolders'] ?? [];
                            $folderMap = collect($folders)->keyBy('name')->toArray();
                            $docLabels = [
                                '01_Fotograflar'    => ['Fotoğraflar', 'kamera'],
                                '02_Videolar'       => ['Videolar', 'video'],
                                '03_Tapu'           => ['Tapu', 'yazi'],
                                '04_Imar'           => ['İmar', 'yazi'],
                                '05_Ekspertiz'      => ['Ekspertiz', 'yazi'],
                                '06_Airbnb'         => ['Airbnb', 'link'],
                                '07_Sahibinden'     => ['Sahibinden', 'link'],
                                '08_Hepsiemlak'     => ['HepsiEmlak', 'link'],
                                '09_CRM'            => ['CRM', 'insan'],
                                '10_Finans'         => ['Finans', 'para'],
                                '11_AI'             => ['AI Analiz', 'yapay-zeka'],
                                '12_Arsiv'          => ['Arşiv', 'klasor'],
                            ];
                        @endphp
                        @foreach($docLabels as $folderName => $info)
                            @php
                                $folder = $folderMap[$folderName] ?? null;
                                $hasIt  = !empty($folder['id']);
                                $url    = $folder['url'] ?? null;
                            @endphp
                            <div class="flex items-center gap-3 px-3 py-2 rounded-xl
                                {{ $hasIt ? 'bg-emerald-50 dark:bg-emerald-950 border border-emerald-100 dark:border-emerald-900'
                                          : 'bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700' }}">
                                <x-icon name="{{ $hasIt ? 'check' : 'bos' }}"
                                        class="w-4 h-4 shrink-0 {{ $hasIt ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400 dark:text-slate-600' }}" />
                                <x-icon name="{{ $info[1] }}"
                                        class="w-4 h-4 shrink-0 text-slate-400 dark:text-slate-600 ml-1" />
                                <span class="text-xs font-semibold text-slate-700 dark:text-slate-300 flex-1">
                                    {{ $info[0] }}
                                </span>
                                @if($hasIt && $url)
                                    <a href="{{ $url }}" target="_blank" rel="noopener"
                                       class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline shrink-0">
                                        Aç
                                    </a>
                                @else
                                    <span class="text-xs text-slate-400 dark:text-slate-600 shrink-0">Boş</span>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        {{-- Panel 11: Finance ─────────────────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-4">
            <div class="cockpit-panel h-full">
                <div class="cockpit-panel-header">
                    <x-icon name="para" class="w-5 h-5 text-green-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Finans</span>
                </div>
                <div class="p-5 space-y-4">
                    @if($workspace['finance'] && $workspace['finance']['listing_price'])
                        {{-- Listing Price --}}
                        <div>
                            <div class="cockpit-stat-label">Satılık Fiyat</div>
                            <div class="text-xl font-black text-slate-900 dark:text-white mt-0.5">
                                {{ $workspace['finance']['listing_formatted'] ?? '—' }}
                            </div>
                        </div>
                        <div class="border-t border-slate-100 dark:border-slate-700/50 pt-3 space-y-3">
                            @if($workspace['finance']['purchase_price'] > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Alım Fiyatı</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ $workspace['finance']['purchase_formatted'] }}
                                    </span>
                                </div>
                            @endif
                            @if($workspace['finance']['daily_rate'] > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Günlük Kiralama</span>
                                    <span class="text-sm font-bold text-slate-700 dark:text-slate-300">
                                        {{ $workspace['finance']['daily_formatted'] }}
                                    </span>
                                </div>
                            @endif
                            @if($workspace['finance']['roi_estimate'] > 0)
                                <div class="flex items-center justify-between">
                                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">Tahmini ROI</span>
                                    <span class="text-sm font-black
                                        {{ $workspace['finance']['roi_estimate'] >= ($workspace['finance']['roi_target'] ?? 0)
                                            ? 'text-emerald-600 dark:text-emerald-400'
                                            : 'text-amber-600 dark:text-amber-400' }}">
                                        ~%{{ $workspace['finance']['roi_estimate'] }}
                                        @if($workspace['finance']['roi_target'] > 0)
                                            <span class="text-xs font-normal text-slate-400">/ hedef %{{ $workspace['finance']['roi_target'] }}</span>
                                        @endif
                                    </span>
                                </div>
                            @endif
                        </div>
                        @if($workspace['finance']['roi_estimate'] > 0)
                            <div>
                                <div class="health-bar-track">
                                    @php
                                        $roiTarget = $workspace['finance']['roi_target'] ?: 10;
                                        $roiWidth = min(100, ($workspace['finance']['roi_estimate'] / $roiTarget) * 100);
                                    @endphp
                                    <div class="health-bar-fill
                                        {{ $workspace['finance']['roi_estimate'] >= $roiTarget ? 'bg-emerald-500' : 'bg-amber-500' }}"
                                         style="width: {{ $roiWidth }}%"></div>
                                </div>
                            </div>
                        @endif
                    @else
                        <div class="text-center py-6 text-slate-400 dark:text-slate-500 text-sm">
                            <x-icon name="para" class="w-8 h-8 mx-auto mb-2 opacity-20" />
                            <p>Yatırım verisi yok</p>
                            <p class="text-xs mt-1">Alım fiyatı veya günlük kiralama girilmemiş</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        {{-- Panel 12: Reservations ───────────────────────────────────────── --}}
        <div class="col-span-12 lg:col-span-3">
            <div class="cockpit-panel h-full">
                <div class="cockpit-panel-header">
                    <x-icon name="takvim" class="w-5 h-5 text-violet-500" />
                    <span class="text-sm font-black text-slate-700 dark:text-slate-300">Rezervasyonlar</span>
                    @if($workspace['reservations'])
                        <span class="ml-auto text-xs font-bold text-slate-400 dark:text-slate-500">
                            {{ $workspace['reservations']['active_count'] }} aktif
                        </span>
                    @endif
                </div>
                <div class="p-4">
                    @if($workspace['reservations'] && $workspace['reservations']['has_reservations'])
                        <div class="space-y-2">
                            @foreach($workspace['reservations']['recent'] as $res)
                                @php
                                    $durumColor = match($res['durum'] ?? 'bilinmiyor') {
                                        'onaylandi', 'approved' => 'emerald',
                                        'iptal', 'cancelled' => 'red',
                                        'beklemede', 'pending' => 'amber',
                                        default => 'slate',
                                    };
                                @endphp
                                <div class="flex items-start gap-3 p-2.5 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                                    <div class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700">
                                        <x-icon name="kullanici" class="w-4 h-4 text-slate-400 dark:text-slate-500" />
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                            {{ $res['guest_name'] ?? 'Misafir #' . $res['id'] }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-mono">
                                            {{ $res['baslangic'] ?? '—' }}
                                            → {{ $res['bitis'] ?? '—' }}
                                        </div>
                                    </div>
                                    <span class="shrink-0 px-1.5 py-0.5 rounded-full text-[10px] font-black uppercase
                                        bg-{{ $durumColor }}-100 dark:bg-{{ $durumColor }}-900 text-{{ $durumColor }}-700 dark:text-{{ $durumColor }}-300">
                                        {{ $res['durum'] ?? '—' }}
                                    </span>
                                </div>
                            @endforeach
                        </div>
                        <div class="mt-3 pt-2 border-t border-slate-100 dark:border-slate-700/50 text-center">
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                Toplam {{ $workspace['reservations']['total_count'] }} rezervasyon
                            </span>
                        </div>
                    @else
                        <div class="text-center py-6 text-slate-400 dark:text-slate-500">
                            <x-icon name="takvim" class="w-8 h-8 mx-auto mb-2 opacity-20" />
                            <p class="text-xs">Rezervasyon yok</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════════════════
         ROW 4: Execution Monitor (full width) + Hermes Timeline
    ═══════════════════════════════════════════════════════════════════════ --}}
    @php
        $execSummary = $workspace['executions'] ?? null;
    @endphp

    <div class="cockpit-panel mb-4">
        <div class="cockpit-panel-header">
            <x-icon name="cog" class="w-5 h-5 text-violet-500" />
            <span class="text-sm font-black text-slate-700 dark:text-slate-300">Execution Monitor</span>
            @if($execSummary && $execSummary['has_active'])
                <span class="ml-2 px-2 py-0.5 rounded-full text-[10px] font-black uppercase bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-400">
                    AKTİF
                </span>
            @endif
            <div class="ml-auto flex items-center gap-4">
                @if($execSummary)
                    <div class="flex items-center gap-3 text-xs">
                        @if($execSummary['running_count'] > 0)
                            <span class="flex items-center gap-1 font-bold text-blue-600 dark:text-blue-400">
                                <span class="w-2 h-2 rounded-full bg-blue-500 animate-pulse"></span>
                                {{ $execSummary['running_count'] }} çalışıyor
                            </span>
                        @endif
                        @if($execSummary['queued_count'] > 0)
                            <span class="flex items-center gap-1 font-bold text-slate-500">
                                <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                                {{ $execSummary['queued_count'] }} sırada
                            </span>
                        @endif
                        @if($execSummary['failed_count'] > 0)
                            <span class="flex items-center gap-1 font-bold text-red-600 dark:text-red-400">
                                <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                {{ $execSummary['failed_count'] }} başarısız
                            </span>
                        @endif
                        @if($execSummary['success_rate'] > 0)
                            <span class="font-bold text-slate-600 dark:text-slate-400">
                                %{{ $execSummary['success_rate'] }} başarı
                            </span>
                        @endif
                    </div>
                @endif
                <a href="{{ route('admin.workspace.executions.index', $ws['id']) }}"
                   class="text-xs font-bold text-blue-600 dark:text-blue-400 hover:underline">
                    Tümü →
                </a>
            </div>
        </div>
        <div class="p-4">
            @if($execSummary && $execSummary['total_count'] > 0)
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">
                    {{-- Failed executions for replay --}}
                    @if(count($execSummary['failed_for_replay'] ?? []) > 0)
                        <div class="space-y-2">
                            <div class="text-xs font-black uppercase tracking-widest text-red-500 mb-2">
                                Başarısız — Yeniden Çalıştırılabilir
                            </div>
                            @foreach($execSummary['failed_for_replay'] as $failed)
                                <div class="flex items-center gap-3 p-3 rounded-xl bg-red-50 dark:bg-red-950 border border-red-100 dark:border-red-900">
                                    <x-icon name="warning" class="w-4 h-4 text-red-500 shrink-0" />
                                    <div class="flex-1 min-w-0">
                                        <div class="text-xs font-bold text-slate-700 dark:text-slate-300 truncate">
                                            {{ $failed['label'] }}
                                        </div>
                                        <div class="text-[10px] text-slate-400 dark:text-slate-500 truncate">
                                            {{ $failed['reason'] ?? 'Bilinmeyen hata' }}
                                        </div>
                                    </div>
                                    <button onclick="replayExecution({{ $failed['id'] }})"
                                            class="shrink-0 px-2 py-1 rounded-lg text-xs font-bold bg-red-100 dark:bg-red-900 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-800 transition-colors">
                                        ▶ Yeniden
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="text-xs text-slate-400 dark:text-slate-500">
                            Başarısız execution yok
                        </div>
                    @endif

                    {{-- Last execution --}}
                    @if($execSummary['last_execution'])
                        @php $last = $execSummary['last_execution']; @endphp
                        <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-700/50">
                            <div class="text-xs font-black uppercase tracking-widest text-slate-400 dark:text-slate-500 mb-2">
                                Son Execution
                            </div>
                            <div class="text-sm font-bold text-slate-700 dark:text-slate-300 mb-1">
                                {{ $last['label'] }}
                            </div>
                            <div class="flex items-center gap-2 mb-1">
                                @php $lc = $last['state_color'] ?? 'slate'; @endphp
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase
                                    bg-{{ $lc }}-100 dark:bg-{{ $lc }}-900 text-{{ $lc }}-600 dark:text-{{ $lc }}-400">
                                    {{ $last['state_label'] }}
                                </span>
                                @if($last['duration'])
                                    <span class="text-xs text-slate-400 dark:text-slate-500 font-mono">
                                        {{ $last['duration'] }}
                                    </span>
                                @endif
                            </div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500">
                                {{ $last['created_at'] ? \Carbon\Carbon::parse($last['created_at'])->diffForHumans() : '—' }}
                            </div>
                        </div>
                    @endif

                    {{-- Quick stats --}}
                    <div class="grid grid-cols-3 gap-2">
                        <div class="text-center p-2 rounded-xl bg-emerald-50 dark:bg-emerald-950 border border-emerald-100 dark:border-emerald-900">
                            <div class="text-xl font-black text-emerald-600 dark:text-emerald-400">{{ $execSummary['succeeded_count'] ?? 0 }}</div>
                            <div class="text-[10px] font-bold text-emerald-500 uppercase tracking-wide">Başarılı</div>
                        </div>
                        <div class="text-center p-2 rounded-xl bg-slate-50 dark:bg-slate-800 border border-slate-100 dark:border-slate-700">
                            <div class="text-xl font-black text-slate-500">{{ $execSummary['active_count'] ?? 0 }}</div>
                            <div class="text-[10px] font-bold text-slate-400 uppercase tracking-wide">Aktif</div>
                        </div>
                        <div class="text-center p-2 rounded-xl bg-red-50 dark:bg-red-950 border border-red-100 dark:border-red-900">
                            <div class="text-xl font-black text-red-500">{{ $execSummary['failed_count'] ?? 0 }}</div>
                            <div class="text-[10px] font-bold text-red-400 uppercase tracking-wide">Başarısız</div>
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-6 text-slate-400 dark:text-slate-500">
                    <x-icon name="cog" class="w-8 h-8 mx-auto mb-2 opacity-20" />
                    <p class="text-sm">Henüz execution yok</p>
                </div>
            @endif
        </div>
    </div>

    {{-- Hermes Timeline (full width) --}}
    <div class="cockpit-panel">
        <div class="cockpit-panel-header">
            <x-icon name="zaman" class="w-5 h-5 text-indigo-500" />
            <span class="text-sm font-black text-slate-700 dark:text-slate-300">Hermes Zaman Çizelgesi</span>
            <span class="ml-auto text-xs font-bold text-slate-400 dark:text-slate-500">
                Tüm olaylar kronolojik sırayla
            </span>
            <button onclick="refreshTimeline()"
                    class="ml-3 flex items-center gap-1.5 px-3 py-1 rounded-lg text-xs font-bold
                           bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400
                           hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                <x-icon name="yenile" class="w-3.5 h-3.5" />
                Yenile
            </button>
        </div>
        <div class="p-5">
            <div id="timeline-container" class="space-y-0">
                <div class="text-center py-8 text-slate-400 dark:text-slate-500">
                    <x-icon name="zaman" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                    <p class="text-sm">Zaman çizelgesi yükleniyor…</p>
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
    // Sprint 4.6 — Timeline Loader
    function loadTimeline() {
        const container = document.getElementById('timeline-container');
        if (!container) return;

        const workspaceId = {{ $ws['id'] }};

        fetch(`/admin/workspace/${workspaceId}/events?limit=30`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            if (!data.events || data.events.length === 0) {
                container.innerHTML = `
                    <div class="text-center py-8 text-slate-400 dark:text-slate-500">
                        <x-icon name="zaman" class="w-10 h-10 mx-auto mb-2 opacity-20" />
                        <p class="text-sm">Henüz olay kaydedilmedi</p>
                    </div>`;
                return;
            }

            const colorMap = {
                emerald: 'bg-emerald-100 dark:bg-emerald-900 text-emerald-700 dark:text-emerald-300',
                red:     'bg-red-100 dark:bg-red-900 text-red-700 dark:text-red-300',
                blue:    'bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300',
                amber:   'bg-amber-100 dark:bg-amber-900 text-amber-700 dark:text-amber-300',
                slate:   'bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400',
            };

            const iconMap = {
                camera: 'kamera', folder: 'klasor', text: 'yazi', chart: 'grafik',
                publish: 'yayin', bell:  'zil', briefcase: 'canta', bolt: 'zaman',
            };

            let html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">';

            data.events.forEach(event => {
                const color = colorMap[event.color] || colorMap.slate;
                const time  = event.occurred_at || event.completed_at || event.started_at;
                const timeStr = time ? new Date(time).toLocaleString('tr-TR', {
                    day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit'
                }) : '—';
                const icon  = iconMap[event.icon] || 'zaman';

                html += `
                    <div class="flex items-start gap-3 p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50
                                border border-slate-100 dark:border-slate-700/50 hover:border-slate-200 dark:hover:border-slate-600 transition-colors">
                        <div class="shrink-0 w-8 h-8 rounded-lg flex items-center justify-center bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 shadow-sm">
                            <x-icon name="${icon}" class="w-4 h-4 text-slate-500 dark:text-slate-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-xs font-black text-slate-700 dark:text-slate-300 leading-tight">
                                ${event.label}
                            </div>
                            <div class="text-[10px] text-slate-400 dark:text-slate-500 mt-0.5 font-mono">${timeStr}</div>
                            <div class="mt-1.5">
                                <span class="timeline-badge ${color}">${event.badge}</span>
                            </div>
                            ${event.error ? `<div class="text-[10px] text-red-500 mt-1 truncate">⚠ ${event.error}</div>` : ''}
                        </div>
                    </div>`;
            });

            html += '</div>';
            container.innerHTML = html;
        })
        .catch(() => {
            container.innerHTML = `
                <div class="text-center py-8 text-red-400 dark:text-red-500">
                    <x-icon name="warning" class="w-10 h-10 mx-auto mb-2 opacity-50" />
                    <p class="text-sm">Zaman çizelgesi yüklenemedi</p>
                </div>`;
        });
    }

    function refreshTimeline() {
        const container = document.getElementById('timeline-container');
        container.innerHTML = `
            <div class="text-center py-8 text-slate-400 dark:text-slate-500">
                <x-icon name="zaman" class="w-10 h-10 mx-auto mb-2 opacity-20 animate-spin" />
                <p class="text-sm">Zaman çizelgesi güncelleniyor…</p>
            </div>`;
        loadTimeline();
    }

    document.addEventListener('DOMContentLoaded', loadTimeline);

    // Sprint 4.7 — Execution Replay
    function replayExecution(execId) {
        const wsId = {{ $ws['id'] }};
        if (!confirm('Bu execution yeniden çalıştırılacak. Devam?')) return;

        fetch(`/admin/workspace/${wsId}/executions/${execId}/replay`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.content,
            },
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                alert('Hata: ' + data.error);
            } else {
                // Refresh the page to update execution monitor
                window.location.reload();
            }
        })
        .catch(() => alert('Replay başlatılamadı.'));
    }
</script>
@endpush
