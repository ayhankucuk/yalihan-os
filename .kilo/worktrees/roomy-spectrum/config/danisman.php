<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Danışman Position (Pozisyon) Seçenekleri
    |--------------------------------------------------------------------------
    | Türk emlak sektöründeki yaygın pozisyonlar.
    | Sıralama: Giriş seviyesinden yönetim pozisyonlarına doğru
    */
    'positions' => [
        'danisman' => 'Danışman',
        'asistan' => 'Asistan',
        'broker' => 'Broker',
    ],

    /*
    |--------------------------------------------------------------------------
    | Danışman Department (Departman) Seçenekleri
    |--------------------------------------------------------------------------
    | Türk emlak sektöründeki yaygın departmanlar.
    | Kategoriler: Operasyonel, Fonksiyonel, Yönetim
    */
    'departments' => [
        // Operasyonel Departmanlar
        'konut_satis' => 'Konut Satış',
        'konut_kiralama' => 'Konut Kiralama',
        'arsa_satis' => 'Arsa Satış',
        'isyeri_satis' => 'İşyeri Satış',
        'isyeri_kiralama' => 'İşyeri Kiralama',
        'yazlik_satis' => 'Yazlık Satış',
        'yazlik_kiralama' => 'Yazlık Kiralama',
        'turistik_tesis' => 'Turistik Tesis',
        'rezidans' => 'Rezidans',
        'luks_emlak' => 'Lüks Emlak',
        'endustriyel_emlak' => 'Endüstriyel Emlak',
        'tarihi_emlak' => 'Tarihi Emlak',

        // Özel Hizmetler
        'yatirim_danismanligi' => 'Yatırım Danışmanlığı',
        'degerleme' => 'Değerleme',
        'proje_gelistirme' => 'Proje Geliştirme',
        'portfoy_yonetimi' => 'Portföy Yönetimi',

        // Destek Departmanları
        'musteri_hizmetleri' => 'Müşteri Hizmetleri',
        'pazarlama' => 'Pazarlama',
        'dijital_pazarlama' => 'Dijital Pazarlama',
        'iletisim' => 'İletişim',
        'insan_kaynaklari' => 'İnsan Kaynakları',
        'finans' => 'Finans',
        'muhasebe' => 'Muhasebe',
        'hukuk' => 'Hukuk',
        'teknoloji' => 'Teknoloji',
        'operasyon' => 'Operasyon',

        // Yönetim
        'yonetim' => 'Yönetim',
        'strateji' => 'Strateji',
    ],

    /*
    |--------------------------------------------------------------------------
    | Uzmanlık Alanları
    |--------------------------------------------------------------------------
    | Danışmanların uzmanlaşabileceği gayrimenkul kategorileri.
    | Çoklu seçim yapılabilir.
    */
    'uzmanlik_alanlari' => [
        'Konut',
        'Arsa',
        'İşyeri',
        'Yazlık',
        'Turistik Tesis',
    ],

    /*
    |--------------------------------------------------------------------------
    | Durum (Status) Seçenekleri
    |--------------------------------------------------------------------------
    | Danışman ve İlan statusları için standart seçenekler.
    | Context7 Compliance: Tüm statuslar tutarlı olmalı
    */
    'status_options' => [
        'taslak' => 'Taslak',
        'onay_bekliyor' => 'Onay Bekliyor',
        'aktif' => 'Aktif',
        'satildi' => 'Satıldı',
        'kiralandi' => 'Kiralandı',
        'pasif' => 'Pasif',
        'arsivlendi' => 'Arşivlendi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Durum Renkleri ve Stilleri
    |--------------------------------------------------------------------------
    | Status badge component için renk tanımlamaları
    */
    'status_colors' => [
        'taslak' => [
            'bg' => 'bg-gray-100 dark:bg-gray-900',
            'text' => 'text-gray-800 dark:text-gray-200',
            'label' => 'Taslak',
        ],
        'onay_bekliyor' => [
            'bg' => 'bg-yellow-100 dark:bg-yellow-900',
            'text' => 'text-yellow-800 dark:text-yellow-200',
            'label' => 'Onay Bekliyor',
        ],
        'aktif' => [
            'bg' => 'bg-green-100 dark:bg-green-900',
            'text' => 'text-green-800 dark:text-green-200',
            'label' => 'Aktif',
        ],
        'satildi' => [
            'bg' => 'bg-blue-100 dark:bg-blue-900',
            'text' => 'text-blue-800 dark:text-blue-200',
            'label' => 'Satıldı',
        ],
        'kiralandi' => [
            'bg' => 'bg-purple-100 dark:bg-purple-900',
            'text' => 'text-purple-800 dark:text-purple-200',
            'label' => 'Kiralandı',
        ],
        'pasif' => [
            'bg' => 'bg-red-100 dark:bg-red-900',
            'text' => 'text-red-800 dark:text-red-200',
            'label' => 'Pasif',
        ],
        'arsivlendi' => [
            'bg' => 'bg-gray-200 dark:bg-gray-800',
            'text' => 'text-gray-700 dark:text-gray-200',
            'label' => 'Arşivlendi',
        ],
    ],
];
