@extends('admin.layouts.admin')

@section('title', 'Bağımlılık Kuralları - Property Hub')

@section('content')
    <div x-data="dependencyRulesManager()" class="space-y-6">
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400 mb-1">
                    <a href="{{ route('admin.property-hub.index') }}"
                        class="hover:text-blue-600 transition-all duration-200">Property Hub</a>
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                    <span>Bağımlılık Kuralları</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100">Bağımlılık Kuralları</h1>
                <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    visible_if, required_if, enabled_if kurallarını yönetin
                </p>
            </div>
        </div>

        {{-- Filters --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 p-4 dark:shadow-none">
            <form method="GET" class="flex flex-col sm:flex-row gap-4">
                <div class="flex-1">
                    <input type="text" name="search" value="{{ request('search') }}" placeholder="Özellik ara..."
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                </div>
                <div class="w-full sm:w-48">
                    <select name="listing_type_id"
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                        <option value="">Tüm Yayın Tipleri</option>
                        @foreach ($listingTypes as $type)
                            <option value="{{ $type->id }}"
                                {{ request('listing_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->ad }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="w-full sm:w-48">
                    <select name="main_category_id"
                        class="w-full rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500 transition-all duration-200">
                        <option value="">Tüm Kategoriler</option>
                        @foreach ($categories as $category)
                            <option value="{{ $category->id }}"
                                {{ request('main_category_id') == $category->id ? 'selected' : '' }}>
                                {{ $category->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                    class="px-4 py-2 bg-gray-100 dark:bg-slate-900 text-gray-700 dark:text-slate-300 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-all duration-200">
                    Filtrele
                </button>
                @if (request()->hasAny(['search', 'listing_type_id', 'main_category_id']))
                    <a href="{{ route('admin.property-hub.dependency-rules.index') }}"
                        class="px-4 py-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 transition-all duration-200">
                        Temizle
                    </a>
                @endif
            </form>
        </div>

        {{-- Stats Bar --}}
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800">
                <div class="text-2xl font-bold text-gray-900 dark:text-slate-100">{{ $assignments->total() }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Toplam Kural</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                    {{ $assignments->filter(fn($a) => $a->visible_if_json)->count() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">visible_if</div>
            </div>
            <div
                class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <div class="text-2xl font-bold text-amber-600 dark:text-amber-400">
                    {{ $assignments->filter(fn($a) => $a->required_if_json)->count() }}
                </div>
                <div class="text-sm text-gray-500 dark:text-gray-400">required_if</div>
            </div>
        </div>

        {{-- Rules Table --}}
        <div
            class="bg-white dark:bg-slate-900 rounded-xl shadow-sm border border-gray-200 dark:border-slate-800 overflow-hidden dark:shadow-none">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-slate-800">
                    <thead class="bg-gray-50 dark:bg-slate-900">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                Özellik
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                visible_if
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                required_if
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                enabled_if
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-right text-xs font-medium text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                                İşlemler
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-slate-800">
                        @forelse($assignments as $assignment)
                            <tr class="hover:bg-gray-50 dark:hover:bg-slate-800/50 transition-all duration-200"
                                x-data="ruleRow({{ $assignment->id }}, {{ json_encode($assignment->visible_if_json) }}, {{ json_encode($assignment->required_if_json) }}, {{ json_encode($assignment->enabled_if_json) }})">
                                <td class="px-6 py-4">
                                    <div>
                                        <div class="font-medium text-gray-900 dark:text-slate-100">
                                            {{ $assignment->feature?->name ?? $assignment->field_slug }}
                                        </div>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                            {{ $assignment->field_slug }} · ID: {{ $assignment->id }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <template x-if="!editing">
                                        <div>
                                            <template x-if="visibleIf">
                                                <code
                                                    class="text-xs bg-blue-50 dark:bg-blue-900/20 text-blue-700 dark:text-blue-300 px-2 py-1 rounded break-all"
                                                    x-text="JSON.stringify(visibleIf)"></code>
                                            </template>
                                            <template x-if="!visibleIf">
                                                <span class="text-gray-400 dark:text-gray-600 text-sm">—</span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="editing">
                                        <textarea x-model="visibleIfStr" rows="3"
                                            class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100 focus:border-blue-500 focus:ring-blue-500"
                                            placeholder='{"field": "slug", "op": "eq", "value": true}'></textarea>
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <template x-if="!editing">
                                        <div>
                                            <template x-if="requiredIf">
                                                <code
                                                    class="text-xs bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-300 px-2 py-1 rounded break-all"
                                                    x-text="JSON.stringify(requiredIf)"></code>
                                            </template>
                                            <template x-if="!requiredIf">
                                                <span class="text-gray-400 dark:text-gray-600 text-sm">—</span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="editing">
                                        <textarea x-model="requiredIfStr" rows="3"
                                            class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                            placeholder='{"field": "slug", "op": "eq", "value": true}'></textarea>
                                    </template>
                                </td>
                                <td class="px-6 py-4">
                                    <template x-if="!editing">
                                        <div>
                                            <template x-if="enabledIf">
                                                <code
                                                    class="text-xs bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 px-2 py-1 rounded break-all"
                                                    x-text="JSON.stringify(enabledIf)"></code>
                                            </template>
                                            <template x-if="!enabledIf">
                                                <span class="text-gray-400 dark:text-gray-600 text-sm">—</span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="editing">
                                        <textarea x-model="enabledIfStr" rows="3"
                                            class="w-full text-xs font-mono rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white focus:border-blue-500 focus:ring-blue-500"
                                            placeholder='{"field": "slug", "op": "eq", "value": true}'></textarea>
                                    </template>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <template x-if="!editing">
                                            <button @click="startEdit()"
                                                class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition-all duration-200"
                                                title="Düzenle">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                    stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                                </svg>
                                            </button>
                                        </template>
                                        <template x-if="editing">
                                            <div class="flex items-center gap-1">
                                                <button @click="saveRules()" :disabled="saving"
                                                    class="p-2 text-green-600 hover:text-green-700 dark:text-green-400 dark:hover:text-green-300 transition-all duration-200"
                                                    title="Kaydet">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M5 13l4 4L19 7" />
                                                    </svg>
                                                </button>
                                                <button @click="cancelEdit()"
                                                    class="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-all duration-200"
                                                    title="İptal">
                                                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                        stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round"
                                                            stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </template>
                                        <button @click="clearRules()"
                                            class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition-all duration-200"
                                            title="Tüm kuralları temizle">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                                                stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" fill="none"
                                            viewBox="0 0 24 24" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
                                        </svg>
                                        <p class="text-gray-500 dark:text-gray-400">Bağımlılık kuralı bulunamadı</p>
                                        <p class="mt-1 text-sm text-gray-400 dark:text-gray-500">
                                            Şablon düzenleyicisinden atama kuralları ekleyin
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($assignments->hasPages())
                <div class="px-6 py-4 border-t border-gray-200 dark:border-slate-800">
                    {{ $assignments->withQueryString()->links() }}
                </div>
            @endif
        </div>

        {{-- Notification --}}
        <div x-show="notification" x-transition
            class="fixed bottom-4 right-4 px-4 py-3 rounded-lg shadow-lg text-white text-sm z-50"
            :class="notificationType === 'success' ? 'bg-green-600' : 'bg-red-600'" x-text="notification"
            @click="notification = null">
        </div>
    </div>

    <script>
        function dependencyRulesManager() {
            return {
                notification: null,
                notificationType: 'success',

                showNotification(message, type = 'success') {
                    this.notification = message;
                    this.notificationType = type;
                    setTimeout(() => this.notification = null, 3000);
                }
            };
        }

        function ruleRow(assignmentId, visibleIf, requiredIf, enabledIf) {
            return {
                assignmentId,
                visibleIf,
                requiredIf,
                enabledIf,
                editing: false,
                saving: false,
                visibleIfStr: '',
                requiredIfStr: '',
                enabledIfStr: '',

                startEdit() {
                    this.visibleIfStr = this.visibleIf ? JSON.stringify(this.visibleIf, null, 2) : '';
                    this.requiredIfStr = this.requiredIf ? JSON.stringify(this.requiredIf, null, 2) : '';
                    this.enabledIfStr = this.enabledIf ? JSON.stringify(this.enabledIf, null, 2) : '';
                    this.editing = true;
                },

                cancelEdit() {
                    this.editing = false;
                },

                async saveRules() {
                    // Validate JSON before sending
                    const payload = {};
                    try {
                        payload.visible_if_json = this.visibleIfStr.trim() ? this.visibleIfStr.trim() : null;
                        payload.required_if_json = this.requiredIfStr.trim() ? this.requiredIfStr.trim() : null;
                        payload.enabled_if_json = this.enabledIfStr.trim() ? this.enabledIfStr.trim() : null;

                        // Validate JSON syntax
                        if (payload.visible_if_json) JSON.parse(payload.visible_if_json);
                        if (payload.required_if_json) JSON.parse(payload.required_if_json);
                        if (payload.enabled_if_json) JSON.parse(payload.enabled_if_json);
                    } catch (e) {
                        this.$root.__x_refs?.manager?.showNotification?.('Geçersiz JSON formatı', 'error');
                        const mgr = document.querySelector('[x-data*="dependencyRulesManager"]');
                        if (mgr?.__x) mgr.__x.$data.showNotification('Geçersiz JSON formatı', 'error');
                        return;
                    }

                    this.saving = true;
                    try {
                        const response = await fetch(`/admin/property-hub/dependency-rules/${this.assignmentId}`, {
                            method: 'PUT',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify(payload),
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.visibleIf = data.data?.visible_if_json ?? null;
                            this.requiredIf = data.data?.required_if_json ?? null;
                            this.enabledIf = data.data?.enabled_if_json ?? null;
                            this.editing = false;
                            const mgr = document.querySelector('[x-data*="dependencyRulesManager"]');
                            if (mgr?.__x) mgr.__x.$data.showNotification('Kurallar güncellendi');
                        } else {
                            const mgr = document.querySelector('[x-data*="dependencyRulesManager"]');
                            if (mgr?.__x) mgr.__x.$data.showNotification(data.message || 'Hata oluştu', 'error');
                        }
                    } catch (e) {
                        const mgr = document.querySelector('[x-data*="dependencyRulesManager"]');
                        if (mgr?.__x) mgr.__x.$data.showNotification('Ağ hatası', 'error');
                    } finally {
                        this.saving = false;
                    }
                },

                async clearRules() {
                    if (!confirm('Bu atamanın tüm bağımlılık kurallarını temizlemek istediğinize emin misiniz?'))
                return;

                    try {
                        const response = await fetch(`/admin/property-hub/dependency-rules/${this.assignmentId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                        });

                        const data = await response.json();
                        if (data.success) {
                            this.visibleIf = null;
                            this.requiredIf = null;
                            this.enabledIf = null;
                            const mgr = document.querySelector('[x-data*="dependencyRulesManager"]');
                            if (mgr?.__x) mgr.__x.$data.showNotification('Kurallar temizlendi');
                        }
                    } catch (e) {
                        const mgr = document.querySelector('[x-data*="dependencyRulesManager"]');
                        if (mgr?.__x) mgr.__x.$data.showNotification('Silme hatası', 'error');
                    }
                }
            };
        }
    </script>
@endsection
