<?php

namespace App\Services;

/**
 * @sab-ignore-catch
 */

use App\Services\Cache\CacheHelper;
use App\Services\Logging\LogService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

/**
 * QR Code Service
 *
 * Context7: QR code generation for listings
 * - İlan detay sayfası QR kodu
 * - İlan kartları QR kodu
 * - Print-friendly QR kodlar
 * - AI-powered QR code suggestions
 */
class QRCodeService
{
    protected string $storagePath = 'public/qrcodes';

    protected int $defaultSize = 300;

    protected array $defaultColors = [
        'foreground' => [0, 0, 0], // Siyah
        'background' => [255, 255, 255], // Beyaz
    ];

    /**
     * Get setting value with fallback
     */
    protected function getSetting(string $key, $default = null)
    {
        return \App\Models\Setting::get($key, $default);
    }

    /**
     * Check if QR code is enabled
     */
    public function isEnabled(): bool
    {
        return $this->getSetting('qrcode_durumu', true);
    }

    /**
     * Get default size from settings
     */
    protected function getDefaultSize(): int
    {
        return (int) $this->getSetting('qrcode_default_size', 300);
    }

    /**
     * Generate QR code for a listing
     *
     * @param  int  $ilanId  Listing ID
     * @param  array  $options  QR code options
     * @return array QR code data (path, url, base64)
     */
    public function generateForListing(int $ilanId, array $options = []): array
    {
        // Check if QR code is enabled
        if (! $this->isEnabled()) {
            throw new \Exception('QR kod özelliği devre dışı bırakılmış');
        }

        $cacheKey = "qrcode.listing.{$ilanId}.".md5(json_encode($options));

        return CacheHelper::remember(
            'qrcode',
            "listing_{$ilanId}",
            'long', // 7 days
            function () use ($ilanId, $options) {
                try {
                    $url = route('ilanlar.show', $ilanId);

                    $size = $options['size'] ?? $this->getDefaultSize();
                    $format = $options['format'] ?? 'svg'; // SVG doesn't require imagick
                    $foreground = $options['foreground'] ?? $this->defaultColors['foreground'];
                    $background = $options['background'] ?? $this->defaultColors['background'];

                    // Generate QR code
                    // SVG format doesn't support color/backgroundColor parameters
                    if ($format === 'svg') {
                        $qrCode = QrCode::format($format)
                            ->size($size)
                            ->margin(1)
                            ->errorCorrection('H')
                            ->generate($url);
                    } else {
                        $qrCode = QrCode::format($format)
                            ->size($size)
                            ->color($foreground[0], $foreground[1], $foreground[2])
                            ->backgroundColor($background[0], $background[1], $background[2])
                            ->margin(1)
                            ->errorCorrection('H')
                            ->generate($url);
                    }

                    // Save to storage
                    $filename = "listing-{$ilanId}-".time().".{$format}";
                    $path = "{$this->storagePath}/{$filename}";

                    Storage::put($path, $qrCode);

                    return [
                        'path' => $path,
                        'url' => Storage::url($path),
                        'base64' => 'data:image/'.($format === 'svg' ? 'svg+xml' : $format).';base64,'.base64_encode($qrCode),
                        'filename' => $filename,
                        'size' => $size,
                        'format' => $format,
                    ];
                } catch (\Exception $e) {
                    LogService::error('QR code generation failed', ['ilan_id' => $ilanId], $e);
                    throw $e;
                }
            },
            $options
        );
    }

    /**
     * Generate QR code for WhatsApp sharing
     *
     * @param  int  $ilanId  Listing ID
     * @param  string|null  $phoneNumber  WhatsApp number
     */
    public function generateForWhatsApp(int $ilanId, ?string $phoneNumber = null): array
    {
        $phoneNumber = $phoneNumber ?? config('app.whatsapp_number', '905332090302');
        $url = route('ilanlar.show', $ilanId);
        $message = urlencode("Merhaba, {$ilanId} numaralı ilan hakkında bilgi almak istiyorum.");
        $whatsappUrl = "https://wa.me/{$phoneNumber}?text={$message}";

        return $this->generateForUrl($whatsappUrl, [
            'size' => 250,
            'filename_prefix' => "whatsapp-{$ilanId}",
        ]);
    }

    /**
     * Generate QR code for any URL
     *
     * @param  string  $url  URL to encode
     * @param  array  $options  Options
     */
    public function generateForUrl(string $url, array $options = []): array
    {
        try {
            $size = $options['size'] ?? $this->defaultSize;
            $format = $options['format'] ?? 'svg'; // SVG doesn't require imagick
            $filename = $options['filename_prefix'] ?? 'qrcode';

            $qrCode = QrCode::format($format)
                ->size($size)
                ->margin(1)
                ->errorCorrection('H')
                ->generate($url);

            $filename = "{$filename}-".time().".{$format}";
            $path = "{$this->storagePath}/{$filename}";

            Storage::put($path, $qrCode);

            return [
                'path' => $path,
                'url' => Storage::url($path),
                'base64' => 'data:image/'.($format === 'svg' ? 'svg+xml' : $format).';base64,'.base64_encode($qrCode),
                'filename' => $filename,
            ];
        } catch (\Exception $e) {
            LogService::error('QR code generation failed', ['url' => $url], $e);
            throw $e;
        }
    }

    /**
     * Get QR code data (with cache)
     *
     * @param  int  $ilanId  Listing ID
     */
    public function getForListing(int $ilanId): ?array
    {
        try {
            return $this->generateForListing($ilanId);
        } catch (\Exception $e) {
            LogService::error('QR code retrieval failed', ['ilan_id' => $ilanId], $e);

            return null;
        }
    }

    /**
     * Delete QR code for a listing
     *
     * @param  int  $ilanId  Listing ID
     */
    public function deleteForListing(int $ilanId): bool
    {
        try {
            $files = Storage::files($this->storagePath);
            $pattern = "listing-{$ilanId}-";

            foreach ($files as $file) {
                if (str_contains($file, $pattern)) {
                    Storage::delete($file);
                }
            }

            CacheHelper::forget('qrcode', "listing_{$ilanId}");

            return true;
        } catch (\Exception $e) {
            LogService::error('QR code deletion failed', ['ilan_id' => $ilanId], $e);

            return false;
        }
    }

    /**
     * Get QR code statistics
     */
    public function getStatistics(): array
    {
        try {
            $files = Storage::files($this->storagePath);
            $totalSize = 0;

            foreach ($files as $file) {
                $totalSize += Storage::size($file);
            }

            return [
                'total_files' => count($files),
                'total_size' => $totalSize,
                'total_size_mb' => round($totalSize / 1024 / 1024, 2),
                'storage_path' => $this->storagePath,
            ];
        } catch (\Exception $e) {
            LogService::error('QR code statistics failed', [], $e);

            return [
                'total_files' => 0,
                'total_size' => 0,
                'total_size_mb' => 0,
            ];
        }
    }
}
