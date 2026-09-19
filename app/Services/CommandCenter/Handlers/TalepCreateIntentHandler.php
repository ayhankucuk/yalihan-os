<?php

declare(strict_types=1);

namespace App\Services\CommandCenter\Handlers;

use App\DTOs\Command\NormalizedCommandInput;
use App\Models\User;
use App\Services\CRM\TalepAuthorityService;
use App\Services\CRM\KisiRegistrationService;
use Illuminate\Support\Facades\Log;

/**
 * TalepCreateIntentHandler
 *
 * Context7 Standard: C7-TALEP-CREATE-INTENT-HANDLER-2026-09-19
 *
 * Doğal dilde CRM talep oluşturma komutlarını işler.
 * Canonical write authority: TalepAuthorityService::createTalep()
 * Canonical Kisi spillover: TalepAuthorityService içinden KisiRegistrationService
 *
 * Güvenlik invariant:
 *   - Talep oluşturma sadece authenticated Telegram human actor üzerinden
 *   - Tenant izolasyonu CommandGateway tarafından kurulur (TEST_VERIFIED)
 *   - GuardsAgentWrites TalepAuthorityService seviyesinde uygulanır
 *   - Telefon numarası eksikse clarification döner, partial write OLMAZ
 */
class TalepCreateIntentHandler
{
    public function __construct(
        private TalepAuthorityService $talepAuthority,
        private KisiRegistrationService $kisiRegistration
    ) {}

