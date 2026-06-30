@extends('admin.layouts.admin')

@section('title', 'Karar Detay — ' . $decision->title)

@section('content')
<div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8" x-data="{ showWhyPanel: false, showFeedbackForm: false }">

    {{-- Breadcrumb --}}
    <nav class="mb-6 text-sm text-gray-500 dark:text-gray-400">
        <a href="{{ route('admin.governance.intelligence-center') }}" class="hover:text-gray-700 dark:hover:text-gray-300">AI Kontrol Merkezi</a>
        <span class="mx-2">/</span>
        <a href="{{ route('admin.governance.review-queue') }}" class="hover:text-gray-700 dark:hover:text-gray-300">İnceleme Kuyruğu</a>
        <span class="mx-2">/</span>
        <span class="text-gray-700 dark:text-gray-200">Karar Detay</span>
    </nav>

    {{-- Header + Quick Actions --}}
    <div class="mb-6 flex flex-wrap items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">{{ $decision->title }}</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">{{ $decision->finding_id }}</p>
        </div>
        {{-- SAB5: Why Panel toggle --}}
        <button @click="showWhyPanel = !showWhyPanel"
                class="rounded-lg border border-indigo-300 px-3 py-2 text-sm font-medium text-indigo-700 hover:bg-indigo-50 dark:border-indigo-600 dark:text-indigo-400 dark:hover:bg-indigo-900/30">
            <span x-text="showWhyPanel ? 'Neden Panelini Gizle' : 'Neden Bu Karar?'"></span>
        </button>
    </div>

    {{-- Status Badge Row with Trust Signals --}}
    <div class="mb-8 flex flex-wrap items-center gap-3">
        @php
            $statusBadge = match($decision->karar_durumu) {
                'pending' => ['bg' => 'yellow', 'label' => 'Bekliyor'],
                'approved' => ['bg' => 'green', 'label' => 'Onaylandı'],
                'rejected' => ['bg' => 'red', 'label' => 'Reddedildi'],
                'auto_applied' => ['bg' => 'blue', 'label' => 'Otomatik Uygulandı'],
                'failed' => ['bg' => 'red', 'label' => 'Başarısız'],
                'rolled_back' => ['bg' => 'purple', 'label' => 'Geri Alındı'],
                'blocked' => ['bg' => 'orange', 'label' => 'Engellendi'],
                default => ['bg' => 'gray', 'label' => ucfirst($decision->karar_durumu)],
            };

            // SAB5: Trust signal — emoji-based confidence indicator
            $trustSignal = match(true) {
                $decision->confidence >= 0.7 => ['emoji' => '🟢', 'label' => 'Yüksek Güven'],
                $decision->confidence >= 0.5 => ['emoji' => '🟡', 'label' => 'Orta Güven'],
                $decision->confidence !== null => ['emoji' => '🔴', 'label' => 'Düşük Güven'],
                default => ['emoji' => '⚪', 'label' => 'Güven Bilinmiyor'],
            };
        @endphp
        <span class="rounded-full bg-{{ $statusBadge['bg'] }}-100 px-3 py-1 text-sm font-medium text-{{ $statusBadge['bg'] }}-800 dark:bg-{{ $statusBadge['bg'] }}-900/30 dark:text-{{ $statusBadge['bg'] }}-400">
            {{ $statusBadge['label'] }}
        </span>

        {{-- SAB5: Trust Signal Badge --}}
        <span class="rounded-full bg-gray-100 px-3 py-1 text-sm font-medium text-gray-700 dark:bg-slate-700 dark:text-gray-300"
              title="{{ $trustSignal['label'] }}">
            {{ $trustSignal['emoji'] }} {{ $trustSignal['label'] }}
            @if($decision->confidence !== null)
                (%{{ round($decision->confidence * 100) }})
            @endif
        </span>

        {{-- Override Badge --}}
        @if($decision->isOverridden())
            <span class="rounded-full bg-indigo-100 px-3 py-1 text-sm font-medium text-indigo-800 dark:bg-indigo-900/30 dark:text-indigo-400">
                Override: {{ $decision->override_decision }}
            </span>
        @endif
    </div>

    {{-- ═══════ SAB5: WHY PANEL ═══════ --}}
    <div x-show="showWhyPanel" x-transition class="mb-6 overflow-hidden rounded-lg border-2 border-indigo-300 bg-indigo-50 shadow-md dark:border-indigo-700 dark:bg-indigo-950/30">
        <div class="border-b border-indigo-200 px-6 py-4 dark:border-indigo-700">
            <h2 class="text-lg font-semibold text-indigo-900 dark:text-indigo-200">Neden Bu Karar? (Why Panel)</h2>
            <p class="mt-1 text-xs text-indigo-600 dark:text-indigo-400">Bu kararın neden verildiğini adım adım açıklar</p>
        </div>
        <div class="space-y-4 px-6 py-4">
            {{-- Step 1: Source --}}
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-200 text-xs font-bold text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200">1</span>
                <div>
                    <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200">Kaynak</h4>
                    <p class="text-sm text-indigo-700 dark:text-indigo-400">
                        Bu bulgu <strong>{{ $decision->source }}</strong> tarama motorunun
                        <strong>{{ $decision->domain }}</strong> alanındaki kontrolünden geldi.
                    </p>
                </div>
            </div>

            {{-- Step 2: What was found --}}
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-200 text-xs font-bold text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200">2</span>
                <div>
                    <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200">Ne Bulundu?</h4>
                    <p class="text-sm text-indigo-700 dark:text-indigo-400">{{ $decision->reason }}</p>
                    <p class="mt-1 text-xs text-indigo-500 dark:text-indigo-500">
                        Hedef: <code class="rounded bg-indigo-200/50 px-1 dark:bg-indigo-800/50">{{ $decision->target }}</code>
                        · Ciddiyet: {{ $decision->severity instanceof \App\Enums\FindingSeverity ? $decision->severity->label() : ucfirst($decision->severity ?? 'bilinmiyor') }}
                    </p>
                </div>
            </div>

            {{-- Step 3: Signals --}}
            @if(!empty($decision->signals))
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-200 text-xs font-bold text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200">3</span>
                <div>
                    <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200">Sinyaller</h4>
                    <p class="text-sm text-indigo-700 dark:text-indigo-400">
                        Guard Policy bu sinyalleri değerlendirdi:
                    </p>
                    <div class="mt-1 flex flex-wrap gap-1.5">
                        @foreach($decision->signals as $signal)
                            <span class="rounded bg-indigo-200/60 px-2 py-0.5 text-xs text-indigo-800 dark:bg-indigo-800/60 dark:text-indigo-300">{{ $signal }}</span>
                        @endforeach
                    </div>
                </div>
            </div>
            @endif

            {{-- Step 4: Decision Logic --}}
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-200 text-xs font-bold text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200">{{ !empty($decision->signals) ? '4' : '3' }}</span>
                <div>
                    <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200">Karar Mantığı</h4>
                    @php
                        $decisionExplanation = match($decision->decision?->value ?? $decision->decision ?? null) {
                            'auto_run', 'AUTO_RUN' => 'Düşük ciddiyet + yüksek güven → Otomatik çalıştırıldı',
                            'needs_review', 'NEEDS_REVIEW' => 'Orta ciddiyet veya düşük güven → İnsan onayı bekleniyor',
                            'blocked', 'BLOCKED' => 'Yüksek ciddiyet veya kritik kural → Engellendi, müdahale gerekli',
                            default => 'Guard Policy kuralları uygulandı',
                        };
                    @endphp
                    <p class="text-sm text-indigo-700 dark:text-indigo-400">{{ $decisionExplanation }}</p>
                    @if(!empty($decision->explanation['rule']))
                        <p class="mt-1 text-xs text-indigo-500 dark:text-indigo-500">Uygulanan kural: <code class="rounded bg-indigo-200/50 px-1 dark:bg-indigo-800/50">{{ $decision->explanation['rule'] }}</code></p>
                    @endif
                    @if($decision->confidence !== null)
                        <p class="mt-1 text-xs text-indigo-500 dark:text-indigo-500">
                            Güven skoru: %{{ round($decision->confidence * 100) }}
                            @if($decision->hasLowConfidence())
                                — <span class="font-medium text-red-600 dark:text-red-400">Düşük güven nedeniyle seviye yükseltildi</span>
                            @endif
                        </p>
                    @endif
                </div>
            </div>

            {{-- Step 5: Similar past cases --}}
            @php
                $similarCount = \App\Models\GovernanceDecision::where('source', $decision->source)
                    ->where('domain', $decision->domain)
                    ->where('id', '!=', $decision->id)
                    ->count();
                $similarApproved = \App\Models\GovernanceDecision::where('source', $decision->source)
                    ->where('domain', $decision->domain)
                    ->where('id', '!=', $decision->id)
                    ->whereIn('karar_durumu', ['approved', 'auto_applied'])
                    ->count();
            @endphp
            @if($similarCount > 0)
            <div class="flex items-start gap-3">
                <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-indigo-200 text-xs font-bold text-indigo-800 dark:bg-indigo-800 dark:text-indigo-200">{{ !empty($decision->signals) ? '5' : '4' }}</span>
                <div>
                    <h4 class="text-sm font-medium text-indigo-900 dark:text-indigo-200">Geçmiş Benzer Kararlar</h4>
                    <p class="text-sm text-indigo-700 dark:text-indigo-400">
                        Aynı kaynak ve alandan <strong>{{ $similarCount }}</strong> benzer karar daha önce verildi.
                        Bunların <strong>{{ $similarApproved }}</strong> tanesi onaylandı/uygulandı
                        (%{{ $similarCount > 0 ? round(($similarApproved / $similarCount) * 100) : 0 }} başarı oranı).
                    </p>
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- ═══════ SAB5: Human-Readable Explanation ═══════ --}}
    @if($decision->explanation)
    <div class="mb-6 overflow-hidden rounded-lg border border-blue-200 bg-blue-50 shadow-sm dark:border-blue-800 dark:bg-blue-900/20">
        <div class="border-b border-blue-200 px-6 py-4 dark:border-blue-800">
            <h2 class="text-lg font-semibold text-blue-900 dark:text-blue-200">Ne Oldu? (Özet)</h2>
        </div>
        <div class="px-6 py-4">
            {{-- Human-readable summary --}}
            <p class="mb-3 text-sm text-blue-800 dark:text-blue-300">
                @php
                    $humanSummary = $decision->explanation['summary'] ?? $decision->reason;
                    // Build a more natural Turkish explanation
                    $naturalExplanation = sprintf(
                        '"%s" tarama motoru, %s alanında bir sorun tespit etti: %s. %s',
                        $decision->source,
                        $decision->domain,
                        $humanSummary,
                        $decision->confidence >= 0.7
                            ? 'Güven skoru yüksek olduğu için sistem bu durumu güvenle değerlendirdi.'
                            : ($decision->confidence >= 0.5
                                ? 'Güven skoru orta seviyede, insan kontrolü önerilir.'
                                : 'Güven skoru düşük — dikkatli inceleme gerekli.')
                    );
                @endphp
                {{ $naturalExplanation }}
            </p>
            @if(!empty($decision->explanation['rule']))
            <p class="mb-2 text-xs text-blue-600 dark:text-blue-400">
                <span class="font-medium">Kural:</span> {{ $decision->explanation['rule'] }}
            </p>
            @endif

            {{-- Recommended action in human terms --}}
            @if($decision->recommended_action)
            <p class="text-xs text-blue-600 dark:text-blue-400">
                <span class="font-medium">Önerilen Aksiyon:</span> {{ $decision->recommended_action }}
            </p>
            @endif
        </div>
    </div>
    @endif

    {{-- Finding Details Card --}}
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Bulgu Detayları</h2>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Kaynak</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->source }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Alan</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->domain }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Ciddiyet</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">
                        {{ $decision->severity instanceof \App\Enums\FindingSeverity ? $decision->severity->label() : ucfirst($decision->severity) }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Risk</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->risk }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Hedef</dt>
                    <dd class="mt-1 text-sm font-mono text-gray-900 dark:text-gray-100">{{ $decision->target }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Önerilen Aksiyon</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->recommended_action }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Sebep</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->reason }}</dd>
                </div>
                @if($decision->meta)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Meta Veri</dt>
                    <dd class="mt-1 rounded bg-gray-50 p-3 font-mono text-xs text-gray-700 dark:bg-slate-900 dark:text-gray-300">
                        <pre>{{ json_encode($decision->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                    </dd>
                </div>
                @endif
            </dl>
        </div>
    </div>

    {{-- Decision Info (if resolved) --}}
    @if($decision->karar_durumu !== 'pending')
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Karar Bilgisi</h2>
        </div>
        <div class="px-6 py-4">
            <dl class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Karar Veren</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->kararVeren?->name ?? 'Sistem (Otomatik)' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Karar Tarihi</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->karar_tarihi?->format('d.m.Y H:i') ?? '—' }}</dd>
                </div>
                @if($decision->karar_notu)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Karar Notu</dt>
                    <dd class="mt-1 text-sm text-gray-900 dark:text-gray-100">{{ $decision->karar_notu }}</dd>
                </div>
                @endif
                @if($decision->proposal_filename)
                <div class="sm:col-span-2">
                    <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">SAB Proposal</dt>
                    <dd class="mt-1 font-mono text-sm text-gray-900 dark:text-gray-100">{{ $decision->proposal_filename }}</dd>
                </div>
                @endif
            </dl>
        </div>
    </div>
    @endif

    {{-- SAB3: Timeline --}}
    @if(!empty($decision->timeline))
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Karar Zaman Çizelgesi</h2>
        </div>
        <div class="px-6 py-4">
            <ol class="relative border-l border-gray-300 dark:border-slate-600">
                @foreach($decision->timeline as $event)
                <li class="mb-6 ml-4">
                    <div class="absolute -left-1.5 mt-1.5 h-3 w-3 rounded-full border border-white bg-gray-400 dark:border-slate-800 dark:bg-gray-500"></div>
                    <time class="mb-1 text-xs font-normal leading-none text-gray-400 dark:text-gray-500">
                        {{ \Carbon\Carbon::parse($event['timestamp'])->format('d.m.Y H:i:s') }}
                    </time>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white">
                        {{ ucfirst(str_replace('_', ' ', $event['event'])) }}
                    </h3>
                    @if(!empty($event['detail']))
                    <p class="text-xs text-gray-500 dark:text-gray-400">{{ $event['detail'] }}</p>
                    @endif
                    @if(!empty($event['user_id']))
                    <p class="text-xs text-gray-400 dark:text-gray-500">Kullanıcı #{{ $event['user_id'] }}</p>
                    @endif
                </li>
                @endforeach
            </ol>
        </div>
    </div>
    @endif

    {{-- Actions (only for pending) --}}
    @if($decision->karar_durumu === 'pending')
    <div class="mb-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">İşlem</h2>
        </div>
        <div class="px-6 py-4">
            <div class="mb-4">
                <label for="karar_notu" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Karar Notu (Opsiyonel)</label>
                <textarea id="karar_notu" name="karar_notu" rows="3" form="approve-form"
                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-600 dark:bg-slate-900 dark:text-gray-100 sm:text-sm"
                    placeholder="Onay veya red sebebi..."></textarea>
            </div>
            <div class="flex gap-3">
                <form id="approve-form" method="POST" action="{{ route('admin.governance.decisions.approve', $decision) }}">
                    @csrf
                    <button type="submit"
                        class="rounded-lg bg-green-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-green-700 dark:bg-green-700 dark:hover:bg-green-600"
                        onclick="this.form.querySelector('[name=karar_notu]') || this.form.appendChild(Object.assign(document.createElement('input'), {type:'hidden', name:'karar_notu', value:document.getElementById('karar_notu').value}))">
                        Onayla — SAB Proposal Oluştur
                    </button>
                </form>
                <form method="POST" action="{{ route('admin.governance.decisions.reject', $decision) }}">
                    @csrf
                    <input type="hidden" name="karar_notu" value="">
                    <button type="submit"
                        class="rounded-lg bg-red-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600"
                        onclick="this.form.querySelector('[name=karar_notu]').value = document.getElementById('karar_notu').value">
                        Reddet
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    {{-- SAB8: Simulate (pending or blocked, no result yet) --}}
    @if(in_array($decision->karar_durumu, ['pending', 'blocked']) && !$decision->hasResult())
    <div class="mb-6 overflow-hidden rounded-lg border border-yellow-200 bg-yellow-50 shadow-sm dark:border-yellow-800 dark:bg-yellow-950/20">
        <div class="border-b border-yellow-200 px-6 py-4 dark:border-yellow-800">
            <h2 class="text-lg font-semibold text-yellow-900 dark:text-yellow-200">Simülasyon</h2>
        </div>
        <div class="px-6 py-4">
            <p class="mb-3 text-sm text-yellow-700 dark:text-yellow-300">Bu kararın aksiyonunu dry-run modunda simüle edin. Gerçek değişiklik yapılmaz.</p>
            <form method="POST" action="{{ route('admin.governance.decisions.simulate', $decision) }}">
                @csrf
                <button type="submit"
                    class="rounded-lg bg-yellow-500 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-yellow-600 dark:bg-yellow-600 dark:hover:bg-yellow-500">
                    🧪 Simüle Et
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- ═══════ SAB8: ACTION LOOP SUMMARY ═══════ --}}
    @if(isset($loopSummary))
    <div class="mb-6 overflow-hidden rounded-lg border-2 border-teal-300 bg-teal-50 shadow-sm dark:border-teal-700 dark:bg-teal-950/20">
        <div class="border-b border-teal-200 px-6 py-4 dark:border-teal-700">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-teal-900 dark:text-teal-200">Karar Döngüsü (SAB8)</h2>
                <a href="{{ route('admin.governance.action-dashboard') }}"
                   class="text-xs text-teal-600 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300">
                    Tüm Aksiyonları Gör →
                </a>
            </div>
        </div>
        <div class="px-6 py-4">
            {{-- Loop Flow Visual --}}
            <div class="mb-4 flex items-center justify-between gap-2">
                @php
                    $steps = [
                        ['label' => 'Bulgu', 'active' => true, 'icon' => '🔍'],
                        ['label' => 'Karar', 'active' => true, 'icon' => '⚖️'],
                        ['label' => 'Aksiyon', 'active' => $loopSummary['action']['applied'], 'icon' => '⚡'],
                        ['label' => 'Sonuç', 'active' => $loopSummary['result'] !== null, 'icon' => '📊'],
                        ['label' => 'Öğrenme', 'active' => $decision->feedback_note !== null, 'icon' => '🧠'],
                    ];
                @endphp
                @foreach($steps as $i => $step)
                    <div class="flex-1 text-center">
                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full
                            {{ $step['active'] ? 'bg-teal-200 dark:bg-teal-800' : 'bg-gray-200 dark:bg-slate-700' }}">
                            <span class="text-lg">{{ $step['icon'] }}</span>
                        </div>
                        <p class="mt-1 text-[10px] font-medium {{ $step['active'] ? 'text-teal-700 dark:text-teal-400' : 'text-gray-400 dark:text-gray-500' }}">{{ $step['label'] }}</p>
                    </div>
                    @if($i < count($steps) - 1)
                        <div class="flex-shrink-0 {{ $step['active'] && ($steps[$i + 1]['active'] ?? false) ? 'text-teal-400 dark:text-teal-600' : 'text-gray-300 dark:text-gray-600' }}">→</div>
                    @endif
                @endforeach
            </div>

            {{-- Action Result (if exists) --}}
            @if($loopSummary['result'] !== null)
                <div class="rounded-lg {{ $loopSummary['result']['success'] ? 'border-green-200 bg-green-50 dark:border-green-800 dark:bg-green-900/20' : 'border-red-200 bg-red-50 dark:border-red-800 dark:bg-red-900/20' }} border p-4">
                    <div class="flex items-center justify-between mb-2">
                        <h4 class="text-sm font-semibold {{ $loopSummary['result']['success'] ? 'text-green-800 dark:text-green-300' : 'text-red-800 dark:text-red-300' }}">
                            {{ $loopSummary['result']['success'] ? '✓ Aksiyon Başarılı' : '✗ Aksiyon Başarısız' }}
                        </h4>
                        @if($loopSummary['result']['impact_score'] !== null)
                            <span class="rounded-full px-3 py-1 text-sm font-bold
                                {{ $loopSummary['result']['impact_score'] > 0 ? 'bg-green-200 text-green-800 dark:bg-green-800/40 dark:text-green-300' :
                                   ($loopSummary['result']['impact_score'] < 0 ? 'bg-red-200 text-red-800 dark:bg-red-800/40 dark:text-red-300' : 'bg-gray-200 text-gray-700 dark:bg-slate-700 dark:text-gray-300') }}">
                                Etki: {{ $loopSummary['result']['impact_score'] > 0 ? '+' : '' }}{{ $loopSummary['result']['impact_score'] }}
                            </span>
                        @endif
                    </div>
                    @if($loopSummary['result']['summary'])
                        <p class="text-sm {{ $loopSummary['result']['success'] ? 'text-green-700 dark:text-green-400' : 'text-red-700 dark:text-red-400' }}">
                            {{ $loopSummary['result']['summary'] }}
                        </p>
                    @endif
                    @if(count($loopSummary['result']['changed_fields']) > 0)
                        <div class="mt-2">
                            <p class="text-xs font-medium {{ $loopSummary['result']['success'] ? 'text-green-600 dark:text-green-500' : 'text-red-600 dark:text-red-500' }}">Değişen alanlar:</p>
                            <div class="mt-1 flex flex-wrap gap-1">
                                @foreach($loopSummary['result']['changed_fields'] as $field)
                                    <span class="rounded bg-white/50 px-2 py-0.5 text-xs font-mono dark:bg-slate-800/50">{{ $field }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    @if($decision->action_completed_at)
                        <p class="mt-2 text-[10px] text-gray-500 dark:text-gray-500">Tamamlandı: {{ $decision->action_completed_at->format('d.m.Y H:i') }}</p>
                    @endif
                </div>
            @elseif($loopSummary['action']['applied'])
                <div class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-900/20">
                    <p class="text-sm text-blue-700 dark:text-blue-400">Aksiyon uygulandı, sonuç henüz kaydedilmedi.</p>
                    <form method="POST" action="{{ route('admin.governance.decisions.record-result', $decision) }}" class="mt-3">
                        @csrf
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="text-xs font-medium text-blue-700 dark:text-blue-400">Başarılı mı?</label>
                                <select name="success" required class="mt-1 block w-full rounded-md border-blue-300 text-sm dark:border-blue-600 dark:bg-slate-900 dark:text-gray-100">
                                    <option value="1">Evet — Başarılı</option>
                                    <option value="0">Hayır — Başarısız</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-medium text-blue-700 dark:text-blue-400">Etki Skoru (-100 / +100)</label>
                                <input type="number" name="impact_score" min="-100" max="100" placeholder="0"
                                       class="mt-1 block w-full rounded-md border-blue-300 text-sm dark:border-blue-600 dark:bg-slate-900 dark:text-gray-100">
                            </div>
                        </div>
                        <div class="mt-3">
                            <label class="text-xs font-medium text-blue-700 dark:text-blue-400">Sonuç Özeti</label>
                            <input type="text" name="result_summary" maxlength="500" placeholder="Ne oldu?"
                                   class="mt-1 block w-full rounded-md border-blue-300 text-sm dark:border-blue-600 dark:bg-slate-900 dark:text-gray-100">
                        </div>
                        <button type="submit" class="mt-3 rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-700 dark:bg-blue-700 dark:hover:bg-blue-600">
                            Sonucu Kaydet
                        </button>
                    </form>
                </div>
            @endif

            {{-- Auto-Run Visibility --}}
            @if($decision->karar_durumu === 'auto_applied')
                <div class="mt-3 rounded-lg border border-blue-200 bg-blue-50 p-3 dark:border-blue-800 dark:bg-blue-900/10">
                    <div class="flex items-center gap-2">
                        <span class="text-lg">🤖</span>
                        <div>
                            <p class="text-sm font-medium text-blue-800 dark:text-blue-300">AI bu aksiyonu otomatik uyguladı</p>
                            <p class="text-xs text-blue-600 dark:text-blue-400">
                                Güven: %{{ $decision->confidence !== null ? round($decision->confidence * 100) : '—' }}
                                · Ciddiyet: {{ $decision->severity->value }}
                                · Kural: {{ $decision->source }}/{{ $decision->domain }}
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- Feedback Section --}}
            <div class="mt-3">
                @if($decision->feedback_note)
                    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-slate-700 dark:bg-slate-800">
                        <p class="text-xs font-medium text-gray-500 dark:text-gray-400">Operatör Geri Bildirimi:</p>
                        <p class="mt-1 text-sm text-gray-800 dark:text-gray-200">{{ $decision->feedback_note }}</p>
                    </div>
                @endif
                <button @click="showFeedbackForm = !showFeedbackForm"
                        class="mt-2 text-xs font-medium text-teal-600 hover:text-teal-800 dark:text-teal-400 dark:hover:text-teal-300">
                    {{ $decision->feedback_note ? 'Geri bildirimi güncelle' : 'Geri bildirim ekle' }}
                </button>
                <div x-show="showFeedbackForm" x-transition class="mt-2">
                    <form method="POST" action="{{ route('admin.governance.decisions.feedback', $decision) }}">
                        @csrf
                        <textarea name="feedback_note" rows="2" required maxlength="500"
                                  class="block w-full rounded-md border-teal-300 text-sm shadow-sm dark:border-teal-600 dark:bg-slate-900 dark:text-gray-100"
                                  placeholder="Bu karar hakkında geri bildiriminiz...">{{ $decision->feedback_note }}</textarea>
                        <button type="submit" class="mt-2 rounded-lg bg-teal-600 px-4 py-1.5 text-sm font-medium text-white hover:bg-teal-700 dark:bg-teal-700 dark:hover:bg-teal-600">
                            Kaydet
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    @endif

    {{-- SAB3: Rollback Action (for approved/auto_applied with snapshot) --}}
    @if($decision->isRollbackable())
    <div class="mb-6 overflow-hidden rounded-lg border border-purple-200 bg-purple-50 shadow-sm dark:border-purple-800 dark:bg-purple-900/20">
        <div class="border-b border-purple-200 px-6 py-4 dark:border-purple-800">
            <h2 class="text-lg font-semibold text-purple-900 dark:text-purple-200">Geri Al (Rollback)</h2>
        </div>
        <div class="px-6 py-4">
            <form method="POST" action="{{ route('admin.governance.decisions.rollback', $decision) }}">
                @csrf
                <div class="mb-4">
                    <label for="rollback_reason" class="block text-sm font-medium text-purple-700 dark:text-purple-300">Geri Alma Sebebi</label>
                    <textarea id="rollback_reason" name="rollback_reason" rows="2" required
                        class="mt-1 block w-full rounded-md border-purple-300 shadow-sm focus:border-purple-500 focus:ring-purple-500 dark:border-purple-600 dark:bg-slate-900 dark:text-gray-100 sm:text-sm"
                        placeholder="Neden geri alınıyor..."></textarea>
                </div>
                <button type="submit"
                    class="rounded-lg bg-purple-600 px-6 py-2 text-sm font-medium text-white shadow-sm hover:bg-purple-700 dark:bg-purple-700 dark:hover:bg-purple-600"
                    onclick="return confirm('Bu kararı geri almak istediğinizden emin misiniz?')">
                    Geri Al
                </button>
            </form>
        </div>
    </div>
    @endif

    {{-- SAB3: Suppress + Override Actions --}}
    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Suppress --}}
        <div class="overflow-hidden rounded-lg border border-amber-200 bg-amber-50 shadow-sm dark:border-amber-800 dark:bg-amber-900/20">
            <div class="border-b border-amber-200 px-6 py-4 dark:border-amber-800">
                <h2 class="text-lg font-semibold text-amber-900 dark:text-amber-200">Bastır (Suppress)</h2>
            </div>
            <div class="px-6 py-4">
                <p class="mb-3 text-xs text-amber-700 dark:text-amber-400">Bu kural gelecekte tetiklendiğinde otomatik olarak yoksayılacak.</p>
                <form method="POST" action="{{ route('admin.governance.decisions.suppress', $decision) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-amber-700 dark:text-amber-300">Kapsam</label>
                        <select name="suppression_scope" required
                            class="mt-1 block w-full rounded-md border-amber-300 text-sm shadow-sm dark:border-amber-600 dark:bg-slate-900 dark:text-gray-100">
                            <option value="domain">Alan ({{ $decision->source }}.{{ $decision->domain }})</option>
                            <option value="source">Kaynak ({{ $decision->source }})</option>
                            <option value="global">Global (tüm kaynaklar)</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-amber-700 dark:text-amber-300">Süre (gün, boş = süresiz)</label>
                        <input type="number" name="suppression_expires_days" min="1" max="365"
                            class="mt-1 block w-full rounded-md border-amber-300 text-sm shadow-sm dark:border-amber-600 dark:bg-slate-900 dark:text-gray-100"
                            placeholder="Örn: 30">
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-amber-700 dark:text-amber-300">Sebep</label>
                        <textarea name="suppression_reason" rows="2" required
                            class="mt-1 block w-full rounded-md border-amber-300 text-sm shadow-sm dark:border-amber-600 dark:bg-slate-900 dark:text-gray-100"
                            placeholder="Neden bastırılıyor..."></textarea>
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700 dark:bg-amber-700 dark:hover:bg-amber-600">
                        Kuralı Bastır
                    </button>
                </form>
            </div>
        </div>

        {{-- Override --}}
        <div class="overflow-hidden rounded-lg border border-indigo-200 bg-indigo-50 shadow-sm dark:border-indigo-800 dark:bg-indigo-900/20">
            <div class="border-b border-indigo-200 px-6 py-4 dark:border-indigo-800">
                <h2 class="text-lg font-semibold text-indigo-900 dark:text-indigo-200">Override (Politika Geçersiz Kıl)</h2>
            </div>
            <div class="px-6 py-4">
                <p class="mb-3 text-xs text-indigo-700 dark:text-indigo-400">Guard kararını manuel olarak değiştir. Loglanır.</p>
                <form method="POST" action="{{ route('admin.governance.decisions.override', $decision) }}">
                    @csrf
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-indigo-700 dark:text-indigo-300">Yeni Karar</label>
                        <select name="override_decision" required
                            class="mt-1 block w-full rounded-md border-indigo-300 text-sm shadow-sm dark:border-indigo-600 dark:bg-slate-900 dark:text-gray-100">
                            <option value="auto_run">Otomatik Çalıştır (Auto-Run)</option>
                            <option value="needs_review">İnceleme Gerekli</option>
                            <option value="blocked">Engelle</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="block text-sm font-medium text-indigo-700 dark:text-indigo-300">Sebep</label>
                        <textarea name="override_reason" rows="2" required
                            class="mt-1 block w-full rounded-md border-indigo-300 text-sm shadow-sm dark:border-indigo-600 dark:bg-slate-900 dark:text-gray-100"
                            placeholder="Neden override ediliyor..."></textarea>
                    </div>
                    <button type="submit"
                        class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-700 dark:bg-indigo-700 dark:hover:bg-indigo-600"
                        onclick="return confirm('Bu kararı override etmek istediğinizden emin misiniz?')">
                        Override Uygula
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
