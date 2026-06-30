<?php

namespace App\Models;

use App\Enums\IlanDurumu;
use App\Models\Dikey\IlanArsaDetail;
use App\Models\Dikey\IlanTicariDetail;
use App\Models\Dikey\IlanTurizmDetail;
use App\Models\IlanCalendarFeed;
use App\Models\PropertyAvailability;
use App\Models\IlanTakvimSync;
use App\Traits\EnforcesContext7Guard;
use App\Traits\Filterable;
use App\Traits\HasActiveScope;
use App\Traits\HasCountryScope;
use App\Traits\HasFeatures;
use App\Traits\IncrementsStateVersion;
use App\Traits\RoiProjectionTrait;
use App\Traits\SabGuard;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

/**
 * App\Models\Ilan
 *
 * @property int $id
 * @property string $baslik
 * @property string|null $aciklama
 * @property float $fiyat
 * @property string $para_birimi
 * @property Carbon|null $ilan_tarihi
 * @property string $yayin_durumu İlan yayın durumu (Aktif/Pasif/Taslak)
 * @property int|null $proje_id
 *
 * // İlişkisel Alanlar
 * @property int|null $ilan_sahibi_id
 * @property int|null $ilgili_kisi_id
 * @property int|null $danisman_id
 * @property int|null $ulke_id
 * @property int|null $il_id
 * @property int|null $ilce_id
 * @property int|null $mahalle_id
 * @property int|null $ana_kategori_id
 * @property int|null $alt_kategori_id
 *
 * // Analitik, SEO ve CRM Alanları
 * @property string|null $slug
 * @property int $view_count
 * @property int $favorite_count
 * @property Carbon|null $son_islem_tarihi
 * @property float|null $son_islem_fiyati
 * @property string|null $islem_tipi // 'satis', 'kiralama'
 *
 * // Diğer Alanlar
 * @property string|null $youtube_video_url
 * @property string|null $sanal_tur_url
 * @property string|null $ada_no
 * @property string|null $parsel_no
 * @property float|null $lat
 * @property float|null $lng
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 *
 * // İlişkiler (Relationships)
 * @property-read Kisi|null $ilanSahibi
 * @property-read Kisi|null $ilgiliKisi
 * @property-read User|null $danisman
 * @property-read Ulke|null $ulke
 * @property-read Il|null $il
 * @property-read Ilce|null $ilce
 * @property-read Mahalle|null $mahalle
 * @property-read IlanKategori|null $anaKategori
 * @property-read IlanKategori|null $altKategori
 * @property-read \Illuminate\Database\Eloquent\Collection|IlanPriceHistory[] $fiyatGecmisi
 * @property-read int|null $fiyat_gecmisi_count
 * @property-read \Illuminate\Database\Eloquent\Collection|IlanFotografi[] $fotograflar
 * @property-read int|null $fotograflar_count
 * @property-read mixed $kapak_fotografi
 */

/**
 * App\Models\Ilan
 *
 * Context7 Sealed Domain: Ilan (2026-02-22)
 */
class Ilan extends BaseModel
{
    use EnforcesContext7Guard;
    use Filterable;
    use HasActiveScope;
    use HasFactory;
    use HasFeatures;
    use IncrementsStateVersion;
    use LogsActivity;
    use RoiProjectionTrait;
    use HasCountryScope;
    use SabGuard;
    use SoftDeletes;

    protected $table = 'ilanlar';

    protected $attributes = [
        'yayin_durumu' => IlanDurumu::TASLAK->value
    ];

    /**
     * Context7 Accessors & Mutators
     */

    /**
     * Safe accessor for yayin_durumu — handles legacy integer/string values
     * that predate the canonical enum migration. Prevents ValueError on hydration.
     * Uses tryFrom() → normalize() → null fallback chain (SAB §5.5 safe).
     */
    public function getYayinDurumuAttribute(): ?IlanDurumu
    {
        $raw = $this->attributes['yayin_durumu'] ?? null;

        if ($raw === null) {
            return null;
        }

        if ($raw instanceof IlanDurumu) {
            return $raw;
        }

        // tryFrom for canonical string values ('taslak', 'yayinda', etc.)
        $fromEnum = IlanDurumu::tryFrom((string) $raw);
        if ($fromEnum !== null) {
            return $fromEnum;
        }

        // Normalize legacy values ('aktif', '0', '1', etc.)
        return IlanDurumu::normalize($raw);
    }

    public function getYayindamiAttribute(): bool
    {
        $val = $this->yayin_durumu ?? null;
        if ($val instanceof IlanDurumu) {
            return $val->isActive();
        }
        return in_array($val, ['aktif', IlanDurumu::YAYINDA->value, 'yayinda'], true);
    }

    /**
     * Context7 compatibility aliases
     * - `aktiflik_durumu` is the canonical name (mirrors legacy `yayin_durumu`)
     * - `display_order` is the canonical name for display_ordering fields
     */
    public function getAktiflikDurumuAttribute()
    {
        return $this->yayin_durumu ?? null;
    }

    public function setAktiflikDurumuAttribute($value)
    {
        $this->yayin_durumu = $value;
    }

    /**
     * Compatibility layer for legacy 'status' insertions from systems that haven't adopted canonical schema
     */
    public function setDurumAttribute($value)
    {
        $this->attributes['yayin_durumu'] = $value instanceof IlanDurumu ? $value->value : $value;
    }

    /**
     * Mutator for yayin_durumu — normalizes any legacy/integer value to canonical string.
     * 🛡️ Phase 8: State Authority Guard — prevents direct unauthorized updates.
     */
    public function setYayinDurumuAttribute($value): void
    {
        // 1. Authority Guard: Block direct write if not via YalihanLifecycle (only for existing models)
        if ($this->exists && ! \App\Services\Listing\YalihanLifecycle::$isAuthorized) {
            $current = $this->getOriginal('yayin_durumu');
            // Allow if values are same (idempotent assignment)
            if ($current !== $value && \App\Enums\IlanDurumu::normalize($current) !== \App\Enums\IlanDurumu::normalize($value)) {
                throw new \DomainException(
                    "İlan durumu (yayin_durumu) doğrudan değiştirilemez. Lütfen YalihanLifecycle otoritesini kullanın."
                );
            }
        }

        if ($value instanceof IlanDurumu) {
            $this->attributes['yayin_durumu'] = $value->value;
            return;
        }

        // Try canonical string first
        $enum = IlanDurumu::tryFrom((string) $value);
        if ($enum !== null) {
            $this->attributes['yayin_durumu'] = $enum->value;
            return;
        }

        // Normalize legacy values ('aktif', '0', '1', etc.)
        $normalized = IlanDurumu::normalize($value);
        $this->attributes['yayin_durumu'] = $normalized?->value ?? 'taslak'; // safe default
    }

    /**
     * Context7: scopeByYayinDurumu yayin_durumu string/int için
     * Filterable trait'inden gelir, integer değerleri canonical string'e çevirir
     */
    public function scopeByYayinDurumu(Builder $query, $yayinDurumu, string $column = 'yayin_durumu')
    {
        if (is_bool($yayinDurumu)) {
            $yayinDurumu = $yayinDurumu ? 'yayinda' : 'pasif';
        }

        if (is_string($yayinDurumu)) {
            $map = [
                'aktif' => 'yayinda',
                'active' => 'yayinda', // context7-ignore
                'yayinda' => 'yayinda',
                'taslak' => 'taslak',
                'draft' => 'taslak',
                'pasif' => 'pasif',
                'beklemede' => 'beklemede',
                'pending' => 'beklemede',
            ];
            $key = strtolower($yayinDurumu);
            $yayinDurumu = $map[$key] ?? $yayinDurumu;
        }

        if (is_numeric($yayinDurumu)) {
            $intValue = (int) $yayinDurumu;
            $map = [
                1 => 'yayinda',
                0 => 'pasif',
                2 => 'taslak',
                3 => 'beklemede',
            ];
            $yayinDurumu = $map[$intValue] ?? 'taslak';
        }

        return $query->where($column, $yayinDurumu);
    }

    public function getDisplayOrderAttribute()
    {
        if (array_key_exists('display_order', $this->attributes)) {
            return $this->attributes['display_order'];
        }

        return null;
    }

