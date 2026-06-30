<?php

namespace App\Modules\TalepAnaliz\Services;

use App\Models\Ilan;
use App\Models\Talep;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AIAnalizService
{
    /**
     * @var string AI modelini çağırmak için kullanılacak API endpoint
     */
    protected $apiEndpoint;

    /**
     * @var string API anahtarı
     */
    protected $apiKey;

    /**
     * @var string Talep analizi için kullanılacak prompt şablonu
     */
    protected $promptTemplate;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->apiEndpoint = config('ai.endpoint', 'https://api.openai.com/v1/chat/completions');
        $this->apiKey = config('ai.api_key');

        // Prompt şablonunu yükle (yeni konumdan)
        $this->promptTemplate = file_get_contents(resource_path('prompts/talep-analizi-legacy.prompt.md'));
    }

    /**
     * Bir talebi analiz et ve emlak eşleştirmelerini döndür
     *
     * @param  Talep  $talep  Analiz edilecek talep
     * @param  int  $limit  Döndürülecek maksimum eşleşme sayısı
     * @return array Eşleşen emlaklar ve skorlar
     */
    public function analizEt(Talep $talep, $limit = 5)
    {
        // Cache anahtarı oluştur
        $cacheKey = 'talep_analiz_'.$talep->id.'_'.md5(json_encode($talep));

        // Eğer önbellekte varsa, önbellekten döndür
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Talep verilerini hazırla
            $talepVerileri = $this->talepVerileriniHazirla($talep);

            // AI modelini çağır
            $analizSonuclari = $this->aiModeliniCagir($talepVerileri);

            // Eşleşen emlakları ve skorları hazırla
            $eslesmeSonuclari = $this->eslesmeSonuclariniHazirla($analizSonuclari, $limit);

            // Sonuçları önbelleğe al (1 saat boyunca)
            Cache::put($cacheKey, $eslesmeSonuclari, now()->addHour());

            return $eslesmeSonuclari;
        } catch (\Exception $e) {
            Log::error('AI Analiz hatası: '.$e->getMessage());

            // Hata statusunda basit eşleştirme algoritmasını kullan
            return $this->basitEslestirmeYap($talep, $limit);
        }
    }

    /**
     * AI modeli için talep verilerini hazırla
     *
     * @return array
     */
    protected function talepVerileriniHazirla(Talep $talep)
    {
        // Talep detaylarını bir diziye dönüştür
        return [
            'id' => $talep->id,
            'il' => $talep->il ?? '',
            'ilce' => $talep->ilce ?? '',
            'mahalle' => $talep->mahalle ?? '',
            'tur' => $talep->tur ?? '',
            'min_fiyat' => $talep->min_fiyat ?? 0,
            'max_fiyat' => $talep->max_fiyat ?? 0,
            'min_metrekare' => $talep->min_metrekare ?? 0,
            'max_metrekare' => $talep->max_metrekare ?? 0,
            'oda_sayisi' => $talep->oda_sayisi ?? '',
            'aciklama' => $talep->aciklama ?? '',
            'ozellikler' => $talep->ozellikler ?? [],
        ];
    }

    /**
     * AI modeline istek gönder ve sonuçları al
     *
     * @param  array  $talepVerileri
     * @return array
     */
    protected function aiModeliniCagir($talepVerileri)
    {
        // Promptu hazırla
        $prompt = $this->promptHazirla($talepVerileri);

        // API isteği gönder
        $response = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Content-Type' => 'application/json',
        ])->post($this->apiEndpoint, [
            'model' => 'gpt-4-turbo',
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Sen emlak sektöründe uzman bir asistansın ve emlak talepleri ile ilanları eşleştirip en uygun seçenekleri sunuyorsun.',
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'temperature' => 0.3,
            'max_tokens' => 1000,
        ]);

        // JSON yanıtını diziye dönüştür
        $result = $response->json();

        if (isset($result['choices'][0]['message']['content'])) {
            return $result['choices'][0]['message']['content'];
        }

        throw new \Exception('AI yanıtı alınamadı.');
    }

    /**
     * Talep ve emlak verilerine göre prompt oluştur
     *
     * @param  array  $talepVerileri
     * @return string
     */
    protected function promptHazirla($talepVerileri)
    {
        // Emlak ilanlarını al (N+1 engellemek için eager loading)
        $emlaklar = \App\Models\Ilan::with('ozellikler')->select([
            'id', 'baslik', 'il', 'ilce', 'mahalle', 'tur', 'fiyat',
            'metrekare', 'oda_sayisi',
        ])->take(20)->get();

        // Prompt şablonunu düzenle
        $prompt = str_replace(
            [
                '{{TALEP_ID}}',
                '{{TALEP_IL}}',
                '{{TALEP_ILCE}}',
                '{{TALEP_MAHALLE}}',
                '{{TALEP_TUR}}',
                '{{TALEP_MIN_FIYAT}}',
                '{{TALEP_MAX_FIYAT}}',
                '{{TALEP_MIN_METREKARE}}',
                '{{TALEP_MAX_METREKARE}}',
                '{{TALEP_ODA_SAYISI}}',
                '{{TALEP_ACIKLAMA}}',
                '{{TALEP_OZELLIKLER}}',
            ],
            [
                $talepVerileri['id'],
                $talepVerileri['il'],
                $talepVerileri['ilce'],
                $talepVerileri['mahalle'],
                $talepVerileri['tur'],
                $talepVerileri['min_fiyat'],
                $talepVerileri['max_fiyat'],
                $talepVerileri['min_metrekare'],
                $talepVerileri['max_metrekare'],
                $talepVerileri['oda_sayisi'],
                $talepVerileri['aciklama'],
                is_array($talepVerileri['ozellikler']) ? implode(', ', $talepVerileri['ozellikler']) : $talepVerileri['ozellikler'],
            ],
            $this->promptTemplate
        );

        // Emlak verilerini ekle
        $emlakListesi = '';
        foreach ($emlaklar as $emlak) {
            $emlakListesi .= "ID: {$emlak->id} | {$emlak->baslik} | {$emlak->il}, {$emlak->ilce}, {$emlak->mahalle} | {$emlak->tur} | {$emlak->fiyat} TL | {$emlak->metrekare}m² | {$emlak->oda_sayisi} | ";
            $emlakListesi .= is_array($emlak->ozellikler) ? implode(', ', $emlak->ozellikler) : $emlak->ozellikler;
            $emlakListesi .= "\n";
        }

        return $prompt."\n\nMEVCUT İLANLAR:\n".$emlakListesi;
    }

    /**
     * AI sonuçlarını işleyip eşleşme sonuçları formatına dönüştür
     *
     * @param  string  $analizSonuclari
     * @param  int  $limit
     * @return array
     */
    protected function eslesmeSonuclariniHazirla($analizSonuclari, $limit)
    {
        // AI yanıtını parçalayıp eşleşme sonuçlarını çıkar
        $eslesmeListesi = [];

        // AI yanıtından emlak ID'lerini ve eşleşme yüzdelerini çıkar
        preg_match_all('/İlan: (.*?)\s*🧮 Eşleşme Oranı: (%\d+)/im', $analizSonuclari, $eslesmeler, PREG_SET_ORDER);

        foreach ($eslesmeler as $eslesme) {
            if (count($eslesme) >= 3) {
                $baslik = trim($eslesme[1]);
                $yuzde = (int) str_replace('%', '', $eslesme[2]);

                // Başlık veya açıklamadan emlak ID'sini bulmaya çalış
                preg_match('/ID: (\d+)/i', $baslik, $idMatch);
                $emlakId = isset($idMatch[1]) ? (int) $idMatch[1] : null;

                if ($emlakId) {
                    $emlak = Ilan::find($emlakId);
                    if ($emlak) {
                        $eslesmeListesi[] = [
                            'emlak' => $emlak,
                            'eslesme_yuzdesi' => $yuzde,
                            'ai_aciklama' => $analizSonuclari,
                        ];
                    }
                }
            }
        }

        // Eşleşme yüzdesine göre sırala ve limiti uygula
        usort($eslesmeListesi, function ($a, $b) {
            return $b['eslesme_yuzdesi'] <=> $a['eslesme_yuzdesi'];
        });

        return array_slice($eslesmeListesi, 0, $limit);
    }

    /**
     * Basit algoritma ile eşleştirme yap (AI çağrısı başarısız olduğunda)
     *
     * @param  Talep  $talep
     * @param  int  $limit
     * @return array
     */
    protected function basitEslestirmeYap($talep, $limit)
    {
        $emlaklar = Ilan::query();

        // Talep kriterlerine göre filtreleme
        if ($talep->il) {
            $emlaklar->where('il', $talep->il);
        }

        if ($talep->ilce) {
            $emlaklar->where('ilce', $talep->ilce);
        }

        if ($talep->min_fiyat && $talep->max_fiyat) {
            $emlaklar->whereBetween('fiyat', [$talep->min_fiyat, $talep->max_fiyat]);
        } elseif ($talep->max_fiyat) {
            $emlaklar->where('fiyat', '<=', $talep->max_fiyat);
        } elseif ($talep->min_fiyat) {
            $emlaklar->where('fiyat', '>=', $talep->min_fiyat);
        }

        // Eşleşme yüzdesi hesaplama
        $sonuclar = $emlaklar->get()->map(function ($emlak) use ($talep) {
            $eslesmeYuzdesi = $this->basitEslesmeYuzdesiHesapla($emlak, $talep);

            return [
                'emlak' => $emlak,
                'eslesme_yuzdesi' => $eslesmeYuzdesi,
            ];
        })->sortByDesc('eslesme_yuzdesi')->take($limit)->toArray();

        return $sonuclar;
    }

    /**
     * Basit eşleşme yüzdesi hesaplama
     *
     * @param  Ilan  $emlak
     * @param  Talep  $talep
     * @return float
     */
    protected function basitEslesmeYuzdesiHesapla($emlak, $talep)
    {
        $puan = 0;
        $kriterSayisi = 0;

        // İl kontrolü
        if ($talep->il) {
            $kriterSayisi++;
            if ($emlak->il == $talep->il) {
                $puan++;
            }
        }

        // İlçe kontrolü
        if ($talep->ilce) {
            $kriterSayisi++;
            if ($emlak->ilce == $talep->ilce) {
                $puan++;
            }
        }

        // Oda sayısı kontrolü
        if ($talep->oda_sayisi) {
            $kriterSayisi++;
            if ($emlak->oda_sayisi == $talep->oda_sayisi) {
                $puan++;
            }
        }

        // Fiyat kontrolü
        if ($talep->min_fiyat && $talep->max_fiyat) {
            $kriterSayisi++;
            if ($emlak->fiyat >= $talep->min_fiyat && $emlak->fiyat <= $talep->max_fiyat) {
                $puan++;
            }
        }

        return $kriterSayisi > 0 ? ($puan / $kriterSayisi) * 100 : 0;
    }
}
