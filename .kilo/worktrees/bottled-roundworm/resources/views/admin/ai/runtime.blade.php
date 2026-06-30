@extends('admin.layouts.admin')

@section('title', 'AI Runtime Control')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">⚙️ AI Runtime Control</h2>
        <p class="text-gray-600 dark:text-gray-400 mt-1">Global kill-switch and rollout percentage management</p>
    </div>

    @if(session('success'))
        <div class="bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-200 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    {{-- Current State Overview --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Global AI</p>
                    <p class="text-2xl font-bold {{ $runtimeState['ai_enabled'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $runtimeState['ai_enabled'] ? 'ENABLED' : 'DISABLED' }}
                    </p>
                </div>
                <div class="text-4xl">{{ $runtimeState['ai_enabled'] ? '✅' : '🚫' }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Vision AI</p>
                    <p class="text-2xl font-bold {{ $runtimeState['vision_enabled'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $runtimeState['vision_enabled'] ? 'ENABLED' : 'DISABLED' }}
                    </p>
                </div>
                <div class="text-4xl">{{ $runtimeState['vision_enabled'] ? '👁️' : '🔒' }}</div>
            </div>
        </div>

        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 dark:shadow-none">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-gray-600 dark:text-gray-400">Suggestions</p>
                    <p class="text-2xl font-bold {{ $runtimeState['suggestion_enabled'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $runtimeState['suggestion_enabled'] ? 'ENABLED' : 'DISABLED' }}
                    </p>
                </div>
                <div class="text-4xl">{{ $runtimeState['suggestion_enabled'] ? '💡' : '🔒' }}</div>
            </div>
        </div>
    </div>

    {{-- Control Panel --}}
    <div class="bg-white dark:bg-slate-900 rounded-lg shadow-sm p-6 mb-6 dark:shadow-none">
        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Runtime Controls</h3>

        <form method="POST" action="{{ route('admin.ai.runtime.update') }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Kill Switches --}}
            <div class="border-b border-gray-200 dark:border-slate-800 pb-6 dark:border-slate-700">
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">🔴 Kill Switches</h4>

                <div class="space-y-4">
                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Global AI System</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="ai_enabled" value="1" class="sr-only peer" {{ $runtimeState['ai_enabled'] ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Vision AI</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="vision_enabled" value="1" class="sr-only peer" {{ $runtimeState['vision_enabled'] ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <label class="text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">Smart Suggestions</label>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="suggestion_enabled" value="1" class="sr-only peer" {{ $runtimeState['suggestion_enabled'] ? 'checked' : '' }}>
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-blue-600"></div>
                        </label>
                    </div>
                </div>
            </div>

            {{-- Rollout Percentages --}}
            <div>
                <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4 dark:text-slate-100">📊 Canary Rollout</h4>

                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Vision AI Rollout: <span id="vision-pct-display" class="font-bold text-blue-600 dark:text-blue-400">{{ $runtimeState['rollout']['vision_percentage'] }}%</span>
                        </label>
                        <input type="range" name="vision_percentage" min="0" max="100" value="{{ $runtimeState['rollout']['vision_percentage'] }}"
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                               oninput="document.getElementById('vision-pct-display').textContent = this.value + '%'">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>0%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                            Suggestions Rollout: <span id="suggestion-pct-display" class="font-bold text-blue-600 dark:text-blue-400">{{ $runtimeState['rollout']['suggestion_percentage'] }}%</span>
                        </label>
                        <input type="range" name="suggestion_percentage" min="0" max="100" value="{{ $runtimeState['rollout']['suggestion_percentage'] }}"
                               class="w-full h-2 bg-gray-200 rounded-lg appearance-none cursor-pointer dark:bg-gray-700"
                               oninput="document.getElementById('suggestion-pct-display').textContent = this.value + '%'">
                        <div class="flex justify-between text-xs text-gray-500 dark:text-gray-400 mt-1">
                            <span>0%</span>
                            <span>50%</span>
                            <span>100%</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Submit Button --}}
            <div class="flex justify-end">
                <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors">
                    💾 Save Changes
                </button>
            </div>
        </form>
    </div>

    {{-- Last Shutdown Info --}}
    @if($runtimeState['last_shutdown']['at'])
        <div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg p-4">
            <h4 class="text-sm font-semibold text-yellow-800 dark:text-yellow-200 mb-2">⚠️ Last Emergency Shutdown</h4>
            <div class="text-sm text-yellow-700 dark:text-yellow-300">
                <p><strong>Time:</strong> {{ $runtimeState['last_shutdown']['at'] }}</p>
                <p><strong>Reason:</strong> {{ $runtimeState['last_shutdown']['reason'] }}</p>
                <p><strong>By:</strong> {{ $runtimeState['last_shutdown']['by'] }}</p>
            </div>
        </div>
    @endif
</div>
@endsection
