<?php

/**
 * Event Helper - Merkezi Event Yönetimi
 *
 * Context7 Standard: C7-EVENT-HELPER-2025-12-06
 * Yalıhan Bekçi: Temiz, düzenli, merkezi yönetim
 *
 * Event dispatch ve metadata yönetimi için helper sınıfı.
 *
 * @version 1.0.0
 * @since 2025-12-06
 */

namespace App\Helpers;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;

class EventHelper
{
    /**
     * Event tanımını al
     *
     * @param string $eventKey Event key (örn: 'ilan.created')
     * @return array|null Event tanımı
     */
    public static function getDefinition(string $eventKey): ?array
    {
        $definitions = config('events.definitions', []);

        return $definitions[$eventKey] ?? null;
    }

    /**
     * Event class'ını al
     *
     * @param string $eventKey Event key
     * @return string|null Event class adı
     */
    public static function getClass(string $eventKey): ?string
    {
        $definition = self::getDefinition($eventKey);

        return $definition['class'] ?? null;
    }

    /**
     * Event listener'larını al
     *
     * @param string $eventKey Event key
     * @return array Listener class'ları
     */
    public static function getListeners(string $eventKey): array
    {
        $definition = self::getDefinition($eventKey);

        return $definition['listeners'] ?? [];
    }

    /**
     * Event'i dispatch et
     *
     * @param string $eventKey Event key
     * @param mixed ...$args Event constructor parametreleri
     * @return void
     * @throws \InvalidArgumentException
     */
    public static function dispatch(string $eventKey, ...$args): void
    {
        $class = self::getClass($eventKey);

        if (!$class) {
            throw new \InvalidArgumentException("Event tanımı bulunamadı: {$eventKey}");
        }

        if (!class_exists($class)) {
            throw new \InvalidArgumentException("Event class bulunamadı: {$class}");
        }

        $event = new $class(...$args);
        Event::dispatch($event);

        Log::debug('Event dispatched', [
            'event_key' => $eventKey,
            'event_class' => $class,
        ]);
    }

    /**
     * Event metadata'sını al
     *
     * @param string $eventKey Event key
     * @return array Event metadata
     */
    public static function getMetadata(string $eventKey): array
    {
        $definition = self::getDefinition($eventKey);

        if (!$definition) {
            return [];
        }

        return [
            'key' => $eventKey,
            'class' => $definition['class'] ?? null,
            'description' => $definition['description'] ?? null,
            'category' => $definition['category'] ?? null,
            'broadcast' => $definition['broadcast'] ?? false,
            'queue' => $definition['queue'] ?? false,
            'listeners' => $definition['listeners'] ?? [],
        ];
    }

    /**
     * Kategoriye göre event'leri al
     *
     * @param string $category Kategori adı
     * @return array Event key'leri
     */
    public static function getByCategory(string $category): array
    {
        $definitions = config('events.definitions', []);
        $events = [];

        foreach ($definitions as $key => $definition) {
            if (($definition['category'] ?? null) === $category) {
                $events[] = $key;
            }
        }

        return $events;
    }

    /**
     * Tüm event'leri al
     *
     * @return array Event key'leri
     */
    public static function getAll(): array
    {
        return array_keys(config('events.definitions', []));
    }

    /**
     * Event'in broadcast yapılıp yapılmayacağını kontrol et
     *
     * @param string $eventKey Event key
     * @return bool
     */
    public static function shouldBroadcast(string $eventKey): bool
    {
        $definition = self::getDefinition($eventKey);

        return $definition['broadcast'] ?? false;
    }

    /**
     * Event'in queue'da çalışıp çalışmayacağını kontrol et
     *
     * @param string $eventKey Event key
     * @return bool
     */
    public static function shouldQueue(string $eventKey): bool
    {
        $definition = self::getDefinition($eventKey);

        return $definition['queue'] ?? false;
    }

    /**
     * Broadcast channel adını al
     *
     * @param string $eventKey Event key
     * @param array $params Channel parametreleri
     * @return string|null Channel adı
     */
    public static function getBroadcastChannel(string $eventKey, array $params = []): ?string
    {
        $definition = self::getDefinition($eventKey);
        $category = $definition['category'] ?? null;

        if (!$category) {
            return null;
        }

        $channels = config('events.broadcast_channels', []);
        $channelTemplate = $channels[$category] ?? null;

        if (!$channelTemplate) {
            return null;
        }

        // Template'deki {param} yerlerini değiştir
        foreach ($params as $key => $value) {
            $channelTemplate = str_replace("{{$key}}", $value, $channelTemplate);
        }

        return $channelTemplate;
    }
}