    public function setDisplayOrderAttribute($value)
    {
        $this->attributes['display_order'] = $value;
    }

    /**
     * Frontend price rendering strategy text.
     */
    public function getFiyatGosterimMetniAttribute(): ?string
    {
        $mod = (string) ($this->attributes['fiyat_gosterim_modu'] ?? 'exact');
        $currency = (string) ($this->attributes['para_birimi'] ?? 'TRY');

        return match ($mod) {
            'hidden' => null,
            'on_request' => 'Fiyat için iletişime geçin',
            'starting_from' => !empty($this->attributes['baslangic_fiyati'])
                ? number_format((float) $this->attributes['baslangic_fiyati'], 0, ',', '.') . ' ' . $currency . "'den başlayan"
                : 'Fiyat için iletişime geçin',
            default => !empty($this->attributes['fiyat'])
                ? number_format((float) $this->attributes['fiyat'], 0, ',', '.') . ' ' . $currency
                : 'Fiyat için iletişime geçin',
        };
    }

    public function scopeOrderByDisplayOrder(Builder $query, string $direction = 'asc')
    {
        return $query->orderBy('display_order', $direction); // context7-ignore
    }

    /**
     * Searchable fields for Filterable trait
     *
     * @var array
     */
    protected $searchable = ['baslik', 'aciklama'];

