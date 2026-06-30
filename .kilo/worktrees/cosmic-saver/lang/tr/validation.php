<?php

/**
 * Validation Dil Dosyası (TR)
 * Laravel validation hata mesajlarının Türkçe karşılıkları
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Hata Mesajları
    |--------------------------------------------------------------------------
    */

    'accepted'             => ':attribute alanı kabul edilmelidir.',
    'accepted_if'          => ':other :value olduğunda :attribute alanı kabul edilmelidir.',
    'active_url'           => ':attribute geçerli bir URL olmalıdır.',
    'after'                => ':attribute :date tarihinden sonra bir tarih olmalıdır.',
    'after_or_equal'       => ':attribute :date tarihi veya sonrası olmalıdır.',
    'alpha'                => ':attribute yalnızca harf içerebilir.',
    'alpha_dash'           => ':attribute yalnızca harf, rakam, tire ve alt çizgi içerebilir.',
    'alpha_num'            => ':attribute yalnızca harf ve rakam içerebilir.',
    'array'                => ':attribute bir dizi olmalıdır.',
    'ascii'                => ':attribute yalnızca tek baytlık alfanümerik karakterler ve semboller içerebilir.',
    'before'               => ':attribute :date tarihinden önce bir tarih olmalıdır.',
    'before_or_equal'      => ':attribute :date tarihi veya öncesi olmalıdır.',
    'between'              => [
        'array'   => ':attribute :min ile :max arasında öğe içermelidir.',
        'file'    => ':attribute :min ile :max kilobayt arasında olmalıdır.',
        'numeric' => ':attribute :min ile :max arasında olmalıdır.',
        'string'  => ':attribute :min ile :max karakter arasında olmalıdır.',
    ],
    'boolean'              => ':attribute alanı yalnızca doğru veya yanlış olabilir.',
    'can'                  => ':attribute alanı yetkisiz bir değer içeriyor.',
    'confirmed'            => ':attribute onayı eşleşmiyor.',
    'contains'             => ':attribute gerekli bir değeri içermiyor.',
    'current_password'     => 'Şifre hatalı.',
    'date'                 => ':attribute geçerli bir tarih değil.',
    'date_equals'          => ':attribute :date ile aynı tarih olmalıdır.',
    'date_format'          => ':attribute :format biçimine uymuyor.',
    'decimal'              => ':attribute :decimal ondalık basamak içermelidir.',
    'declined'             => ':attribute reddedilmelidir.',
    'declined_if'          => ':other :value olduğunda :attribute reddedilmelidir.',
    'different'            => ':attribute ve :other farklı olmalıdır.',
    'digits'               => ':attribute :digits rakamdan oluşmalıdır.',
    'digits_between'       => ':attribute :min ile :max rakam arasında olmalıdır.',
    'dimensions'           => ':attribute geçersiz resim boyutlarına sahip.',
    'distinct'             => ':attribute alanı tekrar eden bir değere sahip.',
    'doesnt_end_with'      => ':attribute şu değerlerden biriyle bitmemelidir: :values.',
    'doesnt_start_with'    => ':attribute şu değerlerden biriyle başlamamalıdır: :values.',
    'email'                => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'ends_with'            => ':attribute şu değerlerden biriyle bitmelidir: :values.',
    'enum'                 => 'Seçilen :attribute geçersiz.',
    'exists'               => 'Seçilen :attribute geçersiz.',
    'extensions'           => ':attribute şu uzantılardan birine sahip olmalıdır: :values.',
    'file'                 => ':attribute bir dosya olmalıdır.',
    'filled'               => ':attribute alanı bir değere sahip olmalıdır.',
    'gt'                   => [
        'array'   => ':attribute :value öğeden fazla içermelidir.',
        'file'    => ':attribute :value kilobayttan büyük olmalıdır.',
        'numeric' => ':attribute :value değerinden büyük olmalıdır.',
        'string'  => ':attribute :value karakterden uzun olmalıdır.',
    ],
    'gte'                  => [
        'array'   => ':attribute en az :value öğe içermelidir.',
        'file'    => ':attribute en az :value kilobayt olmalıdır.',
        'numeric' => ':attribute :value veya daha büyük olmalıdır.',
        'string'  => ':attribute en az :value karakter içermelidir.',
    ],
    'hex_color'            => ':attribute geçerli bir onaltılık renk kodu olmalıdır.',
    'image'                => ':attribute bir resim olmalıdır.',
    'in'                   => 'Seçilen :attribute geçersiz.',
    'in_array'             => ':attribute alanı :other içinde mevcut değil.',
    'integer'              => ':attribute bir tam sayı olmalıdır.',
    'ip'                   => ':attribute geçerli bir IP adresi olmalıdır.',
    'ipv4'                 => ':attribute geçerli bir IPv4 adresi olmalıdır.',
    'ipv6'                 => ':attribute geçerli bir IPv6 adresi olmalıdır.',
    'json'                 => ':attribute geçerli bir JSON metni olmalıdır.',
    'list'                 => ':attribute bir liste olmalıdır.',
    'lowercase'            => ':attribute küçük harf olmalıdır.',
    'lt'                   => [
        'array'   => ':attribute :value öğeden az içermelidir.',
        'file'    => ':attribute :value kilobayttan küçük olmalıdır.',
        'numeric' => ':attribute :value değerinden küçük olmalıdır.',
        'string'  => ':attribute :value karakterden kısa olmalıdır.',
    ],
    'lte'                  => [
        'array'   => ':attribute :value öğeden fazla içermemelidir.',
        'file'    => ':attribute en fazla :value kilobayt olmalıdır.',
        'numeric' => ':attribute :value veya daha küçük olmalıdır.',
        'string'  => ':attribute en fazla :value karakter içermelidir.',
    ],
    'mac_address'          => ':attribute geçerli bir MAC adresi olmalıdır.',
    'max'                  => [
        'array'   => ':attribute :max öğeden fazla içermemelidir.',
        'file'    => ':attribute en fazla :max kilobayt olabilir.',
        'numeric' => ':attribute en fazla :max olabilir.',
        'string'  => ':attribute en fazla :max karakter olabilir.',
    ],
    'max_digits'           => ':attribute en fazla :max rakam içermelidir.',
    'mimes'                => ':attribute şu türde bir dosya olmalıdır: :values.',
    'mimetypes'            => ':attribute şu türde bir dosya olmalıdır: :values.',
    'min'                  => [
        'array'   => ':attribute en az :min öğe içermelidir.',
        'file'    => ':attribute en az :min kilobayt olmalıdır.',
        'numeric' => ':attribute en az :min olmalıdır.',
        'string'  => ':attribute en az :min karakter olmalıdır.',
    ],
    'min_digits'           => ':attribute en az :min rakam içermelidir.',
    'missing'              => ':attribute alanı eksik olmalıdır.',
    'missing_if'           => ':other :value olduğunda :attribute alanı eksik olmalıdır.',
    'missing_unless'       => ':other :value olmadığı sürece :attribute alanı eksik olmalıdır.',
    'missing_with'         => ':values mevcut olduğunda :attribute alanı eksik olmalıdır.',
    'missing_with_all'     => ':values mevcut olduğunda :attribute alanı eksik olmalıdır.',
    'multiple_of'          => ':attribute :value değerinin katı olmalıdır.',
    'not_in'               => 'Seçilen :attribute geçersiz.',
    'not_regex'            => ':attribute geçersiz biçimde.',
    'numeric'              => ':attribute bir sayı olmalıdır.',
    'password'             => [
        'letters'       => ':attribute en az bir harf içermelidir.',
        'mixed'         => ':attribute en az bir büyük ve bir küçük harf içermelidir.',
        'numbers'       => ':attribute en az bir rakam içermelidir.',
        'symbols'       => ':attribute en az bir sembol içermelidir.',
        'uncompromised' => 'Girilen :attribute veri sızıntısında görünmüştür. Lütfen farklı bir :attribute seçin.',
    ],
    'present'              => ':attribute alanı mevcut olmalıdır.',
    'present_if'           => ':other :value olduğunda :attribute alanı mevcut olmalıdır.',
    'present_unless'       => ':other :value olmadığı sürece :attribute alanı mevcut olmalıdır.',
    'present_with'         => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
    'present_with_all'     => ':values mevcut olduğunda :attribute alanı mevcut olmalıdır.',
    'prohibited'           => ':attribute alanı yasaktır.',
    'prohibited_if'        => ':other :value olduğunda :attribute alanı yasaktır.',
    'prohibited_unless'    => ':other şunlardan biri olmadığı sürece :attribute alanı yasaktır: :values.',
    'prohibits'            => ':attribute alanı :other alanının mevcut olmasını engelliyor.',
    'regex'                => ':attribute biçimi geçersiz.',
    'required'             => ':attribute alanı zorunludur.',
    'required_array_keys'  => ':attribute alanı şu anahtarları içermelidir: :values.',
    'required_if'          => ':other :value olduğunda :attribute alanı zorunludur.',
    'required_if_accepted' => ':other kabul edildiğinde :attribute alanı zorunludur.',
    'required_if_declined' => ':other reddedildiğinde :attribute alanı zorunludur.',
    'required_unless'      => ':other şunlardan biri olmadığı sürece :attribute alanı zorunludur: :values.',
    'required_with'        => ':values mevcut olduğunda :attribute alanı zorunludur.',
    'required_with_all'    => ':values mevcut olduğunda :attribute alanı zorunludur.',
    'required_without'     => ':values mevcut olmadığında :attribute alanı zorunludur.',
    'required_without_all' => ':values hiçbiri mevcut olmadığında :attribute alanı zorunludur.',
    'same'                 => ':attribute ve :other eşleşmelidir.',
    'size'                 => [
        'array'   => ':attribute :size öğe içermelidir.',
        'file'    => ':attribute :size kilobayt olmalıdır.',
        'numeric' => ':attribute :size olmalıdır.',
        'string'  => ':attribute :size karakter olmalıdır.',
    ],
    'starts_with'          => ':attribute şu değerlerden biriyle başlamalıdır: :values.',
    'string'               => ':attribute bir metin olmalıdır.',
    'timezone'             => ':attribute geçerli bir zaman dilimi olmalıdır.',
    'unique'               => ':attribute zaten kullanılıyor.',
    'uploaded'             => ':attribute yüklenemedi.',
    'uppercase'            => ':attribute büyük harf olmalıdır.',
    'url'                  => ':attribute geçerli bir URL biçiminde olmalıdır.',
    'ulid'                 => ':attribute geçerli bir ULID olmalıdır.',
    'uuid'                 => ':attribute geçerli bir UUID olmalıdır.',

    /*
    |--------------------------------------------------------------------------
    | Özel Validation Mesajları
    |--------------------------------------------------------------------------
    */

    'custom' => [
        'baslik' => [
            'required' => 'İlan başlığı zorunludur.',
            'max'      => 'İlan başlığı en fazla 255 karakter olabilir.',
        ],
        'fiyat' => [
            'required' => 'Fiyat bilgisi zorunludur.',
            'numeric'  => 'Fiyat geçerli bir sayı olmalıdır.',
            'min'      => 'Fiyat 0\'dan büyük olmalıdır.',
        ],
        'email' => [
            'required' => 'E-posta adresi zorunludur.',
            'email'    => 'Geçerli bir e-posta adresi giriniz.',
            'unique'   => 'Bu e-posta adresi zaten kayıtlı.',
        ],
        'password' => [
            'required'  => 'Şifre zorunludur.',
            'min'       => 'Şifre en az :min karakter olmalıdır.',
            'confirmed' => 'Şifre onayı eşleşmiyor.',
        ],
        'il_id' => [
            'required' => 'İl seçimi zorunludur.',
        ],
        'ilce_id' => [
            'required' => 'İlçe seçimi zorunludur.',
        ],
        'ana_kategori_id' => [
            'required' => 'Kategori seçimi zorunludur.',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alan Adları (Türkçe)
    |--------------------------------------------------------------------------
    */

    'attributes' => [
        'baslik'           => 'başlık',
        'aciklama'         => 'açıklama',
        'fiyat'            => 'fiyat',
        'adres'            => 'adres',
        'il_id'            => 'il',
        'ilce_id'          => 'ilçe',
        'mahalle_id'       => 'mahalle',
        'ana_kategori_id'  => 'kategori',
        'alt_kategori_id'  => 'alt kategori',
        'metrekare'        => 'metrekare',
        'oda_sayisi'       => 'oda sayısı',
        'kat'              => 'kat',
        'bina_yasi'        => 'bina yaşı',
        'para_birimi'      => 'para birimi',
        'email'            => 'e-posta',
        'password'         => 'şifre',
        'name'             => 'ad soyad',
        'phone'            => 'telefon',
        'message'          => 'mesaj',
        'subject'          => 'konu',
        'title'            => 'başlık',
        'description'      => 'açıklama',
        'price'            => 'fiyat',
        'image'            => 'görsel',
        'file'             => 'dosya',
        'date'             => 'tarih',
        'start_date'       => 'başlangıç tarihi',
        'end_date'         => 'bitiş tarihi',
        'check_in'         => 'giriş tarihi',
        'check_out'        => 'çıkış tarihi',
        'guests'           => 'misafir sayısı',
        'api_key'          => 'API anahtarı',
    ],

];
