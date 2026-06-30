<?php

namespace App\Services;

use App\Models\Ilan;
use App\Models\IlanNot;
use App\Services\Cortex\CortexPitchGenerator;
use Illuminate\Support\Facades\Log;

/**
 * Service for generating and managing sales pitches for listings.
 */
class PitchService
{
    protected CortexPitchGenerator $generator;

    public function __construct(CortexPitchGenerator $generator)
    {
        $this->generator = $generator;
    }

    /**
     * Generate a pitch for a listing, save it to notes, and return it.
     *
     * @param int|Ilan $ilan
     * @param string $channel telegram|whatsapp|email|sms
     * @param int|null $userId Optional user ID to associate with the note (AI if null)
     * @return array
     */
    public function generateAndStorePitch($ilan, string $channel = 'telegram', ?int $userId = null): array
    {
        if (is_numeric($ilan)) {
            $ilan = Ilan::findOrFail($ilan);
        }

        // 1. Generate Pitch
        $result = $this->generator->generatePitch($ilan, $channel);

        if (!$result['success']) {
             return [
                 'success' => false,
                 'message' => 'Pitch generasyonu başarısız oldu.',
                 'error' => $result['error'] ?? 'Unknown error',
             ];
        }

        // 2. Context7 Sanitize
        $pitchText = $this->sanitizeOutput($result['content']);

        // 3. Store in IlanNot (Mühürlü Kayıt)
        try {
            $note = IlanNot::create([
                'ilan_id' => $ilan->id,
                'user_id' => $userId, // NULL = AI
                'not_icerigi' => $pitchText,
                'not_tipi' => 'pitch',
                'channel' => $channel,
                'is_ai_generated' => true,
                'onemli_mi' => true,
            ]);

            return [
                'success' => true,
                'pitch' => $pitchText,
                'note_id' => $note->id,
                'channel' => $channel,
            ];

        } catch (\Exception $e) {
            Log::error("PitchService Store Error: " . $e->getMessage());
            return [
                'success' => true, // Generation successful even if storage failed
                'pitch' => $pitchText,
                'warning' => 'Pitch üretildi fakat veritabanına kaydedilemedi.',
            ];
        }
    }

    /**
     * Alias for autonomous command usage
     */
    public function generatePitchForOpportunity($ilan, string $channel = 'telegram')
    {
        return $this->generateAndStorePitch($ilan, $channel);
    }

    /**
     * Context7 Sanitizer
     * Ensures forbidden technical terms do not leak into business text.
     */
    private function sanitizeOutput(string $text): string
    {
        // Replace accidentally leaked technical field names if they appear in raw format
        $forbidden = [
            // context7-ignore
            'is_active' => 'aktif',
            'deleted_at' => 'silinme tarihi',
            'user_id' => 'kullanıcı',
        ];

        foreach ($forbidden as $technical => $business) {
             // Basic check to avoid replacing valid English words if the pitch is English (though it's likely Turkish)
             // We target specific raw technical patterns like "durum: active"
             $text = preg_replace("/\b{$technical}\b/i", $business, $text);
        }

        return trim($text);
    }
}
