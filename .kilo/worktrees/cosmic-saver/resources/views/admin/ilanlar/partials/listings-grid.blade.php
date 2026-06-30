{{-- resources/views/admin/ilanlar/partials/listings-grid.blade.php --}}
{{-- Partial rendered by IlanSearchController::filter() for AJAX grid updates --}}
@if($ilanlar && $ilanlar->count() > 0)
    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
        <thead class="bg-gray-50 dark:bg-slate-900">
            <tr>
                <th class="admin-table-th dark:text-slate-200 w-12">
                    <input type="checkbox" id="select-all-listings-ajax"
                        class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
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
        <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
            @foreach($ilanlar as $listing)
                @php /** @var \App\Models\Ilan $listing */ @endphp
                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200">
                    {{-- Checkbox --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        <input type="checkbox"
                            class="row-checkbox rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                            value="{{ $listing->id }}">
                    </td>

                    {{-- Listing Title --}}
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-4">
                            <img class="h-12 w-12 rounded-lg object-cover"
                                src="{{ $listing->fotograflar?->first() ? Storage::url($listing->fotograflar->first()->dosya_yolu) : asset('images/default-property.jpg') }}"
                                alt="{{ $listing->baslik }}" loading="lazy">
                            <div>
                                <div class="text-sm font-medium text-gray-900 dark:text-slate-100">
                                    {{ Str::limit($listing->baslik, 40) }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    #{{ $listing->id }}
                                </div>
                            </div>
                        </div>
                    </td>

                    {{-- Category --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-slate-200">
                        {{ $listing->altKategori?->name ?? '—' }}
                    </td>

                    {{-- Status --}}
                    <td class="px-6 py-4 whitespace-nowrap">
                        @php
                            $rawDurum = $listing->yayin_durumu;
                            $durum = $rawDurum instanceof \App\Enums\IlanDurumu
                                ? $rawDurum
                                : \App\Enums\IlanDurumu::tryFrom($rawDurum);
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

                    {{-- Price --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900 dark:text-slate-100">
                        @if($listing->fiyat)
                            {{ number_format($listing->fiyat, 0, ',', '.') }}
                            <span class="text-xs text-gray-500">{{ $listing->para_birimi ?? 'TL' }}</span>
                        @else
                            —
                        @endif
                    </td>

                    {{-- Views --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-slate-200">
                        {{ $listing->goruntulenme ?? 0 }}
                    </td>

                    {{-- Date --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                        {{ $listing->created_at?->diffForHumans() ?? '—' }}
                    </td>

                    {{-- Actions --}}
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex gap-2">
                            <a href="{{ route('admin.ilanlar.edit', $listing->id) }}"
                                class="text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors"
                                title="@lang('admin.edit')">
                                ✏️
                            </a>
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- Pagination --}}
    @if($ilanlar->hasPages())
        <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800">
            {{ $ilanlar->links('pagination::tailwind') }}
        </div>
    @endif
@else
    <div class="px-6 py-12 text-center text-gray-500 dark:text-gray-400">
        @lang('admin.no_listings_found')
    </div>
@endif
