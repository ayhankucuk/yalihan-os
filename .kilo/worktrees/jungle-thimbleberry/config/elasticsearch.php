<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Elasticsearch Connection Settings
    |--------------------------------------------------------------------------
    |
    | Bu dosya Elasticsearch bağlantı ayarlarını içerir.
    |
    */

    'hosts' => [
        env('ELASTICSEARCH_HOST', 'localhost:9200'),
    ],

    'username' => env('ELASTICSEARCH_USERNAME', null),
    'password' => env('ELASTICSEARCH_PASSWORD', null),

    'index_prefix' => env('ELASTICSEARCH_INDEX_PREFIX', 'cursoremlak'),

    'default_index' => env('ELASTICSEARCH_DEFAULT_INDEX', 'ilanlar'),

    /*
    |--------------------------------------------------------------------------
    | Connection Options
    |--------------------------------------------------------------------------
    |
    | Elasticsearch bağlantı seçenekleri
    |
    */

    'connection' => [
        'timeout' => env('ELASTICSEARCH_TIMEOUT', 30),
        'retries' => env('ELASTICSEARCH_RETRIES', 3),
        'sniff' => env('ELASTICSEARCH_SNIFF', false),
        'ssl_verify' => env('ELASTICSEARCH_SSL_VERIFY', false),
    ],

    /*
    |--------------------------------------------------------------------------
    | Index Settings
    |--------------------------------------------------------------------------
    |
    | Elasticsearch index ayarları
    |
    */

    'indices' => [
        'ilanlar' => [
            'name' => 'ilanlar',
            'settings' => [
                'number_of_shards' => 1,
                'number_of_replicas' => 0,
                'analysis' => [
                    'analyzer' => [
                        'turkish_analyzer' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => [
                                'lowercase',
                                'turkish_stop',
                                'turkish_stemmer',
                                'asciifolding',
                            ],
                        ],
                        'turkish_search' => [
                            'type' => 'custom',
                            'tokenizer' => 'standard',
                            'filter' => [
                                'lowercase',
                                'turkish_stop',
                                'turkish_stemmer',
                                'asciifolding',
                            ],
                        ],
                    ],
                    'filter' => [
                        'turkish_stop' => [
                            'type' => 'stop',
                            'stopwords' => '_turkish_',
                        ],
                        'turkish_stemmer' => [
                            'type' => 'stemmer',
                            'language' => 'turkish',
                        ],
                    ],
                ],
            ],
            'mappings' => [
                'properties' => [
                    'ilan_basligi' => [
                        'type' => 'text',
                        'analyzer' => 'turkish_analyzer',
                        'search_analyzer' => 'turkish_search',
                        'fields' => [
                            'keyword' => [
                                'type' => 'keyword',
                                'ignore_above' => 256,
                            ],
                            'suggest' => [
                                'type' => 'completion',
                                'analyzer' => 'simple',
                            ],
                        ],
                    ],
                    'aciklama' => [
                        'type' => 'text',
                        'analyzer' => 'turkish_analyzer',
                        'search_analyzer' => 'turkish_search',
                    ],
                    'adres_detay' => [
                        'type' => 'text',
                        'analyzer' => 'turkish_analyzer',
                        'search_analyzer' => 'turkish_search',
                    ],
                    'fiyat' => [
                        'type' => 'long',
                    ],
                    'para_birimi' => [
                        'type' => 'keyword',
                    ],
                    'net_metrekare' => [
                        'type' => 'float',
                    ],
                    'brut_metrekare' => [
                        'type' => 'float',
                    ],
                    'oda_sayisi' => [
                        'type' => 'integer',
                    ],
                    'kat' => [
                        'type' => 'integer',
                    ],
                    'kat_sayisi' => [
                        'type' => 'integer',
                    ],
                    'satilik_mi' => [
                        'type' => 'boolean',
                    ],
                    // Context7: Exempt - Config key (not database field)
                    'mapping_status' => [
                        'type' => 'keyword',
                    ],
                    'is_published' => [
                        'type' => 'boolean',
                    ],
                    'created_at' => [
                        'type' => 'date',
                    ],
                    'updated_at' => [
                        'type' => 'date',
                    ],
                    'kategori' => [
                        'properties' => [
                            'id' => ['type' => 'long'],
                            'name' => ['type' => 'keyword'],
                            'slug' => ['type' => 'keyword'],
                        ],
                    ],
                    'alt_kategori' => [
                        'properties' => [
                            'id' => ['type' => 'long'],
                            'name' => ['type' => 'keyword'],
                            'slug' => ['type' => 'keyword'],
                        ],
                    ],
                    'city' => [
                        'properties' => [
                            'id' => ['type' => 'long'],
                            'ad' => ['type' => 'keyword'],
                        ],
                    ],
                    'ilce' => [
                        'properties' => [
                            'id' => ['type' => 'long'],
                            'ad' => ['type' => 'keyword'],
                        ],
                    ],
                    'mahalle' => [
                        'properties' => [
                            'id' => ['type' => 'long'],
                            'ad' => ['type' => 'keyword'],
                        ],
                    ],
                    'location' => [
                        'type' => 'geo_point',
                    ],
                    'ozellikler' => [
                        'type' => 'keyword',
                    ],
                    'dinamik_ozellikler' => [
                        'type' => 'object',
                        'dynamic' => true,
                    ],
                    'etiketler' => [
                        'type' => 'keyword',
                    ],
                    'danisman' => [
                        'properties' => [
                            'id' => ['type' => 'long'],
                            'ad' => ['type' => 'keyword'],
                        ],
                    ],
                ],
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Search Settings
    |--------------------------------------------------------------------------
    |
    | Arama ayarları
    |
    */

    'search' => [
        'default_size' => 20,
        'max_size' => 100,
        'highlight' => [
            'pre_tags' => ['<mark>'],
            'post_tags' => ['</mark>'],
            'fragment_size' => 150,
            'number_of_fragments' => 3,
        ],
        'suggestions' => [
            // Context7: Exempt - Config key (not database field)
            'enabled' => true,
            'max_suggestions' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Bulk Operations
    |--------------------------------------------------------------------------
    |
    | Toplu işlem ayarları
    |
    */

    'bulk' => [
        'batch_size' => 1000,
        'refresh_interval' => '1s',
    ],

    /*
    |--------------------------------------------------------------------------
    | Monitoring
    |--------------------------------------------------------------------------
    |
    | İzleme ayarları
    |
    */

    'monitoring' => [
        // Context7: Exempt - Config key (not database field)
        'enabled' => env('ELASTICSEARCH_MONITORING', true),
        'log_queries' => env('ELASTICSEARCH_LOG_QUERIES', false),
        'log_responses' => env('ELASTICSEARCH_LOG_RESPONSES', false),
        'performance_threshold' => env('ELASTICSEARCH_PERFORMANCE_THRESHOLD', 1000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache ayarları
    |
    */

    'cache' => [
        // Context7: Exempt - Config key (not database field)
        'enabled' => env('ELASTICSEARCH_CACHE', true),
        'ttl' => env('ELASTICSEARCH_CACHE_TTL', 300),
        'prefix' => env('ELASTICSEARCH_CACHE_PREFIX', 'es_'),
    ],
];
