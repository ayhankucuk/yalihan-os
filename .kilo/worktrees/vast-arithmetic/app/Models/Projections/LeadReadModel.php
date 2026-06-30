<?php

namespace App\Models\Projections;

use App\Models\BaseModel;
use App\Traits\HasCountryScope;

/**
 * Class LeadReadModel
 *
 * Eloquent model for denormalized Leads read model.
 *
 * @package App\Models\Projections
 */
class LeadReadModel extends BaseModel
{
    use HasCountryScope;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'leads_read_model';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'tenant_id',
        'ulke_id',
        'uuid',
        'platform',
        'platform_user_id',
        'message_text',
        'crm_durumu',
        'assigned_to',
        'contact_attempts',
        'last_contact_at',
        'converted_at',
        'aktiflik_durumu',
        'son_islenen_sira_numarasi',
        'olusturulma_zamani',
        'degistirilme_zamani',
    ];
}