    /**
     * The attributes that are mass assignable.
     *
     * Context7 Compliance: Tüm field'lar database ile senkronize edildi (6 Kasım 2025)
     *
     * Field Kategorileri:
     * ✅ REQUIRED: Zorunlu field'lar (validation'da kontrol edilir)
     * ⚠️ CONDITIONAL: Koşullu gerekli (kategori/ilan tipine göre)
     * 🔵 OPTIONAL: Opsiyonel field'lar
     * 🟡 LEGACY: Eski sistemden kalan, deprecated field'lar
     * 🔴 EXCLUDED: Model'de yok ama database'de var (auto-managed: id, created_at, updated_at, deleted_at)
     *
     * @var array<int, string>
     */
    protected $fillable = [
        // ======================================================================
        // ✅ REQUIRED FIELDS - Temel İlan Bilgileri
        // ======================================================================
        'baslik',                    // ✅ REQUIRED: İlan başlığı (varchar(255), NOT NULL)
        'aciklama',                  // ✅ REQUIRED: İlan açıklaması (text, NULL allowed)
        'fiyat',                     // ✅ REQUIRED: Ana fiyat bilgisi (decimal(15,2), NULL allowed)
        'fiyat_gosterim_modu',       // ✅ Price display strategy (exact/starting_from/on_request/hidden)
        'baslangic_fiyati',          // ✅ Optional starting price for starting_from mode
        'fiyat_notu',                // ✅ Optional strategy note
        // ✅ REQUIRED: Fiyatın yazıyla gösterimi
        'para_birimi',               // ✅ REQUIRED: Para birimi (varchar(10), NOT NULL, default: TRY)
        'yayin_durumu',              // ✅ PHYSICAL: İlan durumu (Aktif/Pasif/Taslak) - SSOT Confirmed
        'il_id',                     // ✅ REQUIRED: İl bilgisi (bigint unsigned, NULL allowed)
        'ilce_id',                   // ✅ REQUIRED: İlçe bilgisi (bigint unsigned, NULL allowed)
        'mahalle_id',                // ✅ REQUIRED: Mahalle bilgisi (bigint unsigned, NULL allowed)
        'ana_kategori_id',           // ✅ REQUIRED: Ana kategori (bigint unsigned, NULL allowed)
        'alt_kategori_id',          // ✅ REQUIRED: Alt kategori (bigint unsigned, NULL allowed)
        'yayin_tipi_id',            // ✅ REQUIRED: Yayın tipi (bigint unsigned, NULL allowed)

        // ✅ CONTEXT7: Canonical portfolio fields
        'is_active',           // ✅ SAB: Canonical active/inactive
        'one_cikan',                 // ✅ SAB: Canonical featured
        'display_order',             // ✅ SAB: Canonical display_ordering
        'kategori',                  // Portfolio import: category string
        'alt_kategori',             // Portfolio import: subcategory string
        'yayin_tipi',               // Portfolio import: publication type string
        'il',                        // Portfolio import: city string
        'ilce',                      // Portfolio import: district string
        'mahalle',                   // Portfolio import: neighborhood string
        'external_ref',              // External system reference (e.g., eids:...)
        'tapu_id',                   // FK to tapu_kayitlari
        'metadata',                  // JSON metadata for flexible fields

        // ======================================================================
        // ⚠️ CONDITIONAL FIELDS - Kategori/Tip Bazlı Gerekli Alanlar
        // ======================================================================

        // Arsa İçin Gerekli (kategori = arsa)
        'ada_no',                    // ⚠️ CONDITIONAL: Arsa için gerekli (varchar(50), NULL allowed)
        'parsel_no',                 // ⚠️ CONDITIONAL: Arsa için gerekli (varchar(50), NULL allowed)
        'ada_parsel',                // ⚠️ CONDITIONAL: Arsa için gerekli (varchar(100), NULL allowed)
        // ⚠️ CONDITIONAL: Arsa için önemli (varchar(100), NULL allowed)
        'alan_m2',                   // ⚠️ CONDITIONAL: Arsa için gerekli (decimal(12,2), NULL allowed)
        'yola_cephe',                // ⚠️ CONDITIONAL: Arsa için önemli (tinyint(1), NOT NULL, default: 0)
        'altyapi_elektrik',          // ⚠️ CONDITIONAL: Arsa için önemli (tinyint(1), NOT NULL, default: 0)
        'altyapi_su',                // ⚠️ CONDITIONAL: Arsa için önemli (tinyint(1), NOT NULL, default: 0)
        'altyapi_dogalgaz',          // ⚠️ CONDITIONAL: Arsa için önemli (tinyint(1), NOT NULL, default: 0)
        'kaks',                      // ⚠️ CONDITIONAL: Arsa için önemli (decimal(5,2), NULL allowed)
        'taks',                      // ⚠️ CONDITIONAL: Arsa için önemli (decimal(5,2), NULL allowed)
        'gabari',                    // ⚠️ CONDITIONAL: Arsa için önemli (decimal(5,2), NULL allowed)
        'firsat_mühru',              // 🔵 OPTIONAL: Fırsat mühru (boolean, default: false)

        // Daire/Villa İçin Gerekli (kategori = daire, villa)
        'oda_sayisi',                // ⚠️ CONDITIONAL: Daire/Villa için gerekli (int, NULL allowed)
        'banyo_sayisi',              // ⚠️ CONDITIONAL: Daire/Villa için gerekli (int, NULL allowed)
        'salon_sayisi',              // ⚠️ CONDITIONAL: Daire/Villa için önemli (int, NULL allowed)
        'net_m2',                    // ⚠️ CONDITIONAL: Daire/Villa için gerekli (decimal(10,2), NULL allowed)
        'brut_m2',                   // ⚠️ CONDITIONAL: Daire/Villa için gerekli (decimal(10,2), NULL allowed)
        'kat',                       // ⚠️ CONDITIONAL: Daire/Villa için önemli (int, NULL allowed)
        'toplam_kat',                // ⚠️ CONDITIONAL: Daire/Villa için önemli (int, NULL allowed)
        'bina_yasi',                 // ⚠️ CONDITIONAL: Daire/Villa için önemli (year, NULL allowed)
        'isitma',                    // ⚠️ CONDITIONAL: Daire/Villa için önemli (varchar(255), NULL allowed)
        'isinma_tipi',               // ⚠️ CONDITIONAL: Daire/Villa için önemli (varchar(255), NULL allowed)
        'esyali',                    // ⚠️ CONDITIONAL: Daire/Villa için önemli (tinyint(1), NOT NULL, default: 0)
        'site_ozellikleri',          // ⚠️ CONDITIONAL: Site içi için önemli (json, NULL allowed)
        'aidat',                     // ⚠️ CONDITIONAL: Daire/Villa için önemli (varchar(255), NULL allowed)

        // Yazlık Kiralama İçin Gerekli (kategori = yazlık)
        // ⚠️ DELETED: Moved to ilan_turizm_details (Phase 3 Cleanup)
        // Use hybrid fields: minimum_stay, max_guests, check_in_time, check_out_time, cleaning_fee
        // ======================================================================
        // 💰 ROI ENGINE FIELDS - Finansal Analiz (Phase 7.3)
        // ======================================================================
        // ======================================================================
        // 🚀 LANSMAN FIELDS - Proje Lansman Bilgileri (Phase 5.2)
        // ======================================================================
        // 🚀 LANSMAN: Lansman satış fiyatı (decimal(15,2), NULL allowed)
        // 🚀 LANSMAN: Lansman süresi bitiş tarihi (timestamp, NULL allowed)
        // 🚀 LANSMAN: Lansman için ayrılan stok/kota (int, NULL allowed)

        // ======================================================================
        // ✨ NEW VILLA & LAND FEATURES (EtsTur Sync & Land AI)
        // ======================================================================
        // NOTE: konum_tipi, merkeze_uzaklik, denize_uzaklik, plaja_uzaklik removed
        // These columns don't exist in database schema - removed from fillable
        'havuz_var', // İşyeri İçin Gerekli (kategori = isyeri)
        'isyeri_tipi',               // ⚠️ CONDITIONAL: İşyeri için gerekli (varchar(255), NULL allowed)
        'kira_bilgisi',              // ⚠️ CONDITIONAL: İşyeri için önemli (text, NULL allowed)
        'ciro_bilgisi',              // ⚠️ CONDITIONAL: İşyeri için önemli (decimal(15,2), NULL allowed)
        // ⚠️ CONDITIONAL: İşyeri için önemli (varchar(255), NULL allowed)
        'personel_kapasitesi',       // ⚠️ CONDITIONAL: İşyeri için önemli (int, NULL allowed)
        'isyeri_cephesi',            // ⚠️ CONDITIONAL: İşyeri için önemli (int, NULL allowed)

        // ======================================================================
        // HYBRID CORE FIELDS (English, global-hybrid standard)
        // ======================================================================
        'rental_enabled',            // HYBRID: Kiralama aktif mi?
        'min_stay_nights',           // HYBRID: Minimum stay nights (int)
        'max_stay_nights',           // HYBRID: Maximum stay nights (int) — DB column, API alias: maximum_stay
        'checkin_time',              // HYBRID: Check-in time (string)
        'checkout_time',             // HYBRID: Check-out time (string)
        'max_guests',                // HYBRID: Maximum guests (int)
        'cleaning_fee',              // HYBRID: Cleaning fee (float)
        'deposit_amount',            // HYBRID: Security deposit (float)
        'rental_currency',           // HYBRID: Para birimi (TRY vb.)

        // ======================================================================
        // 🔵 OPTIONAL FIELDS - Opsiyonel Bilgiler
        // ======================================================================

        // İlişkisel Alanlar
        // 🔵 OPTIONAL: İlan sahibi (kisi_id) - NULL allowed
        // 🔵 OPTIONAL: İlgili kişi (kisi_id) - NULL allowed
        'danisman_id',               // 🔵 OPTIONAL: Danışman (user_id) - NULL allowed
        // ❌ REMOVED: 'user_id' - LEGACY, use 'danisman_id' instead
        // 🔵 OPTIONAL: Proje ID - NULL allowed
        // 🔵 OPTIONAL: Ülke ID - NULL allowed

        // Adres Detayları
        'adres',                     // 🔵 OPTIONAL: Tam adres metni (varchar(255), NULL allowed)
        'lat',                       // 🔵 OPTIONAL: Enlem (decimal(10,8), NULL allowed) - Context7 SEALED
        'lng',                       // 🔵 OPTIONAL: Boylam (decimal(11,8), NULL allowed) - Context7 SEALED
        'geometry_type',             // 🔵 OPTIONAL: enum(point,polygon) - default 'point'
        'geometry',                  // 🔵 OPTIONAL: GeoJSON geometry data (json, NULL allowed)
        // ❌ REMOVED: 'l-atitude', 'l-ongitude' - Context7 mühürlendi, 'lat'/'lng' kullan

        // Yapı Detayları
        'taban_alani',               // 🔵 OPTIONAL: Taban alanı (decimal(12,2), NULL allowed)
        'yola_cephesi',              // 🔵 OPTIONAL: Yola cephesi (decimal(8,2), NULL allowed)

        // İlan Yönetimi
        'ilan_no',                   // 🔵 OPTIONAL: İlan numarası (varchar(255), UNIQUE, NULL allowed)
        'referans_no',               // 🔵 OPTIONAL: Referans numarası (varchar(50), UNIQUE, NULL allowed)
        // ❌ REMOVED: 'dosya_adi' - LEGACY, use 'referans_no' instead
        'slug',                      // 🔵 OPTIONAL: SEO slug - auto-generated
        'goruntulenme',              // 🔵 OPTIONAL: Görüntülenme sayısı (int, NOT NULL, default: 0)

        // Structured Data Template Fields
        'structured_data',            // 🔵 OPTIONAL: Template-based structured data (json, NULL allowed)
        'structured_data_scope',      // 🔵 OPTIONAL: Template scope (varchar(50), NULL allowed)
        'schema_version',             // 🔵 OPTIONAL: Schema version (int, default: 1)
        'approved_at',                // 🔵 OPTIONAL: Approval timestamp (timestamp, NULL allowed)
        'approved_by',                // 🔵 OPTIONAL: User ID who approved (bigint unsigned, NULL allowed)

        // Portal Entegrasyonları
        'sahibinden_id',             // 🔵 OPTIONAL: Sahibinden portal ID (varchar(50), NULL allowed)
        'emlakjet_id',               // 🔵 OPTIONAL: Emlakjet portal ID (varchar(50), NULL allowed)
        'hepsiemlak_id',             // 🔵 OPTIONAL: Hepsiemlak portal ID (varchar(50), NULL allowed)
        'zingat_id',                 // 🔵 OPTIONAL: Zingat portal ID (varchar(50), NULL allowed)
        'hurriyetemlak_id',          // 🔵 OPTIONAL: Hurriyetemlak portal ID (varchar(50), NULL allowed)
        // 🔵 OPTIONAL: Portal senkronizasyon durumu (json, NULL allowed)
        'portal_pricing',            // 🔵 OPTIONAL: Portal fiyatlandırma bilgileri (json, NULL allowed)

        // Anahtar Yönetimi
        'anahtar_kimde',             // 🔵 OPTIONAL: Anahtar kimde bilgisi
        'anahtar_turu',              // 🔵 OPTIONAL: Anahtar türü (enum: mal_sahibi, danisman, diger)
        'anahtar_notlari',           // 🔵 OPTIONAL: Anahtar notları (text, NULL allowed)
        'anahtar_ulasilabilirlik',   // 🔵 OPTIONAL: Anahtar ulaşılabilirlik (varchar(100), NULL allowed)
        'anahtar_ek_bilgi',          // 🔵 OPTIONAL: Anahtar ek bilgi (varchar(255), NULL allowed)

        // Medya
        // 🔵 OPTIONAL: Video render durumu (none, queued, rendering, completed) -> video_s-tatus
        // 🔵 OPTIONAL: Render ilerlemesi (0-100)

        // TurkiyeAPI + WikiMapia Integration (5 Kasım 2025)
        // NOTE: location_type, location_data, wikimapia_place_id, environmental_scores, nearby_places
        // removed from fillable - columns don't exist in current DB schema
        // 🔵 OPTIONAL: Cortex Matching Score
        // 🔵 OPTIONAL: Last Cortex Ranking Time
        // 🔵 OPTIONAL: AI Quality Score (Vision)
        // 🔵 OPTIONAL: AI Metadata (Vision/NLP)

        // ======================================================================
        // 🔴 EXCLUDED FIELDS - Auto-managed (Model'de yok ama database'de var)
        // ======================================================================
        // 'id' - Auto-increment primary key
        // 'created_at' - Auto-managed timestamp
        // 'updated_at' - Auto-managed timestamp
        // 'deleted_at' - Soft delete timestamp
        'created_by',                // SAB Phase 17B: Minimum tracking
        'updated_by',                // SAB Phase 17B: Minimum tracking

        // ======================================================================
        // 🟡 LEGACY FIELDS REMOVED - 2026-01-03
        // ======================================================================
        // 'user_id' → use 'danisman_id' instead
        // 'dosya_adi' → use 'referans_no' instead
        // 'l-atitude', 'l-ongitude' → use 'lat', 'lng' instead (Context7 sealed)
        // Previously had 60+ deprecated fields hurting performance
        // Database columns still exist but are no longer mass-assignable
        // See: docs/technical/legacy/ilan-model-legacy-fields-2025-12.md

        // T-UPS-V2-FULL: Kategori bazlı dinamik alan deposu
        'ekstra_ozellikler',

        // [YALIHAN_REPORTING_0206]
        'rapor_yolu',
        'rapor_hash',
        'rapor_uretildi_at',
        'rapor_uretildi_by',
        'rapor_gecersiz_mi',
        'rapor_gecersizlestirildi_at',
        'rapor_locale',
        'rapor_surum',
        'visibility_score',

        // Investor & ROI Fields (Investor Intelligence Sprint)
        'purchase_price',
        'operating_expenses_annual',
        'investment_currency',
        'investor_target_roi',
        'country_code',
        'source_locale',
    ];

