@extends('admin.layouts.admin')

@section('title', 'UPS Feature Governance — Özellik Sağlık Matrisi')

@section('content')
    <div class="container-fluid px-4 py-6">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Özellik Sağlık Matrisi</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    UPS — Template, Schema & Assignment bütünlük izleme
                </p>
            </div>
            <form method="POST" action="{{ route('admin.governance.feature-health.generate-proposals') }}">
                @csrf
                <button type="submit"
                    class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-indigo-700 dark:bg-indigo-500 dark:hover:bg-indigo-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                    Matrisi Optimize Et
                </button>
            </form>
        </div>

        {{-- SAB Pipeline Runtime Strip --}}
        @include('admin.governance.partials.runtime-strip')

        {{-- Flash Messages --}}
        @if (session('success'))
            <div class="mb-4 rounded-lg bg-green-50 border border-green-200 p-4 text-sm text-green-800 dark:bg-green-900/30 dark:border-green-800 dark:text-green-300">
                {{ session('success') }}
            </div>
        @endif

        {{-- Summary Cards --}}
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 dark:shadow-none">
                <div class="text-sm text-gray-600 dark:text-gray-400">Arşivlenmiş (Hala Atanmış)</div>
                <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1">
                    {{ $summary['archived_but_assigned'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 dark:shadow-none">
                <div class="text-sm text-gray-600 dark:text-gray-400">Pasif (Hala Atanmış)</div>
                <div class="text-2xl font-bold text-orange-600 dark:text-orange-400 mt-1">
                    {{ $summary['inactive_but_assigned'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 dark:shadow-none">
                <div class="text-sm text-gray-600 dark:text-gray-400">Deprecated (Atanmış)</div>
                <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1">
                    {{ $summary['deprecated_assigned'] }}
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 dark:shadow-none">
                <div class="text-sm text-gray-600 dark:text-gray-400">Orphan (0 Atama)</div>
                <div class="text-2xl font-bold text-gray-600 dark:text-gray-400 mt-1">
                    {{ $summary['orphaned_count'] }}
                </div>
            </div>
        </div>

        {{-- Filters --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-4 mb-6 dark:shadow-none">
            <form method="GET" action="{{ route('admin.governance.feature-health') }}" class="flex gap-4 items-end">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                        Arama (Ad veya Slug)
                    </label>
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Örn: metrekare"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                        Lifecycle
                    </label>
                    <select name="lifecycle"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Tümü</option>
                        <option value="draft" {{ request('lifecycle') === 'draft' ? 'selected' : '' }}>Taslak</option>
                        <option value="active" {{ request('lifecycle') === 'active' ? 'selected' : '' }}>Aktif</option>
                        <option value="deprecated" {{ request('lifecycle') === 'deprecated' ? 'selected' : '' }}>Deprecated
                        </option>
                        <option value="archived" {{ request('lifecycle') === 'archived' ? 'selected' : '' }}>Arşivlendi
                        </option>
                    </select>
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                        Aktiflik Durumu
                    </label>
                    <select name="aktiflik_durumu"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Tümü</option>
                        <option value="1" {{ request('aktiflik_durumu') === '1' ? 'selected' : '' }}>Aktif</option>
                        <option value="0" {{ request('aktiflik_durumu') === '0' ? 'selected' : '' }}>Pasif</option>
                    </select>
                </div>

                <div class="flex-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">
                        Orphan
                    </label>
                    <select name="orphaned"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Tümü</option>
                        <option value="1" {{ request('orphaned') === '1' ? 'selected' : '' }}>Sadece Orphan</option>
                    </select>
                </div>

                <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    Filtrele
                </button>

                @if (request()->hasAny(['lifecycle', 'aktiflik_durumu', 'orphaned']))
                    <a href="{{ route('admin.governance.feature-health') }}"
                        class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors dark:text-slate-300">
                        Temizle
                    </a>
                @endif
            </form>
        </div>

        {{-- Features Table --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow overflow-hidden dark:shadow-none">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-slate-900">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Sağlık
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Feature
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Type
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Status
                        </th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Lifecycle
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Assignments
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Templates
                        </th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-slate-900 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse($features as $feature)
                        @php
                            // Health status logic
                            $healthStatus = 'healthy';
                            $healthLabel = 'Sağlıklı';
                            $healthColor = 'text-green-500 dark:text-green-400';

                            if (in_array($feature['lifecycle'], ['archived', 'deprecated']) && $feature['assignments_count'] > 0) {
                                $healthStatus = 'error';
                                $healthLabel = $feature['lifecycle'] === 'archived' ? 'Arşiv+Atanmış' : 'Deprecated+Atanmış';
                                $healthColor = 'text-red-500 dark:text-red-400';
                            } elseif (!$feature['aktiflik_durumu'] && $feature['assignments_count'] > 0) {
                                $healthStatus = 'warning';
                                $healthLabel = 'Pasif+Atanmış';
                                $healthColor = 'text-amber-500 dark:text-amber-400';
                            } elseif ($feature['is_orphaned']) {
                                $healthStatus = 'warning';
                                $healthLabel = 'Orphan';
                                $healthColor = 'text-amber-500 dark:text-amber-400';
                            }
                        @endphp
                        <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                            {{-- Health indicator --}}
                            <td class="px-4 py-3 text-center">
                                @if ($healthStatus === 'healthy')
                                    <svg class="h-5 w-5 {{ $healthColor }} mx-auto" fill="currentColor" viewBox="0 0 20 20" title="{{ $healthLabel }}">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                    </svg>
                                @elseif ($healthStatus === 'warning')
                                    <svg class="h-5 w-5 {{ $healthColor }} mx-auto" fill="currentColor" viewBox="0 0 20 20" title="{{ $healthLabel }}">
                                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                @else
                                    <svg class="h-5 w-5 {{ $healthColor }} mx-auto" fill="currentColor" viewBox="0 0 20 20" title="{{ $healthLabel }}">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                @endif
                                <span class="text-xs {{ $healthColor }}">{{ $healthLabel }}</span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="text-sm font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                    {{ $feature['name'] }}
                                </div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ $feature['slug'] }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                {{ $feature['type'] }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($feature['aktiflik_durumu'])
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200">
                                        Aktif
                                    </span>
                                @else
                                    <span
                                        class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-slate-200 dark:bg-slate-900">
                                        Pasif
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @php
                                    $badgeClasses = match ($feature['lifecycle']) {
                                        'draft' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
                                        'deprecated'
                                            => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
                                        'archived' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
                                        default => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                    };
                                @endphp
                                <span class="px-2 py-1 text-xs font-medium rounded-full {{ $badgeClasses }}">
                                    {{ $feature['lifecycle_label'] }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                @if ($feature['is_orphaned'])
                                    <span class="text-gray-400">0</span>
                                @else
                                    <span class="font-medium text-gray-900 dark:text-white dark:text-slate-100">
                                        {{ $feature['assignments_count'] }}
                                    </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                {{ $feature['templates_count'] }}
                            </td>
                            <td class="px-4 py-3 text-right text-sm">
                                <a href="{{ route('admin.ups.features.edit', $feature['id']) }}" class="inline-flex items-center px-3 py-1 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:bg-blue-900/30 dark:text-blue-300 dark:hover:bg-blue-900/50 rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Görüntüle
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-4 py-12 text-center">
                                <div class="flex flex-col items-center">
                                    <svg class="h-12 w-12 text-gray-300 dark:text-gray-600 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                                    </svg>
                                    <p class="text-sm font-medium text-gray-500 dark:text-gray-400">
                                        Filtre kriterlerine uyan feature bulunamadı
                                    </p>
                                    <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                        Filtreleri temizleyerek tüm özellikleri görüntüleyebilirsiniz
                                    </p>
                                    @if (request()->hasAny(['lifecycle', 'aktiflik_durumu', 'orphaned', 'search']))
                                        <a href="{{ route('admin.governance.feature-health') }}"
                                            class="mt-3 inline-flex items-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300">
                                            Filtreleri Temizle
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
