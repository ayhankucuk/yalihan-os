<?php

namespace App\Helpers;

use Carbon\Carbon;
use Exception;

/**
 * Date Parser Helper
 *
 * Context7 Compliance: Tarih parse yardımcı sınıfı
 * Telegram ve Admin ortak kullanım
 */
class DateParser
{
    /**
     * Parse tarih aralığı
     *
     * Desteklenen formatlar:
     * - "2026-06-01 14:00 - 2026-06-05 11:00"
     * - "01.06.2026 14:00 - 05.06.2026 11:00"
     * - "2026-06-01T14:00 - 2026-06-05T11:00"
     *
     * @param string $text
     * @return array{from: Carbon, to: Carbon}
     * @throws Exception
     */
    public static function parseDateRange(string $text): array
    {
        $text = trim($text);

        // Pattern 1: "YYYY-MM-DD HH:MM - YYYY-MM-DD HH:MM"
        if (preg_match('/^(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})\s*-\s*(\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2})$/', $text, $matches)) {
            return [
                'from' => Carbon::parse($matches[1]),
                'to' => Carbon::parse($matches[2]),
            ];
        }

        // Pattern 2: "DD.MM.YYYY HH:MM - DD.MM.YYYY HH:MM"
        if (preg_match('/^(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2})\s*-\s*(\d{2}\.\d{2}\.\d{4}\s+\d{2}:\d{2})$/', $text, $matches)) {
            return [
                'from' => Carbon::createFromFormat('d.m.Y H:i', $matches[1]),
                'to' => Carbon::createFromFormat('d.m.Y H:i', $matches[2]),
            ];
        }

        // Pattern 3: ISO format "YYYY-MM-DDTHH:MM - YYYY-MM-DDTHH:MM"
        if (preg_match('/^(\d{4}-\d{2}-\d{2}T\d{2}:\d{2})\s*-\s*(\d{4}-\d{2}-\d{2}T\d{2}:\d{2})$/', $text, $matches)) {
            return [
                'from' => Carbon::parse($matches[1]),
                'to' => Carbon::parse($matches[2]),
            ];
        }

        throw new Exception('Geçersiz tarih formatı. Örnek: 2026-06-01 14:00 - 2026-06-05 11:00');
    }

    /**
     * Parse tek tarih
     *
     * @param string $text
     * @return Carbon
     * @throws Exception
     */
    public static function parseDate(string $text): Carbon
    {
        $text = trim($text);

        // Try standard formats
        try {
            return Carbon::parse($text);
        } catch (Exception $e) {
            // Try Turkish format
            if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $text, $matches)) {
                return Carbon::createFromFormat('d.m.Y', $text);
            }
            throw new Exception('Geçersiz tarih formatı: ' . $text);
        }
    }
}
