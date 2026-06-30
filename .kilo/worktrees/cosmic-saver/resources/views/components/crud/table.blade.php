@props([
    'columns' => [],
    'items' => [],
    'actions' => true,
    'viewRoute' => null,
    'editRoute' => null,
    'deleteRoute' => null,
    'idField' => 'id',
    'emptyMessage' => 'Kayıt bulunamadı.',
    'tableClass' => 'min-w-full divide-y divide-gray-200 dark:divide-gray-700',
    'theadClass' => 'bg-gray-50 dark:bg-gray-800',
    'tbodyClass' => 'bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700',
])

<div class="overflow-x-auto">
    <table class="{{ $tableClass }}">
        <thead class="{{ $theadClass }}">
            <tr>
                @foreach($columns as $column)
                    <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                        {{ $column['label'] }}
                    </th>
                @endforeach

                @if($actions)
                    <th scope="col" class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-slate-200 uppercase tracking-wider">
                        İşlemler
                    </th>
                @endif
            </tr>
        </thead>
        <tbody class="{{ $tbodyClass }}">
            @forelse($items as $item)
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                    @foreach($columns as $column)
                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ isset($column['class']) ? $column['class'] : 'text-gray-500 dark:text-gray-400' }}">
                            @if(isset($column['render']) && is_callable($column['render']))
                                {!! $column['render']($item) !!}
                            @elseif(isset($column['field']))
                                @if(is_array($item))
                                    {{ $item[$column['field']] ?? '' }}
                                @else
                                    {{ $item->{$column['field']} ?? '' }}
                                @endif
                            @endif
                        </td>
                    @endforeach

                    @if($actions)
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex justify-end space-x-2">
                                @if($viewRoute)
                                    <a href="{{ route($viewRoute, [is_array($item) ? $item[$idField] : $item->{$idField}]) }}" class="text-blue-600 hover:text-blue-900 dark:text-blue-400 dark:hover:text-blue-300" title="Görüntüle">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                        </svg>
                                    </a>
                                @endif

                                @if($editRoute)
                                    <a href="{{ route($editRoute, [is_array($item) ? $item[$idField] : $item->{$idField}]) }}" class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300" title="Düzenle">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                    </a>
                                @endif

                                @if($deleteRoute)
                                    <form method="POST" action="{{ route($deleteRoute, [is_array($item) ? $item[$idField] : $item->{$idField}]) }}" class="inline" onsubmit="return confirm('Bu kaydı silmek istediğinizden emin misiniz?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-red-600 hover:text-red-900 dark:text-red-400 dark:hover:text-red-300" title="Sil">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) + ($actions ? 1 : 0) }}" class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center italic">
                        {{ $emptyMessage }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
