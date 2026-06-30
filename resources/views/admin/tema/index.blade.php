@extends('layouts.admin')

@section('title', 'Frontend Tema Seçici')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">

    {{-- Başlık --}}
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Frontend Tema</h1>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Aktif: <strong>{{ config("themes.{$aktif_tema}.label", $aktif_tema) }}</strong>
                &nbsp;·&nbsp; Kayıt sonrası anında yayına girer
            </p>
        </div>
        <a href="{{ route('admin.ayarlar.index') }}"
           class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 dark:bg-gray-800 dark:border-gray-700 dark:text-gray-300 transition-colors">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/></svg>
            Ayarlara Dön
        </a>
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="flex items-center gap-3 p-4 mb-6 bg-green-50 border border-green-200 rounded-xl dark:bg-green-900/20 dark:border-green-800">
        <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <span class="text-sm font-medium text-green-700 dark:text-green-300">{{ session('success') }}</span>
    </div>
    @endif
    @if(session('error'))
    <div class="flex items-center gap-3 p-4 mb-6 bg-red-50 border border-red-200 rounded-xl dark:bg-red-900/20 dark:border-red-800">
        <svg class="w-5 h-5 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z"/></svg>
        <span class="text-sm font-medium text-red-700 dark:text-red-300">{{ session('error') }}</span>
    </div>
    @endif

    {{-- Form --}}
    <form action="{{ route('admin.tema.store') }}" method="POST" id="theme-form">
        @csrf
        <input type="hidden" name="theme" id="selected-theme" value="{{ $aktif_tema }}">

        {{-- Tema Kartları --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            @foreach($themes as $slug => $theme)
            @php $isActive = ($slug === $aktif_tema); @endphp
            <div class="theme-card relative cursor-pointer rounded-2xl border-2 overflow-hidden transition-all duration-200
                        {{ $isActive ? 'border-blue-500 ring-4 ring-blue-100 dark:ring-blue-900/40' : 'border-gray-200 dark:border-gray-700 hover:border-gray-400' }}"
                 data-slug="{{ $slug }}"
                 onclick="selectTheme('{{ $slug }}')">

                @if($isActive)
                <div class="absolute top-3 right-3 z-10 flex items-center gap-1 px-2.5 py-1 bg-blue-500 text-white text-xs font-bold rounded-full">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                    Aktif
                </div>
                @endif

                {{-- Mini UI Önizlemesi --}}
                <div class="h-48 p-3" style="background:{{ $theme['preview']['background'] }};">
                    {{-- Nav --}}
                    <div class="flex items-center justify-between px-3 py-2 rounded-lg mb-3"
                         style="background:{{ $theme['preview']['primary'] }};">
                        <div class="flex items-center gap-2">
                            <div class="w-4 h-4 rounded-full" style="background:{{ $theme['preview']['accent'] }};"></div>
                            <div class="w-10 h-1.5 rounded" style="background:{{ $theme['preview']['accent'] }};opacity:.7"></div>
                        </div>
                        <div class="flex gap-1">
                            <div class="w-6 h-1 rounded" style="background:{{ $theme['preview']['accent'] }};opacity:.5"></div>
                            <div class="w-6 h-1 rounded" style="background:{{ $theme['preview']['accent'] }};opacity:.5"></div>
                            <div class="w-6 h-1 rounded" style="background:{{ $theme['preview']['accent'] }};opacity:.5"></div>
                        </div>
                    </div>

                    {{-- Hero --}}
                    <div class="rounded-lg px-3 py-2.5 mb-2.5"
                         style="background:{{ $theme['preview']['primary'] }};">
                        <div class="w-16 h-2 rounded mb-1.5" style="background:{{ $theme['preview']['accent'] }};"></div>
                        <div class="w-28 h-1.5 rounded mb-2.5" style="background:{{ $theme['preview']['accent'] }};opacity:.4"></div>
                        <div class="w-14 h-3.5 rounded" style="background:{{ $theme['preview']['accent'] }};"></div>
                    </div>

                    {{-- Kartlar --}}
                    <div class="grid grid-cols-3 gap-1.5">
                        @for($i = 0; $i < 3; $i++)
                        <div class="rounded-lg p-1.5" style="background:{{ $theme['preview']['surface'] }};border:1px solid rgba(0,0,0,.06);">
                            <div class="h-1.5 rounded mb-1" style="background:{{ $theme['preview']['primary'] }};opacity:.3"></div>
                            <div class="h-1 rounded w-3/4" style="background:{{ $theme['preview']['primary'] }};opacity:.15"></div>
                            <div class="h-2 rounded mt-1.5 w-8" style="background:{{ $theme['preview']['accent'] }};"></div>
                        </div>
                        @endfor
                    </div>
                </div>

                {{-- Bilgi --}}
                <div class="p-4 bg-white dark:bg-gray-800 border-t border-gray-100 dark:border-gray-700">
                    <div class="flex items-center gap-2.5 mb-1.5">
                        <div class="flex gap-1">
                            <span class="w-4 h-4 rounded-full border border-white shadow-sm inline-block" style="background:{{ $theme['preview']['primary'] }};"></span>
                            <span class="w-4 h-4 rounded-full border border-white shadow-sm inline-block" style="background:{{ $theme['preview']['accent'] }};"></span>
                            <span class="w-4 h-4 rounded-full border border-white shadow-sm inline-block" style="background:{{ $theme['preview']['background'] }};border-color:#ddd;"></span>
                        </div>
                        <span class="font-bold text-gray-900 dark:text-white text-sm">{{ $theme['label'] }}</span>
                    </div>
                    <p class="text-xs text-gray-500 dark:text-gray-400 leading-relaxed">{{ $theme['description'] }}</p>
                </div>
            </div>
            @endforeach
        </div>

        {{-- Kaydet --}}
        <div class="flex items-center justify-between p-5 bg-gray-50 dark:bg-gray-800 border border-gray-200 dark:border-gray-700 rounded-xl">
            <p class="text-sm text-gray-500 dark:text-gray-400">
                Tema seçildikten sonra <strong>Kaydet</strong>'e basın. Cache otomatik temizlenir.
            </p>
            <button type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-bold text-sm rounded-lg hover:bg-gray-700 transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                Temayı Kaydet
            </button>
        </div>
    </form>

    {{-- Teknik Not --}}
    <div class="mt-5 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl flex gap-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/></svg>
        <p class="text-xs text-amber-700 dark:text-amber-400 leading-relaxed">
            Temalar <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded font-mono">config/themes.php</code> dosyasında tanımlanır.
            Yeni tema eklemek için sadece bu dosyaya yeni bir blok ekleyin — kod değişikliği gerekmez.
            Seçim <code class="bg-amber-100 dark:bg-amber-900 px-1 rounded font-mono">settings.frontend_theme</code> olarak kaydedilir.
        </p>
    </div>
</div>

<script>
function selectTheme(slug) {
    document.getElementById('selected-theme').value = slug;
    document.querySelectorAll('.theme-card').forEach(function(c) {
        c.classList.remove('border-blue-500','ring-4','ring-blue-100');
        c.classList.add('border-gray-200','dark:border-gray-700');
    });
    var sel = document.querySelector('[data-slug="' + slug + '"]');
    if (sel) {
        sel.classList.remove('border-gray-200','dark:border-gray-700');
        sel.classList.add('border-blue-500','ring-4','ring-blue-100');
    }
}
</script>
@endsection
