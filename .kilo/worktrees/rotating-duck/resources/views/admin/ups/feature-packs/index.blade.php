@extends('admin.layouts.admin')

@section('title', 'Feature Packs')

@section('content')
    <div class="container-fluid px-4 py-6">
        {{-- Header --}}
        <div class="flex justify-between items-center mb-6">
            <div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">Feature Packs</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Airbnb, Booking.com gibi önceden tanımlı feature paketleri
                </p>
            </div>
            <button onclick="openCreatePackModal()"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 active:scale-95 transition-all duration-200">
                + Yeni Pack
            </button>
        </div>

        {{-- Packs Grid --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @forelse($packs as $pack)
                <div class="bg-white dark:bg-slate-900 rounded-lg shadow p-6 dark:shadow-none">
                    <div class="flex justify-between items-start mb-4">
                        <div>
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">{{ $pack->name }}</h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400">{{ $pack->slug }}</p>
                        </div>
                        @if ($pack->aktiflik_durumu)
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
                    </div>

                    @if ($pack->description)
                        <p class="text-sm text-gray-600 dark:text-gray-400 mb-4">{{ $pack->description }}</p>
                    @endif

                    <div class="flex items-center gap-4 text-sm text-gray-600 dark:text-gray-400 mb-4">
                        <div class="flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                            </svg>
                            <span>{{ $pack->features_count }} feature</span>
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <button onclick="manageItems({{ $pack->id }})"
                            class="flex-1 px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm dark:bg-slate-900 dark:text-slate-300">
                            Items
                        </button>
                        <button onclick="openApplyModal({{ $pack->id }})"
                            class="flex-1 px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors text-sm">
                            Apply
                        </button>
                        <button onclick="editPack({{ $pack->id }})"
                            class="px-3 py-2 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-200 dark:hover:bg-gray-600 transition-colors text-sm dark:bg-slate-900 dark:text-slate-300">
                            Düzenle
                        </button>
                    </div>
                </div>
            @empty
                <div class="col-span-full bg-white dark:bg-slate-900 rounded-lg shadow p-8 text-center dark:shadow-none">
                    <p class="text-gray-500 dark:text-gray-400">Pack bulunamadı</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Apply Pack Modal --}}
    <div id="applyModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white dark:bg-slate-900 rounded-lg shadow-xl w-full max-w-3xl mx-4 max-h-[90vh] overflow-auto">
            <div class="p-6 border-b border-gray-200 dark:border-slate-800 dark:border-slate-700">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white dark:text-slate-100">Pack Uygula</h3>
            </div>

            <div class="p-6 space-y-4">
                <input type="hidden" id="applyPackId">

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Kategori <span class="text-red-500">*</span>
                    </label>
                    <select id="applyKategoriId" required
                        class="w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        <option value="">Seçin</option>
                        @foreach ($kategoriler as $kat)
                            <option value="{{ $kat->id }}">{{ $kat->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Yayın Tipi <span class="text-red-500">*</span>
                    </label>
                    <div id="yayinTipiCheckboxes"
                        class="space-y-2 border border-gray-300 dark:border-gray-600 rounded-lg p-4">
                        <p class="text-sm text-gray-500">Önce kategori seçin</p>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-slate-200 mb-2 dark:text-slate-300">
                        Mode <span class="text-red-500">*</span>
                    </label>
                    <div class="space-y-2">
                        <label class="flex items-center">
                            <input type="radio" name="applyMode" value="merge" checked
                                class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                <strong>Merge</strong> - Mevcut feature'ları koru, pack'tan eksikleri ekle
                            </span>
                        </label>
                        <label class="flex items-center">
                            <input type="radio" name="applyMode" value="replace"
                                class="text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">
                                <strong>Replace</strong> - Pack dışındaki tüm feature'ları kaldır (destructive)
                            </span>
                        </label>
                    </div>
                </div>

                <div id="replaceConfirmDiv"
                    class="hidden bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg p-4">
                    <label class="flex items-center">
                        <input type="checkbox" id="replaceConfirm"
                            class="rounded border-red-300 text-red-600 focus:ring-red-500">
                        <span class="ml-2 text-sm text-red-700 dark:text-red-300 font-medium">
                            Replace modunda pack dışı feature'ların KALDIRILACAĞını anlıyorum
                        </span>
                    </label>
                </div>

                <div class="flex gap-3">
                    <button type="button" onclick="previewApply()"
                        class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors">
                        🔍 Preview (Diff Göster)
                    </button>
                    <button type="button" onclick="applyPack()"
                        class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                        ✅ Apply
                    </button>
                </div>

                <div id="previewResult" class="hidden space-y-4 pt-4 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                    <h4 class="font-semibold text-gray-900 dark:text-white dark:text-slate-100">Preview Sonucu:</h4>

                    <div class="grid grid-cols-3 gap-4">
                        <div class="bg-green-50 dark:bg-green-900/20 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Eklenecek</div>
                            <div class="text-2xl font-bold text-green-600 dark:text-green-400" id="previewCreateCount">
                                0</div>
                        </div>
                        <div class="bg-gray-50 dark:bg-gray-700 rounded-lg p-4 dark:bg-slate-900">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Skip</div>
                            <div class="text-2xl font-bold text-gray-600 dark:text-gray-400" id="previewSkipCount">0
                            </div>
                        </div>
                        <div class="bg-red-50 dark:bg-red-900/20 rounded-lg p-4">
                            <div class="text-sm text-gray-600 dark:text-gray-400">Kaldırılacak</div>
                            <div class="text-2xl font-bold text-red-600 dark:text-red-400" id="previewRemoveCount">0
                            </div>
                        </div>
                    </div>

                    <div id="previewDetails" class="text-sm space-y-2"></div>
                </div>
            </div>

            <div class="p-6 border-t border-gray-200 dark:border-slate-800 flex justify-end gap-3 dark:border-slate-700">
                <button type="button" onclick="closeApplyModal()"
                    class="px-4 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-slate-200 rounded-lg hover:bg-gray-300 dark:hover:bg-gray-600 transition-colors dark:text-slate-300">
                    İptal
                </button>
            </div>
        </div>
    </div>

    <script>
        // Yayin tipi seçimi için kategori listener
        document.getElementById('applyKategoriId')?.addEventListener('change', async (e) => {
            const kategoriId = e.target.value;
            const container = document.getElementById('yayinTipiCheckboxes');

            if (!kategoriId) {
                container.innerHTML = '<p class="text-sm text-gray-500">Önce kategori seçin</p>';
                return;
            }

            try {
                const response = await fetch(`/api/v1/categories/publication-types/${kategoriId}`);
                const data = await response.json();

                if (data.success && data.data.length > 0) {
                    container.innerHTML = data.data.map(yt => `
                        <label class="flex items-center">
                            <input type="checkbox" name="yayin_tipi_ids[]" value="${yt.id}"
                                class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-700 dark:text-slate-200 dark:text-slate-300">${yt.name}</span>
                        </label>
                    `).join('');
                } else {
                    container.innerHTML =
                        '<p class="text-sm text-gray-500">Bu kategoride yayın tipi bulunamadı</p>';
                }
            } catch (error) {
                container.innerHTML = '<p class="text-sm text-red-500">Yüklenemedi</p>';
            }
        });

        // Replace mode listener
        document.querySelectorAll('input[name="applyMode"]').forEach(radio => {
            radio.addEventListener('change', (e) => {
                const isReplace = e.target.value === 'replace';
                document.getElementById('replaceConfirmDiv').classList.toggle('hidden', !isReplace);
                if (!isReplace) {
                    document.getElementById('replaceConfirm').checked = false;
                }
            });
        });

        function openApplyModal(packId) {
            document.getElementById('applyPackId').value = packId;
            document.getElementById('applyModal').classList.remove('hidden');
            document.getElementById('previewResult').classList.add('hidden');
        }

        function closeApplyModal() {
            document.getElementById('applyModal').classList.add('hidden');
        }

        async function previewApply() {
            const packId = document.getElementById('applyPackId').value;
            const kategoriId = document.getElementById('applyKategoriId').value;
            const mode = document.querySelector('input[name="applyMode"]:checked').value;
            const yayinTipiIds = Array.from(document.querySelectorAll('input[name="yayin_tipi_ids[]"]:checked')).map(
                cb => parseInt(cb.value));

            if (!kategoriId || yayinTipiIds.length === 0) {
                alert('Kategori ve en az 1 yayın tipi seçmelisiniz');
                return;
            }

            try {
                const response = await fetch('/admin/ups/templates/preview', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pack_id: packId,
                        kategori_id: kategoriId,
                        yayin_tipi_ids: yayinTipiIds,
                        mode: mode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    const preview = data.data;
                    document.getElementById('previewCreateCount').textContent = preview.summary.create_count;
                    document.getElementById('previewSkipCount').textContent = preview.summary.skip_count;
                    document.getElementById('previewRemoveCount').textContent = preview.summary.remove_count;

                    let details = '';
                    if (preview.will_create.length > 0) {
                        details += '<div><strong class="text-green-600">Eklenecek:</strong> ' + preview
                            .will_create.map(f => f.slug).join(', ') + '</div>';
                    }
                    if (preview.will_remove.length > 0) {
                        details += '<div><strong class="text-red-600">Kaldırılacak:</strong> ' + preview
                            .will_remove.map(f => f.slug).join(', ') + '</div>';
                    }

                    document.getElementById('previewDetails').innerHTML = details;
                    document.getElementById('previewResult').classList.remove('hidden');
                } else {
                    alert(data.message || 'Preview başarısız');
                }
            } catch (error) {
                alert('İstek başarısız');
            }
        }

        async function applyPack() {
            const mode = document.querySelector('input[name="applyMode"]:checked').value;

            if (mode === 'replace' && !document.getElementById('replaceConfirm').checked) {
                alert('Replace modunda onay checkbox\'unu işaretlemelisiniz');
                return;
            }

            const packId = document.getElementById('applyPackId').value;
            const kategoriId = document.getElementById('applyKategoriId').value;
            const yayinTipiIds = Array.from(document.querySelectorAll('input[name="yayin_tipi_ids[]"]:checked')).map(
                cb => parseInt(cb.value));

            if (!kategoriId || yayinTipiIds.length === 0) {
                alert('Kategori ve en az 1 yayın tipi seçmelisiniz');
                return;
            }

            try {
                const response = await fetch('/admin/ups/feature-packs/apply', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify({
                        pack_id: packId,
                        kategori_id: kategoriId,
                        yayin_tipi_ids: yayinTipiIds,
                        mode: mode
                    })
                });

                const data = await response.json();

                if (data.success) {
                    alert(
                        `Pack uygulandı!\nEklenen: ${data.data.created}\nAtlanan: ${data.data.skipped}\nKaldırılan: ${data.data.removed || 0}`);
                    closeApplyModal();
                } else {
                    alert(data.message || 'Apply başarısız');
                }
            } catch (error) {
                alert('İstek başarısız');
            }
        }

        function openCreatePackModal() {
            alert('Pack create modal henüz implement edilmedi (opsiyonel)');
        }

        function editPack(id) {
            alert('Pack edit modal henüz implement edilmedi (opsiyonel)');
        }

        function manageItems(packId) {
            window.location.href = `/admin/ups/feature-packs/${packId}/items`;
        }
    </script>
@endsection