    /**
     * The attributes that should be cast.
     *
     * Context7 Compliance: Tüm field'lar database type'larına göre cast edildi (6 Kasım 2025)
     *
     * @var array<string, string>
     */
    protected $casts = [
        // ======================================================================
        // ✅ REQUIRED FIELDS - Casts
        // ======================================================================
        'fiyat' => 'float',                          // ✅ REQUIRED: decimal(15,2) → float
        'baslangic_fiyati' => 'float',               // Price strategy optional value
        // Note: yayin_durumu is handled by getYayinDurumuAttribute() accessor (safe normalize)

        // SAB Phase 17B: Score Split
        'completion_score' => 'integer',
        'quality_score'    => 'float',
        'crm_only' => 'boolean',
        'fiyat_gosterim_modu' => 'string',
        'fiyat_notu' => 'string',
        'para_birimi' => 'string',                   // ✅ REQUIRED: varchar(10) → string
        'baslik' => 'string',                        // ✅ REQUIRED: varchar(255) → string
        'aciklama' => 'string',                      // ✅ REQUIRED: text → string

        // ✅ CONTEXT7: Canonical portfolio fields
        'is_active' => \App\Casts\CanonicalBooleanCast::class,
        'one_cikan' => 'boolean',                    // Context7: featured
        'display_order' => 'integer',                // Context7: display_ordering
        'metadata' => 'array',                       // JSON metadata (auto encode/decode)
        'ekstra_ozellikler' => 'array',              // T-UPS-V2-FULL: Kategori bazlı dinamik alanlar (JSON)
        'visibility_score' => 'integer',

        // ======================================================================
        // ⚠️ CONDITIONAL FIELDS - Casts
        // ======================================================================

        // Arsa İçin
        'ada_no' => 'string',                        // ⚠️ CONDITIONAL: varchar(50) → string
        'parsel_no' => 'string',                     // ⚠️ CONDITIONAL: varchar(50) → string
        'ada_parsel' => 'string',                    // ⚠️ CONDITIONAL: varchar(100) → string
        'imar_durumu' => 'string',                  // ⚠️ CONDITIONAL: varchar(100) → string
        'alan_m2' => 'float',                        // ⚠️ CONDITIONAL: decimal(12,2) → float
        'yola_cephe' => 'boolean',                   // ⚠️ CONDITIONAL: tinyint(1) → boolean
        'altyapi_elektrik' => 'boolean',             // ⚠️ CONDITIONAL: tinyint(1) → boolean
        'altyapi_su' => 'boolean',                   // ⚠️ CONDITIONAL: tinyint(1) → boolean
        'altyapi_dogalgaz' => 'boolean',             // ⚠️ CONDITIONAL: tinyint(1) → boolean
        'kaks' => 'float',                           // ⚠️ CONDITIONAL: decimal(5,2) → float
        'taks' => 'float',                           // ⚠️ CONDITIONAL: decimal(5,2) → float
        'gabari' => 'float',                         // ⚠️ CONDITIONAL: decimal(5,2) → float
        'taban_alani' => 'float',                    // ⚠️ CONDITIONAL: decimal(12,2) → float
        'yola_cephesi' => 'float',                   // ⚠️ CONDITIONAL: decimal(8,2) → float

        // 🚀 LANSMAN FIELDS - Casts
        'lansman_fiyati' => 'float',
        'lansman_bitis_tarihi' => 'datetime',
        'lansman_kotasi' => 'integer',

        // Daire/Villa İçin
        'oda_sayisi' => 'integer',                   // ⚠️ CONDITIONAL: int → integer
        'banyo_sayisi' => 'integer',                 // ⚠️ CONDITIONAL: int → integer
        'salon_sayisi' => 'integer',                 // ⚠️ CONDITIONAL: int → integer
        'net_m2' => 'float',                         // ⚠️ CONDITIONAL: decimal(10,2) → float
        'brut_m2' => 'float',                        // ⚠️ CONDITIONAL: decimal(10,2) → float
        'kat' => 'integer',                          // ⚠️ CONDITIONAL: int → integer
        'toplam_kat' => 'integer',                   // ⚠️ CONDITIONAL: int → integer
        'bina_yasi' => 'integer',                    // ⚠️ CONDITIONAL: year → integer
        'isitma' => 'string',                        // ⚠️ CONDITIONAL: varchar(255) → string
        'isinma_tipi' => 'string',                   // ⚠️ CONDITIONAL: varchar(255) → string
        'esyali' => 'boolean',                       // ⚠️ CONDITIONAL: tinyint(1) → boolean
        'site_ozellikleri' => 'array',
        'toplam_hisseli' => 'boolean',

        // Yazlık Kiralama İçin (DELETED - Moved to IlanTurizmDetail)
        // 'gunluk_fiyat' => 'float',
        // 'haftalik_fiyat' => 'float',
        // 'aylik_fiyat' => 'float',
        // 'sezonluk_fiyat' => 'float',
        // 'min_konaklama' => 'integer',
        // 'max_misafir' => 'integer',
        // 'temizlik_ucreti' => 'float',
        // 'havuz' => 'boolean',
        // 'havuz_turu' => 'string',
        // 'havuz_boyut' => 'string',
        // 'havuz_derinlik' => 'float',
        // 'sezon_baslangic' => 'date',
        // 'sezon_bitis' => 'date',
        // 'elektrik_dahil' => 'boolean',
        // 'su_dahil' => 'boolean',
        // 'bebek_uygun' => 'boolean',
        // 'cocuk_uygun' => 'boolean',
        // 'check_in_saati' => 'string',
        // 'check_out_saati' => 'string',
        // 'iptal_politikasi' => 'string',
        // 'havuz_kullanim' => 'string',

        // NEW VILLA & LAND CASTS
        'manzara_tipleri' => 'array',
        'havuz_var' => 'boolean',
        'havuz_isitmali' => 'boolean',
        'bahce_var' => 'boolean',
        'bahce_masasi_var' => 'boolean',
        'barbeku_var' => 'boolean',
        'sezlong_var' => 'boolean',
        'deniz_manzarali' => 'boolean',
        'doga_manzarali' => 'boolean',
        'dag_manzarali' => 'boolean',
        'mutfak_tam_donanmli' => 'boolean',
        'mutfak_bulasik_makinesi' => 'boolean',
        'mutfak_kahve_makinesi' => 'boolean',
        'isitma_var' => 'boolean',
        'evcil_hayvan_uygun' => 'boolean',
        'sigara_icilmez' => 'boolean',
        'kadastral_yol' => 'boolean',
        'depozito' => 'float',

        // HYBRID CORE FIELDS (English, global-hybrid standard)
        'minimum_stay' => 'integer',
        'max_stay_nights' => 'integer',
        'check_in_time' => 'string',
        'check_out_time' => 'string',
        'max_guests' => 'integer',
        'base_guest_count' => 'integer',
        'extra_guest_fee' => 'float',
        'cleaning_fee' => 'float',
        'security_deposit' => 'float',
        'booking_type' => 'string',
        'cancellation_policy' => 'string',

        // İşyeri İçin
        'isyeri_tipi' => 'string',                   // ⚠️ CONDITIONAL: varchar(255) → string
        'kira_bilgisi' => 'string',                  // ⚠️ CONDITIONAL: text → string
        'ciro_bilgisi' => 'float',                   // ⚠️ CONDITIONAL: decimal(15,2) → float
        'ruhsat_durumu' => 'string',                 // ⚠️ CONDITIONAL: varchar(255) → string
        'personel_kapasitesi' => 'integer',          // ⚠️ CONDITIONAL: int → integer
        'isyeri_cephesi' => 'integer',               // ⚠️ CONDITIONAL: int → integer

        // ======================================================================
        // 🔵 OPTIONAL FIELDS - Casts
        // ======================================================================

        // İlişkisel Alanlar
        'ilan_sahibi_id' => 'integer',               // 🔵 OPTIONAL: bigint unsigned → integer
        'ilgili_kisi_id' => 'integer',               // 🔵 OPTIONAL: bigint unsigned → integer
        'danisman_id' => 'integer',                  // 🔵 OPTIONAL: bigint unsigned → integer
        'user_id' => 'integer',                      // 🔵 OPTIONAL: bigint unsigned → integer
        'kategori_id' => 'integer',                  // 🔵 OPTIONAL: bigint unsigned → integer (legacy)
        'proje_id' => 'integer',                     // 🔵 OPTIONAL: bigint unsigned → integer
        'ulke_id' => 'integer',                      // 🔵 OPTIONAL: bigint unsigned → integer
        'il_id' => 'integer',                        // 🔵 OPTIONAL: bigint unsigned → integer
        'ilce_id' => 'integer',                      // 🔵 OPTIONAL: bigint unsigned → integer
        'mahalle_id' => 'integer',                   // 🔵 OPTIONAL: bigint unsigned → integer
        'ana_kategori_id' => 'integer',              // 🔵 OPTIONAL: bigint unsigned → integer
        'alt_kategori_id' => 'integer',              // 🔵 OPTIONAL: bigint unsigned → integer
        'yayin_tipi_id' => 'integer',                // 🔵 OPTIONAL: bigint unsigned → integer

        // Adres Detayları
        'adres' => 'string',                         // 🔵 OPTIONAL: varchar(255) → string
        'lat' => 'float',                            // 🔵 OPTIONAL: decimal(10,8) → float
        'lng' => 'float',                            // 🔵 OPTIONAL: decimal(11,8) → float
        'geometry_type' => 'string',                 // 🔵 OPTIONAL: enum → string (point/polygon)
        'geometry' => 'array',                       // 🔵 OPTIONAL: json → array (GeoJSON data)
        'l-atitude' => 'float',                       // 🔵 OPTIONAL: decimal(10,8) → float (legacy)
        'l-ongitude' => 'float',                      // 🔵 OPTIONAL: decimal(11,8) → float (legacy)

        // Çevresel Bilgiler (POI & Tags)
        'environment_pois' => 'array',               // 🔵 OPTIONAL: json → array (POI listesi)
        'environment_tags' => 'array',               // 🔵 OPTIONAL: json → array (Çevresel etiketler)

        // İlan Yönetimi
        'ilan_no' => 'string',                       // 🔵 OPTIONAL: varchar(255) → string
        'referans_no' => 'string',                   // 🔵 OPTIONAL: varchar(50) → string
        'dosya_adi' => 'string',                     //  LEGACY: varchar(255) → string (use referans_no)
        'slug' => 'string',                          // 🔵 OPTIONAL: varchar(255) → string
        'goruntulenme' => 'integer',                 // 🔵 OPTIONAL: int → integer

        // Anahtar Yönetimi
        'anahtar_kimde' => 'string',                 // 🔵 OPTIONAL: varchar(255) → string
        'anahtar_turu' => 'string',                  // 🔵 OPTIONAL: enum → string
        'anahtar_notlari' => 'string',               // 🔵 OPTIONAL: text → string
        'anahtar_ulasilabilirlik' => 'string',       // 🔵 OPTIONAL: varchar(100) → string
        'anahtar_ek_bilgi' => 'string',              // 🔵 OPTIONAL: varchar(255) → string

        // Medya
        'youtube_video_url' => 'string',             // 🔵 OPTIONAL: varchar(255) → string
        'sanal_tur_url' => 'string',                 // 🔵 OPTIONAL: varchar(255) → string
        'video_url' => 'string',                     // 🔵 OPTIONAL: varchar(255) → string
        'video_isleme_durumu' => 'string',          // 🔵 OPTIONAL: varchar(50) → string
        'video_last_frame' => 'integer',             // 🔵 OPTIONAL: tinyint → integer

        // TurkiyeAPI + WikiMapia Integration
        'location_type' => 'string',                 // 🔵 OPTIONAL: varchar(255) → string
        'location_data' => 'array',                  // 🔵 OPTIONAL: json → array
        'structured_data' => 'array',                 // 🔵 OPTIONAL: json → array (template-based structured data)
        'approved_at' => 'datetime',                  // 🔵 OPTIONAL: timestamp → datetime
        'nearby_places' => 'array',                  // 🔵 OPTIONAL: json → array
        'wikimapia_place_id' => 'string',            // 🔵 OPTIONAL: varchar(255) → string
        'environmental_scores' => 'array',           // 🔵 OPTIONAL: json → array
        'price_text' => 'string',                    // 🔵 OPTIONAL: varchar(255) → string

        // Portal Entegrasyonları
        'sahibinden_id' => 'string',                 // 🔵 OPTIONAL: varchar(50) → string
        'emlakjet_id' => 'string',                   // 🔵 OPTIONAL: varchar(50) → string
        'hepsiemlak_id' => 'string',                 // 🔵 OPTIONAL: varchar(50) → string
        'zingat_id' => 'string',                     // 🔵 OPTIONAL: varchar(50) → string
        'hurriyetemlak_id' => 'string',              // 🔵 OPTIONAL: varchar(50) → string
        'portal_senkronizasyon_durumu' => 'array',             // 🔵 OPTIONAL: json → array
        'portal_pricing' => 'array',                 // 🔵 OPTIONAL: json → array
        'ai_metadata' => 'array',                    // 🔵 OPTIONAL: json → array (Vision/NLP)
        'quality_score' => 'float',                  // 🔵 OPTIONAL: float (Vision)

        // [YALIHAN_REPORTING_0206]
        'rapor_gecersiz_mi' => 'boolean',
        'rapor_uretildi_at' => 'datetime',
        'rapor_gecersizlestirildi_at' => 'datetime',
        'rapor_surum' => 'integer',
        'visibility_score' => 'integer',

        // Investor & ROI Casts
        'purchase_price'             => 'float',
        'operating_expenses_annual' => 'float',
        'investor_target_roi'       => 'float',
        'source_locale'             => 'string',
    ];

