<?php

declare(strict_types=1);

namespace App\Services\Telegram\Processors;

use App\Models\User;
use App\Services\AudioTranscriptionService;
use App\Services\AI\YalihanCortex;
use App\Services\Logging\LogService;
use App\Services\Telegram\AlertService;
use App\Http\Controllers\Api\MatchController;
use Illuminate\Support\Facades\Log;

/**
 * VoiceProcessor
 *
 * Context7 Standard: C7-TELEGRAM-VOICE-PROCESSOR-2026-01-04
 *
 * Voice mesajını işler:
 * 1. Telegram'dan voice dosyasını indir
 * 2. Whisper ile transkribe et
 * 3. YalihanCortex ile draft oluştur
 * 4. Müşteri matching (top 3)
 * 5. Interactive message gönder
 *
 * Timeline: 30 saniye ses → 5 saniyede draft hazır
 * 
 * @package App\Services\Telegram\Processors
 * @version 1.0.0
 */
class VoiceProcessor
{
    private AudioTranscriptionService $audioService;
    private YalihanCortex $cortex;
    private AlertService $alertService;

    public function __construct(
        AudioTranscriptionService $audioService,
        YalihanCortex $cortex,
        AlertService $alertService
    ) {
        $this->audioService = $audioService;
        $this->cortex = $cortex;
        $this->alertService = $alertService;
    }

