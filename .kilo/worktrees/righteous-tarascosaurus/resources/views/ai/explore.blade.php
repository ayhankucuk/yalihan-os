@extends('layouts.frontend')

@section('title', 'AI Portföy Keşfi — Yalıhan Emlak')

@push('styles')
<style>
:root {
    --navy: #0A1628;
    --navy-mid: #162440;
    --gold: #C9A84C;
    --gold-light: rgba(201,168,76,0.15);
    --cream: #F8F6F1;
}

/* Hero */
.ai-hero-bg {
    background: linear-gradient(135deg, var(--navy) 0%, #162440 55%, #1e3052 100%);
}

/* Stat chips */
.stat-chip {
    background: rgba(201,168,76,0.12);
    border: 1px solid rgba(201,168,76,0.25);
    color: var(--gold);
}

/* Feature chips */
.feature-chip {
    background: rgba(255,255,255,0.06);
    border: 1px solid rgba(255,255,255,0.1);
    color: rgba(255,255,255,0.75);
    transition: all 0.2s;
    cursor: pointer;
}
.feature-chip:hover, .feature-chip.active {
    background: var(--gold-light);
    border-color: var(--gold);
    color: var(--gold);
}

/* Ilan kartı */
.ilan-kart {
    border: 1px solid rgba(10,22,40,0.08);
    transition: box-shadow 0.25s, transform 0.25s;
}
.ilan-kart:hover {
    box-shadow: 0 8px 32px rgba(10,22,40,0.12);
    transform: translateY(-3px);
}
.kart-foto {
    height: 200px;
    position: relative;
    overflow: hidden;
}

/* AI Dock */
#aiDock {
    position: fixed;
    bottom: 24px;
    right: 24px;
    z-index: 50;
    width: 360px;
    transition: all 0.3s ease;
}
#aiDock.minimized #dockBody { display: none; }
#aiDock.minimized { width: auto; }

/* Filter bar sticky */
#filterBar {
    position: sticky;
    top: 72px;
    z-index: 30;
    background: rgba(248,246,241,0.95);
    backdrop-filter: blur(8px);
    border-bottom: 1px solid rgba(10,22,40,0.08);
}

/* İlçe kartları */
.ilce-kart {
    border: 1px solid rgba(10,22,40,0.08);
    transition: all 0.2s;
    cursor: pointer;
}
.ilce-kart:hover {
    border-color: var(--gold);
    box-shadow: 0 4px 12px rgba(201,168,76,0.15);
}

/* Sort buton aktif */
.sort-btn.active {
    background: var(--navy);
    color: var(--gold);
}
</style>
@endpush

@section('content')

