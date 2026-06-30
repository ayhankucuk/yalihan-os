{{--
    Context7 Component Library ve rounded-xl border border-gray-200 bg-white shadow-sm hover:shadow-md transition-all duration-200 dark:border-gray-700 dark:bg-gray-800-integration.md referans alınmıştır.
    Bu component, modül başlığı ve kısa açıklama ile birlikte, modülün ana fonksiyonlarını ve AI öneri kutusunu içerir.
--}}
@props(['module'])
<div class="bg-white rounded-2xl shadow-lg p-8 max-w-3xl mx-auto mt-8 rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all duration-200 dark:border-slate-800 dark:bg-slate-900 dark:shadow-none dark:border-slate-700">
    <div class="flex items-center gap-4 mb-4">
        <div
            class="w-12 h-12 rounded-full bg-gradient-to-tr from-blue-500 via-purple-500 to-pink-400 flex items-center justify-center text-white text-2xl font-bold shadow dark:shadow-none">
            <i class="fas fa-cube"></i>
        </div>
        <div>
            <div class="text-xl font-semibold text-gray-900 dark:text-slate-100 dark:text-white">{{ $module['title'] ?? 'Modül Başlığı' }}</div>
            <div class="text-gray-500 text-sm">{{ $module['description'] ?? 'Modülün kısa açıklaması.' }}</div>
        </div>
    </div>
    <div class="mb-6">
        <ul class="list-disc pl-6 space-y-1 text-gray-700 dark:text-slate-300">
            @foreach ($module['features'] ?? [] as $feature)
                <li>{{ $feature }}</li>
            @endforeach
        </ul>
    </div>
    <x-crm.ai-suggestion :context="['ai_suggestion' => $module['ai_suggestion'] ?? 'AI: Bu modül için öneri yok.']" />
</div>
