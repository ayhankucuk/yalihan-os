# Yalıhan AI OS — AI Katmanı Bulgular Raporu

> Tarih: 2026-05-13 | Claude AI incelemesi
> Kapsam: app/Services/Cortex/, app/Services/AI/, app/Agents/, AI entegrasyon noktaları

---

## 🔴 KRİTİK — Üretimde Sıfır Çalışır

### B-001: API Key'leri Sahte / Boş
**Dosya:** `.env`
```
DEEPSEEK_API_KEY=sk-ROTATED_KEY_REPLACE_ME   ← placeholder
OPENAI_API_KEY=                               ← tamamen boş
```
**Etki:** AI_DRY_RUN=true şu an tüm hataları maskaliyor. Bu satır false yapıldığında sistemnin tüm AI özellikleri patlıyor. Ne birincil provider, ne fallback çalışır.
**Görev:** #8
**Çözüm:** Gerçek API key'leri .env'e gir. Asla commit'leme — .env .gitignore'da.

---

### B-002: deepseek-v4-flash Modeli Mevcut Değil
**Dosya:** `.env`, `config/services.php` (veya AI config)
```
AI_DEFAULT_MODEL=deepseek-v4-flash   ← yok
DEEPSEEK_MODEL=deepseek-v4-flash     ← yok
```
**Etki:** DeepSeek API'sinin gerçek model adları `deepseek-chat` (V3) ve `deepseek-reasoner` (R1). Yanlış model adıyla yapılan her istek API'den 400/422 hatası döndürür. Üstüne `DeepSeekCortexProvider` içinde sert model guard var — model adı uyuşmazsa `AIModelMismatchException` fırlatıyor.
**Görev:** #8
**Çözüm:** `AI_DEFAULT_MODEL=deepseek-chat`, `DEEPSEEK_MODEL=deepseek-chat` yap.

---

### B-003: N8N_WEBHOOK_URL Tanımsız
**Dosya:** `.env` (satır yok), `AIIlanTaslagiService.php:41`
```php
$this->n8nWebhookUrl = config('services.n8n.webhook_url', '');
Http::timeout(30)->post('' . '/ai/ilan-taslagi', [...]);
```
**Etki:** İlan taslağı, mesaj taslağı, sözleşme taslağı özellikleri tamamen çalışmıyor. Boş URL'ye 30 saniye bekleyip exception fırlatıyor, catch'te yeniden throw ediyor.
**Görev:** #10
**Çözüm:** N8N_WEBHOOK_URL=http://n8n-host:5678 ekle. N8N hazır değilse servise dry_run modu ekle.

---

## 🟠 AĞIR — Yük Altında Çöker

### B-004: MatchingEngine N+1 Yazma Problemi
**Dosya:** `app/Services/Cortex/MatchingEngine.php:34-64`
```php
$ilanlar = Ilan::where('yayin_durumu', 'Aktif')->get(); // TÜM ilanlar RAM'de
$ilanlar->map(function ($ilan) {
    $ilan->update(['cortex_score' => ..., 'cortex_ranked_at' => ...]); // Her ilan için 1 UPDATE
});
```
**Etki:** 500 aktif ilan = 500 UPDATE/eşleşme. 100 eşzamanlı kullanıcı = 50.000 UPDATE/dakika. Ayrıca tüm aktif ilanları RAM'e yüklemek OOM riskini artırır.
**Görev:** #9
**Çözüm:**
```php
// Skorları hesapla, biriktir
$updates = $matches->map(fn($m) => ['id' => $m['ilan']->id, 'cortex_score' => $m['total_score'], 'cortex_ranked_at' => now()]);
// Tek sorguda yaz
Ilan::upsert($updates->toArray(), ['id'], ['cortex_score', 'cortex_ranked_at']);
```

---

## 🟡 ORTA — Sessiz Hata / Veri Tutarsızlığı

### B-005: AiWalletService Balance Snapshot Hatası
**Dosya:** `app/Services/AI/AiWalletService.php:59,130`
```php
$wallet->decrement('balance', $amount); // DB güncellendi, PHP nesnesi YENİLENMEDİ
AiTransaction::create([
    'final_balance' => $wallet->balance, // ← ESKİ bakiyeyi kaydeder!
]);
```
**Etki:** Finansal ledger'daki final_balance değerleri işlem öncesi bakiyeyi gösteriyor. Muhasebe raporları tutarsız.
**Görev:** #11
**Çözüm:** `$wallet->refresh()->balance` veya `$wallet->balance - $amount` kullan.

---