{{-- ══════════════════════════════════════════════
     HERO
══════════════════════════════════════════════ --}}
<section class="ai-hero-bg py-20 relative overflow-hidden">

    {{-- Decorative arka plan --}}
    <div class="absolute inset-0 opacity-5" style="background-image:radial-gradient(circle at 20% 50%, #C9A84C 0%, transparent 50%), radial-gradient(circle at 80% 20%, #C9A84C 0%, transparent 40%);"></div>

    <div class="max-w-7xl mx-auto px-6 md:px-12 relative">
        <div class="flex flex-col lg:flex-row items-center gap-12">

            {{-- Sol: Metin --}}
            <div class="flex-1 text-center lg:text-left">
                <div class="inline-flex items-center gap-2 mb-5 px-4 py-1.5 rounded-full stat-chip">
                    <span class="material-symbols-outlined text-sm">auto_awesome</span>
                    <span class="text-xs font-semibold uppercase tracking-widest">Cortex AI Motoru</span>
                </div>

                <h1 class="text-4xl md:text-5xl lg:text-6xl font-bold text-white mb-5 leading-tight">
                    AI ile <span style="color:var(--gold)">Akıllı</span><br>Gayrimenkul Keşfi
                </h1>
                <p class="text-white/65 text-lg max-w-xl mb-8">
                    Doğal dille arayın. Bodrum Yarımadası'nda bütçenize ve tercihlerinize göre en uygun ilanları gerçek verilerle bulun.
                </p>

                {{-- İstatistikler --}}
                <div class="flex flex-wrap gap-4 justify-center lg:justify-start mb-8">
                    <div class="stat-chip px-4 py-2 rounded-xl text-sm font-semibold">
                        <span class="text-white/60 font-normal">Aktif İlan</span>
                        <span class="ml-1">{{ number_format($toplamIlan) }}</span>
                    </div>
                    <div class="stat-chip px-4 py-2 rounded-xl text-sm font-semibold">
                        <span class="text-white/60 font-normal">Bölge</span>
                        <span class="ml-1">{{ $toplamIlce }}+</span>
                    </div>
                    @foreach($kategoriSayilari->take(3) as $kat)
                    <div class="stat-chip px-4 py-2 rounded-xl text-sm font-semibold">
                        <span class="text-white/60 font-normal">{{ $kat->name }}</span>
                        <span class="ml-1">{{ $kat->toplam }}</span>
                    </div>
                    @endforeach
                </div>

                {{-- Hızlı sorgular --}}
                <div class="flex flex-wrap gap-2">
                    @foreach([
                        'Yalıkavak\'ta denize yakın villa',
                        'Bodrum merkez 3+1 daire',
                        '5M TL altı yatırımlık arsa',
                        'Türkbükü kiralık yazlık',
                    ] as $q)
                    <button onclick="setAIQuery('{{ addslashes($q) }}')"
                            class="feature-chip text-xs px-3 py-1.5 rounded-full font-medium">
                        {{ $q }}
                    </button>
                    @endforeach
                </div>
            </div>

            {{-- Sağ: Parametre kartı --}}
            <div class="flex-1 w-full max-w-md">
                <div class="rounded-2xl p-6" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);backdrop-filter:blur(12px);">
                    <p class="text-white/50 text-xs uppercase tracking-widest font-semibold mb-4">Anlık Piyasa</p>
                    <div class="grid grid-cols-2 gap-3">
                        @foreach($featuredIlceler->take(4) as $ilce)
                        <a href="{{ route('ilanlar.index', ['ilce' => $ilce->id]) }}"
                           class="ilce-kart rounded-xl p-4 bg-white/5">
                            <p class="text-white font-semibold text-sm">{{ $ilce->ilce_adi }}</p>
                            <p class="text-xs mt-1" style="color:var(--gold);">{{ $ilce->ilan_sayisi }} ilan</p>
                        </a>
                        @endforeach
                    </div>
                    <div class="mt-4 pt-4" style="border-top:1px solid rgba(255,255,255,0.08);">
                        <a href="{{ route('public.conversational') }}"
                           class="w-full flex items-center justify-center gap-2 py-3 rounded-xl font-semibold text-sm transition-all hover:opacity-90"
                           style="background:var(--gold);color:var(--navy);">
                            <span class="material-symbols-outlined text-base">psychology</span>
                            AI Danışman ile Konuş
                        </a>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>

{{-- ══════════════════════════════════════════════
     FILTER BAR
══════════════════════════════════════════════ --}}
<div id="filterBar" class="py-3 px-6">
    <div class="max-w-7xl mx-auto flex flex-wrap gap-3 items-center">

        <select id="filterTur" onchange="applyFilter()"
                class="text-sm border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-400 bg-white"
                style="border-color:rgba(10,22,40,0.15);color:var(--navy);">
            <option value="">Tüm İşlem Tipleri</option>
            <option value="satilik">Satılık</option>
            <option value="kiralik">Kiralık</option>
        </select>

        <select id="filterIlce" onchange="applyFilter()"
                class="text-sm border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-400 bg-white"
                style="border-color:rgba(10,22,40,0.15);color:var(--navy);">
            <option value="">Tüm Bölgeler</option>
            @foreach($featuredIlceler as $ilce)
            <option value="{{ $ilce->ilce_adi }}">{{ $ilce->ilce_adi }} ({{ $ilce->ilan_sayisi }})</option>
            @endforeach
        </select>

        <select id="filterKat" onchange="applyFilter()"
                class="text-sm border rounded-lg px-3 py-2 outline-none focus:ring-2 focus:ring-yellow-400 bg-white"
                style="border-color:rgba(10,22,40,0.15);color:var(--navy);">
            <option value="">Tüm Kategoriler</option>
            @foreach($kategoriSayilari as $kat)
            <option value="{{ $kat->slug }}">{{ $kat->name }} ({{ $kat->toplam }})</option>
            @endforeach
        </select>

        <div class="relative flex-1 min-w-[200px]">
            <span class="material-symbols-outlined absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 text-lg">search</span>
            <input id="filterSearch" type="text" placeholder="İlçe, mahalle veya anahtar kelime..."
                   onkeyup="debounceFilter()"
                   class="w-full pl-10 pr-4 py-2 text-sm border rounded-lg outline-none focus:ring-2 focus:ring-yellow-400 bg-white"
                   style="border-color:rgba(10,22,40,0.15);color:var(--navy);">
        </div>

        <button onclick="clearFilters()"
                class="text-xs px-3 py-2 rounded-lg border transition-all hover:bg-slate-100"
                style="border-color:rgba(10,22,40,0.15);color:var(--navy);">
            Temizle
        </button>
    </div>