    // ======================================================================
    // İLİŞKİLER (RELATIONSHIPS)
    // ======================================================================

    /**
     * İlanın sahibini (Mülk Sahibi) döndürür.
     * Context7-Hybrid: Null-safe with default values
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
     * Context7-Hybrid: Null-safe with default values
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
     * ✅ SAB: yayin_tipi_id → YayinTipiSablonu tablosundan (yayin_tipi_sablonlari)
     * ⚠️ DEPRECATED: Eski sistem (ilan_kategorileri seviye=2) artık kullanılmıyor
     */
    public function yayinTipi(): BelongsTo
    {
        // ✅ SAB: yayin_tipi_sablonlari tablosunu kullan
        return $this->belongsTo(\App\Models\YayinTipiSablonu::class, 'yayin_tipi_id')
                    ->withDefault(['ad' => '-', 'slug' => 'belirsiz']);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Demirbaşlar ilişkisi (pivot)
     * ⚠️ DISABLED: ilan_demirbas pivot tablosu henüz oluşturulmadı.
     * Migration çalıştırılmadan bu ilişki kullanılamaz.
     *
     * @throws \RuntimeException
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
        return $this->hasMany(\App\Models\ListingTranslation::class, 'listing_id');
    }

    /**
     * Alanın yerelleştirilmiş değerini döndürür.
     */
    public function getLocalized(string $field): string
    {
        return app(\App\Services\AITranslation\TranslationFallbackService::class)->getLocalized($this, $field);
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
     * Belirli tarih aralığında uygun olan ilanları getir
     * Context7 (IX-005): Vacation Rental Availability Logic
     *
     * @param  string  $startDate  YYYY-MM-DD
     * @param  string  $endDate  YYYY-MM-DD
     * @return Builder
     */
    public function scopeAvailable(Builder $query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            // 1. MUST HAVE: Active pricing for the period (at least partial overlap)
            $q->whereHas('yazlikFiyatlandirma', function ($subQ) use ($startDate, $endDate) {
                $subQ->where('is_active', true)
                    ->where('baslangic_tarihi', '<=', $endDate)
                    ->where('bitis_tarihi', '>=', $startDate);
            })
            // 2. MUST NOT HAVE: Inactive/Blocked periods for the range
                ->whereDoesntHave('yazlikFiyatlandirma', function ($subQ) use ($startDate, $endDate) {
                    $subQ->where('is_active', false)
                        ->where('baslangic_tarihi', '<=', $endDate)
                        ->where('bitis_tarihi', '>=', $startDate);
                });
        });
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
        return $this->hasMany(Event::class)->active(); // context7-ignore
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
        return $this->hasMany(Season::class)->active(); // context7-ignore
    }

    /**
     * Yazlık rezervasyonları
     * Context7: Yazlık kiralama sistemi için rezervasyon ilişkisi
     */
    public function yazlikRezervasyonlar(): HasMany
    {
        return $this->hasMany(YazlikRezervasyon::class);
    }

    /**
     * RAG / Vector Embedding
     * Context7: AI semantic search integration
     */
    public function embedding(): HasOne
    {
        return $this->hasOne(IlanEmbedding::class);
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
     * Not: Bu danisman() ile aynı ilişki, tutarlılık için danisman() kullanın
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
        // ℹ️ Technical Note: listing_feature ve ilan_feature tabloları birleştirilecek.
        // Veri migration gerektirir, ayrı bir sprint olarak planlanacak.
        return $this->belongsToMany(Feature::class, 'ilan_feature', 'ilan_id', 'feature_id')
            ->withPivot('value')
            ->withTimestamps();
    }

    /**
     * Features relationship (English alias for ozellikler)
     * Context7: English naming standard
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
     * Context7: İlanı favorileyen kullanıcılar (BelongsToMany - ilan_favorileri pivot)
     *
     * T-FAV-01 FIX (2026-06-24): ilan_favorileri tablosunda user_id → users FK var,
     * kisi_id kolonu mevcut değil. Bu nedenle pivot User modeline bağlanır.
     * Kisi bazlı favori için favorilenKisilerViaUser() kullanın.
     *
     * Pivot kolon: aktiflik_durumu (kanonik — ilan_favorileri.aktiflik_durumu)
     */
    public function favorilenKullanicilar(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ilan_favorileri', 'ilan_id', 'user_id')
            ->withTimestamps()
            ->withPivot('aktiflik_durumu')
            ->wherePivot('aktiflik_durumu', 1);
    }

    /**
     * Context7: İlanı favorileyen kişiler — kisiler.user_id bridge üzerinden.
     *
     * T-FAV-01 FIX (2026-06-24): ilan_favorileri pivot'unda user_id → users FK var,
     * kisi_id kolonu mevcut değil. kisiler.user_id alanı üzerinden Kisi'ye ulaşılır.
     *
     * @return \Illuminate\Database\Eloquent\Collection<Kisi>
     */
    public function favorilenKisiler(): \Illuminate\Database\Eloquent\Collection
    {
        $userIds = \Illuminate\Support\Facades\DB::table('ilan_favorileri')
            ->where('ilan_id', $this->id)
            ->where('aktiflik_durumu', 1)
            ->pluck('user_id');

        return Kisi::whereIn('user_id', $userIds)->orderBy('id')->get();
    }

    /**
     * Context7: Tüm favori kullanıcı ilişkileri (aktif ve pasif)
     * Pivot kolon: aktiflik_durumu (kanonik — ilan_favorileri.aktiflik_durumu)
     */
    public function tumFavorileri(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'ilan_favorileri', 'ilan_id', 'user_id')
            ->withTimestamps()
            ->withPivot('aktiflik_durumu');
    }


    /**
     * Context7-Hybrid: Taslak durumu kontrolü
     *
     * @return bool İlan taslak ise true
     */
    public function getTaslakAttribute(): bool
    {
        return in_array($this->yayin_durumu, ['taslak', 'Draft'], true);
    }

    /**
     * Context7-Hybrid: AI işlem durumu
     *
     * @return bool AI tarafından işlendiyse true
     */
    public function getIslendiAttribute(): bool
    {
        // Check if AI processing fields are set
        return ! empty($this->aciklama) &&
            strlen($this->aciklama) > 50 &&
            ! is_null($this->slug);
    }

    /**
     * Context7-Hybrid: Drive klasör adı accessor
     * İlan detay sayfası ve listelerde 📂 butonu için kullanılır
     *
     * Format: YE-SAT-YALKVK-DAİRE-001234 - Yalıkavak - Daire - Ahmet Yılmaz
     */
    public function getDriveFolderNameAttribute(): string
    {
        return app(\App\Services\IlanReferansService::class)
            ->generateDriveFolderName($this);
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
    public function propertyAvailabilities(): \Illuminate\Database\Eloquent\Relations\HasMany
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
     * Table: ilan_turizm_details
     */
    public function turizmDetail()
    {
        return $this->hasOne(IlanTurizmDetail::class, 'ilan_id');
    }

    /**
     * İlanın arsa detaylarını döndürür.
     * Table: ilan_arsa_details
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
     * AUTO-DETAILER: Ensure detail table records exist
     *
     * Context7: Prevents Cortex AI from returning 0% by guaranteeing
     * that every listing has its category-specific detail record.
     *
     * This method is called automatically after save() in IlanCrudService.
     */
    public function ensureDetailTableExists(): void
    {
        if (! $this->id || ! $this->anaKategori) {
            return; // Cannot create detail without ID and category
        }

        $kategoriSlug = strtolower($this->anaKategori->slug ?? '');

        // Yazlık kategorisi için
        if ($kategoriSlug === 'yazlık' || $kategoriSlug === 'yazlik') {
            // IlanTurizmDetail (primary detail table)
            $this->turizmDetail()->firstOrCreate(
                ['ilan_id' => $this->id],
                [
                    'check_in_saati' => $this->check_in_time ?? '14:00',
                    'check_out_saati' => $this->check_out_time ?? '11:00',
                    'min_konaklama' => $this->minimum_stay ?? 1,
                    'max_misafir' => $this->max_guests ?? 2,
                    'gunluk_fiyat' => $this->fiyat ?? 0,
                    'temizlik_ucreti' => $this->cleaning_fee ?? 0,
                    'havuz_var' => false,
                ]
            );

            // YazlikDetail (legacy support)
            $this->yazlikDetail()->firstOrCreate(
                ['ilan_id' => $this->id],
                [
                    'min_konaklama' => $this->minimum_stay ?? 1,
                    'max_misafir' => $this->max_guests ?? 2,
                    'temizlik_ucreti' => $this->cleaning_fee ?? 0,
                    'havuz' => false,
                    'gunluk_fiyat' => $this->fiyat ?? 0,
                ]
            );
        }

        // Arsa kategorisi için (prefix match — arsa, arsa-arazi, vb.)
        if (str_starts_with($kategoriSlug, 'arsa')) {
            $this->arsaDetail()->firstOrCreate(
                ['ilan_id' => $this->id],
                [
                    'ada_no' => null,
                    'parsel_no' => null,
                    'imar_durumu' => 'Belirsiz',
                    'kaks' => 0,
                    'taks' => 0,
                ]
            );
        }

        // Ticari kategorisi için (future-proof)
        if ($kategoriSlug === 'ticari' || $kategoriSlug === 'isyeri') {
            // IlanTicariDetail ilişkisi varsa
            if (method_exists($this, 'ticariDetail')) {
                $this->ticariDetail()->firstOrCreate(
                    ['ilan_id' => $this->id],
                    [
                        'isyeri_tipi' => null,
                        'kira_bilgisi' => null,
                    ]
                );
            }
        }
    }

    // ======================================================================
    // HYBRID FIELD BRIDGES (TR ↔ EN) - Backward compatible accessors/mutators
    // ======================================================================

    // minimum_stay ↔ min_konaklama
    public function getMinimumStayAttribute()
    {
        return $this->attributes['minimum_stay'] ?? $this->attributes['min_konaklama'] ?? 1;
    }

    public function setMinimumStayAttribute($value): void
    {
        $this->attributes['minimum_stay'] = $value;
    }

    public function setMinKonaklamaAttribute($value): void
    {
        $this->attributes['minimum_stay'] = $value;
    }

    // check_in_time ↔ check_in_saati
    public function getCheckInTimeAttribute()
    {
        return $this->attributes['check_in_time'] ?? $this->attributes['check_in_saati'] ?? '14:00';
    }

    public function setCheckInTimeAttribute($value): void
    {
        $this->attributes['check_in_time'] = $value;
    }

    public function setCheckInSaatiAttribute($value): void
    {
        $this->attributes['check_in_time'] = $value;
    }

    // check_out_time ↔ check_out_saati
    public function getCheckOutTimeAttribute()
    {
        return $this->attributes['check_out_time'] ?? $this->attributes['check_out_saati'] ?? '11:00';
    }

    public function setCheckOutTimeAttribute($value): void
    {
        $this->attributes['check_out_time'] = $value;
    }

    public function setCheckOutSaatiAttribute($value): void
    {
        $this->attributes['check_out_time'] = $value;
    }

    // max_guests ↔ max_misafir
    public function getMaxGuestsAttribute()
    {
        return $this->attributes['max_guests'] ?? $this->attributes['max_misafir'] ?? null;
    }

    public function setMaxGuestsAttribute($value): void
    {
        $this->attributes['max_guests'] = $value;
    }

    public function setMaxMisafirAttribute($value): void
    {
        $this->attributes['max_guests'] = $value;
        Log::warning('Context7: Turkish model field set (max_misafir). Mapped to max_guests.', [
            'model' => 'Ilan', 'id' => $this->attributes['id'] ?? null
        ]);
    }

    // cleaning_fee ↔ temizlik_ucreti
    public function getCleaningFeeAttribute()
    {
        return $this->attributes['cleaning_fee'] ?? $this->attributes['temizlik_ucreti'] ?? 0;
    }

    public function setCleaningFeeAttribute($value): void
    {
        $this->attributes['cleaning_fee'] = $value;
    }

    public function setTemizlikUcretiAttribute($value): void
    {
        $this->attributes['cleaning_fee'] = $value;
        Log::warning('Context7: Turkish model field set (temizlik_ucreti). Mapped to cleaning_fee.', [
            'model' => 'Ilan', 'id' => $this->attributes['id'] ?? null
        ]);
    }

    // maximum_stay → max_stay_nights (API alias → DB column)
    public function getMaximumStayAttribute()
    {
        return $this->max_stay_nights;
    }

    public function setMaximumStayAttribute($value): void
    {
        $this->attributes['max_stay_nights'] = $value;
    }

    // cancellation_policy ↔ iptal_politikasi
    public function getCancellationPolicyAttribute()
    {
        $v = $this->getAttributeFromArray('cancellation_policy') ?? $this->attributes['cancellation_policy'] ?? null;

        $legacyVal = $this->getAttributeFromArray('iptal_politikasi') ?? $this->attributes['iptal_politikasi'] ?? null;
        return $v !== null ? $v : $legacyVal;
    }

    public function setCancellationPolicyAttribute($value): void
    {
        $this->attributes['cancellation_policy'] = $value;
        $this->attributes['iptal_politikasi'] = $value;
    }

    public function setIptalPolitikasiAttribute($value): void
    {
        $this->attributes['iptal_politikasi'] = $value;
        $this->attributes['cancellation_policy'] = $value;
        Log::warning('Context7: Turkish model field set (iptal_politikasi). Prefer cancellation_policy.', [
            'model' => 'Ilan', 'id' => $this->attributes['id'] ?? null
        ]);
    }

    // ❌ DEPRECATED: documents relationship (2026-01-29) — IlanDocument ghost, tablo yok
    // B-006 P5D: dead import temizlendi

    // ======================================================================
    // ERİŞİMCİLER & DEĞİŞTİRİCİLER (ACCESSORS & MUTATORS)
    // ======================================================================

    /**
     * Kapak fotoğrafını döndürür.
     */
    public function getKapakFotografiAttribute()
    {
        return $this->fotograflar()->where('kapak_fotografi', true)->first() ?? $this->fotograflar()->first();
    }

    /**
     * Günlük görüntülenme istatistikleri (Phase 19.3)
     */
    public function goruntulenmeler(): HasMany
    {
        return $this->hasMany(IlanGoruntulenmeGunluk::class, 'ilan_id');
    }

    public function metinler(): HasMany
    {
        return $this->hasMany(IlanMetin::class, 'ilan_id');
    }

    /**
     * Kısa referans numarası (Müşteri için - Frontend)
     *
     * Format: Son 3 hane, 0 ile doldurulmuş
     * Örnek: 001, 234, 567
     *
     * Gemini AI Önerisi: Müşteri tarafında kısa, danışman arama yapınca bulur
     * Context7: REFNOMAT İK Sistemi
     */
    public function getKisaReferansAttribute(): string
    {
        if (! $this->referans_no) {
            return '';
        }

        // YE-SAT-YALKVK-DAİRE-001234 → 234
        $parts = explode('-', $this->referans_no);
        $siraNo = end($parts);

        // Son 3 haneyi al ve 0 ile doldur
        return str_pad(substr($siraNo, -3), 3, '0', STR_PAD_LEFT);
        // Sonuç: 001, 234, 567
    }

    /**
     * Orta referans numarası (Danışman için - Hover/Tooltip)
     *
     * Format: Ref No: 001 Lokasyon Kategori Site (Mal Sahibi)
     * Örnek: Ref No: 001 Yalıkavak Satılık Daire Ülkerler Sitesi (Ahmet Yılmaz)
     *
     * Gemini AI Önerisi: Danışman hover'da görür, kopyalar
     * Yalıhan Bekçi: Frontend görünüm için optimize edilmiş format
     */
    public function getOrtaReferansAttribute(): string
    {
        $parts = [];

        // Kısa referans
        $parts[] = 'Ref No: '.$this->kisa_referans;

        // Lokasyon
        if ($this->mahalle && is_object($this->mahalle)) {
            $parts[] = $this->mahalle->mahalle_adi;
        } elseif ($this->ilce && is_object($this->ilce)) {
            $parts[] = $this->ilce->ilce_adi;
        }

        // Yayın Tipi
        if ($this->yayinTipi) {
            $parts[] = $this->yayinTipi->name;
        }

        if ($this->altKategori) {
            $parts[] = $this->altKategori->name;
        } elseif ($this->anaKategori) {
            $parts[] = $this->anaKategori->name;
        }

        // Site
        if ($this->site) {
            $parts[] = $this->site->name;
        }

        // Mal Sahibi (Parantez içinde)
        if ($this->ilanSahibi) {
            // Context7: Kisi model'de first_name/last_name kullanılmalı ama legacy 'ad/soyad' var
            $firstName = $this->ilanSahibi->first_name ?? $this->ilanSahibi->ad ?? '';
            $lastName = $this->ilanSahibi->last_name ?? $this->ilanSahibi->soyad ?? '';
            $sahip = trim($firstName.' '.$lastName);
            $parts[] = "({$sahip})";
        }

        return implode(' ', array_filter($parts));
    }

    /**
     * Uzun referans numarası (Sistem için - Dosya Adı)
     *
     * Format: Ref YE-SAT-YALKVK-DAİRE-001234 - Yalıkavak Satılık...
     *
     * Gemini AI Önerisi: Dosya oluşturma ve arşivleme için
     * Context7: REFNOMATİK tam format
     */
    public function getUzunReferansAttribute(): string
    {
        // Context7: dosya_adi legacy field, referans_no kullan
        return $this->referans_no ?? $this->dosya_adi ?? '';
    }

    /**
     * Tam adres metnini oluşturur.
     */
    public function getTamAdresAttribute(): string
    {
        $adresParcalari = [
            $this->mahalle->mahalle_adi ?? null,
            $this->ilce->ilce_adi ?? null,
            $this->il->il_adi ?? null,
            $this->ulke->ulke_adi ?? null,
        ];

        return implode(', ', array_filter($adresParcalari));
    }

    /**
     * Owner private data (encrypted JSON)
     * { desired_price_min, desired_price_max, notes }
     */
    public function getOwnerPrivateDataAttribute(): array
    {
        $enc = $this->owner_private_encrypted ?? null;
        if (! $enc) {
            return [];
        }
        try {
            $json = Crypt::decryptString($enc);
            $arr = json_decode($json, true);

            return is_array($arr) ? $arr : [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    public function setOwnerPrivateDataAttribute($value): void
    {
        try {
            $json = json_encode($value ?? [], JSON_UNESCAPED_UNICODE);
            $this->attributes['owner_private_encrypted'] = Crypt::encryptString($json);
        } catch (\Throwable $e) {
            $this->attributes['owner_private_encrypted'] = null;
        }
    }

    // ======================================================================
    // KAPSAMLAR (SCOPES)
    // ======================================================================

    /**
     * Yayında olan ilanları getir
     * Context7: Direct column reference, no obfuscation
     */
    public function scopeWhereYayinda($query)
    {
        return $query->whereIn('yayin_durumu', ['yayinda', IlanDurumu::YAYINDA->value]);
    }

    /**
     * Aktif ilanları getiren scope.
     * Context7: Direct column reference, no obfuscation
     */
    public function scopeActive($query)
    {
        return $query->whereIn('yayin_durumu', ['yayinda', IlanDurumu::YAYINDA->value]);
    }

    /**
     * Onay bekleyen ilanlar
     * Context7: Direct column reference, no obfuscation
     */
    public function scopePending($query)
    {
        return $query->whereIn('yayin_durumu', ['beklemede', 'onay_bekliyor', 'Beklemede']);
    }

    /**
     * Public ilanlar (CRM-only olmayan, yayında)
     * Context7: Direct column reference, no obfuscation
     */
    public function scopePublic($query)
    {
        return $query->where('crm_only', false)
            ->whereIn('yayin_durumu', ['yayinda', IlanDurumu::YAYINDA->value]);
    }

    /**
     * Belirli bir kategoriye ait ilanları getiren scope.
     */
    public function scopeKategoriyeGore($query, $kategoriId)
    {
        return $query->where('ana_kategori_id', $kategoriId)
            ->orWhere('alt_kategori_id', $kategoriId);
    }

    /**
     * Ana kategoriye göre filtreleme scope'u
     * Context7: Ana kategori ile ilanları getirir
     */
    public function scopeAnaKategoriyeGore($query, $kategoriId)
    {
        return $query->where('ana_kategori_id', $kategoriId);
    }

    /**
     * Alt kategoriye göre filtreleme scope'u
     * Context7: Alt kategori ile ilanları getirir
     */
    public function scopeAltKategoriyeGore($query, $kategoriId)
    {
        return $query->where('alt_kategori_id', $kategoriId);
    }

    /**
     * Yayın tipine göre filtreleme scope'u
     * Context7: Yayın tipi ile ilanları getirir
     */
    public function scopeYayinTipineGore($query, $yayinTipiId)
    {
        return $query->where('yayin_tipi_id', $yayinTipiId);
    }

    /**
     * Sadece fırsat mühru olan ilanları getir
     * Context7: Opportunity Engine tarafından mühürlenmiş ilanlar
     *
     * [PROSES_MÜHRÜ: YALIHAN_OPPORTUNITY_0206]
     */
    public function scopeOnlyFirsatlar($query)
    {
        return $query->where('firsat_mühru', true);
    }

    /**
     * Ana ve alt kategoriye göre filtreleme scope'u
     * Context7: Hem ana hem alt kategori ile ilanları getirir
     */
    public function scopeKategoriHiyerarsisineGore($query, $anaKategoriId, $altKategoriId = null)
    {
        $query->where('ana_kategori_id', $anaKategoriId);

        if ($altKategoriId) {
            $query->where('alt_kategori_id', $altKategoriId);
        }

        return $query;
    }

    public function scopeSort(
        Builder $query,
        ?string $sortBy = null,
        string $sortDirection = 'desc',
        string $defaultSort = 'created_at'
    )
    {
        $sortBy = $sortBy ?: $defaultSort;
        $dir = strtolower($sortDirection) === 'asc' ? 'asc' : 'desc';
        $query->reorder();
        if ($sortBy === 'fiyat') {
            try {
                $driver = \Illuminate\Support\Facades\DB::getDriverName();
            } catch (\Throwable $e) {
                $driver = 'mysql';
            }
            if ($driver === 'sqlite') {
                if ($dir === 'desc') {
                    $query->orderByRaw('(0 + fiyat) DESC'); // context7-ignore
                } else {
                    $query->orderByRaw('(0 + fiyat) ASC'); // context7-ignore
                }
                $query->orderBy($defaultSort, $dir); // context7-ignore
                $query->orderBy('id', $dir); // context7-ignore
            } else {
                if ($dir === 'desc') {
                    $query->orderByRaw('(0 + fiyat) DESC'); // context7-ignore
                } else {
                    $query->orderByRaw('(0 + fiyat) ASC'); // context7-ignore
                }
                $query->orderBy($defaultSort, $dir); // context7-ignore
                $query->orderBy('id', $dir); // context7-ignore
            }

            return $query;
        }
        if ($this->getConnection()->getSchemaBuilder()->hasColumn($this->getTable(), $sortBy)) {
            return $query->orderBy($sortBy, $dir); // context7-ignore
        }

        return $query->orderByDesc($defaultSort); // context7-ignore
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            if (empty($model->slug) && ! empty($model->baslik)) {
                $model->slug = Str::slug($model->baslik.'-'.uniqid());
            }
        });
        static::updating(function ($model) {
            if (empty($model->slug) && ! empty($model->baslik)) {
                $model->slug = Str::slug($model->baslik.'-'.uniqid());
            }
        });
    }

    /**
     * Activity Log Configuration
     * Architectural Enhancement: Audit trail for all listing changes
     */
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly([
                'baslik',
                'fiyat',
                'yayin_durumu',
                'il_id',
                'ilce_id',
                'mahalle_id',
                'danisman_id',
                'ana_kategori_id',
                'alt_kategori_id',
            ])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn (string $eventName) => "İlan {$eventName}")
            ->useLogName('ilanlar');
    }

    /**
     * The "booted" method of the model.
     */
    protected static function booted(): void
    {
        static::addGlobalScope('visibility', function (\Illuminate\Database\Eloquent\Builder $builder) {
            // Default ordering by visibility_score descending
            $builder->orderBy('visibility_score', 'desc'); // context7-ignore
        });

        // SAB Phase 17B: Minimum Tracking
        static::creating(function ($ilan) {
            if (auth()->check()) {
                $ilan->created_by = auth()->id();
                $ilan->updated_by = auth()->id();
            }
        });

        static::updating(function ($ilan) {
            if (auth()->check()) {
                $ilan->updated_by = auth()->id();
            }
        });
    }

    /**
     * CONTEXT7: Visibility score ve display_order'a göre sırala
     * Hardened: Deterministic tie-breaker (id DESC) added.
     */
    public function scopeRanked($query)
    {
        return $query->orderByDesc('visibility_score') // context7-ignore
            ->orderBy('display_order') // context7-ignore
            ->orderByDesc('id'); // context7-ignore
    }

    public function iletisimler(): HasMany
    {
        // Phase 19.3: Read-only analytics bridge to leads
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
}