    public function handle(NormalizedCommandInput $input, array $params = []): string
    {
        $actor = $this->resolveActor($input);
        if (! $actor) {
            Log::warning('TalepCreateIntentHandler: Actor çözümlenemedi', [
                'channel' => $input->channel,
                'external_actor_id' => $input->externalActorId,
            ]);
            return $this->clarificationResponse([
                'title' => 'Yetkilendirme Hatası',
                'message' => 'Talep oluşturma yetkiniz doğrulanamadı.',
            ]);
        }

        // --- PARAMETRE ÇIKARIMI ---
        $extracted = $this->extractParameters($input->rawText);

        // --- EKSİK BİLGİ KONTROLÜ ---
        $missing = $this->detectMissingRequiredFields($extracted);
        if (! empty($missing)) {
            return $this->clarificationResponse([
                'title' => 'Talep Oluşturmak İçin Bilgi Gerekli',
                'message' => 'Aşağıdaki bilgileri tamamlayarak tekrar gönderebilirsiniz:',
                'fields' => $missing,
            ]);
        }

        // --- TALEP OLUŞTURMA ---
        try {
            $talepData = $this->buildTalepData($extracted);

            $talep = $this->talepAuthority->createTalep($talepData, $actor);

            Log::info('TalepCreateIntentHandler: Talep oluşturuldu', [
                'talep_id' => $talep->id,
                'actor_id' => $actor->id,
                'kisi_id' => $talep->kisi_id,
                'max_fiyat' => $extracted['max_fiyat'] ?? null,
            ]);

            // Combine first+last name for successResponse fallback
            $extracted['kisi_ad'] = trim(($extracted['kisi_ad'] ?? '') . ' ' . ($extracted['kisi_soyad'] ?? '')) ?: null;
            return $this->successResponse($talep, $extracted);

        } catch (\App\Exceptions\AgentWriteViolationException $e) {
            Log::critical('TalepCreateIntentHandler: Agent write violation', [
                'actor_id' => $actor->id,
                'exception' => $e->getMessage(),
            ]);
            return $this->clarificationResponse([
                'title' => 'İşlem Reddedildi',
                'message' => 'Talep oluşturma şu an kullanılamıyor. Lütfen daha sonra tekrar deneyin.',
            ]);

        } catch (\Exception $e) {
            Log::error('TalepCreateIntentHandler: Talep oluşturma hatası', [
                'actor_id' => $actor->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // KisiRegistrationService DUPLICATE_KISI check
            if ($e->getMessage() === 'DUPLICATE_KISI_DETECTED') {
                return $this->clarificationResponse([
                    'title' => 'Müşteri Zaten Kayıtlı',
                    'message' => 'Bu telefon veya e-posta ile kayıtlı bir müşteri bulunmaktadır. '
                               . 'Lütfen mevcut müşteri üzerinden talep oluşturun.',
                ]);
            }

            // Telefon validation error from KisiRegistrationService
            if (str_contains($e->getMessage(), 'telefon') || str_contains($e->getMessage(), 'required')) {
                return $this->clarificationResponse([
                    'title' => 'Telefon Numarası Gerekli',
                    'message' => "Talep oluşturmak için telefon numarası zorunludur. "
                               . "Örnek: \"Yeni talep var. Ahmet Yılmaz, 0532 123 45 67, Bodrum'da villa arıyor.\"",

                ]);
            }

            return $this->clarificationResponse([
                'title' => 'Talep Oluşturulamadı',
                'message' => 'Talep kaydedilirken bir hata oluştu: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * Komut metninden talep parametrelerini çıkar.
     *
     * Sadece METİNDE AÇIKÇA BULUNAN değerleri çıkarır.
     * Asla eksik veri uydurmaz.
     */
    public function extractParameters(string $rawText): array
    {
        $text = mb_strtolower(trim($rawText));
        $params = [
            'kisi_ad' => null,
            'kisi_soyad' => null,
            'kisi_telefon' => null,
            'kisi_email' => null,
            'max_fiyat' => null,
            'para_birimi' => 'EUR',
            'property_type' => null, // villa, daire, arsa, etc.
            'il_adi' => null,
            'ilce_adi' => null,
            'baslik' => null,
        ];

        // --- KİŞİ ADI ÇIKARIMI (FAIL-SAFE — comma-anchored) ---
        // Names are ONLY extracted when they appear after a COMMA that follows
        // the Talep keyword pattern. This prevents generic words like "villa",
        // "bodrum'da", "arıyor" from being interpreted as customer names.
        //
        // Supported structures:
        //   "Yeni talep var. Ahmet Yılmaz, 0532 123 45 67"
        //   "Yeni talep var Sude, 0532 123 45 67"
        //   "Talep ekle. Ali, 0532 123 45 67"
        //   "Yeni talep. Sude Yılmaz, 0532 123 45 67"
        //
        // INVALID (no comma — clarification will be requested):
        //   "Yeni talep var villa arıyor 0532 123 45 67"
        //   "Yeni talep var Bodrum'da villa arıyor 0532 123 45 67"
        if (preg_match(
            '/(?:(?:yeni\s+)?talep\s+(?:var|ekle|oluştur|aç|olustur|ekle))[.,]?\s*([A-ZÇĞİÖŞÜ][A-Za-zçğıöşü]+(?:[\s\x27\x2019][A-ZÇĞİÖŞÜ][a-zçğıöşü]+){0,2})\s*,/iu',
            $rawText,
            $m
        )) {
            $fullName = trim($m[1]);
            $parts = array_filter(preg_split('/[\s\x27\x2019]+/', $fullName));
            $parts = array_values($parts);
            $params['kisi_ad'] = $parts[0] ?? null;
            $params['kisi_soyad'] = isset($parts[1]) ? implode(' ', array_slice($parts, 1)) : null;
        }

        // --- TELEFON NUMARASI ÇIKARIMI ---
        // Turkish phones: 10 digits starting with 5, normalized to 0XXXXXXXXXX format.
        // Formats: 0532 123 45 67, +90 532 123 45 67, 5321234567, +905321234567.
        //
        // ── MULTI-FORMAT PHONE EXTRACTION ──────────────────────────────────
        // Turkish mobiles are 10 digits starting with 5. Input may be grouped
        // in several common formats: 4-3-2-1 · 3-3-2-2 · 3-2-2-2 · plain.
        // We try positional patterns first, then fall back to a plain 10-digit
        // capture. All separators are stripped from the matched text.
        $stripped = preg_replace('/\+90\s*/', '', $text);
        $mobile = null;

        // Try 4-3-2-1  (e.g. 0532 123 45 67) and 3-3-2-2  (e.g. 531 222 33 44)
        foreach ([
            '/5[0-9]{2}(?:[ -]?[0-9]{3}){1}(?:[ -]?[0-9]{2}){2}/',   // 4-3-2-1
            '/5[0-9]{2}(?:[ -]?[0-9]{2}){2}(?:[ -]?[0-9]{2}){2}/',   // 3-3-2-2
        ] as $fmtPattern) {
            if (preg_match($fmtPattern, $stripped, $m)) {
                $candidate = '0' . preg_replace('/[^0-9]/', '', $m[0]);
                if (strlen($candidate) === 11) { // 0 + 10 digits
                    $mobile = $candidate;
                    break;
                }
            }
        }

        // Fallback: plain 10-digit starting with 5 (e.g. 5381234567)
        if (! $mobile && preg_match('/5[0-9]{9}/', $stripped, $m)) {
            $mobile = '0' . $m[0];
        }

        if ($mobile) {
            $params['kisi_telefon'] = $mobile;
        }

        // --- BÜTÇE VE DÖVİZ ÇIKARIMI ---
        $currency = 'EUR';
        if (str_contains($text, 'tl') || str_contains($text, '₺') || str_contains($text, 'try')) {
            $currency = 'TRY';
        } elseif (str_contains($text, '$') || str_contains($text, 'usd') || str_contains($text, 'dolar')) {
            $currency = 'USD';
        }
        $params['para_birimi'] = $currency;

        // max_fiyat pattern'leri (IntentRouter ile aynı mantık)
        $maxFiyat = null;

        // €1M / 1M € / 1 milyon / milyon euro pattern
        if (preg_match(
            '/(?:€|eur|euro|tl|dolar|usd|try)?\s*([0-9]+(?:[.,][0-9]+)?)\s*(?:m|milyon)\s*(?:€|eur|euro|tl|dolar|usd|try)?/iu',
            $text, $m
        )) {
            $val = (float) str_replace(',', '.', $m[1]);
            $maxFiyat = $val * 1_000_000;
        }
        // €750k / 750K pattern
        elseif (preg_match(
            '/(?:€|eur|euro|tl|dolar|usd|try)?\s*([0-9]+(?:[.,][0-9]+)?)\s*(k)\b(?:\s*(?:€|eur|euro|tl|dolar|usd|try))?/iu',
            $text, $m
        )) {
            $val = (float) str_replace(',', '.', $m[1]);
            $maxFiyat = $val * 1_000;
        }
        // €750 bin / 750 bin euro pattern
        elseif (preg_match(
            '/(?:€|eur|euro|tl|dolar|usd|try)?\s*([0-9]+(?:[.,][0-9]+)?)\s*bin\b(?:\s*(?:€|eur|euro|tl|dolar|usd|try))?/iu',
            $text, $m
        )) {
            $val = (float) str_replace(',', '.', $m[1]);
            $maxFiyat = $val * 1_000;
        }
        // Plain numeric (4+ digits) — only when explicit currency/budget keyword exists.
        // MUST NOT use bare phone digits as budget. Safe: explicit budget requires
        // currency symbol or multiplier keyword; phone-only input has neither.
        if (! $maxFiyat
            && preg_match('/([0-9]{4,9})/', $text, $m)
            && (str_contains($text, '€') || str_contains($text, 'eur')
                || str_contains($text, 'tl') || str_contains($text, '₺')
                || str_contains($text, '$') || str_contains($text, 'usd')
                || str_contains($text, 'bin') || str_contains($text, 'milyon')
                || str_contains($text, 'bütçe') || str_contains($text, 'butce')
                || str_contains($text, 'bütçesi') || str_contains($text, 'butcesi')))
        {
            $maxFiyat = (float) $m[1];
        }

        if ($maxFiyat) {
            $params['max_fiyat'] = $maxFiyat;
        }

        // --- MÜLK TİPİ ÇIKARIMI ---
        $propertyTypes = [
            'villa' => ['villa', 'villas', 'villalar'],
            'daire' => ['daire', 'daireler', 'apartment', 'apartman'],
            'arsa' => ['arsa', 'arazi', 'parsel', 'land'],
            'müstakil' => ['müstakil', 'mustakil', 'detached', 'tek ailelik'],
            'rezidans' => ['rezidans', 'residence'],
            'ticari' => ['ticari', 'isyeri', 'dukkan', 'shop', 'ofis'],
        ];

        foreach ($propertyTypes as $type => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($text, $kw)) {
                    $params['property_type'] = $type;
                    break 2;
                }
            }
        }

        // --- YER ÇIKARIMI (Bodrum, Istanbul, vb.) ---
        // "Bodrum'da", "İstanbul'da", "Antalya'da" pattern (case-insensitive match on raw text)
        if (preg_match('/([A-ZÇĞİÖŞÜ][a-zçğıöşü]+)\'(?:da|de|ta|te)\s+(?:villa|daire|arsa|müstakil|ev|arıyor|bakıyor|araniyor)/iu', $rawText, $m)) {
            $params['ilce_adi'] = $m[1];
        }
        // "Bodrum'da" tek başına
        elseif (preg_match('/([A-ZÇĞİÖŞÜ][a-zçğıöşü]+)\'(?:da|de|ta|te)\b/iu', $rawText, $m)) {
            $params['ilce_adi'] = $m[1];
        }

        // --- BAŞLIK OLUŞTURMA (sadece mevcut verilerden) ---
        $titleParts = [];
        if ($params['kisi_ad']) {
            $titleParts[] = $params['kisi_ad'];
            if ($params['kisi_soyad']) {
                $titleParts[0] .= ' ' . $params['kisi_soyad'];
            }
        }
        if ($params['property_type']) {
            $titleParts[] = $params['property_type'] . ' talebi';
        }
        if ($params['ilce_adi']) {
            $titleParts[] = $params['ilce_adi'];
        }
        if (! empty($titleParts)) {
            $params['baslik'] = implode(' — ', $titleParts);
        }

        return $params;
    }

    /**
     * Eksik zorunlu alanları tespit eder.
     *
     * Sadece METİNDE AÇIKÇA BULUNMAYAN zorunlu alanları raporlar.
     * Asla mevcut olmayan bir telefon numarasını "eksik" olarak işaretlemez.
     */
    public function detectMissingRequiredFields(array $params): array
    {
        $missing = [];

        // Kural: isim + telefon birlikte mevcut olmalı. Sadece biri varsa eksik.
        // İsim olmadan telefon: "Yeni talep var 0532..." — isim teyit edilemedi
        // Telefon olmadan isim: "Yeni talep var Ahmet..." — telefon gerekli
        // Ne isim ne telefon: en azından telefon gerekli (isim sonradan alınabilir)
        if (! $params['kisi_telefon'] && ! $params['kisi_ad']) {
            $missing[] = 'Telefon numarası (örneğin: 0532 123 45 67)';
        } elseif ($params['kisi_telefon'] && ! $params['kisi_ad']) {
            $missing[] = 'Müşteri adı (örneğin: Ahmet Yılmaz)';
        } elseif ($params['kisi_ad'] && ! $params['kisi_telefon']) {
            $missing[] = 'Telefon numarası (örneğin: 0532 123 45 67)';
        }

        return $missing;
    }

    /**
     * TalepAuthorityService::createTalep() için veri yapısı oluşturur.
     */
    protected function buildTalepData(array $params): array
    {
        $data = [];

        if ($params['baslik']) {
            $data['baslik'] = $params['baslik'];
        }

        if ($params['kisi_ad']) {
            $data['kisi_ad'] = $params['kisi_ad'];
        }

        if ($params['kisi_soyad']) {
            $data['kisi_soyad'] = $params['kisi_soyad'];
        }

        if ($params['kisi_telefon']) {
            $data['kisi_telefon'] = $params['kisi_telefon'];
        }

        if ($params['kisi_email']) {
            $data['kisi_email'] = $params['kisi_email'];
        }

        if ($params['max_fiyat']) {
            $data['max_fiyat'] = $params['max_fiyat'];
        }

        if ($params['property_type']) {
            // property_type → alt_kategori_id mapping (canonical IlanKategori tablosuna bağlı)
            // Bu mapping statik yapılır —IlanKategori tablosundan dinamik çekilmez
            $typeToKategori = [
                'villa' => 'Villa',
                'daire' => 'Daire',
                'arsa' => 'Arsa',
                'müstakil' => 'Müstakil Ev',
                'rezidans' => 'Rezidans',
                'ticari' => 'Ticari',
            ];
            // IlanKategori tablosundan slug ile bul (fail-safe)
            $kategoriSlug = $typeToKategori[$params['property_type']] ?? null;
            if ($kategoriSlug) {
                $kategori = \App\Models\IlanKategori::where('slug', \Illuminate\Support\Str::slug($kategoriSlug))
                    ->orWhere('ad', 'like', '%' . $kategoriSlug . '%')
                    ->first();
                if ($kategori) {
                    $data['alt_kategori_id'] = $kategori->id;
                }
            }
        }

        // Açıklama oluştur
        $descParts = [];
        if ($params['property_type']) {
            $descParts[] = ucfirst($params['property_type']) . ' aranıyor.';
        }
        if ($params['ilce_adi']) {
            $descParts[] = $params['ilce_adi'] . ' bölgesinde.';
        }
        if ($params['max_fiyat']) {
            $fmt = number_format($params['max_fiyat'], 0, ',', '.');
            $descParts[] = 'Bütçe: ' . $fmt . ' ' . ($params['para_birimi'] ?? 'EUR') . "'a kadar.";
        }
        if (! empty($descParts)) {
            $data['aciklama'] = implode(' ', $descParts);
        }

        $data['talep_durumu'] = \App\Enums\TalepDurumu::AKTIF->value;
        $data['talep_tipi'] = 'Satılık';

        return $data;
    }

    /**
     * Authenticated User'ı çözümle.
     */
    protected function resolveActor(NormalizedCommandInput $input): ?User
    {
        if ($input->channel === 'telegram') {
            return User::where('telegram_chat_id', $input->externalActorId)
                ->orWhere('id', (int) $input->externalActorId)
                ->first();
        }

        return null;
    }

    /**
     * Başarılı talep oluşturma yanıtı.
     */
    protected function successResponse(\App\Models\Talep $talep, array $params): string
    {
        $baslik = e($talep->baslik ?? 'Talep #' . $talep->id);
        $kisiAd = $talep->kisi?->tam_ad ?? $params['kisi_ad'] ?? 'Müşteri';

        $msg = "✅ *Talep Oluşturuldu*\\n\\n";
        $msg .= "📋 *{$baslik}*\\n";
        $msg .= "👤 {$kisiAd}\\n";

        if ($talep->max_fiyat) {
            $fmt = number_format((float) $talep->max_fiyat, 0, ',', '.');
            $currency = $params['para_birimi'] ?? 'EUR';
            $msg .= "💰 Bütçe: {$fmt} {$currency}\\n";
        }

        if ($talep->aciklama) {
            $msg .= "📝 " . e($talep->aciklama) . "\\n";
        }

        $msg .= "\\n⏱️ Oluşturulma: " . $talep->created_at?->format('d.m.Y H:i') . "\\n";
        $msg .= "🔗 [Talebi Görüntüle](" . config('app.url') . "/admin/talepler/{$talep->id})";

        return trim($msg);
    }

    /**
     * Clarification yanıtı oluştur.
     */
    protected function clarificationResponse(array $data): string
    {
        $title = $data['title'] ?? 'Bilgi Gerekli';
        $message = $data['message'] ?? 'Lütfen aşağıdaki bilgileri sağlayın:';

        $msg = "📋 *{$title}*\\n\\n";
        $msg .= e($message) . "\\n";

        if (! empty($data['fields'])) {
            foreach ($data['fields'] as $field) {
                $msg .= "\\n• " . e($field);
            }
        }

        $msg .= "\\n\\n💡 _Örnek: \"Yeni talep var. Ayşe Yılmaz, 0532 111 22 33, Bodrum'da villa arıyor, €500 bin bütçe.\"";

        return trim($msg);
    }
}
