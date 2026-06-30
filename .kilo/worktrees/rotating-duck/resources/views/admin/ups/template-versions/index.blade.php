@extends('admin.layouts.admin')

@section('title', 'Template Versions - ' . $template->ad)

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">🔄 Version History</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $template->ad }}</p>
                </div>
                <a href="{{ route('admin.ups.templates.show', $template->id) }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                    ← Back to Template
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Alert Messages -->
        @if (session('success'))
            <div class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg p-4 mb-6">
                <div class="flex">
                    <div class="flex-shrink-0">✅</div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-green-800 dark:text-green-200">{{ session('success') }}</h3>
                    </div>
                </div>
            </div>
        @endif

        @if ($versions->count() > 0)
            <!-- Version List -->
            <div class="space-y-4">
                @foreach ($versions as $version)
                    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm border border-gray-200 dark:border-slate-800 p-6 dark:shadow-none dark:border-slate-700">
                        <div class="flex items-start justify-between">
                            <div class="flex-1">
                                <div class="flex items-center gap-3">
                                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">
                                        Version {{ $version->version_number }}
                                    </h3>
                                    @if ($version->version_name)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300">
                                            {{ $version->version_name }}
                                        </span>
                                    @endif
                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                                        {{ $version->getChangeTypeLabel() }}
                                    </span>
                                    @if ($version->aktiflik_durumu)
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300">
                                            ✅ Active
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-slate-200 dark:bg-slate-900">
                                            ⛔ Inactive
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="mt-3 grid grid-cols-1 md:grid-cols-3 gap-4">
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Created By</p>
                                        <p class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $version->createdBy?->name ?? '—' }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Date</p>
                                        <p class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $version->created_at->format('d.m.Y H:i:s') }}</p>
                                    </div>
                                    <div>
                                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">IP Address</p>
                                        <p class="text-xs font-mono text-gray-600 dark:text-gray-400">{{ $version->ip_address ?? '—' }}</p>
                                    </div>
                                </div>

                                @if ($version->change_description)
                                    <div class="mt-3">
                                        <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Description</p>
                                        <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $version->change_description }}</p>
                                    </div>
                                @endif
                            </div>

                            <!-- Actions -->
                            <div class="ml-4 flex flex-col gap-2">
                                <a href="{{ route('admin.ups.template-versions.show', [$template->id, $version->id]) }}" class="px-3 py-2 bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300 rounded-lg text-sm hover:bg-blue-200 dark:hover:bg-blue-900/50 transition-all duration-200">
                                    👁️ View
                                </a>

                                @if ($versions->count() > 1 && $version->version_number !== $versions->first()->version_number)
                                    <form method="POST" action="{{ route('admin.ups.template-versions.rollback', [$template->id, $version->id]) }}" style="display: inline;" onsubmit="return confirm('Rollback to version {{ $version->version_number }}?');">
                                        @csrf
                                        <button type="submit" class="w-full px-3 py-2 bg-orange-100 dark:bg-orange-900/30 text-orange-800 dark:text-orange-300 rounded-lg text-sm hover:bg-orange-200 dark:hover:bg-orange-900/50 transition-all duration-200">
                                            ⏮️ Restore
                                        </button>
                                    </form>
                                @endif

                                @if ($versions->count() > 1)
                                    <form method="POST" action="{{ route('admin.ups.template-versions.destroy', [$template->id, $version->id]) }}" style="display: inline;" onsubmit="return confirm('Delete this version?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="w-full px-3 py-2 bg-red-100 dark:bg-red-900/30 text-red-800 dark:text-red-300 rounded-lg text-sm hover:bg-red-200 dark:hover:bg-red-900/50 transition-all duration-200">
                                            🗑️ Delete
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- Pagination -->
            <div class="mt-8 bg-white dark:bg-slate-900 px-4 py-3 flex items-center justify-between border border-gray-200 dark:border-slate-800 rounded-lg sm:px-6 dark:border-slate-700">
                <div class="flex-1 flex justify-between sm:hidden">
                    {{ $versions->links('pagination::simple-bootstrap-4') }}
                </div>
                <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                    <div>
                        <p class="text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                            Showing <span class="font-medium">{{ $versions->firstItem() ?? 0 }}</span> to <span class="font-medium">{{ $versions->lastItem() ?? 0 }}</span> of <span class="font-medium">{{ $versions->total() }}</span> versions
                        </p>
                    </div>
                    <div>
                        {{ $versions->links() }}
                    </div>
                </div>
            </div>
        @else
            <!-- Empty State -->
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-12 text-center border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <div class="text-6xl mb-4">📋</div>
                <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">No Versions</h3>
                <p class="text-gray-600 dark:text-gray-400">Template versions will be created as changes are made</p>
            </div>
        @endif
    </div>
</div>
@endsection