    /**
     * Voice mesajını process et: transcribe → draft → matching → alerts
     *
     * İş Akışı:
     * Step 1: Telegram'dan voice indir
     * Step 2: Whisper ile transkribe et
     * Step 3: YalihanCortex ile draft oluştur
     * Step 4: MatchController ile müşteri eşleştir (top 3)
     * Step 5: Interactive message oluştur
     *
     * @param int $chatId Telegram chat ID
     * @param array $voiceData Telegram voice array: ['file_id', 'file_unique_id', 'duration']
     * @param User $user İşlemi yapan danışman
     * @param string $botToken Telegram bot token
     *
     * @return array [
     *     'success' => bool,
     *     'talep' => ['id' => int, 'baslik' => string, ...],
     *     'kisi' => ['id' => int, 'ad_soyad' => string, ...],
     *     'matches' => [['id' => int, ...], ...],  // Top 3 eşleşme
     *     'message' => ['text' => string, 'reply_markup' => array],
     *     'error' => string (eğer success false ise)
     * ]
     */
    public function processVoiceMessage(
        int $chatId,
        array $voiceData,
        User $user,
        string $botToken
    ): array {
        $timerId = LogService::startTimer('telegram_voice_process');

        try {
            // ✅ Step 1: Telegram'dan voice indir
            Log::info('VoiceProcessor: Voice indiriliyor', [
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'file_id' => $voiceData['file_id'] ?? 'unknown',
                'duration' => $voiceData['duration'] ?? 0,
            ]);

            $fileId = $voiceData['file_id'] ?? null;
            if (!$fileId) {
                return $this->errorResponse('Voice file_id bulunamadı');
            }

            $audioPath = $this->audioService->downloadTelegramVoice($fileId, $botToken);

            LogService::info('Voice downloaded', [
                'file_id' => $fileId,
                'local_path' => $audioPath,
                'duration_ms' => LogService::stopTimer($timerId),
            ]);

            // ✅ Step 2: Whisper ile transkribe et
            Log::info('VoiceProcessor: Voice transkribe ediliyor', [
                'user_id' => $user->id,
                'audio_path' => $audioPath,
            ]);

            $transcript = $this->audioService->transcribe($audioPath);

            if (empty($transcript)) {
                return $this->errorResponse('Ses transkripti boş döndü. Lütfen daha net bir ses gönderin.');
            }

            LogService::info('Voice transcribed', [
                'transcript_length' => strlen($transcript),
                'transcript_preview' => substr($transcript, 0, 100),
                'duration_ms' => LogService::stopTimer($timerId),
            ]);

            Log::info('VoiceProcessor: Transkript başarılı', [
                'user_id' => $user->id,
                'transcript_length' => strlen($transcript),
                'preview' => substr($transcript, 0, 100),
            ]);

            // ✅ Step 3: YalihanCortex ile draft oluştur
            Log::info('VoiceProcessor: Draft oluşturuluyor', [
                'user_id' => $user->id,
                'transcript_length' => strlen($transcript),
            ]);

            $draftResult = $this->cortex->createDraftFromText($transcript, $user->id);

            if (!isset($draftResult['success']) || !$draftResult['success']) {
                return $this->errorResponse(
                    $draftResult['error'] ?? 'Draft oluşturma başarısız oldu'
                );
            }

            $talep = $draftResult['talep'] ?? [];
            $kisi = $draftResult['kisi'] ?? [];
            $taleId = $talep['id'] ?? null;

            LogService::info('Draft created', [
                'talep_id' => $taleId,
                'kisi_id' => $kisi['id'] ?? null,
                'confidence' => $draftResult['confidence'] ?? 'unknown',
                'duration_ms' => LogService::stopTimer($timerId),
            ]);

            Log::info('VoiceProcessor: Draft oluşturuldu', [
                'user_id' => $user->id,
                'talep_id' => $taleId,
                'kisi_id' => $kisi['id'] ?? null,
            ]);

            // ✅ Step 4: MatchController ile müşteri eşleştir (top 3)
            $matches = [];
            if ($taleId) {
                try {
                    Log::info('VoiceProcessor: Matching başlanıyor', [
                        'talep_id' => $taleId,
                    ]);

                    // MatchController::findForDemand() çağırıyoruz
                    $matchController = app(MatchController::class);
                    $matchResult = $matchController->findForDemand([
                        'talep_id' => $taleId,
                        'limit' => 3,
                    ]);

                    // Response'i parse et
                    if (method_exists($matchResult, 'getData')) {
                        $matches = $matchResult->getData()['data'] ?? [];
                    } elseif (is_array($matchResult)) {
                        $matches = $matchResult['data'] ?? [];
                    }

                    LogService::info('Matches found', [
                        'talep_id' => $taleId,
                        'match_count' => count($matches),
                        'duration_ms' => LogService::stopTimer($timerId),
                    ]);

                    Log::info('VoiceProcessor: Matching tamamlandı', [
                        'talep_id' => $taleId,
                        'match_count' => count($matches),
                    ]);
                } catch (\Exception $e) {
                    Log::warning('VoiceProcessor: Matching hatası (devam ediliyor)', [
                        'talep_id' => $taleId,
                        'error' => $e->getMessage(),
                    ]);
                    // Matching başarısız olsa da devam et
                }
            }

            // ✅ Step 5: Interactive message oluştur
            $interactiveMessage = $this->buildInteractiveMessage($talep, $kisi, $matches);

            LogService::info('Interactive message built', [
                'talep_id' => $taleId,
                'has_buttons' => !empty($interactiveMessage['reply_markup']),
                'duration_ms' => LogService::stopTimer($timerId),
            ]);

            // Cleanup: Geçici ses dosyasını sil
            try {
                $this->audioService->cleanup($audioPath);
            } catch (\Exception $e) {
                Log::warning('VoiceProcessor: Cleanup hatası', [
                    'audio_path' => $audioPath,
                    'error' => $e->getMessage(),
                ]);
            }

            LogService::stopTimer($timerId, [
                'talep_id' => $taleId,
                'matches' => count($matches),
            ]);

            return [
                'success' => true,
                'talep' => $talep,
                'kisi' => $kisi,
                'matches' => $matches,
                'message' => $interactiveMessage,
                'confidence' => $draftResult['confidence'] ?? 'unknown',
            ];
        } catch (\Exception $e) {
            Log::error('VoiceProcessor: Kritik hata', [
                'chat_id' => $chatId,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            LogService::stopTimer($timerId, ['error' => $e->getMessage()]);

            return $this->errorResponse(
                "Ses işleme sırasında hata oluştu: {$e->getMessage()}"
            );
        }
    }

    /**
     * Interactive message oluştur
     *
     * Yapı:
     * ✅ Draft Oluşturuldu!
     * 📋 Talep: {baslik}
     * 👤 Kişi: {ad_soyad}
     * 🎯 Benzer İlanlar: {match_count}
     *
     * [Düzenle] [Yayınla] [TKGM Doldur]
     *
     * @param array $talep Draft talep
     * @param array $kisi İlişkili kişi
     * @param array $matches Benzer ilanlar
     *
     * @return array ['text' => string, 'reply_markup' => array]
     */
    private function buildInteractiveMessage(array $talep, array $kisi, array $matches): array
    {
        $taleId = $talep['id'] ?? null;
        $text = "✅ *Draft Oluşturuldu!*\n\n";

        if ($taleId) {
            $text .= "📋 *Talep Detayları:*\n";
            $text .= "• Başlık: `" . ($talep['baslik'] ?? 'Belirtilmedi') . "`\n";
            $text .= "• Bütçe: `" . ($talep['butce_min'] ?? '—') . " - " . ($talep['butce_max'] ?? '—') . "`\n";

            if (!empty($talep['lokasyon'])) {
                $text .= "• Lokasyon: `{$talep['lokasyon']}`\n";
            }

            if (!empty($talep['alan_m2'])) {
                $text .= "• Alan: `{$talep['alan_m2']}` m²\n";
            }

            $text .= "\n";
        }

        if (!empty($kisi)) {
            $text .= "👤 *Kişi Detayları:*\n";
            $text .= "• Ad: `" . ($kisi['ad_soyad'] ?? 'Belirtilmedi') . "`\n";

            if (!empty($kisi['telefon'])) {
                $text .= "• Telefon: `{$kisi['telefon']}`\n";
            }

            if (!empty($kisi['email'])) {
                $text .= "• Email: `{$kisi['email']}`\n";
            }

            $text .= "\n";
        }

        if (!empty($matches)) {
            $text .= "🎯 *Benzer İlanlar:* `" . count($matches) . "` bulundu\n";
            $text .= "_Top 3 müşteri önerisiyle kontrol edebilirsiniz._\n\n";
        }

        $text .= "📌 *Bir işlem seçin:*";

        // Interactive buttons (Inline Keyboard)
        $replyMarkup = [
            'inline_keyboard' => [
                [
                    [
                        'text' => '✏️ Düzenle',
                        'callback_data' => json_encode([
                            'action' => 'edit_draft',
                            'talep_id' => $taleId,
                            'type' => 'talep', // context7-ignore
                        ]),
                    ],
                    [
                        'text' => '✅ Yayınla',
                        'callback_data' => json_encode([
                            'action' => 'publish',
                            'talep_id' => $taleId,
                        ]),
                    ],
                ],
                [
                    [
                        'text' => '📋 TKGM Doldur',
                        'callback_data' => json_encode([
                            'action' => 'tkgm_fill',
                            'talep_id' => $taleId,
                        ]),
                    ],
                ],
            ],
        ];

        return [
            'text' => $text,
            'reply_markup' => $replyMarkup,
            'parse_mode' => 'Markdown',
        ];
    }

    /**
     * Hata response oluştur
     */
    private function errorResponse(string $error): array
    {
        return [
            'success' => false,
            'error' => $error,
            'message' => [
                'text' => "❌ *Hata*\n\n{$error}\n\nLütfen tekrar deneyin veya destek ekibine başvurun.",
                'reply_markup' => null,
            ],
        ];
    }
}
