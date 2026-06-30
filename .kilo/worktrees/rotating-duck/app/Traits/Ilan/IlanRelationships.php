<?php

namespace App\Traits\Ilan;

use App\Models\Kisi;
use App\Models\User;
use App\Models\Ulke;
use App\Models\Il;
use App\Models\Ilce;
use App\Models\Mahalle;
use App\Models\IlanKategori;
use App\Models\Feature;
use App\Models\Etiket;
use App\Models\DealPredictionLog;
use App\Models\DealPredictionSnapshot;
use App\Models\ListingTranslation;
use App\Models\YazlikFiyatlandirma;
use App\Models\IlanPriceHistory;
use App\Models\IlanFotografi;
use App\Models\PropertyReservation;
use App\Models\PropertyExpense;
use App\Models\PropertySubscription;
use App\Models\Photo;
use App\Models\Event;
use App\Models\Season;
use App\Models\YazlikRezervasyon;
use App\Models\IlanEmbedding;
use App\Models\IlanCalendarFeed;
use App\Models\IlanTakvimSync;
use App\Models\PropertyAvailability;
use App\Models\YazlikDetail;
use App\Models\Dikey\IlanTurizmDetail;
use App\Models\Dikey\IlanArsaDetail;
use App\Models\Dikey\IlanTicariDetail;
use App\Models\SiteApartman;
use App\Models\IlanGoruntulenmeGunluk;
use App\Models\IlanMetin;
use App\Models\Lead;
use App\Models\PropertyCalendarFeed;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait IlanRelationships
{
    /**
     * İlanın sahibini (Mülk Sahibi) döndürür.
     */
    public function ilanSahibi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'ilan_sahibi_id')
            ->withDefault([
                'ad' => 'Bilinmeyen',
                'soyad' => 'Kişi',
                'telefon' => '-',
            ]);
    }

    /**
     * AI Deal Predictions (SAB v16.5)
     */
    public function dealPredictions(): HasMany
    {
        return $this->hasMany(DealPredictionLog::class, 'ilan_id');
    }

    /**
     * AI Deal Prediction Snapshots (SAB v16.5)
     */
    public function dealPredictionSnapshots(): HasMany
    {
        return $this->hasMany(DealPredictionSnapshot::class, 'ilan_id');
    }

    /**
     * İlanla ilgilenen kişiyi (Emlakçı, Kiracı adayı vb.) döndürür.
     */
    public function ilgiliKisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'ilgili_kisi_id');
    }

    /**
     * İlanın danışmanı ilişkisi
     */
    public function danisman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'danisman_id')
            ->withDefault([
                'name' => 'Atanmamış',
                'email' => 'no-advisor@yalihanem.com',
            ]);
    }

    /**
     * User modeli ile danışman ilişkisi (Eloquent için)
     */
    public function userDanisman(): BelongsTo
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    // --- Adres İlişkileri ---

    public function ulke(): BelongsTo
    {
        return $this->belongsTo(Ulke::class, 'ulke_id');
    }

    public function il(): BelongsTo
    {
        return $this->belongsTo(Il::class, 'il_id')
            ->withDefault([
                'il_adi' => 'Belirtilmemiş',
            ]);
    }

    public function ilce(): BelongsTo
    {
        return $this->belongsTo(Ilce::class, 'ilce_id')
            ->withDefault([
                'ilce_adi' => 'Belirtilmemiş',
            ]);
    }

    public function mahalle(): BelongsTo
    {
        return $this->belongsTo(Mahalle::class, 'mahalle_id')
            ->withDefault([
                'mahalle_adi' => 'Belirtilmemiş',
            ]);
    }

    // --- Kategori İlişkileri ---

    public function anaKategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'ana_kategori_id')
            ->withDefault([
                'name' => 'Kategorisiz',
                'slug' => 'kategorisiz',
            ]);
    }

    public function altKategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'alt_kategori_id');
    }

    /**
     * Legacy parentKategori ilişkisi (geriye uyumluluk için)
     */
    public function parentKategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'parent_kategori_id');
    }

    /**
     * Yayın tipi ilişkisi
     */
    public function yayinTipi(): BelongsTo
    {
        return $this->belongsTo(\App\Models\YayinTipiSablonu::class, 'yayin_tipi_id')
                    ->withDefault(['ad' => '-', 'slug' => 'belirsiz']);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Demirbaşlar ilişkisi (pivot)
     */
    public function demirbaslar()
    {
        throw new \RuntimeException('ilan_demirbas pivot tablosu mevcut değil. Migration gerekli.');
    }

    /**
     * Demirbaşlar ilişkisi (tümü - aktif/pasif filtresi olmadan)
     */
    public function translations(): HasMany
    {
        return $this->hasMany(ListingTranslation::class, 'listing_id');
    }

    public function tumDemirbaslar()
    {
        throw new \RuntimeException('ilan_demirbas pivot tablosu mevcut değil. Migration gerekli.');
    }

    /**
     * Yazlık fiyatlandırma periyotları
     */
    public function yazlikFiyatlandirma(): HasMany
    {
        return $this->hasMany(YazlikFiyatlandirma::class, 'ilan_id');
    }

    /**
     * İlanın fiyat geçmişini döndürür.
     */
    public function fiyatGecmisi(): HasMany
    {
        return $this->hasMany(IlanPriceHistory::class, 'ilan_id')->orderBy('created_at', 'desc'); // context7-ignore
    }

    /**
     * İlanın fotoğraflarını döndürür.
     */
    public function fotograflar(): HasMany
    {
        return $this->hasMany(IlanFotografi::class, 'ilan_id')->orderBy('display_order'); // context7-ignore
    }

    public function rezervasyonlar(): HasMany
    {
        return $this->hasMany(PropertyReservation::class, 'property_id');
    }

    public function expenses(): HasMany
    {
        return $this->hasMany(PropertyExpense::class, 'ilan_id');
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(PropertySubscription::class, 'ilan_id');
    }

    /**
     * Photo Model ile ilişki (Yeni Photo System)
     */
    public function photos(): HasMany
    {
        return $this->hasMany(Photo::class)->ordered(); // context7-ignore
    }

    /**
     * Öne çıkan fotoğraf (Photo Model)
     */
    public function featuredPhoto()
    {
        return $this->hasOne(Photo::class)->where('one_cikan', true);
    }

    /**
     * Matching Feedback (Cortex AI)
     *
     * B-006 P5D: MatchingFeedback ghost — matching_feedbacks tablosu mevcut değil.
     * Gerçek veri kaynağı: AI eşleştirme metrikleri ilanlar.ai_metadata JSON kolonuna taşındı.
     * @throws \RuntimeException her zaman — lazy/eager loading engeli
     */
    public function matchingFeedbacks(): HasMany
    {
        throw new \RuntimeException(
            'matchingFeedbacks() — matching_feedbacks tablosu mevcut değil. ' .
            'Eşleştirme verisine ilanlar.ai_metadata JSON üzerinden erişin.'
        );
    }

    /**
     * Events (Rezervasyonlar/Etkinlikler)
     */
    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    /**
     * Aktif rezervasyonlar
     */
    public function activeEvents()
    {
        // context7-ignore
        return $this->hasMany(Event::class)->active();
    }

    /**
     * Sezonlar (Fiyatlandırma)
     */
    public function seasons(): HasMany
    {
        return $this->hasMany(Season::class);
    }

    /**
     * Aktif sezonlar
     */
    public function activeSeasons()
    {
        // context7-ignore
        return $this->hasMany(Season::class)->active();
    }

    /**
     * Yazlık rezervasyonları
     */
    public function yazlikRezervasyonlar(): HasMany
    {
        return $this->hasMany(YazlikRezervasyon::class);
    }

    /**
     * RAG / Vector Embedding
     */
    public function embedding(): HasOne
    {
        return $this->hasOne(IlanEmbedding::class);
    }

    /**
     * İlanın çevirilerini döndürür (LEGACY).
     *
     * B-006 P5D: IlanTranslation ghost — ilan_translations tablosu mevcut değil.
     * Gerçek çeviri sistemi: App\Models\ListingTranslation (listing_translations tablosu)
     * translations() relationship'ini kullanın.
     * @throws \RuntimeException her zaman
     */
    public function deprecatedTranslations(): HasMany
    {
        throw new \RuntimeException(
            'deprecatedTranslations() — ilan_translations tablosu mevcut değil. ' .
            'translations() (ListingTranslation) kullanın.'
        );
    }

    /**
     * Token tabanlı outbound ICS calendar feed
     * B-006 P5D: Deprecated\IlanCalendarFeed ghost → App\Models\IlanCalendarFeed (kanonik)
     * Table: ilan_calendar_feeds
     */
    public function calendarFeed(): HasMany
    {
        return $this->hasMany(IlanCalendarFeed::class, 'ilan_id');
    }

    /**
     * İlanın kategorisini döndürür (Alt Kategori).
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(IlanKategori::class, 'alt_kategori_id');
    }

    /**
     * İlanın kullanıcısını döndürür.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'danisman_id');
    }

    /**
     * İlanla ilişkili kişiyi döndürür.
     */
    public function kisi(): BelongsTo
    {
        return $this->belongsTo(Kisi::class, 'kisi_id');
    }

    /**
     * İlanın özelliklerini (features) döndürür.
     */
    public function ozellikler(): BelongsToMany
    {
        return $this->belongsToMany(Feature::class, 'ilan_feature', 'ilan_id', 'feature_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    /**
     * Features relationship (English alias for ozellikler)
     */
    public function features(): BelongsToMany
    {
        return $this->ozellikler();
    }

    /**
     * @deprecated listing_feature tablosu mevcut değil. ozellikler() kullanın.
     */
    public function ozelliklerLegacy(): BelongsToMany
    {
        return $this->ozellikler();
    }

    /**
     * İlanın etiketlerini döndürür.
     */
    public function etiketler(): BelongsToMany
    {
        return $this->belongsToMany(Etiket::class, 'ilan_etiketler')
            ->withPivot(['display_order', 'one_cikan'])
            ->orderByPivot('display_order') // context7-ignore
            ->withTimestamps();
    }

    /**
     * İlanı favorilediği kişiler
     */
    public function favorilenKisiler(): BelongsToMany
    {
        return $this->belongsToMany(Kisi::class, 'ilan_favorileri', 'ilan_id', 'kisi_id')
            ->withTimestamps()
            ->withPivot('aktiflik_durumu')
            ->wherePivot('aktiflik_durumu', 1);
    }

    /**
     * Tüm favori ilişkileri (aktif ve pasif)
     */
    public function tumFavorileri(): BelongsToMany
    {
        return $this->belongsToMany(Kisi::class, 'ilan_favorileri', 'ilan_id', 'kisi_id')
            ->withTimestamps()
            ->withPivot('aktiflik_durumu');
    }

    /**
     * İlanın takvim senkronizasyonlarını döndürür.
     */
    public function takvimSync()
    {
        return $this->hasMany(IlanTakvimSync::class, 'ilan_id');
    }

    /**
     * İlanın doluluk durumlarını döndürür (Yazlık için).
     */
    public function propertyAvailabilities(): HasMany
    {
        return $this->hasMany(PropertyAvailability::class, 'property_id');
    }

    /**
     * İlanın yazlık detaylarını döndürür.
     */
    public function yazlikDetail()
    {
        return $this->hasOne(YazlikDetail::class, 'ilan_id');
    }

    /**
     * İlanın turizm/yazlık detaylarını döndürür.
     */
    public function turizmDetail()
    {
        return $this->hasOne(IlanTurizmDetail::class, 'ilan_id');
    }

    /**
     * İlanın arsa detaylarını döndürür.
     */
    public function arsaDetail()
    {
        return $this->hasOne(IlanArsaDetail::class, 'ilan_id');
    }

    public function site(): BelongsTo
    {
        return $this->belongsTo(SiteApartman::class, 'site_id');
    }

    /**
     * Günlük görüntülenme istatistikleri
     */
    public function goruntulenmeler(): HasMany
    {
        return $this->hasMany(IlanGoruntulenmeGunluk::class, 'ilan_id');
    }

    public function metinler(): HasMany
    {
        return $this->hasMany(IlanMetin::class, 'ilan_id');
    }

    public function iletisimler(): HasMany
    {
        return $this->hasMany(Lead::class, 'ilan_id');
    }

    public function reservations(): HasMany
    {
        return $this->hasMany(PropertyReservation::class, 'property_id');
    }

    public function availabilities(): HasMany
    {
        return $this->hasMany(PropertyAvailability::class, 'property_id');
    }

    public function calendarFeeds(): HasMany
    {
        return $this->hasMany(PropertyCalendarFeed::class, 'property_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Ticari/İşyeri detayları
     * B-006 P5D: Deprecated\IlanTicariDetail ghost → App\Models\Dikey\IlanTicariDetail (kanonik)
     * Table: ilan_ticari_details
     */
    public function ticariDetail()
    {
        return $this->hasOne(IlanTicariDetail::class, 'ilan_id');
    }

    /**
     * Portal senkronizasyon bilgileri (JSON accessor)
     *
     * B-006 P5D: IlanPortalSync ghost — ilan_portal_syncs tablosu mevcut değil.
     * Portal sync durumu ilanlar.portal_senkronizasyon_durumu JSON kolonunda yaşıyor.
     * Bu method geriye uyumluluk için JSON kolon accessor'ına yönlendirir.
     *
     * @return array|null Portal senkronizasyon verisi
     */
    public function getPortalSyncAttribute(): ?array
    {
        return $this->portal_senkronizasyon_durumu ?? null;
    }

    /**
     * @deprecated portalSync() Eloquent ilişkisi kaldırıldı (B-006 P5D).
     *             $ilan->portal_sync (accessor) veya $ilan->portal_senkronizasyon_durumu kullanın.
     * @throws \RuntimeException her zaman
     */
    public function portalSync()
    {
        throw new \RuntimeException(
            'portalSync() ilişkisi kaldırıldı (B-006 P5D). ' .
            'ilan_portal_syncs tablosu mevcut değil. ' .
            '$ilan->portal_senkronizasyon_durumu (JSON) kullanın.'
        );
    }
}
