<?php

namespace App\Services\AI\Copilot;

use App\Models\BaseModel;
use App\Services\AI\YalihanCortex;
use Illuminate\Support\Facades\Log;
use App\Domain\AI\Exceptions\InvalidAIResponseException;

/**
 * Class BaseCopilotEngine
 *
 * SAB Core v2.6 / Context7 Standartlarına Göre Mühürlenmiştir.
 * Tüm Copilot alt motorları (Audit, Prediction, Rule) bu çekirdek yapıyı extend etmek zorundadır.
 *
 * ARCHITECTURAL GOVERNANCE:
 * - Zero Trust Runtime: Her motor çağrısı telemetri ile loglanır
 * - CQRS Compliance: Motorlar sadece read-model projeksiyonları üzerinden beslenir
 * - Context7 Canonical: Tüm çıktılar kanonik sözlük standartlarına uyar
 * - Fail-Loud Principle: Exception'lar yutulmaz, log + rethrow zorunludur
 *
 * @package App\Services\AI\Copilot
 * @version 2.6.0
 * @since Phase 13 Sprint +2
 */
abstract class BaseCopilotEngine
{
    /**
     * @var YalihanCortex Merkezi AI Orkestratörü
     */
    protected YalihanCortex $cortex;

    /**
     * BaseCopilotEngine Constructor.
     *
     * @param YalihanCortex $cortex
     */
    public function __construct(YalihanCortex $cortex)
    {
        $this->cortex = $cortex;
    }

    /**
     * Motorun ana iş kurallarını (Business Logic) yürüten soyut metod.
     *
     * Bu metod her alt motor tarafından implement edilmek zorundadır.
     * Giriş olarak bir domain modeli (Ilan, Kisi, Talep vb.) ve ek bağlam parametreleri alır.
     * Çıkış olarak Context7 uyumlu, semantik analiz sonuçlarını içeren bir array döner.
     *
     * @param BaseModel $model İşlenecek olan domain modeli (Kisi, Ilan vb.)
     * @param array<string, mixed> $baglam Ekstra parametre ve telemetri bağlamı
     * @return array<string, mixed> Context7 uyumlu semantik analiz çıktısı
     * @throws InvalidAIResponseException Motor çalışması sırasında kritik hata oluşursa
     */
    abstract public function analizEt(BaseModel $model, array $baglam = []): array;

    /**
     * Sentezlenen kararları ve analiz sonuçlarını fail-loud prensibiyle loglar.
     *
     * Bu metod her motor çağrısının sonunda telemetri verilerini merkezi log sistemine kaydeder.
     * Governance ve audit trail için kritik öneme sahiptir.
     *
     * @param string $motorAdi Alt motorun kanonik adı (örn: 'CopilotAuditEngine')
     * @param array<string, mixed> $telemetri Veri dökümü (context, sonuçlar, metrikler)
     * @return void
     */
    protected function semantikLogEkle(string $motorAdi, array $telemetri): void
    {
        Log::info(sprintf('[CopilotEngine] Semantik sinyal üretildi: %s', $motorAdi), [
            'motor' => $motorAdi,
            'telemetri' => $telemetri,
            'olusturma_tarihi' => now()->toIso8601String(),
            'tenant_id' => $telemetri['tenant_id'] ?? null,
            'user_id' => $telemetri['user_id'] ?? null,
        ]);
    }

    /**
     * Motorun çalışma zamanı performans metriklerini hesaplar.
     *
     * @param float $baslangicZamani microtime(true) ile alınan başlangıç zamanı
     * @return array<string, mixed> Performans metrikleri
     */
    protected function performansMetrikleri(float $baslangicZamani): array
    {
        $bitisZamani = microtime(true);
        $sureMilisaniye = round(($bitisZamani - $baslangicZamani) * 1000, 2);

        return [
            'sure_ms' => $sureMilisaniye,
            'baslangic' => $baslangicZamani,
            'bitis' => $bitisZamani,
            'performans_seviyesi' => match (true) {
                $sureMilisaniye < 10 => 'excellent',
                $sureMilisaniye < 50 => 'good',
                $sureMilisaniye < 200 => 'acceptable',
                default => 'slow',
            },
        ];
    }

    /**
     * Hata durumlarını Context7 uyumlu formatta döner.
     *
     * @param \Exception $exception Yakalanan exception
     * @param string $motorAdi Motor adı
     * @param array<string, mixed> $baglam Hata bağlamı
     * @return array<string, mixed> Standart hata yanıtı
     */
    protected function hataYaniti(\Exception $exception, string $motorAdi, array $baglam = []): array
    {
        Log::error(sprintf('[CopilotEngine] Motor hatası: %s', $motorAdi), [
            'motor' => $motorAdi,
            'hata_mesaji' => $exception->getMessage(),
            'hata_kodu' => $exception->getCode(),
            'baglam' => $baglam,
            'stack_trace' => $exception->getTraceAsString(),
        ]);

        return [
            'basarili' => false,
            'hata' => true,
            'hata_mesaji' => $exception->getMessage(),
            'hata_kodu' => $exception->getCode(),
            'motor' => $motorAdi,
            'zaman_damgasi' => now()->toIso8601String(),
        ];
    }

    /**
     * Cortex entegrasyonu için standart payload oluşturur.
     *
     * @param string $islemTipi İşlem tipi (örn: 'semantic_analysis', 'prediction')
     * @param array<string, mixed> $veri İşlenecek veri
     * @return array<string, mixed> Cortex uyumlu payload
     */
    protected function cortexPayloadOlustur(string $islemTipi, array $veri): array
    {
        return [
            'islem_tipi' => $islemTipi,
            'veri' => $veri,
            'zaman_damgasi' => now()->toIso8601String(),
            'motor_versiyonu' => '2.6.0',
            'context7_uyumlu' => true,
        ];
    }
}
