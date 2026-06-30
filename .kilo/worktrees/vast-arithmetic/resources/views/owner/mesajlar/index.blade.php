@extends('layouts.owner')

@section('title', 'Danışmanla İletişim')

@section('content')
<div class="mb-6">
    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Danışmanla İletişim</h1>
    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">İlanlarınızla ilgilenen danışmanlarla doğrudan mesajlaşın.</p>
</div>

<div class="flex h-[600px] overflow-hidden rounded-2xl border border-gray-200/60 bg-white/80 shadow-lg backdrop-blur-md dark:border-slate-700/50 dark:bg-slate-800/80">
    
    {{-- Sol Panel: Kişiler Listesi --}}
    <div class="flex w-1/3 flex-col border-r border-gray-200 dark:border-slate-700">
        <div class="border-b border-gray-200 p-4 dark:border-slate-700">
            <h2 class="text-lg font-semibold text-gray-900 dark:text-white">Sohbetler</h2>
        </div>
        
        <div class="flex-1 overflow-y-auto">
            @if($danismanlar->isEmpty())
                <div class="p-6 text-center">
                    <div class="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-gray-100 dark:bg-slate-700">
                        <svg class="h-6 w-6 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    </div>
                    <p class="text-sm text-gray-500 dark:text-slate-400">Henüz kimseyle sohbetiniz bulunmuyor.</p>
                </div>
            @else
                <ul class="divide-y divide-gray-200 dark:divide-slate-700">
                    @foreach($danismanlar as $danisman)
                        <li>
                            <a href="{{ route('owner.mesajlar.index', ['danisman_id' => $danisman->id]) }}" 
                               class="group flex items-center gap-3 p-4 transition-all duration-200 hover:bg-gray-50/80 dark:hover:bg-slate-700/40 
                                      {{ $seciliDanismanId == $danisman->id ? 'bg-blue-50/50 border-l-4 border-blue-500 dark:bg-slate-700/60 dark:border-blue-400' : 'border-l-4 border-transparent' }}">
                                <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                                    {{ strtoupper(substr($danisman->name, 0, 1)) }}
                                </div>
                                <div class="flex-1 overflow-hidden">
                                    <h3 class="truncate text-sm font-medium text-gray-900 dark:text-white">{{ $danisman->name }}</h3>
                                    <p class="truncate text-xs text-gray-500 dark:text-slate-400">Sorumlu Danışman</p>
                                </div>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

    {{-- Sağ Panel: Mesajlaşma Ekranı --}}
    <div class="flex flex-1 flex-col bg-gray-50 dark:bg-slate-900">
        @if($seciliDanisman)
            {{-- Sohbet Başlığı --}}
            <div class="flex items-center gap-3 border-b border-gray-200/60 bg-white/50 p-4 backdrop-blur-sm dark:border-slate-700/50 dark:bg-slate-800/50">
                <div class="flex h-10 w-10 items-center justify-center rounded-full bg-blue-100 text-sm font-bold text-blue-700 dark:bg-blue-900 dark:text-blue-300">
                    {{ strtoupper(substr($seciliDanisman->name, 0, 1)) }}
                </div>
                <div>
                    <h2 class="text-sm font-semibold text-gray-900 dark:text-white">{{ $seciliDanisman->name }}</h2>
                    <p class="text-xs text-green-600 dark:text-green-400">Çevrimiçi</p>
                </div>
            </div>

            {{-- Mesajlar Listesi --}}
            <div class="flex-1 overflow-y-auto p-4 space-y-4" id="chat-messages">
                @if($sohbetMesajlari->isEmpty())
                    <div class="flex h-full items-center justify-center">
                        <div class="text-center text-sm text-gray-500 dark:text-slate-400">
                            Danışmanınıza ilk mesajı göndererek görüşmeyi başlatın.
                        </div>
                    </div>
                @else
                    @foreach($sohbetMesajlari as $mesaj)
                        @if($mesaj->gonderen_id === auth()->id())
                            {{-- Giden Mesaj (Benim Mesajım) --}}
                            <div class="flex justify-end">
                                <div class="max-w-[75%] rounded-2xl rounded-tr-none bg-blue-600 px-4 py-2.5 text-sm text-white shadow-md shadow-blue-500/20 transition-transform hover:-translate-y-0.5 dark:bg-blue-500">
                                    {!! nl2br(e($mesaj->icerik)) !!}
                                    <div class="mt-1 text-right text-[10px] text-blue-100">
                                        {{ $mesaj->created_at->format('H:i') }}
                                        @if($mesaj->okundu_mu) <span class="ml-1 text-green-200">✓✓</span> @else <span class="ml-1 text-white">✓</span> @endif
                                    </div>
                                </div>
                            </div>
                        @else
                            {{-- Gelen Mesaj (Karşı Taraf) --}}
                            <div class="flex justify-start">
                                <div class="max-w-[75%] rounded-2xl rounded-tl-none border border-gray-200/60 bg-white px-4 py-2.5 text-sm text-gray-900 shadow-sm transition-transform hover:-translate-y-0.5 dark:border-slate-700/50 dark:bg-slate-800 dark:text-white">
                                    {!! nl2br(e($mesaj->icerik)) !!}
                                    <div class="mt-1 text-[10px] text-gray-400">
                                        {{ $mesaj->created_at->format('H:i') }}
                                    </div>
                                </div>
                            </div>
                        @endif
                    @endforeach
                @endif
            </div>

            {{-- Mesaj Gönderme Formu --}}
            <div class="border-t border-gray-200 bg-white p-4 dark:border-slate-700 dark:bg-slate-800">
                <form action="{{ route('owner.mesajlar.store') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input type="hidden" name="alici_id" value="{{ $seciliDanisman->id }}">
                    <textarea 
                        name="icerik" 
                        rows="1" 
                        class="block w-full resize-none rounded-lg border-0 py-2.5 pl-3 pr-10 text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-blue-600 sm:text-sm sm:leading-6 dark:bg-slate-900 dark:text-white dark:ring-slate-700 dark:focus:ring-blue-500" 
                        placeholder="Bir mesaj yazın..."
                        required
                    ></textarea>
                    <button type="submit" class="inline-flex flex-shrink-0 items-center justify-center rounded-lg bg-blue-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-blue-600">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" /></svg>
                    </button>
                </form>
            </div>
            
            {{-- Sayfa yüklendiğinde en alta scroll etme --}}
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    var chatDiv = document.getElementById('chat-messages');
                    chatDiv.scrollTop = chatDiv.scrollHeight;
                });
            </script>
        @else
            <div class="flex h-full items-center justify-center text-center">
                <div class="max-w-xs">
                    <svg class="mx-auto h-12 w-12 text-gray-400 dark:text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z" /></svg>
                    <h3 class="mt-4 text-sm font-semibold text-gray-900 dark:text-white">Görüşme Seçin</h3>
                    <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">Danışmanlarınızla iletişime geçmek için sol taraftaki menüden bir sohbet seçin.</p>
                </div>
            </div>
        @endif
    </div>

</div>
@endsection