</div>

{{-- ══════════════════════════════════════════════
     ÖNCÜ BÖLGELER
══════════════════════════════════════════════ --}}
@if($featuredIlceler->count() > 0)
<section class="py-10 bg-white border-b" style="border-color:rgba(10,22,40,0.06);">
    <div class="max-w-7xl mx-auto px-6 md:px-12">
        <h2 class="text-lg font-bold mb-5" style="color:var(--navy);">Öne Çıkan Bölgeler</h2>
        <div class="flex flex-wrap gap-3">
            @foreach($featuredIlceler as $ilce)
            <a href="{{ route('ilanlar.index', ['ilce' => $ilce->id]) }}"
               class="flex items-center gap-2 px-4 py-2 rounded-full bg-white font-medium text-sm transition-all hover:shadow-md"
               style="border:1px solid rgba(10,22,40,0.1);color:var(--navy);">
                <span class="material-symbols-outlined text-sm" style="color:var(--gold);">location_on</span>
                {{ $ilce->ilce_adi }}
                <span class="text-xs px-2 py-0.5 rounded-full font-bold" style="background:rgba(201,168,76,0.12);color:var(--gold);">{{ $ilce->ilan_sayisi }}</span>
            </a>
            @endforeach
        </div>
    </div>
</section>
@endif

