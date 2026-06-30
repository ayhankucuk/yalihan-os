@extends('admin.layouts.admin')

@section('title', 'Template Editor')

@section('content')
<div class="container-fluid px-4 py-6" x-data="templateEditor()">
    {{-- Header --}}
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Template Editor</h1>
            <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                {{ ucfirst(str_replace('_', ' ', $format)) }} - {{ $templateName === 'new' ? 'Yeni Template' : $templateName }}
            </p>
        </div>
        <div class="flex items-center gap-3">
            <button @click="previewTemplate()"
                    class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                <i class="fas fa-eye mr-2"></i>Preview
            </button>
            <button @click="saveTemplate()"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                <i class="fas fa-save mr-2"></i>Kaydet
            </button>
            <a href="{{ route('admin.marketing.templates.index') }}"
               class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-all duration-200 shadow-md hover:shadow-lg dark:shadow-none">
                <i class="fas fa-arrow-left mr-2"></i>Geri
            </a>
        </div>
    </div>

    {{-- Error Message --}}
    <div x-show="error" x-cloak
         class="mb-4 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <p class="text-red-800 dark:text-red-200" x-text="error"></p>
    </div>

    {{-- Success Message --}}
    <div x-show="success" x-cloak
         class="mb-4 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
        <p class="text-green-800 dark:text-green-200" x-text="success"></p>
    </div>

    {{-- Loading Overlay --}}
    <div x-show="loading" x-cloak
         class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-slate-900 rounded-lg p-6">
            <div class="flex items-center gap-3">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                <span class="text-gray-900 dark:text-white dark:text-slate-100">Yükleniyor...</span>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {{-- Left Panel: JSON Editor --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-md p-6 dark:shadow-none">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                Template JSON
            </h2>

            {{-- Template Name Input (for new templates) --}}
            @if($templateName === 'new')
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Template Adı <span class="text-red-500">*</span>
                </label>
                <input type="text" x-model="templateName"
                       class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-900 dark:text-slate-100"
                       placeholder="ornek_template_adi"
                       pattern="[a-z0-9_-]+"
                       required>
                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                    Sadece küçük harf, rakam, tire ve alt çizgi kullanılabilir.
                </p>
            </div>
            @endif
            <textarea x-model="templateJson"
                      class="w-full h-96 p-4 font-mono text-sm border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-50 dark:bg-slate-900 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:text-slate-100"
                      placeholder='{"layout": "centered", "background": {...}, "elements": [...]}'></textarea>
            <div class="mt-4 flex items-center gap-2">
                <button @click="formatJson()"
                        class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 dark:bg-slate-900 dark:text-slate-300">
                    <i class="fas fa-code mr-1"></i>Format
                </button>
                <button @click="validateJson()"
                        class="px-3 py-1 text-sm bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200 dark:bg-slate-900 dark:text-slate-300">
                    <i class="fas fa-check-circle mr-1"></i>Validate
                </button>
            </div>
        </div>

        {{-- Right Panel: Preview --}}
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-md p-6 dark:shadow-none">
            <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                Preview
            </h2>
            <div class="bg-gray-100 dark:bg-slate-900 rounded-lg p-4 flex items-center justify-center min-h-[400px]">
                <div x-show="!previewUrl" class="text-center text-gray-500 dark:text-gray-400">
                    <i class="fas fa-image text-4xl mb-2"></i>
                    <p>Preview için "Preview" butonuna tıklayın</p>
                </div>
                <img x-show="previewUrl" :src="previewUrl" alt="Template Preview"
                     class="max-w-full max-h-[600px] rounded-lg shadow-lg"
                     style="display: none;"
                     x-bind:style="previewUrl ? 'display: block;' : ''">
            </div>

            {{-- Sample Ilan Selector --}}
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                    Örnek İlan Seçin
                </label>
                <select x-model="selectedIlanId"
                        class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent dark:bg-slate-900 dark:text-slate-100">
                    <option value="">Otomatik (İlk Yayınlanmış İlan)</option>
                    @if($sampleIlan)
                    <option value="{{ $sampleIlan->id }}">{{ $sampleIlan->baslik }} (ID: {{ $sampleIlan->id }})</option>
                    @endif
                </select>
            </div>
        </div>
    </div>

    {{-- Template Structure Helper --}}
    <div class="mt-6 bg-white dark:bg-slate-900 rounded-lg shadow-md p-6 dark:shadow-none">
        <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
            Template Yapısı Yardımcısı
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Placeholder'lar</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">@{{baslik}}</code> - İlan başlığı</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">@{{fiyat}}</code> - Fiyat</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">@{{para_birimi}}</code> - Para birimi</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">@{{location.il}}</code> - İl</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">@{{location.ilce}}</code> - İlçe</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">@{{roi.roi_percentage}}</code> - ROI yüzdesi</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">@{{badge.primary_badge}}</code> - Badge</li>
                </ul>
            </div>
            <div>
                <h3 class="font-semibold text-gray-900 dark:text-white mb-2 dark:text-slate-100">Element Tipleri</h3>
                <ul class="text-sm text-gray-600 dark:text-gray-400 space-y-1">
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">text</code> - Metin elementi</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">image</code> - Görsel elementi</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">badge</code> - Badge elementi</li>
                    <li><code class="bg-gray-100 dark:bg-gray-700 px-2 py-1 rounded dark:bg-slate-900">shape</code> - Şekil elementi</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<script>
function templateEditor() {
    return {
        templateJson: @json($template),
        templateName: '{{ $templateName === "new" ? "" : $templateName }}',
        previewUrl: null,
        selectedIlanId: null,
        loading: false,
        error: null,
        success: null,

        init() {
            // Format JSON on init
            this.formatJson();
        },

        formatJson() {
            try {
                const parsed = JSON.parse(this.templateJson);
                this.templateJson = JSON.stringify(parsed, null, 2);
                this.error = null;
            } catch (e) {
                this.error = 'Geçersiz JSON formatı: ' + e.message;
            }
        },

        validateJson() {
            try {
                const parsed = JSON.parse(this.templateJson);

                // Basic validation
                if (!parsed.layout) {
                    this.error = 'Template layout tanımlı değil.';
                    return false;
                }
                if (!parsed.background) {
                    this.error = 'Template background tanımlı değil.';
                    return false;
                }
                if (!parsed.elements || !Array.isArray(parsed.elements)) {
                    this.error = 'Template elements tanımlı değil veya geçersiz.';
                    return false;
                }

                this.success = 'Template yapısı geçerli!';
                setTimeout(() => { this.success = null; }, 3000);
                return true;
            } catch (e) {
                this.error = 'Geçersiz JSON formatı: ' + e.message;
                return false;
            }
        },

        async previewTemplate() {
            if (!this.validateJson()) {
                return;
            }

            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const response = await fetch('{{ route("admin.marketing.templates.preview") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        format: '{{ $format }}',
                        template_data: this.templateJson,
                        ilan_id: this.selectedIlanId || null,
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    this.previewUrl = data.preview_url;
                    this.success = 'Preview başarıyla oluşturuldu!';
                } else {
                    this.error = data.error || 'Preview oluşturulamadı.';
                }
            } catch (e) {
                this.error = 'Preview oluşturulurken hata oluştu: ' + e.message;
            } finally {
                this.loading = false;
            }
        },

        async saveTemplate() {
            if (!this.validateJson()) {
                return;
            }

            this.loading = true;
            this.error = null;
            this.success = null;

            try {
                const isNew = '{{ $templateName }}' === 'new';
                const url = isNew
                    ? '{{ route("admin.marketing.templates.store") }}'
                    : '{{ route("admin.marketing.templates.update") }}';
                const method = isNew ? 'POST' : 'PUT';

                // Validate template name for new templates
                if (isNew && (!this.templateName || !/^[a-z0-9_-]+$/.test(this.templateName))) {
                    this.error = 'Geçerli bir template adı giriniz. (sadece küçük harf, rakam, tire ve alt çizgi)';
                    this.loading = false;
                    return;
                }

                const formData = new FormData();
                formData.append('format', '{{ $format }}');
                formData.append('template_name', isNew ? this.templateName : '{{ $templateName }}');
                formData.append('template_data', this.templateJson);

                const response = await fetch(url, {
                    method: method,
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: formData,
                });

                if (response.ok) {
                    this.success = 'Template başarıyla kaydedildi!';
                    setTimeout(() => {
                        window.location.href = '{{ route("admin.marketing.templates.index") }}';
                    }, 1500);
                } else {
                    const data = await response.json();
                    this.error = data.message || 'Template kaydedilemedi.';
                }
            } catch (e) {
                this.error = 'Template kaydedilirken hata oluştu: ' + e.message;
            } finally {
                this.loading = false;
            }
        },
    };
}
</script>
@endsection

