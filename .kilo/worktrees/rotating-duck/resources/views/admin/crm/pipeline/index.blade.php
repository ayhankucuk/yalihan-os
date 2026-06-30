@extends('layouts.app')

@section('title', 'Sales Pipeline - Kanban')

@section('content')
<div class="container-fluid px-4 py-6" x-data="pipelineKanban()">
    {{-- Header --}}
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2 dark:text-slate-100">
                📊 Sales Pipeline
            </h1>
            <p class="text-gray-600 dark:text-gray-400">
                Sürükle-bırak ile müşteri süreçlerini yönet
            </p>
        </div>
        
        <div class="flex items-center gap-3">
            <button @click="refreshPipeline()" 
                    :disabled="loading"
                    class="px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg 
                           transition-all duration-200 hover:scale-105 disabled:opacity-50">
                <span x-show="!loading">🔄 Yenile</span>
                <span x-show="loading">⏳ Yükleniyor...</span>
            </button>
            <a href="{{ route('admin.crm.dashboard') }}" 
               class="px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg 
                      transition-all duration-200 hover:scale-105">
                📈 Dashboard
            </a>
        </div>
    </div>
    
    {{-- Statistics Bar --}}
    <div class="grid grid-cols-5 gap-4 mb-6">
        @foreach($stages as $key => $label)
        <div class="bg-white dark:bg-slate-900 rounded-lg p-4 border border-gray-200 dark:border-slate-800 dark:border-slate-700">
            <div class="text-sm text-gray-600 dark:text-gray-400 mb-1">{{ $label }}</div>
            <div class="text-2xl font-bold text-gray-900 dark:text-white dark:text-slate-100">
                {{ $pipeline[$key]['count'] }}
            </div>
        </div>
        @endforeach
    </div>
    
    {{-- Kanban Board --}}
    <div class="flex gap-4 overflow-x-auto pb-4">
        @foreach($stages as $stageKey => $stageLabel)
        <div class="flex-shrink-0 w-80">
            {{-- Column Header --}}
            <div class="
                        @if($stageKey === 'yeni') bg-blue-600
                        @elseif($stageKey === 'iletisimde') bg-yellow-600
                        @elseif($stageKey === 'randevu') bg-purple-600
                        @elseif($stageKey === 'teklif') bg-orange-600
                        @elseif($stageKey === 'kapanış') bg-green-600
                        @endif
                        rounded-t-lg p-4 text-white">
                <div class="flex items-center justify-between">
                    <h3 class="font-semibold">{{ $stageLabel }}</h3>
                    <span class="px-2 py-1 bg-white/20 rounded text-sm dark:bg-slate-900/20">
                        {{ $pipeline[$stageKey]['count'] }}
                    </span>
                </div>
            </div>
            
            {{-- Drop Zone --}}
            <div class="bg-gray-100 dark:bg-slate-900 rounded-b-lg p-4 min-h-[600px] space-y-3"
                 data-stage="{{ $stageKey }}"
                 @drop="handleDrop($event, '{{ $stageKey }}')"
                 @dragover.prevent
                 @dragenter="dragEnter($event)"
                 @dragleave="dragLeave($event)">
                
                @foreach($pipeline[$stageKey]['people'] as $person)
                {{-- Person Card --}}
                <div class="bg-white dark:bg-gray-800 rounded-lg p-4 border border-gray-200 dark:border-gray-700 
                            cursor-move hover:shadow-lg transition-all duration-200"
                     draggable="true"
                     data-person-id="{{ $person->id }}"
                     @dragstart="handleDragStart($event, {{ $person->id }}, '{{ $stageKey }}')"
                     @dragend="handleDragEnd($event)">
                    
                    {{-- Header --}}
                    <div class="flex items-start justify-between mb-3">
                        <div class="flex-1">
                            <h4 class="font-semibold text-gray-900 dark:text-white mb-1 dark:text-slate-100">
                                {{ $person->ad_soyad ?? '—' }}
                            </h4>
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                📞 {{ $person->telefon ?? '—' }}
                            </p>
                        </div>
                        
                        {{-- Score Badge --}}
                        @if($person->skor)
                        <span class="px-2 py-1 text-xs font-bold rounded
                                     @if($person->skor >= 80) bg-green-500 text-white
                                     @elseif($person->skor >= 50) bg-yellow-500 text-white
                                     @else bg-gray-400 text-white @endif">
                            {{ $person->skor }}
                        </span>
                        @endif
                    </div>
                    
                    {{-- Latest Talep --}}
                    @if($person->talepler->isNotEmpty())
                    <div class="mb-3 p-2 bg-blue-50 dark:bg-blue-900/20 rounded text-sm">
                        <div class="flex items-center gap-2 text-blue-700 dark:text-blue-300">
                            <span>🏠</span>
                            <span class="truncate">
                                {{ $person->talepler->first()->ilgi_alani ?? 'Talep var' }}
                            </span>
                        </div>
                    </div>
                    @endif
                    
                    {{-- Last Interaction --}}
                    @if($person->latestEtkilesim)
                    <div class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                        💬 {{ $person->latestEtkilesim->tarih->diffForHumans() }}
                    </div>
                    @endif
                    
                    {{-- Quick Actions --}}
                    <div class="flex gap-2 pt-3 border-t border-gray-200 dark:border-slate-800 dark:border-slate-700">
                        <button @click.stop="quickCall({{ $person->id }})" 
                                class="flex-1 px-2 py-1 text-xs bg-blue-500 hover:bg-blue-600 text-white rounded 
                                       transition-all duration-200">
                            📞
                        </button>
                        <button @click.stop="quickNote({{ $person->id }})" 
                                class="flex-1 px-2 py-1 text-xs bg-gray-500 hover:bg-gray-600 text-white rounded 
                                       transition-all duration-200">
                            📝
                        </button>
                        <a href="{{ route('admin.kisiler.show', $person->id) }}" 
                           class="flex-1 px-2 py-1 text-xs bg-purple-500 hover:bg-purple-600 text-white rounded 
                                  text-center transition-all duration-200">
                            👁️
                        </a>
                    </div>
                </div>
                @endforeach
                
                {{-- Empty State --}}
                @if($pipeline[$stageKey]['count'] === 0)
                <div class="text-center py-12 text-gray-400 dark:text-gray-600">
                    <p class="text-4xl mb-2">📭</p>
                    <p class="text-sm">Henüz kişi yok</p>
                </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>
    
    {{-- Quick Note Modal --}}
    <div x-show="showNoteModal" 
         x-cloak
         @click.self="showNoteModal = false"
         class="fixed inset-0 bg-black/50 flex items-center justify-center z-50">
        <div class="bg-white dark:bg-gray-800 rounded-lg p-6 max-w-md w-full mx-4 
                    border border-gray-200 dark:border-gray-700">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4 dark:text-slate-100">
                📝 Hızlı Not Ekle
            </h3>
            
            <textarea x-model="noteText"
                      class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg 
                             bg-white dark:bg-gray-700 text-gray-900 dark:text-white
                             focus:ring-2 focus:ring-blue-500 transition-all duration-200"
                      rows="4"
                      placeholder="Not yazın..."></textarea>
            
            <div class="flex gap-3 mt-4">
                <button @click="saveNote()" 
                        class="flex-1 px-4 py-2 bg-blue-500 hover:bg-blue-600 text-white rounded-lg 
                               transition-all duration-200">
                    Kaydet
                </button>
                <button @click="showNoteModal = false" 
                        class="flex-1 px-4 py-2 bg-gray-500 hover:bg-gray-600 text-white rounded-lg 
                               transition-all duration-200">
                    İptal
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
            console.log('✅ Pipeline Kanban initialized');
        },
        
        // === DRAG & DROP ===
        
        handleDragStart(event, personId, fromStage) {
            this.draggedPersonId = personId;
            this.draggedFromStage = fromStage;
            
            event.dataTransfer.effectAllowed = 'move';
            event.target.style.opacity = '0.5';
            
            console.log('🎯 Dragging:', personId, 'from', fromStage);
        },
        
        handleDragEnd(event) {
            event.target.style.opacity = '1';
        },
        
        dragEnter(event) {
            event.currentTarget.classList.add('bg-blue-100', 'dark:bg-blue-900/30');
        },
        
        dragLeave(event) {
            event.currentTarget.classList.remove('bg-blue-100', 'dark:bg-blue-900/30');
        },
        
        async handleDrop(event, toStage) {
            event.preventDefault();
            event.currentTarget.classList.remove('bg-blue-100', 'dark:bg-blue-900/30');
            
            if (!this.draggedPersonId || this.draggedFromStage === toStage) {
                return;
            }
            
            console.log('📦 Dropping:', this.draggedPersonId, 'to', toStage);
            
            // Optimistic UI: Move card immediately
            const cardElement = document.querySelector(`[data-person-id="${this.draggedPersonId}"]`);
            const targetColumn = event.currentTarget;
            
            if (cardElement) {
                targetColumn.appendChild(cardElement);
            }
            
            // Update server
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
                
                this.showToast('✅ Pipeline güncellendi', 'success');
                
            } catch (error) {
                console.error('❌ Update failed:', error);
                this.showToast('❌ ' + error.message, 'error');
                
                // Rollback: Move card back
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
        
        // === QUICK ACTIONS ===
        
        // ❌ DEPRECATED: quickCall() method (Removed 2026-01-12)
        // Reason: No integration with call system implemented
        // quickCall(personId) {
        //     this.showToast('📞 Arama başlatılıyor...', 'info');
        // },
        
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
                    this.showToast('✅ Not eklendi', 'success');
                    this.showNoteModal = false;
                } else {
                    throw new Error(result.message);
                }
                
            } catch (error) {
                this.showToast('❌ ' + error.message, 'error');
            }
        },
        
        // === UTILITIES ===
        
        async refreshPipeline() {
            this.loading = true;
            await new Promise(resolve => setTimeout(resolve, 500));
            window.location.reload();
        },
        
        showToast(message, type = 'info') {
            const event = new CustomEvent('show-toast', {
                detail: { message, type }
            });
            window.dispatchEvent(event);
            
            const emoji = type === 'success' ? '✅' : type === 'error' ? '❌' : 'ℹ️';
            console.log(`${emoji} ${message}`);
        }
    };
}
</script>
@endpush
@endsection
