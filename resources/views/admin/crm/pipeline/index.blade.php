@extends('admin.layouts.admin')

@section('title', 'Satış Hunisi (Sales Pipeline)')

@section('content')
<div class="space-y-6" x-data="pipelineKanban()">
    {{-- Page Header --}}
    <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 sm:p-8 border border-[#EAE6DF] dark:border-slate-800 shadow-sm transition-all duration-200">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-[#FAF9F6] dark:bg-slate-800 border border-[#C9A84C]/30 flex items-center justify-center shadow-sm text-[#9E7B35] flex-shrink-0">
                    <x-icon name="eslesme" class="w-6 h-6" />
                </div>
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold text-[#0A1628] dark:text-white tracking-tight flex items-center gap-3">
                        Satış Hunisi (Pipeline)
                        <span class="text-xs font-semibold px-2.5 py-0.5 rounded-full bg-[#FAF9F6] text-[#9E7B35] border border-[#C9A84C]/30">Kanban Board</span>
                    </h1>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        Müşteri satış ve alım süreçlerini aşamalara göre sürükle-bırak yöntemiyle yönetin
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <button type="button" @click="refreshPipeline()" :disabled="loading"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl border border-[#EAE6DF] dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-200 text-sm font-semibold hover:border-[#C9A84C]/60 hover:bg-[#FAF9F6] transition-all duration-200 shadow-sm disabled:opacity-50">
                    <x-icon name="ayarlar" class="w-4 h-4 text-[#9E7B35]" />
                    <span x-text="loading ? 'Yenileniyor...' : 'Yenile'">Yenile</span>
                </button>
                <a href="{{ route('admin.crm.dashboard') }}"
                    class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#C9A84C] hover:bg-[#B3933B] text-[#0A1628] text-sm font-semibold shadow-sm hover:shadow transition-all duration-200">
                    <x-icon name="kullanici" class="w-4 h-4" />
                    <span>CRM Komuta Merkezi</span>
                </a>
            </div>
        </div>
    </div>

    {{-- Statistics Bar --}}
    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        @foreach($stages as $key => $label)
            <div class="bg-white dark:bg-slate-900 rounded-2xl p-4 border border-[#EAE6DF] dark:border-slate-800 shadow-sm">
                <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ $label }}</span>
                <div class="mt-2 flex items-baseline gap-2">
                    <span class="text-2xl font-bold text-[#0A1628] dark:text-white">{{ $pipeline[$key]['count'] ?? 0 }}</span>
                    <span class="text-xs font-medium text-slate-400">müşteri</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Kanban Board --}}
    <div class="flex gap-4 overflow-x-auto pb-6 pt-2">
        @foreach($stages as $stageKey => $stageLabel)
            <div class="flex-shrink-0 w-80 flex flex-col">
                {{-- Column Header --}}
                <div class="bg-white dark:bg-slate-900 rounded-t-2xl p-4 border-t-2 border-x border-[#EAE6DF] dark:border-slate-800 shadow-sm flex items-center justify-between border-t-[#C9A84C]">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full 
                            @if($stageKey === 'yeni') bg-indigo-500
                            @elseif($stageKey === 'iletisimde') bg-amber-500
                            @elseif($stageKey === 'randevu') bg-purple-500
                            @elseif($stageKey === 'teklif') bg-orange-500
                            @elseif($stageKey === 'kapanış') bg-emerald-500
                            @endif"></span>
                        <h3 class="text-sm font-bold text-[#0A1628] dark:text-white">{{ $stageLabel }}</h3>
                    </div>
                    <span class="text-xs font-bold px-2 py-0.5 rounded-full bg-[#FAF9F6] text-[#9E7B35] border border-[#C9A84C]/30">
                        {{ $pipeline[$stageKey]['count'] ?? 0 }}
                    </span>
                </div>

                {{-- Drop Zone --}}
                <div class="bg-[#FAF9F6] dark:bg-slate-900/60 rounded-b-2xl p-3 border-b border-x border-[#EAE6DF] dark:border-slate-800 min-h-[580px] space-y-3 transition-colors duration-200"
                    data-stage="{{ $stageKey }}"
                    @drop="handleDrop($event, '{{ $stageKey }}')"
                    @dragover.prevent
                    @dragenter="dragEnter($event)"
                    @dragleave="dragLeave($event)">

                    @forelse($pipeline[$stageKey]['people'] as $person)
                        {{-- Person Card --}}
                        <div class="bg-white dark:bg-slate-900 rounded-xl p-4 border border-[#EAE6DF] dark:border-slate-800 shadow-sm hover:border-[#C9A84C] hover:shadow-md cursor-grab active:cursor-grabbing transition-all duration-200 space-y-3 group"
                            draggable="true"
                            data-person-id="{{ $person->id }}"
                            @dragstart="handleDragStart($event, {{ $person->id }}, '{{ $stageKey }}')"
                            @dragend="handleDragEnd($event)">

                            {{-- Card Header --}}
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex-1">
                                    <a href="{{ route('admin.kisiler.show', $person) }}" class="text-sm font-bold text-[#0A1628] dark:text-white group-hover:text-[#C9A84C] transition-colors line-clamp-1">
                                        {{ $person->tam_ad ?? ($person->ad . ' ' . $person->soyad) }}
                                    </a>
                                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                                        {{ $person->telefon ?? 'Telefon yok' }}
                                    </p>
                                </div>
                                @if($person->skor)
                                    <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-[#FAF9F6] text-[#9E7B35] border border-[#C9A84C]/30">
                                        %{{ $person->skor }} Skor
                                    </span>
                                @endif
                            </div>

                            {{-- Talepler / Bilgiler --}}
                            <div class="flex items-center justify-between text-xs pt-2 border-t border-[#EAE6DF]/60 dark:border-slate-800 text-slate-500">
                                <span class="flex items-center gap-1">
                                    <x-icon name="talep" class="w-3 h-3 text-[#9E7B35]" />
                                    {{ $person->talepler->count() }} Talep
                                </span>
                                <span class="text-slate-400">{{ $person->updated_at ? $person->updated_at->diffForHumans() : '' }}</span>
                            </div>

                            {{-- Quick Actions --}}
                            <div class="flex items-center justify-end gap-2 pt-1">
                                <button type="button" @click="quickNote({{ $person->id }})"
                                    class="text-[11px] font-semibold text-slate-600 dark:text-slate-300 hover:text-[#C9A84C] px-2 py-1 rounded bg-[#FAF9F6] dark:bg-slate-800 border border-[#EAE6DF] dark:border-slate-700 transition-colors">
                                    + Not Ekle
                                </button>
                                <a href="{{ route('admin.kisiler.show', $person) }}"
                                    class="text-[11px] font-bold text-[#9E7B35] hover:underline">
                                    Detay →
                                </a>
                            </div>
                        </div>
                    @empty
                        <div class="py-12 text-center text-xs text-slate-400">
                            Bu aşamada kişi bulunmuyor
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    {{-- Quick Note Modal --}}
    <div x-show="showNoteModal"
        x-cloak
        @click.self="showNoteModal = false"
        class="fixed inset-0 bg-black/40 backdrop-blur-sm flex items-center justify-center z-50 p-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl p-6 max-w-md w-full border border-[#EAE6DF] dark:border-slate-800 shadow-xl space-y-4">
            <div class="flex items-center justify-between pb-3 border-b border-[#EAE6DF] dark:border-slate-800">
                <div class="flex items-center gap-2.5">
                    <div class="w-7 h-7 rounded-lg bg-[#FAF9F6] border border-[#C9A84C]/30 flex items-center justify-center text-[#9E7B35]">
                        <x-icon name="duzenle" class="w-3.5 h-3.5" />
                    </div>
                    <h3 class="text-base font-bold text-[#0A1628] dark:text-white">Müşteri Notu Ekle</h3>
                </div>
                <button type="button" @click="showNoteModal = false" class="text-slate-400 hover:text-slate-600">
                    <x-icon name="kapat" class="w-4 h-4" />
                </button>
            </div>

            <textarea x-model="noteText"
                class="w-full px-4 py-3 rounded-xl border border-[#EAE6DF] dark:border-slate-700 bg-[#FAF9F6] dark:bg-slate-800 text-sm text-[#0A1628] dark:text-slate-100 focus:border-[#C9A84C] focus:bg-white focus:outline-none transition-all duration-200"
                rows="4"
                placeholder="Müşteri görüşme notunuzu yazın..."></textarea>

            <div class="flex gap-3 justify-end pt-2">
                <button type="button" @click="showNoteModal = false"
                    class="px-4 py-2.5 rounded-xl border border-[#EAE6DF] dark:border-slate-700 text-slate-700 dark:text-slate-300 text-xs font-semibold hover:bg-[#FAF9F6] transition-all">
                    İptal
                </button>
                <button type="button" @click="saveNote()"
                    class="px-5 py-2.5 rounded-xl bg-[#C9A84C] hover:bg-[#B3933B] text-[#0A1628] text-xs font-bold shadow-sm transition-all">
                    Notu Kaydet
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
function pipelineKanban() {
    return {
        loading: false,
        draggedPersonId: null,
        draggedFromStage: null,
        showNoteModal: false,
        noteText: '',
        selectedPersonId: null,

        init() {
            console.log('✅ Light Executive Pipeline Kanban initialized');
        },

        handleDragStart(event, personId, fromStage) {
            this.draggedPersonId = personId;
            this.draggedFromStage = fromStage;
            event.dataTransfer.effectAllowed = 'move';
            event.target.style.opacity = '0.5';
        },

        handleDragEnd(event) {
            event.target.style.opacity = '1';
        },

        dragEnter(event) {
            event.currentTarget.classList.add('bg-[#FAF9F6]', 'border-[#C9A84C]');
        },

        dragLeave(event) {
            event.currentTarget.classList.remove('bg-[#FAF9F6]', 'border-[#C9A84C]');
        },

        async handleDrop(event, toStage) {
            event.preventDefault();
            event.currentTarget.classList.remove('bg-[#FAF9F6]', 'border-[#C9A84C]');

            if (!this.draggedPersonId || this.draggedFromStage === toStage) {
                return;
            }

            const cardElement = document.querySelector(`[data-person-id="${this.draggedPersonId}"]`);
            const targetColumn = event.currentTarget;

            if (cardElement) {
                targetColumn.appendChild(cardElement);
            }

            try {
                const response = await fetch(`/admin/crm/pipeline/${this.draggedPersonId}/update-stage`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ stage: toStage })
                });

                const result = await response.json();

                if (!result.success) {
                    throw new Error(result.message || 'Güncelleme başarısız');
                }

                if (window.toast) {
                    window.toast.success('Pipeline aşaması güncellendi');
                }
            } catch (error) {
                console.error('Update failed:', error);
                if (window.toast) {
                    window.toast.error('Güncelleme başarısız: ' + error.message);
                }
                if (cardElement) {
                    const originalColumn = document.querySelector(`[data-stage="${this.draggedFromStage}"]`);
                    if (originalColumn) {
                        originalColumn.appendChild(cardElement);
                    }
                }
            } finally {
                this.draggedPersonId = null;
                this.draggedFromStage = null;
            }
        },

        quickNote(personId) {
            this.selectedPersonId = personId;
            this.noteText = '';
            this.showNoteModal = true;
        },

        async saveNote() {
            if (!this.noteText.trim()) return;

            try {
                const response = await fetch(`/admin/crm/pipeline/${this.selectedPersonId}/quick-note`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ note: this.noteText })
                });

                const result = await response.json();

                if (result.success) {
                    if (window.toast) {
                        window.toast.success('Not kaydedildi');
                    }
                    this.showNoteModal = false;
                } else {
                    throw new Error(result.message);
                }
            } catch (error) {
                if (window.toast) {
                    window.toast.error('Not eklenemedi: ' + error.message);
                }
            }
        },

        async refreshPipeline() {
            this.loading = true;
            await new Promise(resolve => setTimeout(resolve, 300));
            window.location.reload();
        }
    };
}
</script>
@endpush
@endsection
