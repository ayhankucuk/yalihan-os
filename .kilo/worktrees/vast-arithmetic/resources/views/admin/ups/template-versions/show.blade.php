@extends('admin.layouts.admin')

@section('title', 'Version Detail - ' . $template->ad)

@section('content')
<div class="min-h-screen bg-gray-50 dark:bg-slate-900">
    <!-- Header -->
    <div class="bg-white dark:bg-slate-900 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.ups.template-versions.index', $template->id) }}" class="text-blue-600 dark:text-blue-400 hover:text-blue-900 dark:hover:text-blue-300">
                    ← Versions
                </a>
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Version {{ $version->version_number }}</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $version->created_at->format('d.m.Y H:i:s') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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

        <!-- Version Metadata -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 mb-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">📋 Version Info</h2>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Template</label>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $template->ad }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Change Type</label>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-800 dark:text-purple-300">
                        {{ $version->getChangeTypeLabel() }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Status</label>
                    <span class="inline-flex items-center px-4 py-2 rounded-full text-sm font-medium" :class="'{{ $version->aktiflik_durumu ? 'bg-green-100 dark:bg-green-900/30 text-green-800 dark:text-green-300' : 'bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-300' }}' dark:text-slate-200">
                        {{ $version->aktiflik_durumu ? '✅ Active' : '⛔ Inactive' }}
                    </span>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Created By</label>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $version->createdBy?->name ?? '—' }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Created At</label>
                    <p class="text-sm text-gray-900 dark:text-white dark:text-slate-100">{{ $version->created_at->format('d.m.Y H:i:s') }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">IP Address</label>
                    <p class="text-xs font-mono text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $version->ip_address ?? '—' }}</p>
                </div>
            </div>

            @if ($version->version_name)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Version Name</label>
                    <p class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $version->version_name }}</p>
                </div>
            @endif

            @if ($version->change_description)
                <div class="mt-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <label class="block text-sm font-medium text-gray-600 dark:text-gray-400 mb-1">Description</label>
                    <p class="text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ $version->change_description }}</p>
                </div>
            @endif
        </div>

        <!-- Changes from Previous Version -->
        @if ($changes && count($changes) > 0)
            <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 mb-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🔄 Changes from Previous</h2>
                
                <div class="space-y-4">
                    @foreach ($changes as $field => $change)
                        <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 dark:border-slate-700">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase mb-3 dark:text-slate-100">{{ $field }}</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-1">❌ Before</label>
                                    <div class="bg-red-50 dark:bg-red-900/20 rounded p-3 border border-red-200 dark:border-red-800">
                                        <pre class="text-xs text-gray-800 dark:text-slate-200 overflow-auto max-h-32"><code>{{ json_encode($change['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-1">✅ After</label>
                                    <div class="bg-green-50 dark:bg-green-900/20 rounded p-3 border border-green-200 dark:border-green-800">
                                        <pre class="text-xs text-gray-800 dark:text-slate-200 overflow-auto max-h-32"><code>{{ json_encode($change['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @else
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 mb-6">
                <p class="text-sm text-blue-800 dark:text-blue-200">ℹ️ This is the first version or no previous version available for comparison.</p>
            </div>
        @endif

        <!-- Full Snapshot -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">📸 Full Snapshot</h2>
            
            <div class="bg-gray-50 dark:bg-gray-700 rounded p-4 dark:bg-slate-900">
                <pre class="text-xs text-gray-800 dark:text-slate-200 overflow-auto max-h-96"><code>{{ json_encode($version->snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
            </div>
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('admin.ups.template-versions.index', $template->id) }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                ← Back to Versions
            </a>

            @if ($version->version_number !== 1)
                <form method="POST" action="{{ route('admin.ups.template-versions.rollback', [$template->id, $version->id]) }}" style="display: inline;" onsubmit="return confirm('Restore template to this version?');">
                    @csrf
                    <button type="submit" class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white rounded-lg transition-all duration-200 dark:bg-orange-700 dark:hover:bg-orange-600">
                        ⏮️ Restore This Version
                    </button>
                </form>
            @endif
        </div>
    </div>
</div>
@endsection
