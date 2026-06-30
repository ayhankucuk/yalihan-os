@extends('admin.layouts.admin')

@section('title', 'Yeni Ayar Oluştur - Yalıhan Emlak Pro')

@section('content')
<div class="container mx-auto px-4 py-6" x-data="settingsCreator()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3 dark:text-slate-100">
                <div class="w-12 h-12 bg-gradient-to-r from-blue-600 to-purple-600 rounded-xl flex items-center justify-center shadow-lg">
                    <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                </div>
                Yeni Ayar Oluştur
            </h1>
            <p class="text-gray-600 dark:text-gray-400 mt-2">Hızlı şablon kullanın, grup ekleyin veya manuel oluşturun</p>
        </div>
        <a href="{{ route('admin.ayarlar.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 dark:text-slate-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Geri Dön
        </a>
    </div>

    {{-- Category Tabs --}}
    <div class="mb-6">
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-2 dark:shadow-none dark:border-slate-700">
            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    @click="activeTab = 'single'"
                    :class="activeTab === 'single' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    📝 Tek Ayar
                </button>
                <button
                    type="button"
                    @click="activeTab = 'templates'"
                    :class="activeTab === 'templates' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    🚀 Hızlı Şablonlar
                </button>
                <button
                    type="button"
                    @click="activeTab = 'bulk'"
                    :class="activeTab === 'bulk' ? 'bg-blue-600 text-white' : 'text-gray-700 dark:text-slate-200 hover:bg-gray-100 dark:hover:bg-gray-800'"
                    class="px-4 py-2 rounded-lg font-medium transition-colors duration-200">
                    📦 Toplu Ayar Grupları
                </button>
            </div>
        </div>
    </div>

    {{-- TAB 1: Single Setting (Manuel) --}}
    <div x-show="activeTab === 'single'" x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm dark:shadow-none dark:border-slate-700">
            <div class="border-b border-gray-200 dark:border-slate-800 px-6 py-4 dark:border-slate-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">📝 Manuel Ayar Ekle</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Özel bir ayar oluşturun</p>
            </div>

            <form action="{{ route('admin.ayarlar.store') }}" method="POST" class="p-6">
                @csrf

                <div class="space-y-6">
                    {{-- Ayar Anahtarı --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Ayar Anahtarı <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="text"
                            name="key"
                            x-model="form.key"
                            @input="validateKey"
                            required
                            class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all duration-200 dark:text-slate-100"
                            placeholder="site_name, maintenance_mode">
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            ✅ Sadece küçük harf, rakam ve alt çizgi (snake_case). Örnek: site_name, max_upload_size
                        </p>
                        <div x-show="keyError" class="mt-2 text-sm text-red-600 dark:text-red-400" x-text="keyError"></div>
                    </div>

                    {{-- Veri Tipi --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Veri Tipi <span class="text-red-500">*</span>
                        </label>
                        <select
                            name="type"
                            x-model="form.type"
                            required
                            class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                            <option value="">Veri tipi seçin...</option>
                            <option value="string">📝 String (Metin)</option>
                            <option value="integer">🔢 Integer (Sayı)</option>
                            <option value="boolean">✅ Boolean (Doğru/Yanlış)</option>
                            <option value="json">🗂️ JSON (Yapılandırılmış Veri)</option>
                        </select>
                    </div>

                    {{-- Grup (Autocomplete) --}}
                    <div x-data="{ showGroupSuggestions: false }">
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Grup <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input
                                type="text"
                                name="group"
                                x-model="form.group"
                                @focus="showGroupSuggestions = true"
                                @blur="setTimeout(() => showGroupSuggestions = false, 200)"
                                required
                                class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                                placeholder="general, email, system">

                            {{-- Group Suggestions Dropdown --}}
                            <div x-show="showGroupSuggestions"
                                 class="absolute z-10 w-full mt-1 bg-white dark:bg-slate-900 border border-gray-200 dark:border-slate-800 rounded-lg shadow-lg max-h-60 overflow-y-auto dark:border-slate-700">
                                <template x-for="group in filteredGroups" :key="group.value">
                                    <button
                                        type="button"
                                        @click="form.group = group.value; showGroupSuggestions = false"
                                        class="w-full px-4 py-2 text-left hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors duration-200 flex items-center gap-2">
                                        <span x-text="group.icon"></span>
                                        <div>
                                            <div class="font-medium text-gray-900 dark:text-white dark:text-slate-100" x-text="group.value"></div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400" x-text="group.description"></div>
                                        </div>
                                    </button>
                                </template>
                            </div>
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                            💡 Yazmaya başlayın, öneriler görünecek
                        </p>
                    </div>

                    {{-- Değer (Dynamic by Type) --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                            Değer <span class="text-red-500">*</span>
                        </label>

                        {{-- String/Default --}}
                        <div x-show="form.type === 'string' || form.type === ''">
                            <input
                                type="text"
                                name="value"
                                x-model="form.value"
                                required
                                class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                                placeholder="Yalıhan Emlak">
                        </div>

                        {{-- Integer --}}
                        <div x-show="form.type === 'integer'">
                            <input
                                type="number"
                                name="value"
                                x-model="form.value"
                                required
                                min="0"
                                class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                                placeholder="10">
                            <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                                🔢 Sadece sayı girebilirsiniz
                            </p>
                        </div>

                        {{-- Boolean --}}
                        <div x-show="form.type === 'boolean'">
                            <select
                                name="value"
                                x-model="form.value"
                                class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100">
                                <option value="true">✅ True (Aktif)</option>
                                <option value="false">❌ False (Pasif)</option>
                            </select>
                        </div>

                        {{-- JSON --}}
                        <div x-show="form.type === 'json'">
                            <textarea
                                name="value"
                                x-model="form.value"
                                @input="validateJson"
                                rows="10"
                                class="w-full px-4 py-3 bg-gray-900 text-green-400 font-mono text-sm border border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                                placeholder='{\n  "facebook": "https://...",\n  "instagram": "https://..."\n}'></textarea>
                            <div class="flex items-center justify-between mt-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    🗂️ JSON formatında girin (syntax highlighting aktif)
                                </p>
                                <div x-show="jsonValid === true" class="text-xs text-green-600 dark:text-green-400">
                                    ✅ Valid JSON
                                </div>
                                <div x-show="jsonValid === false" class="text-xs text-red-600 dark:text-red-400">
                                    ❌ Invalid JSON
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Açıklama --}}
                    <div>
                        <label class="block text-sm font-medium text-gray-900 dark:text-white mb-2 dark:text-slate-100">Açıklama</label>
                        <textarea
                            name="description"
                            x-model="form.description"
                            rows="3"
                            maxlength="500"
                            class="w-full px-4 py-3 bg-white dark:bg-slate-900 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 transition-all duration-200 dark:text-slate-100"
                            placeholder="Bu ayarın ne işe yaradığını açıklayın..."></textarea>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400" x-text="`${form.description.length}/500 karakter`"></p>
                    </div>

                    {{-- Preview Section --}}
                    <div x-show="form.key" class="bg-gradient-to-r from-blue-50 to-purple-50 dark:from-blue-900/20 dark:to-purple-900/20 border border-blue-200 dark:border-blue-800 rounded-lg p-6">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-4 flex items-center gap-2 dark:text-slate-100">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                            </svg>
                            Önizleme
                        </h3>
                        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                            <dl class="grid grid-cols-2 gap-4 text-sm">
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400 mb-1">Key:</dt>
                                    <dd class="text-gray-900 dark:text-white font-mono bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900 dark:text-slate-100" x-text="form.key || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400 mb-1">Type:</dt>
                                    <dd class="text-gray-900 dark:text-white dark:text-slate-100" x-text="form.type || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400 mb-1">Group:</dt>
                                    <dd class="text-gray-900 dark:text-white dark:text-slate-100" x-text="form.group || '-'"></dd>
                                </div>
                                <div>
                                    <dt class="font-medium text-gray-600 dark:text-gray-400 mb-1">Value:</dt>
                                    <dd class="text-gray-900 dark:text-white font-mono text-xs break-all bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900 dark:text-slate-100" x-text="form.value || '-'"></dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="flex items-center justify-end gap-3 mt-8 pt-6 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <a href="{{ route('admin.ayarlar.index') }}"
                       class="px-6 py-3 rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-slate-900 text-gray-700 dark:text-slate-200 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200 font-medium dark:text-slate-300">
                        İptal
                    </a>
                    <button type="submit"
                            class="px-6 py-3 rounded-lg bg-gradient-to-r from-blue-600 to-purple-600 text-white hover:from-blue-700 hover:to-purple-700 transition-all duration-200 shadow-md hover:shadow-lg font-medium flex items-center gap-2 dark:shadow-none">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        Ayar Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- TAB 2: Quick Templates --}}
    <div x-show="activeTab === 'templates'" x-cloak>
        <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm p-6 dark:shadow-none dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">🚀 Hızlı Şablonlar</h2>
            <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">Yaygın kullanılan ayarları tek tıkla ekleyin</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @foreach($templates ?? [] as $templateKey => $template)
                <button
                    type="button"
                    @click="applyTemplate('{{ $templateKey }}')"
                    class="bg-white dark:bg-slate-900 rounded-xl border-2 border-gray-200 dark:border-slate-800 p-6 text-center hover:border-blue-500 dark:hover:border-blue-400 hover:shadow-xl transition-all duration-200 group dark:border-slate-700">
                    <div class="text-5xl mb-3 group-hover:scale-110 transition-transform duration-200">
                        {{ $template['icon'] }}
                    </div>
                    <h3 class="font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                        {{ ucfirst(str_replace('_', ' ', $template['key'])) }}
                    </h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 font-mono mb-2">
                        {{ $template['key'] }}
                    </p>
                    <p class="text-xs text-gray-600 dark:text-gray-400">
                        {{ $template['description'] }}
                    </p>
                    <div class="mt-3 inline-flex items-center gap-1 px-2 py-1 bg-blue-100 dark:bg-blue-900 text-blue-700 dark:text-blue-300 rounded-full text-xs">
                        {{ $template['type'] }}
                    </div>
                </button>
                @endforeach
            </div>
        </div>
    </div>

    {{-- TAB 3: Bulk Groups --}}
    <div x-show="activeTab === 'bulk'" x-cloak>
        <div class="space-y-6">
            {{-- Email SMTP Group --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                <div class="bg-gradient-to-r from-orange-500 to-red-500 px-6 py-4">
                    <div class="flex items-center justify-between text-white">
                        <div class="flex items-center gap-3">
                            <div class="text-4xl">📧</div>
                            <div>
                                <h3 class="text-lg font-semibold">Email SMTP Ayarları</h3>
                                <p class="text-sm text-orange-100">5 ayar birden ekle</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="createBulkGroup('email_smtp')"
                            class="px-6 py-2 bg-white text-orange-600 rounded-lg font-semibold hover:bg-orange-50 transition-colors duration-200 shadow-md dark:bg-slate-900 dark:shadow-none">
                            Hepsini Ekle
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">smtp_host</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">smtp_port</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">smtp_username</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">smtp_password</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">smtp_encryption</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- AI Complete Group --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                <div class="bg-gradient-to-r from-blue-500 to-purple-500 px-6 py-4">
                    <div class="flex items-center justify-between text-white">
                        <div class="flex items-center gap-3">
                            <div class="text-4xl">🤖</div>
                            <div>
                                <h3 class="text-lg font-semibold">AI Provider Tam Kurulum</h3>
                                <p class="text-sm text-blue-100">4 ayar birden ekle</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="createBulkGroup('ai_complete')"
                            class="px-6 py-2 bg-white text-blue-600 rounded-lg font-semibold hover:bg-blue-50 transition-colors duration-200 shadow-md dark:bg-slate-900 dark:shadow-none">
                            Hepsini Ekle
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">ai_status</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">ai_provider</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">ollama_url</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">ollama_model</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Security Group --}}
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-gray-200 dark:border-slate-800 shadow-sm overflow-hidden dark:shadow-none dark:border-slate-700">
                <div class="bg-gradient-to-r from-green-500 to-emerald-500 px-6 py-4">
                    <div class="flex items-center justify-between text-white">
                        <div class="flex items-center gap-3">
                            <div class="text-4xl">🔒</div>
                            <div>
                                <h3 class="text-lg font-semibold">Temel Güvenlik Ayarları</h3>
                                <p class="text-sm text-green-100">4 ayar birden ekle</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="createBulkGroup('security_basic')"
                            class="px-6 py-2 bg-white text-green-600 rounded-lg font-semibold hover:bg-green-50 transition-colors duration-200 shadow-md dark:bg-slate-900 dark:shadow-none">
                            Hepsini Ekle
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">force_https</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">csrf_protection</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">max_login_attempts</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-green-500">✓</span>
                            <span class="text-gray-700 dark:text-slate-200 dark:text-slate-300">login_lockout_time</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function settingsCreator() {
    return {
        activeTab: 'templates', // Start with templates tab
        form: {
            key: '',
            value: '',
            type: '',
            group: '',
            description: ''
        },
        keyError: '',
        jsonValid: null,

        groups: [
            { value: 'general', icon: '⚙️', description: 'Genel sistem ayarları' },
            { value: 'contact', icon: '📞', description: 'İletişim bilgileri' },
            { value: 'email', icon: '📧', description: 'Email ve SMTP ayarları' },
            { value: 'social', icon: '📱', description: 'Sosyal medya linkleri' },
            { value: 'seo', icon: '🔍', description: 'SEO ve meta ayarları' },
            { value: 'currency', icon: '💰', description: 'Para birimi ayarları' },
            { value: 'ai', icon: '🤖', description: 'AI provider ayarları' },
            { value: 'system', icon: '🖥️', description: 'Sistem ayarları' },
            { value: 'security', icon: '🔒', description: 'Güvenlik ayarları' },
            { value: 'performance', icon: '⚡', description: 'Performans ayarları' }
        ],

        templates: @json($templates ?? []),

        get filteredGroups() {
            if (!this.form.group) return this.groups;
            return this.groups.filter(g =>
                g.value.toLowerCase().includes(this.form.group.toLowerCase()) ||
                g.description.toLowerCase().includes(this.form.group.toLowerCase())
            );
        },

        validateKey() {
            const key = this.form.key;
            if (!key) {
                this.keyError = '';
                return;
            }

            // snake_case validation
            const snakeCaseRegex = /^[a-z][a-z0-9_]*$/;
            if (!snakeCaseRegex.test(key)) {
                this.keyError = '❌ Sadece küçük harf, rakam ve alt çizgi kullanın (snake_case)';
            } else {
                this.keyError = '';
            }
        },

        validateJson() {
            if (this.form.type !== 'json' || !this.form.value) {
                this.jsonValid = null;
                return;
            }

            try {
                JSON.parse(this.form.value);
                this.jsonValid = true;
            } catch (e) {
                this.jsonValid = false;
            }
        },

        applyTemplate(templateKey) {
            const template = this.templates[templateKey];
            if (template) {
                this.form = {
                    key: template.key,
                    value: template.value,
                    type: template.type,
                    group: template.group,
                    description: template.description
                };

                // Switch to single tab
                this.activeTab = 'single';

                // Scroll to form
                setTimeout(() => {
                    document.querySelector('form').scrollIntoView({ behavior: 'smooth', block: 'start' });
                }, 100);

                // Show toast
                this.showToast('success', `✅ ${template.key} şablonu yüklendi!`);
            }
        },

        async createBulkGroup(groupKey) {
            const groups = {
                email_smtp: {
                    name: 'Email SMTP Ayarları',
                    settings: [
                        { key: 'smtp_host', value: 'smtp.gmail.com', type: 'string', group: 'email', description: 'SMTP sunucu adresi' },
                        { key: 'smtp_port', value: '587', type: 'integer', group: 'email', description: 'SMTP port numarası' },
                        { key: 'smtp_username', value: '', type: 'string', group: 'email', description: 'SMTP kullanıcı adı' },
                        { key: 'smtp_password', value: '', type: 'string', group: 'email', description: 'SMTP şifresi' },
                        { key: 'smtp_encryption', value: 'tls', type: 'string', group: 'email', description: 'Şifreleme tipi' },
                    ]
                },
                ai_complete: {
                    name: 'AI Provider Tam Kurulum',
                    settings: [
                        { key: 'ai_status', value: 'true', type: 'boolean', group: 'ai', description: 'AI özellikleri aktif' },
                        { key: 'ai_provider', value: 'ollama', type: 'string', group: 'ai', description: 'Varsayılan provider' },
                        { key: 'ollama_url', value: 'http://localhost:11434', type: 'string', group: 'ai', description: 'Ollama URL' },
                        { key: 'ollama_model', value: 'gemma2:2b', type: 'string', group: 'ai', description: 'Ollama model' },
                    ]
                },
                security_basic: {
                    name: 'Temel Güvenlik',
                    settings: [
                        { key: 'force_https', value: 'true', type: 'boolean', group: 'security', description: 'HTTPS zorunlu' },
                        { key: 'csrf_protection', value: 'true', type: 'boolean', group: 'security', description: 'CSRF koruması' },
                        { key: 'max_login_attempts', value: '5', type: 'integer', group: 'security', description: 'Max giriş denemesi' },
                        { key: 'login_lockout_time', value: '15', type: 'integer', group: 'security', description: 'Engelleme süresi (dk)' },
                    ]
                }
            };

            const group = groups[groupKey];
            if (!group) return;

            if (!confirm(`${group.name} için ${group.settings.length} ayar oluşturulacak. Devam edilsin mi?`)) {
                return;
            }

            try {
                const response = await fetch('{{ route("admin.ayarlar.bulk-store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({ settings: group.settings })
                });

                const result = await response.json();

                if (result.success) {
                    this.showToast('success', result.message);
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.ayarlar.index") }}';
                    }, 1500);
                } else {
                    this.showToast('error', 'Hata oluştu!');
                }
            } catch (error) {
                console.error('Bulk create error:', error);
                this.showToast('error', 'Ayarlar oluşturulamadı!');
            }
        },

        showToast(type, message) {
            if (window.toast) {
                window.toast(type, message);
            } else {
                alert(message);
            }
        }
    }
}
</script>
@endpush
@endsection
