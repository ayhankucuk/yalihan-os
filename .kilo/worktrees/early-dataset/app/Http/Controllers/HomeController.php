<?php

namespace App\Http\Controllers;

/**
 * @sab-ignore-service
 */

/**
 * @sab-ignore-thin
 */

use App\Enums\IlanDurumu;
use App\Models\Ilan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // İstatistikler (Mock verisi yerine gerçek veri)
        $stats = [
            'active_listings' => Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->count(), // context7-ignore
            'experience_years' => 20, // Sabit değer (Kurumsal)
            'happy_customers' => 1500, // Sabit/Tahmini değer
        ];

        // Öne Çıkan İlanlar (Son eklenen 6 aktif ilan)
        // Not: Gerçek bir 'one_cikan' kolonu varsa o da eklenebilir.
        // Şimdilik son eklenenleri 'öne çıkan' olarak gösteriyoruz.
        $featuredProperties = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->with(['il', 'ilce', 'mahalle', 'fiyatGecmisi', 'fotograflar', 'yayinTipi:id,yayin_tipi'])
            ->latest()
            ->take(6)
            ->get();

        // Lokasyonlar — integer ID gönderir (PublicListingService whereIn ilce_id bekler)
        $locations = DB::table('ilanlar')
            ->join('ilceler', 'ilanlar.ilce_id', '=', 'ilceler.id')
            ->where('ilanlar.yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereNotNull('ilanlar.ilce_id')
            ->orderBy('ilceler.ilce_adi')
            ->distinct()
            ->select('ilceler.id', 'ilceler.ilce_adi')
            ->get()
            ->unique('id')
            ->map(fn ($row) => ['value' => $row->id, 'label' => $row->ilce_adi])
            ->values()
            ->toArray();

        // Yazlık lokasyonları — mahalle adı string (VillaService whereIn mahalle_adi bekler)
        $villaLocations = DB::table('ilanlar')
            ->join('mahalleler', 'ilanlar.mahalle_id', '=', 'mahalleler.id')
            ->join('ilan_kategorileri', 'ilanlar.ana_kategori_id', '=', 'ilan_kategorileri.id')
            ->where('ilanlar.yayin_durumu', IlanDurumu::YAYINDA->value)
            ->where('ilan_kategorileri.slug', 'yazlik-kiralama')
            ->whereNotNull('ilanlar.mahalle_id')
            ->orderBy('mahalleler.mahalle_adi')
            ->distinct()
            ->pluck('mahalleler.mahalle_adi')
            ->map(fn ($m) => ['value' => $m, 'label' => $m])
            ->values()
            ->toArray();

        // Yazlık lokasyonu boşsa ilçe adlarını fallback olarak kullan
        if (empty($villaLocations)) {
            $villaLocations = $locations;
        }

        // Eğer hiç ilan yoksa Bodrum ilçelerini fallback olarak yükle (ID bazlı)
        if (empty($locations)) {
            $locations = DB::table('ilceler')
                ->whereIn('ilce_adi', ['Bodrum', 'Yalıkavak', 'Gümüşlük', 'Bitez', 'Türkbükü'])
                ->orderBy('ilce_adi')
                ->get(['id', 'ilce_adi'])
                ->map(fn ($r) => ['value' => $r->id, 'label' => $r->ilce_adi])
                ->values()
                ->toArray();
        }

        // Yurt dışı ülkeler — ilanlar.ulke_id ile ilişkili, TR hariç aktif ülkeler + ilan sayısı
        // value=ulke_kodu → InternationalListingService country filtresi (ulke_kodu OR id kabul eder)
        // İlan sayısı: Schema::hasColumn korumalı tek sorguda çekiliyor
        $ulkeIlanSayilari = \Illuminate\Support\Facades\Schema::hasColumn('ilanlar', 'ulke_id')
            ? DB::table('ilanlar')
                ->where('yayin_durumu', IlanDurumu::YAYINDA->value)
                ->whereNotNull('ulke_id')
                ->groupBy('ulke_id')
                ->selectRaw('ulke_id, COUNT(*) as toplam')
                ->pluck('toplam', 'ulke_id')
                ->toArray()
            : [];

        $yurtDisiUlkeler = \App\Models\Ulke::where('aktiflik_durumu', true)
            ->where('ulke_kodu', '!=', 'TR')
            ->orderBy('ulke_adi')
            ->get(['id', 'ulke_adi', 'ulke_kodu'])
            ->map(fn ($u) => [
                'value'       => $u->ulke_kodu,
                'label'       => $u->ulke_adi,
                'ilan_sayisi' => $ulkeIlanSayilari[$u->id] ?? 0,
            ])
            ->values()
            ->toArray();

        // Emlak Türleri (Ana Kategoriler)
        $propertyTypes = \App\Models\IlanKategori::anaKategoriler()
            ->active() // ✅ SAB v3.0: Standardized scope // context7-ignore
            ->orderBy('display_order') // context7-ignore
            ->orderBy('name') // context7-ignore
            ->get()
            ->map(function ($kategori) {
                return ['value' => $kategori->id, 'label' => $kategori->name];
            })
            ->toArray();

        // Arsa ilanları — Ana sayfa "Arsa & Parsel" section
        $arsaIlanları = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereHas('anaKategori', fn ($q) => $q->where('slug', 'arsa-arazi')) // @sab-ignore-naming: Eloquent relation name
            ->with(['ilce:id,ilce_adi', 'fotograflar'])
            ->latest()
            ->take(3)
            ->get();

        // Yazlık kiralık ilanlar — Ana sayfa "Yazlık & Kiralık" section
        $yazlıkIlanları = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereHas('anaKategori', fn ($q) => $q->where('slug', 'yazlik-kiralama')) // @sab-ignore-naming: Eloquent relation name
            ->with(['ilce:id,ilce_adi', 'fotograflar', 'yayinTipi:id,yayin_tipi'])
            ->latest()
            ->take(3)
            ->get();

        // Satılık konut ilanları — Hero tab grid
        $satılıkKonut = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereHas('anaKategori', fn ($q) => $q->whereIn('slug', ['daire', 'konut', 'villa', 'mustakil-ev']))
            ->with(['il:id,il_adi', 'ilce:id,ilce_adi', 'anaKategori:id,name,slug', 'yayinTipi:id,yayin_tipi', 'fotograflar'])
            ->latest()
            ->take(6)
            ->get();

        // Yurt dışı ilanlar
        $yurtDışıIlanları = Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)
            ->whereHas('anaKategori', fn ($q) => $q->where('slug', 'yurt-disi'))
            ->with(['il:id,il_adi', 'ilce:id,ilce_adi', 'fotograflar', 'yayinTipi:id,yayin_tipi'])
            ->latest()
            ->take(3)
            ->get();

        // Popüler mahalleler — ilan sayısına göre sıralı, max 6 kart
        $populerMahalleler = \App\Models\Mahalle::withCount(['ilanlar as ilan_sayisi' => fn ($q) => $q->where('yayin_durumu', IlanDurumu::YAYINDA->value)])
            ->having('ilan_sayisi', '>', 0)
            ->orderByDesc('ilan_sayisi')
            ->with('ilce:id,ilce_adi')
            ->take(6)
            ->get(['id', 'mahalle_adi', 'ilce_id']);

        // ── SEO ─────────────────────────────────────────────────────
        $seo = [
            'title'       => 'Yalıhan Emlak — Bodrum\'da Lüks Gayrimenkul | Villa & Daire',
            'description' => 'Bodrum\'un en seçkin lüks gayrimenkul portföyleri. Yalıkavak, Bodrum Merkez ve Ege kıyılarında villa, daire, arsa. 15 yıllık deneyim.',
            'og_type'     => 'website',
            'og_image'    => asset('images/og-image.jpg'),
            'og_locale'   => 'tr_TR',
            'canonical'   => url('/'),
            'robots'      => 'index, follow',
        ];

        // @sab-ignore-naming: View layer variables (not DB columns)
        // @sab-ignore-naming: View layer variables (not DB columns)
        return view('yaliihan-home-clean', compact(
            'stats', 'featuredProperties', 'locations', 'villaLocations', 'propertyTypes',
            'yurtDisiUlkeler',
            'arsaIlanları', 'yazlıkIlanları',
            'satılıkKonut', 'yurtDışıIlanları', 'populerMahalleler',
            'seo'
        ));
    }
}
