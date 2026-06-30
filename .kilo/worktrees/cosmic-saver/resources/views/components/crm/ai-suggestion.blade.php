@props(['context'])
<div
    class="p-4 bg-gradient-to-r from-blue-50 to-purple-50 rounded-xl border border-blue-100 flex items-center gap-3 shadow rounded-lg border-gray-200 bg-gray-50 dark:bg-slate-900 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
    <svg class="w-6 h-6 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
            d="M13 16h-1v-4h-1m4 4v-1a2 2 0 00-2-2h-1a2 2 0 00-2 2v1m6 4H6a2 2 0 01-2-2V6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2z" />
    </svg>
    <span class="text-sm text-blue-700">
        {{ $context['ai_suggestion'] ?? 'AI: Bu müşteri için “Premium Emlak” öneriliyor.' }}
    </span>
</div>
