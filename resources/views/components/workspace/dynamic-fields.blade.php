{{-- resources/views/components/workspace/dynamic-fields.blade.php --}}
@props(['fields' => [], 'values' => [], 'readiness' => null])

@php
    $missingFields = $readiness['missing_fields'] ?? [];
    $score = $readiness['readiness_score'] ?? 0;
    $status = $readiness['readiness_status'] ?? 'incomplete';
    $summary = $readiness['summary'] ?? '';

    // Color schema based on status (Context7 premium aesthetic)
    $statusColors = match($status) {
        'ready' => [
            'bg' => 'bg-emerald-50 dark:bg-emerald-950/15',
            'border' => 'border-emerald-200 dark:border-emerald-900/30',
            'text' => 'text-emerald-800 dark:text-emerald-400',
            'badge' => 'bg-emerald-500 text-white',
            'icon' => '🟢'
        ],
        'warning' => [
            'bg' => 'bg-amber-50 dark:bg-amber-950/15',
            'border' => 'border-amber-200 dark:border-amber-900/30',
            'text' => 'text-amber-800 dark:text-amber-400',
            'badge' => 'bg-amber-500 text-white',
            'icon' => '⚠️'
        ],
        default => [
            'bg' => 'bg-rose-50 dark:bg-rose-950/15',
            'border' => 'border-rose-200 dark:border-rose-900/30',
            'text' => 'text-rose-800 dark:text-rose-400',
            'badge' => 'bg-rose-500 text-white',
            'icon' => '❌'
        ]
    };
@endphp

<div class="space-y-6" id="dynamic-fields-container">
    {{-- Readiness Summary Header --}}
    @if($readiness)
        <div class="p-5 rounded-2xl border {{ $statusColors['bg'] }} {{ $statusColors['border'] }} {{ $statusColors['text'] }} transition-all">
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-3">
                    <span class="text-2xl mt-0.5">{{ $statusColors['icon'] }}</span>
                    <div>
                        <h4 class="font-bold text-sm tracking-wide uppercase">Yayın Hazırlık Durumu</h4>
                        <p class="mt-1 text-xs opacity-90 leading-relaxed">{{ $summary }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-3 self-end sm:self-center">
                    <div class="text-right">
                        <span class="block text-2xl font-black">{{ $score }}<span class="text-xs opacity-75">/100</span></span>
                        <span class="text-[10px] uppercase font-bold tracking-widest opacity-75">Hazırlık Skoru</span>
                    </div>
                    <span class="px-3 py-1 rounded-full text-xs font-black uppercase tracking-wider {{ $statusColors['badge'] }}">
                        {{ $status }}
                    </span>
                </div>
            </div>
        </div>
    @endif

    {{-- Fields Grid --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        @foreach($fields as $field)
            @php
                $key = $field['key'];
                $val = $values[$key] ?? null;
                $isMissing = in_array($key, $missingFields, true);
            @endphp
            <x-workspace.dynamic-field
                :field="$field"
                :value="$val"
                :isMissing="$isMissing"
            />
        @endforeach
    </div>
</div>
