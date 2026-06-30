{{-- ========================================
     QR CODE DISPLAY COMPONENT
     Context7: QR code for listings
     ======================================== --}}

@props([
    'ilan' => null,
    'ilanId' => null,
    'size' => 'medium', // small, medium, large
    'showLabel' => true,
    'showDownload' => true,
    'showWhatsApp' => false,
    'type' => 'listing', // listing, whatsapp, custom
    'customUrl' => null,
])

@php
    $ilanId = $ilanId ?? $ilan->id ?? null;

    if (!$ilanId && !$customUrl) {
        return;
    }

    $qrCodeService = app(\App\Services\QRCodeService::class);

    // Check if QR code is status from settings
    if (!$qrCodeService->isEnabled()) {
        return;
    }

    // Check if should show on cards/detail pages
    if ($type === 'listing' && isset($ilan)) {
        $showOnCards = setting('qrcode_show_on_cards', true);
        $showOnDetail = setting('qrcode_show_on_detail', true);

        // Component context'e göre kontrol et (detay sayfası mı, kart mı?)
        // Bu kontrol component kullanım yerine göre yapılabilir
    }

    $sizeMap = [
        'small' => 200,
        'medium' => 300,
        'large' => 400
    ];

    $qrSize = $sizeMap[$size] ?? 300;

    try {
        if ($type === 'whatsapp' || $showWhatsApp) {
            $qrData = $qrCodeService->generateForWhatsApp($ilanId);
        } elseif ($customUrl) {
            $qrData = $qrCodeService->generateForUrl($customUrl, ['size' => $qrSize]);
        } else {
            $qrData = $qrCodeService->generateForListing($ilanId, ['size' => $qrSize]);
        }
    } catch (\Exception $e) {
        $qrData = null;
    }
@endphp

@if($qrData)
<div class="qr-code-display inline-flex flex-col items-center gap-3 p-4 bg-white dark:bg-slate-900 rounded-xl shadow-lg border border-gray-200 dark:border-slate-800 transition-all duration-200 hover:shadow-xl"
     x-data="{ showDownload: false }">

    {{-- QR Code Image --}}
    <div class="qr-code-image relative">
        {{-- Context7: Use base64 for reliability, fallback to URL --}}
        <img src="{{ $qrData['base64'] ?? $qrData['url'] ?? '' }}"
             alt="QR Code"
             onerror="this.onerror=null; this.src='{{ $qrData['url'] ?? '' }}';"
             class="w-{{ $size === 'small' ? '48' : ($size === 'large' ? '64' : '56') }} h-{{ $size === 'small' ? '48' : ($size === 'large' ? '64' : '56') }} rounded-lg border-2 border-gray-200 dark:border-slate-800 transition-all duration-200 hover:border-blue-500 dark:hover:border-blue-400">

        {{-- Hover overlay --}}
        <div class="absolute inset-0 bg-blue-500 bg-opacity-0 hover:bg-opacity-10 rounded-lg transition-all duration-200 flex items-center justify-center opacity-0 hover:opacity-100">
            <i class="fas fa-qrcode text-blue-600 dark:text-blue-400 text-2xl"></i>
        </div>
    </div>

    {{-- Label --}}
    @if($showLabel)
    <div class="text-center">
        <p class="text-sm font-semibold text-gray-900 dark:text-slate-100 mb-1">
            @if($type === 'whatsapp' || $showWhatsApp)
                WhatsApp ile Paylaş
            @else
                İlanı Paylaş
            @endif
        </p>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            QR kodu tarayarak ilana erişin
        </p>
    </div>
    @endif

    {{-- Actions --}}
    @if($showDownload)
    <div class="flex gap-2 mt-2">
        <button @click="showDownload = !showDownload"
                class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-all duration-200 hover:scale-105 active:scale-95 flex items-center gap-1.5">
            <i class="fas fa-download"></i>
            <span>İndir</span>
        </button>

        <button onclick="copyQRCode('{{ $qrData['base64'] }}')"
                class="px-3 py-1.5 text-xs font-medium text-gray-700 dark:text-slate-200 bg-gray-100 dark:bg-slate-800 hover:bg-gray-200 dark:hover:bg-slate-700 rounded-lg transition-all duration-200 hover:scale-105 active:scale-95 flex items-center gap-1.5">
            <i class="fas fa-copy"></i>
            <span>Kopyala</span>
        </button>
    </div>
    @endif

    {{-- Download Options (Hidden by default) --}}
    <div x-show="showDownload"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 transform scale-95"
         x-transition:enter-end="opacity-100 transform scale-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 transform scale-100"
         x-transition:leave-end="opacity-0 transform scale-95"
         class="w-full mt-2 p-3 bg-gray-50 dark:bg-slate-800 rounded-lg border border-gray-200 dark:border-slate-700">
        <div class="flex flex-col gap-2">
            <a href="{{ $qrData['url'] }}"
               download="{{ $qrData['filename'] }}"
               class="px-3 py-2 text-xs font-medium text-center text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/30 transition-colors">
                <i class="fas fa-download mr-1"></i>
                PNG Olarak İndir
            </a>
        </div>
    </div>
</div>

<script>
function copyQRCode(base64) {
    // Create a temporary canvas to convert base64 to blob
    const img = new Image();
    img.onload = function() {
        const canvas = document.createElement('canvas');
        canvas.width = img.width;
        canvas.height = img.height;
        const ctx = canvas.getContext('2d');
        ctx.drawImage(img, 0, 0);

        canvas.toBlob(function(blob) {
            const item = new ClipboardItem({ 'image/png': blob });
            navigator.clipboard.write([item]).then(() => {
                // Show toast notification
                if (typeof showToast === 'function') {
                    showToast('QR kod panoya kopyalandı!', 'success');
                } else {
                    alert('QR kod panoya kopyalandı!');
                }
            }).catch(err => {
                console.error('Copy failed:', err);
                alert('QR kod kopyalanamadı');
            });
        });
    };
    img.src = base64;
}
</script>
@else
<div class="qr-code-display-error text-center p-4 bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800">
    <p class="text-sm text-red-600 dark:text-red-400">
        <i class="fas fa-exclamation-circle mr-2"></i>
        QR kod oluşturulamadı
    </p>
</div>
@endif
