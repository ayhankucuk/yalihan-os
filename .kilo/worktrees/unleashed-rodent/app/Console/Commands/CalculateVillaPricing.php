<?php

namespace App\Console\Commands;

use App\Services\VillaPricingCalculatorService;
use Illuminate\Console\Command;

/**
 * Villa Fiyatlandırma Hesaplama Komutu
 *
 * Context7: C7-VILLA-PRICING-CALCULATOR-2025-11-30
 *
 * Bu komut, villalar için günlük fiyattan haftalık, aylık ve sezonluk
 * fiyat önerileri üretir. Danışmanların hesap makinesiyle uğraşmasını
 * engeller ve fiyatlandırma stratejisini standartlaştırır.
 *
 * Kullanım:
 *   php artisan villa:calculate-pricing 10000
 *   php artisan villa:calculate-pricing 10000 --currency=USD
 */
class CalculateVillaPricing extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'villa:calculate-pricing
                            {daily_price : Günlük fiyat (örn: 10000)}
                            {--currency=TRY : Para birimi (TRY, USD, EUR)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Villa fiyatlandırma hesaplamaları - Günlük fiyattan haftalık/aylık/sezonluk öneriler';

    protected VillaPricingCalculatorService $calculator;

    /**
     * Create a new command instance.
     */
    public function __construct(VillaPricingCalculatorService $calculator)
    {
        parent::__construct();
        $this->calculator = $calculator;
    }

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dailyPrice = (float) $this->argument('daily_price');
        $currency = $this->option('currency') ?? 'TRY';

        if ($dailyPrice <= 0) {
            $this->error('❌ Günlük fiyat 0\'dan büyük olmalıdır!');
            return Command::FAILURE;
        }

        $this->info('🏖️  Villa Fiyatlandırma Hesaplama');
        $this->info(str_repeat('=', 80));
        $this->newLine();

        // Hesaplamaları yap
        $results = $this->calculator->calculateAllPrices($dailyPrice, $currency);

        // Günlük fiyat
        $this->info('📊 Günlük Fiyat:');
        $this->line("   {$results['daily_price']['formatted']} - {$results['daily_price']['description']}");
        $this->newLine();

        // Haftalık fiyat
        $this->info('📅 Haftalık Fiyat:');
        $this->line("   {$results['weekly_price']['formatted']} - {$results['weekly_price']['description']}");
        $this->line("   💰 Tasarruf: {$results['weekly_price']['savings_formatted']}");
        $this->newLine();

        // Aylık fiyat
        $this->info('📆 Aylık Fiyat:');
        $this->line("   {$results['monthly_price']['formatted']} - {$results['monthly_price']['description']}");
        $this->line("   💰 Tasarruf: {$results['monthly_price']['savings_formatted']}");
        $this->newLine();

        // Sezonluk fiyatlar
        $this->info('🌍 Sezonluk Fiyatlar:');
        foreach ($results['seasonal_prices'] as $season => $data) {
            $seasonName = match ($season) {
                'yaz' => '☀️  Yaz Sezonu',
                'ara_sezon' => '🌤️  Ara Sezon',
                'kis' => '❄️  Kış Sezonu',
                default => ucfirst($season),
            };

            $this->line("   {$seasonName}: {$data['formatted']}");
            if (isset($data['discount']) && $data['discount'] > 0) {
                $this->line("      (%{$data['discount']} indirim)");
            }
        }
        $this->newLine();

        // Öneriler
        $this->info('💡 Danışman Önerileri:');
        $this->info(str_repeat('-', 80));

        foreach ($results['recommendations'] as $recommendation) {
            $priorityIcon = match ($recommendation['priority']) {
                'high' => '🔥',
                'medium' => '⭐',
                'low' => '📋',
                default => '💡',
            };

            $this->line("   {$priorityIcon} {$recommendation['title']}:");
            $this->line("      {$recommendation['message']}");

            if (isset($recommendation['savings'])) {
                $this->line("      💰 Müşteri tasarrufu: {$this->formatPrice($recommendation['savings'],$currency)}");
            }

            if (isset($recommendation['discount'])) {
                $this->line("      📉 İndirim oranı: %{$recommendation['discount']}");
            }

            $this->newLine();
        }

        // Özet tablo
        $this->info('📋 Özet Tablo:');
        $this->table(
            ['Fiyat Tipi', 'Değer', 'Açıklama'],
            [
                ['Günlük', $results['daily_price']['formatted'], 'Temel fiyat'],
                ['Haftalık', $results['weekly_price']['formatted'], '7 gün × %5 indirim'],
                ['Aylık', $results['monthly_price']['formatted'], '30 gün × %10 indirim'],
                ['Kış Sezonu', $results['seasonal_prices']['kis']['formatted'], 'Günlük × %50 indirim'],
                ['Ara Sezon', $results['seasonal_prices']['ara_sezon']['formatted'], 'Günlük × %30 indirim'],
            ]
        );

        $this->newLine();
        $this->info('✅ Hesaplama tamamlandı!');
        $this->line('💡 Bu önerileri danışmanlara sunarak fiyatlandırma stratejisini standartlaştırabilirsiniz.');

        return Command::SUCCESS;
    }

    /**
     * Fiyatı formatla
     *
     * @param  float  $price
     * @param  string  $currency
     * @return string
     */
    private function formatPrice(float $price, string $currency = 'TRY'): string
    {
        $formatted = number_format($price, 2, ',', '.');

        return match ($currency) {
            'TRY' => "{$formatted} ₺",
            'USD' => "\${$formatted}",
            'EUR' => "€{$formatted}",
            default => "{$formatted} {$currency}",
        };
    }
}