{{-- ══════════════════════════════════════════════
     İLAN GRID
══════════════════════════════════════════════ --}}
<section id="ai-explorer" class="py-12" style="background:var(--cream);">
    <div class="max-w-7xl mx-auto px-6 md:px-12">

        {{-- Başlık + Sort --}}
        <div class="flex items-center justify-between mb-6">
            <div>
                <h2 class="text-xl font-bold" style="color:var(--navy);">Güncel İlanlar</h2>
                <p id="ilan-count" class="text-sm text-slate-500 mt-0.5">{{ $ilanlar->count() }} ilan gösteriliyor</p>
            </div>
            <div class="flex gap-1 p-1 rounded-lg" style="background:rgba(10,22,40,0.06);">
                <button onclick="sortIlanlar('newest')" class="sort-btn active text-xs px-3 py-1.5 rounded-md font-medium transition-all" data-sort="newest">En Yeni</button>
                <button onclick="sortIlanlar('price_asc')" class="sort-btn text-xs px-3 py-1.5 rounded-md font-medium text-slate-500 transition-all" data-sort="price_asc">↑ Fiyat</button>
                <button onclick="sortIlanlar('price_desc')" class="sort-btn text-xs px-3 py-1.5 rounded-md font-medium text-slate-500 transition-all" data-sort="price_desc">↓ Fiyat</button>
            </div>
        </div>

        {{-- Grid --}}
        <div id="ilanGrid" class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
            @forelse($ilanlar as $ilan)
            @php
                $fotoUrl = $ilan->fotograflar->first()?->dosya_yolu ?? null;
                $gradients = [
                    'linear-gradient(135deg,#162440 0%,#2d5a9a 100%)',
                    'linear-gradient(135deg,#1a3a30 0%,#2a6655 100%)',
                    'linear-gradient(135deg,#2a1f1a 0%,#7a5535 100%)',
                    'linear-gradient(135deg,#1e1545 0%,#4a3085 100%)',
                    'linear-gradient(135deg,#0f3020 0%,#256040 100%)',
                    'linear-gradient(135deg,#3a1a10 0%,#7a4528 100%)',
                ];
                $grad = $gradients[$loop->index % count($gradients)];
                $fiyat = $ilan->fiyat ?? 0;
                $fiyatStr = $fiyat > 0 ? '₺ ' . number_format($fiyat, 0, ',', '.') : 'Fiyat Sorunuz';
            @endphp
            <article class="ilan-kart bg-white rounded-2xl overflow-hidden flex flex-col"
                     data-ilce="{{ is_object($ilan->ilce) ? ($ilan->ilce->ilce_adi ?? '') : '' }}"
                     data-kategori="{{ $ilan->anaKategori?->slug ?? '' }}"
                     data-baslik="{{ strtolower($ilan->baslik ?? '') }}"
                     data-fiyat="{{ $fiyat }}">

                {{-- Fotoğraf / Gradient --}}
                <div class="kart-foto" style="{{ $fotoUrl ? "background:url('".asset('storage/'.$fotoUrl)."') center/cover" : "background:$grad" }}">
                    <div class="absolute inset-0 bg-gradient-to-t from-black/40 to-transparent"></div>

                    {{-- Badges --}}
                    <div class="absolute top-3 left-3 flex gap-1.5">
                        @if($ilan->anaKategori)
                        <span class="text-xs font-bold px-2 py-1 rounded text-white" style="background:var(--navy);">
                            {{ $ilan->anaKategori->name }}
                        </span>
                        @endif
                        @if($ilan->yayinTipi && !in_array(trim($ilan->yayinTipi->yayin_tipi), ['', '—', '-', 'null']))
                        <span class="text-xs font-bold px-2 py-1 rounded" style="background:var(--gold);color:var(--navy);">
                            {{ $ilan->yayinTipi->yayin_tipi }}
                        </span>
                        @endif
                    </div>

                    {{-- Favori --}}
                    <button class="absolute top-3 right-3 w-8 h-8 rounded-full bg-white/90 flex items-center justify-center text-slate-400 hover:text-red-500 transition-colors">
                        <span class="material-symbols-outlined text-sm">favorite</span>
                    </button>
                </div>

                {{-- İçerik --}}
                <div class="p-4 flex flex-col flex-1">
                    <h3 class="font-medium text-sm line-clamp-2 mb-2" style="color:#334155;">
                        {{ $ilan->baslik ?? 'İlan' }}
                    </h3>
                    @php
                        $lokasyon = null;
                        if (is_object($ilan->ilce) && isset($ilan->ilce->ilce_adi) && $ilan->ilce->ilce_adi !== 'Belirtilmemiş') {
                            $lokasyon = $ilan->ilce->ilce_adi;
                        } elseif (is_object($ilan->il) && isset($ilan->il->il_adi) && $ilan->il->il_adi !== 'Belirtilmemiş') {
                            $lokasyon = $ilan->il->il_adi;
                        }
                    @endphp
                    @if($lokasyon)
                    <p class="text-xs text-slate-400 flex items-center gap-1 mb-3">
                        <span class="material-symbols-outlined text-xs" style="color:var(--gold);">location_on</span>
                        {{ $lokasyon }}
                    </p>
                    @else
                    <p class="mb-3"></p>
                    @endif

                    {{-- Özellikler --}}
                    <div class="flex gap-3 text-xs text-slate-500 mb-4">
                        @if($ilan->alan)
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">straighten</span>
                            {{ $ilan->alan }} m²
                        </span>
                        @endif
                        @if($ilan->oda_sayisi)
                        <span class="flex items-center gap-1">
                            <span class="material-symbols-outlined text-xs">bed</span>
                            {{ $ilan->oda_sayisi }}
                        </span>
                        @endif
                    </div>

                    <div class="mt-auto flex items-center justify-between pt-3" style="border-top:1px solid rgba(10,22,40,0.06);">
                        <p class="font-bold text-base" style="color:var(--navy);">{{ $fiyatStr }}</p>
                        <a href="{{ route('ilanlar.show', $ilan->id) }}"
                           class="text-xs px-3 py-1.5 rounded-lg font-semibold transition-all hover:opacity-90"
                           style="background:var(--navy);color:var(--gold);">
                            İncele
                        </a>
                    </div>
                </div>
            </article>
            @empty
            <div class="col-span-4 py-20 text-center">
                <span class="material-symbols-outlined text-5xl text-slate-300 mb-4 block">home_search</span>
                <p class="text-slate-400">Henüz aktif ilan bulunmuyor.</p>
            </div>
            @endforelse
        </div>

        {{-- Tüm ilanlar butonu --}}
        <div class="mt-10 text-center">
            <a href="{{ route('ilanlar.index') }}"
               class="inline-flex items-center gap-2 px-8 py-3 rounded-xl font-semibold text-sm transition-all hover:opacity-90"
               style="background:var(--navy);color:var(--gold);">
                <span class="material-symbols-outlined text-base">grid_view</span>
                Tüm İlanları Görüntüle
            </a>
        </div>

    </div>
