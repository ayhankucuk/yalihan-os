@extends('admin.layouts.admin')

@section('title', 'AI Ayarları')

@section('content')
    @vite('resources/js/admin/ai-register.js')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{-- Page Header --}}
        <div class="mb-8">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2 flex items-center gap-3 dark:text-slate-100">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13 10V3L4 14h7v7l9-11h-7z" />
                        </svg>
                        AI Ayarları
                    </h1>
                    <p class="text-gray-600 dark:text-gray-400 mt-1">AI sağlayıcılarını yapılandırın, API key'lerini ve
                        model ayarlarını yönetin</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.ai-settings.analytics') }}"
                        class="inline-flex items-center px-4 py-2.5 text-sm font-medium text-gray-700 dark:text-slate-200 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-purple-500 transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none dark:text-slate-300">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                        </svg>
                        Analytics
                    </a>
                </div>
            </div>
        </div>

        {{-- Success/Error Messages --}}
        @if (session('success'))
            <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border-l-4 border-green-500 rounded-lg shadow-sm dark:shadow-none">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd"
                            d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"
                            clip-rule="evenodd" />
                    </svg>
                    <p class="text-green-800 dark:text-green-200 font-medium">{{ session('success') }}</p>
                </div>
            </div>
        @endif

        {{-- Aktif Provider Durumu --}}
        <div
            class="mb-6 bg-gradient-to-r from-purple-50 to-blue-50 dark:from-purple-900/20 dark:to-blue-900/20 rounded-xl border border-purple-200 dark:border-purple-800 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">Aktif Sağlayıcı</h2>
                    <div id="ai-provider-durumu" role="presentation" aria-live="polite"
                        class="text-sm text-gray-600 dark:text-gray-400">
                        Yükleniyor...
                    </div>
                </div>
                <button id="ai-test-provider"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 shadow-sm hover:shadow-md dark:shadow-none">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    Bağlantıyı Test Et
                </button>
            </div>
        </div>

        {{-- Provider Seçimi --}}
        <div class="mb-6 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Sağlayıcı Seçimi</h2>
            <div class="flex flex-wrap gap-3">
                @php
                    $providers = [
                        'openai' => ['name' => 'OpenAI', 'icon' => '🤖', 'color' => 'green'],
                        'google' => ['name' => 'Google Gemini', 'icon' => '🔍', 'color' => 'blue'],
                        'claude' => ['name' => 'Claude', 'icon' => '🧠', 'color' => 'orange'],
                        'deepseek' => ['name' => 'DeepSeek', 'icon' => '⚡', 'color' => 'purple'],
                        'ollama' => ['name' => 'Ollama (Local)', 'icon' => '🖥️', 'color' => 'indigo'],
                    ];
                @endphp
                @foreach ($providers as $providerKey => $providerInfo)
                    <button data-ai-provider="{{ $providerKey }}"
                        class="provider-btn px-4 py-3 rounded-lg border-2 transition-all duration-200 font-medium text-sm
                            {{ $currentProvider === $providerKey
                                ? 'border-' .
                                    $providerInfo['color'] .
                                    '-500 bg-' .
                                    $providerInfo['color'] .
                                    '-50 dark:bg-' .
                                    $providerInfo['color'] .
                                    '-900/20 text-' .
                                    $providerInfo['color'] .
                                    '-700 dark:text-' .
                                    $providerInfo['color'] .
                                    '-300'
                                : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-300 dark:hover:border-gray-600' }}">
                        <span class="text-lg mr-2">{{ $providerInfo['icon'] }}</span>
                        {{ $providerInfo['name'] }}
                    </button>
                @endforeach
            </div>
        </div>

        {{-- Provider Ayarları (Accordion) --}}
        <div class="space-y-4" x-data="{ activeProvider: '{{ $currentProvider ?? 'ollama' }}' }" x-init="activeProvider = '{{ $currentProvider ?? 'ollama' }}'">
            {{-- OpenAI Ayarları --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700"
                x-show="activeProvider === 'openai'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100">
                <div
                    class="p-6 border-b border-gray-200 dark:border-slate-800 bg-gradient-to-r from-green-50 to-green-100 dark:from-green-900/20 dark:to-green-800/20 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🤖</span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">OpenAI Ayarları</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">GPT-3.5, GPT-4 ve diğer OpenAI modelleri</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="openai_api_key" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            API Key <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="openai_api_key" name="openai_api_key"
                                value="" placeholder="{{ !empty($providerSettings['openai']['api_key']) ? 'sk-**** (Kayıtlı)' : 'sk-...' }}"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">
                            <button type="button" onclick="togglePasswordVisibility('openai_api_key')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">OpenAI API key'inizi buraya girin (sk- ile
                            başlar)</p>
                    </div>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label for="openai_model" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                Model
                            </label>
                            <select id="openai_model" name="openai_model"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                                <option value="gpt-3.5-turbo"
                                    {{ ($providerSettings['openai']['model'] ?? '') === 'gpt-3.5-turbo' ? 'selected' : '' }}>
                                    GPT-3.5 Turbo</option>
                                <option value="gpt-4"
                                    {{ ($providerSettings['openai']['model'] ?? '') === 'gpt-4' ? 'selected' : '' }}>GPT-4
                                </option>
                                <option value="gpt-4-turbo"
                                    {{ ($providerSettings['openai']['model'] ?? '') === 'gpt-4-turbo' ? 'selected' : '' }}>
                                    GPT-4 Turbo</option>
                                <option value="gpt-4o"
                                    {{ ($providerSettings['openai']['model'] ?? '') === 'gpt-4o' ? 'selected' : '' }}>
                                    GPT-4o</option>
                            </select>
                        </div>
                        <div>
                            <label for="openai_organization"
                                class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                                Organization ID <span class="text-xs text-gray-500">(Opsiyonel)</span>
                            </label>
                            <input type="text" id="openai_organization" name="openai_organization"
                                value="{{ $providerSettings['openai']['organization'] ?? '' }}" placeholder="org-..."
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="saveProviderSettings('openai')"
                            class="px-4 py-2.5 bg-green-600 text-white rounded-lg hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition-all duration-200 font-medium">
                            Kaydet
                        </button>
                        <button type="button" onclick="testProvider('openai')"
                            class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 font-medium dark:bg-slate-900 dark:text-slate-300">
                            Test Et
                        </button>
                    </div>
                </div>
            </div>

            {{-- Google Gemini Ayarları --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700"
                x-show="activeProvider === 'google'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100">
                <div
                    class="p-6 border-b border-gray-200 dark:border-slate-800 bg-gradient-to-r from-blue-50 to-blue-100 dark:from-blue-900/20 dark:to-blue-800/20 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🔍</span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Google Gemini Ayarları</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Gemini Pro ve Gemini Pro Vision modelleri
                            </p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="google_api_key" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            API Key <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="google_api_key" name="google_api_key"
                                value="" placeholder="{{ !empty($providerSettings['google']['api_key']) ? 'AIza**** (Kayıtlı)' : 'AIza...' }}"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">
                            <button type="button" onclick="togglePasswordVisibility('google_api_key')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Google AI Studio'dan aldığınız API key'i
                            buraya girin</p>
                    </div>
                    <div>
                        <label for="google_model" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Model
                        </label>
                        <select id="google_model" name="google_model"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                            <option value="gemini-pro"
                                {{ ($providerSettings['google']['model'] ?? '') === 'gemini-pro' ? 'selected' : '' }}>
                                Gemini Pro</option>
                            <option value="gemini-pro-vision"
                                {{ ($providerSettings['google']['model'] ?? '') === 'gemini-pro-vision' ? 'selected' : '' }}>
                                Gemini Pro Vision</option>
                            <option value="gemini-2.5-flash"
                                {{ ($providerSettings['google']['model'] ?? '') === 'gemini-2.5-flash' ? 'selected' : '' }}>
                                Gemini 2.5 Flash</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="saveProviderSettings('google')"
                            class="px-4 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all duration-200 font-medium">
                            Kaydet
                        </button>
                        <button type="button" onclick="testProvider('google')"
                            class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 font-medium dark:bg-slate-900 dark:text-slate-300">
                            Test Et
                        </button>
                    </div>
                </div>
            </div>

            {{-- Claude Ayarları --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700"
                x-show="activeProvider === 'claude'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100">
                <div
                    class="p-6 border-b border-gray-200 dark:border-slate-800 bg-gradient-to-r from-orange-50 to-orange-100 dark:from-orange-900/20 dark:to-orange-800/20 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🧠</span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Claude (Anthropic) Ayarları
                            </h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Claude Sonnet, Opus ve Haiku modelleri</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="claude_api_key" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            API Key <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="claude_api_key" name="claude_api_key"
                                value="" placeholder="{{ !empty($providerSettings['claude']['api_key']) ? 'sk-ant-**** (Kayıtlı)' : 'sk-ant-...' }}"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">
                            <button type="button" onclick="togglePasswordVisibility('claude_api_key')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Anthropic API key'inizi buraya girin
                            (sk-ant- ile başlar)</p>
                    </div>
                    <div>
                        <label for="claude_model" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Model
                        </label>
                        <select id="claude_model" name="claude_model"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-orange-500 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                            <option value="claude-3-sonnet-20240229"
                                {{ ($providerSettings['claude']['model'] ?? '') === 'claude-3-sonnet-20240229' ? 'selected' : '' }}>
                                Claude 3 Sonnet</option>
                            <option value="claude-3-opus-20240229"
                                {{ ($providerSettings['claude']['model'] ?? '') === 'claude-3-opus-20240229' ? 'selected' : '' }}>
                                Claude 3 Opus</option>
                            <option value="claude-3-haiku-20240307"
                                {{ ($providerSettings['claude']['model'] ?? '') === 'claude-3-haiku-20240307' ? 'selected' : '' }}>
                                Claude 3 Haiku</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="saveProviderSettings('claude')"
                            class="px-4 py-2.5 bg-orange-600 text-white rounded-lg hover:bg-orange-700 focus:outline-none focus:ring-2 focus:ring-orange-500 transition-all duration-200 font-medium">
                            Kaydet
                        </button>
                        <button type="button" onclick="testProvider('claude')"
                            class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 font-medium dark:bg-slate-900 dark:text-slate-300">
                            Test Et
                        </button>
                    </div>
                </div>
            </div>

            {{-- DeepSeek Ayarları --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700"
                x-show="activeProvider === 'deepseek'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100">
                <div
                    class="p-6 border-b border-gray-200 dark:border-slate-800 bg-gradient-to-r from-purple-50 to-purple-100 dark:from-purple-900/20 dark:to-purple-800/20 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">⚡</span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">DeepSeek Ayarları</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">DeepSeek V4 (Flash / Pro) modelleri</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="deepseek_api_key"
                            class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            API Key <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" id="deepseek_api_key" name="deepseek_api_key"
                                value="" placeholder="{{ !empty($providerSettings['deepseek']['api_key']) ? 'sk-**** (Kayıtlı)' : 'sk-...' }}"
                                class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">
                            <button type="button" onclick="togglePasswordVisibility('deepseek_api_key')"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">DeepSeek API key'inizi buraya girin</p>
                    </div>
                    <div>
                        <label for="deepseek_model" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Model
                        </label>
                        <select id="deepseek_model" name="deepseek_model"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 dark:text-slate-100">
                            <option value="deepseek-v4-flash"
                                {{ ($providerSettings['deepseek']['model'] ?? '') === 'deepseek-v4-flash' ? 'selected' : '' }}>
                                DeepSeek V4 Flash</option>
                            <option value="deepseek-v4-pro"
                                {{ ($providerSettings['deepseek']['model'] ?? '') === 'deepseek-v4-pro' ? 'selected' : '' }}>
                                DeepSeek V4 Pro</option>
                        </select>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="saveProviderSettings('deepseek')"
                            class="px-4 py-2.5 bg-purple-600 text-white rounded-lg hover:bg-purple-700 focus:outline-none focus:ring-2 focus:ring-purple-500 transition-all duration-200 font-medium">
                            Kaydet
                        </button>
                        <button type="button" onclick="testProvider('deepseek')"
                            class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 font-medium dark:bg-slate-900 dark:text-slate-300">
                            Test Et
                        </button>
                    </div>
                </div>
            </div>

            {{-- Ollama Ayarları (Local) --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700"
                x-show="activeProvider === 'ollama'" x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 transform scale-95"
                x-transition:enter-end="opacity-100 transform scale-100">
                <div
                    class="p-6 border-b border-gray-200 dark:border-slate-800 bg-gradient-to-r from-indigo-50 to-indigo-100 dark:from-indigo-900/20 dark:to-indigo-800/20 dark:border-slate-700">
                    <div class="flex items-center gap-3">
                        <span class="text-2xl">🖥️</span>
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Ollama Ayarları (Local AI)</h3>
                            <p class="text-sm text-gray-600 dark:text-gray-400">Yerel Ollama sunucusu yapılandırması</p>
                        </div>
                    </div>
                </div>
                <div class="p-6 space-y-4">
                    <div>
                        <label for="ollama_url" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Sunucu URL <span class="text-red-500">*</span>
                        </label>
                        <input type="url" id="ollama_url" name="ollama_url"
                            value="{{ $providerSettings['ollama']['url'] ?? 'http://localhost:11434' }}"
                            placeholder="http://localhost:11434 veya http://51.75.64.121:11434"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Ollama sunucunuzun URL'ini girin (örn:
                            http://localhost:11434 veya uzak sunucu IP)</p>
                    </div>
                    <div>
                        <label for="ollama_model" class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Model
                        </label>
                        <input type="text" id="ollama_model" name="ollama_model"
                            value="{{ $providerSettings['ollama']['model'] ?? 'gemma2:2b' }}"
                            placeholder="gemma2:2b, qwen2.5:latest, llama3.2"
                            class="w-full px-4 py-2.5 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-slate-900 text-gray-900 dark:text-white placeholder-gray-400 dark:placeholder-gray-500 focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all duration-200 font-mono text-sm dark:text-slate-100">
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Kullanmak istediğiniz Ollama model adını
                            girin</p>
                    </div>
                    <div class="flex gap-3">
                        <button type="button" onclick="saveProviderSettings('ollama')"
                            class="px-4 py-2.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-all duration-200 font-medium">
                            Kaydet
                        </button>
                        <button type="button" onclick="testProvider('ollama')"
                            class="px-4 py-2.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 focus:outline-none focus:ring-2 focus:ring-gray-500 transition-all duration-200 font-medium dark:bg-slate-900 dark:text-slate-300">
                            Test Et
                        </button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Genel Ayarlar --}}
        <div class="mt-6 bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">Genel Ayarlar</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Dil</label>
                    <div class="flex gap-2">
                        <button data-ai-locale="tr"
                            class="px-4 py-2 rounded-lg border-2 transition-all duration-200 font-medium text-sm
                                {{ ($appLocale ?? 'tr') === 'tr'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                            Türkçe
                        </button>
                        <button data-ai-locale="en"
                            class="px-4 py-2 rounded-lg border-2 transition-all duration-200 font-medium text-sm
                                {{ ($appLocale ?? 'tr') === 'en'
                                    ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                    : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                            English
                        </button>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Para Birimi</label>
                    <div class="flex gap-2 flex-wrap">
                        @foreach (['TRY', 'USD', 'EUR', 'GBP'] as $currency)
                            <button data-ai-currency="{{ $currency }}"
                                class="px-4 py-2 rounded-lg border-2 transition-all duration-200 font-medium text-sm
                                    {{ ($currencyDefault ?? 'TRY') === $currency
                                        ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300'
                                        : 'border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:border-gray-300' }}">
                                {{ $currency }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Provider status yükle
            var statusEl = document.getElementById('ai-provider-durumu');
            if (window.AdminAIService && typeof window.AdminAIService.getProviderStatus === 'function') {
                window.AdminAIService.getProviderStatus().then(function(res) {
                    if (res && res.success) {
                        var d = res.data || res;
                        statusEl.textContent = 'Sağlayıcı: ' + (d.provider || '-') + ' • Model: ' + (d
                            .model || '-');
                    } else {
                        statusEl.textContent = 'Durum alınamadı';
                    }
                }).catch(function() {
                    statusEl.textContent = 'Durum alınamadı';
                });
            }

            // Provider seçimi
            var currentProvider = '{{ $currentProvider ?? 'ollama' }}';
            document.querySelectorAll('[data-ai-provider]').forEach(function(el) {
                var provider = el.getAttribute('data-ai-provider');
                el.addEventListener('click', function() {
                    // Alpine.js state'i güncelle (sayfa yenilenmeden önce)
                    var alpineComponent = document.querySelector('[x-data*="activeProvider"]');
                    if (alpineComponent && window.Alpine) {
                        var alpineData = window.Alpine.$data(alpineComponent);
                        if (alpineData) {
                            alpineData.activeProvider = provider;
                        }
                    }

                    if (window.AdminAIService && provider) {
                        window.AdminAIService.updateProvider(provider).then(function(res) {
                            if (res.success) {
                                window.showToast('Sağlayıcı güncellendi: ' + provider,
                                    'success');
                                // Sayfayı yenile
                                setTimeout(function() {
                                    location.reload();
                                }, 1000);
                            } else {
                                window.showToast('Sağlayıcı güncellenemedi', 'error');
                            }
                        });
                    }
                });
            });

            // Locale ve Currency güncelleme
            document.querySelectorAll('[data-ai-locale]').forEach(function(el) {
                el.addEventListener('click', function() {
                    var locale = el.getAttribute('data-ai-locale');
                    if (window.AdminAIService && window.AdminAIService.updateLocale) {
                        window.AdminAIService.updateLocale(locale).then(function(res) {
                            if (res.success) {
                                window.showToast('Dil güncellendi', 'success');
                                setTimeout(function() {
                                    location.reload();
                                }, 500);
                            }
                        });
                    }
                });
            });

            document.querySelectorAll('[data-ai-currency]').forEach(function(el) {
                el.addEventListener('click', function() {
                    var currency = el.getAttribute('data-ai-currency');
                    if (window.AdminAIService && window.AdminAIService.updateCurrency) {
                        window.AdminAIService.updateCurrency(currency).then(function(res) {
                            if (res.success) {
                                window.showToast('Para birimi güncellendi', 'success');
                                setTimeout(function() {
                                    location.reload();
                                }, 500);
                            }
                        });
                    }
                });
            });
        });

        // Provider ayarlarını kaydet
        function saveProviderSettings(provider) {
            var data = {};
            var promises = [];

            if (provider === 'openai') {
                data = {
                    provider: 'openai',
                    api_key: document.getElementById('openai_api_key').value,
                    model: document.getElementById('openai_model').value,
                    organization: document.getElementById('openai_organization').value || null,
                };
            } else if (provider === 'google') {
                data = {
                    provider: 'google',
                    api_key: document.getElementById('google_api_key').value,
                    model: document.getElementById('google_model').value,
                };
            } else if (provider === 'claude') {
                data = {
                    provider: 'claude',
                    api_key: document.getElementById('claude_api_key').value,
                    model: document.getElementById('claude_model').value,
                };
            } else if (provider === 'deepseek') {
                data = {
                    provider: 'deepseek',
                    api_key: document.getElementById('deepseek_api_key').value,
                    model: document.getElementById('deepseek_model').value,
                };
            } else if (provider === 'ollama') {
                data = {
                    provider: 'ollama',
                    url: document.getElementById('ollama_url').value,
                    model: document.getElementById('ollama_model').value,
                };
            }

            // API Key kaydet
            if (data.api_key) {
                promises.push(
                    fetch('{{ route("admin.ai-settings.update-api-key") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            provider: provider,
                            api_key: data.api_key
                        })
                    }).then(res => res.json()).then(function(res) {
                        if (res.success) {
                            console.log('API Key kaydedildi:', provider);
                            return {
                                success: true,
                                type: 'api_key'
                            };
                        } else {
                            throw new Error(res.message || 'API Key kaydedilemedi');
                        }
                    })
                );
            }

            // Ollama URL kaydet
            if (data.url) {
                promises.push(
                    fetch('{{ route("admin.ai-settings.update-ollama-url") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            url: data.url
                        })
                    }).then(res => res.json()).then(function(res) {
                        if (res.success) {
                            console.log('Ollama URL kaydedildi:', res.data?.url);
                            return {
                                success: true,
                                type: 'url',
                                url: res.data?.url
                            };
                        } else {
                            throw new Error(res.message || 'Ollama URL kaydedilemedi');
                        }
                    })
                );
            }

            // Model kaydet
            if (data.model) {
                promises.push(
                    fetch('{{ route("admin.ai-settings.update-provider-model") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            provider: provider,
                            model: data.model
                        })
                    }).then(res => res.json()).then(function(res) {
                        if (res.success) {
                            console.log('Model kaydedildi:', provider, data.model);
                            return {
                                success: true,
                                type: 'model'
                            };
                        } else {
                            throw new Error(res.message || 'Model kaydedilemedi');
                        }
                    })
                );
            }

            // OpenAI Organization kaydet
            if (provider === 'openai' && data.organization !== undefined) {
                promises.push(
                    fetch('{{ route("admin.ai-settings.update-openai-organization") }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({
                            organization: data.organization
                        })
                    }).then(res => res.json()).then(function(res) {
                        if (res.success) {
                            console.log('OpenAI Organization kaydedildi');
                            return {
                                success: true,
                                type: 'organization'
                            };
                        } else {
                            throw new Error(res.message || 'Organization kaydedilemedi');
                        }
                    })
                );
            }

            // Tüm kaydetme işlemlerini bekle
            Promise.all(promises).then(function(results) {
                console.log('Tüm ayarlar başarıyla kaydedildi:', results);
                window.showToast('Tüm ayarlar başarıyla kaydedildi', 'success');
                // Sayfayı yenile
                setTimeout(function() {
                    location.reload();
                }, 800);
            }).catch(function(err) {
                console.error('Ayarlar kaydedilirken hata oluştu:', err);
                window.showToast('Ayarlar kaydedilirken hata oluştu: ' + (err.message || 'Bilinmeyen hata'),
                    'error');
            });
        }

        // Provider test et
        function testProvider(provider) {
            window.showToast('Test başlatılıyor...', 'info');

            var testData = {
                provider: provider
            };

            if (provider === 'openai') {
                testData.api_key = document.getElementById('openai_api_key').value;
                testData.model = document.getElementById('openai_model').value;
            } else if (provider === 'google') {
                testData.api_key = document.getElementById('google_api_key').value;
                testData.model = document.getElementById('google_model').value;
            } else if (provider === 'claude') {
                testData.api_key = document.getElementById('claude_api_key').value;
                testData.model = document.getElementById('claude_model').value;
            } else if (provider === 'deepseek') {
                testData.api_key = document.getElementById('deepseek_api_key').value;
                testData.model = document.getElementById('deepseek_model').value;
            } else if (provider === 'ollama') {
                testData.url = document.getElementById('ollama_url').value;
                testData.model = document.getElementById('ollama_model').value;
            }

            fetch('/admin/ai-settings/test-provider', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(testData)
            }).then(res => res.json()).then(function(res) {
                if (res.success) {
                    window.showToast('✅ Bağlantı başarılı!', 'success');
                } else {
                    window.showToast('❌ Bağlantı başarısız: ' + (res.message || 'Bilinmeyen hata'), 'error');
                }
            }).catch(function(err) {
                console.error('Test error:', err);
                window.showToast('❌ Test sırasında hata oluştu', 'error');
            });
        }

        // Password visibility toggle
        function togglePasswordVisibility(inputId) {
            var input = document.getElementById(inputId);
            var button = input.nextElementSibling;
            var svg = button.querySelector('svg');

            if (input.type === 'password') {
                input.type = 'text';
                svg.innerHTML =
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21" />';
            } else {
                input.type = 'password';
                svg.innerHTML =
                    '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            }
        }
    </script>
@endsection
