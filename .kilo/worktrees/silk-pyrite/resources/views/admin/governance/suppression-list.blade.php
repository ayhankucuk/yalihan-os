@extends('admin.layouts.admin')

@section('title', 'Bastırma Kuralları')

@section('content')
<div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">

    {{-- Header --}}
    <div class="mb-8 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Bastırma Kuralları (Suppression Rules)</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">Bastırılmış kural tetiklenmelerini yönetin</p>
        </div>
        <a href="{{ route('admin.governance.review-queue') }}"
           class="inline-flex items-center gap-2 rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 dark:border-slate-600 dark:bg-slate-800 dark:text-gray-300 dark:hover:bg-slate-700">
            İnceleme Kuyruğu
        </a>
    </div>

    {{-- Flash Messages --}}
    @if(session('success'))
        <div class="mb-6 rounded-lg border border-green-200 bg-green-50 p-4 text-sm text-green-800 dark:border-green-800 dark:bg-green-900/20 dark:text-green-400">
            {{ session('success') }}
        </div>
    @endif

    {{-- Active Suppressions --}}
    <div class="mb-8 overflow-hidden rounded-lg border border-amber-200 bg-white shadow-sm dark:border-amber-800 dark:bg-slate-800">
        <div class="border-b border-amber-200 px-6 py-4 dark:border-amber-800">
            <h2 class="text-lg font-semibold text-amber-900 dark:text-amber-200">Aktif Kurallar ({{ $active->count() }})</h2>
        </div>

        @if($active->isEmpty())
            <div class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                <p class="text-sm">Aktif bastırma kuralı yok.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                    <thead class="bg-gray-50 dark:bg-slate-900/50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kural</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kapsam</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kaynak / Alan</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Sebep</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Bitiş</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Oluşturan</th>
                            <th class="px-4 py-3 text-right text-xs font-medium uppercase text-gray-500 dark:text-gray-400">İşlem</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                        @foreach($active as $suppression)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-700/50">
                                <td class="whitespace-nowrap px-4 py-3 text-sm font-mono font-medium text-gray-900 dark:text-gray-100">
                                    {{ $suppression->rule_key }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm">
                                    @php
                                        $scopeColor = match($suppression->scope) {
                                            'global' => 'red',
                                            'source' => 'yellow',
                                            'domain' => 'blue',
                                            default => 'gray',
                                        };
                                    @endphp
                                    <span class="rounded-full bg-{{ $scopeColor }}-100 px-2 py-0.5 text-xs font-medium text-{{ $scopeColor }}-800 dark:bg-{{ $scopeColor }}-900/30 dark:text-{{ $scopeColor }}-400">
                                        {{ ucfirst($suppression->scope) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ $suppression->source ?? '—' }} / {{ $suppression->domain ?? '—' }}
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-600 dark:text-gray-300">
                                    {{ Str::limit($suppression->reason, 60) }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $suppression->expires_at ? $suppression->expires_at->format('d.m.Y') : 'Süresiz' }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">
                                    {{ $suppression->suppressedByUser?->name ?? '#' . $suppression->suppressed_by }}
                                </td>
                                <td class="whitespace-nowrap px-4 py-3 text-right text-sm">
                                    <form method="POST" action="{{ route('admin.governance.suppressions.remove', $suppression) }}"
                                          onsubmit="return confirm('Bu bastırma kuralını kaldırmak istediğinizden emin misiniz?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="rounded bg-red-600 px-3 py-1 text-xs font-medium text-white hover:bg-red-700 dark:bg-red-700 dark:hover:bg-red-600">
                                            Kaldır
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Expired / Removed Suppressions --}}
    @if($expired->isNotEmpty())
    <div class="overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-800">
        <div class="border-b border-gray-200 px-6 py-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Geçmiş Kurallar (son 25)</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-700">
                <thead class="bg-gray-50 dark:bg-slate-900/50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kural</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kapsam</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Sebep</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase text-gray-500 dark:text-gray-400">Kaldırıldı</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach($expired as $suppression)
                        <tr>
                            <td class="whitespace-nowrap px-4 py-3 text-sm font-mono text-gray-500 dark:text-gray-400">{{ $suppression->rule_key }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ ucfirst($suppression->scope) }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ Str::limit($suppression->reason, 60) }}</td>
                            <td class="whitespace-nowrap px-4 py-3 text-sm text-gray-500 dark:text-gray-400">{{ $suppression->updated_at?->diffForHumans() }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif
</div>
@endsection
