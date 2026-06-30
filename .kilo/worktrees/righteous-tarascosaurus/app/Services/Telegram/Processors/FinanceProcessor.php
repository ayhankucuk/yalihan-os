<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Models\User;
use App\Modules\Finans\Models\FinansalIslem;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * FinanceProcessor - Telegram Financial Intelligence
 *
 * Context7 Standard: C7-TELEGRAM-FINANCE-2026-01-01
 *
 * GPT-4o ile serbest metinden finansal işlem çıkarımı.
 * Pattern Recognition: "kaydet", "masraf", "gelir", "gider"
 */
class FinanceProcessor
{
    private ?string $openaiApiKey;

    public function __construct()
    {
        $this->openaiApiKey = config('ai.api_key');
    }

    /**
     * Telegram mesajından finansal işlem oluştur
     *
     * @param User $user Kullanıcı
     * @param string $message Telegram mesajı
     * @return string|null Yanıt mesajı (null = finansal mesaj değil)
     */
    public function handle(User $user, string $message): ?string
    {
        // 1. Ön kontrol: Finansal anahtar kelimeler var mı?
        if (!$this->isFinancialMessage($message)) {
            return null;
        }

        Log::info('FinanceProcessor: Finansal mesaj algılandı', [
            'user_id' => $user->id,
            'message' => $message,
        ]);

        try {
            // 2. GPT-4o ile finansal verileri çıkar
            $financialData = $this->extractFinancialDataWithAI($message);

            if (!$financialData) {
                return "❌ Finansal bilgileri çıkaramadım. Lütfen şu formatı deneyin:\n\n" .
                       "\"500 TL kahve masrafı\"\n" .
                       "\"10,000 TL kira geliri\"";
            }

            // 3. Finansal işlem oluştur
            $islem = $this->createFinancialTransaction($user, $financialData);

            // 4. Mühürlü onay mesajı
            return $this->generateSuccessMessage($islem);

        } catch (\Exception $e) {
            Log::error('FinanceProcessor: Hata', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return "❌ Finansal işlem kaydedilirken bir hata oluştu. Lütfen tekrar deneyin.";
        }
    }

    /**
     * Mesajda finansal anahtar kelimeler var mı?
     */
    private function isFinancialMessage(string $message): bool
    {
        $keywords = [
            'tl', 'lira', '₺', 'dolar', 'dollar', '$', 'euro', '€', 'usd', 'eur',
            'gelir', 'gider', 'masraf', 'harcama', 'ödeme', 'odeme',
            'kaydet', 'komisyon', 'fatura', 'tahsilat', 'ödendi'
        ];

        $lowerMessage = mb_strtolower($message);

        foreach ($keywords as $keyword) {
            if (mb_strpos($lowerMessage, $keyword) !== false) {
                return true;
            }
        }

        // Rakam kontrolü (finansal mesajlarda genelde rakam olur)
        return preg_match('/\d+/', $message) === 1;
    }

    /**
     * GPT-4o ile finansal verileri JSON olarak çıkar
     */
    private function extractFinancialDataWithAI(string $message): ?array
    {
        if (empty($this->openaiApiKey)) {
            Log::warning('FinanceProcessor: OpenAI API key bulunamadı, fallback parsing kullanılacak');
            return $this->fallbackExtraction($message);
        }

        try {
            $systemPrompt = "Sen bir finansal asistansın. Kullanıcının Türkçe mesajından finansal bilgileri çıkar ve JSON formatında döndür.\n\n" .
                           "ZORUNLU ALANLAR:\n" .
                           "- miktar (float): Tutarı sayı olarak çıkar\n" .
                           "- para_birimi (string): TRY, USD, EUR, GBP\n" .
                           "- islem_tipi (string): gelir, gider, komisyon, masraf, odeme\n" .
                           "- aciklama (string): İşlem açıklaması\n\n" .
                           "KURALLAR:\n" .
                           "1. Varsayılan para birimi: TRY\n" .
                           "2. 'masraf', 'harcama' → gider\n" .
                           "3. 'kazanç', 'tahsilat' → gelir\n" .
                           "4. Miktar bulunamazsa null döndür\n\n" .
                           "SADECE JSON döndür, başka metin ekleme!";

            $response = Http::timeout(10)
                ->withHeaders([
                    'Authorization' => 'Bearer ' . $this->openaiApiKey,
                    'Content-Type' => 'application/json',
                ])
                ->post('https://api.openai.com/v1/chat/completions', [
                    'model' => 'gpt-4o',
                    'messages' => [
                        [
                            'role' => 'system',
                            'content' => $systemPrompt,
                        ],
                        [
                            'role' => 'user',
                            'content' => $message,
                        ],
                    ],
                    'temperature' => 0.3,
                    'max_tokens' => 200,
                ]);

            if (!$response->successful()) {
                Log::error('FinanceProcessor: GPT-4o API hatası', [
                    'islem_durumu' => $response->getStatusCode(), // Context7: renamed from original
                    'body' => $response->body(),
                ]);
                return $this->fallbackExtraction($message);
            }

            $result = $response->json();
            $content = $result['choices'][0]['message']['content'] ?? null;

            if (!$content) {
                return $this->fallbackExtraction($message);
            }

            // JSON parse
            $data = json_decode($content, true);

            if (!$data || !isset($data['miktar'])) {
                Log::warning('FinanceProcessor: GPT-4o geçersiz JSON döndürdü', [
                    'content' => $content,
                ]);
                return $this->fallbackExtraction($message);
            }

            // Validation
            return $this->validateFinancialData($data);

        } catch (\Exception $e) {
            Log::error('FinanceProcessor: GPT-4o exception', [
                'error' => $e->getMessage(),
            ]);
            return $this->fallbackExtraction($message);
        }
    }

    /**
     * AI başarısız olursa basit regex parsing
     */
    private function fallbackExtraction(string $message): ?array
    {
        // Miktar çıkar
        preg_match('/(\d+(?:[.,]\d{3})*(?:[.,]\d{1,2})?)\s*(tl|lira|dolar|euro|usd|eur|₺|\$|€)?/i', $message, $matches);

        if (empty($matches[1])) {
            return null;
        }

        $amount = str_replace(['.', ','], ['', '.'], $matches[1]);
        $currency = 'TRY';

        if (!empty($matches[2])) {
            $curr = strtolower($matches[2]);
            if (in_array($curr, ['dolar', 'dollar', 'usd', '$'])) {
                $currency = 'USD';
            } elseif (in_array($curr, ['euro', 'eur', '€'])) {
                $currency = 'EUR';
            }
        }

        // İşlem tipi
        $lowerMessage = mb_strtolower($message);
        $type = 'gider'; // Default

        if (strpos($lowerMessage, 'gelir') !== false || strpos($lowerMessage, 'kazanç') !== false || strpos($lowerMessage, 'tahsilat') !== false) {
            $type = 'gelir';
        } elseif (strpos($lowerMessage, 'komisyon') !== false) {
            $type = 'komisyon';
        } elseif (strpos($lowerMessage, 'masraf') !== false) {
            $type = 'masraf';
        }

        // Açıklama
        $description = preg_replace('/\d+(?:[.,]\d{3})*(?:[.,]\d{1,2})?\s*(tl|lira|dolar|euro|usd|eur|₺|\$|€)?/i', '', $message);
        $description = trim(str_replace(['gelir', 'gider', 'masraf', 'komisyon', ':', '-'], '', $description));

        return [
            'miktar' => (float) $amount,
            'para_birimi' => $currency,
            'islem_tipi' => $type,
            'aciklama' => !empty($description) ? $description : 'Telegram üzerinden eklendi',
        ];
    }

    /**
     * Finansal verileri validate et
     */
    private function validateFinancialData(array $data): ?array
    {
        // Miktar kontrolü
        if (!isset($data['miktar']) || $data['miktar'] <= 0) {
            return null;
        }

        // Para birimi
        $validCurrencies = ['TRY', 'USD', 'EUR', 'GBP'];
        $data['para_birimi'] = strtoupper($data['para_birimi'] ?? 'TRY');
        if (!in_array($data['para_birimi'], $validCurrencies)) {
            $data['para_birimi'] = 'TRY';
        }

        // İşlem tipi
        $validTypes = ['gelir', 'gider', 'komisyon', 'masraf', 'odeme'];
        $data['islem_tipi'] = strtolower($data['islem_tipi'] ?? 'gider');
        if (!in_array($data['islem_tipi'], $validTypes)) {
            $data['islem_tipi'] = 'gider';
        }

        // Açıklama
        $data['aciklama'] = !empty($data['aciklama']) ? $data['aciklama'] : 'Telegram üzerinden eklendi';

        return $data;
    }

    /**
     * Finansal işlem kaydı oluştur
     */
    private function createFinancialTransaction(User $user, array $data): FinansalIslem
    {
        $islem = FinansalIslem::create([
            'user_id' => $user->id,
            'islem_tipi' => $data['islem_tipi'],
            'miktar' => $data['miktar'],
            'para_birimi' => $data['para_birimi'],
            'aciklama' => $data['aciklama'],
            'tarih' => now(), // Mesajın atıldığı an
            'islem_durumu' => 'bekliyor', // Onay bekliyor
            'notlar' => 'Telegram Bot üzerinden otomatik oluşturuldu',
        ]);

        Log::info('FinanceProcessor: İşlem oluşturuldu', [
            'islem_id' => $islem->id,
            'user_id' => $user->id,
            'tip' => $data['islem_tipi'],
            'miktar' => $data['miktar'],
            'birim' => $data['para_birimi'],
        ]);

        return $islem;
    }

    /**
     * Mühürlü onay mesajı oluştur
     */
    private function generateSuccessMessage(FinansalIslem $islem): string
    {
        $tipLabel = $this->getTypeLabel($islem->islem_tipi);
        $birimSymbol = $this->getCurrencySymbol($islem->para_birimi);

        return "💎 *Kaptan, {$islem->miktar} {$birimSymbol} tutarındaki {$tipLabel} işlemi finansal kayıtlara mühürlendi!*\n\n" .
               "📝 *Detaylar:*\n" .
               "• Tip: {$tipLabel}\n" .
               "• Tutar: " . number_format($islem->miktar, 2, ',', '.') . " {$birimSymbol}\n" .
               "• Açıklama: {$islem->aciklama}\n" .
               "• Tarih: " . $islem->tarih->format('d.m.Y H:i') . "\n" .
               "• Durum: ⏳ Onay Bekliyor\n\n" .
               "🔍 Panel'den görüntüleyebilir ve düzenleyebilirsiniz.";
    }

    /**
     * İşlem tipi Türkçe label
     */
    private function getTypeLabel(string $type): string
    {
        return match($type) {
            'gelir' => 'Gelir',
            'gider' => 'Gider',
            'komisyon' => 'Komisyon',
            'masraf' => 'Masraf',
            'odeme' => 'Ödeme',
            default => ucfirst($type),
        };
    }

    /**
     * Para birimi sembolü
     */
    private function getCurrencySymbol(string $currency): string
    {
        return match($currency) {
            'TRY' => '₺',
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            default => $currency,
        };
    }
}
