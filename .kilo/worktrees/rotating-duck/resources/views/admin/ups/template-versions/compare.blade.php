@extends('admin.layouts.admin')

@section('title', 'Compare Versions - ' . $template->ad)

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
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">🔍 Compare Versions</h1>
                    <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $template->ad }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Version Selection -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 mb-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Select Versions to Compare</h2>
            
            <form method="POST" action="{{ route('admin.ups.template-versions.compare', $template->id) }}" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Version 1 (Old)</label>
                    <select name="version1_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-900 dark:text-slate-100">
                        @foreach ($allVersions as $v)
                            <option value="{{ $v->id }}" {{ $v->id == $version1->id ? 'selected' : '' }}>
                                v{{ $v->version_number }} {{ $v->version_name ? "- {$v->version_name}" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-1 dark:text-slate-300">Version 2 (New)</label>
                    <select name="version2_id" required class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-900 dark:text-slate-100">
                        @foreach ($allVersions as $v)
                            <option value="{{ $v->id }}" {{ $v->id == $version2->id ? 'selected' : '' }}>
                                v{{ $v->version_number }} {{ $v->version_name ? "- {$v->version_name}" : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="flex items-end">
                    <button type="submit" class="w-full px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-all duration-200 dark:bg-blue-700 dark:hover:bg-blue-600">
                        🔄 Compare
                    </button>
                </div>
            </form>
        </div>

        <!-- Comparison Results -->
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 border border-gray-200 dark:border-slate-800 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                📊 Comparing v{{ $version1->version_number }} → v{{ $version2->version_number }}
            </h2>

            @if (count($changes) > 0)
                <div class="space-y-4">
                    <p class="text-sm text-gray-600 dark:text-gray-400">
                        <span class="font-medium">{{ count($changes) }} field(s) changed</span>
                    </p>

                    @foreach ($changes as $field => $change)
                        <div class="border border-gray-200 dark:border-slate-800 rounded-lg p-4 dark:border-slate-700">
                            <h3 class="text-sm font-bold text-gray-900 dark:text-white uppercase mb-3 dark:text-slate-100">
                                {{ $field }}
                                <span class="text-xs font-normal text-orange-600 dark:text-orange-400 ml-2">Modified</span>
                            </h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-1">❌ Version {{ $version1->version_number }}</label>
                                    <div class="bg-red-50 dark:bg-red-900/20 rounded p-3 border border-red-200 dark:border-red-800">
                                        <pre class="text-xs text-gray-800 dark:text-slate-200 overflow-auto max-h-48"><code>{{ json_encode($change['old'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    </div>
                                </div>
                                
                                <div>
                                    <label class="text-xs font-medium text-gray-600 dark:text-gray-400 block mb-1">✅ Version {{ $version2->version_number }}</label>
                                    <div class="bg-green-50 dark:bg-green-900/20 rounded p-3 border border-green-200 dark:border-green-800">
                                        <pre class="text-xs text-gray-800 dark:text-slate-200 overflow-auto max-h-48"><code>{{ json_encode($change['new'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</code></pre>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-4 text-center">
                    <p class="text-sm text-blue-800 dark:text-blue-200">✅ No differences found between these versions</p>
                </div>
            @endif
        </div>

        <!-- Actions -->
        <div class="mt-8 flex gap-4">
            <a href="{{ route('admin.ups.template-versions.index', $template->id) }}" class="px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white rounded-lg transition-all duration-200 dark:bg-gray-700 dark:hover:bg-gray-600">
                ← Back to Versions
            </a>
        </div>
    </div>
</div>
@endsection
