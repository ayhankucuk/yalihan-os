<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * Ilan Reservation Model
 *
 * Context7 Compliance: Rezervasyon yönetimi
 */
class IlanReservation extends BaseModel
{
    use HasFactory;
    use HasCountryScope;

    protected $table = 'property_reservations';

    protected $fillable = [
        'property_id',
        'start_date',
        'end_date',
        'nights',
        'guest_name',
        'guest_phone',
        'guest_email',
        'guest_count',
        'notes',
        'reservation_state',
        'finansal_durum',
        'depozito_tutari',
        'depozito_durumu',
        'locked_nightly_rate',
        'booking_currency',
        'booking_fx_rate',
        'booking_country_code',
        'total_amount',
        'currency',
        'created_by_user_id',
        'ulke_id',
        'cancelled_at',
        'confirmed_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'cancelled_at' => 'datetime',
        'confirmed_at' => 'datetime',
        'nights' => 'integer',
        'guest_count' => 'integer',
        'depozito_tutari' => 'float',
        'locked_nightly_rate' => 'float',
        'booking_fx_rate' => 'float',
        'total_amount' => 'float',
    ];

    /**
     * İlan ilişkisi
     */
    public function ilan()
    {
        return $this->belongsTo(Ilan::class, 'ilan_id');
    }

    /**
     * Oluşturan kullanıcı
     */
    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    /**
     * Scope: Sadece aktif rezervasyonlar
     */
    public function scopeActive($query)
    {
        // reservation_state: confirmed veya pending — iptal edilmemiş rezervasyonlar
        return $query->whereIn('reservation_state', ['confirmed', 'pending'])
                     ->whereNull('cancelled_at'); // context7-ignore
    }

    /**
     * Scope: İptal edilmiş
     */
    public function scopeCancelled($query)
    {
        return $query->whereNotNull('cancelled_at');
    }

    /**
     * Scope: Belirli ilan için
     * property_reservations tablosu property_id FK kullanır
     */
    public function scopeForIlan($query, int $ilanId)
    {
        return $query->where('property_id', $ilanId);
    }
}
