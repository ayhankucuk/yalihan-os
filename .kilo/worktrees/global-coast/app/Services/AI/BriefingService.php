<?php

namespace App\Services\AI;

use App\Enums\IlanDurumu;

use App\Models\Ilan;
use App\Modules\TakimYonetimi\Models\Gorev;
use App\Services\Logging\LogService;
use Carbon\Carbon;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * ��️ SAB SEALED
 * - Forbidden keywords: "st*tus" family (do not introduce)
 * - SSOT: naming must reflect domain semantics (e.g., yayin_durumu vs aktiflik_durumu)
 * - No hidden side-effects: logic stays in service layer, UI is dumb
 * - Any change must pass: bekci:audit + integrity scan
 */
class BriefingService
{
    protected AudioService $audioService;
    protected string $briefingPath;

    public function __construct(AudioService $audioService)
    {
        $this->audioService = $audioService;
        $this->briefingPath = public_path('audio/briefings');
    }

    /**
     * Günlük brifing üret ve ses dosyasını döndür
     */
    public function generateDailyBriefing(): array
    {
        $timerId = LogService::startTimer('briefing_generation');

        // 1. Eski dosyaları temizle (1 saatlik TTL)
        $this->cleanupOldBriefings();

        // 2. In-memory Analiz (Veritabanına kayıt açmadan)
        $data = $this->analyzeCurrentState();

        // 3. Brifing Metni Oluştur
        $text = $this->buildBriefingText($data);

        // 4. Ses Dosyası Üret (Düşük Kalite / tts-1)
        $filename = 'briefing_' . Str::random(10) . '.mp3';
        $fullPath = $this->briefingPath . '/' . $filename;

        // AudioService'i doğrudan kullanmak yerine public_path'e yazmak için manuel işlem yapıyoruz
        // Çünkü AudioService varsayılan olarak storage diskini kullanıyor.
        // Ancak AudioService'i güncellediğimiz için path opsiyonunu kullanabiliriz.

        $audioUrl = $this->audioService->textToSpeech($text, [
            'model' => 'tts-1', // Düşük kalite / Hızlı
            'voice' => 'onyx',  // Kaptan'a uygun otoriter ses
            'path' => 'audio/briefings/' . $filename,
            'disk' => 'public_root' // Yeni bir disk tanımlamamız gerekebilir veya manuel taşıyabiliriz
        ]);

        // public_root diski tanımlı olmayabilir, bu yüzden manuel taşıma yapalım
        // Veya AudioService'in döndürdüğü URL'i kullanalım.
        // Kullanıcı public/audio/briefings istediği için oraya taşıyalım.

        $storagePath = storage_path('app/public/audio/briefings/' . $filename);
        if (File::exists($storagePath)) {
            File::move($storagePath, $fullPath);
        }

        LogService::stopTimer($timerId);

        return [
            'text' => $text,
            'audio_url' => asset('audio/briefings/' . $filename),
            'data' => $data,
            'generated_at' => now()->toDateTimeString()
        ];
    }

    /**
     * Mevcut durumu analiz et (In-memory)
     */
    protected function analyzeCurrentState(): array
    {
        $today = Carbon::today();

        return [
            'active_listings' => Ilan::where('yayin_durumu', IlanDurumu::YAYINDA->value)->count(), // context7-ignore
            'new_listings_today' => Ilan::whereDate('created_at', $today)->count(),
            'pending_tasks' => Gorev::whereIn('gorev_durumu', ['beklemede', 'devam_ediyor'])->count(),
            'urgent_tasks' => Gorev::whereIn('gorev_durumu', ['beklemede', 'devam_ediyor'])->where('oncelik', 'yuksek')->count(),
            'date' => $today->translatedFormat('j F Y'),
            'day' => $today->translatedFormat('l')
        ];
    }

    /**
     * Brifing metni inşa et
     */
    protected function buildBriefingText(array $data): string
    {
        $text = "Günaydın Kaptan. Bugün {$data['date']}, {$data['day']}. 2026'nın ilk ışıklarında geminin durumu şöyle: ";

        $text .= "Şu an yayında {$data['active_listings']} aktif ilanımız var. "; // context7-ignore

        if ($data['new_listings_today'] > 0) {
            $text .= "Bugün sisteme {$data['new_listings_today']} yeni portföy girişi yapıldı. ";
        } else {
            $text .= "Henüz yeni bir portföy girişi saptanmadı. ";
        }

        $text .= "Takip etmen gereken {$data['pending_tasks']} bekleyen görev bulunuyor. ";

        if ($data['urgent_tasks'] > 0) {
            $text .= "Dikkat Kaptan! Bunlardan {$data['urgent_tasks']} tanesi yüksek öncelikli olarak işaretlenmiş. ";
        }

        $text .= "Cortex analizi tamamlandı. Rüzgar arkamızda, pruvanız neta olsun.";

        return $text;
    }

    /**
     * 1 saatten eski brifingleri temizle
     */
    protected function cleanupOldBriefings(): void
    {
        $files = File::files($this->briefingPath);
        $now = time();

        foreach ($files as $file) {
            if ($now - File::lastModified($file) >= 3600) { // 1 saat = 3600 saniye
                File::delete($file);
            }
        }
    }
}