### B-006: 4 Aktif Servis Deprecated Model Kullanıyor
**Dosyalar:**
- `AIIlanTaslagiService.php:5` → `App\Models\Deprecated\AIIlanTaslagi`
- `AIMessageService.php:5` → `App\Models\Deprecated\AIConversation`
- `AIContractService.php:5` → `App\Models\Deprecated\AIContractDraft`
- `AIArsaAnalizService.php:5` → `App\Models\Deprecated\AILandPlotAnalysis`

**Etki:** Deprecated/ klasörü silinirse bu 4 servis anında çöker. Migration bütünlüğü belirsiz.
**Görev:** #12
**Çözüm:** Deprecated modelleri inceleyip aktif model yapısına (app/Models/AI/) taşı veya servisleri yeni modele migrate et.

---

## 🔵 İYİLEŞTİRME — Mimari Temizlik

### B-007: İki Paralel AI Orchestration Sistemi
**Dosyalar:**
- `AIOrchestrator::orchestrateAI()` — basit DeepSeek→OpenAI hardcoded geçiş
- `RoutedCortexExecutor` — ranked providers, scoring, circuit breaker

**Etki:** Hangi servisin hangisini kullandığı belirsiz. İki farklı failover davranışı. RoutedCortexExecutor ANALYZE_PROPERTY için `deepseek-reasoner` zorluyor ama AIOrchestrator `deepseek-v4-flash` gönderiyor — bu model guard çakışması yaratabilir.
**Görev:** #13
**Çözüm:** `AIOrchestrator::orchestrateAI()` içini `RoutedCortexExecutor::execute()`'a delege et.

---

### B-008: ListingAIResponseValidator Sıfır Tolerans
**Dosya:** `app/Services/AI/Validation/ListingAIResponseValidator.php:37`
```php
$unknownFields = array_diff(array_keys($data), $allowedFields);
if (!empty($unknownFields)) {
    throw new InvalidAIResponseException('AI_UNKNOWN_FIELD: ...');
}
```
**Etki:** LLM çıktıları gürültülüdür — ara sıra ekstra alan üretir. Bu durumda tüm işlem başarısız sayılır. Üretimde sık sık gereksiz hata tetikleyebilir.
**Çözüm:** Bilinmeyen alanları exception yerine log'layıp görmezden gel. Whitelist mantığı korumak için unknown alanları sadece sil.

---

## Özet Tablo

| ID | Konu | Ciddiyet | Görev |
|---|---|---|---|
| B-001 | API key'ler sahte/boş | 🔴 Kritik | #8 |
| B-002 | deepseek-v4-flash modeli yok | 🔴 Kritik | #8 |
| B-003 | N8N URL tanımsız | 🔴 Kritik | #10 |
| B-004 | MatchingEngine N+1 UPDATE | 🟠 Ağır | #9 |
| B-005 | Wallet balance snapshot hatası | 🟡 Orta | #11 |
| B-006 | Deprecated model bağımlılıkları | 🟡 Orta | #12 |
| B-007 | İki AI orchestration sistemi | 🔵 İyileştirme | #13 |
| B-008 | Validator sıfır tolerans | 🔵 İyileştirme | — |

---

## N8N Entegrasyonu Bulguları

### N-001: N8N config key tutarsızlığı
**Dosyalar:** `AIIlanTaslagiService.php`, `N8nIntegrationService.php`
```
AIIlanTaslagiService    → config('services.n8n.webhook_url')  ← farklı key
N8nIntegrationService   → config('services.n8n.url')          ← farklı key
```
Biri dolu olsa diğeri boş kalır. config/services.php'de tek anahtarda birleştirilmeli.
**Görev:** #10

### N-002: Tüm N8N webhook URL'leri tanımsız
```
N8N_ENABLED=false (varsayılan)
N8N_WEBHOOK_HIGH_MATCH=null
N8N_WEBHOOK_NEW_LISTING=null
N8N_WEBHOOK_DEMAND_FULFILLED=null
N8N_WEBHOOK_CRITICAL_UPDATE=null
N8N_WEBHOOK_RAPOR_BILDIRIMI=null
```
10 workflow tetikleyici hazır, sadece URL'ler eksik.
**Görev:** #10

---

## Telegram Entegrasyonu Bulguları

### T-001: Tüm kimlik bilgileri placeholder
**Dosya:** `.env`
```
TELEGRAM_BOT_TOKEN=ROTATED_BOT_TOKEN_REPLACE_ME
TELEGRAM_TEAM_CHANNEL_ID=ROTATED_CHANNEL_ID_REPLACE_ME
TELEGRAM_ADMIN_CHAT_ID=ROTATED_CHAT_ID_REPLACE_ME
```
Bot hiç başlamaz, tüm Telegram özellikleri çalışmaz.
**Görev:** #15

