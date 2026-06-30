{{-- resources/views/admin/ilanlar/components/listings-table.blade.php --}}
@props(['listings'])

<div class="bg-white dark:bg-slate-900 rounded-2xl shadow-lg border border-gray-100 dark:border-slate-800 overflow-hidden">
    <!-- Header -->
    <div class="p-6 border-b border-gray-100 dark:border-slate-800 flex items-center justify-between">
        <h2 class="text-xl font-bold text-gray-800 dark:text-slate-200 flex items-center">
            <svg class="w-6 h-6 mr-3 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor"
                viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4">
                </path>
            </svg>
            @lang('admin.listing_list')
        </h2>
        <span class="text-sm text-gray-500 dark:text-gray-400" x-text="`${totalCount} @lang('admin.listings')`">
            {{ $listings->total() }} @lang('admin.listings')
        </span>
    </div>

    <!-- Table -->
    <div x-show="viewMode === 'table'" class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-700 dark:bg-slate-900">
                <tr>
                    <th class="admin-table-th dark:text-slate-200 w-12">
                        <input type="checkbox" id="select-all-listings"
                            class="rounded border-gray-300 text-blue-600 focus:ring-blue-500" x-model="selectAll"
                            @change="toggleSelectAll()">
                    </th>
                    <th class="admin-table-th dark:text-slate-200">@lang('admin.listing')</th>
                    <th class="admin-table-th dark:text-slate-200">@lang('admin.category')</th>
                    <th class="admin-table-th dark:text-slate-200">@lang('admin.durum')</th>
                    <th class="admin-table-th dark:text-slate-200">@lang('admin.price')</th>
                    <th class="admin-table-th dark:text-slate-200">@lang('admin.views')</th>
                    <th class="admin-table-th dark:text-slate-200">@lang('admin.date')</th>
                    <th class="admin-table-th dark:text-slate-200">@lang('admin.actions')</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700"
                id="listings-table-body">
                @forelse($listings ?? [] as $listing)
                    @php /** @var \App\Models\Ilan $listing */ @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                        <!-- Checkbox -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            <input type="checkbox"
                                class="row-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                value="{{ $listing->id }}" x-model="selectedIds" @change="updateSelectAll()">
                        </td>

                        <!-- Listing Title -->
                        <td class="px-6 py-4">
                            <div class="flex items-center gap-4">
                                <img class="h-12 w-12 rounded-lg object-cover"
                                    src="{{ $listing->fotograflar?->first() ? Storage::url($listing->fotograflar->first()->dosya_yolu) : asset('images/default-property.jpg') }}"
                                    alt="{{ $listing->baslik }}" loading="lazy">
                                <div>
                                    <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ Str::limit($listing->baslik, 40) }}
                                    </div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">
                                        #{{ $listing->id }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <!-- Category -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-slate-200">
                            {{ $listing->altKategori?->name ?? '—' }}
                        </td>

                        <!-- Status -->
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $durum = \App\Enums\IlanDurumu::tryFrom($listing->yayin_durumu);
                                $durumBadgeClass = match ($durum?->color()) {
                                    'green' => 'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300',
                                    'blue' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
                                    'yellow' => 'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-300',
                                    'slate' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300',
                                    default => 'bg-gray-100 text-gray-700 dark:bg-gray-800 dark:text-gray-300',
                                };
                            @endphp
                            @if($durum)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $durumBadgeClass }}">
                                    {{ $durum->label() }}
                                </span>
                            @else
                                <span class="text-xs text-gray-500">{{ $listing->yayin_durumu }}</span>
                            @endif
                        </td>

                        <!-- Price -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                            @if($listing->fiyat)
                                {{ number_format($listing->fiyat, 0, ',', '.') }}
                                <span class="text-xs text-gray-500">{{ $listing->para_birimi ?? 'TL' }}</span>
                            @else
                                —
                            @endif
                        </td>

                        <!-- Views -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-slate-200">
                            {{ $listing->goruntulenme ?? 0 }}
                        </td>

                        <!-- Date -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                            {{ $listing->created_at?->diffForHumans() ?? '—' }}
                        </td>

                        <!-- Actions -->
                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex gap-2">
                                <a href="{{ route('admin.ilanlar.edit', $listing->id) }}"
                                    class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                    title="@lang('admin.edit')">
                                    ✏️
                                </a>
                                <button onclick="deleteRow({{ $listing->id }})"
                                    class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300 transition-colors"
                                    title="@lang('admin.delete')">
                                    🗑️
                                </button>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
                            @lang('admin.no_listings_found')
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    @if($listings && $listings->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
            {{ $listings->links('pagination::tailwind') }}
        </div>
    @endif
</div>