</section>

{{-- ══════════════════════════════════════════════
     AI DOCK (sağ alt, minimize edilebilir)
══════════════════════════════════════════════ --}}
<div id="aiDock">
    {{-- Dock header --}}
    <div class="rounded-t-2xl px-4 py-3 flex items-center justify-between cursor-pointer select-none"
         style="background:var(--navy);"
         onclick="toggleDock()">
        <div class="flex items-center gap-2 font-bold text-white text-sm">
            <span class="material-symbols-outlined text-base" style="color:var(--gold);">psychology</span>
            Yalıhan AI Asistan
        </div>
        <div class="flex items-center gap-2">
            <span id="dockToggleIcon" class="material-symbols-outlined text-white/60 hover:text-white text-base">remove</span>
            <button onclick="event.stopPropagation(); closeDock()"
                    class="material-symbols-outlined text-white/60 hover:text-white text-base leading-none">close</button>
        </div>
    </div>

    {{-- Dock body --}}
    <div id="dockBody" class="rounded-b-2xl overflow-hidden shadow-2xl" style="background:#F8F6F1;border:1px solid rgba(10,22,40,0.1);border-top:none;">
        <div id="dockMessages" class="p-4 h-36 overflow-y-auto text-sm text-slate-600 space-y-2">
            <p class="font-semibold text-xs mb-1" style="color:var(--navy);">Örnek sorgular:</p>
            <p class="text-xs text-slate-500 cursor-pointer hover:text-slate-800" onclick="setAIQuery('Yalıkavak\'ta 20M TL altı denize sıfır villa')">
                → "Yalıkavak'ta 20M TL altı denize sıfır villa"
            </p>
            <p class="text-xs text-slate-500 cursor-pointer hover:text-slate-800" onclick="setAIQuery('Kiralık 3+1 daire Bodrum merkez')">
                → "Kiralık 3+1 daire Bodrum merkez"
            </p>
            <p class="text-xs text-slate-500 cursor-pointer hover:text-slate-800" onclick="setAIQuery('Yatırımlık arsa fırsatları')">
                → "Yatırımlık arsa fırsatları"
            </p>
        </div>
        <div class="p-3" style="border-top:1px solid rgba(10,22,40,0.08);">
            <div class="flex gap-2">
                <input id="aiDockInput" type="text" placeholder="Aramak istediğinizi yazın..."
                       onkeydown="if(event.key==='Enter') sendAIQuery()"
                       class="flex-1 text-sm px-3 py-2.5 rounded-xl border outline-none focus:ring-2 focus:ring-yellow-400 bg-white"
                       style="border-color:rgba(10,22,40,0.15);color:var(--navy);">
                <button onclick="sendAIQuery()"
                        class="px-4 py-2.5 rounded-xl font-semibold text-xs transition-all hover:opacity-90 flex items-center gap-1"
                        style="background:var(--navy);color:var(--gold);">
                    <span class="material-symbols-outlined text-sm">send</span>
                </button>
            </div>
            <p class="text-center mt-2">
                <a href="{{ route('public.conversational') }}" class="text-xs text-slate-400 hover:text-slate-600 underline">
                    Tam AI Danışman için tıklayın →
                </a>
            </p>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
