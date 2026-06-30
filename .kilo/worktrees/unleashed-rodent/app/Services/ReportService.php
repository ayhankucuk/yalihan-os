<?php

namespace App\Services;

use App\Models\Ilan;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf as DomPDF;

/**
 * Yalihan Report Service
 *
 * [YALIHAN_REPORTING_0206]
 * Mühürlü Yatırım Raporu üretimi
 * - NeuralRoiEngine entegrasyonu
 * - Snappy/DomPDF fallback
 * - Signed URL ile 24 saat erişim
 * - Silme yok, sadece invalidate
 */
class ReportService
{
    protected bool $useSnappy = false;

    public function __construct()
    {
        // Check if Snappy is available
        $this->useSnappy = class_exists('Knp\Snappy\Pdf') && config('snappy.pdf.binary');
    }

    /**
     * Generate mühürlü rapor for ilan
     *
     * @param  Ilan  $ilan
     * @param  string  $locale  'tr' or 'en'
     * @return array ['success' => bool, 'path' => string|null, 'hash' => string|null, 'metadata' => array]
     */
    public function generate(Ilan $ilan, string $locale = 'tr'): array
    {
        try {
            Log::info('[YALIHAN_REPORTING] Starting report generation', [
                'ilan_id' => $ilan->id,
                'locale' => $locale,
                'request_id' => request()->id ?? Str::uuid(),
            ]);

            // 1. Build data
            $data = $this->buildData($ilan, $locale);

            // 2. Render view to HTML
            $viewPath = "reports.{$locale}.neural_analiz";
            if (!view()->exists($viewPath)) {
                $viewPath = "reports.tr.neural_analiz"; // Fallback to Turkish
            }

            $html = view($viewPath, $data)->render();

            // 3. Render PDF
            $pdfBinary = $this->renderPdf($html);

            // 4. Store PDF
            $result = $this->storePdf($ilan, $pdfBinary, $locale);

            Log::info('[YALIHAN_REPORTING] Report generated successfully', [
                'ilan_id' => $ilan->id,
                'path' => $result['path'],
                'hash' => $result['hash'],
            ]);

            return [
                'success' => true,
                'path' => $result['path'],
                'hash' => $result['hash'],
                'metadata' => [
                    'locale' => $locale,
                    'generated_at' => now()->toIso8601String(),
                    'pdf_engine' => $this->useSnappy ? 'snappy' : 'dompdf',
                ],
            ];
        } catch (\Exception $e) {
            Log::error('[YALIHAN_REPORTING] Report generation failed', [
                'ilan_id' => $ilan->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'path' => null,
                'hash' => null,
                'metadata' => [
                    'error' => $e->getMessage(),
                ],
            ];
        }
    }

    /**
     * Build data for report template
     * Integrates NeuralRoiEngine outputs
     *
     * @param  Ilan  $ilan
     * @param  string  $locale
     * @return array
     */
    protected function buildData(Ilan $ilan, string $locale = 'tr'): array
    {
        // Load ilan with necessary relationships
        $ilan->load([
            'anaKategori',
            'altKategori',
            'yayinTipi',
            'il',
            'ilce',
            'mahalle',
        ]);

        // Opportunity Engine data
        // Context7: Opportunity model is replaced by field relationships.

        $firsatData = [
            'firsat_skoru' => null,
            'skor_detayi' => null,
            'firsat_nedeni' => null,
            'firsat_durumu' => null,
        ];

        // Neural ROI Engine data (stub - gerçek engine hazır olunca doldurulacak)
        $roiData = $this->getNeuralRoiData($ilan);

        // Coordinates for map
        $coordinates = [
            'latitude' => $ilan->latitude ?? null,
            'longitude' => $ilan->longitude ?? null,
            'has_coordinates' => !empty($ilan->latitude) && !empty($ilan->longitude),
        ];

        // Address data
        $address = $this->buildAddressString($ilan);

        return [
            // Ilan basic info
            'ilan' => $ilan,
            'baslik' => $ilan->baslik ?? '-',
            'ilan_id' => $ilan->id,
            'ilan_no' => $ilan->ilan_no ?? '-',

            // Category info
            'kategori' => $ilan->altKategori?->name ?? $ilan->anaKategori?->name ?? '-',
            'yayin_tipi' => $ilan->yayinTipi?->ad ?? $ilan->yayinTipi?->name ?? '-',

            // Location
            'lokasyon' => $address,
            'coordinates' => $coordinates,

            // Price
            'fiyat' => $ilan->fiyat ? number_format($ilan->fiyat, 0, ',', '.') : '-',
            'para_birimi' => $ilan->para_birimi ?? 'TRY',

            // Opportunity Engine
            'firsat' => $firsatData,

            // Neural ROI Engine
            'roi' => $roiData,

            // Report meta
            'locale' => $locale,
            'rapor_tarihi' => now()->format('d.m.Y H:i'),
            'rapor_hash_preview' => Str::random(8), // Will be replaced with real hash

            // QR data (will contain signed URL after generation)
            'qr_data' => route('admin.ilanlar.index'), // Placeholder
        ];
    }

    /**
     * Get Neural ROI Engine data
     * [Phase 8] NeuralRoiEngine hazır olduğunda entegre edilecek
     */
    protected function getNeuralRoiData(Ilan $ilan): array
    {
        // Stub implementation - gerçek engine hazır olunca değiştirilecek
        return [
            'tahmini_getiri' => null,
            'yillik_kira_getirisi' => null,
            'deger_artis_tahmini' => null,
            'risk_seviyesi' => null,
            'yatirim_puani' => null,
            'roi_breakdown' => [
                // Veri yoksa boş döner
            ],
        ];
    }

    /**
     * Build readable address string
     */
    protected function buildAddressString(Ilan $ilan): string
    {
        $parts = array_filter([
            $ilan->mahalle?->mahalle_adi ?? $ilan->mahalle?->name ?? null,
            $ilan->ilce?->ilce_adi ?? $ilan->ilce?->name ?? null,
            $ilan->il?->il_adi ?? $ilan->il?->name ?? null,
        ], fn($val) => !empty($val) && $val !== 'Belirtilmemiş');

        return !empty($parts) ? implode(', ', $parts) : '-';
    }

    /**
     * Render HTML to PDF
     * Tries Snappy first, falls back to DomPDF
     */
    protected function renderPdf(string $html): string
    {
        if ($this->useSnappy) {
            try {
                return $this->renderWithSnappy($html);
            } catch (\Exception $e) {
                Log::warning('[YALIHAN_REPORTING] Snappy failed, falling back to DomPDF', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $this->renderWithDomPdf($html);
    }

    /**
     * Render with Snappy (faster, better quality)
     */
    protected function renderWithSnappy(string $html): string
    {
        $snappy = app('snappy.pdf');

        return $snappy->getOutputFromHtml($html, [
            'encoding' => 'UTF-8',
            'page-size' => 'A4',
            'margin-top' => 10,
            'margin-right' => 10,
            'margin-bottom' => 10,
            'margin-left' => 10,
            'print-media-type' => true,
        ]);
    }

    /**
     * Render with DomPDF (fallback)
     */
    protected function renderWithDomPdf(string $html): string
    {
        $pdf = DomPDF::loadHTML($html);
        $pdf->setPaper('A4', 'portrait');

        return $pdf->output();
    }

    /**
     * Store PDF to storage with hash-based filename
     *
     * @return array ['path' => string, 'hash' => string]
     */
    protected function storePdf(Ilan $ilan, string $pdfBinary, string $locale): array
    {
        $year = now()->format('Y');
        $month = now()->format('m');

        // Generate unique hash
        $hash = hash('sha256', $ilan->id . '|' . now()->timestamp . '|' . Str::random(16));
        $hashShort = substr($hash, 0, 16); // Use first 16 chars

        // Build filename: YALIHAN_REPORT_{ID}_{HASH}.pdf
        $filename = sprintf('YALIHAN_REPORT_%d_%s.pdf', $ilan->id, $hashShort);

        // Build directory path: mühürlü_raporlar/Y/m/
        $directory = "mühürlü_raporlar/{$year}/{$month}";
        $fullPath = "{$directory}/{$filename}";

        // Store file
        Storage::disk('local')->put($fullPath, $pdfBinary);

        Log::info('[YALIHAN_REPORTING] PDF stored', [
            'ilan_id' => $ilan->id,
            'path' => $fullPath,
            'size' => strlen($pdfBinary),
        ]);

        return [
            'path' => $fullPath,
            'hash' => $hashShort,
        ];
    }

    /**
     * Invalidate existing report (no deletion!)
     */
    public function invalidate(Ilan $ilan): bool
    {
        if (!$ilan->rapor_yolu) {
            return false; // No report to invalidate
        }

        $ilan->update([
            'rapor_gecersiz_mi' => true,
            'rapor_gecersizlestirildi_at' => now(),
        ]);

        Log::info('[YALIHAN_REPORTING] Report invalidated', [
            'ilan_id' => $ilan->id,
            'rapor_yolu' => $ilan->rapor_yolu,
        ]);

        return true;
    }

    /**
     * Regenerate report (invalidate old + generate new)
     */
    public function regenerate(Ilan $ilan, string $locale = 'tr'): array
    {
        // Invalidate existing
        if ($ilan->rapor_yolu) {
            $this->invalidate($ilan);
        }

        // Generate new
        $result = $this->generate($ilan, $locale);

        if ($result['success']) {
            // Increment version
            $ilan->increment('rapor_surum');
        }

        return $result;
    }
}
