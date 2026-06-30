@extends('admin.layouts.admin')

@section('title', 'Otonom Kontrol Paneli')

@section('content')
<div class="mx-auto max-w-7xl" x-data="{ showDryRunLog: false }">

    {{-- ═══════ HEADER ═══════ --}}
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Otonom Kontrol Paneli</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">SAB6 — Kontrollü Otonom Sistem Yönetimi</p>
        </div>
        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.governance.intelligence-center') }}"
               class="rounded-lg border border-gray-300 px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 dark:border-slate-600 dark:text-gray-300 dark:hover:bg-slate-700">
                AI Kontrol Merkezi
            </a>
        </div>
    </div>

    {{-- ═══════ SYSTEM STATUS ALERT ═══════ --}}
    @if($autonomyStatus['system_paused'])
        <div class="mb-6 rounded-lg border-2 border-red-400 bg-red-50 p-4 dark:border-red-700 dark:bg-red-950/30">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <span class="text-3xl">🛑</span>
                    <div>
                        <h3 class="text-lg font-bold text-red-800 dark:text-red-300">SİSTEM DURDURULDU</h3>
                        <p class="text-sm text-red-600 dark:text-red-400">
                            Tüm otonom operasyonlar durdu.
                            @if($autonomyStatus['pause_info'])
                                Durduran: Kullanıcı #{{ $autonomyStatus['pause_info']['paused_by'] ?? 'Sistem' }}
                                · {{ \Carbon\Carbon::parse($autonomyStatus['pause_info']['paused_at'])->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                </div>
                <form method="POST" action="{{ route('admin.governance.autonomy.resume') }}">
                    @csrf
                    <button type="submit"
                            class="rounded-lg bg-green-600 px-6 py-3 text-sm font-bold text-white hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600"
                            onclick="return confirm('Otonom operasyonları devam ettirmek istediğinizden emin misiniz?')">
                        Sistemi Devam Ettir
                    </button>
                </form>
            </div>
        </div>
    @endif

    {{-- ═══════ TOP ROW: CRITICAL CONTROLS ═══════ --}}
    <div class="mb-6 grid grid-cols-1 gap-4 lg:grid-cols-3">

        {{-- STOP AI Button --}}
        <div class="rounded-lg border-2 {{ $autonomyStatus['system_paused'] ? 'border-gray-300 bg-gray-50 dark:border-slate-600 dark:bg-slate-800' : 'border-red-300 bg-white dark:border-red-700 dark:bg-slate-800' }} p-6 text-center shadow-sm">
            <span class="text-4xl">{{ $autonomyStatus['system_paused'] ? '⏸️' : '🔴' }}</span>
            <h3 class="mt-3 text-lg font-bold text-gray-900 dark:text-white">Acil Durdurma</h3>
            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Tüm otonom aksiyonları anında durdur</p>
            @if(!$autonomyStatus['system_paused'])
                <form method="POST" action="{{ route('admin.governance.autonomy.pause') }}" class="mt-4">
                    @csrf
                    <button type="submit"
                            class="w-full rounded-lg bg-red-600 px-6 py-3 text-lg font-bold text-white shadow-md hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600"
                            onclick="return confirm('TÜM OTONOM OPERASYONLARı DURDURMAK İSTEDİĞİNİZDEN EMİN MİSİNİZ?')">
                        STOP AI
                    </button>
                </form>
            @else
                <p class="mt-4 text-sm font-medium text-gray-500 dark:text-gray-400">Sistem zaten durdu</p>
            @endif
        </div>

        {{-- Autonomy Level --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Otonom Seviye</h3>
                @php
                    $levelColor = match($autonomyStatus['autonomy_level']) {
                        0 => 'bg-gray-500',
                        1 => 'bg-blue-500',
                        2 => 'bg-green-500',
                        3 => 'bg-yellow-500',
                        4 => 'bg-red-500',
                        default => 'bg-gray-500',
                    };
                @endphp
                <span class="rounded-full {{ $levelColor }} px-3 py-1 text-lg font-bold text-white">
                    L{{ $autonomyStatus['autonomy_level'] }}
                </span>
            </div>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">{{ $autonomyStatus['autonomy_label'] }}</p>
            <form method="POST" action="{{ route('admin.governance.autonomy.update-level') }}">
                @csrf
                <select name="autonomy_level"
                        class="mb-3 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                    @for($i = 0; $i <= 4; $i++)
                        <option value="{{ $i }}" {{ $autonomyStatus['autonomy_level'] === $i ? 'selected' : '' }}>
                            Seviye {{ $i }}: {{ app(\App\Services\Intelligence\AutonomyService::class)->getAutonomyLevelLabel($i) }}
                        </option>
                    @endfor
                </select>
                <button type="submit"
                        class="w-full rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600"
                        onclick="return confirm('Otonom seviyeyi değiştirmek istediğinizden emin misiniz?')">
                    Seviye Güncelle
                </button>
            </form>
        </div>

        {{-- Dry Run Mode --}}
        <div class="rounded-lg border {{ $autonomyStatus['dry_run'] ? 'border-amber-300 bg-amber-50 dark:border-amber-700 dark:bg-amber-950/20' : 'border-gray-200 bg-white dark:border-slate-700 dark:bg-slate-800' }} p-6 shadow-sm">
            <div class="mb-3 flex items-center justify-between">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white">Simülasyon Modu</h3>
                @if($autonomyStatus['dry_run'])
                    <span class="rounded-full bg-amber-500 px-3 py-1 text-xs font-bold text-white">AKTİF</span>
                @else
                    <span class="rounded-full bg-gray-300 px-3 py-1 text-xs font-bold text-gray-700 dark:bg-slate-600 dark:text-gray-300">KAPALI</span>
                @endif
            </div>
            <p class="mb-4 text-sm text-gray-600 dark:text-gray-400">
                {{ $autonomyStatus['dry_run']
                    ? 'Aksiyonlar simüle ediliyor, gerçekte uygulanmıyor.'
                    : 'Aksiyonlar gerçekte uygulanıyor.' }}
            </p>
            <form method="POST" action="{{ route('admin.governance.autonomy.toggle-dry-run') }}">
                @csrf
                <button type="submit"
                        class="w-full rounded-lg {{ $autonomyStatus['dry_run'] ? 'bg-green-600 hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600' : 'bg-amber-600 hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600' }} px-4 py-2 text-sm font-medium text-white">
                    {{ $autonomyStatus['dry_run'] ? 'Simülasyonu Kapat (Gerçek Mod)' : 'Simülasyon Moduna Geç' }}
                </button>
            </form>
            @if($autonomyStatus['dry_run_log_count'] > 0)
                <button @click="showDryRunLog = !showDryRunLog"
                        class="mt-2 w-full text-center text-xs text-amber-600 hover:text-amber-700 dark:text-amber-400">
                    Bugünkü simülasyonlar: {{ $autonomyStatus['dry_run_log_count'] }}
                </button>
            @endif
        </div>
    </div>

    {{-- ═══════ AUTONOMY LEVEL VISUAL ═══════ --}}
    <div class="mb-6 rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Otonom Seviye Haritası</h3>
        <div class="flex items-center gap-2">
            @foreach([
                0 => ['label' => 'Manuel', 'color' => 'gray', 'desc' => 'Sadece insan'],
                1 => ['label' => 'Öneri', 'color' => 'blue', 'desc' => 'AI önerir'],
                2 => ['label' => 'Düşük Oto', 'color' => 'green', 'desc' => 'LOW auto-run'],
                3 => ['label' => 'Orta Oto', 'color' => 'yellow', 'desc' => 'LOW+MED auto'],
                4 => ['label' => 'Tam Oto', 'color' => 'red', 'desc' => 'Kısıtlı tam'],
            ] as $lvl => $info)
                <div class="flex-1 {{ $autonomyStatus['autonomy_level'] === $lvl ? 'ring-2 ring-indigo-500 ring-offset-2 dark:ring-offset-slate-800' : '' }} rounded-lg border border-{{ $info['color'] }}-200 bg-{{ $info['color'] }}-50 p-3 text-center dark:border-{{ $info['color'] }}-800 dark:bg-{{ $info['color'] }}-900/20">
                    <p class="text-lg font-bold text-{{ $info['color'] }}-700 dark:text-{{ $info['color'] }}-400">{{ $lvl }}</p>
                    <p class="text-xs font-medium text-{{ $info['color'] }}-600 dark:text-{{ $info['color'] }}-500">{{ $info['label'] }}</p>
                    <p class="mt-1 text-[10px] text-{{ $info['color'] }}-500 dark:text-{{ $info['color'] }}-600">{{ $info['desc'] }}</p>
                </div>
                @if($lvl < 4)
                    <span class="text-gray-400 dark:text-slate-600">→</span>
                @endif
            @endforeach
        </div>
    </div>

    {{-- ═══════ ACTION BUDGET + ANOMALY ═══════ --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Action Budget --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Aksiyon Bütçesi</h3>
            <div class="mb-4 space-y-3">
                {{-- Hourly --}}
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                        <span>Bu saat</span>
                        <span class="{{ $autonomyStatus['actions']['this_hour'] >= $autonomyStatus['actions']['max_per_hour'] ? 'font-bold text-red-600 dark:text-red-400' : '' }}">
                            {{ $autonomyStatus['actions']['this_hour'] }} / {{ $autonomyStatus['actions']['max_per_hour'] }}
                        </span>
                    </div>
                    @php
                        $hourPct = $autonomyStatus['actions']['max_per_hour'] > 0
                            ? min(100, ($autonomyStatus['actions']['this_hour'] / $autonomyStatus['actions']['max_per_hour']) * 100)
                            : 0;
                        $hourColor = $hourPct >= 90 ? 'bg-red-500' : ($hourPct >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                    @endphp
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-slate-700">
                        <div class="{{ $hourColor }} h-2 rounded-full transition-all" style="width: {{ $hourPct }}%"></div>
                    </div>
                </div>

                {{-- Daily --}}
                <div>
                    <div class="mb-1 flex items-center justify-between text-xs text-gray-600 dark:text-gray-400">
                        <span>Bugün</span>
                        <span class="{{ $autonomyStatus['actions']['today'] >= $autonomyStatus['actions']['max_per_day'] ? 'font-bold text-red-600 dark:text-red-400' : '' }}">
                            {{ $autonomyStatus['actions']['today'] }} / {{ $autonomyStatus['actions']['max_per_day'] }}
                        </span>
                    </div>
                    @php
                        $dayPct = $autonomyStatus['actions']['max_per_day'] > 0
                            ? min(100, ($autonomyStatus['actions']['today'] / $autonomyStatus['actions']['max_per_day']) * 100)
                            : 0;
                        $dayColor = $dayPct >= 90 ? 'bg-red-500' : ($dayPct >= 70 ? 'bg-yellow-500' : 'bg-green-500');
                    @endphp
                    <div class="h-2 w-full rounded-full bg-gray-200 dark:bg-slate-700">
                        <div class="{{ $dayColor }} h-2 rounded-full transition-all" style="width: {{ $dayPct }}%"></div>
                    </div>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.governance.autonomy.update-budget') }}" class="border-t border-gray-200 pt-4 dark:border-slate-700">
                @csrf
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Saatlik limit</label>
                        <input type="number" name="max_actions_per_hour" min="1" max="100"
                               value="{{ $autonomyStatus['actions']['max_per_hour'] }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                    </div>
                    <div>
                        <label class="text-xs font-medium text-gray-600 dark:text-gray-400">Günlük limit</label>
                        <input type="number" name="max_actions_per_day" min="1" max="1000"
                               value="{{ $autonomyStatus['actions']['max_per_day'] }}"
                               class="mt-1 block w-full rounded-md border-gray-300 text-sm shadow-sm dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100">
                    </div>
                </div>
                <button type="submit"
                        class="mt-3 w-full rounded-lg bg-gray-600 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 dark:bg-slate-600 dark:hover:bg-slate-500">
                    Bütçe Güncelle
                </button>
            </form>
        </div>

        {{-- Anomaly Detection --}}
        <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
            <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Anomali Tespiti</h3>
            <div class="space-y-4">
                {{-- Rollbacks this hour --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Geri almalar (bu saat)</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Limit: {{ $autonomyStatus['anomaly']['max_rollbacks_per_hour'] }}/saat</p>
                    </div>
                    @php
                        $rollbackDanger = $autonomyStatus['anomaly']['rollbacks_this_hour'] >= $autonomyStatus['anomaly']['max_rollbacks_per_hour'];
                    @endphp
                    <span class="rounded-full {{ $rollbackDanger ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' }} px-3 py-1 text-sm font-bold">
                        {{ $autonomyStatus['anomaly']['rollbacks_this_hour'] }}
                    </span>
                </div>

                {{-- Failures this hour --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Başarısızlıklar (bu saat)</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Limit: {{ $autonomyStatus['anomaly']['max_failures_per_hour'] }}/saat</p>
                    </div>
                    @php
                        $failureDanger = $autonomyStatus['anomaly']['failures_this_hour'] >= $autonomyStatus['anomaly']['max_failures_per_hour'];
                    @endphp
                    <span class="rounded-full {{ $failureDanger ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' }} px-3 py-1 text-sm font-bold">
                        {{ $autonomyStatus['anomaly']['failures_this_hour'] }}
                    </span>
                </div>

                {{-- Error rate threshold --}}
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-gray-300">Hata oranı eşiği</p>
                        <p class="text-xs text-gray-500 dark:text-gray-500">Aşarsa sistem otomatik durur</p>
                    </div>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-bold text-gray-700 dark:bg-slate-700 dark:text-gray-300">
                        %{{ round($autonomyStatus['anomaly']['error_rate_threshold'] * 100) }}
                    </span>
                </div>
            </div>

            @if($rollbackDanger || $failureDanger)
                <div class="mt-4 rounded-lg bg-red-100 p-3 dark:bg-red-900/20">
                    <p class="text-xs font-medium text-red-800 dark:text-red-300">
                        Anomali eşiğine yaklaşıldı veya aşıldı. Sistem otomatik olarak duracak.
                    </p>
                </div>
            @endif
        </div>
    </div>

    {{-- ═══════ ZONES ═══════ --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">

        {{-- Safe Zones --}}
        <div class="rounded-lg border border-green-200 bg-green-50 p-6 shadow-sm dark:border-green-800 dark:bg-green-900/10">
            <h3 class="mb-3 text-sm font-semibold text-green-900 dark:text-green-300">Güvenli Bölgeler (AI çalışabilir)</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($autonomyStatus['safe_zones'] as $zone)
                    <span class="rounded-full bg-green-200 px-3 py-1 text-xs font-medium text-green-800 dark:bg-green-800/40 dark:text-green-300">
                        {{ $zone }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Blocked Zones --}}
        <div class="rounded-lg border border-red-200 bg-red-50 p-6 shadow-sm dark:border-red-800 dark:bg-red-900/10">
            <h3 class="mb-3 text-sm font-semibold text-red-900 dark:text-red-300">Engelli Bölgeler (AI asla çalışamaz)</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($autonomyStatus['blocked_zones'] as $zone)
                    <span class="rounded-full bg-red-200 px-3 py-1 text-xs font-medium text-red-800 dark:bg-red-800/40 dark:text-red-300">
                        {{ $zone }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- ═══════ DRY RUN LOG ═══════ --}}
    <div x-show="showDryRunLog" x-transition class="mb-6">
        <div class="rounded-lg border border-amber-200 bg-amber-50 p-6 shadow-sm dark:border-amber-800 dark:bg-amber-900/10">
            <h3 class="mb-4 text-sm font-semibold text-amber-900 dark:text-amber-300">Bugünkü Simülasyon Kayıtları</h3>
            @if(count($dryRunLog) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="text-xs text-amber-700 dark:text-amber-400">
                            <tr>
                                <th class="pb-2">Zaman</th>
                                <th class="pb-2">Bulgu</th>
                                <th class="pb-2">Aksiyon</th>
                                <th class="pb-2">Ciddiyet</th>
                                <th class="pb-2">Hedef</th>
                            </tr>
                        </thead>
                        <tbody class="text-amber-800 dark:text-amber-300">
                            @foreach($dryRunLog as $entry)
                                <tr class="border-t border-amber-200 dark:border-amber-800">
                                    <td class="py-2 text-xs">{{ \Carbon\Carbon::parse($entry['simulated_at'])->format('H:i') }}</td>
                                    <td class="py-2">{{ Str::limit($entry['title'], 40) }}</td>
                                    <td class="py-2 text-xs">{{ $entry['action'] }}</td>
                                    <td class="py-2 text-xs">{{ $entry['impact']['severity'] ?? '—' }}</td>
                                    <td class="py-2 font-mono text-xs">{{ Str::limit($entry['impact']['target'] ?? '—', 30) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-amber-600 dark:text-amber-400">Bugün henüz simülasyon yapılmadı.</p>
            @endif
        </div>
    </div>

    {{-- ═══════ DECISION RULES SUMMARY ═══════ --}}
    <div class="rounded-lg border border-gray-200 bg-white p-6 shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <h3 class="mb-4 text-sm font-semibold text-gray-900 dark:text-white">Karar Kuralları (Seviye {{ $autonomyStatus['autonomy_level'] }})</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="text-xs text-gray-500 dark:text-gray-400">
                    <tr>
                        <th class="pb-3">Ciddiyet</th>
                        <th class="pb-3">Karar</th>
                        <th class="pb-3">Durum</th>
                    </tr>
                </thead>
                <tbody class="text-gray-700 dark:text-gray-300">
                    @php
                        $level = $autonomyStatus['autonomy_level'];
                        $rules = [
                            ['severity' => 'LOW', 'decision' => $level >= 2 ? 'AUTO_RUN' : ($level === 1 ? 'SUGGEST' : 'MANUAL'), 'ok' => $level >= 2],
                            ['severity' => 'MEDIUM', 'decision' => $level >= 3 ? 'AUTO_RUN' : 'NEEDS_REVIEW', 'ok' => $level >= 3],
                            ['severity' => 'HIGH', 'decision' => 'NEEDS_REVIEW', 'ok' => false],
                            ['severity' => 'CRITICAL', 'decision' => 'BLOCKED', 'ok' => false],
                        ];
                    @endphp
                    @foreach($rules as $rule)
                        <tr class="border-t border-gray-100 dark:border-slate-700">
                            <td class="py-2 font-medium">{{ $rule['severity'] }}</td>
                            <td class="py-2">
                                <span class="rounded-full px-2 py-0.5 text-xs font-medium
                                    {{ $rule['decision'] === 'AUTO_RUN' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' :
                                       ($rule['decision'] === 'BLOCKED' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' :
                                       ($rule['decision'] === 'SUGGEST' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400' :
                                       'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400')) }}">
                                    {{ $rule['decision'] }}
                                </span>
                            </td>
                            <td class="py-2">
                                {{ $rule['ok'] ? '🟢 Otonom' : '🔴 İnsan gerekli' }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
