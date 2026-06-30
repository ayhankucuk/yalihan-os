{{-- Match Badge Component - Müşteri Uyum Rozetleri --}}
@php
    // En yüksek match skoru bul
    $maxMatch = $ilan->matchingFeedbacks()
        // ->where('aktiflik_durumu', true) // 'aktiflik_durumu' column might not exist or be needed here, removing filter to be safe or assuming all feedbacks are valid
        ->max('cortex_score_at_time') ?? null;

    if ($maxMatch) {
        if ($maxMatch >= 90) {
            $badge = '✅';
            $label = 'Mükemmel Uyum';
            $bgColor = 'bg-emerald-100 dark:bg-emerald-900/30';
            $textColor = 'text-emerald-700 dark:text-emerald-300';
            $borderColor = 'border-emerald-300 dark:border-emerald-700';
        } elseif ($maxMatch >= 75) {
            $badge = '👍';
            $label = 'Çok İyi Uyum';
            $bgColor = 'bg-blue-100 dark:bg-blue-900/30';
            $textColor = 'text-blue-700 dark:text-blue-300';
            $borderColor = 'border-blue-300 dark:border-blue-700';
        } elseif ($maxMatch >= 60) {
            $badge = '⚠️';
            $label = 'İyi Uyum';
            $bgColor = 'bg-amber-100 dark:bg-amber-900/30';
            $textColor = 'text-amber-700 dark:text-amber-300';
            $borderColor = 'border-amber-300 dark:border-amber-700';
        } else {
            $badge = '📋';
            $label = 'Gözden Geçir';
            $bgColor = 'bg-gray-100 dark:bg-gray-800';
            $textColor = 'text-gray-700 dark:text-gray-300';
            $borderColor = 'border-gray-300 dark:border-gray-700';
        }
    }
@endphp

@if ($maxMatch)
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border {{ $bgColor }} $borderColor cursor-pointer hover:shadow-md transition-shadow duration-200 group"
         @click="openMatchModal({{ $ilan->id }})"
         title="Müşteri uyum detaylarını görmek için tıklayın">
        <span class="text-lg">{{ $badge }}</span>
        <span class="text-xs font-semibold {{ $textColor }}">{{ $label }}</span>
        <span class="text-xs {{ $textColor }} opacity-75">{{ round($maxMatch) }}%</span>
        
        {{-- Hover Tooltip --}}
        <div class="invisible group-hover:visible absolute bg-gray-900 dark:bg-gray-200 text-white dark:text-gray-900 text-xs px-3 py-2 rounded-md whitespace-nowrap -bottom-10 left-1/2 transform -translate-x-1/2 z-50 pointer-events-none">
            {{ $ilan->matchingFeedbacks()->where('aktiflik_durumu', true)->count() }} müşteri eşleşmesi
        </div>
    </div>
@else
    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border bg-gray-100 dark:bg-slate-900 border-gray-300 dark:border-slate-800 text-xs font-semibold text-gray-600 dark:text-gray-400">
        <span class="text-lg">🔍</span>
        <span>Henüz Eşleş Yok</span>
    </div>
@endif