// ─── DOM refs ─────────────────────────────────────
const grid        = document.getElementById('ilanGrid');
const ilanCount   = document.getElementById('ilan-count');
const filterTur   = document.getElementById('filterTur');
const filterIlce  = document.getElementById('filterIlce');
const filterKat   = document.getElementById('filterKat');
const filterSearch = document.getElementById('filterSearch');
let   debounceTimer = null;

// ─── Filter ───────────────────────────────────────
function applyFilter() {
    const tur    = filterTur.value.toLowerCase();
    const ilce   = filterIlce.value.toLowerCase();
    const kat    = filterKat.value.toLowerCase();
    const search = filterSearch.value.toLowerCase();

    const cards = grid.querySelectorAll('article');
    let visible = 0;

    cards.forEach(card => {
        const cIlce   = card.dataset.ilce.toLowerCase();
        const cKat    = card.dataset.kategori.toLowerCase();
        const cBaslik = card.dataset.baslik.toLowerCase();

        const matchIlce   = !ilce   || cIlce.includes(ilce);
        const matchKat    = !kat    || cKat === kat;
        const matchSearch = !search || cBaslik.includes(search) || cIlce.includes(search);

        if (matchIlce && matchKat && matchSearch) {
            card.style.display = '';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    if (ilanCount) ilanCount.textContent = visible + ' ilan gösteriliyor';
}

function debounceFilter() {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(applyFilter, 300);
}

function clearFilters() {
    filterTur.value = '';
    filterIlce.value = '';
    filterKat.value = '';
    filterSearch.value = '';
    applyFilter();
}

// ─── Sort ─────────────────────────────────────────
function sortIlanlar(by) {
    document.querySelectorAll('.sort-btn').forEach(b => {
        b.classList.remove('active');
        b.style.color = '';
    });
    const btn = document.querySelector(`.sort-btn[data-sort="${by}"]`);
    if (btn) btn.classList.add('active');

    const cards = Array.from(grid.querySelectorAll('article'));
    cards.sort((a, b) => {
        const fa = parseInt(a.dataset.fiyat) || 0;
        const fb = parseInt(b.dataset.fiyat) || 0;
        if (by === 'price_asc')  return fa - fb;
        if (by === 'price_desc') return fb - fa;
        return 0; // newest: already sorted by server
    });
    cards.forEach(c => grid.appendChild(c));
}

// ─── AI Dock ──────────────────────────────────────
const dock     = document.getElementById('aiDock');
const dockBody = document.getElementById('dockBody');
const dockIcon = document.getElementById('dockToggleIcon');

function toggleDock() {
    dock.classList.toggle('minimized');
    dockIcon.textContent = dock.classList.contains('minimized') ? 'add' : 'remove';
}

function closeDock() {
    dock.style.display = 'none';
}

function setAIQuery(q) {
    const inp = document.getElementById('aiDockInput');
    if (inp) { inp.value = q; inp.focus(); }
    if (dock.classList.contains('minimized')) toggleDock();
}

async function sendAIQuery() {
    const inp  = document.getElementById('aiDockInput');
    const msgs = document.getElementById('dockMessages');
    const q    = inp.value.trim();
    if (!q) return;

    // Kullanıcı mesajı
    const userMsg = document.createElement('p');
    userMsg.className = 'text-xs font-semibold';
    userMsg.style.color = 'var(--navy)';
    userMsg.textContent = '› ' + q;
    msgs.appendChild(userMsg);
    inp.value = '';
    msgs.scrollTop = msgs.scrollHeight;

    // Tam danışman sayfasına yönlendir
    const url = "{{ route('public.conversational') }}";
    setTimeout(() => {
        window.location.href = url + '?q=' + encodeURIComponent(q);
    }, 600);
}
</script>
@endpush
