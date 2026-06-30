@props([
    'tabs' => [
        ['label' => 'Satılık', 'value' => 'sale'],
        ['label' => 'Kiralık', 'value' => 'rent'],
        ['label' => 'Yazlık', 'value' => 'seasonal'],
        ['label' => 'Vatandaşlığa Uygun', 'value' => 'citizenship'],
    ],
    'active' => 'sale',
])

<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-md border border-gray-200 dark:border-slate-800 p-2 dark:shadow-none dark:border-slate-700">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2">
        @foreach ($tabs as $tab)
            <button
                type="button"
                data-category-tab="{{ $tab['value'] }}"
                class="group flex items-center justify-between gap-2 rounded-xl px-4 py-3 text-sm font-semibold transition-all duration-300
                {{ $active === $tab['value'] ? 'bg-gradient-to-r from-blue-600 to-indigo-600 text-white shadow-lg' : 'bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-200 hover:bg-gray-200 dark:hover:bg-gray-700' }}"
            >
                <span>{{ $tab['label'] }}</span>
                <span class="material-symbols-outlined text-xs opacity-70 group-hover:translate-x-1 transition-transform duration-300">chevron_right</span>
            </button>
        @endforeach
    </div>
</div>

