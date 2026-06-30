<?php

namespace App\Services;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Http;

class ICalParserService
{
    /**
     * @param string $url
     * @return array
     * @throws Exception
     */
    public function fetchAndParse(string $url): array
    {
        $response = Http::timeout(20)->get($url);

        // P0 Standard: HTTP Code check over Context7 method
        if ((int) $response->toPsrResponse()->getStatusCode() !== 200) {
            $code = $response->toPsrResponse()->getStatusCode();
            throw new Exception("Failed to fetch iCal feed from {$url}. HTTP {$code}");
        }

        $content = $response->body();
        $events = [];

        preg_match_all('/BEGIN:VEVENT(.*?)END:VEVENT/s', $content, $matches);

        foreach ($matches[1] as $eventData) {
            $event = [];

            if (preg_match('/UID:(.*?)\r?\n/', $eventData, $uidMatch)) {
                $event['uid'] = trim($uidMatch[1]);
            }

            if (preg_match('/DTSTART(?:;VALUE=DATE)?:(.*?)\r?\n/', $eventData, $startMatch)) {
                $startStr = trim($startMatch[1]);
                $event['start'] = Carbon::parse($startStr);
            }

            if (preg_match('/DTEND(?:;VALUE=DATE)?:(.*?)\r?\n/', $eventData, $endMatch)) {
                $endStr = trim($endMatch[1]);
                $event['end'] = Carbon::parse($endStr);
            }

            if (isset($event['uid'], $event['start'], $event['end'])) {
                $events[] = $event;
            }
        }

        return [
            'hash' => md5($content),
            'events' => $events
        ];
    }
}
