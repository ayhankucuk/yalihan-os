<?php

/**
 * Validation Rules Registry - Merkezi Validation Rules Yönetimi
 *
 * Context7 Standard: C7-VALIDATION-RULES-2025-12-06
 *
 * Tüm validation rules merkezi config'de tanımlanır.
 * Reusable validation rules ve frontend validation hints.
 *
 * ✅ Context7 Exemption: 'durum' keys in this file are VALIDATION RULE KEYS,
 * not database field references. These do not violate canonical field naming.
 * Validation keys can use any naming convention for rule organization.
 *
 * @context7-ignore-file
 * @context7-exempt validation-rule-keys
 * @version 1.0.0
 * @since 2025-12-06
 */

return [
    /*
    |--------------------------------------------------------------------------
    | User Validation Rules
    |--------------------------------------------------------------------------
    */

    'user' => [
        'store' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|string|in:superadmin,admin,danisman,editor,musteri',
            'aktiflik_durumu' => 'nullable|boolean',
        ],
        'update' => [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,{id}',
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|string|in:superadmin,admin,danisman,editor,musteri',
            'aktiflik_durumu' => 'nullable|boolean',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | İlan Validation Rules
    |--------------------------------------------------------------------------
    */

    'ilan' => [
        'store' => [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'required|string|min:50|max:2000',
            'fiyat' => 'required|numeric|min:0',
            'kategori_id' => 'required|exists:ilan_kategorileri,id',
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'yayin_durumu' => 'sometimes|string|in:taslak,yayinda,kapali',
        ],
        'update' => [
            'baslik' => 'required|string|max:255',
            'aciklama' => 'required|string|min:50|max:2000',
            'fiyat' => 'required|numeric|min:0',
            'kategori_id' => 'required|exists:ilan_kategorileri,id',
            'il_id' => 'required|exists:iller,id',
            'ilce_id' => 'required|exists:ilceler,id',
            'mahalle_id' => 'nullable|exists:mahalleler,id',
            'yayin_durumu' => 'sometimes|string|in:taslak,yayinda,kapali',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Kisi Validation Rules
    |--------------------------------------------------------------------------
    */

    'kisi' => [
        'store' => [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefon' => 'required|string|max:20',
            'danisman_id' => 'nullable|exists:users,id',
        ],
        'update' => [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'telefon' => 'required|string|max:20',
            'danisman_id' => 'nullable|exists:users,id',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Finansal İşlem Validation Rules
    |--------------------------------------------------------------------------
    */

    'finansal-islem' => [
        'store' => [
            'islem_tipi' => 'required|string|in:komisyon,odeme,masraf,gelir,gider',
            'miktar' => 'required|numeric|min:0',
            'para_birimi' => 'required|string|in:TRY,USD,EUR,GBP',
            'tarih' => 'required|date',
            'kisi_id' => 'nullable|exists:kisiler,id',
            'ilan_id' => 'nullable|exists:ilanlar,id',
            'islem_durumu' => 'sometimes|string|in:bekliyor,onaylandi,reddedildi,tamamlandi',
        ],
        'update' => [
            'islem_tipi' => 'required|string|in:komisyon,odeme,masraf,gelir,gider',
            'miktar' => 'required|numeric|min:0',
            'para_birimi' => 'required|string|in:TRY,USD,EUR,GBP',
            'tarih' => 'required|date',
            'kisi_id' => 'nullable|exists:kisiler,id',
            'ilan_id' => 'nullable|exists:ilanlar,id',
            'islem_durumu' => 'sometimes|string|in:bekliyor,onaylandi,reddedildi,tamamlandi',
        ],
    ],


    /*
    |--------------------------------------------------------------------------
    | Common Validation Rules (Reusable)
    |--------------------------------------------------------------------------
    */

    'common' => [
        'email' => 'required|email|max:255',
        'email_optional' => 'nullable|email|max:255',
        'phone' => 'required|string|max:20',
        'phone_optional' => 'nullable|string|max:20',
        'name' => 'required|string|max:255',
        'name_optional' => 'nullable|string|max:255',
        'password' => 'required|string|min:8|confirmed',
        'password_optional' => 'sometimes|nullable|string|min:8|confirmed',
        'durum' => 'sometimes|string|in:active,inactive,pending',
        'aktiflik_durumu' => 'sometimes|boolean',
        'url' => 'nullable|url|max:500',
        'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
        'date' => 'required|date',
        'date_optional' => 'nullable|date',
        'numeric_positive' => 'required|numeric|min:0',
        'numeric_optional' => 'nullable|numeric',
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Validation Hints
    |--------------------------------------------------------------------------
    |
    | Frontend'de gösterilecek validation mesajları ve hints
    |
    */

    'hints' => [
        'email' => [
            'type' => 'email',
            'required' => true,
            'message' => 'Geçerli bir email adresi giriniz',
            'placeholder' => 'ornek@email.com',
        ],
        'phone' => [
            'type' => 'tel',
            'required' => true,
            'message' => 'Geçerli bir telefon numarası giriniz',
            'placeholder' => '0555 123 45 67',
            'pattern' => '[0-9\\s\\-\\+\\(\\)]+',
        ],
        'password' => [
            'type' => 'password',
            'required' => true,
            'minLength' => 8,
            'message' => 'Şifre en az 8 karakter olmalıdır',
        ],
        'name' => [
            'type' => 'text',
            'required' => true,
            'maxLength' => 255,
            'message' => 'İsim en fazla 255 karakter olabilir',
        ],
        'fiyat' => [
            'type' => 'number',
            'required' => true,
            'min' => 0,
            'message' => 'Fiyat 0 veya daha büyük olmalıdır',
            'step' => '0.01',
        ],
    ],
];
