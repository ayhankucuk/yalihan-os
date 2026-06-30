@props(['customer', 'aiSuggestion' => null])
<div class="space-y-4">
    <div class="flex items-center gap-4">
        <span class="font-semibold text-gray-700 dark:text-slate-300">Telefon:</span>
        <span>
            @if (is_array($customer))
                {{ $customer['phone'] ?? '-' }}
            @else
                {{ $customer->phone ?? '-' }}
            @endif
        </span>
    </div>
    <div class="flex items-center gap-4">
        <span class="font-semibold text-gray-700 dark:text-slate-300">Kayıt Tarihi:</span>
        <span>
            @if (is_array($customer))
                {{ isset($customer['created_at']) ? \Carbon\Carbon::parse($customer['created_at'])->format('d.m.Y') : '-' }}
            @else
                {{ isset($customer->created_at) ? $customer->created_at->format('d.m.Y') : '-' }}
            @endif
        </span>
    </div>
    <div class="flex items-center gap-4">
        <span class="font-semibold text-gray-700 dark:text-slate-300">Etiketler:</span>
        <div class="flex gap-2">
            @php
                $tags = is_array($customer)
                    ? $customer['tags'] ?? ['VIP', 'Hızlı Karar']
                    : $customer->tags ?? ['VIP', 'Hızlı Karar'];
            @endphp
            @foreach ($tags as $tag)
                <span
                    class="px-2 py-1 rounded-full text-xs font-bold bg-blue-100 text-blue-700">{{ $tag }}</span>
            @endforeach
        </div>
    </div>
    <!-- AI Öneri Kutusu -->
    <x-crm.ai-suggestion :context="['ai_suggestion' => $aiSuggestion]" />
</div>
