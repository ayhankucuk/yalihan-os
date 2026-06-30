<?php

return [
    'storage' => [
        'provider' => env('AI_STORAGE_PROVIDER', 'local_mysql'),

        'local_mysql' => [
            'table' => 'ai_storage',
            'connection' => env('DB_CONNECTION', 'mysql'),
        ],

        'remote_mysql' => [
            'host' => env('AI_REMOTE_DB_HOST', 'localhost'),
            'database' => env('AI_REMOTE_DB_DATABASE', 'yalihan_ai'),
            'username' => env('AI_REMOTE_DB_USERNAME', 'root'),
            'password' => env('AI_REMOTE_DB_PASSWORD', ''),
            'port' => env('AI_REMOTE_DB_PORT', 3306),
        ],

        'google_drive' => [
            'credentials' => storage_path('app/google-credentials.json'),
            'folder_id' => env('AI_GOOGLE_DRIVE_FOLDER_ID'),
            'scopes' => ['https://www.googleapis.com/auth/drive'],
        ],

        'aws_s3' => [
            'credentials' => [
                'key' => env('AI_AWS_ACCESS_KEY'),
                'secret' => env('AI_AWS_SECRET_KEY'),
                'region' => env('AI_AWS_REGION', 'eu-west-1'),
            ],
            'bucket' => env('AI_AWS_BUCKET'),
            'prefix' => env('AI_AWS_PREFIX', 'ai-storage/'),
        ],
    ],

    'learning' => [
        'status' => env('AI_LEARNING_ENABLED', true),
        'auto_save' => env('AI_AUTO_SAVE', true),
        'success_threshold' => env('AI_SUCCESS_THRESHOLD', 0.7),
        'max_patterns' => env('AI_MAX_PATTERNS', 1000),
    ],

    'prompts' => [
        'templates' => [
            'form_generation' => [
                'template' => "Sen bir emlak formu uzmanısın. {category} kategorisi için {publication_type} yayın tipinde form alanları öner.\n\nKategori: {category}\nYayın Tipi: {publication_type}\n\nÖneriler:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Emlak sektörüne uygun',
                    'Kullanıcı dostu',
                    'Zorunlu alanları belirt',
                ],
            ],
            'matrix_management' => [
                'template' => "Sen bir emlak matrix uzmanısın. {category} kategorisi için field dependency matrix oluştur.\n\nKategori: {category}\nAlanlar: {fields}\n\nMatrix:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Mantıklı bağımlılıklar',
                    'AI destekli alanları belirt',
                    'Kullanıcı deneyimi odaklı',
                ],
            ],
            'suggestion_engine' => [
                'template' => "Sen bir emlak öneri uzmanısın. {context} bağlamında {input} için öneriler ver.\n\nBağlam: {context}\nGiriş: {input}\n\nÖneriler:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Pratik öneriler',
                    'Kullanıcı odaklı',
                    'Detaylı açıklama',
                ],
            ],
            'hibrit_siralama' => [
                'template' => "Sen bir emlak sıralama uzmanısın. {category} kategorisi için özellikleri önem sırasına göre sırala.\n\nKategori: {category}\nÖzellikler: {features}\n\nSıralama:",
                'rules' => [
                    'Türkçe yanıt ver',
                    'Önem sırasına göre',
                    'Kullanım sıklığına göre',
                    'AI önerilerine göre',
                ],
            ],
        ],

        'context_rules' => [
            'konut' => [
                'Alanlar: Oda sayısı, Banyo sayısı, Metrekare, Kat, Isıtma, Asansör',
                'AI Destekli: Fiyat tahmini, Özellik önerileri, Benzer ilanlar',
            ],
            'arsa' => [
                'Alanlar: Ada, Parsel, İmar statusu, KAKS, TAKS, Gabari',
                'AI Destekli: Değerleme, İmar analizi, Yatırım potansiyeli',
            ],
            'yazlik' => [
                'Alanlar: Günlük fiyat, Minimum konaklama, Havuz, Sezon',
                'AI Destekli: Fiyat optimizasyonu, Sezon analizi, Rezervasyon önerileri',
            ],
            'isyeri' => [
                'Alanlar: Metrekare, Kat, Otopark, Asansör, Lokasyon',
                'AI Destekli: Kira analizi, Lokasyon değerlendirmesi, Yatırım önerileri',
            ],
        ],
    ],

    'cache' => [
        'status' => env('AI_CACHE_ENABLED', true),
        'ttl' => env('AI_CACHE_TTL', 3600),
        'prefix' => env('AI_CACHE_PREFIX', 'ai_'),
    ],
];
