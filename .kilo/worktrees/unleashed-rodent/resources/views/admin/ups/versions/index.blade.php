@extends('admin.layouts.admin')

@section('title', 'UPS Versions')

@section('content')
    <div class="container-fluid px-4 py-6">
        {{-- Header --}}
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-gray-900 dark:text-slate-100 dark:text-white">UPS Version Timeline</h1>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Snapshot geçmişi ve rollback yönetimi
            </p>
        </div>

        {{-- Filters --}}
        <div class="mb-6 rounded-lg bg-white p-4 shadow dark:bg-slate-900 dark:shadow-none">
            <form method="GET" class="flex items-end gap-4">
                <div class="flex-1">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        Entity Type
                    </label>
                    <select name="entity_type"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Tümü</option>
                        <option value="Feature" {{ request('entity_type') === 'Feature' ? 'selected' : '' }}>Feature
                        </option>
                        <option value="FeatureAssignment"
                            {{ request('entity_type') === 'FeatureAssignment' ? 'selected' : '' }}>FeatureAssignment
                        </option>
                        <option value="FeaturePack" {{ request('entity_type') === 'FeaturePack' ? 'selected' : '' }}>
                            FeaturePack</option>
                    </select>
                </div>

                <div class="w-48">
                    <label class="mb-1 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        Entity ID
                    </label>
                    <input type="number" name="entity_id" value="{{ request('entity_id') }}"
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                </div>

                <button type="submit"
                    class="rounded-lg bg-blue-600 px-4 py-2 text-white transition-colors hover:bg-blue-700">
                    Filtrele
                </button>

                @if (request()->hasAny(['entity_type', 'entity_id']))
                    <a href="{{ route('ups.versions.index') }}"
                        class="rounded-lg bg-gray-200 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-600">
                        Temizle
                    </a>
                @endif
            </form>
        </div>

        {{-- Version Timeline --}}
        <div class="space-y-4">
            @forelse($versions as $version)
                <div class="rounded-lg bg-white p-6 shadow dark:bg-slate-900 dark:shadow-none">
                    <div class="mb-4 flex items-start justify-between">
                        <div class="flex-1">
                            <div class="mb-2 flex items-center gap-3">
                                <span
                                    class="rounded-full bg-blue-100 px-3 py-1 text-sm font-medium text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                    {{ $version->entity_type }}
                                </span>
                                <span class="text-sm text-gray-600 dark:text-gray-400">
                                    ID: {{ $version->entity_id }}
                                </span>
                                <span class="text-sm font-semibold text-gray-900 dark:text-slate-100 dark:text-white">
                                    Version {{ $version->version }}
                                </span>
                            </div>

                            @if ($version->reason)
                                <p class="mb-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                    📝 {{ $version->reason }}
                                </p>
                            @endif

                            <div class="flex items-center gap-4 text-xs text-gray-500 dark:text-gray-400">
                                <span>
                                    👤 {{ $version->createdBy->name ?? 'System' }}
                                </span>
                                <span>
                                    🕐 {{ $version->created_at->format('d.m.Y H:i') }}
                                </span>
                            </div>
                        </div>

                        <button onclick="openRollbackModal({{ $version->id }})"
                            class="rounded-lg bg-yellow-600 px-4 py-2 text-sm text-white transition-colors hover:bg-yellow-700">
                            ↩️ Rollback
                        </button>
                    </div>

                    {{-- Snapshot Info --}}
                    <div class="border-t border-gray-200 pt-4 dark:border-slate-700 dark:border-slate-800">
                        <details class="group">
                            <summary
                                class="cursor-pointer text-sm font-medium text-gray-700 hover:text-gray-900 dark:text-slate-200 dark:text-slate-300 dark:hover:text-white">
                                Snapshot Bilgisi
                                <span class="ml-2 text-xs text-gray-500">
                                    @if (is_array($version->snapshot_json))
                                        ({{ count($version->snapshot_json) }} key)
                                    @endif
                                </span>
                            </summary>

                            <div class="mt-3 rounded-lg bg-gray-50 p-4 dark:bg-slate-900">
                                <pre class="max-h-64 overflow-auto text-xs text-gray-700 dark:text-slate-200 dark:text-slate-300">{{ json_encode($version->snapshot_json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                            </div>
                        </details>
                    </div>

                    {{-- Events --}}
                    @if ($version->events->isNotEmpty())
                        <div class="mt-4 border-t border-gray-200 pt-4 dark:border-slate-700 dark:border-slate-800">
                            <h4 class="mb-2 text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                Events:</h4>
                            <div class="space-y-1">
                                @foreach ($version->events as $event)
                                    <div class="flex items-center gap-2 text-xs text-gray-600 dark:text-gray-400">
                                        <span
                                            class="rounded bg-gray-200 px-2 py-0.5 dark:bg-gray-700">{{ $event->event_type }}</span>
                                        <span>{{ $event->event_at->format('d.m.Y H:i:s') }}</span>
                                        @if ($event->user)
                                            <span>{{ $event->user->name }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @empty
                <div class="rounded-lg bg-white p-8 text-center shadow dark:bg-slate-900 dark:shadow-none">
                    <p class="text-gray-500 dark:text-gray-400">Version bulunamadı</p>
                </div>
            @endforelse
        </div>

        {{-- Pagination --}}
        @if ($versions->hasPages())
            <div class="mt-6">
                {{ $versions->links() }}
            </div>
        @endif
    </div>

    {{-- Rollback Confirmation Modal --}}
    <div id="rollbackModal" class="fixed inset-0 z-50 flex hidden items-center justify-center bg-black bg-opacity-50">
        <div class="mx-4 w-full max-w-lg rounded-lg bg-white shadow-xl dark:bg-slate-900">
            <div class="border-b border-gray-200 p-6 dark:border-slate-700 dark:border-slate-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-100 dark:text-white">⚠️ Rollback Onayı</h3>
            </div>

            <div class="space-y-4 p-6">
                <input type="hidden" id="rollbackVersionId">

                <div class="rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-900/20">
                    <div class="flex gap-3">
                        <div class="flex-shrink-0">
                            <svg class="h-6 w-6 text-red-600 dark:text-red-400" fill="none" stroke="currentColor"
                                viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                        </div>
                        <div class="flex-1">
                            <h4 class="mb-2 text-sm font-semibold text-red-800 dark:text-red-300">
                                Dikkat: Geri Alınamaz İşlem
                            </h4>
                            <ul class="list-inside list-disc space-y-1 text-sm text-red-700 dark:text-red-400">
                                <li>Mevcut durum bu snapshot ile DEĞİŞTİRİLECEKTİR</li>
                                <li>Rollback sonrası değişiklikler kaybolabilir</li>
                                <li>İşlem öncesi manuel snapshot almanız önerilir</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="mb-2 block text-sm font-medium text-gray-700 dark:text-slate-200 dark:text-slate-300">
                        Rollback Nedeni (zorunlu)
                    </label>
                    <textarea id="rollbackReason" rows="3" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white"
                        placeholder="Örn: Hatalı pack apply geri alınıyor"></textarea>
                </div>

                <div
                    class="rounded-lg border border-yellow-200 bg-yellow-50 p-3 dark:border-yellow-800 dark:bg-yellow-900/20">
                    <label class="flex items-start">
                        <input type="checkbox" id="rollbackConfirm" required
                            class="mt-1 rounded border-yellow-300 text-yellow-600 focus:ring-yellow-500">
                        <span class="ml-2 text-sm text-yellow-800 dark:text-yellow-300">
                            Rollback işleminin <strong>GERİ ALINAMAZ</strong> olduğunu anlıyorum ve devam etmek
                            istiyorum
                        </span>
                    </label>
                </div>
            </div>

            <div class="flex justify-end gap-3 border-t border-gray-200 p-6 dark:border-slate-700 dark:border-slate-800">
                <button type="button" onclick="closeRollbackModal()"
                    class="rounded-lg bg-gray-200 px-4 py-2 text-gray-700 transition-colors hover:bg-gray-300 dark:bg-gray-700 dark:text-slate-200 dark:text-slate-300 dark:hover:bg-gray-600">
                    İptal
                </button>
                <button type="button" onclick="executeRollback()"
                    class="rounded-lg bg-red-600 px-4 py-2 text-white transition-colors hover:bg-red-700">
                    Rollback Yap
                </button>
            </div>
        </div>
    </div>

    <script>
        function openRollbackModal(versionId) {
            document.getElementById('rollbackVersionId').value = versionId;
            document.getElementById('rollbackReason').value = '';
            document.getElementById('rollbackConfirm').checked = false;
            document.getElementById('rollbackModal').classList.remove('hidden');
        }

        function closeRollbackModal() {
            document.getElementById('rollbackModal').classList.add('hidden');
        }

        async function executeRollback() {
            const versionId = document.getElementById('rollbackVersionId').value;
            const reason = document.getElementById('rollbackReason').value.trim();
            const confirmed = document.getElementById('rollbackConfirm').checked;

            if (!reason) {
                alert('Rollback nedeni zorunludur');
                return;
            }

            if (!confirmed) {
                alert('Onay checkbox\'unu işaretlemelisiniz');
                return;
            }

            if (!confirm('SON ONAY: Rollback işlemini başlatmak istediğinize EMİN MİSİNİZ?')) {
                return;
            }

            try {
                const response = await fetch(`/admin/ups/versions/${versionId}/rollback`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        reason: reason
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert('✅ Rollback başarılı!\n\n' + (data.message || 'State geri yüklendi'));
                    window.location.reload();
                } else {
                    alert('❌ Rollback başarısız:\n' + (data.message || 'Bilinmeyen hata'));
                }
            } catch (error) {
                alert('❌ İstek başarısız: ' + error.message);
            }
        }

        // ESC tuşu ile modal kapat
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeRollbackModal();
            }
        });
    </script>
@endsection
