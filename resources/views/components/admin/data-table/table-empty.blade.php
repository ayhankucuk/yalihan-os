{{-- resources/views/components/admin/data-table/table-empty.blade.php --}}
@props([
    'message' => 'No items found',
    'action' => null,
    'actionText' => 'Add New',
    'columnsCount' => 1,
])

<tr>
    <td colspan="{{ $columnsCount }}" class="px-6 py-16 text-center">
        <div class="flex flex-col items-center justify-center space-y-4">
            {{-- Icon --}}
            <div class="w-16 h-16 rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center">
                <svg class="w-8 h-8 text-slate-400 dark:text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path>
                </svg>
            </div>

            {{-- Message --}}
            <div class="text-center">
                <p class="text-slate-600 dark:text-slate-400 font-medium">{{ $message }}</p>
                <p class="text-sm text-slate-400 dark:text-slate-500 mt-1">Try adjusting your search or filter criteria</p>
            </div>

            {{-- Action Buttons --}}
            <div class="flex items-center gap-3 pt-2">
                @if($action)
                <a href="{{ $action }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    {{ $actionText }}
                </a>
                @endif

                @if(request()->has('q'))
                <a href="{{ url()->current() }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-sm font-medium rounded-lg transition-colors">
                    Clear Filters
                </a>
                @endif
            </div>
        </div>
    </td>
</tr>