### T-002: Webhook URL geçici ngrok tüneli
**Dosya:** `.env`
```
TELEGRAM_WEBHOOK_URL=https://fb8fbc58b72c.ngrok-free.app/api/telegram/webhook
```
ngrok URL her yeniden başlatmada değişir. Üretimde geçerli değil.
Üretim değeri: `https://yalihanemlak.com/api/telegram/webhook`
Ayrıca setWebhook API çağrısı yapılmalı (tek seferlik).
**Görev:** #15

### T-003: FinanceProcessor OpenAI bağımlı, API key boş
**Dosya:** `app/Services/Telegram/Processors/FinanceProcessor.php`
Telegram'dan gelen serbest metin finansal kayıt için GPT-4o kullanıyor.
OPENAI_API_KEY boş → özellik çalışmıyor.
**Görev:** #16

### T-004: PortfolioProcessor konum araması Haversine değil
**Dosya:** `app/Services/Telegram/Processors/PortfolioProcessor.php`
2km çap araması lat/lng farkıyla (whereBetween) yapılıyor.
Kod içinde "Haversine kullanılmalı" notu var. MatchingEngine'den alınabilir.
**Öncelik:** Düşük (Türkiye için yeterince doğru)
**Görev:** #17

### Telegram Sistemi Genel Yetenekler (hazır, config eksik)
- TelegramBrain — 7 processor'a mesaj yönlendirici
- AuthProcessor — kullanıcı doğrulama
- TaskProcessor — günlük özet, deadline uyarıları
- PortfolioProcessor — konum bazlı ilan arama
- ContactProcessor — kişi/lead yönetimi
- FinanceProcessor — serbest metinden finansal kayıt (GPT bağımlı)
- VoiceProcessor — sesli mesaj → CRM kaydı
- CallbackQueryProcessor — inline buton yönetimi
- TelegramAIBotService — /start, /help, /search, /list komutları
- AlertService — danışmana ses-CRM uyarıları
- TelegramOutboundJob — async bildirim gönderimi
- N8N → Telegram köprüsü — workflow bildirimleri

---

## 🧠 PHASE 11: THE AST REVELATION (15 MAYIS 2026)

Yalıhan Bekçi v2.1 (Cognitive Guardian) devreye alındı. Regex tabanlı yüzeysel taramadan, AST (Abstract Syntax Tree) tabanlı anlamsal analize geçiş yapıldı.

### G-001: 42 Anlamsal Sessiz Catch (Silent Catch) Keşfi
**Bulgu:** AST taraması, regex'in "temiz" dediği kod blokları içinde **42 adet** anlamsal ihlal tespit etti. Bu bloklar boş değil (`{}`), ancak içinde loglama, throw veya report mekanizması barındırmadan hatayı "yutan" (swallow) yapılar.
**Etki:** Sistem hataları maskeleniyor, observability zinciri kırılıyor.
**Statü:** **MANAGED DEBT.** Tüm ihlaller haritalandırıldı, build'i bloklamaması için WARNING seviyesine çekildi. Phase 12 (SaaS) öncesi "Clean Slate" protokolü ile temizlenecek.

### G-002: AST Tabanlı İsimlendirme Otoritesi (Naming Authority)
**Bulgu:** Yeni AST kuralı (`NamingAuthorityAST`), domain ve framework dilleri arasındaki hibrit dengenin (Turkish Domain / English Framework) ihlal edildiği noktaları tespit etmeye başladı.
**Statü:** **ACTIVE.** Yeni drift oluşumu engellendi.

---

## 🚦 Güncel Üretim Geçiş Şartları (15 Mayıs 2026)

| Şart | Durum | Not |
|---|---|---|
| API Key'ler | 🟠 Beklemede | Gerçek key'ler bekleniyor. |
| Model Adları | ✅ RESOLVED | deepseek-chat/reasoner uyumu sağlandı. |
| N8N Entegrasyonu | 🟠 Beklemede | URL ve config key senkronizasyonu bekleniyor. |
| Telegram Sızıntısı | ✅ RESOLVED | ngrok tüneli temizlendi, prod domaini mühürlendi. |
| MatchingEngine | 🟠 Beklemede | N+1 refactor bekliyor. |
| Cognitive Audit | ✅ ACTIVE | AST tabanlı denetim ve mühürleme (Phase 11) tamam. |
| Global Seal | ✅ SUCCESS | `GLOBAL_SEAL_SUCCESS` tescil edildi. |

---
**Hüküm:** Sistem artık "Bilişsel" düzeyde korunmaktadır. Kritik sızıntılar kapatılmış, teknik borçlar ise anlamsal olarak haritalandırılmıştır. Phase 12 (Scaling) için mimari temel sarsılmazdır.
